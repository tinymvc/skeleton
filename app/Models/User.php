<?php

namespace App\Models;

use Spark\Database\Model;

class User extends Model
{
    public static string $table = 'users';

    protected array $guarded = [];

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
