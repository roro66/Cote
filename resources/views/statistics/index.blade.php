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
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Gasto mensual por persona (Top 5)</h3>
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
            const personDatasetsRaw = @json($personDatasets);
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
                        datasets: buildLineDatasets(personDatasetsRaw),
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
                                    autoSkip: true,
                                    maxTicksLimit: 12,
                                    maxRotation: 0,
                                    minRotation: 0,
                                    callback: (val, idx) => {
                                        // Fuerza el uso de nuestras etiquetas tal cual y evita formateos implícitos
                                        const raw = monthLabels[idx] ?? '';
                                        // Opcional: abreviar meses muy largos para evitar solape
                                        // p.ej., "septiembre 2024" -> "Sep 2024"
                                        const parts = String(raw).split(/\s+/);
                                        if (parts.length >= 2) {
                                            const m = parts[0];
                                            const y = parts[1];
                                            const map = {
                                                'enero': 'Ene', 'febrero': 'Feb', 'marzo': 'Mar', 'abril': 'Abr', 'mayo': 'May', 'junio': 'Jun',
                                                'julio': 'Jul', 'agosto': 'Ago', 'septiembre': 'Sep', 'setiembre': 'Sep', 'octubre': 'Oct', 'noviembre': 'Nov', 'diciembre': 'Dic',
                                                'ene': 'Ene', 'feb': 'Feb', 'mar': 'Mar', 'abr': 'Abr', 'may': 'May', 'jun': 'Jun',
                                                'jul': 'Jul', 'ago': 'Ago', 'sep': 'Sep', 'oct': 'Oct', 'nov': 'Nov', 'dic': 'Dic'
                                            };
                                            const key = m.toLowerCase();
                                            const shortM = map[key] ?? m;
                                            return `${shortM} ${y}`;
                                        }
                                        return raw;
                                    },
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

            document.addEventListener('DOMContentLoaded', () => {
                renderCharts();
                window.addEventListener('theme-changed', () => {
                    renderCharts();
                });
            });
        </script>
    @endpush
</x-app-layout>
