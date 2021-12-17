<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/signin', [AuthController::class, 'signIn']);
    Route::post('/reset-password', [PasswordResetController::class, 'ForgotPassword']);
    Route::post('/reset-password/{token}', [PasswordResetController::class, 'ResetPassword']);
    Route::get('/reset-password/{token}/remove', [PasswordResetController::class, 'RemoveRequestPassword']);
    Route::group(['middleware' => 'auth'], function () {
        Route::post('/signout', [AuthController::class, 'signOut']);
        Route::get('/refresh', [AuthController::class, 'refreshToken']);
    });
});

Route::group(['prefix' => 'users', 'middleware' => 'auth'], function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/{id}/events', [UserController::class, 'getEvents']);
    Route::post('/me/avatar', [UserController::class, 'uploadAvatar']);
    Route::patch('/me', [UserController::class, 'updateMe']);
});
Route::apiResource('users', UserController::class);

Route::group(['prefix' => 'events'], function () {
    Route::get('/{id}/comments', [EventController::class, 'getComments']);
    Route::get('/{id}/notifications', [EventController::class, 'getNotifications']);
    Route::post('/{id}/poster', [EventController::class, 'uploadPoster']);
    Route::post('/{id}/subscribe', [EventController::class, 'subscribe']);
    Route::post('/{id}/comments', [EventController::class, 'createComment']);
    Route::post('/{id}/notifications', [EventController::class, 'createNotification']);
});
Route::apiResource('events', EventController::class);

Route::apiResource('comments', CommentController::class);
