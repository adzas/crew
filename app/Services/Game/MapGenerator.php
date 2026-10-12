<?php

namespace App\Services\Game;

use Random\Engine\Mt19937;
use Random\Randomizer;

class MapGenerator
{
    private const SIZE = 20;

    private const START_CLEARANCE_RADIUS = 2;

    public function __construct(private readonly CourseRules $courseRules) {}

    public function generate(int $seed): array
    {
        $randomizer = new Randomizer(new Mt19937($seed));
        $start = ['x' => 4, 'y' => 9];
        $target = ['x' => 16, 'y' => 15];

        for ($attempt = 0; $attempt < 40; $attempt++) {
            $blocked = [];
            $islandCount = $randomizer->getInt(10, 14);

            for ($island = 0; $island < $islandCount; $island++) {
                $this->growIsland($randomizer, $blocked, $start, $target);
            }
            $obstacles = array_values($blocked);

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

    private function growIsland(Randomizer $randomizer, array &$blocked, array $start, array $target): void
    {
        $center = null;

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $x = $randomizer->getInt(1, self::SIZE - 2);
            $y = $randomizer->getInt(1, self::SIZE - 2);
            $key = $x.':'.$y;

            if (! isset($blocked[$key]) && ! $this->isProtectedCell($x, $y, $start, $target)) {
                $center = [$key, $x, $y];
                break;
            }
        }

        if ($center === null) {
            return;
        }

        [$key, $x, $y] = $center;
        $island = [$key => [$x, $y]];
        $blocked[$key] = [$x, $y];
        $islandSize = $randomizer->getInt(5, 10);

        while (count($island) < $islandSize) {
            $frontier = [];

            foreach ($island as [$islandX, $islandY]) {
                for ($offsetY = -1; $offsetY <= 1; $offsetY++) {
                    for ($offsetX = -1; $offsetX <= 1; $offsetX++) {
                        if ($offsetX === 0 && $offsetY === 0) {
                            continue;
                        }

                        $nextX = $islandX + $offsetX;
                        $nextY = $islandY + $offsetY;
                        $nextKey = $nextX.':'.$nextY;

                        if ($nextX < 1 || $nextY < 1 || $nextX >= self::SIZE - 1 || $nextY >= self::SIZE - 1
                            || isset($blocked[$nextKey]) || $this->isProtectedCell($nextX, $nextY, $start, $target)) {
                            continue;
                        }

                        $frontier[$nextKey] = [$nextX, $nextY];
                    }
                }
            }

            if ($frontier === []) {
                break;
            }

            $frontierKeys = array_keys($frontier);
            $nextKey = $frontierKeys[$randomizer->getInt(0, count($frontierKeys) - 1)];
            $island[$nextKey] = $frontier[$nextKey];
            $blocked[$nextKey] = $frontier[$nextKey];
        }
    }

    private function isProtectedCell(int $x, int $y, array $start, array $target): bool
    {
        return (abs($x - $start['x']) <= self::START_CLEARANCE_RADIUS
                && abs($y - $start['y']) <= self::START_CLEARANCE_RADIUS)
            || ($x === $target['x'] && $y === $target['y']);
    }
}
