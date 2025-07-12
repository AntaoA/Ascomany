<?php
/**
 * Flowmodoro History Shortcode V4
 *
 * @package Flowmodoro
 */
function flowmodoro_history_shortcode() {
    if (!is_user_logged_in()) {
        return '
            <div style="max-width: 600px; margin: 60px auto; text-align: center; font-family: sans-serif;">
                <p style="font-size: 18px;">Vous devez être connecté pour consulter votre historique.</p>
                <a href="/flowmodoro" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2c80c4; color: white; text-decoration: none; border-radius: 6px; font-size: 16px;">
                    ⏱️ Retour au timer
                </a>
            </div>
        ';
    }
    

    ob_start();
    ?>
    <div class="flowmodoro-history-container">
        <h2>📜 Historique Flowmodoro</h2>

        <!-- BOUTONS TIMER ET STATISTIQUES EN HAUT À DROITE -->
        <div id="flowmodoro-right-panel">
            <div class="flowmodoro-history-actions">
                <button id="show-timer" class="flowmodoro-main-btn full-width-btn">⏱️ Voir le timer</button>
                <button id="show-stats" class="flowmodoro-main-btn full-width-btn">📊 Voir les statistiques</button>
            </div>
        </div>

        <div class="history-controls">
            <div class="grouping-select">
                <button id="grouping-toggle" class="toggle-button">
                    📆 Regrouper par : <span id="grouping-label">Jour</span> ⏷
                </button>
                <ul id="grouping-options" class="dropdown hidden">
                    <li data-mode="year">Année</li>
                    <li data-mode="month">Mois</li>
                    <li data-mode="week">Semaine</li>
                    <li data-mode="day">Jour</li>
                    <li data-mode="session">Session</li>
                    <li data-mode="phase">Phase</li>
                </ul>
            </div>

            <div class="limit-select">
                <label for="limit">Limiter à : </label>
                <select id="item-limit">
                    <option value="10">10 éléments</option>
                    <option value="20" selected>20 éléments</option>
                    <option value="50">50 éléments</option>
                    <option value="0">Tous</option>
                </select>
            </div>
        </div>
        
        <div id="history-output"></div>

        <div id="popup-confirm" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 9999;">
            <div style="background: white; padding: 20px; border-radius: 8px; max-width: 400px; text-align: center; color: #111;">
                <p id="popup-message" style="margin-bottom: 20px;">Êtes-vous sûr ?</p>
                <button id="popup-yes" style="margin-right: 10px;">Oui</button>
                <button id="popup-no">Non</button>
            </div>
        </div>

        <div id="flowmodoro-feedback-button">💬 Feedback</div>

        <div id="flowmodoro-feedback-modal" style="display: none;">
            <div id="flowmodoro-feedback-content">
                <h3>Votre retour</h3>
                <select id="feedback-type">
                    <option value="avis">Donner un avis</option>
                    <option value="bug">Signaler un bug</option>
                    <option value="suggestion">Suggérer une amélioration</option>
                </select>
                <textarea id="feedback-text" placeholder="Votre message..." rows="5"></textarea>
                <div style="margin-top: 10px; text-align: right;">
                    <button id="send-feedback">Envoyer</button>
                    <button id="cancel-feedback">Annuler</button>
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
            max-width: 1000px;
            margin: auto;
            padding: 30px;
            font-family: 'Roboto', sans-serif;
            position: relative;
            background: #fafafa;
            color: #111;
        }

        .history-controls {
            margin-top: 50px; /* Ajout de marge pour éviter chevauchement avec les boutons */
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
            width: 100%;
        }

        .entry-travail {
            color: #e74c3c;
        }

        .entry-line.pause {
            background-color: #f6f6f6;
            border-radius: 4px;
        }

        .entry-phase {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .phase-left {
            flex: 1;
            min-width: 0;
        }

        .phase-right {
            margin-left: 12px;
            flex-shrink: 0;
        }

        .phase-left.travail {
            color: #e74c3c;
        }
        .phase-left.pause {
            color: #3498db;
        }


        .phase-left.pause {
            background-color: #f6f6f6;
            border-radius: 4px;
            padding: 2px 4px;
        }

        .phase-left.travail {
            color: #e74c3c;
        }

        .phase-left.pause {
            color: #3498db;
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


        .flowmodoro-main-btn {
            font-size: 18px;
            padding: 10px 24px;
            background: #2c80c4;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            box-sizing: border-box;
            transition: background 0.2s ease;
        }

        .flowmodoro-main-btn:hover {
            background: #21679d;
        }

        .full-width-btn {
            width: 100%;
        }

        #flowmodoro-right-panel {
            position: fixed;
            top: 130px;
            right: 40px;
            width: 300px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 100;
        }


        .flowmodoro-history-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .flowmodoro-history-container h2 {
            margin-bottom: 25px;
        }

        #flowmodoro-feedback-button {
            position: fixed;
            top: 50px;
            right: 15px;
            background: #2c80c4;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        #flowmodoro-feedback-modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1001;
        }
        #flowmodoro-feedback-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }
        #feedback-text {
            width: 100%;
            margin-top: 10px;
            font-size: 16px;
        }
        #flowmodoro-feedback-content select,
        #flowmodoro-feedback-content textarea,
        #flowmodoro-feedback-content button {
            font-size: 16px;
        }
        #flowmodoro-feedback-content button {
            padding: 6px 12px;
            border: none;
            background: #2c80c4;
            color: white;
            border-radius: 4px;
            margin-left: 5px;
        }
        #flowmodoro-feedback-content button:hover {
            background: #21679d;
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
    
    let itemLimit = 20;
    let currentPage = 1;

    document.getElementById("item-limit").addEventListener("change", (e) => {
        itemLimit = parseInt(e.target.value);
        currentPage = 1;
        render();
    });

    function paginate(items, page, limit) {
        if (limit === 0) return items; // 0 = no limit
        const start = (page - 1) * limit;
        return items.slice(start, start + limit);
    }

    function renderPagination(totalItems, container) {
        const totalPages = itemLimit === 0 ? 1 : Math.ceil(totalItems / itemLimit);
        if (totalPages <= 1) return;

        const nav = document.createElement("div");
        nav.style.textAlign = "center";
        nav.style.marginTop = "15px";

        const prev = document.createElement("button");
        prev.textContent = "← Précédent";
        prev.disabled = currentPage === 1;
        prev.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                render();
            }
        };

        const next = document.createElement("button");
        next.textContent = "Suivant →";
        next.disabled = currentPage >= totalPages;
        next.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                render();
            }
        };

        const label = document.createElement("span");
        label.textContent = ` Page ${currentPage} / ${totalPages} `;
        label.style.margin = "0 10px";

        nav.appendChild(prev);
        nav.appendChild(label);
        nav.appendChild(next);
        container.appendChild(nav);
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

    const phaseNumbers = new Map();

    function updatePhaseNumbers() {
        phaseNumbers.clear();
        [...allHistory]
            .sort((a, b) => a.timestamp - b.timestamp || a.type.localeCompare(b.type))
            .forEach((e, i) => {
                phaseNumbers.set(JSON.stringify(e), i + 1);
            });
    }
    updatePhaseNumbers();


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
        if (!Array.isArray(entries)) {
            console.error("renderGroupedLevel expected an array but got:", entries);
            return;
        }

        const grouped = groupByMode(entries, mode);
        const keys = Object.keys(grouped).sort((a, b) => {
            const aDate = new Date(deepFlatPhases(grouped[a])[0]?.timestamp || 0);
            const bDate = new Date(deepFlatPhases(grouped[b])[0]?.timestamp || 0);
            return bDate - aDate;
        });

        keys.forEach(key => {
            const block = document.createElement("div");
            block.className = "session-block";

            const group = grouped[key];
            const flatGroup = deepFlatPhases(group);

            const totalTravail = flatGroup.filter(e => e.type === "Travail").reduce((sum, e) => sum + (e.duration || 0), 0);
            const totalPause = flatGroup.filter(e => e.type === "Pause").reduce((sum, e) => sum + (e.duration || 0), 0);
            const realPause = computeRealPause(flatGroup);
            const percentPause = (realPause === 0 || totalPause === 0) ? 100 : (totalPause / realPause) * 100 || 0;

            block.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0;">${key}</h5>
                    <div>
                        <small>
                            Travail : ${formatTime(totalTravail)} |
                            Pause : ${formatTime(totalPause)} |
                            Pause réelle : ${formatTime(realPause)} |
                            % Pause comptabilisée : ${percentPause.toFixed(1)}%
                        </small>
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
                        renderSessions(groupSessions(flatGroup), detail);
                    } else if (next === "phase") {
                        renderPhases(flatGroup, detail);
                    } else {
                        renderGroupedLevel(next, flatGroup, detail);
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

                                const toDeleteFlat = deepFlatPhases(toDelete);
                                const timestampsToDelete = toDeleteFlat.map(e => e.timestamp);

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

                                const currentBlock = btn.closest(".session-block");
                                const parentDetail = currentBlock?.parentElement;
                                currentBlock?.remove();

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

        attachDeletePhaseHandlers();
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

    function deepFlatPhases(input) {
        const result = [];

        const flatten = (arr) => {
            for (const item of arr) {
                if (Array.isArray(item)) {
                    flatten(item); // récursion
                } else if (item && typeof item === 'object' && 'timestamp' in item && 'type' in item) {
                    result.push(item); // on considère que c’est une phase valide
                }
            }
        };

        flatten(input);
        return result;
    }


    [...allHistory]
        .sort((a, b) => a.timestamp - b.timestamp || a.type.localeCompare(b.type))
        .forEach((e, i) => {
            // On utilise JSON.stringify comme clé unique fiable
            phaseNumbers.set(JSON.stringify(e), i + 1);
        });
    

        function computeRealPause(phases) {
            const sessions = groupSessions(deepFlatPhases(phases));
            let totalRealPause = 0;

            for (const session of sessions) {
                // On trie les phases dans l’ordre
                const sorted = [...session].sort((a, b) => a.timestamp - b.timestamp);

                for (let i = 0; i < sorted.length; i++) {
                    const current = sorted[i];
                    const next = sorted[i + 1];

                    // Si la phase actuelle est une pause
                    if (current.type === "Pause") {
                        // Et qu'elle est suivie d'un travail dans la même session
                        if (next && next.type === "Travail") {
                            const pauseEnd = current.timestamp + (current.duration || 0);
                            const nextStart = next.timestamp;

                            const gap = Math.max(0, nextStart - pauseEnd); // temps d'inactivité entre pause et travail
                            totalRealPause += (current.duration || 0) + gap;
                        }
                        // Sinon (pause terminale, ou sans suite de travail) => ignorée
                    }
                }
            }

            return totalRealPause;
        }




    function renderSessions(sessions, container = output) {
        sessions.sort((a, b) => b[0].timestamp - a[0].timestamp);
        const paginated = paginate(sessions, currentPage, itemLimit);

        const globalSessions = groupSessions(allHistory).sort((a, b) => a[0].timestamp - b[0].timestamp);
        const sessionNumbers = new Map();
        globalSessions.forEach((s, i) => {
            const key = s.map(e => e.timestamp).join("-");
            sessionNumbers.set(key, i + 1);
        });

        paginated.forEach((session, idx) => {
            const div = document.createElement("div");
            div.className = "session-block";

            let totalTravail = 0, totalPause = 0;
            session.forEach(e => {
                if (e.type === "Travail") totalTravail += e.duration || 0;
                if (e.type === "Pause") totalPause += e.duration || 0;
            });

            const realPause = computeRealPause(session);
            const pause = session.filter(e => e.type === "Pause").reduce((a, b) => a + (b.duration || 0), 0);
            const percentPause = (pause === 0 && realPause === 0) ? 100 : (pause / realPause) * 100 || 0;

            const details = document.createElement("div");
            details.className = "session-details";

            session.forEach(e => {
                const line = document.createElement("div");
                line.className = "entry-line " + (e.type === "Pause" ? "pause" : "");
                const phaseLeftClass = "phase-left " + (e.type === "Pause" ? "pause" : "travail");
                const phaseNum = phaseNumbers.get(JSON.stringify(e));
                line.innerHTML = `
                    <div class="entry-phase">
                        <div class="${phaseLeftClass}">
                            Phase ${phaseNum} — ${e.type} — ${formatTime(e.duration)} — ${formatDate(e.timestamp)}
                        </div>
                        <div class="phase-right">
                            <button class="delete-phase-btn" data-ts="${e.timestamp}" title="Supprimer cette phase">🗑</button>
                        </div>
                    </div>
                `;
                details.appendChild(line);
            });


            const sessionKey = session.map(e => e.timestamp).join("-");
            const sessionNum = sessionNumbers.get(sessionKey);
            const firstPhase = session[0];
            const sessionDate = formatDate(firstPhase.timestamp, false);
            const sessionTime = new Date(firstPhase.timestamp).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });


            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0;">Session ${sessionNum} — ${sessionDate} à ${sessionTime}</h4>
                        <small>
                            Travail : ${formatTime(totalTravail)} |
                            Pause : ${formatTime(totalPause)} |
                            Pause réelle : ${formatTime(realPause)} |
                            % Pause comptabilisée : ${percentPause.toFixed(1)}%
                        </small>
                    </div>
                    <button class="delete-session-btn" data-ts="${session[0].timestamp}" title="Supprimer cette session">🗑</button>
                </div>
            `;
            
            div.querySelector(".delete-session-btn")?.addEventListener("click", (e) => {
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
                updatePhaseNumbers();
                render(); // 🔁 plus propre et suffisant ici
            });
        });

            div.appendChild(details);
            div.addEventListener("click", (e) => {
                if (e.target.closest(".delete-session-btn") || e.target.closest(".delete-phase-btn")) return;
                e.stopPropagation();
                details.style.display = details.style.display === "block" ? "none" : "block";
            });

            container.appendChild(div);
        });

        attachDeletePhaseHandlers();
        renderPagination(sessions.length, container);
    }




    function renderPhases(phases, container = output) {
        container.innerHTML = "";
        phases.sort((a, b) => b.timestamp - a.timestamp); // plus récent en premier

        const paginated = paginate(phases, currentPage, itemLimit);
        
        paginated.forEach((e, i) => {
            const isTravail = e.type === "Travail";
            const icon = isTravail ? "💼" : "☕";
            const color = isTravail ? "#e74c3c" : "#3498db";
            const startTs = e.timestamp
            const phaseNum = phaseNumbers.get(JSON.stringify(e));

            const div = document.createElement("div");
            div.className = "session-block";
            div.style.borderLeft = `6px solid ${color}`;
            div.style.cursor = "pointer";

            div.innerHTML = `
                <div class="entry-phase" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="color: ${color}; font-weight: bold;">${icon} Phase ${phaseNum} — ${e.type}</div>
                    <div>${formatTime(e.duration)} — ${formatDate(startTs)}</div>
                    <div>
                        <button class="delete-phase-btn" data-ts="${e.timestamp}">🗑</button>
                    </div>
                </div>
            `;

            

            const sessionDetail = document.createElement("div");
            sessionDetail.className = "session-details";
            sessionDetail.style.display = "none";
            sessionDetail.style.padding = "10px";
            sessionDetail.style.borderTop = "1px solid #ddd";
            sessionDetail.style.marginTop = "10px";
            sessionDetail.style.fontSize = "14px";
            div.appendChild(sessionDetail);

            div.addEventListener("click", (ev) => {
                if (ev.target.closest(".delete-phase-btn")) return;

                if (sessionDetail.innerHTML !== "") {
                    sessionDetail.style.display = sessionDetail.style.display === "none" ? "block" : "none";
                    return;
                }

                const sessions = groupSessions(allHistory);
                const session = sessions.find(s => s.some(p => p.timestamp === e.timestamp));
                if (!session) return;

                const travail = session.filter(p => p.type === "Travail").reduce((a, b) => a + (b.duration || 0), 0);
                const realPause = computeRealPause(session);
                const pause = session.filter(e => e.type === "Pause").reduce((a, b) => a + (b.duration || 0), 0);
                const percentPause = (pause === 0 && realPause === 0) ? 100 : (pause / realPause) * 100 || 0;
                const start = new Date(session[0].timestamp).toLocaleString();

                sessionDetail.innerHTML = `
                    <strong>Session contenant cette phase :</strong><br>
                    Début : ${start}<br>
                    Travail : ${formatTime(travail)}<br>
                    Pause : ${formatTime(pause)}<br>
                    Temps réel de pause : ${formatTime(realPause)}<br>
                    Pourcentage de pause réelle : ${percentPause} %<br>
                    Phases : ${session.length}
                    <div style="margin-top: 10px;">
                        <a href="/historique?focus=session:${session[0].timestamp}" class="view-session-btn">
                            👁 Voir la session complète
                        </a>
                    </div>
                `;
                sessionDetail.style.display = "block";
            });

            container.appendChild(div);
        });

        attachDeletePhaseHandlers();
        renderPagination(phases.length, container);
    }







    function attachDeletePhaseHandlers() {
        document.querySelectorAll(".delete-phase-btn").forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                const ts = parseInt(btn.dataset.ts);

                confirmCustom("Supprimer cette phase ?", (ok) => {
                    if (!ok) return;

                    // Supprimer de allHistory
                    for (let i = allHistory.length - 1; i >= 0; i--) {
                        if (allHistory[i].timestamp === ts) {
                            allHistory.splice(i, 1);
                            break;
                        }
                    }

                    // Supprimer de sessionHistory (localStorage)
                    sessionHistory = sessionHistory.filter(e => e.timestamp !== ts);
                    sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

                    updatePhaseNumbers();

                    // Sauvegarde WordPress
                    if (typeof userIsLoggedIn !== "undefined" && userIsLoggedIn) {
                        fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                        });
                    }

                    // Supprimer visuellement la ligne
                    const line = btn.closest(".entry-line");
                    const detailBlock = btn.closest(".session-details");
                    if (line) line.remove();

                    if (!detailBlock) return;

                    // Si la session n’a plus de phases, on supprime le bloc parent
                    if (detailBlock.childElementCount === 0) {
                        const parentBlock = detailBlock.closest(".session-block");
                        const outerDetail = parentBlock?.parentElement;

                        parentBlock?.remove();

                        // Si le parent est une vue session imbriquée, on recalcule les sessions restantes dans ce bloc
                        if (outerDetail && outerDetail.classList.contains("session-details")) {
                            const remainingEntries = [];

                            outerDetail.querySelectorAll(".delete-phase-btn").forEach(btn => {
                                const ts = parseInt(btn.dataset.ts);
                                const item = allHistory.find(e => e.timestamp === ts);
                                if (item) remainingEntries.push(item);
                            });

                            outerDetail.innerHTML = "";
                            if (remainingEntries.length > 0) {
                                const sessions = groupSessions(remainingEntries);
                                renderSessions(sessions, outerDetail); // 🔁 avec renumérotation
                            } else {
                                // suppression récursive
                                let current = outerDetail;
                                while (
                                    current &&
                                    current.classList.contains("session-details") &&
                                    current.childElementCount === 0
                                ) {
                                    const sessionBlock = current.closest(".session-block");
                                    current.remove();

                                    if (sessionBlock && sessionBlock.parentElement?.classList.contains("session-details")) {
                                        current = sessionBlock.parentElement;
                                        sessionBlock.remove();
                                    } else {
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    // Si la session n’a plus de phases, supprimer récursivement
                    let current = detailBlock;
                    while (
                        current &&
                        current.classList.contains("session-details") &&
                        current.childElementCount === 0
                    ) {
                        const sessionBlock = current.closest(".session-block");
                        current.remove();

                        if (sessionBlock && sessionBlock.parentElement?.classList.contains("session-details")) {
                            current = sessionBlock.parentElement;
                            sessionBlock.remove();
                        } else {
                            break;
                        }
                    }
                });
            };
        });
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

    function getISOWeek(date) {
        const temp = new Date(date.getTime());
        temp.setHours(0, 0, 0, 0);
        // Jeudi dans la semaine courante
        temp.setDate(temp.getDate() + 3 - ((temp.getDay() + 6) % 7));
        const week1 = new Date(temp.getFullYear(), 0, 4);
        return 1 + Math.round(((temp.getTime() - week1.getTime()) / 86400000 - 3 + ((week1.getDay() + 6) % 7)) / 7);
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function groupByMode(history, mode) {
        const grouped = {};

        history.forEach(entry => {
            const date = new Date(entry.timestamp);
            let key = '';

            if (mode === 'day') {
                key = capitalize(date.toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }));
            } else if (mode === 'week') {
                const year = date.getFullYear();
                const week = getISOWeek(date);
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


    document.getElementById("show-timer").addEventListener("click", () => {
        window.location.href = "/flowmodoro";
    });

    document.getElementById("show-stats").addEventListener("click", () => {
        window.location.href = "/statistiques-flowmodoro";
    });


    const focusParam = new URLSearchParams(window.location.search).get("focus");

    if (focusParam) {
        const [level, target] = focusParam.split(":");
        const tsTarget = parseInt(target);
        const normalized = str => str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();

        const switchToMode = (mode, callback) => {
            const li = document.querySelector(`#grouping-options li[data-mode="${mode}"]`);
            if (li) {
                li.click();
                setTimeout(callback, 300);
            } else {
                callback();
            }
        };

        const findElementAndClick = (selector, matchFn) => {
            const blocks = document.querySelectorAll(selector);
            for (const block of blocks) {
                if (matchFn(block)) {
                    block.scrollIntoView({ behavior: "smooth", block: "center" });
                    block.click();
                    break;
                }
            }
        };

        if (["phase", "session"].includes(level)) {
            const isPhase = level === "phase";
            switchToMode(level, () => {
                // Cherche l’index dans la liste complète
                let fullList = isPhase
                    ? [...allHistory].sort((a, b) => b.timestamp - a.timestamp)
                    : groupSessions(allHistory).sort((a, b) => b[0].timestamp - a[0].timestamp);

                const index = isPhase
                    ? fullList.findIndex(p => p.timestamp === tsTarget)
                    : fullList.findIndex(s => s.some(p => p.timestamp === tsTarget));

                if (index === -1) return;

                // Calcule la page, puis render, puis simule le clic
                currentPage = Math.floor(index / itemLimit) + 1;
                render();

                setTimeout(() => {
                    findElementAndClick(
                        ".session-block",
                        block => {
                            const btn = block.querySelector(isPhase ? `.delete-phase-btn[data-ts="${tsTarget}"]` : `.delete-session-btn[data-ts="${tsTarget}"]`);
                            return !!btn;
                        }
                    );
                }, 300);
            });
        }

        else if (level === "day") {
            const d = new Date(tsTarget);
            d.setHours(0, 0, 0, 0);
            const dateStr = d.toLocaleDateString("fr-FR");

            switchToMode("day", () => {
                setTimeout(() => {
                    findElementAndClick(".session-block", block => {
                        const h = block.querySelector("h5");
                        return h && normalized(h.textContent).includes(normalized(dateStr));
                    });
                }, 300);
            });
        }

        else if (level === "month") {
            const [y, m] = target.split("-");
            const monthName = new Date(`${y}-${m}-01`).toLocaleString('fr-FR', { month: 'long' });
            const label = `${monthName} ${y}`;

            switchToMode("month", () => {
                setTimeout(() => {
                    findElementAndClick(".session-block", block => {
                        const h = block.querySelector("h5");
                        return h && normalized(h.textContent).includes(normalized(label));
                    });
                }, 300);
            });
        }

        else if (level === "year") {
            switchToMode("year", () => {
                setTimeout(() => {
                    findElementAndClick(".session-block", block => {
                        const h = block.querySelector("h5");
                        return h && h.textContent.includes(target);
                    });
                }, 300);
            });
        }


        else if (level === "week") {
            const [y, w] = target.split("-W");
            const label = `${y} - Semaine ${parseInt(w)}`;

            switchToMode("week", () => {
                setTimeout(() => {
                    findElementAndClick(".session-block", block => {
                        const h = block.querySelector("h5");
                        return h && normalized(h.textContent).includes(normalized(label));
                    });
                }, 300);
            });
        }

    }


    document.getElementById("flowmodoro-feedback-button").addEventListener("click", () => {
            document.getElementById("flowmodoro-feedback-modal").style.display = "flex";
        });
        document.getElementById("cancel-feedback").addEventListener("click", () => {
            document.getElementById("flowmodoro-feedback-modal").style.display = "none";
        });
        document.getElementById("send-feedback").addEventListener("click", () => {
            const type = document.getElementById("feedback-type").value;
            const text = document.getElementById("feedback-text").value.trim();

            if (!text) return alert("Merci de remplir votre message.");

            fetch('/wp-admin/admin-ajax.php?action=flowmodoro_send_feedback', {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ type, text })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Merci pour votre retour !");
                    document.getElementById("flowmodoro-feedback-modal").style.display = "none";
                    document.getElementById("feedback-text").value = "";
                } else {
                    alert("Erreur : " + data.data);
                }
            })
            .catch(err => {
                console.error("Erreur d'envoi :", err);
                alert("Une erreur est survenue.");
            });

            document.getElementById("flowmodoro-feedback-modal").style.display = "none";
            document.getElementById("feedback-text").value = "";
        });









}); // fin du IIFE
</script>
<?php
return ob_get_clean();
}
add_shortcode('flowmodoro_history', 'flowmodoro_history_shortcode');
