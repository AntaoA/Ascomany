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
            <input id="datepicker" placeholder="📅 Sélectionner une période" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; cursor: pointer;" readonly />
            <div class="filter-dropdown" id="filter-dropdown" style="display: none;"></div>
            </div>
        </div>
        <div id="history-output"></div>
        <div id="popup-confirm" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:#0008; z-index:10000; justify-content:center; align-items:center;">
            <div style="background:white; padding:20px; border-radius:8px; text-align:center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <p id="popup-message" style="margin-bottom: 20px;">Confirmer la suppression ?</p>
                <button id="popup-yes" style="margin-right: 10px;">✅ Oui</button>
                <button id="popup-no">❌ Non</button>
            </div>
        </div>
    </div>
    <?php if (is_user_logged_in()) : ?>
        <script>const userIsLoggedIn = true;</script>
    <?php else : ?>
        <script>const userIsLoggedIn = false;</script>
    <?php endif; ?>
    <!-- Litepicker CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <style>
        .flowmodoro-history-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            font-family: sans-serif;
            background: #fafafa;
            color: #111;
        }

        .history-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .toggle-button, #filter-button {
            padding: 8px 16px;
            background: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #111;
        }

        .toggle-button:hover, #filter-button:hover {
            background: #ddd;
        }

        .session-block {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 10px 15px;
            cursor: pointer;
            color: #111;
        }

        .session-block:hover {
            background: #f2f2f2;
        }

        .session-details {
            margin-top: 10px;
            display: none;
        }

        .entry-line {
            font-family: monospace;
            margin: 5px 0;
            color: #222;
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
            color: #111;
            cursor: pointer;
            border-radius: 4px;
        }

        .view-session-btn:hover {
            background: #eee;
        }

        .filter-dropdown {
            position: absolute;
            background: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 10;
            max-height: 300px;
            overflow-y: auto;
            color: #111;
        }

        .filter-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .filter-dropdown li {
            padding: 5px 10px;
            cursor: pointer;
            color: #111;
        }

        .filter-dropdown li:hover {
            background: #eee;
        }

        .empty-message {
            font-style: italic;
            color: #888;
        }
        .litepicker-day.has-session {
            background-color: #d1e8ff !important;
            border-radius: 50%;
            position: relative;
        }

        .litepicker-day.has-session:hover {
            background-color: #a8d2ff !important;
        }

        .delete-session-btn {
            background: none;
            border: none;
            color: #888;
            font-size: 16px;
            cursor: pointer;
        }
        .delete-session-btn:hover {
            color: #e74c3c;
        }

        .delete-phase-btn {
            background: none;
            border: none;
            font-size: 14px;
            cursor: pointer;
            color: #888;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .delete-phase-btn:hover {
            color: #e74c3c;
            background: #f5f5f5;
        }

    </style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const allHistory = <?php
        $user_id = get_current_user_id();
        $history = get_user_meta($user_id, 'flowmodoro_history', true);
        $data = is_string($history) ? json_decode($history, true) : $history;
        if (!is_array($data)) $data = [];
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>;

    function getActiveDates(history) {
        const dates = new Set();
        history.forEach(e => {
            const d = new Date(e.timestamp);
            d.setHours(0, 0, 0, 0);
            dates.add(d.toISOString().split('T')[0]); // format 'YYYY-MM-DD'
        });
        return Array.from(dates);
    }

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
    const filterDropdown = document.getElementById("filter-dropdown");

    let currentView = "session"; // ou "phase"
    let selectedRange = null;
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
        const source = currentView === "session" ? allHistory : allHistory;

        if (!selectedRange || selectedRange.length !== 2) {
            return source;
        }

        const [startTs, endTs] = selectedRange;
        return source.filter(e => e.timestamp >= startTs && e.timestamp <= endTs);
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
                    const line = document.createElement("div");
                    line.className = "entry-line " + (e.type === "Travail" ? "entry-travail" : "entry-pause");
                    line.innerHTML = `
                        <div class="entry-phase" style="justify-content: space-between;">
                            <span>${e.type} — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}</span>
                            <button class="delete-phase-btn" data-ts="${e.timestamp}" title="Supprimer cette phase">🗑</button>
                        </div>
                    `;
                    details.appendChild(line);
                });


                div.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0;">${formatDate(session[0].timestamp, false)}</h4>
                            <small>Travail : ${formatTime(totalTravail)} | Pause : ${formatTime(totalPause)}</small>
                        </div>
                        <button class="delete-session-btn" data-ts="${session[0].timestamp}" title="Supprimer cette session">🗑</button>
                    </div>
                `;
                div.appendChild(details);

                div.addEventListener("click", (e) => {
                    if (e.target.closest(".delete-session-btn")) return;
                    details.style.display = details.style.display === "block" ? "none" : "block";
                });

                output.appendChild(div);
                details.querySelectorAll(".delete-phase-btn").forEach(btn => {
                    btn.onclick = (e) => {
                        e.stopPropagation();
                        const ts = parseInt(btn.dataset.ts);

                        confirmCustom("Supprimer cette phase ?", (ok) => {
                            if (!ok) return;

                            // 🔁 Supprimer la phase dans allHistory et sessionHistory
                            for (let i = allHistory.length - 1; i >= 0; i--) {
                                if (allHistory[i].timestamp === ts) {
                                    allHistory.splice(i, 1);
                                    break;
                                }
                            }

                            sessionHistory = sessionHistory.filter(e => e.timestamp !== ts);
                            sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

                            // 🔄 Met à jour WordPress si connecté
                            if (userIsLoggedIn) {
                                fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                    body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                                });
                            }

                            // 🎯 Met à jour juste cette session
                            const updatedSession = session.filter(el => el.timestamp !== ts);
                            renderSingleSession(updatedSession);
                        });
                    };
                });

            // gestion des suppressions de session
            output.querySelectorAll(".delete-session-btn").forEach(btn => {
                btn.onclick = (e) => {
                    e.stopPropagation();
                    const ts = parseInt(btn.dataset.ts);
                    const sessionToDelete = sessions.find(s => s[0].timestamp === ts);

                    confirmCustom("Supprimer cette session ?", (ok) => {
                        if (!ok) return;

                        const timestampsToDelete = sessionToDelete.map(e => e.timestamp);
                        for (let i = allHistory.length - 1; i >= 0; i--) {
                            if (timestampsToDelete.includes(allHistory[i].timestamp)) {
                                allHistory.splice(i, 1);
                            }
                        }
                        sessionHistory = sessionHistory.filter(e => !timestampsToDelete.includes(e.timestamp));
                        sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));
                        if (typeof userIsLoggedIn !== "undefined" && userIsLoggedIn) {
                            fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                            });
                        }

                        render();
                    });
                };
            });


            });
        } else {
            const sorted = [...data].sort((a, b) => b.timestamp - a.timestamp);
            sorted.forEach(e => {
                const div = document.createElement("div");
                div.className = "session-block entry-line " + (e.type === "Travail" ? "entry-travail" : "entry-pause");
                div.innerHTML = `
                    <div class="entry-phase">
                        <span><strong>${e.type}</strong> — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}</span>
                        <div>
                            <button class="view-session-btn" data-ts="${e.timestamp}">👁</button>
                            <button class="delete-phase-btn" data-ts="${e.timestamp}" title="Supprimer cette phase">🗑</button>
                        </div>
                    </div>
                `;
                output.appendChild(div);
            // gestion des suppressions de phases
            output.querySelectorAll(".delete-phase-btn").forEach(btn => {
                btn.onclick = (e) => {
                    e.stopPropagation();
                    const ts = parseInt(btn.dataset.ts);

                    confirmCustom("Supprimer cette phase ?", (ok) => {
                        if (!ok) return;

                        // Supprime de allHistory
                        for (let i = allHistory.length - 1; i >= 0; i--) {
                            if (allHistory[i].timestamp === ts) {
                                allHistory.splice(i, 1);
                                break;
                            }
                        }

                        // Supprime de la session locale
                        sessionHistory = sessionHistory.filter(e => e.timestamp !== ts);
                        sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

                        // si connecté, sync
                        if (typeof userIsLoggedIn !== "undefined" && userIsLoggedIn) {
                            fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                            });
                        }

                        render();
                    });
                };
            });
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
            const line = document.createElement("div");
            line.className = "entry-line " + (e.type === "Travail" ? "entry-travail" : "entry-pause");
            line.innerHTML = `
                <div class="entry-phase" style="justify-content: space-between;">
                    <span>${e.type} — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}</span>
                    <button class="delete-phase-btn" data-ts="${e.timestamp}" title="Supprimer cette phase">🗑</button>
                </div>
            `;
            details.appendChild(line);
        });

        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0;">${formatDate(session[0].timestamp, false)}</h4>
                    <small>Travail : ${formatTime(totalTravail)} | Pause : ${formatTime(totalPause)}</small>
                </div>
                <button class="delete-session-btn" data-ts="${session[0].timestamp}" title="Supprimer cette session">🗑</button>
            </div>
        `;
        div.appendChild(details);
        output.appendChild(div);
        div.querySelectorAll(".delete-phase-btn").forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                const ts = parseInt(btn.dataset.ts);

                confirmCustom("Supprimer cette phase ?", (ok) => {
                    if (!ok) return;

                    for (let i = allHistory.length - 1; i >= 0; i--) {
                        if (allHistory[i].timestamp === ts) {
                            allHistory.splice(i, 1);
                            break;
                        }
                    }

                    sessionHistory = sessionHistory.filter(e => e.timestamp !== ts);
                    sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

                    if (userIsLoggedIn) {
                        fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                        });
                    }

                    renderSingleSession(session.filter(e => e.timestamp !== ts));
                });
            };
        });

        div.querySelector(".delete-session-btn").onclick = (e) => {
            e.stopPropagation();
            confirmCustom("Supprimer cette session ?", (ok) => {
                if (!ok) return;

                const timestampsToDelete = session.map(e => e.timestamp);
                for (let i = allHistory.length - 1; i >= 0; i--) {
                    if (timestampsToDelete.includes(allHistory[i].timestamp)) {
                        allHistory.splice(i, 1);
                    }
                }

                sessionHistory = sessionHistory.filter(e => !timestampsToDelete.includes(e.timestamp));
                sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

                if (userIsLoggedIn) {
                    fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                    });
                }

                render();
            });
        };

    }

    function confirmCustom(message, callback) {
        const popup = document.getElementById("popup-confirm");
        const msg = document.getElementById("popup-message");
        const yes = document.getElementById("popup-yes");
        const no = document.getElementById("popup-no");

        msg.textContent = message;
        popup.style.display = "flex";

        const clean = () => {
            popup.style.display = "none";
            yes.onclick = null;
            no.onclick = null;
        };

        yes.onclick = () => {
            clean();
            callback(true);
        };
        no.onclick = () => {
            clean();
            callback(false);
        };
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
    

    const dateInput = document.getElementById('datepicker');
    if (dateInput) {
        const activeDates = getActiveDates(allHistory);
        const picker = new Litepicker({
            element: dateInput,
            singleMode: false,
            format: 'YYYY-MM-DD',
            numberOfMonths: 2,
            numberOfColumns: 2,
            lang: 'fr',
            autoApply: true,
            tooltipText: {
                one: 'jour sélectionné',
                other: 'jours sélectionnés'
            },
            setup: (picker) => {
                picker.on('render', () => {
                    const days = document.querySelectorAll('.litepicker-day');

                    days.forEach(day => {
                        const date = day.dataset.time
                            ? new Date(parseInt(day.dataset.time)).toISOString().split('T')[0]
                            : null;

                        if (date && activeDates.includes(date)) {
                            day.classList.add('has-session');
                        }
                    });
                });
                picker.on('selected', (start, end) => {
                    selectedRange = [start.dateInstance.getTime(), end.dateInstance.getTime()];
                    render();
                });
                picker.on('shown', () => picker.refresh());
            }
        });
    }


}); // fin du IIFE
</script>
<?php
return ob_get_clean();
}
add_shortcode('flowmodoro_history', 'flowmodoro_history_shortcode');
