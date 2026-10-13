<?php

use App\Library\Adapters\Inbound\Http\Controllers\AuthController;
use App\Library\Adapters\Inbound\Http\Controllers\BookController;
use App\Library\Adapters\Inbound\Http\Controllers\LoanController;
use App\Library\Adapters\Inbound\Http\Controllers\MemberController;
use App\Library\Adapters\Inbound\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// api-contract.md section 3.
Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('library.auth')->group(function () {
    Route::get('/categories', [BookController::class, 'categories']);
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show'])->whereUuid('id');
});

Route::middleware('library.auth:admin')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{id}', [BookController::class, 'update'])->whereUuid('id');
    Route::delete('/books/{id}', [BookController::class, 'destroy'])->whereUuid('id');
});

Route::middleware('library.auth:admin,librarian')->group(function () {
    Route::get('/members', [MemberController::class, 'index']);
    Route::get('/members/{id}', [MemberController::class, 'show'])->whereUuid('id');
    Route::post('/members', [MemberController::class, 'store']);

    // /loans/overdue is registered before /loans/{id} so it is not taken as an id.
    Route::get('/loans/overdue', [LoanController::class, 'overdue']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::get('/loans/{id}', [LoanController::class, 'show'])->whereUuid('id');
    Route::post('/loans/{loan_id}/items/{item_id}/return', [LoanController::class, 'return'])
        ->whereUuid(['loan_id', 'item_id']);
});
