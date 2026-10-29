<?php

require_once "auth.php";

requireRole("STAFF");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Now Serving - Smart Queuing System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <h1>Smart Queuing System</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?> (STAFF)</p>
        <a href="logout.php" class="logout-link">Logout</a>
    </header>

    <main>

        <section class="queue-box staff-dashboard">

            <h2>Now Serving</h2>

            <div class="current-serving">

                <p>Currently Serving</p>

                <div id="staffCurrentToken">No Token</div>
                <p id="staffCurrentName">-</p>
                <p id="staffCurrentService">No Service</p>
                <p id="staffCurrentPriority">Normal</p>

            </div>

            <button onclick="completeToken()">Complete Token</button>
            <button onclick="skipToken()">Skip Token</button>

            <a href="staff_dashboard.php">
                <button type="button">Back to Dashboard</button>
            </a>

        </section>

        <section class="queue-box">

            <h2>Waiting Queue</h2>

            <div id="queueList">
                <p>No tokens in queue.</p>
            </div>

        </section>

    </main>

    <footer>
        <p>Smart Queuing System © 2026</p>
    </footer>

    <script src="script.js"></script>
    <script src="notify.js?v=1"></script>
</body>

</html>