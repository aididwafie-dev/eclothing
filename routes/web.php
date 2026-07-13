<?php

use App\Http\Controllers\AdminAccessoriesController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminNewListController;
use App\Http\Controllers\AdminPasswordController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSizeController;
use App\Http\Controllers\AdminTredController;
use App\Http\Controllers\AdminUniformController;
use App\Http\Controllers\AdminUnitController;
use App\Http\Controllers\AdminUsersReportController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserChangesController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/************	Public routes	************/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/health', function () {
    return 'OK';
});

Route::get('/register', [UserController::class, 'register'])->name('user.register');
Route::post('/value-exist', [UserController::class, 'ajaxValueExist'])->name('user.ajax');
Route::post('/signed-up', [UserController::class, 'SignedUp'])->name('user.signedup');
Route::get('/signed-up', [UserController::class, 'notSignedUp'])->name('user.signedup');
Route::get('/user/login', [HomeController::class, 'index'])->name('user.login');
Route::post('/user/login-check', [UserController::class, 'UserLoginCheck'])->name('user.logincheck');
Route::get('/verify-account/{string}', [UserController::class, 'verifyAccount'])->name('user.verify-account');
Route::get('/forgot-password', [UserController::class, 'forgotPassword'])->name('user.forgot-password');
Route::post('/if-valid', [UserController::class, 'ajaxValidUser'])->name('user.valid');
Route::post('/send-code', [UserController::class, 'ajaxSendNewPassword'])->name('send.code');

Route::get('/site-admin', [AdminController::class, 'index'])->name('site-admin.login');
Route::post('/admin/login-check', [AdminController::class, 'checkAdminLogin'])->name('admin.logincheck');
Route::get('/admin/forgot-password', [AdminPasswordController::class, 'adminForgotPassword'])->name('admin.forgot-password');
Route::post('/admin/if-validEmail', [AdminPasswordController::class, 'ajaxCheckIfValidEmail'])->name('admin.if-validEmail');
Route::post('/admin/send-code', [AdminPasswordController::class, 'ajaxEmailAdminNewPassword'])->name('admin.send-code');

/************	Routes requiring a logged-in user	************/

Route::middleware('user.auth')->group(function (): void {

	Route::get('/user/logout', [UserController::class, 'userLogout'])->name('user.logout');

	/************	UserChangesController	************/
	Route::get('/user/change-password', [UserChangesController::class, 'changePassword'])->name('user.change-password');
	Route::post('/user/edit-password', [UserChangesController::class, 'editPassword'])->name('user.edit-password');
	Route::get('/user/change-email', [UserChangesController::class, 'changeEmail'])->name('user.change-email');
	Route::post('/if-already-exists', [UserChangesController::class, 'ifEmailAlreadyExists'])->name('email.already-exists');
	Route::post('/user/edit-email', [UserChangesController::class, 'editEmail'])->name('user.edit-email');

	/************	DashboardController	************/
	Route::get('/user/personal-details/', [DashboardController::class, 'index'])->name('user.personal');
	Route::post('/ajax-load-rank-values', [DashboardController::class, 'ajaxLoadRankValues'])->name('/ajax-load.rank-values');
	Route::post('/personal-details-save', [DashboardController::class, 'savePersonalDetails'])->name('personal.details.save');
	Route::get('/personal-details/restore', [DashboardController::class, 'restorePersonalDetails'])->name('personal-details.restore');
	Route::get('/user/uniform-selection/', [DashboardController::class, 'userUniformSelection'])->name('user.uniform');
	Route::get('/user/accessories-selection/', [DashboardController::class, 'userAccessoriesSelection'])->name('user.accessories');
	Route::get('/cancel', [DashboardController::class, 'cancelSave'])->name('save.cancle');
	Route::post('/load-uniform-data', [DashboardController::class, 'loadUniformData'])->name('loadUniform.data');
	Route::post('/load-uniform-photos', [DashboardController::class, 'loadUniformPhotos'])->name('loadUniform.photos');
	Route::post('/uniform-cart/add', [DashboardController::class, 'addUniformCartItem'])->name('uniformCart.add');
	Route::post('/uniform-cart/remove', [DashboardController::class, 'removeUniformCartItem'])->name('uniformCart.remove');
	Route::post('/uniform-cart/checkout', [DashboardController::class, 'checkoutUniformCart'])->name('uniformCart.checkout');
	Route::post('/uniform-details-save', [DashboardController::class, 'saveUniformDetailsInOrders'])->name('loadUniform.data');
	Route::get('/user/ordered-uniform', [DashboardController::class, 'getOrderedUniform'])->name('user.ordered-uniform');
	Route::post('/ajax-mail-user-order-details', [DashboardController::class, 'mailUserOrderDetails'])->name('mail-user.order-details');
	Route::post('/ajax-delete-user-order', [DashboardController::class, 'deleteUserOrder'])->name('delete-user.order');
	Route::get('/user/orders/{id}/kew-ps8', [DashboardController::class, 'generateKewPs8Report'])->name('user.order.kew-ps8');
});

