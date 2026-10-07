<?php

namespace App\Services\Game;

use Random\Engine\Mt19937;
use Random\Randomizer;

class MapGenerator
{
    private const SIZE = 20;

    public function __construct(private readonly CourseRules $courseRules) {}

    public function generate(int $seed): array
    {
        $randomizer = new Randomizer(new Mt19937($seed));
        $start = ['x' => 4, 'y' => 9];
        $target = ['x' => 16, 'y' => 15];

        for ($attempt = 0; $attempt < 40; $attempt++) {
            $obstacles = [];

            for ($y = 0; $y < self::SIZE; $y++) {
                for ($x = 0; $x < self::SIZE; $x++) {
                    if (($x === $start['x'] && $y === $start['y']) || ($x === $target['x'] && $y === $target['y'])) {
                        continue;
                    }

                    if ($randomizer->getInt(0, 99) < 8) {
                        $obstacles[] = [$x, $y];
                    }
                }
            }

            $map = [
                'size' => self::SIZE,
                'start' => $start,
                'target' => $target,
                'obstacles' => $obstacles,
            ];

            if ($this->courseRules->shortestDistance($map, $start['x'], $start['y'], 'SE') !== null) {
                return $map;
            }
        }

        return [
            'size' => self::SIZE,
            'start' => $start,
            'target' => $target,
            'obstacles' => [],
        ];
    }

    private function hasRoute(array $start, array $target, array $blocked): bool
    {
        $queue = [[$start['x'], $start['y'], 'SE']];
        $visited = ['4:9:SE' => true];

        for ($cursor = 0; $cursor < count($queue); $cursor++) {
            [$x, $y, $heading] = $queue[$cursor];
            if ($x === $target['x'] && $y === $target['y']) {
                return true;
            }

            foreach ($this->courseRules->movementHeadings($heading) as $nextHeading) {
                [$offsetX, $offsetY] = $this->courseRules->offset($nextHeading);
                $nextX = $x + $offsetX;
                $nextY = $y + $offsetY;
                $key = $nextX.':'.$nextY.':'.$nextHeading;

                if ($nextX < 0 || $nextY < 0 || $nextX >= self::SIZE || $nextY >= self::SIZE
                    || isset($blocked[$nextX.':'.$nextY]) || isset($visited[$key])) {
                    continue;
                }

                $visited[$key] = true;
                $queue[] = [$nextX, $nextY, $nextHeading];
            }
        }

        return false;
    }
}
