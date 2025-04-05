<?php
/*
Plugin Name: Flowmodoro
Description: Un timer Flowmodoro.
Version: 1.0
Author: Ascomany
*/

function flowmodoro_shortcode() {
    ob_start();
    ?>
    <div id="flowmodoro-container" style="text-align: center; padding: 40px;">
        <h2>Flowmodoro</h2>
        <div id="flowmodoro-timer" style="font-size: 80px; margin: 30px 0;">25:00</div>
        <button id="flowmodoro-start" style="font-size: 20px; padding: 10px 20px;">Démarrer</button>
        <button id="flowmodoro-stop" style="font-size: 20px; padding: 10px 20px;" disabled>Arrêter</button>
    </div>
    <script>
    (function(){
        let timer;
        let time = 25 * 60;
        const display = document.getElementById("flowmodoro-timer");
        const startBtn = document.getElementById("flowmodoro-start");
        const stopBtn = document.getElementById("flowmodoro-stop");

        function update() {
            const min = String(Math.floor(time / 60)).padStart(2, '0');
            const sec = String(time % 60).padStart(2, '0');
            display.textContent = `${min}:${sec}`;
        }

        startBtn.addEventListener("click", () => {
            startBtn.disabled = true;
            stopBtn.disabled = false;
            timer = setInterval(() => {
                if (time > 0) {
                    time--;
                    update();
                } else {
                    clearInterval(timer);
                    alert("Temps écoulé !");
                    time = 25 * 60;
                    update();
                    startBtn.disabled = false;
                    stopBtn.disabled = true;
                }
            }, 1000);
        });

        stopBtn.addEventListener("click", () => {
            clearInterval(timer);
            time = 25 * 60;
            update();
            startBtn.disabled = false;
            stopBtn.disabled = true;
        });

        update();
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro', 'flowmodoro_shortcode');

