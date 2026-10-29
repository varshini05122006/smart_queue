<?php
require_once "db.php";
header("Content-Type: application/json");

$sql = "
    SELECT 
        tokens.id,
        tokens.token_number,
        tokens.person_name,
        tokens.priority,
        services.service_name
    FROM tokens
    INNER JOIN services ON tokens.service_id = services.id
    WHERE tokens.status = 'WAITING'
    ORDER BY tokens.priority DESC, tokens.id ASC
";

$result = $conn->query($sql);
$queue = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $queue[] = $row;
    }
}

echo json_encode($queue);
$conn->close();
?>