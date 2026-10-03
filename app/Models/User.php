<?php

namespace App\Models;

use Spark\Database\Model;

class User extends Model
{
    protected array $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    protected array $hidden = [
        'password',
        'remember_token',
    ];

    protected array $casts = [
        'password' => 'hashed',
    ];
}
