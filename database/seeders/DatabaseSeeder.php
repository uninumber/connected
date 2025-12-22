<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Chat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Enums\ChatTypeEnum;
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

        $user = User::factory()->create([
            "nickname" => "Mink",
            "password" => Hash::make("Pass123!"),
        ]);

        $anotherUser = User::factory()->create([
            "nickname" => "Richard",
            "password" => Hash::make("Pass123!"),
        ]);

        $chat = Chat::factory()->create([
            "id" => $faker->uuid(),
            "type" => $faker->randomElement(ChatTypeEnum::cases()),
            "last_message" => $faker->sentence(),
        ]);

        DB::table("chat_users")->insert([
            ["chat_id" => $chat->id, "user_id" => $user->id],
            ["chat_id" => $chat->id, "user_id" => $anotherUser->id],
        ]);
    }
}
