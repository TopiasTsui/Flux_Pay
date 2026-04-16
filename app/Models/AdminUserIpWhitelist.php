<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminUserIpWhitelist extends Model
{
    protected $fillable = ['admin_user_id', 'ip_address', 'remark', 'status'];

    protected $casts = ['status' => 'integer'];
}
