<?php

use App\Http\Controllers\ItemLabelController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
Route::get('/items/{item}/label', ItemLabelController::class)->middleware('auth')->name('items.label');
