<?php

require_once "auth.php";
requireRole("STAFF");

require_once "db.php";


// Only add samples to an empty tokens table

$count = $conn->query("SELECT COUNT(*) AS c FROM tokens")->fetch_assoc()["c"];

if ($count > 0) {

    echo "Tokens already exist. Use Clear Statistics on the staff dashboard first.";
    echo '<br><a href="staff_dashboard.php">Back to staff dashboard</a>';

    $conn->close();
    exit;
}


// Sample patients: name, service id, priority (1 = priority)
// Service ids: 1 Fee Payment, 2 Admission, 3 Certificate, 4 General Enquiry

$samples = [
    ["Rahul Sharma",   1, 0],
    ["Priya Nair",     2, 0],
    ["Arjun Reddy",    1, 1],
    ["Divya Menon",    3, 0],
    ["Karthik Iyer",   4, 0],
    ["Sneha Patel",    2, 1],
    ["Mohammed Faiz",  1, 0],
    ["Anitha Raj",     3, 0]
];


// Read each service prefix from the database

$prefixes = [];

$result = $conn->query("SELECT id, prefix FROM services");

while ($row = $result->fetch_assoc()) {
    $prefixes[$row["id"]] = $row["prefix"];
}


// Insert the sample tokens

$counters = [];

$stmt = $conn->prepare(
    "INSERT INTO tokens (token_number, person_name, service_id, priority)
     VALUES (?, ?, ?, ?)"
);

foreach ($samples as $sample) {

    $name      = $sample[0];
    $serviceId = $sample[1];
    $priority  = $sample[2];

    if (!isset($prefixes[$serviceId])) {
        continue;
    }

    $counters[$serviceId] = ($counters[$serviceId] ?? 0) + 1;

    $tokenNumber = $prefixes[$serviceId] .
                   str_pad($counters[$serviceId], 3, "0", STR_PAD_LEFT);

    $stmt->bind_param("ssii", $tokenNumber, $name, $serviceId, $priority);
    $stmt->execute();

    echo "Added: $tokenNumber - " . htmlspecialchars($name) .
         ($priority ? " (PRIORITY)" : "") . "<br>";
}

echo '<br><a href="staff_dashboard.php">Back to staff dashboard</a>';

$conn->close();

?>