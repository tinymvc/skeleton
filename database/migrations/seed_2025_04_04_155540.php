<?php

use App\Models\User;

return new class {
    public function up(): void
    {
        User::firstOrCreate(
            attributes: ['email' => 'admin@mail.com'],
            values: [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function down(): void
    {
        User::truncate();
    }
};