<?php

use App\Library\Adapters\Inbound\Http\Controllers\AuthController;
use App\Library\Adapters\Inbound\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('library.auth:admin')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
});
