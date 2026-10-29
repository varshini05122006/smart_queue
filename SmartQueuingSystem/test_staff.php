<?php

require_once "auth.php";

requireRole("STAFF");

echo "Welcome STAFF: " . htmlspecialchars($_SESSION["name"]);
echo '<br><a href="logout.php">Logout</a>';

?>