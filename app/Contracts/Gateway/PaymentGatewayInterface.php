<?php

namespace App\Contracts\Gateway;

use App\DTOs\Gateway\BalanceQueryResult;
use App\DTOs\Gateway\DepositApplyResult;
use App\DTOs\Gateway\DepositCallbackResult;
use App\DTOs\Gateway\WithdrawApplyResult;
use App\DTOs\Gateway\WithdrawCallbackResult;

interface PaymentGatewayInterface
{
    public function depositApply(array $data): DepositApplyResult;

    public function depositQuery(array $data): DepositCallbackResult;

    public function depositCallback(array $data, array $options = []): DepositCallbackResult;

    public function withdrawApply(array $data): WithdrawApplyResult;

    public function withdrawQuery(array $data): WithdrawCallbackResult;

    public function withdrawCallback(array $data, array $options = []): WithdrawCallbackResult;

    public function balanceQuery(): BalanceQueryResult;

    public function supportsDeposit(): bool;

    public function supportsWithdraw(): bool;
}
