<?php

require_once "auth.php";

requireRole("USER");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Smart Queuing System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <h1>Smart Queuing System</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?> (USER)</p>
        <a href="logout.php" class="logout-link">Logout</a>
    </header>

    <main>

        <section class="queue-box">

            <h2>Select Service</h2>

            <input
                type="text"
                id="personName"
                placeholder="Patient / Person Name"
                maxlength="100"
            >

            <select id="service">
                <option value="">-- Select Service --</option>
                <option value="Fee Payment">Fee Payment</option>
                <option value="Admission">Admission</option>
                <option value="Certificate">Certificate</option>
                <option value="General Enquiry">General Enquiry</option>
            </select>

            <div class="priority-option">
                <label>
                    <input type="checkbox" id="priority">
                    Priority Service
                </label>
            </div>

            <button onclick="generateToken()">Get Token</button>
            <a href="my_token.php">
                <button type="button">View My Tokens</button>
            </a>

        </section>

        <section id="tokenSection" class="token-box">

            <h2>Your Token</h2>

            <div id="tokenNumber">---</div>
            <p id="tokenPersonName" class="person-name"></p>

            <p>Currently Serving: <span id="currentToken">---</span></p>
            <p id="currentTokenName" class="person-name"></p>
            <p>People Ahead: <span id="peopleAhead">---</span></p>
            <p>Estimated Waiting Time: <span id="waitingTime">---</span></p>
            <div id="predictionNote"></div>
            <p>Status: <span id="status">---</span></p>

        </section>

        <section class="queue-box">

            <h2>My Tokens</h2>

            <div id="myTokensList">
                <p>No tokens generated yet.</p>
            </div>

            <button onclick="clearAllMyTokens()">Clear My Token List</button>

        </section>

        <section class="queue-box">

            <h2>Live Queue</h2>

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
