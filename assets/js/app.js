const API = {
    latest: 'api/latest.php',
    history: 'api/history.php',
    daily: 'api/daily.php',
    summary: 'api/summary.php',
    records: 'api/records.php',
    exportCsv: 'api/export_csv.php',
    control: 'api/control.php',
};

const REFRESH_INTERVAL_MS = 8000;

let metrics = {};
let selectedMetricKeys = ['napiecie', 'natezenie', 'moc', 'energia'];
let overviewChart;
let dailyChart;
let comparisonChart;
let latestMeasurement = {};
let historyRows = [];
let summaryData = {};

const elements = {
    summaryCount: document.querySelector('#summaryCount'),
    summaryRange: document.querySelector('#summaryRange'),
    summaryEnergy: document.querySelector('#summaryEnergy'),
    summaryMaxPower: document.querySelector('#summaryMaxPower'),
    relayState: document.querySelector('#relayStateLabel'),
    relayUpdated: document.querySelector('#relayUpdated'),
    powerToggle: document.querySelector('#powerToggleButton'),
    controls: document.querySelector('#parameterControls'),
    metricsGrid: document.querySelector('#metricsGrid'),
    lastUpdate: document.querySelector('#lastUpdate'),
    dailyDate: document.querySelector('#dailyDateSelect'),
    dailyMetric: document.querySelector('#dailyMetricSelect'),
    dailySummary: document.querySelector('#dailySummary'),
    recordsDate: document.querySelector('#recordsDateSelect'),
    recordsBody: document.querySelector('#recordsBody'),
    exportCsv: document.querySelector('#exportCsvButton'),
};

let controlState = {
    power_enabled: true,
    label: 'Zasilanie włączone',
};

function formatNumber(value, digits = 1) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return '--';
    }

    return number.toFixed(digits);
}

function metricUnit(key) {
    return metrics[key]?.unit ?? '';
}

function metricDigits(key) {
    return Number(metrics[key]?.digits ?? 1);
}

async function getJson(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`API zwróciło status ${response.status}`);
    }

    return response.json();
}

function buildUrl(url, params) {
    const query = new URLSearchParams(params);
    return `${url}?${query.toString()}`;
}

function renderParameterControls() {
    elements.controls.innerHTML = Object.entries(metrics)
        .map(([key, metric]) => `
            <label class="parameter-toggle">
                <input type="checkbox" value="${key}" ${selectedMetricKeys.includes(key) ? 'checked' : ''}>
                <span>${metric.label}</span>
            </label>
        `)
        .join('');
}

function readSelectedMetrics() {
    selectedMetricKeys = [...elements.controls.querySelectorAll('input:checked')]
        .map((input) => input.value);

    if (selectedMetricKeys.length === 0) {
        selectedMetricKeys = ['moc'];
        const powerInput = elements.controls.querySelector('input[value="moc"]');
        if (powerInput) {
            powerInput.checked = true;
        }
    }
}

function renderMetricCards(measurement) {
    elements.metricsGrid.innerHTML = selectedMetricKeys
        .map((key) => {
            const metric = metrics[key];
            return `
                <article class="metric-card">
                    <span class="metric-label">${metric.label}</span>
                    <strong>${formatNumber(measurement[key], metric.digits)}</strong>
                    <small>${metric.unit}</small>
                </article>
            `;
        })
        .join('');
}

function renderSummary(summary) {
    elements.summaryCount.textContent = summary.measurement_count ?? '--';
    elements.summaryRange.textContent = `${summary.date_from} - ${summary.date_to}`;
    elements.summaryEnergy.textContent = formatNumber(summary.total_energy, 3);
    elements.summaryMaxPower.textContent = formatNumber(summary.max_power, 1);
}

function renderControlState(state) {
    controlState = state;
    elements.relayState.textContent = state.label;
    elements.relayState.classList.toggle('off', !state.power_enabled);
    elements.relayUpdated.textContent = `Ostatnia komenda: ${state.updated_at}`;
    elements.powerToggle.textContent = state.power_enabled
        ? 'Odłącz zasilanie'
        : 'Włącz zasilanie';
    elements.powerToggle.classList.toggle('restore', !state.power_enabled);
}

function datasetsFromRows(rows) {
    return selectedMetricKeys.map((key) => {
        const metric = metrics[key];

        return {
            label: `${metric.label} [${metric.unit}]`,
            data: rows.map((row) => row[key]),
            borderColor: metric.color,
            backgroundColor: `${metric.color}22`,
            borderWidth: 2,
            fill: false,
            tension: 0.35,
            pointRadius: 2,
        };
    });
}

function renderOverviewChart(rows) {
    const labels = rows.map((row) => row.godzina ?? row.czas_pomiaru?.slice(11, 16));

    if (!overviewChart) {
        overviewChart = new Chart(document.querySelector('#overviewChart'), {
            type: 'line',
            data: {
                labels,
                datasets: datasetsFromRows(rows),
            },
            options: chartOptions(true),
        });
        return;
    }

    overviewChart.data.labels = labels;
    overviewChart.data.datasets = datasetsFromRows(rows);
    overviewChart.update();
}

function renderDailyChart(payload) {
    elements.dailySummary.textContent = `${payload.date} - ${payload.device}. ${payload.description}`;

    const data = {
        labels: payload.rows.map((row) => row.godzina),
        datasets: [
            {
                label: `${payload.metric_label} [${payload.unit}]`,
                data: payload.rows.map((row) => row.value),
                borderColor: metrics[payload.metric]?.color ?? '#0f8b8d',
                backgroundColor: `${metrics[payload.metric]?.color ?? '#0f8b8d'}22`,
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointRadius: 2,
            },
        ],
    };

    if (!dailyChart) {
        dailyChart = new Chart(document.querySelector('#dailyChart'), {
            type: 'line',
            data,
            options: chartOptions(false),
        });
        return;
    }

    dailyChart.data = data;
    dailyChart.update();
}

