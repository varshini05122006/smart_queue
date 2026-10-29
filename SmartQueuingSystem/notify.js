(function () {

    // ---------- Styling (built in, so style.css is not needed) ----------

    if (!document.getElementById("toastStyles")) {

        const style = document.createElement("style");

        style.id = "toastStyles";

        style.textContent = `
        #toastContainer {
            position: fixed; top: 20px; right: 20px; z-index: 99999;
            display: flex; flex-direction: column; gap: 12px;
            width: 360px; max-width: calc(100vw - 32px);
            font-family: "Segoe UI", system-ui, Arial, sans-serif;
        }
        .toast {
            position: relative; display: flex; align-items: flex-start; gap: 14px;
            padding: 16px 40px 18px 16px; background: #fff;
            border: 1px solid #e2e8f0; border-left: 5px solid #14213d;
            border-radius: 12px; box-shadow: 0 12px 32px rgba(15,23,42,0.22);
            overflow: hidden; animation: toastIn 0.35s ease both;
        }
        .toast-hide { animation: toastOut 0.3s ease both; }
        @keyframes toastIn  { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(40px); } }
        .toast-icon {
            flex-shrink: 0; width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; font-weight: 700; color: #fff; background: #14213d;
        }
        .toast-content { flex: 1; }
        .toast-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.09em; color: #64748b; margin-bottom: 3px;
        }
        .toast-text { font-size: 15px; line-height: 1.4; color: #1e293b; }
        .toast-close {
            position: absolute; top: 8px; right: 12px; font-size: 22px;
            line-height: 1; color: #94a3b8; cursor: pointer;
        }
        .toast-bar {
            position: absolute; left: 0; bottom: 0; height: 3px; width: 100%;
            background: #14213d; transform-origin: left;
            animation: toastBar 4.5s linear forwards;
        }
        @keyframes toastBar { from { transform: scaleX(1); } to { transform: scaleX(0); } }
        .toast-success { border-left-color: #2f5d50; }
        .toast-success .toast-icon, .toast-success .toast-bar { background: #2f5d50; }
        .toast-error { border-left-color: #9b2c2c; }
        .toast-error .toast-icon, .toast-error .toast-bar { background: #9b2c2c; }
        .toast-warning { border-left-color: #8a6d3b; }
        .toast-warning .toast-icon, .toast-warning .toast-bar { background: #8a6d3b; }
        @media (max-width: 600px) {
            #toastContainer { top: 12px; right: 16px; left: 16px; width: auto; max-width: none; }
        }
        `;

        document.head.appendChild(style);
    }


    // ---------- The message card ----------

    function showToast(message, type) {

        type = type || "info";

        let container = document.getElementById("toastContainer");

        if (!container) {
            container = document.createElement("div");
            container.id = "toastContainer";
            document.body.appendChild(container);
        }

        const titles = { success: "Success", error: "Error", warning: "Notice", info: "Information" };
        const icons  = { success: "✓", error: "✕", warning: "!", info: "i" };

        const toast = document.createElement("div");
        toast.className = "toast toast-" + type;

        const icon = document.createElement("div");
        icon.className = "toast-icon";
        icon.textContent = icons[type] || "i";

        const content = document.createElement("div");
        content.className = "toast-content";

        const title = document.createElement("div");
        title.className = "toast-title";
        title.textContent = titles[type] || "Information";

        const text = document.createElement("div");
        text.className = "toast-text";
        text.textContent = message;

        content.appendChild(title);
        content.appendChild(text);

        const close = document.createElement("span");
        close.className = "toast-close";
        close.textContent = "×";

        const bar = document.createElement("div");
        bar.className = "toast-bar";

        toast.appendChild(icon);
        toast.appendChild(content);
        toast.appendChild(close);
        toast.appendChild(bar);

        container.appendChild(toast);

        function removeToast() {
            toast.classList.add("toast-hide");
            setTimeout(function () { toast.remove(); }, 300);
        }

        close.addEventListener("click", removeToast);

        setTimeout(removeToast, 4500);
    }

    window.showToast = showToast;


    // ---------- Replace the browser alert box ----------

    window.alert = function (message) {

        const text = String(message);

        let type = "info";

        if (/unable|failed|invalid|denied|error/i.test(text)) {
            type = "error";
        } else if (/^no\s|^there is no|please|already|required/i.test(text)) {
            type = "warning";
        } else if (/success|generated|completed|skipped|cleared/i.test(text)) {
            type = "success";
        }

        showToast(text, type);
    };


    // ---------- Keep the message visible when the page changes ----------

    window.addEventListener("beforeunload", function () {

        const toasts = document.querySelectorAll("#toastContainer .toast");

        if (toasts.length === 0) {
            return;
        }

        const last = toasts[toasts.length - 1];

        let type = "info";

        ["success", "error", "warning"].forEach(function (t) {
            if (last.classList.contains("toast-" + t)) {
                type = t;
            }
        });

        sessionStorage.setItem("pendingToast", JSON.stringify({
            message: last.querySelector(".toast-text").textContent,
            type: type,
            time: Date.now()
        }));
    });

    window.addEventListener("DOMContentLoaded", function () {

        const saved = sessionStorage.getItem("pendingToast");

        if (!saved) {
            return;
        }

        sessionStorage.removeItem("pendingToast");

        const toast = JSON.parse(saved);

        if (Date.now() - toast.time < 4000) {
            showToast(toast.message, toast.type);
        }
    });

})();