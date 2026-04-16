<?php

namespace App\Exceptions;

use RuntimeException;

class SignatureVerificationException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Signature verification failed');
    }
}
