<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['bank_code' => 'BDO', 'name' => 'BDO Unibank'],
            ['bank_code' => 'BPI', 'name' => 'Bank of the Philippine Islands'],
            ['bank_code' => 'MBTC', 'name' => 'Metrobank'],
            ['bank_code' => 'LBP', 'name' => 'Landbank of the Philippines'],
            ['bank_code' => 'PNB', 'name' => 'Philippine National Bank'],
            ['bank_code' => 'UBP', 'name' => 'UnionBank of the Philippines'],
            ['bank_code' => 'RCBC', 'name' => 'Rizal Commercial Banking Corporation'],
            ['bank_code' => 'SBC', 'name' => 'Security Bank Corporation'],
            ['bank_code' => 'CBC', 'name' => 'China Banking Corporation'],
            ['bank_code' => 'EWB', 'name' => 'EastWest Banking Corporation'],
        ];

        foreach ($banks as $index => $bank) {
            Bank::updateOrCreate(
                ['bank_code' => $bank['bank_code']],
                array_merge($bank, [
                    'status' => EntityStatus::ACTIVE->value,
                    'sort_order' => $index + 1,
                ]),
            );
        }
    }
}
