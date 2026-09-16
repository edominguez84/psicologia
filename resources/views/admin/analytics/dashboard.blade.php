<x-admin-layout title="Dashboard analítico">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.54.1/apexcharts.min.js"></script>

    <h1 class="text-2xl font-serif text-sky-800">Dashboard analítico</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Métricas de la plataforma: visitas, interacción con redes sociales, conversión de
        registro a cita, demografía de pacientes y estado de las citas.
    </p>

    {{-- Tarjetas de números clave --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <p class="text-3xl font-serif text-sky-800">{{ number_format($siteVisits['total']) }}</p>
            <p class="mt-1 text-sm text-ink-soft">Visitas totales al sitio</p>
            <p class="mt-1 text-xs text-ink-soft">{{ number_format($siteVisits['last30Days']) }} en los últimos 30 días</p>
        </div>
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <p class="text-3xl font-serif text-sky-800">{{ number_format($conversionFunnel['registered']) }}</p>
            <p class="mt-1 text-sm text-ink-soft">Pacientes registrados</p>
        </div>
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <p class="text-3xl font-serif text-sky-800">{{ number_format($conversionFunnel['withAppointment']) }}</p>
            <p class="mt-1 text-sm text-ink-soft">Con al menos una cita</p>
            <p class="mt-1 text-xs text-ink-soft">{{ $conversionFunnel['rate'] }}% de conversión</p>
        </div>
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <p class="text-3xl font-serif text-sky-800">{{ number_format($socialClicks['total']) }}</p>
            <p class="mt-1 text-sm text-ink-soft">Clics en redes sociales</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        {{-- Citas aprobadas vs. canceladas por mes --}}
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <h2 class="font-serif text-lg text-sky-800">Citas aprobadas y canceladas por mes</h2>
            <div
                x-data="{
                    init() {
                        new ApexCharts(this.$refs.chart, {
                            chart: { type: 'bar', height: 280, toolbar: { show: false } },
                            series: [
                                { name: 'Aprobadas', data: @js($appointmentsByMonth['approved']) },
                                { name: 'Canceladas', data: @js($appointmentsByMonth['cancelled']) },
                            ],
                            xaxis: { categories: @js($appointmentsByMonth['labels']) },
                            colors: ['#0284c7', '#dc6b52'],
                            plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
                            dataLabels: { enabled: false },
                            legend: { position: 'top' },
                        }).render();
                    }
                }"
            >
                <div x-ref="chart" class="mt-4"></div>
            </div>
        </div>

        {{-- Clics por red social --}}
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <h2 class="font-serif text-lg text-sky-800">Interacción por red social</h2>
            @if (empty($socialClicks['data']))
                <p class="mt-6 rounded-xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                    Todavía no hay clics registrados en ningún link de red social.
                </p>
            @else
                <div
                    x-data="{
                        init() {
                            new ApexCharts(this.$refs.chart, {
                                chart: { type: 'donut', height: 280 },
                                series: @js($socialClicks['data']),
                                labels: @js($socialClicks['labels']),
                                colors: ['#1877f2', '#e1306c', '#000000', '#0a66c2', '#ff0000', '#111111'],
                                legend: { position: 'bottom' },
                            }).render();
                        }
                    }"
                >
                    <div x-ref="chart" class="mt-4"></div>
                </div>
            @endif
        </div>

        {{-- Género: registrados vs. con cita --}}
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <h2 class="font-serif text-lg text-sky-800">Género de pacientes</h2>
            <p class="mt-1 text-xs text-ink-soft">Registrados vs. los que llegaron a agendar una cita.</p>
            <div
                x-data="{
                    init() {
                        new ApexCharts(this.$refs.chart, {
                            chart: { type: 'bar', height: 280, toolbar: { show: false } },
                            series: [
                                { name: 'Registrados', data: @js($genderBreakdown['registered']) },
                                { name: 'Con cita', data: @js($genderBreakdown['withAppointment']) },
                            ],
                            xaxis: { categories: @js($genderBreakdown['labels']) },
                            colors: ['#94a3b8', '#0284c7'],
                            plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
                            dataLabels: { enabled: false },
                            legend: { position: 'top' },
                        }).render();
                    }
                }"
            >
                <div x-ref="chart" class="mt-4"></div>
            </div>
        </div>

        {{-- Departamentos --}}
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <h2 class="font-serif text-lg text-sky-800">Registros por departamento</h2>
            @if (empty($departmentBreakdown['data']))
                <p class="mt-6 rounded-xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                    Todavía no hay pacientes registrados con departamento.
                </p>
            @else
                <div
                    x-data="{
                        init() {
                            new ApexCharts(this.$refs.chart, {
                                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                                series: [{ name: 'Registros', data: @js($departmentBreakdown['data']) }],
                                xaxis: { categories: @js($departmentBreakdown['labels']) },
                                colors: ['#0284c7'],
                                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                                dataLabels: { enabled: false },
                            }).render();
                        }
                    }"
                >
                    <div x-ref="chart" class="mt-4"></div>
                </div>
            @endif
        </div>

        {{-- Municipios (top 10) --}}
        <div class="rounded-2xl border border-paper-200 bg-white p-5 lg:col-span-2">
            <h2 class="font-serif text-lg text-sky-800">Top 10 municipios con más registros</h2>
            @if (empty($municipalityBreakdown['data']))
                <p class="mt-6 rounded-xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                    Todavía no hay pacientes registrados con municipio.
                </p>
            @else
                <div
                    x-data="{
                        init() {
                            new ApexCharts(this.$refs.chart, {
                                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                                series: [{ name: 'Registros', data: @js($municipalityBreakdown['data']) }],
                                xaxis: { categories: @js($municipalityBreakdown['labels']) },
                                colors: ['#0284c7'],
                                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                                dataLabels: { enabled: false },
                            }).render();
                        }
                    }"
                >
                    <div x-ref="chart" class="mt-4"></div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
