<?php
require_once "auth.php";
requireRoleApi("STAFF");
require_once "db.php";

header("Content-Type: application/json");

$type = $_POST["type"] ?? "";


if ($type === "queue") {

    // Remove only WAITING tokens

    if ($conn->query("DELETE FROM tokens WHERE status = 'WAITING'")) {

        echo json_encode([
            "success" => true,
            "message" => "Live queue cleared."
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Unable to clear the live queue."
        ]);
    }

} elseif ($type === "all") {

    // Remove every token and restart IDs from 1

    if ($conn->query("TRUNCATE TABLE tokens")) {

        echo json_encode([
            "success" => true,
            "message" => "All tokens and statistics cleared."
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Unable to clear statistics."
        ]);
    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
}

$conn->close();

?>