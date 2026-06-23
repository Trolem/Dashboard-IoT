<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/demo_data.php';

try {
    if (USE_MOCK_DATA) {
        send_json(demo_summary());
    }

    $pdo = database();

    $summary = [
        'measurement_count' => (int) $pdo->query('SELECT COUNT(*) FROM pomiary')->fetchColumn(),
        'device_count' => (int) $pdo->query('SELECT COUNT(DISTINCT urzadzenie) FROM pomiary')->fetchColumn(),
        'date_from' => $pdo->query('SELECT MIN(DATE(czas_pomiaru)) FROM pomiary')->fetchColumn(),
        'date_to' => $pdo->query('SELECT MAX(DATE(czas_pomiaru)) FROM pomiary')->fetchColumn(),
        'total_energy' => (float) $pdo->query('SELECT MAX(energia) FROM pomiary')->fetchColumn(),
        'max_power' => (float) $pdo->query('SELECT MAX(moc) FROM pomiary')->fetchColumn(),
        'avg_power_factor' => (float) $pdo->query('SELECT AVG(wspolczynnik_mocy) FROM pomiary')->fetchColumn(),
        'days' => [],
    ];

    send_json($summary);
} catch (Throwable $exception) {
    send_json([
        'error' => 'Nie udało się pobrać podsumowania.',
        'details' => $exception->getMessage(),
    ], 500);
}
