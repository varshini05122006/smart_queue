// ========================================
// SMART QUEUING SYSTEM
// DATABASE-DRIVEN JAVASCRIPT
// ========================================


// ========================================
// GLOBAL VARIABLES
// ========================================

let currentToken = null;

// List of token IDs generated in this browser
let myTokenIds = JSON.parse(localStorage.getItem("myTokenIds") || "[]");

let isCallingToken = false;
let isCompletingToken = false;
let isSkippingToken = false;


// ========================================
// HELPER FUNCTIONS
// ========================================

function setText(id, value) {

    const element = document.getElementById(id);

    if (element) {
        element.innerText = value;
    }
}


// Stops a typed name from being treated as HTML

function escapeHtml(text) {

    const div = document.createElement("div");

    div.textContent = text;

    return div.innerHTML;
}


// ========================================
// CLEAR BIG USER TOKEN DISPLAY
// ========================================

function clearMyTokenDisplay() {

    setText("tokenNumber", "—");
    setText("tokenPersonName", "");
    setText("peopleAhead", "—");
    setText("waitingTime", "—");
    setText("status", "—");
}


// ========================================
// SERVICE ID MAP (temporary)
// ========================================

function getServiceId(serviceName) {

    const serviceIds = {
        "Fee Payment": 1,
        "Admission": 2,
        "Certificate": 3,
        "General Enquiry": 4
    };

    return serviceIds[serviceName];
}


// ========================================
// GENERATE TOKEN
// ========================================

function generateToken() {

    const serviceElement = document.getElementById("service");
    const priorityElement = document.getElementById("priority");
    const nameElement = document.getElementById("personName");

    const personName = nameElement ? nameElement.value.trim() : "";

    if (personName === "") {
        alert("Please enter the person's name.");
        return;
    }

    if (!serviceElement) {
        return;
    }

    const service = serviceElement.value;

    if (service === "") {
        alert("Please select a service.");
        return;
    }

    const serviceId = getServiceId(service);

    if (!serviceId) {
        alert("Invalid service selected.");
        return;
    }

    const isPriority = priorityElement ? priorityElement.checked : false;

    const formData = new FormData();

    formData.append("service_id", serviceId);
    formData.append("person_name", personName);
    formData.append("priority", isPriority ? 1 : 0);

    fetch("generate_token.php", {
        method: "POST",
        body: formData
    })

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Server returned an error.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!data.success) {
            alert(data.message);
            return;
        }

        // Remember this token's database ID

        myTokenIds.push(data.id);

        localStorage.setItem(
            "myTokenIds",
            JSON.stringify(myTokenIds)
        );

        // Display token immediately

        setText("tokenNumber", data.token);
        setText("tokenPersonName", personName);
        setText("status", "WAITING");
        setText("peopleAhead", "...");
        setText("waitingTime", "...");

        if (priorityElement) {
            priorityElement.checked = false;
        }

        if (nameElement) {
            nameElement.value = "";
        }

        // Refresh everything

        loadCurrentServing();
        loadMyTokenStatus();
        displayQueue();
        loadStatistics();

        showToast("Token " + data.token + " generated successfully!", "success");
        window.location.href = "my_token.php";
    })

    .catch(function(error) {

        console.error("Token generation error:", error);

        alert("Unable to generate token.");
    });
}


// ========================================
// LOAD CURRENTLY SERVING TOKEN
// ========================================

function loadCurrentServing() {

    fetch("get_current_token.php")

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Unable to load current token.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!data.success) {
            console.error("Current token error:", data.message);
            return;
        }

        // ---------- NO TOKEN SERVING ----------

        if (!data.serving) {

            currentToken = null;
            
            // On the serving page with nothing to serve: go back

            if (window.location.pathname.endsWith("serving.php")) {
                window.location.href = "staff_dashboard.php";
                return;
            }

            // Waiting hall
            setText("currentToken", "None");
            setText("currentTokenName", "");

            // Staff dashboard
            setText("staffCurrentToken", "No token");
            setText("staffCurrentName", "-");
            setText("staffCurrentService", "-");
            setText("staffCurrentPriority", "-");

            return;
        }

        // ---------- TOKEN IS SERVING ----------
        
        // On the dashboard while a token is serving: go to the serving page

        if (window.location.pathname.endsWith("staff_dashboard.php")) {
            window.location.href = "serving.php";
            return;
        }
        currentToken = {
            id: data.id,
            number: data.token,
            service: data.service,
            priority: Number(data.priority)
        };

        // Waiting hall
        setText("currentToken", data.token);
        setText("currentTokenName", data.person_name || "");

        // Staff dashboard
        setText("staffCurrentToken", data.token);
        setText("staffCurrentName", data.person_name || "-");
        setText("staffCurrentService", data.service);
        setText(
            "staffCurrentPriority",
            Number(data.priority) === 1 ? "⭐ PRIORITY" : "Normal"
        );
    })

    .catch(function(error) {
        console.error("Current serving token error:", error);
    });
}


// ========================================
// LOAD STATUS OF ALL MY TOKENS
// ========================================

