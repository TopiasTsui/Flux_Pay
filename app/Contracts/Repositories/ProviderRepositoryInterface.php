<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Provider;

interface ProviderRepositoryInterface
{
    public function find(int $id): ?Provider;

    public function findOrFail(int $id): Provider;

    public function create(array $data): Provider;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    /**
     * Lock the provider row for update within a transaction.
     */
    public function lockForUpdate(int $id): ?Provider;
}
