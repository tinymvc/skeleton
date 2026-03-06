<?php

namespace App\Models;

use Spark\Database\Model;

class User extends Model
{
    protected array $hidden = [
        'password',
        'remember_token',
    ];

    protected array $casts = [
        'password' => 'hashed',
    ];

    public function getCreatedAtAttribute($value): string
    {
        return carbon($value)->toFormattedDateString();
    }
}
