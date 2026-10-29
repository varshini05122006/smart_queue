<?php

$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "";
$name = getenv("DB_NAME") ?: "smart_queue";
$port = intval(getenv("DB_PORT") ?: 3306);

$conn = mysqli_init();

$flags = 0;

// Cloud databases usually require an encrypted connection

if (getenv("DB_SSL") === "1") {
    $conn->ssl_set(null, null, null, null, null);
    $flags = MYSQLI_CLIENT_SSL;
}

try {

    $connected = @$conn->real_connect($host, $user, $pass, $name, $port, null, $flags);

} catch (Throwable $e) {

    $connected = false;
}

if (!$connected) {

    http_response_code(500);

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;
}

$conn->set_charset("utf8mb4");

?>