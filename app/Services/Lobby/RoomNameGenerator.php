<?php

namespace App\Services\Lobby;

use Illuminate\Support\Arr;

final class RoomNameGenerator
{
    public const NAMES = [
        'Borsuk',
        'Bocian',
        'Dzięcioł',
        'Jaskółka',
        'Jeleń',
        'Kormoran',
        'Kuna',
        'Łabędź',
        'Lis',
        'Łoś',
        'Mewa',
        'Orzeł',
        'Ryś',
        'Sarna',
        'Sokół',
        'Sowa',
        'Wilk',
        'Wydra',
        'Żubr',
        'Żuraw',
    ];

    public function generate(): string
    {
        return Arr::random(self::NAMES);
    }
}
