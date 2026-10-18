<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventRegistrationController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth.optional')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show'])->whereNumber('event');
});
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show'])->whereNumber('category');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users/me', [UserController::class, 'me']);
    Route::get('/users/me/registrations', [EventRegistrationController::class, 'index']);

    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'replace'])->whereNumber('event');
    Route::patch('/events/{event}', [EventController::class, 'update'])->whereNumber('event');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->whereNumber('event');
    Route::post('/events/{event}/registrations', [EventRegistrationController::class, 'store'])->whereNumber('event');
    Route::delete('/events/{event}/registrations', [EventRegistrationController::class, 'destroy'])->whereNumber('event');

    Route::middleware('role:administrator')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->whereNumber('user');

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('category');
    });
});
