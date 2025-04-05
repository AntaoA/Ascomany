<?php
/*
Plugin Name: Flowmodoro
Description: Un timer Flowmodoro sans limite, avec retour en arrière en pause.
Version: 1.2
Author: Ascomany
*/

function flowmodoro_shortcode() {
    ob_start();
    ?>
    <div id="flowmodoro-container" style="text-align: center; padding: 40px;">
        <h2>Flowmodoro</h2>
        <div id="flowmodoro-status" style="font-size: 24px; color: #888; margin-bottom: 10px;"></div>
        <div id="flowmodoro-timer" style="font-size: 80px; margin: 30px 0;">00:00</div>
        <button id="flowmodoro-start" style="font-size: 20px; padding: 10px 20px;">Démarrer</button>
        <button id="flowmodoro-stop" style="font-size: 20px; padding: 10px 20px;" disabled>Arrêter</button>
    </div>
    <script>
    (function(){
        let timer;
        let time = 0;
        let reversing = false;

        const display = document.getElementById("flowmodoro-timer");
        const status = document.getElementById("flowmodoro-status");
        const startBtn = document.getElementById("flowmodoro-start");
        const stopBtn = document.getElementById("flowmodoro-stop");

        function update() {
            const min = String(Math.floor(Math.abs(time) / 60)).padStart(2, '0');
            const sec = String(Math.abs(time) % 60).padStart(2, '0');
            display.textContent = `${min}:${sec}`;
        }

        startBtn.addEventListener("click", () => {
            startBtn.disabled = true;
            stopBtn.disabled = false;
            status.textContent = "";
            reversing = false;

            timer = setInterval(() => {
                time++;
                update();
            }, 1000);
        });

        stopBtn.addEventListener("click", () => {
            clearInterval(timer);
            stopBtn.disabled = true;
            status.textContent = "Pause";
            reversing = true;

            timer = setInterval(() => {
                if (time > 0) {
                    time--;
                    update();
                } else {
                    clearInterval(timer);
                    status.textContent = "";
                    startBtn.disabled = false;
                }
            }, 1000);
        });

        update();
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro', 'flowmodoro_shortcode');
