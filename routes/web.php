<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RedirectIfInstalled;
use App\Livewire\Installer\Installer;

Route::get('/', function () {
    return view('welcome');
});

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/pos/orders/{order}/print', [PosController::class, 'printReceipt'])->name('pos.print-receipt');
    Route::get('/pos/sessions/{session}/print-z-report', [PosController::class, 'printZReport'])->name('pos.print-z-report');
});

Route::group(['prefix' => 'install', 'as' => 'installer.', 'middleware' => ['web', RedirectIfInstalled::class]], function () {
    Route::get('/', Installer::class)->name('welcome');
});



Route::view('/pos/display', 'pos.customer-display')->name('pos.customer-display')->middleware('web');

