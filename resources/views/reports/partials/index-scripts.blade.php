{{--
    Shared report-page scripts — included by BOTH themes.

    Extracted verbatim from reports/index.blade.php (Phase 5.6), following the
    §22 precedent set by dashboard/partials/enterprise-scripts.blade.php: report
    logic lives in one file so the two themes cannot drift apart.

    DOM contract this relies on (both themes must honour it):
      - .accordion-header elements carrying data-target="<panel id>"
      - .accordion-body panels with those ids, toggled via the `hidden` class
      - .accordion-icon inside each header
      - #analysisPieChart canvas
      - #btnScope / #btnEmission toggle buttons, styled with the
        btn-primary / btn-secondary pair

    NOTE ON CHART.JS VERSION: both shells load Chart.js 3.9.1 in <head>. The
    unpinned tag below resolves to v4 and deliberately overrides it, because
    chartjs-plugin-datalabels@2 is what this page needs. Do not "tidy" this by
    removing the tag — the page would fall back to 3.9.1 without ChartDataLabels.
--}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const headers = document.querySelectorAll('.accordion-header');

        headers.forEach(header => {
            header.addEventListener('click', () => {
                const targetId = header.dataset.target;
                const targetBody = document.getElementById(targetId);
                const icon = header.querySelector('.accordion-icon');

                document.querySelectorAll('.accordion-body').forEach(body => {
                    if (body !== targetBody) body.classList.add('hidden');
                });

                document.querySelectorAll('.accordion-icon').forEach(i => {
                    if (i !== icon) i.style.transform = 'rotate(0deg) scale(1)';
                });

                const isOpen = !targetBody.classList.contains('hidden');
                targetBody.classList.toggle('hidden', isOpen);

                icon.style.transform = isOpen ? 'rotate(0deg) scale(1)' :
                    'rotate(90deg) scale(1.05)';
            });
        });
    });

    @if (isset($measurement) && $measurement && isset($report) && isset($chartPayload))
        const ctx = document.getElementById('analysisPieChart');

        const scopeData = {
            labels: @json($chartPayload['scopeLabels']),
            values: @json($chartPayload['scopePercentages']),
            raw: @json($chartPayload['scopeRawValues']),
            colors: @json($chartPayload['scopeColors'])
        };

        const emissionSourceData = {
            labels: @json($chartPayload['sourceLabels']),
            values: @json($chartPayload['sourcePercents']),
            raw: @json($chartPayload['sourceRawTonnes']),
            colors: @json($chartPayload['sourceColors'])
        };

        let chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: scopeData.labels,
                datasets: [{
                    data: scopeData.values,
                    raw: scopeData.raw,
                    backgroundColor: scopeData.colors,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: chartOptions(),
            plugins: [ChartDataLabels]
        });

        function chartOptions() {
            return {
                responsive: true,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 14 }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value) => value > 0 ? value + '%' : ''
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const index = context.dataIndex;
                                const rawValue = context.dataset.raw[index];
                                return `${context.label}: ${rawValue} tCO₂e (${context.raw}%)`;
                            }
                        }
                    }
                }
            }
        }

        document.getElementById('btnScope').addEventListener('click', () => {
            setActiveButton('btnScope', 'btnEmission');
            updateChart(scopeData);
        });

        document.getElementById('btnEmission').addEventListener('click', () => {
            setActiveButton('btnEmission', 'btnScope');
            updateChart(emissionSourceData);
        });

        function updateChart(data) {
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.data.datasets[0].raw = data.raw;
            chart.data.datasets[0].backgroundColor = data.colors.slice(0, data.labels.length);
            chart.update();
        }

        function setActiveButton(active, inactive) {
            document.getElementById(active).classList.remove('btn-secondary');
            document.getElementById(active).classList.add('btn-primary');
            document.getElementById(inactive).classList.remove('btn-primary');
            document.getElementById(inactive).classList.add('btn-secondary');
        }
    @endif
</script>
