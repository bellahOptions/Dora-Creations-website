<?php

use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/{gateway}', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.payment');
