<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends BaseModel
{
    protected $fillable = [
        'locale',
        'key',
        'value',
        'group',
        'updated_by',
    ];

    protected $casts = [
        'updated_by' => 'integer',
    ];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
