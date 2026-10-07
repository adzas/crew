<?php

namespace App\Services\Game;

class CourseRules
{
    private const HEADINGS = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];

    private const VECTORS = [
        'N' => [0, -1],
        'NE' => [1, -1],
        'E' => [1, 0],
        'SE' => [1, 1],
        'S' => [0, 1],
        'SW' => [-1, 1],
        'W' => [-1, 0],
        'NW' => [-1, -1],
    ];

    public function turnOptions(string $heading): array
    {
        $index = array_search($heading, self::HEADINGS, true);
        if ($index === false) {
            return [];
        }

        $count = count(self::HEADINGS);

        return [
            self::HEADINGS[($index + $count - 1) % $count],
            self::HEADINGS[($index + 1) % $count],
        ];
    }

    public function movementHeadings(string $heading): array
    {
        return [$heading, ...$this->turnOptions($heading)];
    }

    public function offset(string $heading): array
    {
        return self::VECTORS[$heading] ?? [0, 0];
    }

    public function isTurnAllowed(string $current, string $requested): bool
    {
        return in_array($requested, $this->turnOptions($current), true);
    }

    public function shortestDistance(array $map, int $x, int $y, string $heading): ?int
    {
        $target = $map['target'];
        if ($x === $target['x'] && $y === $target['y']) {
            return 0;
        }

        $blocked = array_fill_keys(array_map(
            fn (array $cell): string => $cell[0].':'.$cell[1],
            $map['obstacles'],
        ), true);
        $queue = [[$x, $y, $heading, 0]];
        $visited = [$x.':'.$y.':'.$heading => true];

        for ($cursor = 0; $cursor < count($queue); $cursor++) {
            [$currentX, $currentY, $currentHeading, $distance] = $queue[$cursor];

            foreach ($this->movementHeadings($currentHeading) as $nextHeading) {
                [$offsetX, $offsetY] = $this->offset($nextHeading);
                $nextX = $currentX + $offsetX;
                $nextY = $currentY + $offsetY;
                $key = $nextX.':'.$nextY.':'.$nextHeading;

                if ($nextX < 0 || $nextY < 0 || $nextX >= $map['size'] || $nextY >= $map['size']
                    || isset($blocked[$nextX.':'.$nextY]) || isset($visited[$key])) {
                    continue;
                }

                if ($nextX === $target['x'] && $nextY === $target['y']) {
                    return $distance + 1;
                }

                $visited[$key] = true;
                $queue[] = [$nextX, $nextY, $nextHeading, $distance + 1];
            }
        }

        return null;
    }
}
