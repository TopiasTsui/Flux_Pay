<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WithdrawQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchantNo' => ['required', 'string'],
            'orderNo' => ['required', 'string', 'max:64'],
            'signature' => ['required', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'code' => 2001,
            'message' => 'Validation failed',
            'data' => $validator->errors(),
            'timestamp' => time(),
        ], 200));
    }
}
