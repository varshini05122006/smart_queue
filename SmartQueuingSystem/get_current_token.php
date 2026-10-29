<?php

require_once "db.php";

header("Content-Type: application/json");


// Find the token currently being served

$sql = "
    SELECT
        tokens.id,
        tokens.token_number,
        tokens.person_name,
        tokens.priority,
        services.service_name
    FROM tokens
    INNER JOIN services
        ON tokens.service_id = services.id
    WHERE tokens.status = 'SERVING'
    LIMIT 1
";

$result = $conn->query($sql);


if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to check current token."
    ]);

    $conn->close();

    exit;
}


// No token is currently serving

if ($result->num_rows === 0) {

    echo json_encode([

        "success" => true,

        "serving" => false

    ]);

    $conn->close();

    exit;
}


// Get current serving token

$token = $result->fetch_assoc();


echo json_encode([

    "success" => true,

    "serving" => true,

    "id" => $token["id"],

    "token" => $token["token_number"],

    "person_name" => $token["person_name"],

    "service" => $token["service_name"],

    "priority" => intval($token["priority"])

]);


$conn->close();

?>