<?php
/**
 * Flowmodoro History Shortcode
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
        .history-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .history-filters button {
            padding: 8px 16px;
            border: none;
            background: #eee;
            cursor: pointer;
            border-radius: 4px;
        }
        .history-filters button.active {
            background: #333;
            color: white;
        }
        .session-block {
            background: #f9f9f9;
            border-left: 5px solid #3498db;
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 4px;
        }
        .session-block h4 {
            margin: 0 0 10px 0;
        }
        .entry-line {
            margin-left: 10px;
            font-family: monospace;
            color: #555;
        }
        .empty-message {
            color: #888;
            font-style: italic;
        }
    </style>

    <div class="flowmodoro-history-container">
        <h2>📜 Historique Flowmodoro</h2>
        <div class="history-filters">
            <button data-range="session">Session actuelle</button>
            <button data-range="day">Aujourd’hui</button>
            <button data-range="week">Semaine</button>
            <button data-range="all">Tout</button>
        </div>
        <div id="history-output"></div>
    </div>

    <script>
    (function(){
        const output = document.getElementById("history-output");

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

        const filters = document.querySelectorAll(".history-filters button");
        filters.forEach(btn => {
            btn.addEventListener("click", () => {
                filters.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
                render(btn.dataset.range);
            });
        });

        const formatTime = (ms) => {
            const sec = Math.floor(ms / 1000);
            const h = String(Math.floor(sec / 3600)).padStart(2, '0');
            const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
            const s = String(sec % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        };

        const formatDate = (ts) => {
            const d = new Date(ts);
            return d.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' }) + ' — ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        };

        const groupSessions = (history) => {
            const sessions = [];
            let current = [];

            for (let i = 0; i < history.length; i++) {
                const entry = history[i];
                current.push(entry);

                const next = history[i + 1];
                const endTime = entry.timestamp + (entry.duration || 0);
                const nextStart = next ? next.timestamp : 0;

                // Si plus de 10 minutes entre deux entrées → nouvelle session
                if (!next || (nextStart - endTime > 10 * 60 * 1000)) {
                    sessions.push([...current]);
                    current = [];
                }
            }

            return sessions;
        };

        const render = (range) => {
            output.innerHTML = "";

            let historyToUse = (range === "session") ? sessionHistory : allHistory;

            // Filtrage temporel
            const now = new Date();
            const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
            const startOfWeek = new Date(now);
            startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
            startOfWeek.setHours(0,0,0,0);
            const startWeekTs = startOfWeek.getTime();

            if (range === "day") {
                historyToUse = allHistory.filter(e => e.timestamp >= startOfDay);
            } else if (range === "week") {
                historyToUse = allHistory.filter(e => e.timestamp >= startWeekTs);
            }

            if (!historyToUse || historyToUse.length === 0) {
                output.innerHTML = `<p class="empty-message">Aucune session enregistrée pour cette période.</p>`;
                return;
            }

            const sessions = groupSessions(historyToUse);
            sessions.sort((a, b) => b[0].timestamp - a[0].timestamp); // plus récentes en haut

            sessions.forEach((session, index) => {
                const first = session[0];
                const block = document.createElement("div");
                block.className = "session-block";

                let totalTravail = 0;
                let totalPause = 0;
                session.forEach(e => {
                    if (e.type === "Travail") totalTravail += e.duration || 0;
                    if (e.type === "Pause") totalPause += e.duration || 0;
                });

                block.innerHTML = `
                    <h4>🧠 Session — ${formatDate(first.timestamp)}</h4>
                    <div class="entry-line">Travail : ${formatTime(totalTravail)}</div>
                    <div class="entry-line">Pause : ${formatTime(totalPause)}</div>
                `;

                output.appendChild(block);
            });
        };

        // Initialisation
        document.querySelector('.history-filters button[data-range="all"]').classList.add("active");
        render("all");
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro_history', 'flowmodoro_history_shortcode');
