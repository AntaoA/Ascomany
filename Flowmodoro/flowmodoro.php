<?php
/*
Plugin Name: Flowmodoro
Description: Timer Flowmodoro
Version: 1.5
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
            <input type="number" id="pause-factor" value="2" min="0.1" step="0.1" style="width: 60px;">
            <button id="save-settings" style="margin-left: 10px;">Enregistrer</button>
        </div>
    </div>

    <script>
    (function(){
        let timer;
        let milliseconds = 0;
        let working = false;
        let reversing = false;
        let pauseTarget = 0;
        let pauseFactor = 2;

        const display = document.getElementById("flowmodoro-timer");
        const status = document.getElementById("flowmodoro-status");
        const startBtn = document.getElementById("flowmodoro-start");
        const stopBtn = document.getElementById("flowmodoro-stop");
        const settingsBtn = document.getElementById("flowmodoro-settings");
        const settingsMenu = document.getElementById("flowmodoro-settings-menu");
        const pauseInput = document.getElementById("pause-factor");
        const saveBtn = document.getElementById("save-settings");

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

            const pauseDuration = Math.floor(milliseconds / pauseFactor);
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
