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
            <div class="grouping-select">
                <button id="grouping-toggle" class="toggle-button">📆 Regrouper par : <span id="grouping-label">Jour</span> ⏷</button>
                <ul id="grouping-options" class="dropdown hidden">
                    <li data-mode="year">Année</li>
                    <li data-mode="month">Mois</li>
                    <li data-mode="week">Semaine</li>
                    <li data-mode="day">Jour</li>
                    <li data-mode="session">Session</li>
                    <li data-mode="phase">Phase</li>
                </ul>
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

        .grouping-select {
            position: relative;
            display: inline-block;
        }

        .grouping-select .dropdown {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            padding: 5px 0;
            border-radius: 4px;
            list-style: none;
            top: 100%;
            left: 0;
            margin-top: 5px;
            min-width: 160px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 20;
        }

        .grouping-select .dropdown li {
            padding: 8px 16px;
            cursor: pointer;
            color: #111;
            font-size: 14px;
        }

        .grouping-select .dropdown li:hover {
            background-color: #eee;
        }

        .grouping-select .dropdown.hidden {
            display: none;
        }

        .delete-group-btn {
            background: none;
            border: none;
            color: #888;
            font-size: 16px;
            cursor: pointer;
            margin-left: 10px;
        }
        .delete-group-btn:hover {
            color: #e74c3c;
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
    const filterDropdown = document.getElementById("filter-dropdown");

    let groupingMode = "day"; // ou "week", "month", "year", "phase", "session"
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

        // On trie les phases du plus ancien au plus récent
        const sorted = [...history].sort((a, b) => a.timestamp - b.timestamp);

        let current = [];
        for (let i = 0; i < sorted.length; i++) {
            const entry = sorted[i];
            current.push(entry);

            const next = sorted[i + 1];
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
        return allHistory;
    }

    function renderGroupedLevel(mode, entries, container) {
        const grouped = groupByMode(entries, mode);
        const keys = Object.keys(grouped).sort((a, b) => {
            const aDate = new Date(grouped[a][0].timestamp);
            const bDate = new Date(grouped[b][0].timestamp);
            return bDate - aDate;
        });

        keys.forEach(key => {
            const block = document.createElement("div");
            block.className = "session-block";

            const group = grouped[key];
            const totalTravail = group.filter(e => e.type === "Travail").reduce((sum, e) => sum + (e.duration || 0), 0);
            const totalPause = group.filter(e => e.type === "Pause").reduce((sum, e) => sum + (e.duration || 0), 0);

            block.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0;">${key}</h5>
                    <div>
                        <small>Travail : ${formatTime(totalTravail)} | Pause : ${formatTime(totalPause)}</small>
                        <button class="delete-group-btn" data-group="${key}" data-level="${mode}" title="Supprimer tout ce groupe">🗑</button>
                    </div>
                </div>
            `;

            const detail = document.createElement("div");
            detail.className = "session-details";
            detail.style.display = "none";
            block.appendChild(detail);

            block.addEventListener("click", (e) => {
                if (e.target.closest("button")) return;
                e.stopPropagation();

                const next = getNextLevel(mode);
                if (detail.innerHTML === "" && next) {
                    if (next === "session") {
                        renderSessions(groupSessions(group), detail);
                    } else if (next === "phase") {
                        renderPhases(group, detail);
                    } else {
                        renderGroupedLevel(next, group, detail);
                    }

                    detail.querySelectorAll(".delete-group-btn").forEach(btn => {
                        btn.onclick = (e) => {
                            e.stopPropagation();
                            const groupKey = btn.dataset.group;
                            const level = btn.dataset.level;

                            confirmCustom(`Supprimer toutes les entrées du groupe "${groupKey}" ?`, (ok) => {
                                if (!ok) return;

                                const toDelete = Object.entries(groupByMode(allHistory, level))
                                    .find(([label]) => label === groupKey)?.[1] || [];

                                const timestampsToDelete = toDelete.map(e => e.timestamp);
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

                                // Supprime le bloc visuellement
                                const currentBlock = btn.closest(".session-block");
                                const parentDetail = currentBlock?.parentElement;
                                currentBlock?.remove();

                                // Si le parent (session-details) devient vide, le supprimer aussi (récursivement)
                                let detailBlock = parentDetail;
                                while (
                                    detailBlock &&
                                    detailBlock.classList.contains("session-details") &&
                                    detailBlock.childElementCount === 0
                                ) {
                                    const parentBlock = detailBlock.closest(".session-block");
                                    detailBlock.remove();

                                    if (parentBlock && parentBlock.parentElement?.classList.contains("session-details")) {
                                        const outerDetail = parentBlock.parentElement;
                                        parentBlock.remove();
                                        detailBlock = outerDetail;
                                    } else {
                                        break;
                                    }
                                }
                            });
                        };
                    });


                }

                detail.style.display = detail.style.display === "block" ? "none" : "block";
            });

            container.appendChild(block);
        });
    }



    function render() {
        const data = [...allHistory].sort((a, b) => b.timestamp - a.timestamp);
        output.innerHTML = "";

        if (data.length === 0) {
            output.innerHTML = `<p class="empty-message">Aucune entrée pour ce filtre.</p>`;
            return;
        }

        if (groupingMode === "phase") {
            renderPhases(data, output);
        } else if (groupingMode === "session") {
            renderSessions(groupSessions(data), output);
        } else {
            renderGroupedLevel(groupingMode, data, output);
        }

        // Ajout des événements de suppression
        output.querySelectorAll(".delete-group-btn").forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                const group = btn.dataset.group;
                const level = btn.dataset.level;

                confirmCustom(`Supprimer toutes les entrées du groupe "${group}" ?`, (ok) => {
                    if (!ok) return;

                    const toDelete = Object.entries(groupByMode(allHistory, level))
                        .find(([label]) => label === group)?.[1] || [];

                    // Supprimer du tableau global
                    const timestampsToDelete = toDelete.map(e => e.timestamp);
                    for (let i = allHistory.length - 1; i >= 0; i--) {
                        if (timestampsToDelete.includes(allHistory[i].timestamp)) {
                            allHistory.splice(i, 1);
                        }
                    }

                    // Supprimer du local
                    sessionHistory = sessionHistory.filter(e => !timestampsToDelete.includes(e.timestamp));
                    sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

                    // MAJ WordPress
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

    }


    function renderSessions(sessions, container = output) {
        // Trier du plus ancien au plus récent pour la numérotation
        sessions.sort((a, b) => a[0].timestamp - b[0].timestamp);

        // Générer tous les blocs dans un tableau temporaire
        const blocks = sessions.map((session, index) => {
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
                        <h4 style="margin: 0;">Session ${index + 1} — ${formatDate(session[0].timestamp, false)}</h4>
                        <small>Travail : ${formatTime(totalTravail)} | Pause : ${formatTime(totalPause)}</small>
                    </div>
                    <button class="delete-session-btn" data-ts="${session[0].timestamp}" title="Supprimer cette session">🗑</button>
                </div>
            `;
            div.appendChild(details);

            div.addEventListener("click", (e) => {
                if (e.target.closest(".delete-session-btn")) return;
                if (e.target.closest(".delete-phase-btn")) return;
                e.stopPropagation();
                details.style.display = details.style.display === "block" ? "none" : "block";
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

                    if (typeof userIsLoggedIn !== "undefined" && userIsLoggedIn) {
                        fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                        });
                    }

                    // 🔁 Mise à jour du parent
                    const container = div.parentElement; // .session-details
                    const restants = Array.from(container.querySelectorAll(".delete-session-btn"))
                        .map(btn => parseInt(btn.dataset.ts))
                        .filter(t => !timestampsToDelete.includes(t));

                    if (restants.length === 0) {
                        const parentBlock = container.closest(".session-block");
                        container.remove();
                        parentBlock?.remove();
                    } else {
                        const nouvellesSessions = groupSessions(allHistory).filter(s => {
                            return restants.includes(s[0].timestamp);
                        });

                        container.innerHTML = '';
                        renderSessions(nouvellesSessions, container);
                        container.style.display = "block"; // garder ouvert
                    }
                });
            };


            return div;
        });

        // Afficher dans l'ordre inverse (plus récent en haut)
        blocks.reverse().forEach(div => container.appendChild(div));
    }




    function renderPhases(phases, container = output) {
        phases.sort((a, b) => b.timestamp - a.timestamp);
        phases.forEach(e => {
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
            container.appendChild(div);
            div.querySelector(".delete-phase-btn").onclick = (e) => {
                e.stopPropagation();
                const ts = parseInt(e.currentTarget.dataset.ts);

                confirmCustom("Supprimer cette phase ?", (ok) => {
                    if (!ok) return;

                    // Supprime la phase dans allHistory
                    for (let i = allHistory.length - 1; i >= 0; i--) {
                        if (allHistory[i].timestamp === ts) {
                            allHistory.splice(i, 1);
                            break;
                        }
                    }

                    sessionHistory = sessionHistory.filter(e => e.timestamp !== ts);
                    sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

                    if (typeof userIsLoggedIn !== "undefined" && userIsLoggedIn) {
                        fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                        });
                    }

                    // 🔁 Mise à jour du parent
                    const container = e.currentTarget.closest(".session-details");
                    const entries = Array.from(container.querySelectorAll(".delete-phase-btn"))
                        .map(btn => parseInt(btn.dataset.ts))
                        .filter(t => t !== ts); // exclut celle supprimée

                    if (entries.length === 0) {
                        const parentBlock = container.closest(".session-block");
                        container.remove();
                        parentBlock?.remove();
                    } else {
                        // recalculer les entrées à afficher
                        const phasesRestantes = allHistory.filter(p => entries.includes(p.timestamp));
                        container.innerHTML = '';
                        renderPhases(phasesRestantes, container);
                        container.style.display = "block"; // garder ouvert
                    }
                });
            };

        });
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



    
    function getNextLevel(mode) {
        switch (mode) {
            case "year": return "month";
            case "month": return "day";
            case "week": return "day";
            case "day": return "session";
            case "session": return "phase";
            default: return null;
        }
    }

    const groupingToggle = document.getElementById("grouping-toggle");
    const groupingLabel = document.getElementById("grouping-label");
    const groupingOptions = document.getElementById("grouping-options");

    function groupByMode(history, mode) {
        const grouped = {};

        history.forEach(entry => {
            const date = new Date(entry.timestamp);
            let key = '';

            if (mode === 'day') {
                key = date.toLocaleDateString('fr-FR');
            } else if (mode === 'week') {
                const year = date.getFullYear();
                const week = Math.ceil(((date - new Date(year, 0, 1)) / 86400000 + new Date(year, 0, 1).getDay() + 1) / 7);
                key = `${year} - Semaine ${week}`;
            } else if (mode === 'month') {
                key = date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
            } else if (mode === 'year') {
                key = date.getFullYear();
            }

            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(entry);
        });

        return grouped;
    }

    groupingToggle.addEventListener("click", () => {
        groupingOptions.classList.toggle("hidden");
    });

    groupingOptions.querySelectorAll("li").forEach(li => {
        li.addEventListener("click", () => {
            groupingMode = li.dataset.mode;
            groupingLabel.textContent = li.textContent;
            groupingOptions.classList.add("hidden");
            render();
        });
    });


}); // fin du IIFE
</script>
<?php
return ob_get_clean();
}
add_shortcode('flowmodoro_history', 'flowmodoro_history_shortcode');