function renderComparisonChart(days) {
    const data = {
        labels: days.map((day) => day.device),
        datasets: [
            {
                label: 'Energia [kWh]',
                data: days.map((day) => day.energy),
                backgroundColor: ['#0f8b8d', '#7c3aed', '#b45309', '#dc2626', '#2563eb', '#15803d'],
                borderRadius: 6,
            },
        ],
    };

    if (!comparisonChart) {
        comparisonChart = new Chart(document.querySelector('#comparisonChart'), {
            type: 'bar',
            data,
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e4e9f2',
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });
        return;
    }

    comparisonChart.data = data;
    comparisonChart.update();
}

function chartOptions(beginAtZero) {
    return {
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            y: {
                beginAtZero,
                grid: {
                    color: '#e4e9f2',
                },
            },
            x: {
                grid: {
                    display: false,
                },
            },
        },
        plugins: {
            legend: {
                display: true,
                labels: {
                    boxWidth: 12,
                    boxHeight: 12,
                },
            },
        },
    };
}

function renderRecords(rows) {
    elements.recordsBody.innerHTML = rows
        .map((row) => `
            <tr>
                <td>${row.czas_pomiaru}</td>
                <td>${row.urzadzenie ?? '-'}</td>
                <td>${formatNumber(row.napiecie, 1)} V</td>
                <td>${formatNumber(row.natezenie, 2)} A</td>
                <td>${formatNumber(row.moc, 1)} W</td>
                <td>${formatNumber(row.energia, 3)} kWh</td>
                <td>${formatNumber(row.wspolczynnik_mocy, 2)}</td>
                <td>${formatNumber(row.moc_pozorna, 1)} VA</td>
            </tr>
        `)
        .join('');
}

function populateSelectors(metadata) {
    const dayOptions = metadata.days
        .map((day) => `<option value="${day.date}">${day.date} - ${day.device}</option>`)
        .join('');

    elements.dailyDate.innerHTML = dayOptions;
    elements.recordsDate.innerHTML = `<option value="">Wszystkie dni</option>${dayOptions}`;

    elements.dailyMetric.innerHTML = Object.entries(metadata.metrics)
        .map(([key, metric]) => `<option value="${key}">${metric.label}</option>`)
        .join('');
}

function refreshVisibleData() {
    readSelectedMetrics();
    renderMetricCards(latestMeasurement);
    renderOverviewChart(historyRows);
}

async function loadSummary() {
    summaryData = await getJson(API.summary);
    renderSummary(summaryData);
    renderComparisonChart(summaryData.days);
}

async function refreshDashboard() {
    try {
        const [latest, history] = await Promise.all([
            getJson(API.latest),
            getJson(API.history),
        ]);

        latestMeasurement = latest;
        if (latest.control) {
            renderControlState(latest.control);
        }
        historyRows = history;
        refreshVisibleData();
        elements.lastUpdate.textContent = latest.czas_pomiaru
            ? `Ostatni pomiar: ${latest.czas_pomiaru}`
            : 'Brak danych';
    } catch (error) {
        elements.lastUpdate.textContent = `Błąd pobierania danych: ${error.message}`;
    }
}

async function loadControlState() {
    const state = await getJson(API.control);
    renderControlState(state);
}

async function togglePower() {
    const nextState = !controlState.power_enabled;
    elements.powerToggle.disabled = true;

    try {
        const response = await fetch(API.control, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                power_enabled: nextState,
            }),
        });

        if (!response.ok) {
            throw new Error(`API zwróciło status ${response.status}`);
        }

        renderControlState(await response.json());
        await refreshDashboard();
    } finally {
        elements.powerToggle.disabled = false;
    }
}

async function refreshDailyChart() {
    if (!elements.dailyDate.value || !elements.dailyMetric.value) {
        return;
    }

    const payload = await getJson(buildUrl(API.daily, {
        date: elements.dailyDate.value,
        metric: elements.dailyMetric.value,
    }));

    renderDailyChart(payload);
}

async function refreshRecords() {
    const rows = await getJson(buildUrl(API.records, {
        date: elements.recordsDate.value,
        limit: 30,
    }));

    renderRecords(rows);
}

function exportRecordsCsv() {
    const exportUrl = new URL(API.exportCsv, window.location.href);

    if (elements.recordsDate.value) {
        exportUrl.searchParams.set('date', elements.recordsDate.value);
    }

    const downloadLink = document.createElement('a');
    downloadLink.href = exportUrl.toString();
    downloadLink.download = '';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    downloadLink.remove();
}

async function init() {
    const metadata = await getJson(API.daily);
    metrics = metadata.metrics;

    renderParameterControls();
    populateSelectors(metadata);

    elements.controls.addEventListener('change', refreshVisibleData);
    elements.dailyDate.addEventListener('change', refreshDailyChart);
    elements.dailyMetric.addEventListener('change', refreshDailyChart);
    elements.recordsDate.addEventListener('change', refreshRecords);
    elements.exportCsv.addEventListener('click', exportRecordsCsv);
    elements.powerToggle.addEventListener('click', togglePower);

    await Promise.all([
        loadSummary(),
        loadControlState(),
        refreshDashboard(),
        refreshDailyChart(),
        refreshRecords(),
    ]);

    setInterval(refreshDashboard, REFRESH_INTERVAL_MS);
}

init().catch((error) => {
    elements.lastUpdate.textContent = `Błąd startu aplikacji: ${error.message}`;
});
