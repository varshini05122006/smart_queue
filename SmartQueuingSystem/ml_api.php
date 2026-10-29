<?php

require_once "auth.php";
requireRoleApi("STAFF");

set_time_limit(130);

$action = $_GET["action"] ?? "";

function mlRequest($path, $method, $timeout) {

    $context = stream_context_create([
        "http" => [
            "method"        => $method,
            "header"        => "Content-Length: 0\r\n",
            "content"       => "",
            "timeout"       => $timeout,
            "ignore_errors" => true
        ]
    ]);

    return @file_get_contents("http://127.0.0.1:5001" . $path, false, $context);
}

if ($action === "metrics") {

    $response = mlRequest("/metrics", "GET", 3);

} elseif ($action === "retrain" && $_SERVER["REQUEST_METHOD"] === "POST") {

    $response = mlRequest("/retrain", "POST", 120);

} else {

    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

if ($response === false) {

    echo json_encode([
        "success" => false,
        "message" => "The prediction service is not running."
    ]);
    exit;
}

echo $response;

?>