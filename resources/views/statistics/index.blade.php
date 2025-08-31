<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Estadísticas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Gasto mensual por persona</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Últimos 12 meses</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <div>
                                <label for="personSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Persona</label>
                                <select id="personSelect" class="form-select form-select-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600">
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
                                    <i class="fa fa-file-excel"></i> Exportar CSV
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Gasto por categoría (últimos 90 días)</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-2">
                            <canvas id="categoryBarChart" height="160"></canvas>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <p class="mb-2">Incluye rendiciones aprobadas en los últimos 90 días.</p>
                            <ul class="list-disc ms-5">
                                <li>Máx. 10 categorías más altas.</li>
                                <li>Las cifras están en CLP.</li>
                            </ul>
                        </div>
                    </div>
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

            // Utilidades de color según tema
            function getThemeColors() {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    grid: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                    ticks: isDark ? '#e5e7eb' : '#374151',
                    text: isDark ? '#f9fafb' : '#111827',
                    palette: [
                        '#4f46e5', '#16a34a', '#dc2626', '#f59e0b', '#0ea5e9',
                        '#84cc16', '#a21caf', '#ea580c', '#22c55e', '#ef4444'
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
                    backgroundColor: c + '33',
                    tension: 0.25,
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

            let perPersonMonthlyChart, categoryBarChart;

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

                // Bar chart: category totals
                const barCtx = document.getElementById('categoryBarChart').getContext('2d');
                if (categoryBarChart) categoryBarChart.destroy();
                categoryBarChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            label: 'Total por categoría',
                            data: categoryTotals,
                            backgroundColor: getThemeColors().palette[0] + '99',
                            borderColor: getThemeColors().palette[0],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: {
                            legend: { labels: { color: colors.text } },
                            tooltip: {
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

            document.addEventListener('DOMContentLoaded', () => {
                renderCharts();
                const select = document.getElementById('personSelect');
                if (select) {
                    select.addEventListener('change', (e) => reloadPersonMonthly(e.target.value));
                }
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
