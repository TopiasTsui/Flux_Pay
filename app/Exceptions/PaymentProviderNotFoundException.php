<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentProviderNotFoundException extends RuntimeException
{
    public function __construct(string $vendorId)
    {
        parent::__construct("Payment provider not found: {$vendorId}");
    }
}
