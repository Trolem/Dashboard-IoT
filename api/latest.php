<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/demo_data.php';
require __DIR__ . '/control_state.php';

try {
    if (USE_MOCK_DATA) {
        $rows = demo_all_rows();
        $latest = end($rows);
        $latest['source'] = 'demo';

        send_json(apply_control_state_to_measurement($latest));
    }

    $pdo = database();

    $statement = $pdo->query(
        'SELECT id, czas_pomiaru, urzadzenie, napiecie, natezenie, moc, energia,
                czestotliwosc, wspolczynnik_mocy, moc_pozorna
         FROM pomiary
         ORDER BY czas_pomiaru DESC, id DESC
         LIMIT 1'
    );

    $measurement = $statement->fetch();

    if (!$measurement) {
        send_json(['error' => 'Brak pomiarów w bazie danych.'], 404);
    }

    $measurement['source'] = 'mysql';
    send_json(apply_control_state_to_measurement($measurement));
} catch (Throwable $exception) {
    send_json([
        'error' => 'Nie udało się pobrać najnowszego pomiaru.',
        'details' => $exception->getMessage(),
    ], 500);
}
