<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'username'  => fake()->unique()->userName(),
            'name'      => $name,
            'email'     => fake()->unique()->safeEmail(),
            'phone'     => fake()->phoneNumber(),
            'password'  => static::$password ??= Hash::make('password'),
            'role'      => fake()->randomElement(['admin', 'sales', 'tech_writer', 'tech_lead']),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
