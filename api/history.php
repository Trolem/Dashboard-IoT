<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/demo_data.php';

try {
    if (USE_MOCK_DATA) {
        $rows = array_slice(demo_all_rows(), -24);

        send_json($rows);
    }

    $pdo = database();

    $statement = $pdo->query(
        'SELECT id, czas_pomiaru, urzadzenie, napiecie, natezenie, moc, energia,
                czestotliwosc, wspolczynnik_mocy, moc_pozorna
         FROM pomiary
         ORDER BY czas_pomiaru DESC, id DESC
         LIMIT 24'
    );

    $rows = array_reverse($statement->fetchAll());

    send_json($rows);
} catch (Throwable $exception) {
    send_json([
        'error' => 'Nie udało się pobrać historii pomiarów.',
        'details' => $exception->getMessage(),
    ], 500);
}
