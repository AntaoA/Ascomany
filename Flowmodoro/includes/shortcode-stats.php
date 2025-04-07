<?php
/**
 * Flowmodoro Statistics Shortcode
 * 
 * @package Flowmodoro
 */

function flowmodoro_stats_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour consulter vos statistiques.</p>';
    }

    ob_start();

    $user_id = get_current_user_id();
    $history = get_user_meta($user_id, 'flowmodoro_history', true);
    $entries = json_decode((string) $history, true);
    if (!is_array($entries)) $entries = [];

    ?>
    <div id="flowmodoro-stats" style="padding: 30px; max-width: 900px; margin: auto; font-family: sans-serif;">
        <h2 style="margin-bottom: 20px;">📊 Statistiques Flowmodoro</h2>

        <div style="margin-bottom: 20px;">
            <label for="stats-start">📅 Du </label>
            <input type="date" id="stats-start">
            <label for="stats-end"> au </label>
            <input type="date" id="stats-end">
            <button id="stats-apply" style="margin-left: 10px;">Appliquer</button>
        </div>

        <div id="stats-summary" style="margin-bottom: 40px;"></div>

        <canvas id="stats-chart" height="200" style="background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 10px;"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const rawEntries = <?php echo json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        const parseDate = ts => {
            const d = new Date(ts);
            return d.toISOString().split("T")[0];
        };

        function getStatsBetween(startDate, endDate) {
            const filtered = rawEntries.filter(e => {
                const d = parseDate(e.timestamp);
                return d >= startDate && d <= endDate;
            });

            const days = new Set();
            const sessions = [];
            let work = 0, pause = 0, pauseReal = 0;
            let previousPauseEnd = null;

            const byDate = {};

            for (let i = 0; i < filtered.length; i++) {
                const e = filtered[i];
                const d = parseDate(e.timestamp);
                days.add(d);
                if (!byDate[d]) byDate[d] = { travail: 0, pause: 0 };

                if (e.type === "Travail") {
                    work += e.duration || 0;
                    byDate[d].travail += e.duration || 0;

                    if (previousPauseEnd !== null) {
                        pauseReal += e.timestamp - previousPauseEnd;
                        previousPauseEnd = null;
                    }
                } else if (e.type === "Pause") {
                    pause += e.duration || 0;
                    byDate[d].pause += e.duration || 0;
                    previousPauseEnd = e.timestamp + (e.duration || 0);
                }
            }

            // sessions = succession de phases espacées de moins de 10 min
            let lastEnd = null;
            let sessionCount = 0;
            filtered.forEach(e => {
                const start = e.timestamp;
                const end = start + (e.duration || 0);
                if (!lastEnd || start - lastEnd > 10 * 60 * 1000) sessionCount++;
                lastEnd = end;
            });

            return {
                work, pause, pauseReal,
                sessionCount,
                daysActive: days.size,
                first: filtered[0]?.timestamp,
                last: filtered.at(-1)?.timestamp,
                byDate
            };
        }

        function format(ms) {
            const s = Math.floor(ms / 1000);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = s % 60;
            return `${h.toString().padStart(2, "0")}:${m.toString().padStart(2, "0")}:${sec.toString().padStart(2, "0")}`;
        }

        function renderStats(stats) {
            const el = document.getElementById("stats-summary");
            el.innerHTML = `
                <ul style="list-style: none; padding: 0; font-size: 16px;">
                    <li><strong>Total travail :</strong> ${format(stats.work)}</li>
                    <li><strong>Total pause :</strong> ${format(stats.pause)}</li>
                    <li><strong>Pause réelle cumulée :</strong> ${format(stats.pauseReal)}</li>
                    <li><strong>Nombre de sessions :</strong> ${stats.sessionCount}</li>
                    <li><strong>Jours actifs :</strong> ${stats.daysActive}</li>
                    <li><strong>Durée moyenne par session :</strong> ${format(stats.work / Math.max(stats.sessionCount, 1))}</li>
                    <li><strong>Première entrée :</strong> ${stats.first ? new Date(stats.first).toLocaleString() : "—"}</li>
                    <li><strong>Dernière entrée :</strong> ${stats.last ? new Date(stats.last).toLocaleString() : "—"}</li>
                </ul>
            `;
        }

        let chartInstance = null;

        function renderChart(dataByDate) {
            const ctx = document.getElementById('stats-chart').getContext('2d');
            const labels = Object.keys(dataByDate).sort();
            const travail = labels.map(d => Math.round((dataByDate[d].travail || 0) / 60000));
            const pause = labels.map(d => Math.round((dataByDate[d].pause || 0) / 60000));

            if (chartInstance) chartInstance.destroy();

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Travail (min)',
                            data: travail,
                            backgroundColor: '#e74c3c'
                        },
                        {
                            label: 'Pause (min)',
                            data: pause,
                            backgroundColor: '#3498db'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Minutes' }
                        }
                    }
                }
            });
        }

        function applyFilter() {
            const start = document.getElementById("stats-start").value;
            const end = document.getElementById("stats-end").value;
            if (!start || !end || start > end) {
                alert("Veuillez sélectionner une période valide.");
                return;
            }

            const stats = getStatsBetween(start, end);
            renderStats(stats);
            renderChart(stats.byDate);
        }

        // Valeurs par défaut
        const dates = rawEntries.map(e => parseDate(e.timestamp)).sort();
        if (dates.length > 0) {
            document.getElementById("stats-start").value = dates[0];
            document.getElementById("stats-end").value = dates.at(-1);
            applyFilter();
        }

        document.getElementById("stats-apply").addEventListener("click", applyFilter);
    });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('flowmodoro_stats', 'flowmodoro_stats_shortcode');
