<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

// If not authenticated, a 401 Unauthorized response will be returned by the middleware
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/chat', [ChatbotController::class, 'chat']);

Route::post('/chat-auth', [ChatbotController::class, 'chatAuth'])->middleware('auth:sanctum');
