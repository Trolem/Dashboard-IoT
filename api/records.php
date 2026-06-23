<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/demo_data.php';

try {
    $date = $_GET['date'] ?? '';
    $limit = min(80, max(10, (int) ($_GET['limit'] ?? 30)));

    if (USE_MOCK_DATA) {
        $rows = demo_all_rows();

        if ($date !== '') {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => $row['data'] === $date
            ));
        }

        send_json(array_slice(array_reverse($rows), 0, $limit));
    }

    $pdo = database();

    if ($date !== '') {
        $statement = $pdo->prepare(
            'SELECT id, czas_pomiaru, urzadzenie, napiecie, natezenie, moc, energia,
                    czestotliwosc, wspolczynnik_mocy, moc_pozorna
             FROM pomiary
             WHERE DATE(czas_pomiaru) = :date
             ORDER BY czas_pomiaru DESC, id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':date', $date);
    } else {
        $statement = $pdo->prepare(
            'SELECT id, czas_pomiaru, urzadzenie, napiecie, natezenie, moc, energia,
                    czestotliwosc, wspolczynnik_mocy, moc_pozorna
             FROM pomiary
             ORDER BY czas_pomiaru DESC, id DESC
             LIMIT :limit'
        );
    }

    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    send_json($statement->fetchAll());
} catch (Throwable $exception) {
    send_json([
        'error' => 'Nie udało się pobrać rekordów pomiarowych.',
        'details' => $exception->getMessage(),
    ], 500);
}
