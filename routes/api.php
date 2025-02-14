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

Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/test', function (Request $request) {
    try {
        $request->validate([
            'jsonData' => 'required|string',
        ]);

        dump($request->jsonData);

        return response()->json(['response' => 'hello from backend']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
});

Route::post('/chat', [ChatbotController::class, 'chat']);

Route::post('/chat-auth', [ChatbotController::class, 'chatAuth'])->middleware('auth:sanctum');
