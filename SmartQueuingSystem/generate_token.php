<?php
require_once "auth.php";
requireRoleApi("USER");
require_once "db.php";

header("Content-Type: application/json");


// Get data sent from JavaScript

$serviceId = $_POST["service_id"] ?? null;

$priority = $_POST["priority"] ?? 0;

$personName = trim($_POST["person_name"] ?? "");


// Check name

if ($personName === "") {

    echo json_encode([
        "success" => false,
        "message" => "Person name is required."
    ]);

    exit;
}


// Check service

if (!$serviceId) {

    echo json_encode([
        "success" => false,
        "message" => "Service is required."
    ]);

    exit;
}


// Find service information

$sql = "SELECT * FROM services WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $serviceId);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Service not found."
    ]);

    exit;
}


$service = $result->fetch_assoc();


// Get service prefix

$prefix = $service["prefix"];


// Check whether this service currently has active tokens

$sql = "
    SELECT COUNT(*) AS active_count
    FROM tokens
    WHERE service_id = ?
    AND status IN ('WAITING', 'SERVING')
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $serviceId);

$stmt->execute();

$result = $stmt->get_result();

$activeCount = $result->fetch_assoc()["active_count"];


// Decide the next token number

if ($activeCount == 0) {

    /*
        There are no active tokens.

        Check whether all previous tokens
        for this service are completed.
    */

    $sql = "
        SELECT COUNT(*) AS incomplete_count
        FROM tokens
        WHERE service_id = ?
        AND status != 'COMPLETED'
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $serviceId);

    $stmt->execute();

    $result = $stmt->get_result();

    $incompleteCount =
        $result->fetch_assoc()["incomplete_count"];


    if ($incompleteCount == 0) {

        /*
            All previous tokens are completed.

            Start a fresh sequence.
        */

        $nextNumber = 1;

    } else {

        /*
            There are previous tokens that are not
            completed, for example SKIPPED tokens.

            Continue from the highest token number.
        */

        $sql = "
            SELECT token_number
            FROM tokens
            WHERE service_id = ?
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("i", $serviceId);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            $nextNumber = 1;

        } else {

            $lastToken =
                $result->fetch_assoc()["token_number"];

            $lastNumber =
                intval(substr($lastToken, 1));

            $nextNumber =
                $lastNumber + 1;
        }
    }

} else {

    /*
        Active tokens still exist.

        Continue from the highest token number
        already generated for this service.
    */

    $sql = "
        SELECT token_number
        FROM tokens
        WHERE service_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $serviceId);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $nextNumber = 1;

    } else {

        $lastToken =
            $result->fetch_assoc()["token_number"];

        $lastNumber =
            intval(substr($lastToken, 1));

        $nextNumber =
            $lastNumber + 1;
    }
}


// Create token number

$tokenNumber =
    $prefix . str_pad(
        $nextNumber,
        3,
        "0",
        STR_PAD_LEFT
    );


// Insert token (now includes the person's name)

$sql = "
    INSERT INTO tokens
    (token_number, person_name, service_id, priority)
    VALUES (?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssii",
    $tokenNumber,
    $personName,
    $serviceId,
    $priority
);


// Generate token

if ($stmt->execute()) {

    $newTokenId = $stmt->insert_id;

    echo json_encode([

        "success" => true,

        "id" => $newTokenId,

        "token" => $tokenNumber,

        "message" => "Token generated successfully."

    ]);

} else {

    echo json_encode([

        "success" => false,

        "message" => "Failed to generate token."

    ]);

}


$conn->close();

?>