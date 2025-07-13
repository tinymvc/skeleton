<?php

use App\Models\User;

return new class {
    public function up(): void
    {
        User::insert([
            [
                'id' => 1,
                'name' => 'Admin',
                'email' => 'admin@mail.com',
                'password' => hashing()->hashPassword('password'),
            ]
        ]);
    }

    public function down(): void
    {
        User::delete(['id' => [1]]);
    }
};