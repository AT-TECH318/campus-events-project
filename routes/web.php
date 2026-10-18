<?php

use App\Http\Controllers\Web\BrowserSessionController;
use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/events/{event}', [PageController::class, 'event'])->whereNumber('event')->name('events.show');
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/register', [PageController::class, 'register'])->name('register');
Route::get('/registrations', [PageController::class, 'registrations'])->name('registrations');
Route::get('/organizer/events', [PageController::class, 'organizerEvents'])->name('organizer.events');
Route::get('/organizer/events/create', [PageController::class, 'createEvent'])->name('organizer.events.create');
Route::get('/organizer/events/{event}/edit', [PageController::class, 'editEvent'])->whereNumber('event')->name('organizer.events.edit');
Route::get('/admin/categories', [PageController::class, 'adminCategories'])->name('admin.categories');
Route::get('/admin/users', [PageController::class, 'adminUsers'])->name('admin.users');

Route::post('/browser-session', [BrowserSessionController::class, 'store'])->name('browser-session.store');
Route::delete('/browser-session', [BrowserSessionController::class, 'destroy'])->name('browser-session.destroy');
