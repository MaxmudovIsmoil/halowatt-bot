<?php

use App\Http\Controllers\AiSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SourceController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/run-now', [DashboardController::class, 'runNow'])->name('run.now');
    Route::post('/generate-now', [DashboardController::class, 'generateNow'])->name('generate.now');

    // Kanallar
    Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    Route::put('/channels/{channel}', [ChannelController::class, 'update'])->name('channels.update');
    Route::put('/channels/{channel}/settings', [ChannelController::class, 'updateSettings'])->name('channels.settings.update');
    Route::post('/channels/{channel}/run', [ChannelController::class, 'runNow'])->name('channels.run');
    Route::post('/channels/{channel}/toggle', [ChannelController::class, 'toggle'])->name('channels.toggle');
    Route::delete('/channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');

    // Kanal manbalari (RSS) — kanal ichida boshqariladi
    Route::post('/channels/{channel}/sources', [SourceController::class, 'store'])->name('channels.sources.store');
    Route::put('/channels/{channel}/sources/{source}', [SourceController::class, 'update'])->name('channels.sources.update');
    Route::post('/channels/{channel}/sources/{source}/toggle', [SourceController::class, 'toggle'])->name('channels.sources.toggle');
    Route::delete('/channels/{channel}/sources/{source}', [SourceController::class, 'destroy'])->name('channels.sources.destroy');

    // Postlar (tasdiq / tarix)
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::post('/posts/{post}/approve', [PostController::class, 'approve'])->name('posts.approve');
    Route::post('/posts/{post}/reject', [PostController::class, 'reject'])->name('posts.reject');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Global sozlamalar (fallback)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // AI provayderlar (Claude / ChatGPT / Grok / Gemini) — API kalit va model
    Route::get('/settings/ai', [AiSettingController::class, 'index'])->name('settings.ai');
    Route::post('/settings/ai/{key}', [AiSettingController::class, 'update'])->name('settings.ai.update');
    Route::post('/settings/ai/{key}/check-balance', [AiSettingController::class, 'checkBalance'])->name('settings.ai.check-balance');
});
