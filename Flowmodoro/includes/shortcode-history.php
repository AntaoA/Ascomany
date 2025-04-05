<?php
/**
 * Flowmodoro History Shortcode
 *
 * @package Flowmodoro
 */
function flowmodoro_history_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour consulter votre historique.</p>';
    }

    ob_start();
    ?>
    <div style="padding: 20px;">
        <h2>Historique Flowmodoro</h2>

        <div style="margin-bottom: 15px;">
            <button class="history-filter" data-range="session">Session</button>
            <button class="history-filter" data-range="day">Aujourd’hui</button>
            <button class="history-filter" data-range="week">Semaine</button>
            <button class="history-filter" data-range="all">Tout</button>
        </div>

        <ul id="history-list" style="font-family: monospace; list-style: none; padding: 0;"></ul>
    </div>

    <script>
    (function(){
        const allHistory = <?php
            $user_id = get_current_user_id();
            $history = get_user_meta($user_id, 'flowmodoro_history', true);
            $data = is_string($history) ? json_decode($history, true) : $history;
            if (!is_array($data)) $data = [];
            echo json_encode($data);
        ?>;

        const list = document.getElementById("history-list");

        function formatTime(ms) {
            const totalSec = Math.floor(ms / 1000);
            const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
            const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
            const s = String(totalSec % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        }

        function formatDate(ts) {
            const d = new Date(ts);
            return d.toLocaleString();
        }

        function render(range) {
            list.innerHTML = "";

            const now = Date.now();
            const startOfDay = new Date();
            startOfDay.setHours(0,0,0,0);

            const startOfWeek = new Date();
            startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
            startOfWeek.setHours(0,0,0,0);

            const sessionStart = window.performance.timing.navigationStart;

            let filtered = [];
            switch (range) {
                case "session":
                    filtered = allHistory.filter(e => e.timestamp > sessionStart);
                    break;
                case "day":
                    filtered = allHistory.filter(e => e.timestamp > startOfDay.getTime());
                    break;
                case "week":
                    filtered = allHistory.filter(e => e.timestamp > startOfWeek.getTime());
                    break;
                case "all":
                    filtered = allHistory;
                    break;
            }

            filtered.forEach(e => {
                const li = document.createElement("li");
                li.textContent = `${e.type} — ${formatTime(e.duration)} — ${formatDate(e.timestamp || Date.now())}`;
                li.style.color = e.type === "Travail" ? "#e74c3c" : "#3498db";
                list.appendChild(li);
            });
        }

        document.querySelectorAll(".history-filter").forEach(btn => {
            btn.addEventListener("click", () => {
                render(btn.dataset.range);
            });
        });

        render("all");
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro_history', 'flowmodoro_history_shortcode');