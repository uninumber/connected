<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Chat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create([
                "nickname" => fake()->name(),
                "password" => Hash::make("Pass123!"),
            ]);
        }

        $robert = User::factory()->create([
            "nickname" => "Robert",
            "password" => Hash::make("Pass123!"),
        ]);

        $michaela = User::factory()->create([
            "nickname" => "Michaela",
            "password" => Hash::make("Pass123!"),
        ]);

        $richard = User::factory()->create([
            "nickname" => "Richard",
            "password" => Hash::make("Pass123!"),
        ]);

        $chat = Chat::factory()->create([
            "id" => $faker->uuid(),
            "last_message" => $faker->sentence(),
        ]);

        $secondChat = Chat::factory()->create([
            "id" => $faker->uuid(),
            "last_message" => $faker->sentence(),
        ]);

        DB::table("chat_user")->insert([
            ["chat_id" => $chat->id, "user_id" => $michaela->id],
            ["chat_id" => $chat->id, "user_id" => $robert->id],
            ["chat_id" => $secondChat->id, "user_id" => $michaela->id],
            ["chat_id" => $secondChat->id, "user_id" => $richard->id],
        ]);
    }
}