function loadMyTokenStatus() {

    const list = document.getElementById("myTokensList");

    // No tokens generated in this browser

    if (myTokenIds.length === 0) {

        clearMyTokenDisplay();

        if (list) {
            list.innerHTML = "<p>No tokens generated yet.</p>";
        }

        return;
    }

    // Ask PHP about EVERY token this browser generated

    Promise.all(

        myTokenIds.map(function(id) {

            return fetch(
                "get_token_status.php?token_id=" +
                encodeURIComponent(id)
            )
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                return { id: id, data: data };
            })
            .catch(function() {
                return { id: id, data: null };
            });

        })

    )

    .then(function(results) {

        // Forget tokens that no longer exist in the database.
        // Keep tokens if the request merely failed (data === null).

        results = results.filter(function(r) {
            return r.data === null || r.data.success;
        });

        myTokenIds = results.map(function(r) {
            return r.id;
        });

        localStorage.setItem(
            "myTokenIds",
            JSON.stringify(myTokenIds)
        );

        const valid = results.filter(function(r) {
            return r.data !== null;
        });

        // ---------- One card per token ----------

        if (list) {

            if (valid.length === 0) {

                list.innerHTML = "<p>No tokens generated yet.</p>";

            } else {

                list.innerHTML = "";

                valid.forEach(function(r) {

                    const d = r.data;

                    const ahead =
                        d.status === "WAITING" ? d.people_ahead
                        : d.status === "SERVING" ? 0
                        : "—";

                    const time =
                        d.status === "WAITING"
                            ? d.waiting_time + " minutes" + (d.prediction_source === "ML" ? " (AI estimate)" : "")
                        : d.status === "SERVING" ? "0 minutes"
                        : "—";

                    const card = document.createElement("div");

                    card.className = "queue-item";

                    card.innerHTML = `
                        <strong>${d.token}</strong>
                        <span>${d.service}</span>
                        <small>${escapeHtml(d.person_name || "")}</small>
                        <span>Status: ${d.status}</span>
                        <span>People Ahead: ${ahead}</span>
                        <span>Waiting Time: ${time}</span>
                    `;

                    list.appendChild(card);
                });
            }
        }

        // ---------- Big display = newest ACTIVE token ----------

        const active = valid.filter(function(r) {
            return r.data.status === "WAITING" ||
                   r.data.status === "SERVING";
        });

        if (active.length === 0) {
            clearMyTokenDisplay();
            return;
        }

        const latest = active[active.length - 1].data;

        setText("tokenPersonName", latest.person_name || "");
        
        setText(
            "predictionNote",
            latest.status === "WAITING" && latest.prediction_source === "ML"
                ? "✦ AI-predicted waiting time"
                : ""
        );

        if (latest.status === "WAITING") {

            setText("tokenNumber", latest.token);
            setText("status", "WAITING");
            setText("predictionNote", "");
            setText("peopleAhead", latest.people_ahead);
            setText("waitingTime", latest.waiting_time + " minutes");

        } else {

            setText("tokenNumber", latest.token);
            setText("status", "SERVING");
            setText("peopleAhead", "0");
            setText("waitingTime", "0 minutes");
        }
    });
}


// ========================================
// CLEAR MY TOKEN LIST (button)
// ========================================

function clearAllMyTokens() {

    myTokenIds = [];

    localStorage.removeItem("myTokenIds");

    loadMyTokenStatus();

    function clearAllMyTokens() {

    myTokenIds = [];

    localStorage.removeItem("myTokenIds");

    loadMyTokenStatus();

    showToast("Your token list has been cleared.", "success");
}
}


// ========================================
// CLEAR LIVE QUEUE / STATISTICS (testing helper)
// ========================================

function clearData(type) {

    const message =
        type === "queue"
            ? "Delete all WAITING tokens from the live queue?"
            : "Delete ALL tokens and reset statistics to 0?";

    if (!confirm(message)) {
        return;
    }

    const formData = new FormData();

    formData.append("type", type);

    fetch("clear_tokens.php", {
        method: "POST",
        body: formData
    })

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Server returned an error.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!data.success) {
            alert(data.message);
            return;
        }

        // Clearing everything also forgets this browser's tokens

        if (type === "all") {

            myTokenIds = [];

            localStorage.removeItem("myTokenIds");

            currentToken = null;
        }

        loadCurrentServing();
        loadMyTokenStatus();
        displayQueue();
        loadStatistics();

        alert(data.message);
    })

    .catch(function(error) {

        console.error("Clear error:", error);

        alert("Unable to clear.");
    });
}


// ========================================
// DISPLAY WAITING QUEUE
// ========================================

function displayQueue() {

    const queueList = document.getElementById("queueList");

    if (!queueList) {
        return;
    }

    fetch("get_queue.php")

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Unable to load queue.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!Array.isArray(data) || data.length === 0) {
            queueList.innerHTML = "<p>No tokens in queue.</p>";
            return;
        }

        queueList.innerHTML = "";

        data.forEach(function(token) {

            const queueItem = document.createElement("div");

            queueItem.className = "queue-item";

            const priorityText =
                Number(token.priority) === 1 ? "⭐ Priority" : "Normal";

            queueItem.innerHTML = `
                <div class="queue-token">
                    <strong>${token.token_number}</strong>
                    <small>${escapeHtml(token.person_name || "")}</small>
                </div>
                <span>${token.service_name}</span>
                <span>${priorityText}</span>
            `;

            queueList.appendChild(queueItem);
        });
    })

    .catch(function(error) {
        console.error("Queue loading error:", error);
    });
}


