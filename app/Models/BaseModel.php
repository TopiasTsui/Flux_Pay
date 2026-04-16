<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

abstract class BaseModel extends Model
{
    use AsSource, Filterable;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Override Orchid's AsSource::getContent to handle BackedEnum values.
     */
    public function getContent(string $name): mixed
    {
        $value = data_get($this, $name);

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
