<?php

use Illuminate\Http\Request;
use App\Http\Middleware\EnsureActiveAccount;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', EnsureActiveAccount::class]);