// ========================================
// CALL NEXT TOKEN
// ========================================

function callNextToken() {

    if (isCallingToken) {
        return;
    }

    // Refresh the serving state from the database

    loadCurrentServing();

    if (currentToken !== null) {
        alert("Please complete or skip the current token first.");
        return;
    }

    isCallingToken = true;

    fetch("call_next_token.php", {
        method: "POST"
    })

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Unable to call token.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!data.success) {
            alert(data.message);
            return;
        }

        currentToken = {
            id: data.id,
            number: data.token,
            service: data.service,
            priority: Number(data.priority)
        };

        // Refresh everything
        // (loadCurrentServing also fetches the person's name)

        loadCurrentServing();
        loadMyTokenStatus();
        displayQueue();
        loadStatistics();
        
        window.location.href = "serving.php";
    })

    .catch(function(error) {

        console.error("Call next token error:", error);

        alert("Unable to call next token.");
    })

    .finally(function() {
        isCallingToken = false;
    });
}


// ========================================
// COMPLETE TOKEN
// ========================================

function completeToken() {

    if (isCompletingToken) {
        return;
    }

    if (currentToken === null) {
        alert("There is no token currently being served.");
        return;
    }

    isCompletingToken = true;

    fetch("complete_token.php", {
        method: "POST"
    })

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Unable to complete token.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!data.success) {
            alert(data.message);
            return;
        }

        currentToken = null;

        loadCurrentServing();
        loadMyTokenStatus();
        displayQueue();
        loadStatistics();

        alert("Token " + data.token + " completed successfully.");
        window.location.href = "staff_dashboard.php";
    })

    .catch(function(error) {

        console.error("Complete token error:", error);

        alert("Unable to complete token.");
    })

    .finally(function() {
        isCompletingToken = false;
    });
}


// ========================================
// SKIP TOKEN
// ========================================

function skipToken() {

    if (isSkippingToken) {
        return;
    }

    if (currentToken === null) {
        alert("There is no token currently being served.");
        return;
    }

    isSkippingToken = true;

    fetch("skip_token.php", {
        method: "POST"
    })

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Unable to skip token.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!data.success) {
            alert(data.message);
            return;
        }

        currentToken = null;

        loadCurrentServing();
        loadMyTokenStatus();
        displayQueue();
        loadStatistics();

        alert("Token " + data.token + " skipped successfully.");
        window.location.href = "staff_dashboard.php";
    })

    .catch(function(error) {

        console.error("Skip token error:", error);

        alert("Unable to skip token.");
    })

    .finally(function() {
        isSkippingToken = false;
    });
}


// ========================================
// LOAD STATISTICS
// ========================================

function loadStatistics() {

    fetch("get_statistics.php")

    .then(function(response) {

        if (!response.ok) {
            throw new Error("Unable to load statistics.");
        }

        return response.json();
    })

    .then(function(data) {

        if (!data.success) {
            return;
        }

        setText("totalTokens", data.total);
        setText("waitingTokens", data.waiting);
        setText("servingTokens", data.serving);
        setText("completedTokens", data.completed);
    })

    .catch(function(error) {
        console.error("Statistics loading error:", error);
    });
}


// ========================================
// INITIAL LOAD
// ========================================

function initializeSystem() {

    loadCurrentServing();
    loadMyTokenStatus();
    displayQueue();
    loadStatistics();
}


// ========================================
// AUTOMATIC REFRESH
// ========================================

initializeSystem();

setInterval(loadCurrentServing, 5000);
setInterval(loadMyTokenStatus, 5000);
setInterval(displayQueue, 5000);
setInterval(loadStatistics, 5000);

// ========================================
// KEEP A MESSAGE VISIBLE WHEN THE PAGE CHANGES
// ========================================

window.addEventListener("beforeunload", function () {

    const toasts = document.querySelectorAll("#toastContainer .toast");

    if (toasts.length === 0) {
        return;
    }

    const last = toasts[toasts.length - 1];

    const message = last.querySelector(".toast-text").textContent;

    let type = "info";

    ["success", "error", "warning"].forEach(function (t) {
        if (last.classList.contains("toast-" + t)) {
            type = t;
        }
    });

    sessionStorage.setItem(
        "pendingToast",
        JSON.stringify({ message: message, type: type, time: Date.now() })
    );
});


window.addEventListener("DOMContentLoaded", function () {

    const saved = sessionStorage.getItem("pendingToast");

    if (!saved) {
        return;
    }

    sessionStorage.removeItem("pendingToast");

    const toast = JSON.parse(saved);

    // Only show it if it was created in the last 4 seconds

    if (Date.now() - toast.time < 4000) {
        showToast(toast.message, toast.type);
    }
});