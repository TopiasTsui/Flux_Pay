<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['payment_type_code' => 'BANK_TRANSFER', 'name' => 'Bank Transfer', 'sort_order' => 1],
            ['payment_type_code' => 'GCASH', 'name' => 'GCash', 'sort_order' => 2],
            ['payment_type_code' => 'GRABPAY', 'name' => 'GrabPay', 'sort_order' => 3],
            ['payment_type_code' => 'PAYMAYA', 'name' => 'PayMaya', 'sort_order' => 4],
            ['payment_type_code' => 'INSTAPAY', 'name' => 'InstaPay', 'sort_order' => 5],
            ['payment_type_code' => 'PESONET', 'name' => 'PESONet', 'sort_order' => 6],
        ];

        foreach ($types as $type) {
            PaymentType::updateOrCreate(
                ['payment_type_code' => $type['payment_type_code']],
                array_merge($type, ['status' => EntityStatus::ACTIVE->value]),
            );
        }
    }
}
