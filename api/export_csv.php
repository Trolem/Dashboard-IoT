<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/demo_data.php';

function csv_filename(string $date): string
{
    $suffix = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : 'wszystkie-dni';

    return "pomiary-energy-lab-{$suffix}.csv";
}

function send_csv(array $rows, string $date): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . csv_filename($date) . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'id',
        'czas_pomiaru',
        'urzadzenie',
        'napiecie_V',
        'natezenie_A',
        'moc_W',
        'energia_kWh',
        'czestotliwosc_Hz',
        'wspolczynnik_mocy',
        'moc_pozorna_VA',
    ], ';', '"', '\\');

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['id'] ?? '',
            $row['czas_pomiaru'] ?? '',
            $row['urzadzenie'] ?? '',
            $row['napiecie'] ?? '',
            $row['natezenie'] ?? '',
            $row['moc'] ?? '',
            $row['energia'] ?? '',
            $row['czestotliwosc'] ?? '',
            $row['wspolczynnik_mocy'] ?? '',
            $row['moc_pozorna'] ?? '',
        ], ';', '"', '\\');
    }

    fclose($output);
    exit;
}

try {
    $date = $_GET['date'] ?? '';

    if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo 'Niepoprawny format daty.';
        exit;
    }

    if (USE_MOCK_DATA) {
        $rows = demo_all_rows();

        if ($date !== '') {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => $row['data'] === $date
            ));
        }

        send_csv($rows, $date);
    }

    $pdo = database();

    if ($date !== '') {
        $statement = $pdo->prepare(
            'SELECT id, czas_pomiaru, urzadzenie, napiecie, natezenie, moc, energia,
                    czestotliwosc, wspolczynnik_mocy, moc_pozorna
             FROM pomiary
             WHERE DATE(czas_pomiaru) = :date
             ORDER BY czas_pomiaru ASC, id ASC'
        );
        $statement->bindValue(':date', $date);
    } else {
        $statement = $pdo->prepare(
            'SELECT id, czas_pomiaru, urzadzenie, napiecie, natezenie, moc, energia,
                    czestotliwosc, wspolczynnik_mocy, moc_pozorna
             FROM pomiary
             ORDER BY czas_pomiaru ASC, id ASC'
        );
    }

    $statement->execute();

    send_csv($statement->fetchAll(), $date);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');

    echo 'Nie udalo sie wyeksportowac danych pomiarowych.';
}
