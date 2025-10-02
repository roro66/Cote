<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Estadísticas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Gasto mensual por persona</h3>
                            <p class="text-sm text-gray-500 ">Últimos 12 meses</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <div>
                                <label for="personSelect" class="block text-sm font-medium text-gray-700 ">Persona</label>
                                <select id="personSelect" class="form-select form-select-sm bg-white  text-gray-900  border-gray-300 ">
                                    @foreach($people as $p)
                                        <option value="{{ $p->id }}" @selected($p->id === $selectedPersonId)>
                                            {{ trim($p->first_name.' '.$p->last_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-transparent">Exportar</label>
                                <a id="exportMonthlyBtn" href="{{ $selectedPersonId ? route('statistics.person.monthly.export', $selectedPersonId) : '#' }}" class="btn btn-sm btn-outline-primary disabled:opacity-60">
                                    <i class="fa fa-file-excel"></i> Exportar Excel
                                </a>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-transparent"> </label>
                                <button type="button" id="downloadMonthlyPngBtn" class="btn btn-sm btn-outline-secondary" onclick="downloadMonthlyPng()">
                                    <i class="fa fa-image"></i> Descargar PNG
                                </button>
                            </div>
                        </div>
                    </div>
                    <canvas id="perPersonMonthlyChart" height="120"></canvas>
                </div>
            </div>

            <div class="bg-white  overflow-hidden shadow sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-2">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 ">Gasto por categoría</h3>
                            <p class="text-sm text-gray-500 ">Últimos <span id="categoryDaysSpan">{{ $categoryDays }}</span> días</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <div>
                                <label for="categoryDaysInput" class="block text-sm font-medium text-gray-700 ">Días</label>
                                <input id="categoryDaysInput" type="number" min="7" max="3650" step="1" value="{{ $categoryDays }}" class="form-control form-control-sm bg-white  text-gray-900  border-gray-300 " />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-transparent"> </label>
                                <button type="button" id="applyCategoryDaysBtn" class="btn btn-sm btn-primary">Aplicar</button>
                            </div>
                            <div>
                                <label for="categoryShowAll" class="block text-sm font-medium text-gray-700 ">Mostrar todas</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="categoryShowAll" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-2">
                            <canvas id="categoryBarChart" height="160"></canvas>
                        </div>
                        <div class="text-sm text-gray-600 ">
                            <p class="mb-2">Incluye rendiciones aprobadas en el período seleccionado.</p>
                            <ul class="list-disc ms-5">
                                <li>Máx. 10 categorías más altas.</li>
                                <li>Las cifras están en CLP.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white  overflow-hidden shadow sm:rounded-lg mt-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 ">Gasto por categoría - últimos N meses</h3>
                            <p class="text-sm text-gray-500 ">Selecciona cuántos meses mostrar</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <div>
                                <label for="categoriesMonthsInput" class="block text-sm font-medium text-gray-700 ">Meses</label>
                                <input id="categoriesMonthsInput" data-months-input data-target="categoriesMonthly" type="number" min="1" max="60" step="1" value="6" class="form-control form-control-sm" />
                            </div>
                            <div>
                                <label for="categoriesPersonSelect" class="block text-sm font-medium text-gray-700 ">Persona</label>
                                <select id="categoriesPersonSelect" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    @foreach($people as $p)
                                        <option value="{{ $p->id }}">{{ trim($p->first_name.' '.$p->last_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <canvas id="categoriesMonthlyChart" height="160"></canvas>
                </div>
            </div>

            <div class="bg-white  overflow-hidden shadow sm:rounded-lg mt-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 ">Gastos por categoría de una persona</h3>
                            <p class="text-sm text-gray-500 ">Selecciona persona para ver su distribución por categorías</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <div>
                                <label for="personSelectForCategories" class="block text-sm font-medium text-gray-700 ">Persona</label>
                                <select id="personSelectForCategories" class="form-select form-select-sm">
                                    @foreach($people as $p)
                                        <option value="{{ $p->id }}">{{ trim($p->first_name.' '.$p->last_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <canvas id="personCategoriesChart" height="120"></canvas>
                </div>
            </div>

            <div class="bg-white  overflow-hidden shadow sm:rounded-lg mt-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 ">Gastos por técnico - últimos N meses</h3>
                            <p class="text-sm text-gray-500 ">Comparativa por técnico (top 8)</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <div>
                                <label for="techniciansMonthsInput" class="block text-sm font-medium text-gray-700 ">Meses</label>
                                <input id="techniciansMonthsInput" data-months-input data-target="techniciansMonthly" type="number" min="1" max="24" step="1" value="6" class="form-control form-control-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-transparent"> </label>
                                <button id="reloadTechniciansBtn" class="btn btn-sm btn-primary">Recargar</button>
                            </div>
                        </div>
                    </div>
                    <canvas id="techniciansMonthlyChart" height="140"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            // Datos del servidor
            const monthLabels = @json($monthLabels);
            const selectedPersonId = @json($selectedPersonId);
            const initialMonthlyData = @json($selectedPersonMonthly);
            const categoryLabels = @json($categoryLabels);
            const categoryTotals = @json($categoryTotals);
            let categoryDays = @json($categoryDays);

            // Utilidades de color según tema
            function getThemeColors() {
                const html = document.documentElement;
                const isDark = html.getAttribute('data-bs-theme') === 'dark' || html.classList.contains('dark');
                const styles = getComputedStyle(html);
                const textVar = styles.getPropertyValue('--bs-body-color').trim();
                const gridVar = styles.getPropertyValue('--bs-border-color').trim();
                const bgVar = styles.getPropertyValue('--bs-body-bg').trim();
                const text = textVar || (isDark ? '#e5e7eb' : '#111827');
                const grid = gridVar || (isDark ? 'rgba(255,255,255,0.12)' : 'rgba(0,0,0,0.12)');
                const tooltipBg = isDark ? 'rgba(20,20,20,0.9)' : 'rgba(255,255,255,0.95)';
                const tooltipText = isDark ? '#f3f4f6' : '#111827';
                return {
                    grid,
                    ticks: text,
                    text,
                    bg: bgVar || 'transparent',
                    tooltipBg,
                    tooltipText,
                    palette: [
                        '#7c8cff', '#5fd0a3', '#ff7b7b', '#ffcd70', '#5fc7ff',
                        '#b8f171', '#cf70ff', '#ff995f', '#5fe089', '#ff8aa0'
                    ]
                };
            }

            function buildLineDatasets(raw) {
                const colors = getThemeColors().palette;
                return raw.map((d, idx) => ({
                    label: d.label,
                    data: d.data,
                    borderColor: colors[idx % colors.length],
                    backgroundColor: colors[idx % colors.length] + '33',
                    tension: 0.25,
                }));
            }
            function buildSingleLineDataset(data) {
                const colors = getThemeColors().palette;
                const c = colors[0];
                return [{
                    label: 'Total mensual',
                    data,
                    borderColor: c,
                    backgroundColor: c + '1A', // leve relleno
                    borderWidth: 3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: c,
                    pointBorderColor: c,
                    tension: 0.25,
                    fill: false,
                }];
            }

            // Etiquetas de meses: usaremos nuestras labels de servidor directamente
            function normalizeMonthLabel(raw) {
                if (!raw) return '';
                const months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                let out = String(raw);
                for (const m of months) {
                    const re = new RegExp(`(${m})+`, 'gi');
                    out = out.replace(re, (match) => m);
                }
                return out;
            }

            function normalizeMonthLabels(arr) {
                if (!Array.isArray(arr)) return arr;
                return arr.map(l => normalizeMonthLabel(l));
            }

            // Paleta pastel por barra (mejor contraste para light/dark)
            function hsl(h, s, l, a = 1) { return `hsla(${h}, ${s}%, ${l}%, ${a})`; }
            function getPastelPalette(count) {
                const isDark = document.documentElement.classList.contains('dark');
                const backgrounds = [], borders = [];
                for (let i = 0; i < count; i++) {
                    const hue = Math.round(360 * (i / Math.max(1, count)));
                    const sat = 60;
                    const light = isDark ? 60 : 78; // un poco más oscuro en dark
                    const borderLight = isDark ? light - 12 : light - 18;
                    backgrounds.push(hsl(hue, sat, light, 0.7));
                    borders.push(hsl(hue, sat, Math.max(30, borderLight), 1));
                }
                return { backgrounds, borders };
            }

            // Plugin: escribe el valor de cada barra encima/delante de la barra
            let valueLabelsRegistered = false;
            function ensureValueLabelsPlugin() {
                if (valueLabelsRegistered || typeof Chart === 'undefined') return;
                const valueLabels = {
                    id: 'barValueLabels',
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea, data } = chart;
                        const meta = chart.getDatasetMeta(0);
                        if (!meta || !meta.data) return;
                        const colors = getThemeColors();
                        ctx.save();
                        ctx.fillStyle = colors.text;
                        ctx.font = '12px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                        ctx.textBaseline = 'middle';
                        const fmt = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 });
                        for (let i = 0; i < meta.data.length; i++) {
                            const el = meta.data[i];
                            const val = data.datasets[0].data[i];
                            if (val == null) continue;
                            const pos = el.tooltipPosition();
                            const text = fmt.format(val);
                            let x = pos.x + 6;
                            const y = pos.y;
                            // Evitar que el texto se salga a la derecha
                            const w = ctx.measureText(text).width + 4;
                            if (x + w > chartArea.right) {
                                x = chartArea.right - 4;
                                ctx.textAlign = 'right';
                            } else {
                                ctx.textAlign = 'left';
                            }
                            ctx.fillText(text, x, y);
                        }
                        ctx.restore();
                    }
                };
                Chart.register(valueLabels);
                valueLabelsRegistered = true;
            }

            let perPersonMonthlyChart, categoryBarChart;
            let categoriesMonthlyChart, personCategoriesChart, techniciansMonthlyChart;

            function renderCharts() {
                const colors = getThemeColors();

                // Line chart: per-person monthly
                const lineCtx = document.getElementById('perPersonMonthlyChart').getContext('2d');
                if (perPersonMonthlyChart) perPersonMonthlyChart.destroy();
        perPersonMonthlyChart = new Chart(lineCtx, {
                    type: 'line',
                    data: {
            labels: monthLabels,
            datasets: buildSingleLineDataset(initialMonthlyData),
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { labels: { color: colors.text } },
                            tooltip: {
                                backgroundColor: colors.tooltipBg,
                                titleColor: colors.tooltipText,
                                bodyColor: colors.tooltipText,
                                borderColor: colors.grid,
                                borderWidth: 1,
                                displayColors: false,
                                callbacks: {
                                    label: (ctx) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(ctx.parsed.y)
                                }
                            }
                        },
                        scales: {
                            x: {
                                type: 'category',
                                ticks: {
                                    color: colors.ticks,
                                    autoSkip: false, // mostrar los 12 meses
                                    maxRotation: 45,
                                    minRotation: 45, // diagonal
                                    callback: function(value) { return normalizeMonthLabel(this.getLabelForValue(value)); },
                                },
                                grid: { color: colors.grid }
                            },
                            y: { ticks: { color: colors.ticks, callback: v => new Intl.NumberFormat('es-CL').format(v) }, grid: { color: colors.grid } }
                        }
                    }
                });

                // Bar chart: category totals (apply "Mostrar todas" filter on initial render)
                const barCanvas = document.getElementById('categoryBarChart');
                // Prepare initial labels/totals according to the checkbox state
                let initLabels = categoryLabels.slice();
                let initTotals = categoryTotals.slice();
                try {
                    const showAllInit = document.getElementById('categoryShowAll')?.checked;
                    if (!showAllInit) {
                        const filteredIdx = [];
                        for (let i = 0; i < initTotals.length; i++) if (initTotals[i] > 0) filteredIdx.push(i);
                        initLabels = filteredIdx.map(i => initLabels[i]);
                        initTotals = filteredIdx.map(i => initTotals[i]);
                    }
                } catch (e) {
                    // ignore and fallback to full lists
                }
                // Ajustar altura del canvas para mostrar todas las categorías (28px por fila aprox.)
                try {
                    const rows = Math.max(1, initTotals.length);
                    barCanvas.style.height = Math.max(160, rows * 28) + 'px';
                } catch (e) {
                    // fallback: leave default
                }
                const barCtx = barCanvas.getContext('2d');
                if (categoryBarChart) categoryBarChart.destroy();
                const pastel = getPastelPalette(initTotals.length);
                ensureValueLabelsPlugin();
                categoryBarChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: initLabels,
                        datasets: [{
                            label: 'Total por categoría',
                            data: initTotals,
                            backgroundColor: pastel.backgrounds,
                            borderColor: pastel.borders,
                            borderWidth: 1.25
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        layout: { padding: { right: 36 } },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: colors.tooltipBg,
                                titleColor: colors.tooltipText,
                                bodyColor: colors.tooltipText,
                                borderColor: colors.grid,
                                borderWidth: 1,
                                displayColors: false,
                                callbacks: {
                                    label: (ctx) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(ctx.parsed.x)
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: colors.ticks, callback: v => new Intl.NumberFormat('es-CL').format(v) }, grid: { color: colors.grid } },
                            y: { ticks: { color: colors.ticks }, grid: { color: colors.grid } }
                        }
                    }
                });

                // Placeholder charts for additional widgets
                // categoriesMonthlyChart (stacked lines by category)
                const catMonthlyEl = document.getElementById('categoriesMonthlyChart');
                if (catMonthlyEl) {
                    const ctx = catMonthlyEl.getContext('2d');
                    if (categoriesMonthlyChart) categoriesMonthlyChart.destroy();
                    categoriesMonthlyChart = new Chart(ctx, {
                        type: 'line',
                        data: { labels: [], datasets: [] },
                        options: { responsive: true, plugins: { legend: { labels: { color: colors.text } } }, scales: { x: { ticks: { color: colors.ticks } }, y: { ticks: { color: colors.ticks } } } }
                    });
                }

                // personCategoriesChart (bar)
                const personCatEl = document.getElementById('personCategoriesChart');
                if (personCatEl) {
                    const ctx2 = personCatEl.getContext('2d');
                    if (personCategoriesChart) personCategoriesChart.destroy();
                    personCategoriesChart = new Chart(ctx2, {
                        type: 'bar',
                        data: { labels: [], datasets: [{ label: 'Total por categoría', data: [], backgroundColor: getPastelPalette(10).backgrounds }] },
                        options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: colors.ticks } }, y: { ticks: { color: colors.ticks } } } }
                    });
                }

                // techniciansMonthlyChart (multi-line)
                const techEl = document.getElementById('techniciansMonthlyChart');
                if (techEl) {
                    const ctx3 = techEl.getContext('2d');
                    if (techniciansMonthlyChart) techniciansMonthlyChart.destroy();
                    techniciansMonthlyChart = new Chart(ctx3, {
                        type: 'line',
                        data: { labels: [], datasets: [] },
                        options: { responsive: true, plugins: { legend: { labels: { color: colors.ticks } } }, scales: { x: { ticks: { color: colors.ticks } }, y: { ticks: { color: colors.ticks } } } }
                    });
                }
            }

            async function reloadPersonMonthly(personId) {
                const url = `{{ url('/statistics/person') }}/${personId}/monthly`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const json = await res.json();
                // Actualizamos labels y dataset
                monthLabels.length = 0; json.monthLabels.forEach(l => monthLabels.push(l));
                perPersonMonthlyChart.data.labels = monthLabels;
                perPersonMonthlyChart.data.datasets = buildSingleLineDataset(json.data);
                perPersonMonthlyChart.update();

                // Actualiza link de exportación
                const exportBtn = document.getElementById('exportMonthlyBtn');
                exportBtn.href = `{{ url('/statistics/person') }}/${personId}/monthly/export`;
                exportBtn.classList.remove('disabled');
            }

            async function reloadCategories(days) {
                const url = `{{ route('statistics.categories') }}?days=${encodeURIComponent(days)}`;
                console.log('Statistics: reloadCategories start, url=', url);
                let res;
                try {
                    res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                } catch (err) {
                    console.error('Statistics: fetch error when reloading categories', err);
                    return;
                }
                if (!res.ok) {
                    console.error('Statistics: reloadCategories response not ok', res.status, res.statusText);
                    try { const t = await res.text(); console.error('Response body:', t.slice(0,200)); } catch(e){}
                    return;
                }
                let json;
                try {
                    json = await res.json();
                    console.log('Statistics: reloadCategories response json labels=', (json.labels||[]).length, 'totals=', (json.totals||[]).length, 'days=', json.days);
                } catch (err) {
                    console.error('Statistics: error parsing JSON from reloadCategories', err);
                    try { const t = await res.text(); console.error('Response text:', t.slice(0,400)); } catch(e){}
                    return;
                }
                categoryDays = json.days;
                document.getElementById('categoryDaysSpan').textContent = String(categoryDays);
                // Recalcular paleta
                const pastel = getPastelPalette(json.totals.length);
                // Actualizar datos
                // Ajustar canvas height para mostrar todas las categorías
                try {
                    const rows = Math.max(1, json.totals.length);
                    const canvas = document.getElementById('categoryBarChart');
                    canvas.style.height = Math.max(160, rows * 28) + 'px';
                } catch (e) {}
                
                try {
                    const showAll = document.getElementById('categoryShowAll')?.checked;
                    let labels = json.labels.slice();
                    let totals = json.totals.slice();
                    let backgrounds = pastel.backgrounds.slice();
                    let borders = pastel.borders.slice();
                    if (!showAll) {
                        // filter out zero totals
                        const filtered = [];
                        for (let i = 0; i < totals.length; i++) {
                            if (totals[i] > 0) filtered.push(i);
                        }
                        labels = filtered.map(i => labels[i]);
                        totals = filtered.map(i => totals[i]);
                        backgrounds = filtered.map(i => backgrounds[i % backgrounds.length]);
                        borders = filtered.map(i => borders[i % borders.length]);
                    }
                    categoryBarChart.data.labels = labels;
                    categoryBarChart.data.datasets[0].data = totals;
                    categoryBarChart.data.datasets[0].backgroundColor = backgrounds;
                    categoryBarChart.data.datasets[0].borderColor = borders;
                    categoryBarChart.update();
                    try { categoryBarChart.resize(); } catch (e) {}
                } catch (err) {
                    console.error('Statistics: error updating categoryBarChart', err);
                }
            }

            // New: load categories monthly for N months
            async function loadCategoriesMonthly(months, personId = '') {
                let url = `{{ route('statistics.categories.monthly') }}?months=${encodeURIComponent(months)}`;
                if (personId) url += `&person_id=${encodeURIComponent(personId)}`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const json = await res.json();
                if (!categoriesMonthlyChart) return;
                categoriesMonthlyChart.data.labels = normalizeMonthLabels(json.monthLabels);
                categoriesMonthlyChart.data.datasets = buildLineDatasets(json.datasets);
                categoriesMonthlyChart.update();
            }

            async function loadPersonCategories(personId, months=6) {
                const url = `{{ url('/statistics/person') }}/${personId}/categories?months=${months}`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const json = await res.json();
                if (!personCategoriesChart) return;
                personCategoriesChart.data.labels = json.labels;
                personCategoriesChart.data.datasets[0].data = json.totals;
                personCategoriesChart.update();
            }

            async function loadTechniciansMonthly(months=6) {
                const url = `{{ route('statistics.technicians.monthly') }}?months=${months}`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const json = await res.json();
                if (!techniciansMonthlyChart) return;
                techniciansMonthlyChart.data.labels = normalizeMonthLabels(json.monthLabels);
                techniciansMonthlyChart.data.datasets = buildLineDatasets(json.datasets);
                techniciansMonthlyChart.update();
            }

            document.addEventListener('DOMContentLoaded', () => {
                renderCharts();
                const select = document.getElementById('personSelect');
                if (select) {
                    select.addEventListener('change', (e) => reloadPersonMonthly(e.target.value));
                }
                // Categories controls
                const applyBtn = document.getElementById('applyCategoryDaysBtn');
                const daysInput = document.getElementById('categoryDaysInput');
                if (applyBtn && daysInput) {
                    applyBtn.addEventListener('click', () => {
                        try {
                            const val = parseInt(daysInput.value, 10);
                            console.log('Statistics: applyCategoryDaysBtn clicked, days=', val);
                            if (!isNaN(val)) reloadCategories(val);
                        } catch (err) {
                            console.error('Statistics: error in apply handler', err);
                        }
                    });
                }
                // New controls: months inputs and tech dropdown
                const monthsInputs = document.querySelectorAll('[data-months-input]');
                monthsInputs.forEach(el => {
                    const target = el.getAttribute('data-target');
                    el.addEventListener('change', (e) => {
                        const v = parseInt(e.target.value, 10) || 6;
                        if (target === 'categoriesMonthly') {
                            const personEl = document.getElementById('categoriesPersonSelect');
                            const pid = personEl ? personEl.value : '';
                            loadCategoriesMonthly(v, pid);
                        }
                        if (target === 'techniciansMonthly') loadTechniciansMonthly(v);
                    });
                });

                const categoriesPersonSelect = document.getElementById('categoriesPersonSelect');
                if (categoriesPersonSelect) {
                    categoriesPersonSelect.addEventListener('change', (e) => {
                        const monthsEl = document.getElementById('categoriesMonthsInput');
                        const months = parseInt(monthsEl.value, 10) || 6;
                        loadCategoriesMonthly(months, e.target.value);
                    });
                }
                const categoryShowAll = document.getElementById('categoryShowAll');
                if (categoryShowAll) {
                    categoryShowAll.addEventListener('change', () => {
                        // re-apply current days filter
                        const daysEl = document.getElementById('categoryDaysInput');
                        const val = parseInt(daysEl.value, 10) || 90;
                        reloadCategories(val);
                    });
                }

                const techReloadBtn = document.getElementById('reloadTechniciansBtn');
                if (techReloadBtn) techReloadBtn.addEventListener('click', () => {
                    const el = document.getElementById('techniciansMonthsInput');
                    const v = parseInt(el.value, 10) || 6;
                    loadTechniciansMonthly(v);
                });

                const personCatSelect = document.getElementById('personSelectForCategories');
                if (personCatSelect) {
                    personCatSelect.addEventListener('change', (e) => loadPersonCategories(e.target.value));
                }
                // bootstrap: init default loads (include selected person if set)
                const initPersonEl = document.getElementById('categoriesPersonSelect');
                const initPersonId = initPersonEl ? initPersonEl.value : '';
                loadCategoriesMonthly(6, initPersonId);
                loadTechniciansMonthly(6);
                if (personCatSelect) loadPersonCategories(personCatSelect.value);
                window.addEventListener('theme-changed', () => { renderCharts(); });
            });

            function downloadMonthlyPng() {
                if (!perPersonMonthlyChart) return;
                const select = document.getElementById('personSelect');
                const name = select ? (select.options[select.selectedIndex]?.text || 'persona') : 'persona';
                const safe = name.toLowerCase().replace(/[^a-z0-9-_]+/g, '-');
                const link = document.createElement('a');
                link.download = `gasto-mensual-${safe}.png`;
                // Chart.js 4: toBase64Image() retorna dataURL del canvas
                link.href = perPersonMonthlyChart.toBase64Image('image/png', 1);
                document.body.appendChild(link);
                link.click();
                link.remove();
            }
        </script>
    @endpush
</x-app-layout>
