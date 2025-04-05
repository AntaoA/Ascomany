<?php
/**
 * Flowmodoro History Shortcode V3
 *
 * @package Flowmodoro
 */
function flowmodoro_history_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour consulter votre historique.</p>';
    }

    ob_start();
    ?>
    <style>
        .flowmodoro-history-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            font-family: sans-serif;
        }
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .history-toggle button,
        .history-filter-toggle {
            padding: 8px 16px;
            background: #eee;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .history-filter-dropdown {
            position: relative;
            display: inline-block;
        }
        .history-filter-options {
            position: absolute;
            top: 110%;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            display: none;
            min-width: 180px;
            z-index: 99;
        }
        .history-filter-options button {
            display: block;
            width: 100%;
            border: none;
            background: none;
            padding: 8px 12px;
            text-align: left;
            cursor: pointer;
        }
        .history-filter-options button:hover {
            background: #f0f0f0;
        }

        .session-block {
            background: #f9f9f9;
            border-left: 5px solid #3498db;
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 4px;
        }
        .session-block h4 {
            margin: 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }
        .session-details {
            margin-top: 10px;
            padding-left: 10px;
            display: none;
        }
        .entry-line {
            font-family: monospace;
            color: #555;
        }
        .entry-line span {
            display: inline-block;
            min-width: 80px;
        }
        .empty-message {
            color: #888;
            font-style: italic;
        }
    </style>

    <div class="flowmodoro-history-container">
        <h2>📜 Historique Flowmodoro</h2>
        <div class="history-header">
            <div class="history-toggle">
                <button id="toggle-view">🔁 Affichage : par session</button>
            </div>
            <div class="history-filter-dropdown">
                <button class="history-filter-toggle">🔍 Filtrer</button>
                <div class="history-filter-options" id="filter-options">
                    <button data-range="day">Aujourd’hui</button>
                    <button data-range="week">Cette semaine</button>
                    <button data-range="all">Tout</button>
                    <button data-range="session">Session actuelle</button>
                </div>
            </div>
        </div>
        <div id="history-output"></div>
    </div>

    <script>
    (function(){
        const output = document.getElementById("history-output");
        const toggleBtn = document.getElementById("toggle-view");
        const filterOptions = document.getElementById("filter-options");
        const filterToggleBtn = document.querySelector(".history-filter-toggle");

        let currentView = "session"; // "session" or "phase"
        let currentRange = "all";

        const allHistory = <?php
            $user_id = get_current_user_id();
            $history = get_user_meta($user_id, 'flowmodoro_history', true);
            $data = is_string($history) ? json_decode($history, true) : $history;
            if (!is_array($data)) $data = [];
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ?>;

        const sessionParam = new URLSearchParams(window.location.search).get("session");
        let sessionHistory = [];
        if (sessionParam) {
            try {
                sessionHistory = JSON.parse(decodeURIComponent(sessionParam));
            } catch(e) {}
        } else {
            const raw = sessionStorage.getItem("flowmodoro_session");
            sessionHistory = raw ? JSON.parse(raw) : [];
        }

        function formatTime(ms) {
            const totalSec = Math.floor(ms / 1000);
            const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
            const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
            const s = String(totalSec % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        }

        function formatDate(ts) {
            const d = new Date(ts);
            return d.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' }) + ' à ' +
                   d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        }

        function groupSessions(history) {
            const sessions = [];
            let current = [];

            for (let i = 0; i < history.length; i++) {
                const entry = history[i];
                current.push(entry);

                const next = history[i + 1];
                const endTime = entry.timestamp + (entry.duration || 0);
                const nextStart = next ? next.timestamp : 0;

                if (!next || (nextStart - endTime > 10 * 60 * 1000)) {
                    sessions.push([...current]);
                    current = [];
                }
            }

            return sessions;
        }

        function applyFilter(history, range) {
            const now = new Date();
            const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();

            const startOfWeek = new Date(now);
            startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
            startOfWeek.setHours(0,0,0,0);
            const startWeekTs = startOfWeek.getTime();

            if (range === "day") {
                return history.filter(e => e.timestamp >= startOfDay);
            } else if (range === "week") {
                return history.filter(e => e.timestamp >= startWeekTs);
            } else if (range === "session") {
                return sessionHistory;
            } else {
                return allHistory;
            }
        }

        function render() {
            const data = applyFilter(allHistory, currentRange);
            output.innerHTML = "";

            if (data.length === 0) {
                output.innerHTML = `<p class="empty-message">Aucune entrée pour ce filtre.</p>`;
                return;
            }

            if (currentView === "session") {
                const sessions = groupSessions(data);
                sessions.sort((a, b) => b[0].timestamp - a[0].timestamp);
                sessions.forEach((session, index) => {
                    const first = session[0];
                    const div = document.createElement("div");
                    div.className = "session-block";

                    let totalWork = 0, totalPause = 0;
                    session.forEach(e => {
                        if (e.type === "Travail") totalWork += e.duration || 0;
                        if (e.type === "Pause") totalPause += e.duration || 0;
                    });

                    div.innerHTML = `
                        <h4>
                            🧠 Session — ${formatDate(first.timestamp)}
                            <span>▼</span>
                        </h4>
                        <div class="session-details">
                            ${session.map(e =>
                                `<div class="entry-line">
                                    <span>${e.type}</span> — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}
                                </div>`
                            ).join("")}
                        </div>
                        <div class="entry-line" style="margin-top: 10px; font-weight: bold;">
                            Total Travail : ${formatTime(totalWork)} | Total Pause : ${formatTime(totalPause)}
                        </div>
                    `;

                    const h4 = div.querySelector("h4");
                    const details = div.querySelector(".session-details");
                    h4.addEventListener("click", () => {
                        details.style.display = details.style.display === "none" ? "block" : "none";
                        h4.querySelector("span").textContent = details.style.display === "block" ? "▲" : "▼";
                    });

                    output.appendChild(div);
                });

            } else {
                const sorted = [...data].sort((a, b) => b.timestamp - a.timestamp);
                sorted.forEach(e => {
                    const div = document.createElement("div");
                    div.className = "session-block";
                    div.innerHTML = `
                        <div class="entry-line">
                            <strong>${e.type}</strong> — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}
                        </div>
                    `;
                    output.appendChild(div);
                });
            }
        }

        toggleBtn.addEventListener("click", () => {
            currentView = (currentView === "session") ? "phase" : "session";
            toggleBtn.textContent = currentView === "session" ? "🔁 Affichage : par session" : "🔁 Affichage : par phase";
            render();
        });

        filterToggleBtn.addEventListener("click", () => {
            filterOptions.style.display = filterOptions.style.display === "block" ? "none" : "block";
        });

        filterOptions.querySelectorAll("button").forEach(btn => {
            btn.addEventListener("click", () => {
                currentRange = btn.dataset.range;
                filterOptions.style.display = "none";
                render();
            });
        });

        render();
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro_history', 'flowmodoro_history_shortcode');
