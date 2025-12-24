<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Real-time broadcasting auth
Broadcast::routes(["middleware" => ["auth:sanctum"]]);

// Public Auth Routes
Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);

// Protected Routes
Route::middleware("auth:sanctum")->group(function () {
    // Auth & User Profile
    Route::post("/logout", [AuthController::class, "logout"]);
    Route::get("/user", [AuthController::class, "user"]);

    // Search (Unified search for chats and users)
    Route::get("/search", [ChatController::class, "search"]);

    // Chats
    Route::get("/users/chats", [ChatController::class, "index"]);
    Route::post("/users/chats", [ChatController::class, "store"]);
    Route::get("/chats/{chat}", [ChatController::class, "show"]);

    // Messages
    Route::get("/chats/{chat}/messages", [
        ChatController::class,
        "listMessages",
    ]);
    Route::post("/messages", [ChatController::class, "sendMessage"]);
});
