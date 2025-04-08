<?php
/**
 * Flowmodoro Shortcode
 * 
 * @package Flowmodoro
 */
function flowmodoro_shortcode() {
    ob_start();
    ?>
    <div id="flowmodoro-container" style="text-align: center; padding: 40px;">
        <h2>Flowmodoro</h2>
        <div id="flowmodoro-status" style="font-size: 24px; color: #888; margin-bottom: 10px;"></div>
        <div id="flowmodoro-timer" style="font-size: 60px; margin: 20px 0;">00:00:00</div>
        <button id="flowmodoro-start" style="font-size: 18px; padding: 10px 20px;">Démarrer</button>
        <button id="flowmodoro-stop" style="font-size: 18px; padding: 10px 20px;" disabled>Arrêter</button>
        <button id="flowmodoro-settings" style="font-size: 18px; padding: 10px 20px;">Paramètres</button>

        <div id="flowmodoro-settings-menu" style="display: none; margin-top: 20px; text-align: center;">
            <label for="pause-factor">Facteur de pause :</label>
            <input type="number" id="pause-factor" value="5" min="0.1" step="0.1" style="width: 75px;">
            <button id="save-settings" style="margin-left: 10px;">Enregistrer</button>
        </div>

        <div id="flowmodoro-log-wrapper" style="margin-top: 30px;">
            <h3>Historique (session)</h3>
            <ul id="flowmodoro-log" style="list-style: none; padding: 0; font-family: monospace;"></ul>
            <div id="flowmodoro-total" style="margin-top: 10px; font-weight: bold;"></div>
        </div>

        <div id="flowmodoro-history" style="position: absolute; top: 120px; right: 40px; text-align: left;">
            <button id="show-history" style="margin-top: 20px;">📜 Voir l’historique</button><br>
            <button id="show-stats" style="margin-top: 10px;">📊 Voir les statistiques</button>
        </div>
    </div>


    <style>
        .flowmodoro-history-container {
            max-width: 800px;
            margin: auto;
            padding: 25px;
            background: #ffffff;
            color: #111;
            font-family: 'Roboto', sans-serif;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .history-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .toggle-button {
            padding: 8px 14px;
            background: #3498db;
            color: white;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }

        .toggle-button:hover {
            background: #2980b9;
        }

        .session-block {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .session-block:hover {
            background: #f2f2f2;
        }

        .session-details {
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
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
            justify-content: space-between;
            align-items: center;
        }

        .view-session-btn {
            font-size: 0.8em;
            padding: 2px 8px;
            border: 1px solid #ccc;
            background: #fff;
            color: #111;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .view-session-btn:hover {
            background: #eee;
            border-color: #bbb;
        }

        .delete-session-btn,
        .delete-phase-btn,
        .delete-group-btn {
            background: none;
            border: none;
            color: #bbb;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .delete-session-btn:hover,
        .delete-phase-btn:hover,
        .delete-group-btn:hover {
            color: #e74c3c;
        }

        .grouping-select {
            position: relative;
            display: inline-block;
        }

        .grouping-select .dropdown {
            position: absolute;
            background: #fff;
            border: 1px solid #ccc;
            padding: 5px 0;
            border-radius: 4px;
            top: 100%;
            left: 0;
            margin-top: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 20;
            list-style: none;
        }

        .grouping-select .dropdown.hidden {
            display: none;
        }

        .grouping-select .dropdown li {
            padding: 8px 16px;
            cursor: pointer;
        }

        .grouping-select .dropdown li:hover {
            background-color: #eee;
        }

        .empty-message {
            font-style: italic;
            color: #888;
        }

        #popup-confirm {
            background: rgba(0,0,0,0.6);
        }

        #popup-confirm div {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        #popup-yes, #popup-no {
            background: #3498db;
            color: white;
            padding: 6px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        #popup-yes:hover, #popup-no:hover {
            background: #2980b9;
        }


    </style>

    <?php if (is_user_logged_in()) :
        $user_id = get_current_user_id();
        $raw_history = get_user_meta($user_id, 'flowmodoro_history', true);
        $history = is_string($raw_history) ? json_decode($raw_history, true) : $raw_history;
        if (!is_array($history)) $history = [];
        ?>
        <script>
        const savedHistory = <?php echo json_encode($history); ?>;
        const userIsLoggedIn = true;
        </script>
    <?php else : ?>
        <div style="color: red; margin-top: 20px;">
            <p>Connectez-vous pour sauvegarder votre historique.</p>
            <p style="margin-top: 10px;">
                <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" style="margin-right: 10px;">
                    🔐 Se connecter
                </a>
                <a href="<?php echo esc_url( wp_registration_url() ); ?>">
                    🆕 Créer un compte
                </a>
            </p>
        </div>
        <script>
        const savedHistory = [];
        const userIsLoggedIn = false;
        </script>
    <?php endif; ?>
    <script>
    (function(){
        let timer;
        let milliseconds = 0;
        let working = false;
        let reversing = false;
        let pauseFactor = 5;
        let totalWork = 0;
        let totalPause = 0;
        let allHistory = [];
        let sessionHistory = [];
        let currentLiveEntry = null;
        let liveEntryInterval = null;

        const display = document.getElementById("flowmodoro-timer");
        const status = document.getElementById("flowmodoro-status");
        const startBtn = document.getElementById("flowmodoro-start");
        const stopBtn = document.getElementById("flowmodoro-stop");
        const settingsBtn = document.getElementById("flowmodoro-settings");
        const settingsMenu = document.getElementById("flowmodoro-settings-menu");
        const pauseInput = document.getElementById("pause-factor");
        const saveBtn = document.getElementById("save-settings");
        const log = document.getElementById("flowmodoro-log");

        if (savedHistory.length > 0) {
            allHistory = savedHistory.map(e => ({
                ...e,
                timestamp: e.timestamp || Date.now()
            }));
            renderHistory("session");
            updateTotals();
        }

        function formatTime(ms) {
            const totalSec = Math.floor(ms / 1000);
            const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
            const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
            const s = String(totalSec % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        }

        function update() {
            const totalDeci = Math.floor(milliseconds / 100); // 1/10e de seconde
            const h = Math.floor(totalDeci / 36000);
            const m = String(Math.floor((totalDeci % 36000) / 600)).padStart(2, '0');
            const s = String(Math.floor((totalDeci % 600) / 10)).padStart(2, '0');
            const d = String(totalDeci % 10); // dixième
 
            display.innerHTML = h > 0
            ? `${String(h).padStart(2, '0')}:${m}:${s}<span style="font-size: 60%;">.${d}</span>`
            : `${m}:${s}<span style="font-size: 60%;">.${d}</span>`;
        }

        function logHistory(type, duration) {
            clearInterval(liveEntryInterval);
            if (currentLiveEntry) {
                currentLiveEntry.duration = duration;
                currentLiveEntry.end = Date.now();
                currentLiveEntry = null;
            }

            const entry = {
                type,
                duration,
                timestamp: Date.now()
            };

            allHistory.push(entry);
            sessionHistory.push(entry);
            sessionStorage.setItem("flowmodoro_session", JSON.stringify(sessionHistory));

            renderHistory("session");
            updateTotals();

            if (userIsLoggedIn) {
                fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "history=" + encodeURIComponent(JSON.stringify(allHistory))
                });
            }
        }

        function renderHistory() {
            if (!log) return;
            log.innerHTML = "";
            sessionHistory.forEach(item => {
                const li = document.createElement("li");
                li.textContent = `${item.type} : ${formatTime(item.duration)}`;
                li.style.color = item.type === "Travail" ? "#e74c3c" : "#3498db";
                log.appendChild(li);
            });
        }

        function updateTotals() {
            const totalDiv = document.getElementById("flowmodoro-total");
            totalDiv.innerHTML = `
                Total Travail : ${formatTime(totalWork)}<br>
                Total Pause : ${formatTime(totalPause)}
            `;
        }

        function renderLiveEntry() {
            clearInterval(liveEntryInterval);
            if (!log || !currentLiveEntry) return;

            const li = document.createElement("li");
            li.style.color = currentLiveEntry.type === "Travail" ? "#e74c3c" : "#3498db";
            log.appendChild(li);

            function updateLive(addToTotal = false) {
                const now = Date.now();
                const elapsed = now - currentLiveEntry.start;

                let displayTime = formatTime(elapsed)
                li.textContent = `${currentLiveEntry.type} (en cours) : ${displayTime}`;
                if (addToTotal) {
                    if (currentLiveEntry.type === "Travail") {
                        totalWork += 1000;
                    } else if (currentLiveEntry.type === "Pause") {
                        totalPause += 1000;
                    }
                    updateTotals();
                }
            }

            updateLive(false); // premier affichage, pas d'ajout de temps
            liveEntryInterval = setInterval(() => updateLive(true), 1000);

        }

        settingsBtn.addEventListener("click", () => {
            settingsMenu.style.display = settingsMenu.style.display === "none" ? "block" : "none";
            pauseInput.value = pauseFactor;
        });

        saveBtn.addEventListener("click", () => {
            const value = parseFloat(pauseInput.value);
            if (!isNaN(value) && value > 0) {
                pauseFactor = value;
                alert("Paramètres enregistrés : facteur de pause = " + pauseFactor);
                settingsMenu.style.display = "none";
            } else {
                alert("Veuillez entrer un nombre valide supérieur à 0.");
            }
        });

        let lastUpdateTimestamp = null;

        startBtn.addEventListener("click", () => {
            if (timer) clearInterval(timer);
            startBtn.disabled = true;
            stopBtn.disabled = false;
            status.textContent = "";
            working = true;
            reversing = false;
            milliseconds = 0;

            currentLiveEntry = {
                type: "Travail",
                start: Date.now()
            };
            renderLiveEntry();

            lastUpdateTimestamp = Date.now();
            timer = setInterval(() => {
                const now = Date.now();
                const delta = now - lastUpdateTimestamp;
                lastUpdateTimestamp = now;
                milliseconds += delta;
                update();
            }, 50); // pas besoin de 10ms pour une précision utile
        });

        stopBtn.addEventListener("click", () => {
            clearInterval(timer);
            stopBtn.disabled = true;
            status.textContent = "Pause";
            working = false;
            reversing = true;

            const pauseDuration = Math.floor(milliseconds / pauseFactor);
            logHistory("Travail", milliseconds);

            currentLiveEntry = {
                type: "Pause",
                start: Date.now(),
                duration: pauseDuration
            };
            renderLiveEntry();

            let pauseRemaining = pauseDuration;
            lastUpdateTimestamp = Date.now();

            timer = setInterval(() => {
                const now = Date.now();
                const delta = now - lastUpdateTimestamp;
                lastUpdateTimestamp = now;

                pauseRemaining -= delta;
                milliseconds = pauseRemaining;
                update();

                if (pauseRemaining <= 0) {
                    clearInterval(timer);
                    status.textContent = "";
                    milliseconds = 0;
                    logHistory("Pause", pauseDuration);
                    update();
                    startBtn.disabled = false;
                }
            }, 100);
        });
        
        update();

    
        document.getElementById("show-history").addEventListener("click", () => {
            const encoded = encodeURIComponent(JSON.stringify(sessionHistory));
            window.open("/historique-flowmodoro?session=" + encoded, "_blank");
        });

        document.getElementById("show-stats").addEventListener("click", () => {
            window.open("/statistiques-flowmodoro", "_blank");
        });


    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro', 'flowmodoro_shortcode');
