<?php
/*
Plugin Name: Flowmodoro
Description: Timer Flowmodoro avec centièmes et durée de pause réglable.
Version: 1.3
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
            <label for="pause-factor">Facteur de pause (ex: 1 = pause = travail):</label>
            <input type="number" id="pause-factor" value="2" min="0.1" step="0.1" style="width: 60px;">
        </div>
    </div>

    <script>
    (function(){
        let timer;
        let milliseconds = 0;
        let working = false;
        let reversing = false;
        let pauseTarget = 0;

        const display = document.getElementById("flowmodoro-timer");
        const status = document.getElementById("flowmodoro-status");
        const startBtn = document.getElementById("flowmodoro-start");
        const stopBtn = document.getElementById("flowmodoro-stop");
        const settingsBtn = document.getElementById("flowmodoro-settings");
        const settingsMenu = document.getElementById("flowmodoro-settings-menu");
        const pauseInput = document.getElementById("pause-factor");

        function update() {
            const totalCs = Math.floor(milliseconds / 10);
            const min = String(Math.floor(totalCs / 6000)).padStart(2, '0');
            const sec = String(Math.floor((totalCs % 6000) / 100)).padStart(2, '0');
            const cs = String(totalCs % 100).padStart(2, '0');
            display.textContent = `${min}:${sec}:${cs}`;
        }

        settingsBtn.addEventListener("click", () => {
            settingsMenu.style.display = settingsMenu.style.display === "none" ? "block" : "none";
        });

        startBtn.addEventListener("click", () => {
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
            clearInterval(timer);
            stopBtn.disabled = true;
            status.textContent = "Pause";
            working = false;
            reversing = true;

            const factor = parseFloat(pauseInput.value) || 1;
            pauseTarget = Math.floor(milliseconds / factor);

            timer = setInterval(() => {
                if (milliseconds > 0 && milliseconds > pauseTarget) {
                    milliseconds -= 10;
                    update();
                } else {
                    clearInterval(timer);
                    status.textContent = "";
                    milliseconds = 0;
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
