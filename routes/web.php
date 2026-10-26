<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('contracts.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('contracts.index'))->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Contracts
    Route::resource('contracts', ContractController::class)->except(['destroy']);
    Route::post('contracts/{contract}/activate', [ContractController::class, 'activate'])->name('contracts.activate');

    // Deliveries
    Route::get('contracts/{contract}/deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('contracts/{contract}/deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');

    // Penalties (calculate + chief review)
    Route::post('contracts/{contract}/penalties', [PenaltyController::class, 'store'])->name('penalties.store');
    Route::post('penalties/{penalty}/review', [PenaltyController::class, 'review'])->name('penalties.review');

    // Accounting
    Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');
    Route::get('accounting/{contract}', [AccountingController::class, 'show'])->name('accounting.show');

    // Payments
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');

    // Users (chief only)
    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/auth.php';
