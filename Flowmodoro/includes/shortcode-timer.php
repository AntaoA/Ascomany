<?php
/**
 * Flowmodoro Shortcode
 * 
 * @package Flowmodoro
 */
function flowmodoro_shortcode() {
    ob_start();
    ?>
    <div id="flowmodoro-container">
        <!-- BOUTONS EN HAUT À DROITE -->
        <div id="flowmodoro-right-panel">
            <div class="flowmodoro-history-actions">
                <button id="show-history" class="flowmodoro-main-btn full-width-btn">📜 Voir l’historique</button>
                <button id="show-stats" class="flowmodoro-main-btn full-width-btn">📊 Voir les statistiques</button>
            </div>

            <div id="flowmodoro-log-wrapper" class="flowmodoro-history-log">
                <h3>Historique (session)</h3>
                <div id="flowmodoro-total"></div>
                <ul id="flowmodoro-log"></ul>
            </div>
        </div>

        <!-- TIMER + INFO GAUCHE + PAUSE DROITE -->
        <div id="flowmodoro-layout-wrapper">
            <div id="flowmodoro-left-text" class="side-info-box">
                Lancez-vous dans une session de travail
            </div>

            <div id="flowmodoro-timer-wrapper">
                <div id="flowmodoro-timer">00:00:00</div>
            </div>

            <div id="pause-expected-box" class="side-info-box" style="visibility: hidden;">
                🕒 Pause attendue :&nbsp;<span id="pause-expected-time">00:00</span>
            </div>
        </div>

        <!-- BOUTONS DEMARRER + PARAMETRES -->
        <div class="flowmodoro-controls">
            <button id="flowmodoro-toggle" class="flowmodoro-main-btn">▶️ Démarrer</button>
            <button id="flowmodoro-settings" class="flowmodoro-main-btn">⚙️ Paramètres</button>
        </div>

        <!-- MENU PARAMETRES -->
        <div id="flowmodoro-settings-menu" style="display: none;">
            <label for="pause-factor">Facteur de pause :</label>
            <input type="number" id="pause-factor" value="5" min="0.1" step="0.1" style="width: 75px;">

            <label for="sound-upload">Son de fin de pause :</label>
            <input type="file" id="sound-upload" accept="audio/mpeg">

            <button id="reset-sound" class="flowmodoro-main-btn">🔁 Son par défaut</button>
            <button id="save-settings" class="flowmodoro-main-btn">Enregistrer</button>
        </div>
        <audio id="flowmodoro-sound" preload="auto">
            <source id="flowmodoro-sound-src" src="<?php echo plugin_dir_url(__FILE__); ?>assets/end-pause.mp3" type="audio/mpeg">
        </audio>    

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

    </div>



    <style>
        /* === CONTAINER PRINCIPAL === */
        #flowmodoro-container {
            text-align: center;
            padding: 40px;
            max-width: 1200px;
            margin: auto;
            position: relative;
            font-family: 'Roboto', sans-serif;
        }


        /* === BOUTONS HISTORIQUE + STATS EN HAUT À DROITE === */
        #flowmodoro-right-panel {
            position: fixed;
            top: 130px;           /* ↓ un peu plus bas */
            right: 40px;         /* ← un peu plus à gauche */
            width: 300px;        /* ⬅️ plus large pour éviter retour à la ligne */
            text-align: left;
            z-index: 100;
        }

        .flowmodoro-history-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;           /* un peu plus d'espace entre les boutons */
        }


        #pause-expected-box {
            white-space: normal;
        }

        .flowmodoro-history-log {
            font-family: monospace;
            margin-top: 25px;    /* espace sous les boutons */
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

        .full-width-btn {
            width: 100%;
        }

        .flowmodoro-main-btn:hover {
            background: #21679d;
        }

        /* === BLOC DU TIMER AU CENTRE === */
        #flowmodoro-layout-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 80px; /* plus d’espace entre les blocs */
            margin: 60px auto;
            max-width: 1400px; /* plus large */
            position: relative;
            flex-wrap: nowrap; /* important pour forcer tout sur une ligne */
        }

        #flowmodoro-timer-wrapper {
            background: #f9fbfd;
            border: 2px solid #dce6f2;
            border-radius: 20px;
            padding: 50px 100px; /* plus large horizontalement */
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.06);
            text-align: center;
            min-width: 500px; /* augmentation claire */
            max-width: 600px;
        }

        #flowmodoro-timer {
            white-space: nowrap; /* empêche le texte de sauter à la ligne */
        }

        #flowmodoro-timer {
            font-size: 84px; /* au lieu de 72px */
            font-weight: bold;
            color: #2c80c4;
            user-select: none;
        }

        /* === BLOCS LATÉRAUX GAUCHE / DROITE DU TIMER === */
        .side-info-box {
            background: #f0f4fa;
            border: 1px solid #dce6f2;
            border-radius: 14px;
            padding: 20px 20px;
            min-width: 270px;
            max-width: 300px;
            font-size: 17px;
            color: #333;
            text-align: center;
            min-height: 110px;
            display: flex;
            justify-content: center;
            align-items: center;
        }


        /* === BOUTONS PARAMÈTRES ET CONTROLES === */
        .flowmodoro-controls {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        #flowmodoro-settings-menu {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        #flowmodoro-settings-menu label,
        #flowmodoro-settings-menu input {
            font-size: 16px;
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
        const toggleBtn = document.getElementById("flowmodoro-toggle");
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

        let lastPhaseEndedWithPause = false;


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
                timestamp: Date.now() - duration
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
            [...sessionHistory].reverse().forEach(item => {
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

        function updatePauseExpected() {
            const pauseBox = document.getElementById("pause-expected-box");

            if (working && !reversing) {
                const pauseMs = Math.floor(milliseconds / pauseFactor);
                const min = Math.floor(pauseMs / 60000);
                const sec = Math.floor((pauseMs % 60000) / 1000);
                document.getElementById("pause-expected-time").textContent =
                    `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
                pauseBox.style.visibility = "visible";
            } else {
                pauseBox.style.visibility = "hidden";
            }
        }

        function updateStatusText() {
            const statusText = document.getElementById("flowmodoro-left-text");

            if (working) {
                statusText.textContent = "💼 Travail en cours";
            } else if (reversing) {
                statusText.textContent = "☕ Pause en cours";
            } else if (lastPhaseEndedWithPause) {
                statusText.textContent = "⏰ C’est l’heure de reprendre le travail";
            } else {
                statusText.textContent = "Lancez-vous dans une session de travail";
            }
        }

        


        const audioElement = document.getElementById("flowmodoro-sound");
        const audioSource = document.getElementById("flowmodoro-sound-src");

        function loadCustomSound() {
            const customSound = localStorage.getItem("flowmodoro_custom_sound");
            if (customSound) {
                audioSource.src = customSound;
            } else {
                audioSource.src = "<?php echo plugin_dir_url(__FILE__); ?>assets/end-pause.mp3";
            }
            audioElement.load();
        }
        loadCustomSound();

        function renderLiveEntry() {
            clearInterval(liveEntryInterval);
            if (!log || !currentLiveEntry) return;

            const li = document.createElement("li");
            li.style.color = currentLiveEntry.type === "Travail" ? "#e74c3c" : "#3498db";
            log.insertBefore(li, log.firstChild);

            function updateLive(addToTotal = false) {
                const now = Date.now();
                const start = currentLiveEntry.start || (Date.now() - (currentLiveEntry.duration || 0));
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
            } else {
                return; // Ne rien faire si valeur invalide
            }

            // Enregistrement du son personnalisé
            const file = document.getElementById("sound-upload").files[0];
            if (file && file.type === "audio/mpeg") {
                const reader = new FileReader();
                reader.onload = function (e) {
                    localStorage.setItem("flowmodoro_custom_sound", e.target.result);
                    loadCustomSound();
                };
                reader.readAsDataURL(file);
            }

            settingsMenu.style.display = "none";
        });



        document.getElementById("reset-sound").addEventListener("click", () => {
            localStorage.removeItem("flowmodoro_custom_sound");
            loadCustomSound();
            alert("Son réinitialisé !");
        });

        let lastUpdateTimestamp = null;

        toggleBtn.addEventListener("click", () => {
            if (!working && !reversing) {
                // Lancer le travail
                working = true;
                reversing = false;
                lastPhaseEndedWithPause = false;
                milliseconds = 0;
                updatePauseExpected();
                updateStatusText();
                toggleBtn.textContent = "⏹️ Arrêter";

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
                    updatePauseExpected();
                    updateStatusText();
                }, 50);

            } else if (working) {
                // Passer en pause
                clearInterval(timer);
                working = false;
                reversing = true;
                updatePauseExpected();
                updateStatusText();
                toggleBtn.disabled = true;
                toggleBtn.textContent = "⏳ En pause...";

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
                        logHistory("Pause", pauseDuration);
                        toggleBtn.textContent = "▶️ Démarrer";
                        toggleBtn.disabled = false;
                        milliseconds = 0;
                        update();
                        reversing = false;
                        lastPhaseEndedWithPause = true;
                        updatePauseExpected();
                        updateStatusText();

                        // Joue le son à la fin de la pause
                        const audio = document.getElementById("flowmodoro-sound");
                        if (audio) audio.play().catch(err => console.warn("Échec du son :", err));
                    }
                }, 100);
            }
        });

        
        update();

    

        function isTimerRunning() {
            return working || reversing;
        }

        document.getElementById("show-history").addEventListener("click", () => {
            const encoded = encodeURIComponent(JSON.stringify(sessionHistory));
            if (isTimerRunning()) {
                window.open("/historique-flowmodoro?session=" + encoded, "_blank");
            } else {
                window.location.href = "/historique-flowmodoro";
            }
        });

        document.getElementById("show-stats").addEventListener("click", () => {
            if (isTimerRunning()) {
                window.open("/statistiques-flowmodoro", "_blank");
            } else {
                window.location.href = "/statistiques-flowmodoro";
            }

        });
   
        document.getElementById("flowmodoro-feedback-button").addEventListener("click", () => {
            document.getElementById("flowmodoro-feedback-modal").style.display = "flex";
        });

        if (typeof userIsLoggedIn !== "undefined" && !userIsLoggedIn) {
            const btn = document.getElementById("flowmodoro-feedback-button");
            if (btn) {
                btn.style.top = "20px";
            }
        }
        
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


    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro', 'flowmodoro_shortcode');
