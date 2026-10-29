<?php

require_once "db.php";

// Demo accounts: name, username, plain password, role
$accounts = [
    ["Demo User",  "user1",  "user123",  "USER"],
    ["Demo Staff", "staff1", "staff123", "STAFF"]
];

foreach ($accounts as $account) {

    $name     = $account[0];
    $username = $account[1];
    $password = $account[2];
    $role     = $account[3];

    // Skip if this username already exists

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "Already exists: $username<br>";
        continue;
    }

    // Hash the password. The real password is never stored.

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare(
        "INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)"
    );
    $insert->bind_param("ssss", $name, $username, $hash, $role);

    if ($insert->execute()) {
        echo "Created: $username ($role)<br>";
    } else {
        echo "Error creating $username: " . $conn->error . "<br>";
    }
}

$conn->close();

?>