/************	Routes requiring a logged-in admin	************/

Route::middleware('admin.auth')->group(function (): void {

	/************	AdminController	************/
	Route::get('/admin-logout', [AdminController::class, 'adminLogout'])->name('admin.logout');
	Route::get('/all-users', [AdminController::class, 'allUsersTable'])->name('all.users');
	Route::post('/ajax-usersTable', [AdminController::class, 'ajaxDatatableUsersDetails'])->name('ajax.usersTable');
	Route::get('/edit/basic_details/{id}', [AdminController::class, 'fromEditUserBasicDetails'])->name('edit.basic_details');
	Route::post('/change-basicDetails', [AdminController::class, 'changeBasicDetails'])->name('change.basicDetails');
	Route::get('/admin-cancel', [AdminController::class, 'adminCancel'])->name('admin.cancel');
	Route::get('/edit/personal_details/{id}', [AdminController::class, 'fromEditUserPersonalDetails'])->name('edit.personal_details');
	Route::post('/admin/ajax-load-rank-values', [AdminController::class, 'ajaxLoadRankValuesForAdmin'])->name('admin-load.rank-values');
	Route::post('/change-personalDetails', [AdminController::class, 'changePersonalDetails'])->name('change.personalDetails');
	Route::get('/change-status/{id}', [AdminController::class, 'changeUserAccessStatus'])->name('change.user-status');
	Route::get('/block_all_users', [AdminController::class, 'changeUserAccessBlockAll'])->name('change.user-block-all');
	Route::get('/unblock_all_users', [AdminController::class, 'changeUserAccessUnblockAll'])->name('change.user-unblock-all');
	Route::get('/uniform_enable_disable/{id}', [AdminController::class, 'changeUniformEnableDisable'])->name('change.uniform-enable-disable');
	Route::get('/show/uniform_details/{id}', [AdminController::class, 'listUserUniformDetails'])->name('show.uniform_details');
	Route::get('/admin/uniform-orders', [AdminController::class, 'uniformOrdersList'])->name('admin.uniform-orders');
	Route::get('/admin/uniform-orders/{id}', [AdminController::class, 'uniformOrderDetail'])->name('admin.uniform-orders.show');
	Route::post('/admin/uniform-orders/update', [AdminController::class, 'updateUniformOrderStatus'])->name('admin.uniform-orders.update');
	Route::get('/edit/uniform_details/{id}', [AdminController::class, 'fromEditUserUniformDetails'])->name('edit.uniform_details');
	Route::post('/uniform-details-saveEdit', [AdminController::class, 'saveUniformEditedDetails'])->name('saveEdit.uniformDetails');
	Route::get('/delete-user/{id}', [AdminController::class, 'deleteGeneralUser'])->name('admin.delete-user');
	Route::get('/delete-unit/{id}', [AdminController::class, 'deleteUnit'])->name('admin.delete-unit');
	Route::get('/delete-tred/{id}', [AdminController::class, 'deleteTred'])->name('admin.delete-tred');
	Route::get('/delete-order/{user_id}/{id}', [AdminController::class, 'deleteOrder'])->name('admin.delete-order');
	Route::post('/ajax-resend-mail', [AdminController::class, 'resendActivationMailToUser'])->name('admin.resend-mail');
	Route::post('/send-announcements', [AdminController::class, 'sendAnnouncement'])->name('send.announcements');
	Route::get('/admin/system-settings', [AdminController::class, 'systemSettings'])->name('admin.system-settings');
	Route::post('/admin/system-settings', [AdminController::class, 'saveSystemSettings'])->name('admin.system-settings.save');

	/************	AdminNewListController	************/
	Route::get('/new-admin', [AdminNewListController::class, 'index'])->name('admin.new-admin');
	Route::post('/get-new-admin-details', [AdminNewListController::class, 'getNewAdminDetails'])->name('new-admin.details');
	Route::post('/add-admin', [AdminNewListController::class, 'addNewAdmin'])->name('add.admin');
	Route::get('/all-admins', [AdminNewListController::class, 'getAllAdminsList'])->name('all.admins');
	Route::post('/ajax-admin-list', [AdminNewListController::class, 'ajaxDatatableAdminsList'])->name('ajax.admin-list');
	Route::get('/change-admin-status/{id}', [AdminNewListController::class, 'changeAdminStatus'])->name('change.admin-status');
	Route::get('/delete-admin/{id}', [AdminNewListController::class, 'deleteUserAsAdmin'])->name('admin.delete-admin');

	/************	AdminUniformController / AdminAccessoriesController / AdminUnitController / AdminSizeController / AdminTredController	************/
	Route::get('/admin/uniform', [AdminUniformController::class, 'index'])->name('admin.uniform');
	Route::get('/admin/accessories/{id}', [AdminAccessoriesController::class, 'index'])->name('admin.accessories');
	Route::get('/admin/unit', [AdminUnitController::class, 'index'])->name('admin.unit');
	Route::get('/admin/unit/edit/{id}', [AdminUnitController::class, 'editUnit'])->name('unit.edit');
	Route::get('/admin/unit/add', [AdminUnitController::class, 'addUnit'])->name('unit.add');
	Route::get('/admin/size', [AdminSizeController::class, 'index'])->name('admin.size');
	Route::get('/admin/size/edit/{id}', [AdminSizeController::class, 'editSize'])->name('size.edit');
	Route::get('/admin/size/add', [AdminSizeController::class, 'addSize'])->name('size.add');
	Route::get('/admin/tred', [AdminTredController::class, 'index'])->name('admin.tred');
	Route::get('/admin/tred/edit/{id}', [AdminTredController::class, 'editTred'])->name('tred.edit');
	Route::get('/admin/tred/add', [AdminTredController::class, 'addTred'])->name('tred.add');
	Route::post('/save-edited-unit', [AdminUnitController::class, 'saveEditedUnit'])->name('save-edited.unit');
	Route::post('/save-edited-size', [AdminSizeController::class, 'saveEditedSize'])->name('save-edited.size');
	Route::post('/save-edited-tred', [AdminTredController::class, 'saveEditedTred'])->name('save-edited.tred');
	Route::get('/uniform/edit-name/{id}', [AdminUniformController::class, 'editUniformName'])->name('uniform.edit-name');
	Route::post('/save-edited-uniformName', [AdminUniformController::class, 'saveEditedUniformName'])->name('save-edited.uniformName');
	Route::get('/admin/uniform-edit-cancel', [AdminUniformController::class, 'index'])->name('uniform.edit-name-cancel');
	Route::get('/admin/clothes/{id}', [AdminUniformController::class, 'clothesSummaryToAdmin'])->name('admin.clothes');
	Route::get('/uniform/add-cloth/{id}', [AdminUniformController::class, 'addUniformClothes'])->name('uniform.add-cloth');
	Route::get('/accessories/add-accessory/{id}', [AdminAccessoriesController::class, 'addAccessory'])->name('uniform.add-accessory');
	Route::get('/uniform/add-uniform', [AdminUniformController::class, 'addUniform'])->name('uniform.add-uniform');
	Route::get('/admin/clothes-add-cancel/{id}', [AdminUniformController::class, 'clothesSummaryToAdmin'])->name('uniform.clothes-add-cancel');
	Route::post('/save-added-cloth', [AdminUniformController::class, 'saveAddedCloth'])->name('save.added-cloth');
	Route::post('/save-added-accessory', [AdminAccessoriesController::class, 'saveAddedAccessory'])->name('save.added-accessory');
	Route::post('/save-added-unit', [AdminUnitController::class, 'saveAddedUnit'])->name('save.added-unit');
	Route::post('/save-added-tred', [AdminTredController::class, 'saveAddedTred'])->name('save.added-tred');
	Route::post('/save-added-uniform', [AdminUniformController::class, 'saveAddedUniform'])->name('save.added-uniform');
	Route::post('/save-added-size', [AdminSizeController::class, 'saveAddedSize'])->name('save.added-size');
	Route::get('/uniform/clothes-edit/{id}', [AdminUniformController::class, 'uniformClothesEditForm'])->name('uniform.clothes-edit');
	Route::get('/accessories/edit/{id}', [AdminAccessoriesController::class, 'editAccessory'])->name('accessories.edit');
	Route::get('/admin/clothes-edit-cancel/{id}', [AdminUniformController::class, 'clothesSummaryToAdmin'])->name('admin.clothes-edit-cancel');
	Route::post('/save-edited-accessory', [AdminAccessoriesController::class, 'saveEdited'])->name('save.edited-accessory');
	Route::post('/save-edited-clothes', [AdminUniformController::class, 'saveEditedClothes'])->name('save.edited-clothes');
	Route::get('/uniform/clothes-delete/{id}/{uniform_id}', [AdminUniformController::class, 'deleteCloth'])->name('admin.clothes-delete');
	Route::get('/accessories/delete/{id}', [AdminAccessoriesController::class, 'delete'])->name('admin.accessories-delete');

	/************	AdminReportController / AdminUsersReportController	************/
	Route::get('/admin/orders-report', [AdminReportController::class, 'index'])->name('admin.orders-report');
	Route::get('/admin/users-report', [AdminUsersReportController::class, 'index'])->name('admin.users-report');
	Route::get('/orders-user-wise', [AdminReportController::class, 'orderSelectUserWise'])->name('admin.orders-user-wise');
	Route::get('/orders-user-wise-unit', [AdminReportController::class, 'orderSelectUserWiseUnit'])->name('admin.orders-user-wise-unit');
	Route::get('/users-unit-wise', [AdminUsersReportController::class, 'userSelectUnitWise'])->name('admin.users-unit-wise');
	Route::get('/orders-uniform-wise', [AdminReportController::class, 'orderSelectUniformWise'])->name('admin.orders-uniform-wise');
	Route::get('/orders-uniform-unit-wise', [AdminReportController::class, 'orderSelectUniformUnitWise'])->name('admin.orders-uniform-unit-wise');
	Route::get('/orders-cloth-wise', [AdminReportController::class, 'orderSelectClothWise'])->name('admin.orders-cloth-wise');
	Route::get('/orders-unit-wise', [AdminReportController::class, 'orderSelectUnitWise'])->name('admin.orders-unit-wise');
	Route::post('/uniform-report', [AdminReportController::class, 'getReportUniformWise'])->name('admin.uniform-report');
	Route::post('/uniform-unit-report', [AdminReportController::class, 'getReportUniformUnitWise'])->name('admin.uniform-report');
	Route::get('/creat-excel-uniform/{id}', [AdminReportController::class, 'adminCreatExcelUniform'])->name('admin.creat-excel-uniform');
	Route::get('/creat-excel-users-units', [AdminUsersReportController::class, 'adminCreatExcelUnit'])->name('admin.creat-excel-unitWise');
	Route::get('/creat-excel-users-without-orders', [AdminUsersReportController::class, 'adminCreatExcelWithoutOrders'])->name('admin.creat-excel-without-orders');
	Route::get('/creat-excel-users-uniform-tags', [AdminUsersReportController::class, 'adminCreatExcelUniformTags'])->name('admin.creat-excel-uniform-tags');
	Route::get('/creat-excel-users-strength-units', [AdminUsersReportController::class, 'adminCreatExcelStrengthUnits'])->name('admin.creat-excel-strength-units');
	Route::post('/uniform-report-with-user-details', [AdminReportController::class, 'getReportUserWise'])->name('admin.user-details-uniform-report');
	Route::post('/uniform-report-with-user-details-unit', [AdminReportController::class, 'getReportUserWiseUnit'])->name('admin.user-details-uniform-report-unit');
	Route::get('/user-report-with-unit', [AdminUsersReportController::class, 'getReportUnitWise'])->name('admin.user-report-with-unit');
	Route::get('/user-report-with-uniform-name', [AdminUsersReportController::class, 'getReportUniformTag'])->name('admin.user-report-with-uniform-name');
	Route::post('/user-report-with-uniform-name', [AdminUsersReportController::class, 'getReportUniformTag'])->name('admin.user-report-with-uniform-name');
	Route::get('/user-report-without-orders', [AdminUsersReportController::class, 'getReportWithoutOrders'])->name('admin.user-report-without-orders');
	Route::get('/user-report-strength-units', [AdminUsersReportController::class, 'getReportStrengthUnits'])->name('admin.user-report-strength-units');
	Route::get('/excel-uniform-user-details/{id}', [AdminReportController::class, 'adminCreatExcelUniformWithUserDetails'])->name('admin.creat-excel-uniform-user-details');
	Route::get('/excel-uniform-user-details-unit/{id}', [AdminReportController::class, 'adminCreatExcelUniformWithUserDetailsUnit'])->name('admin.creat-excel-uniform-user-details-unit');
	Route::post('/load-cloth-ajax', [AdminReportController::class, 'loadClothAjax'])->name('admin.load-cloth-ajax');
	Route::post('/cloth-report', [AdminReportController::class, 'getReportUniformTag'])->name('admin.cloth-report');
	Route::post('/unit-report', [AdminReportController::class, 'getReportunitWise'])->name('admin.unit-report');
	Route::get('/creat-excel-cloth/{id}/{slug}', [AdminReportController::class, 'adminCreatExcelCloth'])->name('admin.creat-excel-cloth');

	/************	AdminPasswordController / AnnouncementsController	************/
	Route::get('/admin/change-password', [AdminPasswordController::class, 'index'])->name('admin.change-password');
	Route::get('/admin/announcements', [AnnouncementsController::class, 'index'])->name('admin.announcements');
	Route::post('/admin/save-change-password', [AdminPasswordController::class, 'saveChangePassword'])->name('admin.save-change-password');
});
