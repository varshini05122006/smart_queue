<?php
require_once "auth.php";
requireRoleApi("STAFF");
require_once "db.php";


// Find the currently serving token

$sql = "
    SELECT id, token_number
    FROM tokens
    WHERE status = 'SERVING'
    LIMIT 1
";

$result = $conn->query($sql);


// No serving token

if ($result->num_rows === 0) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "There is no token currently being served."
    ]);

    $conn->close();

    exit;
}


// Get current token

$token = $result->fetch_assoc();


// Mark token as skipped

$sql = "
    UPDATE tokens
    SET
        status = 'SKIPPED'
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $token["id"]
);


if ($stmt->execute()) {

    header("Content-Type: application/json");

    echo json_encode([

        "success" => true,

        "token" => $token["token_number"],

        "message" => "Token skipped successfully."

    ]);

} else {

    header("Content-Type: application/json");

    echo json_encode([

        "success" => false,

        "message" => "Unable to skip token."

    ]);

}


$conn->close();

?>