<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\BaseModel;

class Proxy extends BaseModel
{
    
    protected $fillable = ['name', 'host', 'port', 'username', 'password', 'protocol', 'status', 'priority', 'description'];

    protected $casts = ['status' => EntityStatus::class];
    protected $hidden = ['password'];

    public function getUrl(): string
    {
        $auth = $this->username ? "{$this->username}:{$this->password}@" : '';

        return "{$this->protocol}://{$auth}{$this->host}:{$this->port}";
    }
}
