<?php

use App\Models\User;

return new class {
    public function up(): void
    {
        User::firstOrCreate(
            attributes: ['email' => 'admin@mail.com'],
            values: [
                'name' => 'John Doe',
                'username' => 'admin',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function down(): void
    {
        User::delete(['email' => 'admin@mail.com']);
    }
};