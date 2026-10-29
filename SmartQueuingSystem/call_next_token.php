<?php
require_once "auth.php";
requireRoleApi("STAFF");

require_once "db.php";


// Check if a token is already being served

$sql = "
    SELECT id
    FROM tokens
    WHERE status = 'SERVING'
    LIMIT 1
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "A token is already being served. Complete or skip it first."
    ]);

    $conn->close();

    exit;
}


// Find the next waiting token
// Priority tokens come first.
// If priority is the same, oldest token comes first.

$sql = "
    SELECT
        tokens.id,
        tokens.token_number,
        tokens.person_name,
        tokens.service_id,
        tokens.priority,
        services.service_name
    FROM tokens
    INNER JOIN services
        ON tokens.service_id = services.id
    WHERE tokens.status = 'WAITING'
    ORDER BY tokens.priority DESC, tokens.id ASC
    LIMIT 1
";

$result = $conn->query($sql);


// Check whether a waiting token exists

if ($result->num_rows === 0) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "No waiting tokens."
    ]);

    $conn->close();

    exit;
}


// Get token

$token = $result->fetch_assoc();


// Change token status to SERVING

$sql = "
    UPDATE tokens
    SET
        status = 'SERVING',
        called_at = CURRENT_TIMESTAMP
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $token["id"]
);


// Update token

if ($stmt->execute()) {

    header("Content-Type: application/json");

    echo json_encode([

        "success" => true,

        "id" => $token["id"],

        "token" => $token["token_number"],
        "person_name" => $token["person_name"],
        "service" => $token["service_name"],

        "priority" => $token["priority"],

        "message" => "Token is now being served."

    ]);

} else {

    header("Content-Type: application/json");

    echo json_encode([

        "success" => false,

        "message" => "Unable to call token."

    ]);

}


$conn->close();

?>