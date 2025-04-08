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

        <div style="margin-bottom: 30px;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <button class="period-btn selected" data-period="full">Depuis le début</button>
                <button class="period-btn" data-period="week">Cette semaine</button>
                <button class="period-btn" data-period="month">Ce mois</button>
                <button class="period-btn" data-period="year">Cette année</button>
                <div id="manual-picker-wrapper" style="position: relative; display: inline-block;">
                    <button class="period-btn" id="manual-picker-btn" data-period="manual">Sélection manuelle</button>
                </div>
                <input type="hidden" id="date-range-picker">
            </div>
            <div id="selected-dates" style="margin-top: 10px; font-size: 14px; color: #333;"></div>
        </div>
        <div id="stats-summary" style="margin-bottom: 40px;"></div>

        <canvas id="stats-chart" height="200" style="background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 10px;"></canvas>
        <canvas id="stats-line-chart" height="200" style="margin-top: 40px; background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 10px;"></canvas>


        <div id="heatmap-container"
            style="display: grid; grid-template-columns: repeat(53, 1fr); gap: 2px; max-width: 100%;"></div>
            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                <span style="background: #eee; padding: 2px 6px; border-radius: 3px;">0</span>
                →
                <span style="background: #e74c3c; padding: 2px 6px; border-radius: 3px; color: white;">+ de travail</span>
            </div>

            <div id="hour-distribution" style="margin-top: 40px;">
                <h3>🕓 Répartition horaire du travail</h3>
                <canvas id="hour-chart" height="200" style="background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 10px;"></canvas>
            </div>
    </div>

    <style>
        .period-btn {
            padding: 8px 14px;
            background: #f7f7f7;
            border: 1px solid #aaa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            color: #111;
            font-weight: 500;
            transition: 0.2s ease;
        }

        .period-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .period-btn.selected {
            background: #3498db;
            color: white;
            font-weight: bold;
            border-color: #2980b9;
        }

    </style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {


        const picker = new Litepicker({
            element: document.getElementById('date-range-picker'),
            parentEl: document.getElementById('manual-picker-wrapper'),
            singleMode: false,
            numberOfMonths: 2,
            numberOfColumns: 2,
            firstDay: 1,
            autoApply: true,
            lang: 'fr-FR',
            tooltipText: {
                one: 'jour',
                other: 'jours'
            },
            format: 'YYYY-MM-DD',
            setup: (picker) => {
                picker.on('selected', (startDate, endDate) => {
                    const start = startDate.format('YYYY-MM-DD');
                    const end = endDate.format('YYYY-MM-DD');
                    document.getElementById("date-range-picker").value = `${start} - ${end}`;
                    applyFilter(start, end);
                    document.querySelectorAll(".period-btn").forEach(b => b.classList.remove("selected"));
                    document.getElementById("manual-picker-btn").classList.add("selected");
                });
            }
        });



        function getMinMaxDates(entries) {
            const dates = entries.map(e => parseDate(e.timestamp)).sort();
            return [dates[0], dates.at(-1)];
        }



        const rawEntries = <?php echo json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        const parseDate = ts => {
            const d = new Date(ts);
            return d.toISOString().split("T")[0];
        };

        function fillMissingDates(start, end, dataByDate) {
            const filled = {};
            const cursor = new Date(start);
            const endDate = new Date(end);

            while (cursor <= endDate) {
                const d = cursor.toISOString().split("T")[0];
                filled[d] = dataByDate[d] || { travail: 0, pause: 0 };
                cursor.setDate(cursor.getDate() + 1);
            }

            return filled;
        }


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
                byDate,
                filtered
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

        let lineChartInstance = null;

        function renderLineChart(dataByDate) {

            const ctx = document.getElementById('stats-line-chart').getContext('2d');
            const labels = Object.keys(dataByDate).sort();
            const travail = labels.map(d => Math.round((dataByDate[d].travail || 0) / 60000));
            const pause = labels.map(d => Math.round((dataByDate[d].pause || 0) / 60000));

            if (lineChartInstance) lineChartInstance.destroy();

            lineChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Travail (min)',
                            data: travail,
                            borderColor: '#e74c3c',
                            backgroundColor: 'transparent',
                            tension: 0.3
                        },
                        {
                            label: 'Pause (min)',
                            data: pause,
                            borderColor: '#3498db',
                            backgroundColor: 'transparent',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        x: {
                            type: 'category',
                            ticks: {
                                autoSkip: false, // ✅ force une colonne par jour
                                maxRotation: 90,
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Minutes' }
                        }
                    }
                }
            });
        }

        function renderHeatmap(dataByDate) {
            const container = document.getElementById("heatmap-container");
            container.innerHTML = "";

            // Obtenir les bornes temporelles
            const allDates = Object.keys(dataByDate).sort();
            if (allDates.length === 0) return;

            const start = new Date(allDates[0]);
            const end = new Date(allDates.at(-1));
            const grid = {};

            // Générer chaque jour entre start et end
            const cursor = new Date(start);
            while (cursor <= end) {
                const iso = cursor.toISOString().split("T")[0];
                grid[iso] = dataByDate[iso]?.travail || 0;
                cursor.setDate(cursor.getDate() + 1);
            }

            // Calcule du max pour les intensités
            const max = Math.max(...Object.values(grid), 1); // éviter /0

            // Reformatage en colonnes (semaines)
            const weeks = [];
            // fixe : 53 semaines = 371 jours
            const today = new Date();
            const tmp = new Date(today);
            tmp.setMonth(0);
            tmp.setDate(1);
            tmp.setHours(0, 0, 0, 0);
            tmp.setDate(tmp.getDate() - ((tmp.getDay() + 6) % 7)); // aligné sur lundi

            const last = new Date(tmp);
            last.setDate(tmp.getDate() + (7 * 53)-1);
            const dateArray = [];
            while (tmp <= last) {
                dateArray.push(new Date(tmp));
                tmp.setDate(tmp.getDate() + 1);
            }

            // S’assurer qu’on a bien un multiple de 7 (grille propre)
            while (dateArray.length % 7 !== 0) {
                const filler = new Date(dateArray.at(-1));
                filler.setDate(filler.getDate() + 1);
                dateArray.push(filler);
            }


            for (let i = 0; i < dateArray.length; i += 7) {
                const week = dateArray.slice(i, i + 7);
                weeks.push(week);
            }

            // Affichage : colonne = semaine, ligne = jour
            weeks.forEach(week => {
                week.forEach((day, i) => {
                    const d = day.toISOString().split("T")[0];
                    const work = grid[d] || 0;
                    const intensity = Math.min(1, work / max);
                    const color = intensity === 0 ? "#eee" : `rgba(231, 76, 60, ${intensity})`;

                    const square = document.createElement("div");
                    square.title = `${d} — ${format(work)} de travail`;
                    square.style.width = "14px";
                    square.style.height = "14px";
                    square.style.borderRadius = "2px";
                    square.style.backgroundColor = color;
                    square.style.gridColumn = `${weeks.indexOf(week) + 1}`;
                    const weekday = (day.getDay() + 6) % 7; // lundi = 0, dimanche = 6
                    square.style.gridRow = weekday + 1;
                    square.style.cursor = "pointer";
 
                    const todayISO = new Date().toISOString().split("T")[0];
                    if (d > todayISO) {
                        square.style.opacity = "0.3";
                        square.title += " (futur)";
                    }
 
                    if (day.getDate() === 1) {
                        square.style.borderTop = "2px solid #aaa"; // ligne discrète
                        square.title += " (Début du mois)";
                    }
 
                    container.appendChild(square);
                });
            });
        }
 
        let hourChartInstance = null;
 
        function renderHourChart(filteredEntries) {
            const hours = new Array(24).fill(0);

            filteredEntries.forEach(e => {
                if (e.type !== "Travail") return;
                const start = new Date(e.timestamp);
                const end = new Date(e.timestamp + e.duration);

                const startHour = start.getHours();
                const endHour = end.getHours();
                for (let h = startHour; h <= endHour; h++) {
                    const sliceStart = new Date(start);
                    sliceStart.setHours(h, 0, 0, 0);
                    const sliceEnd = new Date(sliceStart);
                    sliceEnd.setHours(h + 1, 0, 0, 0);
 
                    const overlap = Math.min(end, sliceEnd) - Math.max(start, sliceStart);
                    if (overlap > 0) hours[h] += overlap;
                }
            });

            // Convertir en minutes
            const minutes = hours.map(ms => Math.round(ms / 60000));

            const ctx = document.getElementById("hour-chart").getContext("2d");
            if (hourChartInstance) hourChartInstance.destroy();

            hourChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [...Array(24)].map((_, i) => `${String(i).padStart(2, '0')}h`),
                    datasets: [{
                        label: 'Temps de travail (min)',
                        data: minutes,
                        backgroundColor: '#e67e22'
                    }]
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
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        x: {
                            type: 'category',
                            ticks: {
                                autoSkip: false,
                                maxRotation: 90,
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Minutes' }
                        }
                    }
                }
            });
        }
 
        document.getElementById("manual-picker-btn").addEventListener("click", () => {
            picker.show();
        });
 
        document.querySelectorAll(".period-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const period = btn.dataset.period;
                const now = new Date();
                let start, end;
 
                const dates = rawEntries.map(e => parseDate(e.timestamp)).sort();
 
                if (period === "full") {
                    [start, end] = getMinMaxDates(rawEntries);
                } else if (period === "week") {
                    const day = (now.getDay() + 6) % 7;
                    start = new Date(now);
                    start.setDate(now.getDate() - day);
                    end = new Date(now);
                    end.setDate(start.getDate() + 6);
                } else if (period === "month") {
                    start = new Date(now.getFullYear(), now.getMonth(), 1);
                    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                } else if (period === "year") {
                    start = new Date(now.getFullYear(), 0, 1);
                    end = new Date(now.getFullYear(), 11, 31);
                } else if (period === "all") {
                    if (dates.length > 0) {
                        start = new Date(dates[0]);
                        end = new Date(dates.at(-1));
                    }
                }
 
                if (start && end) {
                console.log(">>> Bouton cliqué :", period);
                console.log("Start (raw):", start);
                console.log("End (raw):", end);

                const startStr = start.getFullYear() + "-" + String(start.getMonth() + 1).padStart(2, '0') + "-" + String(start.getDate()).padStart(2, '0');
                const endStr = end.getFullYear() + "-" + String(end.getMonth() + 1).padStart(2, '0') + "-" + String(end.getDate()).padStart(2, '0');

                console.log("StartStr:", startStr);
                console.log("EndStr:", endStr);

                document.getElementById("date-range-picker").value = `${startStr} - ${endStr}`;
                applyFilter(startStr, endStr);
                document.querySelectorAll(".period-btn").forEach(b => b.classList.remove("selected"));
                btn.classList.add("selected");
            }



            });
        });
 

        function updateSelectedDatesDisplay(start, end) {
            const el = document.getElementById("selected-dates");
            const startDate = new Date(start);
            const endDate = new Date(end);
            el.textContent = `Période sélectionnée : du ${startDate.toLocaleDateString()} au ${endDate.toLocaleDateString()}`;
        }
 
        function applyFilter(start = null, end = null) {
            if (!start || !end) {
                const range = document.getElementById("date-range-picker").value;
                [start, end] = range.split(" - ");
            }

            console.log(">>> applyFilter appelé avec :", start, end);

            if (!start || !end || start > end) {
                alert("Veuillez sélectionner une période valide.");
                return;
            }

            const stats = getStatsBetween(start, end);
            renderStats(stats);
            renderChart(fillMissingDates(start, end, stats.byDate));
            renderLineChart(fillMissingDates(start, end, stats.byDate));
            renderHourChart(stats.filtered);
            updateSelectedDatesDisplay(start, end); // <-- ici
        }

 
 
 
        const fullByDate = {};
        rawEntries.forEach(e => {
            const d = parseDate(e.timestamp);
            if (!fullByDate[d]) fullByDate[d] = { travail: 0, pause: 0 };
            if (e.type === "Travail") fullByDate[d].travail += e.duration || 0;
            if (e.type === "Pause") fullByDate[d].pause += e.duration || 0;
        });
        renderHeatmap(fullByDate);
 
        // Valeurs par défaut (depuis le début jusqu'à maintenant)
        const [defaultStart, defaultEnd] = getMinMaxDates(rawEntries);
        document.getElementById("date-range-picker").value = `${defaultStart} - ${defaultEnd}`;
        applyFilter();
 
 
    });
 
 
    </script>
    <?php
 
    return ob_get_clean();
}
add_shortcode('flowmodoro_stats', 'flowmodoro_stats_shortcode');