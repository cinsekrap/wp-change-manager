<?php

namespace Database\Seeders;

use App\Models\CptType;
use Illuminate\Database\Seeder;

class CptTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['slug' => 'page', 'name' => 'Pages', 'description' => 'Standard website pages', 'sort_order' => 0],
            ['slug' => 'news', 'name' => 'News Articles', 'description' => 'News and blog posts', 'sort_order' => 1],
            ['slug' => 'services', 'name' => 'Services', 'description' => 'Service listing pages', 'sort_order' => 2],
            ['slug' => 'events', 'name' => 'Events', 'description' => 'Event listing pages', 'sort_order' => 3],
            ['slug' => 'vacancies', 'name' => 'Vacancies', 'description' => 'Job vacancy listings', 'sort_order' => 4],
        ];

        foreach ($types as $type) {
            CptType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
