<?php

declare(strict_types=1);

function control_state_path(): string
{
    return __DIR__ . '/control-state.json';
}

function default_control_state(): array
{
    return [
        'power_enabled' => true,
        'relay_state' => 'closed',
        'label' => 'Zasilanie włączone',
        'updated_at' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        'updated_by' => 'system',
    ];
}

function read_control_state(): array
{
    $path = control_state_path();

    if (!is_file($path)) {
        return default_control_state();
    }

    $content = file_get_contents($path);
    $state = json_decode((string) $content, true);

    if (!is_array($state)) {
        return default_control_state();
    }

    return array_merge(default_control_state(), $state);
}

function write_control_state(bool $powerEnabled, string $updatedBy = 'dashboard'): array
{
    $state = [
        'power_enabled' => $powerEnabled,
        'relay_state' => $powerEnabled ? 'closed' : 'open',
        'label' => $powerEnabled ? 'Zasilanie włączone' : 'Zasilanie odłączone',
        'updated_at' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        'updated_by' => $updatedBy,
    ];

    file_put_contents(
        control_state_path(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    return $state;
}

function apply_control_state_to_measurement(array $measurement): array
{
    $state = read_control_state();
    $measurement['control'] = $state;

    if (!$state['power_enabled']) {
        $measurement['natezenie'] = 0;
        $measurement['moc'] = 0;
        $measurement['moc_pozorna'] = 0;
        $measurement['wspolczynnik_mocy'] = 0;
    }

    return $measurement;
}
