<?php

declare(strict_types=1);

function demo_metrics(): array
{
    return [
        'napiecie' => [
            'label' => 'Napięcie',
            'unit' => 'V',
            'digits' => 1,
            'color' => '#0f8b8d',
        ],
        'natezenie' => [
            'label' => 'Natężenie',
            'unit' => 'A',
            'digits' => 2,
            'color' => '#7c3aed',
        ],
        'moc' => [
            'label' => 'Moc czynna',
            'unit' => 'W',
            'digits' => 1,
            'color' => '#dc2626',
        ],
        'energia' => [
            'label' => 'Energia',
            'unit' => 'kWh',
            'digits' => 3,
            'color' => '#b45309',
        ],
        'czestotliwosc' => [
            'label' => 'Częstotliwość',
            'unit' => 'Hz',
            'digits' => 2,
            'color' => '#2563eb',
        ],
        'wspolczynnik_mocy' => [
            'label' => 'Współczynnik mocy',
            'unit' => '-',
            'digits' => 2,
            'color' => '#15803d',
        ],
        'moc_pozorna' => [
            'label' => 'Moc pozorna',
            'unit' => 'VA',
            'digits' => 1,
            'color' => '#475569',
        ],
    ];
}

function demo_scenarios(): array
{
    return [
        '2026-06-10' => [
            'device' => 'Czajnik elektryczny',
            'description' => 'Krótki pomiar urządzenia o dużej mocy podczas gotowania wody.',
            'start' => '08:00',
            'minutes' => 18,
            'step' => 2,
            'base_power' => 1850,
            'profile' => 'kettle',
            'power_factor' => 0.98,
        ],
        '2026-06-11' => [
            'device' => 'Telewizor',
            'description' => 'Dwunastogodzinny pomiar stałego poboru energii przez telewizor.',
            'start' => '10:00',
            'minutes' => 720,
            'step' => 30,
            'base_power' => 82,
            'profile' => 'tv',
            'power_factor' => 0.72,
        ],
        '2026-06-12' => [
            'device' => 'Stanowisko komputerowe',
            'description' => 'Pomiar pracy laptopa, monitora i akcesoriów w trakcie dnia.',
            'start' => '09:00',
            'minutes' => 480,
            'step' => 20,
            'base_power' => 135,
            'profile' => 'computer',
            'power_factor' => 0.84,
        ],
        '2026-06-13' => [
            'device' => 'Pralka',
            'description' => 'Cykl prania z widocznymi skokami mocy podczas grzania wody.',
            'start' => '17:00',
            'minutes' => 150,
            'step' => 10,
            'base_power' => 420,
            'profile' => 'washing_machine',
            'power_factor' => 0.82,
        ],
        '2026-06-14' => [
            'device' => 'Lodówka',
            'description' => 'Całodobowy pomiar lodówki z cykliczną pracą sprężarki.',
            'start' => '00:00',
            'minutes' => 1410,
            'step' => 30,
            'base_power' => 92,
            'profile' => 'fridge',
            'power_factor' => 0.68,
        ],
        '2026-06-15' => [
            'device' => 'Mikrofalówka',
            'description' => 'Krótki pomiar impulsowego poboru mocy podczas podgrzewania posiłku.',
            'start' => '19:10',
            'minutes' => 24,
            'step' => 2,
            'base_power' => 1150,
            'profile' => 'microwave',
            'power_factor' => 0.87,
        ],
    ];
}

function demo_profile_power(string $profile, float $basePower, int $index, int $count): float
{
    $progress = $count > 1 ? $index / ($count - 1) : 0;

    return match ($profile) {
        'kettle' => $progress < 0.78 ? $basePower + sin($index * 1.1) * 42 : 32,
        'tv' => $basePower + sin($index / 2) * 6 + ($index % 9 === 0 ? 4 : 0),
        'computer' => $basePower + sin($index / 3) * 24 + ($index % 6 === 0 ? 38 : 0),
        'washing_machine' => $progress < 0.16 ? 115 :
            ($progress < 0.43 ? 1650 + sin($index) * 110 :
            ($progress < 0.77 ? 350 + sin($index / 2) * 120 : 82)),
        'fridge' => $index % 4 === 0 || $index % 4 === 1
            ? $basePower + sin($index) * 12
            : 8 + sin($index) * 2,
        'microwave' => $progress < 0.12 ? 18 :
            ($progress < 0.82 ? $basePower + sin($index * 1.7) * 90 : 12),
        default => $basePower,
    };
}

function demo_rows_for_day(string $date, array $scenario): array
{
    $rows = [];
    $count = (int) floor($scenario['minutes'] / $scenario['step']) + 1;
    $energy = 0.0;
    $start = new DateTimeImmutable("{$date} {$scenario['start']}:00");

    for ($i = 0; $i < $count; $i++) {
        $time = $start->modify('+' . ($i * $scenario['step']) . ' minutes');
        $power = max(0, demo_profile_power($scenario['profile'], $scenario['base_power'], $i, $count));
        $voltage = 230 + sin($i / 4) * 1.3 + cos($i / 7) * 0.4;
        $frequency = 50 + sin($i / 6) * 0.035;
        $powerFactor = min(
            0.99,
            max(0.55, $scenario['power_factor'] + sin($i / 5) * 0.035)
        );
        $apparentPower = $powerFactor > 0 ? $power / $powerFactor : $power;
        $current = $voltage > 0 ? $apparentPower / $voltage : 0;

        if ($i > 0) {
            $energy += ($power / 1000) * ($scenario['step'] / 60);
        }

        $rows[] = [
            'czas_pomiaru' => $time->format('Y-m-d H:i:s'),
            'data' => $time->format('Y-m-d'),
            'godzina' => $time->format('H:i'),
            'urzadzenie' => $scenario['device'],
            'opis' => $scenario['description'],
            'napiecie' => round($voltage, 1),
            'natezenie' => round($current, 2),
            'moc' => round($power, 1),
            'energia' => round($energy, 3),
            'czestotliwosc' => round($frequency, 2),
            'wspolczynnik_mocy' => round($powerFactor, 2),
            'moc_pozorna' => round($apparentPower, 1),
        ];
    }

    return $rows;
}

function demo_all_rows(): array
{
    $rows = [];
    $id = 1;

    foreach (demo_scenarios() as $date => $scenario) {
        foreach (demo_rows_for_day($date, $scenario) as $row) {
            $row['id'] = $id++;
            $rows[] = $row;
        }
    }

    return $rows;
}

function demo_days(): array
{
    $days = [];

    foreach (demo_scenarios() as $date => $scenario) {
        $rows = demo_rows_for_day($date, $scenario);
        $last = end($rows);
        $maxPower = max(array_column($rows, 'moc'));

        $days[] = [
            'date' => $date,
            'device' => $scenario['device'],
            'description' => $scenario['description'],
            'samples' => count($rows),
            'energy' => $last['energia'],
            'max_power' => round($maxPower, 1),
        ];
    }

    return $days;
}

function demo_summary(): array
{
    $rows = demo_all_rows();
    $days = demo_days();
    $energies = array_column($days, 'energy');
    $powers = array_column($rows, 'moc');

    return [
        'measurement_count' => count($rows),
        'device_count' => count($days),
        'date_from' => min(array_column($days, 'date')),
        'date_to' => max(array_column($days, 'date')),
        'total_energy' => round(array_sum($energies), 3),
        'max_power' => round(max($powers), 1),
        'avg_power_factor' => round(
            array_sum(array_column($rows, 'wspolczynnik_mocy')) / max(1, count($rows)),
            2
        ),
        'days' => $days,
    ];
}
