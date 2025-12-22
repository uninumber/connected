<?php

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("messages", function (Blueprint $blueprint) {
            $blueprint->uuid("id")->primary();

            $blueprint
                ->foreignIdFor(User::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $blueprint
                ->foreignIdFor(Chat::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $blueprint->text("text");
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("messages");
    }
};
