<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MerchantRepositoryInterface;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;

class MerchantRepository implements MerchantRepositoryInterface
{
    public function find(int $id): ?Merchant
    {
        return Merchant::find($id);
    }

    public function findOrFail(int $id): Merchant
    {
        return Merchant::findOrFail($id);
    }

    public function findByCode(string $code): ?Merchant
    {
        return Merchant::where('code', $code)->first();
    }

    public function create(array $data): Merchant
    {
        return Merchant::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Merchant::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) Merchant::where('id', $id)->delete();
    }

    public function lockForUpdate(int $id): ?Merchant
    {
        return Merchant::lockForUpdate()->find($id);
    }
}
