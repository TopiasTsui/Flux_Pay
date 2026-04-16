<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ProviderRepositoryInterface;
use App\Models\Provider;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function find(int $id): ?Provider
    {
        return Provider::find($id);
    }

    public function findOrFail(int $id): Provider
    {
        return Provider::findOrFail($id);
    }

    public function create(array $data): Provider
    {
        return Provider::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Provider::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) Provider::where('id', $id)->delete();
    }

    public function lockForUpdate(int $id): ?Provider
    {
        return Provider::lockForUpdate()->find($id);
    }
}
