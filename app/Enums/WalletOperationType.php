<?php

namespace App\Enums;

enum WalletOperationType: string
{
    case DEPOSIT_INCOME = 'deposit_income';
    case WITHDRAW_DEBIT = 'withdraw_debit';
    case COMMISSION_DEPOSIT = 'commission_deposit';
    case COMMISSION_WITHDRAW = 'commission_withdraw';
    case PROVIDER_COMMISSION_DEPOSIT = 'provider_commission_deposit';
    case PROVIDER_COMMISSION_WITHDRAW = 'provider_commission_withdraw';
    case FREEZE = 'freeze';
    case UNFREEZE = 'unfreeze';
    case MANUAL_CREDIT = 'manual_credit';
    case MANUAL_DEBIT = 'manual_debit';
    case REVERSAL_CREDIT = 'reversal_credit';
    case REVERSAL_DEBIT = 'reversal_debit';
    case PROVIDER_DEPOSIT_DEBIT = 'provider_deposit_debit';
    case PROVIDER_WITHDRAW_CREDIT = 'provider_withdraw_credit';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT_INCOME => 'Deposit Income',
            self::WITHDRAW_DEBIT => 'Withdraw Debit',
            self::COMMISSION_DEPOSIT => 'Commission (Deposit)',
            self::COMMISSION_WITHDRAW => 'Commission (Withdraw)',
            self::PROVIDER_COMMISSION_DEPOSIT => 'Provider Commission (Deposit)',
            self::PROVIDER_COMMISSION_WITHDRAW => 'Provider Commission (Withdraw)',
            self::FREEZE => 'Freeze',
            self::UNFREEZE => 'Unfreeze',
            self::MANUAL_CREDIT => 'Manual Credit',
            self::MANUAL_DEBIT => 'Manual Debit',
            self::REVERSAL_CREDIT => 'Reversal Credit',
            self::REVERSAL_DEBIT => 'Reversal Debit',
            self::PROVIDER_DEPOSIT_DEBIT => 'Provider Deposit Debit',
            self::PROVIDER_WITHDRAW_CREDIT => 'Provider Withdraw Credit',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }

    public function isCredit(): bool
    {
        return in_array($this, [
            self::DEPOSIT_INCOME,
            self::COMMISSION_DEPOSIT,
            self::COMMISSION_WITHDRAW,
            self::PROVIDER_COMMISSION_DEPOSIT,
            self::PROVIDER_COMMISSION_WITHDRAW,
            self::UNFREEZE,
            self::MANUAL_CREDIT,
            self::REVERSAL_CREDIT,
            self::PROVIDER_WITHDRAW_CREDIT,
        ]);
    }
}
