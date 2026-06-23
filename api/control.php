<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/control_state.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        send_json(read_control_state());
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json(['error' => 'Metoda nieobsługiwana.'], 405);
    }

    $payload = json_decode((string) file_get_contents('php://input'), true);

    if (!is_array($payload) || !array_key_exists('power_enabled', $payload)) {
        send_json(['error' => 'Brak pola power_enabled.'], 400);
    }

    $state = write_control_state((bool) $payload['power_enabled']);

    send_json($state);
} catch (Throwable $exception) {
    send_json([
        'error' => 'Nie udało się zmienić stanu zasilania.',
        'details' => $exception->getMessage(),
    ], 500);
}
