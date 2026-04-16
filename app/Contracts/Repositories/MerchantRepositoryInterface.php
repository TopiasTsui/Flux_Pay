<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Merchant;

interface MerchantRepositoryInterface
{
    public function find(int $id): ?Merchant;

    public function findOrFail(int $id): Merchant;

    public function findByCode(string $code): ?Merchant;

    public function create(array $data): Merchant;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    /**
     * Lock the merchant row for update within a transaction.
     */
    public function lockForUpdate(int $id): ?Merchant;
}
