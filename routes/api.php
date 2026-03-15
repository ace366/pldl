<?php

use App\Http\Controllers\Api\LineWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/line/webhook', [LineWebhookController::class, 'handle'])
    ->middleware('throttle:line-webhook')
    ->name('api.line.webhook');
