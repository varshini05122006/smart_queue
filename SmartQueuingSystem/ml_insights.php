<?php

require_once "auth.php";

requireRole("STAFF");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ML Insights - Smart Queuing System</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .bar-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .bar-label { width: 130px; font-size: 14px; color: #475569; text-align: left; }
        .bar-track { flex: 1; height: 12px; background: #e8edf5; border-radius: 999px; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #14213d, #1f3157); border-radius: 999px; width: 0; transition: width 0.8s ease; }
        .bar-value { width: 52px; font-size: 14px; font-weight: 700; color: #14213d; text-align: right; }
        .insight-note { font-size: 14px; color: #64748b; margin-top: 14px; }
        .stat-container.three { grid-template-columns: repeat(3, 1fr); }
        @media (max-width: 600px) { .stat-container.three { grid-template-columns: 1fr; } }
    </style>
</head>

<body>

    <header>
        <h1>Smart Queuing System</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?> (STAFF)</p>
        <a href="logout.php" class="logout-link">Logout</a>
    </header>

    <main>

        <section class="queue-box">

            <h2>Waiting-Time Model</h2>

            <div class="stat-container">

                <div class="stat-card">
                    <h3 id="mRows">-</h3>
                    <p>Training Visits</p>
                </div>

                <div class="stat-card">
                    <h3 id="mBaseline">-</h3>
                    <p>Fixed-time error (min)</p>
                </div>

                <div class="stat-card">
                    <h3 id="mModel">-</h3>
                    <p>ML error (min)</p>
                </div>

                <div class="stat-card">
                    <h3 id="mImprove">-</h3>
                    <p>More accurate</p>
                </div>

            </div>

            <p class="insight-note" id="mNote">Loading results...</p>

        </section>

        <section class="queue-box">

            <h2>What The Model Looks At</h2>

            <div id="importanceList"></div>

            <p class="insight-note">
                A longer bar means that factor changes the predicted time more.
            </p>

        </section>

        <section class="queue-box">

            <h2>Model Control</h2>

            <button id="retrainBtn" onclick="retrainModel()">Retrain Model</button>

            <p class="insight-note">
                Retraining learns again from the service_history table.
                Keep the prediction service running while you do this.
            </p>

            <a href="staff_dashboard.php">
                <button type="button">Back to Dashboard</button>
            </a>

        </section>

    </main>

    <footer>
        <p>Smart Queuing System © 2026</p>
    </footer>

    <script src="notify.js?v=1"></script>

    <script>

        function put(id, text) {
            document.getElementById(id).textContent = text;
        }

        function loadMetrics() {

            fetch("ml_api.php?action=metrics")

            .then(function (r) { return r.json(); })

            .then(function (d) {

                if (!d.success) {
                    put("mNote", d.message);
                    return;
                }

                put("mRows", d.rows);
                put("mBaseline", d.baseline_mae);
                put("mModel", d.model_mae);
                put("mImprove", d.improvement + "%");

                put(
                    "mNote",
                    "R2 score " + d.r2 + ". Last trained: " + d.trained_at +
                    ". Error is the average difference between the predicted and actual service time, tested on visits the model had not seen."
                );

                const list = document.getElementById("importanceList");
                list.innerHTML = "";

                const labels = {
                    service_id: "Service type",
                    priority: "Priority",
                    hour_of_day: "Hour of day",
                    day_of_week: "Day of week",
                    queue_length: "Queue length"
                };

                const max = Math.max.apply(null, d.importance.map(function (i) { return i.value; })) || 1;

                d.importance.forEach(function (item) {

                    const row = document.createElement("div");
                    row.className = "bar-row";

                    const label = document.createElement("div");
                    label.className = "bar-label";
                    label.textContent = labels[item.name] || item.name;

                    const track = document.createElement("div");
                    track.className = "bar-track";

                    const fill = document.createElement("div");
                    fill.className = "bar-fill";

                    track.appendChild(fill);

                    const value = document.createElement("div");
                    value.className = "bar-value";
                    value.textContent = Math.round(item.value * 100) + "%";

                    row.appendChild(label);
                    row.appendChild(track);
                    row.appendChild(value);

                    list.appendChild(row);

                    setTimeout(function () {
                        fill.style.width = (item.value / max * 100) + "%";
                    }, 100);
                });
            })

            .catch(function () {
                put("mNote", "Could not load the results.");
            });
        }

        function retrainModel() {

            if (!confirm("Retrain the model now? This takes a few seconds.")) {
                return;
            }

            const button = document.getElementById("retrainBtn");

            button.disabled = true;
            button.textContent = "Training...";

            fetch("ml_api.php?action=retrain", { method: "POST" })

            .then(function (r) { return r.json(); })

            .then(function (d) {

                showToast(d.message, d.success ? "success" : "error");

                if (d.success) {
                    loadMetrics();
                }
            })

            .catch(function () {
                showToast("Unable to retrain the model.", "error");
            })

            .finally(function () {
                button.disabled = false;
                button.textContent = "Retrain Model";
            });
        }

        loadMetrics();

    </script>

</body>

</html>