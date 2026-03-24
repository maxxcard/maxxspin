<?php

declare(strict_types=1);

namespace App\Support;

final class SegmentForm
{
    /**
     * @param array<int, array{name?: mixed, color?: mixed, chance?: mixed}> $segments
     * @return array<int, array{name:string, color:string, chance:string}>
     */
    public static function normalize(array $segments): array
    {
        $normalized = [];

        foreach ($segments as $segment) {
            $normalized[] = [
                'name' => trim((string) ($segment['name'] ?? '')),
                'color' => trim((string) ($segment['color'] ?? '#94a3b8')),
                'chance' => (string) ($segment['chance'] ?? '0'),
            ];
        }

        return $normalized === [] ? [['name' => '', 'color' => '#94a3b8', 'chance' => '0']] : $normalized;
    }

    /**
     * @param array<string, mixed> $parsedBody
     * @return array<int, array{name:string, color:string, chance:string}>
     */
    public static function fromRequest(array $parsedBody): array
    {
        $names = array_values((array) ($parsedBody['segment_name'] ?? []));
        $colors = array_values((array) ($parsedBody['segment_color'] ?? []));
        $chances = array_values((array) ($parsedBody['segment_chance'] ?? []));
        $rowCount = max(count($names), count($colors), count($chances));
        $segments = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $segments[] = [
                'name' => $names[$index] ?? '',
                'color' => $colors[$index] ?? '#94a3b8',
                'chance' => $chances[$index] ?? '0',
            ];
        }

        return self::normalize($segments);
    }

    /**
     * @param array<int, array{name:string, color:string, chance:string}> $rows
     * @return array{errors: array<int, string>, total: int}
     */
    public static function validate(array $rows): array
    {
        $errors = [];
        $chanceTotal = 0;

        if ($rows === []) {
            $errors[] = 'Add at least one row before saving.';
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            if ($row['name'] === '') {
                $errors[] = "Row {$rowNumber}: name is required.";
            }

            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $row['color'])) {
                $errors[] = "Row {$rowNumber}: color must be a valid hex color.";
            }

            if ($row['chance'] === '' || filter_var($row['chance'], FILTER_VALIDATE_INT) === false) {
                $errors[] = "Row {$rowNumber}: chance must be an integer.";
                continue;
            }

            $chance = (int) $row['chance'];

            if ($chance < 0 || $chance > 100) {
                $errors[] = "Row {$rowNumber}: chance must be between 0 and 100.";
            }

            $chanceTotal += $chance;
        }

        if ($errors === [] && $chanceTotal !== 100) {
            $errors[] = "The sum of all chance fields must be 100. Current total: {$chanceTotal}.";
        }

        return ['errors' => $errors, 'total' => $chanceTotal];
    }
}
