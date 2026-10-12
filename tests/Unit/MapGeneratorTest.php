<?php

namespace Tests\Unit;

use App\Services\Game\CourseRules;
use App\Services\Game\MapGenerator;
use Tests\TestCase;

class MapGeneratorTest extends TestCase
{
    public function test_generated_map_has_dense_grouped_obstacles_and_a_route_to_the_target(): void
    {
        $generator = new MapGenerator(new CourseRules);
        $map = $generator->generate(12345);
        $obstacles = $map['obstacles'];
        $start = $map['start'];
        $blocked = array_fill_keys(array_map(
            fn (array $cell): string => $cell[0].':'.$cell[1],
            $obstacles,
        ), true);
        $hasAdjacentObstacles = false;

        foreach ($obstacles as [$x, $y]) {
            for ($offsetY = -1; $offsetY <= 1; $offsetY++) {
                for ($offsetX = -1; $offsetX <= 1; $offsetX++) {
                    if (($offsetX !== 0 || $offsetY !== 0)
                        && isset($blocked[($x + $offsetX).':'.($y + $offsetY)])) {
                        $hasAdjacentObstacles = true;
                    }
                }
            }
        }

        $this->assertGreaterThan(40, count($obstacles));
        $this->assertSame($map, $generator->generate(12345));
        foreach ($obstacles as [$x, $y]) {
            $this->assertTrue(
                abs($x - $start['x']) > 2 || abs($y - $start['y']) > 2,
                "Expected the 5x5 start area to be obstacle-free, found obstacle at {$x},{$y}.",
            );
        }
        $this->assertNotNull((new CourseRules)->shortestDistance(
            $map,
            $map['start']['x'],
            $map['start']['y'],
            'SE',
        ));
        $this->assertTrue($hasAdjacentObstacles);
    }
}
