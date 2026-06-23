<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/demo_data.php';

try {
    $date = $_GET['date'] ?? '';
    $metric = $_GET['metric'] ?? 'moc';
    $metrics = demo_metrics();
    $scenarios = demo_scenarios();

    if ($date === '') {
        send_json([
            'metrics' => $metrics,
            'days' => demo_days(),
        ]);
    }

    if (!isset($scenarios[$date])) {
        send_json(['error' => 'Nie znaleziono danych dla wybranej daty.'], 404);
    }

    if (!isset($metrics[$metric])) {
        send_json(['error' => 'Nieznany parametr pomiarowy.'], 400);
    }

    $rows = demo_rows_for_day($date, $scenarios[$date]);

    send_json([
        'date' => $date,
        'device' => $scenarios[$date]['device'],
        'description' => $scenarios[$date]['description'],
        'metric' => $metric,
        'metric_label' => $metrics[$metric]['label'],
        'unit' => $metrics[$metric]['unit'],
        'rows' => array_map(
            fn (array $row): array => [
                'czas_pomiaru' => $row['czas_pomiaru'],
                'godzina' => $row['godzina'],
                'value' => $row[$metric],
            ],
            $rows
        ),
    ]);
} catch (Throwable $exception) {
    send_json([
        'error' => 'Nie udało się pobrać danych dziennych.',
        'details' => $exception->getMessage(),
    ], 500);
}
