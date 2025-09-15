<?php

use App\Models\User;

return new class {
    public function up(): void
    {
        User::create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => passcode('password'),
        ]);
    }

    public function down(): void
    {
        User::delete(['id' => [1]]);
    }
};