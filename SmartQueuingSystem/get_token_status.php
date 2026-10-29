<?php

require_once "db.php";

header("Content-Type: application/json");


// Get token ID

$tokenId = $_GET["token_id"] ?? null;


if (!$tokenId) {

    echo json_encode([
        "success" => false,
        "message" => "Token ID is required."
    ]);

    exit;
}


// Find the token

$sql = "
    SELECT
        tokens.id,
        tokens.token_number,
        tokens.person_name,
        tokens.status,
        tokens.priority,
        tokens.service_id,
        services.service_name,
        services.average_time
    FROM tokens
    INNER JOIN services
        ON tokens.service_id = services.id
    WHERE tokens.id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $tokenId);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Token not found."
    ]);

    exit;
}


$token = $result->fetch_assoc();


// Default values

$peopleAhead = 0;
$waitingTime = 0;
$items = [];
$source = "FIXED";


// Only calculate queue position for waiting tokens

if ($token["status"] === "WAITING") {


    // Currently serving token

    $sql = "
        SELECT
            tokens.service_id,
            tokens.priority,
            services.average_time
        FROM tokens
        INNER JOIN services
            ON tokens.service_id = services.id
        WHERE tokens.status = 'SERVING'
        LIMIT 1
    ";

    $result = $conn->query($sql);


    if ($result->num_rows > 0) {

        $servingToken = $result->fetch_assoc();
        $items[] = [
            "service_id" => intval($servingToken["service_id"]),
            "priority"   => intval($servingToken["priority"])
        ];
        $peopleAhead++;

        $waitingTime += intval($servingToken["average_time"]);
    }


    // Waiting tokens ahead
    // Priority tokens come first.
    // If priority is the same, older tokens come first.

    $sql = "
        SELECT
            tokens.id,
            tokens.service_id,
            tokens.priority,
            services.average_time
        FROM tokens
        INNER JOIN services
            ON tokens.service_id = services.id
        WHERE tokens.status = 'WAITING'
        AND (
            tokens.priority > ?
            OR (
                tokens.priority = ?
                AND tokens.id < ?
            )
        )
        ORDER BY tokens.priority DESC, tokens.id ASC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iii",
        $token["priority"],
        $token["priority"],
        $token["id"]
    );

    $stmt->execute();

    $aheadResult = $stmt->get_result();


    while ($ahead = $aheadResult->fetch_assoc()) {
            $items[] = [
            "service_id" => intval($ahead["service_id"]),
            "priority"   => intval($ahead["priority"])
            ];

        $peopleAhead++;

        $waitingTime += intval($ahead["average_time"]);
    }
}
// ----------------------------------------
// ML PREDICTION (falls back to fixed times)
// ----------------------------------------

if ($token["status"] === "WAITING" && count($items) > 0) {

    foreach ($items as $i => $item) {
        $items[$i]["queue_length"] = $peopleAhead;
    }

    $context = stream_context_create([
        "http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/json\r\n",
            "content"       => json_encode(["items" => $items]),
            "timeout"       => 2,
            "ignore_errors" => true
        ]
    ]);

    $response = @file_get_contents(
        "http://127.0.0.1:5001/predict_batch",
        false,
        $context
    );

    if ($response !== false) {

        $ml = json_decode($response, true);

        if (
            $ml &&
            !empty($ml["success"]) &&
            count($ml["minutes"]) === count($items)
        ) {
            $waitingTime = intval(round(array_sum($ml["minutes"])));
            $source = "ML";
        }
    }
}

// Send result

echo json_encode([

    "success" => true,

    "token" => $token["token_number"],

    "person_name" => $token["person_name"],

    "status" => $token["status"],

    "priority" => intval($token["priority"]),

    "service" => $token["service_name"],

    "people_ahead" => $peopleAhead,

    "waiting_time" => $waitingTime,

    "prediction_source" => $source

]);


$conn->close();

?>