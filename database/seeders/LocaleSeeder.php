<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Locale;
use Illuminate\Database\Seeder;

class LocaleSeeder extends Seeder
{
    public function run(): void
    {
        $locales = [
            ['code' => 'en', 'name' => 'English', 'is_default' => true, 'is_active' => true, 'sort_order' => 1],
            ['code' => 'zh-CN', 'name' => '简体中文', 'is_default' => false, 'is_active' => true, 'sort_order' => 2],
            ['code' => 'zh-TW', 'name' => '繁體中文', 'is_default' => false, 'is_active' => true, 'sort_order' => 3],
        ];

        foreach ($locales as $row) {
            Locale::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
