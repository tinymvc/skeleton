<?php

use App\Models\User;

return new class {
    public function up(): void
    {
        User::firstOrCreate(
            attributes: ['email' => 'admin@mail.com'],
            values: [
                'first_name' => 'John',
                'last_name' => ' Doe',
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