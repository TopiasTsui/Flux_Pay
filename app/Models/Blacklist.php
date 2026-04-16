<?php

namespace App\Models;

use App\Models\BaseModel;

class Blacklist extends BaseModel
{
    
    protected $fillable = ['type', 'value', 'remark', 'status'];

    protected $casts = ['status' => 'integer'];
}
