<?php

use App\Http\Controllers\DopplerController;
use App\Http\Controllers\LobbyController;
use Illuminate\Support\Facades\Route;

Route::middleware('player.not-banned')->group(function () {
    Route::get('/', [LobbyController::class, 'index'])->name('lobby');
    Route::post('/lobby/join', [LobbyController::class, 'join'])->middleware('throttle:10,1')->name('lobby.join');
    Route::post('/lobby/roles/{role}', [LobbyController::class, 'claimRole'])->middleware('throttle:30,1')->name('lobby.roles.claim');
    Route::post('/lobby/actions/{action}', [LobbyController::class, 'logAction'])->middleware('throttle:60,1')->name('lobby.actions');
    Route::post('/lobby/doppler/stop', [DopplerController::class, 'stop'])->middleware('throttle:10,1')->name('lobby.doppler.stop');
    Route::post('/lobby/doppler', [DopplerController::class, 'switchRole'])->middleware('throttle:10,1')->name('lobby.doppler.switch');
});
