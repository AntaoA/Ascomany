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

        const sessionStorageHistory = sessionStorage.getItem("flowmodoro_session");
        const sessionHistory = sessionStorageHistory ? JSON.parse(sessionStorageHistory) : [];

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

        function setActiveButton(range) {
            document.querySelectorAll(".history-filter").forEach(btn => {
                if (btn.dataset.range === range) {
                    btn.style.fontWeight = "bold";
                    btn.style.backgroundColor = "#eee";
                } else {
                    btn.style.fontWeight = "normal";
                    btn.style.backgroundColor = "";
                }
            });
        }

        function render(range) {
            list.innerHTML = "";
            setActiveButton(range);

            let filtered = [];

            if (range === "session") {
                filtered = sessionHistory;
            } else {
                const now = new Date();
                now.setHours(0,0,0,0);

                const startOfDay = now.getTime();

                const startOfWeek = new Date();
                startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
                startOfWeek.setHours(0,0,0,0);
                const startWeekTs = startOfWeek.getTime();

                if (range === "day") {
                    filtered = allHistory.filter(e => e.timestamp >= startOfDay);
                } else if (range === "week") {
                    filtered = allHistory.filter(e => e.timestamp >= startWeekTs);
                } else {
                    filtered = allHistory;
                }
            }

            if (filtered.length === 0) {
                list.innerHTML = "<li style='color:#999;'>Aucune entrée trouvée.</li>";
                return;
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
