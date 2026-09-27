<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'captain', 'name' => 'Kapitan', 'description' => 'Widok całej mapy i koordynacja załogi.', 'is_active' => true, 'sort_order' => 1],
            ['slug' => 'helmsman', 'name' => 'Sternik', 'description' => 'Wybiera kurs statku.', 'is_active' => true, 'sort_order' => 2],
            ['slug' => 'navigator', 'name' => 'Lokalizator', 'description' => 'W przyszłości wykrywa obiekty i zagrożenia.', 'is_active' => false, 'sort_order' => 3],
            ['slug' => 'lookout', 'name' => 'Bocianie gniazdo', 'description' => 'W przyszłości obserwuje najbliższe otoczenie.', 'is_active' => false, 'sort_order' => 4],
            ['slug' => 'gunner', 'name' => 'Działonowy', 'description' => 'W przyszłości obsługuje uzbrojenie.', 'is_active' => false, 'sort_order' => 5],
            ['slug' => 'speed-operator', 'name' => 'Operator prędkości', 'description' => 'W przyszłości steruje prędkością statku.', 'is_active' => false, 'sort_order' => 6],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
