<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\NotificationController;
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
	Route::get('/profile/clothing-sizes', [ProfileController::class, 'clothingSizes']);
	Route::put('/profile/clothing-sizes', [ProfileController::class, 'updateClothingSizes']);

	Route::get('/uniforms', [UniformController::class, 'index']);
	Route::get('/uniforms/{id}/clothes', [UniformController::class, 'clothes']);

	Route::get('/cart', [CartController::class, 'show']);
	Route::post('/cart/add', [CartController::class, 'add']);
	Route::post('/cart/remove', [CartController::class, 'remove']);
	Route::post('/cart/load-from-order', [CartController::class, 'loadFromOrder']);
	Route::post('/cart/checkout', [CartController::class, 'checkout']);

	Route::get('/orders', [OrderController::class, 'index']);
	Route::get('/orders/{id}/kew-ps8', [OrderController::class, 'kewPs8']);
	Route::post('/orders/email-details', [OrderController::class, 'emailDetails']);
	Route::delete('/orders', [OrderController::class, 'destroyAll']);

	Route::get('/notifications', [NotificationController::class, 'index']);
	Route::post('/notifications/read', [NotificationController::class, 'markRead']);
	Route::post('/devices', [NotificationController::class, 'registerDevice']);
	Route::delete('/devices', [NotificationController::class, 'unregisterDevice']);

	Route::put('/account/email', [AccountController::class, 'updateEmail']);
	Route::put('/account/password', [AccountController::class, 'updatePassword']);
});

/************	Admin side of the Next.js UI	************/
Route::post('/admin/auth/login', [\App\Http\Controllers\Api\Admin\AuthController::class, 'login']);

Route::middleware('admin.api.auth')->prefix('admin')->group(function (): void {
	Route::post('/auth/logout', [\App\Http\Controllers\Api\Admin\AuthController::class, 'logout']);

	Route::get('/orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index']);
	Route::get('/orders/{id}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
	Route::put('/orders/{id}/status', [\App\Http\Controllers\Api\Admin\OrderController::class, 'updateStatus']);

	Route::get('/personal-inventory', [\App\Http\Controllers\Api\Admin\PersonalInventoryController::class, 'index']);
	Route::get('/personal-inventory/{sId}', [\App\Http\Controllers\Api\Admin\PersonalInventoryController::class, 'show']);

	Route::middleware('admin.api.superadmin')->group(function (): void {
		Route::get('/bases', [\App\Http\Controllers\Api\Admin\BaseController::class, 'index']);
		Route::get('/bases/{id}', [\App\Http\Controllers\Api\Admin\BaseController::class, 'show']);
		Route::post('/bases', [\App\Http\Controllers\Api\Admin\BaseController::class, 'store']);
		Route::put('/bases/{id}', [\App\Http\Controllers\Api\Admin\BaseController::class, 'update']);
		Route::delete('/bases/{id}', [\App\Http\Controllers\Api\Admin\BaseController::class, 'destroy']);

		Route::get('/announcements/audience-counts', [\App\Http\Controllers\Api\Admin\AnnouncementController::class, 'audienceCounts']);
		Route::post('/announcements/send', [\App\Http\Controllers\Api\Admin\AnnouncementController::class, 'send']);
	});
});
