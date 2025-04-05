<?php
/*
Plugin Name: Flowmodoro
Description: Timer Flowmodoro
Version: 2.0.1
Author: Ascomany
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

        <!-- Menu des paramètres -->
        <div id="flowmodoro-settings-menu" style="display: none; margin-top: 20px; text-align: center;">
            <label for="pause-factor">Facteur de pause :</label>
            <input type="number" id="pause-factor" value="5" min="0.1" step="0.1" style="width: 75px;">
            <button id="save-settings" style="margin-left: 10px;">Enregistrer</button>
        </div>

        <div id="flowmodoro-history" style="position: absolute; top: 40px; right: 40px; text-align: left;">
            <h3>Historique</h3>
            <ul id="flowmodoro-log" style="list-style: none; padding: 0; font-family: monospace;"></ul>
            <div id="flowmodoro-total" style="margin-top: 10px; font-weight: bold;"></div>
        </div>
    </div>

    <?php if (is_user_logged_in()) :
        $user_id = get_current_user_id();
        $history = get_user_meta($user_id, 'flowmodoro_history', true);
    ?>
        <script>
        const savedHistory = <?php echo json_encode($history ?: []); ?>;
        const userIsLoggedIn = true;
        </script>
    <?php else : ?>
        <p style="color: red;">Connectez-vous pour sauvegarder votre historique.</p>
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
        let pauseTarget = 0;
        let pauseFactor = 5;
        let totalWork = 0;
        let totalPause = 0;

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
            savedHistory.forEach(item => {
                const li = document.createElement("li");
                li.textContent = `${item.type} : ${formatTime(item.duration)}`;
                li.style.color = item.type === "Travail" ? "#e74c3c" : "#3498db";
                log.appendChild(li);

                if (item.type === "Travail") totalWork += item.duration;
                else totalPause += item.duration;
            });
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
            const totalCs = Math.floor(milliseconds / 10);
            const h = Math.floor(totalCs / 360000);
            const m = String(Math.floor((totalCs % 360000) / 6000)).padStart(2, '0');
            const s = String(Math.floor((totalCs % 6000) / 100)).padStart(2, '0');
            const cs = String(totalCs % 100).padStart(2, '0');

            let timeFormatted;
            if (h > 0) {
                timeFormatted = `${String(h).padStart(2, '0')}:${m}:${s}<span style="font-size: 60%;">:${cs}</span>`;
            } else {
                timeFormatted = `${m}:${s}<span style="font-size: 60%;">:${cs}</span>`;
            }

            display.innerHTML = timeFormatted;
        }


        function logHistory(type, duration) {
            const log = document.getElementById("flowmodoro-log");
            const li = document.createElement("li");
            li.textContent = `${type} : ${formatTime(duration)}`;
            li.style.color = (type === "Travail") ? "#e74c3c" : "#3498db";
            log.prepend(li);

            if (type === "Travail") {
                totalWork += duration;
            } else {
                totalPause += duration;
            }

            updateTotals();

            // 🔐 SAUVEGARDE si connecté
            if (userIsLoggedIn) {
                const historyItems = [...document.querySelectorAll('#flowmodoro-log li')].map(li => {
                    const [type, timeStr] = li.textContent.split(" : ");
                    const [h, m, s] = timeStr.split(":").map(n => parseInt(n));
                    return {
                        type,
                        duration: ((h * 3600) + (m * 60) + s) * 1000
                    };
                });

                fetch("/wp-admin/admin-ajax.php?action=save_flowmodoro", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "history=" + encodeURIComponent(JSON.stringify(historyItems))
                });
            }
        }

        function updateTotals() {
            const totalDiv = document.getElementById("flowmodoro-total");
            totalDiv.innerHTML = `
                Total Travail : ${formatTime(totalWork)}<br>
                Total Pause : ${formatTime(totalPause)}
            `;
        }

        settingsBtn.addEventListener("click", () => {
            console.log("settingsBtn clicked");
            settingsMenu.style.display = settingsMenu.style.display === "none" ? "block" : "none";
            pauseInput.value = pauseFactor;
        });
        
        saveBtn.addEventListener("click", () => {
            console.log("saveBtn clicked");
            settingsMenu.style.display = "none";
            const value = parseFloat(pauseInput.value);
            if (!isNaN(value) && value > 0) {
                pauseFactor = value;
                alert("Paramètres enregistrés : facteur de pause = " + pauseFactor);
                settingsMenu.style.display = "none";
            } else {
                alert("Veuillez entrer un nombre valide supérieur à 0.");
            }
        });

        startBtn.addEventListener("click", () => {
            console.log("startBtn clicked");
            if (timer) clearInterval(timer);
            startBtn.disabled = true;
            stopBtn.disabled = false;
            status.textContent = "";
            working = true;
            reversing = false;

            timer = setInterval(() => {
                milliseconds += 10;
                update();
            }, 10);
        });

        stopBtn.addEventListener("click", () => {
            console.log("stopBtn clicked");
            clearInterval(timer);
            stopBtn.disabled = true;
            status.textContent = "Pause";
            working = false;
            reversing = true;

            const pauseDuration = Math.floor(milliseconds / pauseFactor);
            logHistory("Travail", milliseconds);
            let pauseRemaining = pauseDuration;

            timer = setInterval(() => {
                if (pauseRemaining > 0) {
                    pauseRemaining -= 10;
                    milliseconds = pauseRemaining;
                    update();
                } else {
                    clearInterval(timer);
                    status.textContent = "";
                    milliseconds = 0;
                    logHistory("Pause", pauseDuration);
                    update();
                    startBtn.disabled = false;
                }
            }, 10);
        });
        update();
    })();
    </script>
    <?php
        return ob_get_clean();
}
add_shortcode('flowmodoro', 'flowmodoro_shortcode');



add_action('wp_ajax_save_flowmodoro', function() {
    if (!is_user_logged_in()) wp_send_json_error('Non connecté');

    $user_id = get_current_user_id();
    $history = $_POST['history'] ?? [];
    update_user_meta($user_id, 'flowmodoro_history', $history);
    wp_send_json_success();
});