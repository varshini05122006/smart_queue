<?php

require_once "db.php";

// old username => [new username, new password]
$changes = [
    "user1"  => ["user",  "1234"],
    "staff1" => ["staff", "1234"]
];

foreach ($changes as $oldUsername => $new) {

    $newUsername = $new[0];
    $hash        = password_hash($new[1], PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "UPDATE users SET username = ?, password = ? WHERE username = ?"
    );
    $stmt->bind_param("sss", $newUsername, $hash, $oldUsername);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Updated: $oldUsername is now $newUsername<br>";
    } else {
        echo "Not changed: $oldUsername (not found, or already updated)<br>";
    }
}

$conn->close();

?>