<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UniformController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| JSON backend for the plas-mobile Flutter app. See API_CONTRACT.md in
| the plas-mobile project for the full contract these implement.
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/************	Public	************/
// Uploaded images, served under api/* so CORS headers are attached (see AssetController).
Route::get('/uploads/{path}', [AssetController::class, 'upload'])->where('path', '.*');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::get('/auth/check-availability', [AuthController::class, 'checkAvailability']);
Route::post('/auth/activate/{token}', [AuthController::class, 'activate']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

/************	Requires a valid Bearer token	************/
Route::middleware('api.auth')->group(function (): void {
	Route::post('/auth/logout', [AuthController::class, 'logout']);

	Route::get('/profile', [ProfileController::class, 'show']);
	Route::put('/profile', [ProfileController::class, 'update']);
	Route::get('/profile/ranks', [ProfileController::class, 'ranks']);

	Route::get('/uniforms', [UniformController::class, 'index']);
	Route::get('/uniforms/{id}/clothes', [UniformController::class, 'clothes']);

	Route::get('/cart', [CartController::class, 'show']);
	Route::post('/cart/add', [CartController::class, 'add']);
	Route::post('/cart/remove', [CartController::class, 'remove']);
	Route::post('/cart/checkout', [CartController::class, 'checkout']);

	Route::get('/orders', [OrderController::class, 'index']);
	Route::get('/orders/{id}/kew-ps8', [OrderController::class, 'kewPs8']);
	Route::post('/orders/email-details', [OrderController::class, 'emailDetails']);
	Route::delete('/orders', [OrderController::class, 'destroyAll']);

	Route::put('/account/email', [AccountController::class, 'updateEmail']);
	Route::put('/account/password', [AccountController::class, 'updatePassword']);
});
