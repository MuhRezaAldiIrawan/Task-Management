<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::apiResource('tasks', TaskController::class);

        Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
        Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
        Route::put('comments/{comment}', [CommentController::class, 'update']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    });
});
