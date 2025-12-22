<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            "uuid" => Str::uuid()->toString(),
            "text" => $this->faker->sentence(),
            "user_id" => User::first()->id,
        ];
    }
}
