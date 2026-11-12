<?php

use App\Http\Controllers\ItemLabelController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
Route::get('/items/{item}/label', ItemLabelController::class)->middleware('auth')->name('items.label');

Route::get('/inventory-photo/{kind}/{record}/{index?}', \App\Http\Controllers\InventoryPhotoController::class)->middleware('auth')->whereNumber('record')->whereNumber('index')->name('inventory.photo');

Route::get('/profile-image/{user}', \App\Http\Controllers\ProfileImageController::class)->middleware('auth')->whereNumber('user')->name('profile.image');
