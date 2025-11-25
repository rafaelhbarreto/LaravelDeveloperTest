<?php

use App\Http\Controllers\CongressMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Congress Members Routes
Route::prefix('congress/members')->name('congress.members.')->group(function () {
    Route::get('/', [CongressMemberController::class, 'index'])->name('index');
    Route::get('/{bioguideId}', [CongressMemberController::class, 'show'])->name('show');
});
