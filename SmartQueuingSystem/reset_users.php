<?php

// ONE-TIME SCRIPT: rebuilds only the users table. Does NOT touch tokens.
// Delete this file after running it once.

require_once "db.php";

$conn->query("DROP TABLE IF EXISTS users");

$created = $conn->query("
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('USER', 'STAFF') NOT NULL DEFAULT 'USER',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

if (!$created) {
    die("Error creating users table: " . htmlspecialchars($conn->error));
}

// name, username, plain password, role
$accounts = [
    ["Demo User",  "user",  "1234", "USER"],
    ["Demo Staff", "staff", "1234", "STAFF"]
];

foreach ($accounts as $a) {

    $hash = password_hash($a[2], PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $a[0], $a[1], $hash, $a[3]);

    if ($stmt->execute()) {
        echo "Created: " . htmlspecialchars($a[1]) . " (" . $a[3] . ")<br>";
    } else {
        echo "Error creating " . htmlspecialchars($a[1]) . ": " . htmlspecialchars($conn->error) . "<br>";
    }
}

echo "<br>Done. Now delete reset_users.php.";

$conn->close();

?>
