<?php
/**
 * Flowmodoro History Shortcode V4
 *
 * @package Flowmodoro
 */
function flowmodoro_history_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour consulter votre historique.</p>';
    }

    ob_start();
    ?>
    <div class="flowmodoro-history-container">
        <h2>📜 Historique Flowmodoro</h2>
        <div class="history-controls">
            <button id="toggle-view" class="toggle-button">🔁 Affichage : par session</button>
            <div class="time-filter">
                <button id="filter-button">📅 Filtrer par date</button>
                <div class="filter-dropdown" id="filter-dropdown" style="display: none;"></div>
            </div>
        </div>
        <div id="history-output"></div>
    </div>
    <style>
        .flowmodoro-history-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            font-family: sans-serif;
            color: #222;
        }
        .history-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .toggle-button, #filter-button {
            padding: 8px 16px;
            background: #eee;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .toggle-button:hover, #filter-button:hover {
            background: #ddd;
        }
        .session-block {
            background: #f9f9f9;
            border: 2px solid #ccc;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 10px 15px;
            transition: background 0.2s;
            cursor: pointer;
        }
        .session-block:hover {
            background: #f0f0f0;
        }
        .session-details {
            margin-top: 10px;
            display: none;
        }
        .entry-line {
            font-family: monospace;
            margin: 5px 0;
        }
        .entry-travail {
            color: #e74c3c;
        }
        .entry-pause {
            color: #3498db;
        }
        .entry-phase {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .view-session-btn {
            font-size: 0.8em;
            padding: 2px 6px;
            border: 1px solid #ccc;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        .filter-dropdown {
            position: absolute;
            background: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 10;
            max-height: 300px;
            overflow-y: auto;
        }
        .filter-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .filter-dropdown li {
            padding: 5px 10px;
            cursor: pointer;
        }
        .filter-dropdown li:hover {
            background: #eee;
        }
        .empty-message {
            font-style: italic;
            color: #999;
        }
    </style>
<script>
(function(){
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
    const output = document.getElementById("history-output");
    const toggleBtn = document.getElementById("toggle-view");
    const filterBtn = document.getElementById("filter-button");
    const filterDropdown = document.getElementById("filter-dropdown");

    let currentView = "session"; // ou "phase"
    let selectedDate = null; // timestamp de jour sélectionné

    function formatTime(ms) {
        const sec = Math.floor(ms / 1000);
        const h = String(Math.floor(sec / 3600)).padStart(2, '0');
        const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    function formatDate(ts, withTime = true) {
        const d = new Date(ts);
        return d.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) +
            (withTime ? ' à ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) : '');
    }

    function groupSessions(history) {
        const sessions = [];
        let current = [];

        for (let i = 0; i < history.length; i++) {
            const entry = history[i];
            current.push(entry);

            const next = history[i + 1];
            const end = entry.timestamp + (entry.duration || 0);
            const nextStart = next ? next.timestamp : 0;

            if (!next || nextStart - end > 10 * 60 * 1000) {
                sessions.push([...current]);
                current = [];
            }
        }

        return sessions;
    }

    function extractAvailableDates(history) {
        const days = new Set();
        history.forEach(e => {
            const d = new Date(e.timestamp);
            d.setHours(0, 0, 0, 0);
            days.add(d.getTime());
        });
        return Array.from(days).sort((a, b) => b - a);
    }

    function getFilteredHistory() {
        const source = selectedDate === "session" ? sessionHistory : allHistory;
        if (!selectedDate || selectedDate === "all" || selectedDate === "session") return source;

        const start = new Date(parseInt(selectedDate));
        const end = new Date(start);
        end.setHours(23, 59, 59, 999);
        return source.filter(e => e.timestamp >= start.getTime() && e.timestamp <= end.getTime());
    }

    function render() {
        const data = getFilteredHistory();
        output.innerHTML = "";

        if (data.length === 0) {
            output.innerHTML = `<p class="empty-message">Aucune entrée pour ce filtre.</p>`;
            return;
        }

        if (currentView === "session") {
            const sessions = groupSessions(data);
            sessions.sort((a, b) => b[0].timestamp - a[0].timestamp);
            sessions.forEach((session, index) => {
                const div = document.createElement("div");
                div.className = "session-block";

                let totalTravail = 0, totalPause = 0;
                session.forEach(e => {
                    if (e.type === "Travail") totalTravail += e.duration || 0;
                    if (e.type === "Pause") totalPause += e.duration || 0;
                });

                const details = document.createElement("div");
                details.className = "session-details";
                session.forEach(e => {
                    const p = document.createElement("div");
                    p.className = "entry-line " + (e.type === "Travail" ? "entry-travail" : "entry-pause");
                    p.innerHTML = `<span>${e.type}</span> — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}`;
                    details.appendChild(p);
                });

                div.innerHTML = `
                    <h4>${formatDate(session[0].timestamp, false)}<br>
                    <small>Travail : ${formatTime(totalTravail)} | Pause : ${formatTime(totalPause)}</small>
                    </h4>
                `;
                div.appendChild(details);

                div.addEventListener("click", () => {
                    details.style.display = details.style.display === "block" ? "none" : "block";
                });

                output.appendChild(div);
            });
        } else {
            const sorted = [...data].sort((a, b) => b.timestamp - a.timestamp);
            sorted.forEach(e => {
                const div = document.createElement("div");
                div.className = "session-block entry-line " + (e.type === "Travail" ? "entry-travail" : "entry-pause");
                div.innerHTML = `
                    <div class="entry-phase">
                        <span><strong>${e.type}</strong> — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}</span>
                        <button class="view-session-btn" data-ts="${e.timestamp}">👁 Voir session</button>
                    </div>
                `;
                output.appendChild(div);
            });

            // gestion des boutons "voir session"
            output.querySelectorAll(".view-session-btn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const ts = parseInt(btn.dataset.ts);
                    const match = groupSessions(allHistory).find(s =>
                        s.some(e => e.timestamp === ts)
                    );
                    if (match) {
                        selectedDate = null;
                        currentView = "session";
                        toggleBtn.textContent = "🔁 Affichage : par phase";
                        renderSingleSession(match);
                    }
                });
            });
        }
    }

    function renderSingleSession(session) {
        output.innerHTML = "";
        let totalTravail = 0, totalPause = 0;
        const div = document.createElement("div");
        div.className = "session-block";

        session.forEach(e => {
            if (e.type === "Travail") totalTravail += e.duration || 0;
            if (e.type === "Pause") totalPause += e.duration || 0;
        });

        const details = document.createElement("div");
        details.className = "session-details";
        details.style.display = "block";

        session.forEach(e => {
            const p = document.createElement("div");
            p.className = "entry-line " + (e.type === "Travail" ? "entry-travail" : "entry-pause");
            p.innerHTML = `<span>${e.type}</span> — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}`;
            details.appendChild(p);
        });

        div.innerHTML = `
            <h4>${formatDate(session[0].timestamp, false)}<br>
            <small>Travail : ${formatTime(totalTravail)} | Pause : ${formatTime(totalPause)}</small>
            </h4>
        `;
        div.appendChild(details);
        output.appendChild(div);
    }
    // Affichage initial
    selectedDate = "all";
    render();

    // Toggle session / phase
    toggleBtn.addEventListener("click", () => {
        currentView = currentView === "session" ? "phase" : "session";
        toggleBtn.textContent = currentView === "session"
            ? "🔁 Affichage : par session"
            : "🔁 Affichage : par phase";
        render();
    });

    // Ouvrir ou fermer le menu filtre
    filterBtn.addEventListener("click", () => {
        const state = filterDropdown.style.display;
        filterDropdown.style.display = (state === "block") ? "none" : "block";
        if (filterDropdown.innerHTML.trim() === "") renderFilterDropdown();
    });

    // Gérer le menu de filtre par jour
    function renderFilterDropdown() {
        const uniqueDays = extractAvailableDates(allHistory);
        const groupedByYearMonth = {};

        uniqueDays.forEach(ts => {
            const d = new Date(ts);
            const year = d.getFullYear();
            const month = d.toLocaleString(undefined, { month: 'long' });
            if (!groupedByYearMonth[year]) groupedByYearMonth[year] = {};
            if (!groupedByYearMonth[year][month]) groupedByYearMonth[year][month] = [];
            groupedByYearMonth[year][month].push(ts);
        });

        const container = document.createElement("ul");

        Object.entries(groupedByYearMonth).forEach(([year, months]) => {
            const yearLi = document.createElement("li");
            yearLi.innerHTML = `<strong>${year}</strong>`;
            container.appendChild(yearLi);

            Object.entries(months).forEach(([month, dates]) => {
                const monthLi = document.createElement("li");
                monthLi.innerHTML = `<em>${month}</em>`;
                container.appendChild(monthLi);

                dates.forEach(ts => {
                    const dayLi = document.createElement("li");
                    dayLi.textContent = new Date(ts).toLocaleDateString();
                    dayLi.dataset.ts = ts;
                    dayLi.addEventListener("click", () => {
                        selectedDate = ts;
                        filterDropdown.style.display = "none";
                        render();
                    });
                    container.appendChild(dayLi);
                });
            });
        });

        const allBtn = document.createElement("li");
        allBtn.innerHTML = `<strong>📂 Voir tout</strong>`;
        allBtn.addEventListener("click", () => {
            selectedDate = "all";
            filterDropdown.style.display = "none";
            render();
        });
        container.insertBefore(allBtn, container.firstChild);

        filterDropdown.innerHTML = "";
        filterDropdown.appendChild(container);
    }

})(); // fin du IIFE
</script>
<?php
return ob_get_clean();
}
add_shortcode('flowmodoro_history', 'flowmodoro_history_shortcode');
