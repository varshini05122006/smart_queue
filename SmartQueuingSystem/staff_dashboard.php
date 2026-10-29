<?php

require_once "auth.php";

requireRole("STAFF");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Smart Queuing System</title>
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

            <h2>Staff Dashboard</h2>

            <div class="current-serving">

                <p>Currently Serving</p>

                <div id="staffCurrentToken">No Token</div>
                <p id="staffCurrentName">-</p>
                <p id="staffCurrentName" class="person-name"></p>
                <p id="staffCurrentService">No Service</p>
                <p id="staffCurrentPriority">Normal</p>

            </div>

            <button onclick="callNextToken()">Call Next Token</button>
            <button onclick="completeToken()">Complete Token</button>
            <button onclick="skipToken()">Skip Token</button>
            <a href="ml_insights.php">
                <button type="button">ML Insights</button>
            </a>
                        
            <a href="serving.php">
                <button type="button">Go to Serving Page</button>
            </a>

        </section>

        <section class="queue-box statistics">

            <h2>Queue Statistics</h2>

            <div class="stat-container">

                <div class="stat-card">
                    <h3 id="totalTokens">0</h3>
                    <p>Total Tokens</p>
                </div>

                <div class="stat-card">
                    <h3 id="waitingTokens">0</h3>
                    <p>Waiting</p>
                </div>

                <div class="stat-card">
                    <h3 id="servingTokens">0</h3>
                    <p>Serving</p>
                </div>

                <div class="stat-card">
                    <h3 id="completedTokens">0</h3>
                    <p>Completed</p>
                </div>

            </div>

            <button class="clear-btn" onclick="clearData('all')">Clear Statistics</button>

        </section>

        <section class="queue-box">

            <h2>Live Queue</h2>

            <div id="queueList">
                <p>No tokens in queue.</p>
            </div>

            <button class="clear-btn" onclick="clearData('queue')">Clear Live Queue</button>

        </section>

    </main>

    <footer>
        <p>Smart Queuing System © 2026</p>
    </footer>

    <script src="script.js"></script>
    <script src="notify.js?v=1"></script>

</body>

</html>
