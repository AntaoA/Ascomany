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
                <button class="period-btn selected" data-period="full" id="btn-full">Depuis le début</button>
                <button class="period-btn" data-period="week">Cette semaine</button>
                <button class="period-btn" data-period="month">Ce mois</button>
                <button class="period-btn" data-period="year">Cette année</button>
                <div id="manual-picker-wrapper" style="position: relative; display: inline-block;">
                    <button class="period-btn" id="manual-picker-btn" data-period="manual">Sélection manuelle</button>
                </div>
                <div id="nav-buttons" style="display: flex; gap: 10px; margin-top: 10px;">
                    <button id="prev-period" class="period-btn">← Période précédente</button>
                    <button id="next-period" class="period-btn">Période suivante →</button>
                </div>
                <div id="period-label" style="margin-top: 10px; font-weight: bold; font-size: 16px;"></div>
                <input type="hidden" id="date-range-picker">
            </div>
            <select id="grouping-select" style="margin-left: 10px; padding: 6px; border-radius: 4px;">
                <option value="day">📅 Jour</option>
                <option value="week">📖 Semaine</option>
                <option value="month">🗓 Mois</option>
                <option value="year">📆 Année</option>
            </select>

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

            <div id="top-ranking" style="margin-top: 50px;">
                <h3>🏅 Classements</h3>
                <div id="ranking-list"></div>
                <button id="show-more-ranking" class="period-btn" style="margin-top: 10px;">Afficher plus</button>
            </div>
    </div>

    <style>
        .period-btn {
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        #period-label {
            font-weight: 600;
            color: #444;
        }

        #stats-summary {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        #ranking-list div {
            transition: box-shadow 0.2s ease, transform 0.1s ease;
        }

        #ranking-list div:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        #heatmap-container div {
            transition: transform 0.1s ease;
        }

        #heatmap-container div:hover {
            transform: scale(1.2);
            z-index: 10;
        }


    </style>
 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
 
 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
 
        let currentPeriodType = "full"; // semaine/mois/année/full/manual
        let currentRange = { start: null, end: null };
 
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
 
                    // Forcer mise à jour visuelle du label
                    updatePeriodLabel("manual", start, end);
 
                    document.querySelectorAll(".period-btn").forEach(b => b.classList.remove("selected"));
                    document.getElementById("manual-picker-btn").classList.add("selected");
                });
            },
 
        });
 
 
        let currentStart = null;
        let currentEnd = null;
 
 
 
        function getMinMaxDates(entries) {
            const dates = entries.map(e => parseDate(e.timestamp)).sort();
            return [dates[0], dates.at(-1)];
        }
 
 
        function groupDataByTemporalUnit(dataByDate, unit) {
            const grouped = {};

            Object.entries(dataByDate).forEach(([dateStr, value]) => {
                const d = new Date(dateStr);
                let key = "";

                if (unit === "day") {
                    key = dateStr;
                } else if (unit === "week") {
                    const year = d.getFullYear();
                    const week = Math.ceil((((d - new Date(year, 0, 1)) / 86400000) + d.getDay() + 1) / 7);
                    key = `${year}-S${String(week).padStart(2, "0")}`;
                } else if (unit === "month") {
                    key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
                } else if (unit === "year") {
                    key = `${d.getFullYear()}`;
                }

                if (!grouped[key]) grouped[key] = { travail: 0, pause: 0 };
                grouped[key].travail += value.travail;
                grouped[key].pause += value.pause;
            });

            return grouped;
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
            const byDate = {};

            // Groupement des entrées en sessions
            let currentSession = [];
            let lastEnd = null;

            filtered.forEach(e => {
                const start = e.timestamp;
                const end = start + (e.duration || 0);
                const d = parseDate(start);

                days.add(d);
                if (!byDate[d]) byDate[d] = { travail: 0, pause: 0 };

                if (!lastEnd || (start - lastEnd) > 10 * 60 * 1000) {
                    if (currentSession.length) sessions.push(currentSession);
                    currentSession = [];
                }
                currentSession.push(e);
                lastEnd = end;

                if (e.type === "Travail") {
                    work += e.duration || 0;
                    byDate[d].travail += e.duration || 0;
                } else if (e.type === "Pause") {
                    pause += e.duration || 0;
                    byDate[d].pause += e.duration || 0;
                }
            });
            if (currentSession.length) sessions.push(currentSession);

            // Pause réelle par session = (dernière pause) - (première pause)
            sessions.forEach(session => {
                const pauses = session.filter(e => e.type === "Pause");
                if (pauses.length === 0) return;

                const first = pauses[0];
                const last = pauses[pauses.length - 1];
                const start = first.timestamp;
                const end = last.timestamp + (last.duration || 0);
                const real = end - start;

                if (real > 0) pauseReal += real;
            });


            const pauseExcess = pause - pauseReal;
            const pauseExcessPercentage = pauseReal > 0 
                ? ((pauseExcess / pauseReal) * 100).toFixed(1) 
                : "0.0";

            return {
                work,
                pause,
                pauseReal,
                sessionCount: sessions.length,
                daysActive: days.size,
                first: filtered[0]?.timestamp,
                last: filtered.at(-1)?.timestamp,
                byDate,
                filtered
            };
        }



        function computeContinuationRate(entries) {
            let continued = 0;
            let totalPauses = 0;

            for (let i = 0; i < entries.length; i++) {
                const e = entries[i];
                if (e.type === "Pause") {
                    totalPauses++;
                    const pauseEnd = e.timestamp + (e.duration || 0);

                    const next = entries[i + 1];
                    if (next && next.type === "Travail" && (next.timestamp - pauseEnd) <= 10 * 60 * 1000) {
                        continued++;
                    }
                }
            }

            const percentage = totalPauses === 0 ? 0 : (continued / totalPauses) * 100;

            return {
                continued,
                totalPauses,
                percentage: percentage.toFixed(1)
            };
        }

        function updateGroupingVisibility() {
            const select = document.getElementById("grouping-select");
            if (["full", "year", "manual"].includes(currentPeriodType)) {
                select.style.display = "inline-block";
            } else {
                select.style.display = "none";
            }
        }



        function computeConsistencyStreaks(dataByDate) {
            const dates = Object.keys(dataByDate).sort();
            const today = new Date().toISOString().split("T")[0];

            let maxStreak = 0, maxStart = null, maxEnd = null;
            let currentStreak = 0, currentStart = null;

            let previousDate = null;
            let todayIncluded = false;

            for (let i = 0; i < dates.length; i++) {
                const d = dates[i];
                const hasWork = dataByDate[d]?.travail > 0;

                if (!hasWork) {
                    currentStreak = 0;
                    currentStart = null;
                    previousDate = null;
                    continue;
                }

                if (d === today) {
                    todayIncluded = true;
                }

                if (
                    previousDate &&
                    new Date(d).getTime() - new Date(previousDate).getTime() === 86400000
                ) {
                    currentStreak++;
                } else {
                    currentStreak = 1;
                    currentStart = d;
                }

                if (currentStreak > maxStreak) {
                    maxStreak = currentStreak;
                    maxStart = currentStart;
                    maxEnd = d;
                }

                previousDate = d;
            }

            // streak en cours (à partir d’aujourd’hui en reculant)
            let ongoingStreak = 0;
            let ongoingStart = null;
            let cursor = new Date(today);

            while (true) {
                const iso = cursor.toISOString().split("T")[0];
                if (dataByDate[iso]?.travail > 0) {
                    ongoingStreak++;
                    ongoingStart = iso;
                    cursor.setDate(cursor.getDate() - 1);
                } else {
                    break;
                }
            }

            return {
                max: { streak: maxStreak, start: maxStart, end: maxEnd },
                current: { streak: ongoingStreak, start: ongoingStart },
                todayIncluded
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
            const streaks = computeConsistencyStreaks(stats.byDate);
            const cont = computeContinuationRate(stats.filtered);
            el.innerHTML = `
                <ul style="list-style: none; padding: 0; font-size: 16px;">
                    <li><strong>Total travail :</strong> ${format(stats.work)}</li>
                    <li><strong>Total pause :</strong> ${format(stats.pause)}</li>
                    <li><strong>🕓 Pause réelle :</strong> ${format(stats.pauseReal)}</li>
                    <li><strong>📉 % de pause comptabilisée :</strong> ${stats.pauseReal > 0 ? ((stats.pause / stats.pauseReal) * 100).toFixed(1) : "100.0"}%</li>
                    <li><strong>Nombre de sessions :</strong> ${stats.sessionCount}</li>
                    <li><strong>Jours actifs :</strong> ${stats.daysActive}</li>
                    <li><strong>🔥 Streak en cours :</strong> ${streaks.current.streak} jour(s) ${streaks.current.streak > 0 ? `depuis ${streaks.current.start}` : ''} ${!streaks.todayIncluded ? `<span style="color:#e74c3c;">(⚠️ aujourd'hui non compté)</span>` : ''}</li>
                    <li><strong>🏅 Streak maximum :</strong> ${streaks.max.streak} jour(s) ${streaks.max.streak > 0 ? `(${streaks.max.start} → ${streaks.max.end})` : ''}</li>
                    <li><strong>Première entrée :</strong> ${stats.first ? new Date(stats.first).toLocaleString() : "—"}</li>
                    <li><strong>Dernière entrée :</strong> ${stats.last ? new Date(stats.last).toLocaleString() : "—"}</li>
                    <li><strong>🔁 Taux de continuation :</strong> ${cont.percentage}% (${cont.continued} / ${cont.totalPauses} pauses)</li>
                </ul>
            `;
        }

 
        let lineChartInstance = null;
 
        function renderLineChart(dataByDate) {
 
            const ctx = document.getElementById('stats-line-chart').getContext('2d');
            const labels = Object.keys(dataByDate).sort();
            const travail = labels.map(d => parseFloat((dataByDate[d].travail || 0) / 60000).toFixed(2));
            const pause = labels.map(d => parseFloat((dataByDate[d].pause || 0) / 60000).toFixed(2));
 
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
                                autoSkip: true,
                                maxTicksLimit: 31,
                                maxRotation: 45,
                                minRotation: 0
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
            const minutes = hours.map(ms => parseFloat((ms / 60000).toFixed(2)));
 
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
 
 
        function cloneDate(date) {
            return new Date(date.getTime());
        }
 
 
 
        let chartInstance = null;
 
        function renderChart(dataByDate) {
            const ctx = document.getElementById('stats-chart').getContext('2d');
            const labels = Object.keys(dataByDate).sort();
            const travail = labels.map(d => parseFloat((dataByDate[d].travail || 0) / 60000).toFixed(2));
            const pause = labels.map(d => parseFloat((dataByDate[d].pause || 0) / 60000).toFixed(2));

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
                                autoSkip: true,
                                maxTicksLimit: 31,
                                maxRotation: 45,
                                minRotation: 0
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
                const now = currentRange.start ? new Date(currentRange.start) : new Date();
                let start, end;

                if (period === "full") {
                    [start, end] = getMinMaxDates(rawEntries);
                    const startStr = start;
                    const endStr = end;
                    document.getElementById("date-range-picker").value = `${startStr} - ${endStr}`;

                    // 👇 force groupement par jour
                    document.getElementById("grouping-select").value = "day";

                    applyFilter(startStr, endStr);
                    updatePeriodLabel("full", startStr, endStr);
                    updateGroupingVisibility(); // 👈 important ici aussi

                    document.querySelectorAll(".period-btn").forEach(b => b.classList.remove("selected"));
                    btn.classList.add("selected");
                } else if (period === "week") {
                    const today = new Date();
                    const day = (today.getDay() + 6) % 7; // lundi = 0
                    start = new Date(today);
                    start.setDate(today.getDate() - day);
                    end = new Date(start);
                    end.setDate(start.getDate() + 6);

                    const startStr = start.toISOString().split("T")[0];
                    const endStr = end.toISOString().split("T")[0];


                    if (currentPeriodType === "week" && currentRange.start === startStr && currentRange.end === endStr) {
                        return; // déjà sur cette semaine
                    }

                    currentPeriodType = "week";

                } else if (period === "month") {
                    const today = new Date();
                    const month = today.getMonth();
                    const year = today.getFullYear();

                    const currentStartDate = currentRange.start ? new Date(currentRange.start) : null;
                    const isAlreadyThisMonth = currentStartDate && currentStartDate.getMonth() === month && currentStartDate.getFullYear() === year;

                    if (currentPeriodType === "month" && isAlreadyThisMonth) {
                        return;
                    }

                    start = new Date(year, month, 1);
                    end = new Date(year, month + 1, 0);
                    currentPeriodType = "month";
                } else if (period === "year") {
                    const today = new Date();
                    const thisYear = today.getFullYear();

                    const currentStartDate = currentRange.start ? new Date(currentRange.start) : null;
                    const isAlreadyThisYear = currentStartDate && currentStartDate.getFullYear() === thisYear;

                    if (currentPeriodType === "year" && isAlreadyThisYear) {
                        return;
                    }
 
                    start = new Date(thisYear, 0, 1);
                    end = new Date(thisYear, 11, 31);
                    currentPeriodType = "year";
                }

                if (period === "year") {
                    document.getElementById("grouping-select").value = "month";
                } else {
                    document.getElementById("grouping-select").value = "day";
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
                    updateGroupingVisibility();
                    document.querySelectorAll(".period-btn").forEach(b => b.classList.remove("selected"));
                    btn.classList.add("selected");
                }

            });
        });
 

        function getTopRankings(filteredEntries, limit = 5) {
            const byPhase = [...filteredEntries]
                .filter(e => e.type === "Travail")
                .sort((a, b) => b.duration - a.duration)
                .slice(0, limit);

            const sessions = [];
            let currentSession = [];
            let lastEnd = null;

            filteredEntries.forEach(e => {
                const start = e.timestamp;
                const end = start + (e.duration || 0);
                if (!lastEnd || start - lastEnd > 10 * 60 * 1000) {
                    if (currentSession.length > 0) sessions.push(currentSession);
                    currentSession = [];
                }
                currentSession.push(e);
                lastEnd = end;
            });
            if (currentSession.length > 0) sessions.push(currentSession);

            const sessionDurations = sessions.map(session => {
                return {
                    start: session[0].timestamp,
                    end: session.at(-1).timestamp + session.at(-1).duration,
                    duration: session.filter(e => e.type === "Travail").reduce((sum, e) => sum + (e.duration || 0), 0)
                };
            }).sort((a, b) => b.duration - a.duration).slice(0, limit);

            const byDay = {};
            filteredEntries.forEach(e => {
                const d = parseDate(e.timestamp);
                if (!byDay[d]) byDay[d] = 0;
                if (e.type === "Travail") byDay[d] += e.duration || 0;
            });

            const topDays = Object.entries(byDay)
                .map(([date, duration]) => ({ date, duration }))
                .sort((a, b) => b.duration - a.duration)
                .slice(0, limit);

            return { byPhase, sessionDurations, topDays };
        }


        function renderTopRankings(rankings, showAll = false) {
            const container = document.getElementById("ranking-list");
            container.innerHTML = "";

            const formatDuration = d => (d / 60000).toFixed(2) + " min";
            const formatDate = ts => new Date(ts).toLocaleString('fr-FR', {
                weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            const getDateStr = (ts) => {
                const d = new Date(ts);
                return d.toLocaleDateString('fr-CA'); // YYYY-MM-DD
            };

            const cardStyle = `
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 10px;
                padding: 12px 18px;
                margin-bottom: 12px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.05);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            `;

            const sections = [
                {
                    title: "🧱 Phases les plus longues",
                    items: rankings.byPhase,
                    render: e => ({
                        label: `${formatDate(e.timestamp)}`,
                        value: formatDuration(e.duration),
                        url: `/historique?focus=phase:${e.timestamp}`
                    })
                },
                {
                    title: "📚 Sessions les plus longues",
                    items: rankings.sessionDurations,
                    render: s => ({
                        label: `${formatDate(s.start)} → ${formatDate(s.end)}`,
                        value: formatDuration(s.duration),
                        url: `/historique?focus=session:${s.start}`
                    })
                },
                {
                    title: "📅 Journées les plus productives",
                    items: rankings.topDays,
                    render: d => ({
                        label: d.date,
                        value: formatDuration(d.duration),
                        url: `/historique?focus=day:${d.date}`
                    })
                }
            ];

            sections.forEach(section => {
                const sectionTitle = document.createElement("h4");
                sectionTitle.textContent = section.title;
                sectionTitle.style.marginTop = "25px";
                container.appendChild(sectionTitle);

                (showAll ? section.items : section.items.slice(0, 3)).forEach(item => {
                    const { label, value, url } = section.render(item);
                    const card = document.createElement("div");
                    card.style = cardStyle;

                    const left = document.createElement("div");
                    left.innerHTML = `<strong>${label}</strong><br><small>${value}</small>`;

                    const right = document.createElement("div");
                    const btn = document.createElement("a");
                    btn.href = url;
                    btn.textContent = "👁 Voir";
                    btn.style = `
                        font-size: 14px;
                        padding: 6px 10px;
                        background: #f0f0f0;
                        border-radius: 6px;
                        text-decoration: none;
                        color: #111;
                        border: 1px solid #ccc;
                    `;
                    btn.onmouseover = () => btn.style.background = "#ddd";
                    btn.onmouseout = () => btn.style.background = "#f0f0f0";

                    right.appendChild(btn);
                    card.appendChild(left);
                    card.appendChild(right);
                    container.appendChild(card);
                });
            });
        }



 
 
        function shiftPeriod(days) {
            const start = new Date(currentRange.start);
            const end = new Date(currentRange.end);
            const diff = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
 
            start.setDate(start.getDate() + days);
            end.setDate(end.getDate() + days);
 
            const startStr = start.toISOString().split("T")[0];
            const endStr = end.toISOString().split("T")[0];
 
            currentRange = { start: startStr, end: endStr };
            currentPeriodType = "manual"; // bien forcer ici
 
            document.getElementById("date-range-picker").value = `${startStr} - ${endStr}`;
            applyFilter(startStr, endStr);
            updatePeriodLabel("manual", startStr, endStr);
 
            document.querySelectorAll(".period-btn").forEach(b => b.classList.remove("selected"));
            document.getElementById("manual-picker-btn").classList.add("selected");
        }
 
 
 
        function updatePeriodLabel(period = currentPeriodType, start = currentRange.start, end = currentRange.end) {
            currentPeriodType = period;
            const label = document.getElementById("period-label");
            if (!label) return;
 
            if (period === "full") {
                label.textContent = "Depuis le début";
            } else if (period === "week") {
                const weekStart = new Date(start);
                const weekNumber = getWeekNumber(weekStart);
                label.textContent = `Semaine ${weekNumber} (${start} → ${end})`;
            } else if (period === "month") {
                const monthStart = new Date(start);
                const monthName = monthStart.toLocaleString('fr-FR', { month: 'long' });
                const monthNameCapitalized = monthName.charAt(0).toUpperCase() + monthName.slice(1);
                const year = monthStart.getFullYear();
                label.textContent = `${monthNameCapitalized} ${year}`;
            } else if (period === "year") {
                const year = start.split("-")[0];
                label.textContent = `Année ${year}`;
            } else if (period === "manual") {
                label.textContent = `${start} → ${end}`;
            }
        }
 
 
 
        function getWeekNumber(d) {
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            const dayNum = d.getUTCDay() || 7;
            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        }
 
 
 
        function applyFilter() {
            const range = document.getElementById("date-range-picker").value;
            const [start, end] = range.split(" - ");

            if (!start || !end || start > end) {
                alert("Veuillez sélectionner une période valide.");
                return;
            }
            currentStart = new Date(start);
            currentEnd = new Date(end);
            currentRange = { start, end }; // <-- stocke la période
            const stats = getStatsBetween(start, end);
            const grouping = document.getElementById("grouping-select").value || "day";
            const grouped = groupDataByTemporalUnit(fillMissingDates(start, end, stats.byDate), grouping);

            renderStats(stats);
            renderChart(grouped);
            renderLineChart(grouped);
            renderHourChart(stats.filtered);

            const rankings = getTopRankings(stats.filtered);
            renderTopRankings(rankings);

            updatePeriodLabel();
            updateGroupingVisibility();

        }

        document.getElementById("grouping-select").addEventListener("change", () => {
            applyFilter();
        });

        let showAllRanking = false;
        document.getElementById("show-more-ranking").addEventListener("click", () => {
            showAllRanking = !showAllRanking;
            const rankings = getTopRankings(stats.filtered, showAllRanking ? 100 : 5);
            renderTopRankings(rankings, showAllRanking);
            document.getElementById("show-more-ranking").textContent = showAllRanking ? "Afficher moins" : "Afficher plus";
        });

 
        function shiftDateRange(amount, unit) {
            let newStart, newEnd;

            if (unit === "manual") {
                const start = new Date(currentRange.start);
                const end = new Date(currentRange.end);
                const days = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
                newStart = new Date(start);
                newEnd = new Date(end);
                newStart.setDate(newStart.getDate() + days * amount);
                newEnd.setDate(newEnd.getDate() + days * amount);
                currentPeriodType = "manual";
            } else if (unit === "week") {
                const start = new Date(currentRange.start);
                const day = (start.getDay() + 6) % 7; // lundi = 0
                const monday = new Date(start);
                monday.setDate(start.getDate() - day + amount * 7);
                newStart = monday;
                newEnd = new Date(monday);
                newEnd.setDate(monday.getDate() + 6);
                currentPeriodType = "week";
            } else if (unit === "month") {
                const start = new Date(currentRange.start);
                const base = new Date(start.getFullYear(), start.getMonth() + amount, 1);
                newStart = new Date(base.getFullYear(), base.getMonth(), 1);
                newEnd = new Date(base.getFullYear(), base.getMonth() + 1, 0); // dernier jour du mois
                currentPeriodType = "month";
            } else if (unit === "year") {
                const start = new Date(currentRange.start);
                const year = start.getFullYear() + amount;
                newStart = new Date(year, 0, 1);
                newEnd = new Date(year, 11, 31);
                currentPeriodType = "year";
            }

            const startStr = newStart.getFullYear() + "-" + String(newStart.getMonth() + 1).padStart(2, '0') + "-" + String(newStart.getDate()).padStart(2, '0');
            const endStr = newEnd.getFullYear() + "-" + String(newEnd.getMonth() + 1).padStart(2, '0') + "-" + String(newEnd.getDate()).padStart(2, '0');

            currentRange = { start: startStr, end: endStr };

            document.getElementById("date-range-picker").value = `${startStr} - ${endStr}`;
            applyFilter(startStr, endStr);
            updatePeriodLabel(currentPeriodType, startStr, endStr);

            // mettre à jour visuellement les boutons
            document.querySelectorAll(".period-btn").forEach(b => b.classList.remove("selected"));
            const currentBtn = document.querySelector(`.period-btn[data-period="${currentPeriodType}"]`);
            if (currentBtn) currentBtn.classList.add("selected");
        }



 
 
 
 

        document.getElementById("prev-period").addEventListener("click", () => {
            const type = ["week", "month", "year"].includes(currentPeriodType) ? currentPeriodType : "manual";
            shiftDateRange(-1, type);
        });

        document.getElementById("next-period").addEventListener("click", () => {
            const type = ["week", "month", "year"].includes(currentPeriodType) ? currentPeriodType : "manual";
            shiftDateRange(1, type);
        });


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