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
Route::get('/admin/auth/check-email', [\App\Http\Controllers\Api\Admin\AuthController::class, 'checkEmail']);
Route::post('/admin/auth/forgot-password', [\App\Http\Controllers\Api\Admin\AuthController::class, 'forgotPassword']);

Route::middleware('admin.api.auth')->prefix('admin')->group(function (): void {
	Route::post('/auth/logout', [\App\Http\Controllers\Api\Admin\AuthController::class, 'logout']);
	Route::get('/auth/me', [\App\Http\Controllers\Api\Admin\AuthController::class, 'me']);
	Route::put('/auth/password', [\App\Http\Controllers\Api\Admin\AuthController::class, 'updatePassword']);

	Route::get('/notifications', [\App\Http\Controllers\AdminNotificationController::class, 'index']);
	Route::post('/notifications/read', [\App\Http\Controllers\AdminNotificationController::class, 'markRead']);

	Route::get('/orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index']);
	Route::get('/orders/{id}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
	Route::get('/orders/{id}/kew-ps8', [\App\Http\Controllers\Api\Admin\OrderController::class, 'kewPs8']);
	Route::put('/orders/{id}/status', [\App\Http\Controllers\Api\Admin\OrderController::class, 'updateStatus']);

	Route::get('/personal-inventory', [\App\Http\Controllers\Api\Admin\PersonalInventoryController::class, 'index']);
	Route::get('/personal-inventory/{sId}', [\App\Http\Controllers\Api\Admin\PersonalInventoryController::class, 'show']);

	Route::middleware('admin.api.superadmin')->group(function (): void {
		Route::get('/bases', [\App\Http\Controllers\Api\Admin\BaseController::class, 'index']);
		Route::get('/bases/{id}', [\App\Http\Controllers\Api\Admin\BaseController::class, 'show']);
		Route::post('/bases', [\App\Http\Controllers\Api\Admin\BaseController::class, 'store']);
		Route::put('/bases/{id}', [\App\Http\Controllers\Api\Admin\BaseController::class, 'update']);
		Route::delete('/bases/{id}', [\App\Http\Controllers\Api\Admin\BaseController::class, 'destroy']);

		Route::get('/admins', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'index']);
		Route::get('/admins/lookup', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'lookup']);
		Route::post('/admins', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'store']);
		Route::get('/admins/{id}', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'show']);
		Route::put('/admins/{id}', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'update']);
		Route::post('/admins/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'toggleStatus']);
		Route::delete('/admins/{id}', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'destroy']);

		Route::get('/uniforms', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'index']);
		Route::get('/uniforms/{id}', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'show']);
		Route::post('/uniforms/{id}/toggle-active', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'toggleActive']);
		Route::get('/uniforms/{id}/items', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'items']);
		Route::get('/uniform-clothes/options', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'options']);
		Route::post('/uniform-clothes', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'storeItem']);
		Route::get('/uniform-clothes/{id}', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'showItem']);
		Route::put('/uniform-clothes/{id}', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'updateItem']);
		Route::delete('/uniform-clothes/{id}', [\App\Http\Controllers\Api\Admin\UniformCatalogController::class, 'destroyItem']);

		Route::prefix('reports')->group(function (): void {
			$c = \App\Http\Controllers\Api\Admin\ReportController::class;

			Route::get('/options', [$c, 'options']);
			Route::get('/clothes', [$c, 'clothes']);
			Route::get('/orders/uniform', [$c, 'uniform']);
			Route::get('/orders/uniform-users', [$c, 'uniformUsers']);
			Route::get('/orders/unit-users', [$c, 'unitUsers']);
			Route::get('/orders/unit', [$c, 'unit']);
			Route::get('/orders/uniform-unit', [$c, 'uniformUnit']);
			Route::get('/orders/cloth', [$c, 'cloth']);
			Route::get('/users/units', [$c, 'usersByUnit']);
			Route::get('/users/uniform-tags', [$c, 'uniformTags']);
			Route::get('/users/without-orders', [$c, 'withoutOrders']);
			Route::get('/users/strength', [$c, 'strength']);
			Route::get('/excel/{type}', [$c, 'excel']);
		});

		Route::get('/units', [\App\Http\Controllers\Api\Admin\UnitController::class, 'index']);
		Route::get('/units/bases', [\App\Http\Controllers\Api\Admin\UnitController::class, 'bases']);
		Route::get('/units/{id}', [\App\Http\Controllers\Api\Admin\UnitController::class, 'show']);
		Route::post('/units', [\App\Http\Controllers\Api\Admin\UnitController::class, 'store']);
		Route::put('/units/{id}', [\App\Http\Controllers\Api\Admin\UnitController::class, 'update']);
		Route::delete('/units/{id}', [\App\Http\Controllers\Api\Admin\UnitController::class, 'destroy']);

		Route::get('/treds', [\App\Http\Controllers\Api\Admin\TredController::class, 'index']);
		Route::get('/treds/{id}', [\App\Http\Controllers\Api\Admin\TredController::class, 'show']);
		Route::post('/treds', [\App\Http\Controllers\Api\Admin\TredController::class, 'store']);
		Route::put('/treds/{id}', [\App\Http\Controllers\Api\Admin\TredController::class, 'update']);
		Route::delete('/treds/{id}', [\App\Http\Controllers\Api\Admin\TredController::class, 'destroy']);

		Route::get('/announcements/audience-counts', [\App\Http\Controllers\Api\Admin\AnnouncementController::class, 'audienceCounts']);
		Route::post('/announcements/send', [\App\Http\Controllers\Api\Admin\AnnouncementController::class, 'send']);

		Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
		Route::get('/users/ranks', [\App\Http\Controllers\Api\Admin\UserController::class, 'ranks']);
		Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
		Route::put('/users/{id}/basic', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateBasic']);
		Route::put('/users/{id}/personal', [\App\Http\Controllers\Api\Admin\UserController::class, 'updatePersonal']);
		Route::post('/users/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\UserController::class, 'toggleStatus']);
		Route::post('/users/block-all', [\App\Http\Controllers\Api\Admin\UserController::class, 'blockAll']);
		Route::post('/users/unblock-all', [\App\Http\Controllers\Api\Admin\UserController::class, 'unblockAll']);
		Route::delete('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
		Route::post('/users/{id}/resend-activation', [\App\Http\Controllers\Api\Admin\UserController::class, 'resendActivation']);

		Route::prefix('system-settings')->group(function (): void {
			$c = \App\Http\Controllers\Api\Admin\SystemSettingsController::class;

			Route::get('/site', [$c, 'siteSettings']);
			Route::put('/site', [$c, 'saveSiteTitle']);
			Route::post('/site/logo', [$c, 'saveLogo']);

			Route::get('/uniforms', [$c, 'uniforms']);
			Route::post('/uniforms', [$c, 'createUniform']);
			// POST, not PUT -- PHP never populates $_FILES for a PUT body, multipart
			// or not, so a photo re-upload needs the same method file creation uses.
			Route::post('/uniforms/{id}', [$c, 'renameUniform']);
			Route::post('/uniform-items', [$c, 'createUniformItem']);
			Route::post('/uniform-items/{id}', [$c, 'renameUniformItem']);

			Route::get('/scale', [$c, 'scale']);
			Route::post('/scale/uniform-visibility', [$c, 'toggleUniformVisibility']);
			Route::post('/scale/item', [$c, 'saveScaleItem']);

			Route::get('/notifications', [$c, 'notificationSettings']);
			Route::put('/notifications', [$c, 'savePushEnabled']);
			Route::post('/notifications/announce', [$c, 'sendPushAnnouncement']);
		});
	});
});
