<?php

require_once "db.php";


// Waiting tokens

$sql = "
    SELECT COUNT(*) AS waiting
    FROM tokens
    WHERE status = 'WAITING'
";

$result = $conn->query($sql);
$waiting = $result->fetch_assoc()["waiting"];


// Serving tokens

$sql = "
    SELECT COUNT(*) AS serving
    FROM tokens
    WHERE status = 'SERVING'
";

$result = $conn->query($sql);
$serving = $result->fetch_assoc()["serving"];


// Completed tokens

$sql = "
    SELECT COUNT(*) AS completed
    FROM tokens
    WHERE status = 'COMPLETED'
";

$result = $conn->query($sql);
$completed = $result->fetch_assoc()["completed"];


// Total active tokens
// Total = Waiting + Serving

$total = $waiting + $serving;


// Send statistics as JSON

header("Content-Type: application/json");

echo json_encode([

    "success" => true,

    "total" => $total,

    "waiting" => $waiting,

    "serving" => $serving,

    "completed" => $completed

]);


$conn->close();

?>