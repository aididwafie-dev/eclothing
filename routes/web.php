<?php

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

Route::namespace('App\Http\Controllers')->group(function (): void {

/************	HomeController Starts	************/
/**
 * site home.
 */
Route::get('/', [
	'as' => 'home',
	'uses' => 'HomeController@index',
]);
Route::get('/home', [
	'as' => 'home',
	'uses' => 'HomeController@index',
]);
/************	HomeController Ends	************/

Route::get('/health', function () {
    return 'OK';
});

/************	UserController Starts	************/
/**
 * user registration, account varification, user login, 
 * user forgot password, user logout.
 */
Route::get('/register', [
	'as' => 'user.register',
	'uses' => 'UserController@register',
]);

Route::post('/value-exist', [
	'as' => 'user.ajax',
	'uses' => 'UserController@ajaxValueExist',
]);

Route::post('/signed-up', [
	'as' => 'user.signedup',
	'uses' => 'UserController@SignedUp',
]);

Route::get('/signed-up', [
	'as' => 'user.signedup',
	'uses' => 'UserController@notSignedUp',
]);

Route::get('/user/login', [
//	'as' => 'user.login',
//	'uses' => 'UserController@UserLogin',
		'as' => 'user.login',
	'uses' => 'HomeController@index',
]);

Route::post('/user/login-check', [
	'as' => 'user.logincheck',
	'uses' => 'UserController@UserLoginCheck',
]);

Route::get('/verify-account/{string}', [
	'as' => 'user.verify-account',
	'uses' => 'UserController@verifyAccount',
]);

Route::get('/forgot-password', [
	'as' => 'user.forgot-password',
	'uses' => 'UserController@forgotPassword',
]);

Route::post('/if-valid', [
	'as' => 'user.valid',
	'uses' => 'UserController@ajaxValidUser',
]);

Route::post('/send-code', [
	'as' => 'send.code',
	'uses' => 'UserController@ajaxSendNewPassword',
]);

Route::post('/send-announcements', [
	'as' => 'send.announcements',
	'uses' => 'AdminController@sendAnnouncement',
]);

Route::get('/user/logout', [
	'as' => 'user.logout',
	'uses' => 'UserController@userLogout',
]);
/************	UserController Ends	************/

/************	UserChangesController Starts	************/
/**
 * user reset email id, user reset password.
 */
Route::get('/user/change-password', [
	'as' => 'user.change-password',
	'uses' => 'UserChangesController@changePassword',
]);

Route::post('/user/edit-password', [
	'as' => 'user.edit-password',
	'uses' => 'UserChangesController@editPassword',
]);

Route::get('/user/change-email', [
	'as' => 'user.change-email',
	'uses' => 'UserChangesController@changeEmail',
]);

Route::post('/if-already-exists', [
	'as' => 'email.already-exists',
	'uses' => 'UserChangesController@ifEmailAlreadyExists',
]);

Route::post('/user/edit-email', [
	'as' => 'user.edit-email',
	'uses' => 'UserChangesController@editEmail',
]);
/************	UserChangesController Ends	************/

/************	DashboardController Starts	************/
/**
 * set personal details dropdown values, save personal details of user,
 * set uniform dropdown values, place order.
 */
Route::get('/user/personal-details/', [
	'as' => 'user.personal',
	'uses' => 'DashboardController@index',
]);

Route::post('/ajax-load-rank-values', [
	'as' => '/ajax-load.rank-values',
	'uses' => 'DashboardController@ajaxLoadRankValues',
]);

Route::post('/personal-details-save', [
	'as' => 'personal.details.save',
	'uses' => 'DashboardController@savePersonalDetails',
]);

Route::get('/personal-details/restore', [
	'as' => 'personal-details.restore',
	'uses' => 'DashboardController@restorePersonalDetails',
]);

Route::get('/user/uniform-selection/', [
	'as' => 'user.uniform',
	'uses' => 'DashboardController@userUniformSelection',
]);

Route::get('/user/accessories-selection/', [
	'as' => 'user.accessories',
	'uses' => 'DashboardController@userAccessoriesSelection',
]);

Route::get('/cancel', [
	'as' => 'save.cancle',
	'uses' => 'DashboardController@cancelSave',
]);

Route::post('/load-uniform-data', [
	'as' => 'loadUniform.data',
	'uses' => 'DashboardController@loadUniformData',
]);

Route::post('/load-uniform-photos', [
	'as' => 'loadUniform.photos',
	'uses' => 'DashboardController@loadUniformPhotos',
]);

Route::post('/uniform-cart/add', [
	'as' => 'uniformCart.add',
	'uses' => 'DashboardController@addUniformCartItem',
]);

Route::post('/uniform-cart/remove', [
	'as' => 'uniformCart.remove',
	'uses' => 'DashboardController@removeUniformCartItem',
]);

Route::post('/uniform-cart/checkout', [
	'as' => 'uniformCart.checkout',
	'uses' => 'DashboardController@checkoutUniformCart',
]);

Route::post('/uniform-details-save', [
	'as' => 'loadUniform.data',
	'uses' => 'DashboardController@saveUniformDetailsInOrders',
]);

Route::get('/user/ordered-uniform', [
	'as' => 'user.ordered-uniform',
	'uses' => 'DashboardController@getOrderedUniform',
]);

Route::post('/ajax-mail-user-order-details', [
	'as' => 'mail-user.order-details',
	'uses' => 'DashboardController@mailUserOrderDetails',
]);

Route::post('/ajax-delete-user-order', [
	'as' => 'delete-user.order',
	'uses' => 'DashboardController@deleteUserOrder',
]);
/************	DashboardController Ends	************/

/************	AdminController Starts	************/
/**
 * Login and Logout for admin, List of all user,
 * Edit besic, personal and uniform details of user,
 * change status of and user.
 */
Route::get('/site-admin', [
	'as' => 'site-admin.login',
	'uses' => 'AdminController@index',
]);

Route::post('/admin/login-check', [
	'as' => 'admin.logincheck',
	'uses' => 'AdminController@checkAdminLogin',
]);

Route::get('/admin-logout', [
	'as' => 'admin.logout',
	'uses' => 'AdminController@adminLogout',
]);

Route::get('/all-users', [
	'as' => 'all.users',
	'uses' => 'AdminController@allUsersTable',
]);

Route::post('/ajax-usersTable', [
	'as' => 'ajax.usersTable',
	'uses' => 'AdminController@ajaxDatatableUsersDetails',
]);

Route::get('/edit/basic_details/{id}', [
 	'as' => 'edit.basic_details',
 	'uses' => 'AdminController@fromEditUserBasicDetails',
]);

Route::post('/change-basicDetails', [
 	'as' => 'change.basicDetails',
 	'uses' => 'AdminController@changeBasicDetails',
]);

Route::get('/admin-cancel', [
	'as' => 'admin.cancel',
	'uses' => 'AdminController@adminCancel',
]);

Route::get('/edit/personal_details/{id}', [
	'as' => 'edit.personal_details',
	'uses' => 'AdminController@fromEditUserPersonalDetails',
]);

Route::post('/admin/ajax-load-rank-values', [
	'as' => 'admin-load.rank-values',
	'uses' => 'AdminController@ajaxLoadRankValuesForAdmin',
]);

Route::post('/change-personalDetails', [
	'as' => 'change.personalDetails',
	'uses' => 'AdminController@changePersonalDetails',
]);

Route::get('/change-status/{id}', [
	'as' => 'change.user-status',
	'uses' => 'AdminController@changeUserAccessStatus',
]);

Route::get('/block_all_users', [
	'as' => 'change.user-block-all',
	'uses' => 'AdminController@changeUserAccessBlockAll',
]);

Route::get('/unblock_all_users', [
	'as' => 'change.user-unblock-all',
	'uses' => 'AdminController@changeUserAccessUnblockAll',
]);

Route::get('/uniform_enable_disable/{id}', [
	'as' => 'change.uniform-enable-disable',
	'uses' => 'AdminController@changeUniformEnableDisable',
]);


Route::get('/show/uniform_details/{id}', [
	'as' => 'show.uniform_details',
	'uses' => 'AdminController@listUserUniformDetails',
]);

Route::get('/admin/uniform-orders', [
	'as' => 'admin.uniform-orders',
	'uses' => 'AdminController@uniformOrdersList',
]);

Route::get('/admin/uniform-orders/{id}', [
	'as' => 'admin.uniform-orders.show',
	'uses' => 'AdminController@uniformOrderDetail',
]);

Route::post('/admin/uniform-orders/update', [
	'as' => 'admin.uniform-orders.update',
	'uses' => 'AdminController@updateUniformOrderStatus',
]);

Route::get('/edit/uniform_details/{id}', [
	'as' => 'edit.uniform_details',
	'uses' => 'AdminController@fromEditUserUniformDetails',
]);

Route::post('/uniform-details-saveEdit', [
	'as' => 'saveEdit.uniformDetails',
	'uses' => 'AdminController@saveUniformEditedDetails',
]);

Route::get('/delete-user/{id}', [
	'as' => 'admin.delete-user',
	'uses' => 'AdminController@deleteGeneralUser',
]);

Route::get('/delete-unit/{id}', [
	'as' => 'admin.delete-unit',
	'uses' => 'AdminController@deleteUnit',
]);

Route::get('/delete-tred/{id}', [
	'as' => 'admin.delete-tred',
	'uses' => 'AdminController@deleteTred',
]);

Route::get('/delete-order/{user_id}/{id}', [
	'as' => 'admin.delete-order',
	'uses' => 'AdminController@deleteOrder',
]);

Route::post('/ajax-resend-mail', [
	'as' => 'admin.resend-mail',
	'uses' => 'AdminController@resendActivationMailToUser',
]);
/************	AdminController Ends	************/

/************	AdminNewListController Starts	************/
/**
 * Adding new admin, List of all admins, change status of admin.
 */
Route::get('/new-admin', [
	'as' => 'admin.new-admin',
	'uses' => 'AdminNewListController@index',
]);

Route::post('/get-new-admin-details', [
	'as' => 'new-admin.details',
	'uses' => 'AdminNewListController@getNewAdminDetails',
]);

Route::post('/add-admin', [
	'as' => 'add.admin',
	'uses' => 'AdminNewListController@addNewAdmin',
]);

Route::get('/all-admins', [
	'as' => 'all.admins',
	'uses' => 'AdminNewListController@getAllAdminsList',
]);

Route::get('/admin/system-settings', [
	'as' => 'admin.system-settings',
	'uses' => 'AdminController@systemSettings',
]);

Route::post('/admin/system-settings', [
	'as' => 'admin.system-settings.save',
	'uses' => 'AdminController@saveSystemSettings',
]);

Route::post('/ajax-admin-list', [
	'as' => 'ajax.admin-list',
	'uses' => 'AdminNewListController@ajaxDatatableAdminsList',
]);

Route::get('/change-admin-status/{id}', [
	'as' => 'change.admin-status',
	'uses' => 'AdminNewListController@changeAdminStatus',
]);

Route::get('/delete-admin/{id}', [
	'as' => 'admin.delete-admin',
	'uses' => 'AdminNewListController@deleteUserAsAdmin',
]);
/************	AdminNewListController Ends	************/

/************	AdminUniformController Starts	************/
/**
 * Uniform and Cloth list, Edit form for Uniform and Cloth.
 */
Route::get('/admin/uniform', [
	'as' => 'admin.uniform',
	'uses' => 'AdminUniformController@index',
]);

Route::get('/admin/accessories/{id}', [
	'as' => 'admin.accessories',
	'uses' => 'AdminAccessoriesController@index',
]);

Route::get('/admin/unit', [
	'as' => 'admin.unit',
	'uses' => 'AdminUnitController@index',
]);

Route::get('/admin/unit/edit/{id}', [
	'as' => 'unit.edit',
	'uses' => 'AdminUnitController@editUnit',
]);

Route::get('/admin/unit/add', [
	'as' => 'unit.add',
	'uses' => 'AdminUnitController@addUnit',
]);

Route::get('/admin/size', [
	'as' => 'admin.size',
	'uses' => 'AdminSizeController@index',
]);

Route::get('/admin/size/edit/{id}', [
	'as' => 'size.edit',
	'uses' => 'AdminSizeController@editSize',
]);

Route::get('/admin/size/add', [
	'as' => 'size.add',
	'uses' => 'AdminSizeController@addSize',
]);

Route::get('/admin/tred', [
	'as' => 'admin.tred',
	'uses' => 'AdminTredController@index',
]);

Route::get('/admin/tred/edit/{id}', [
	'as' => 'tred.edit',
	'uses' => 'AdminTredController@editTred',
]);

Route::get('/admin/tred/add', [
	'as' => 'tred.add',
	'uses' => 'AdminTredController@addTred',
]);

Route::post('/save-edited-unit', [
	'as' => 'save-edited.unit',
	'uses' => 'AdminUnitController@saveEditedUnit',
]);

Route::post('/save-edited-size', [
	'as' => 'save-edited.size',
	'uses' => 'AdminSizeController@saveEditedSize',
]);

Route::post('/save-edited-tred', [
	'as' => 'save-edited.tred',
	'uses' => 'AdminTredController@saveEditedTred',
]);

Route::get('/uniform/edit-name/{id}', [
	'as' => 'uniform.edit-name',
	'uses' => 'AdminUniformController@editUniformName',
]);

Route::post('/save-edited-uniformName', [
	'as' => 'save-edited.uniformName',
	'uses' => 'AdminUniformController@saveEditedUniformName',
]);

Route::get('/admin/uniform-edit-cancel', [
	'as' => 'uniform.edit-name-cancel',
	'uses' => 'AdminUniformController@index',
]);

Route::get('/admin/clothes/{id}', [
	'as' => 'admin.clothes',
	'uses' => 'AdminUniformController@clothesSummaryToAdmin',
]);

Route::get('/uniform/add-cloth/{id}', [
	'as' => 'uniform.add-cloth',
	'uses' => 'AdminUniformController@addUniformClothes',
]);

Route::get('/accessories/add-accessory/{id}', [
	'as' => 'uniform.add-accessory',
	'uses' => 'AdminAccessoriesController@addAccessory',
]);

Route::get('/uniform/add-uniform', [
	'as' => 'uniform.add-uniform',
	'uses' => 'AdminUniformController@addUniform',
]);

Route::get('/admin/clothes-add-cancel/{id}', [
	'as' => 'uniform.clothes-add-cancel',
	'uses' => 'AdminUniformController@clothesSummaryToAdmin',
]);

Route::post('/save-added-cloth', [
	'as' => 'save.added-cloth',
	'uses' => 'AdminUniformController@saveAddedCloth',
]);

Route::post('/save-added-accessory', [
	'as' => 'save.added-accessory',
	'uses' => 'AdminAccessoriesController@saveAddedAccessory',
]);

Route::post('/save-added-unit', [
	'as' => 'save.added-unit',
	'uses' => 'AdminUnitController@saveAddedUnit',
]);

Route::post('/save-added-tred', [
	'as' => 'save.added-tred',
	'uses' => 'AdminTredController@saveAddedTred',
]);

Route::post('/save-added-uniform', [
	'as' => 'save.added-uniform',
	'uses' => 'AdminUniformController@saveAddedUniform',
]);

Route::post('/save-added-size', [
	'as' => 'save.added-size',
	'uses' => 'AdminSizeController@saveAddedSize',
]);

Route::get('/uniform/clothes-edit/{id}', [
	'as' => 'uniform.clothes-edit',
	'uses' => 'AdminUniformController@uniformClothesEditForm',
]);

Route::get('/accessories/edit/{id}', [
	'as' => 'accessories.edit',
	'uses' => 'AdminAccessoriesController@editAccessory',
]);

Route::get('/admin/clothes-edit-cancel/{id}', [
	'as' => 'admin.clothes-edit-cancel',
	'uses' => 'AdminUniformController@clothesSummaryToAdmin',
]);

Route::post('/save-edited-accessory', [
	'as' => 'save.edited-accessory',
	'uses' => 'AdminAccessoriesController@saveEdited',
]);

Route::post('/save-edited-clothes', [
	'as' => 'save.edited-clothes',
	'uses' => 'AdminUniformController@saveEditedClothes',
]);

Route::get('/uniform/clothes-delete/{id}/{uniform_id}', [
	'as' => 'admin.clothes-delete',
	'uses' => 'AdminUniformController@deleteCloth',
]);

Route::get('/accessories/delete/{id}', [
	'as' => 'admin.accessories-delete',
	'uses' => 'AdminAccessoriesController@delete',
]);
/************	AdminUniformController Ends	************/

/************	AdminReportController Starts	************/
/**
 * Showing and creating report in excel in three ways (User wise, Uniform wise, Cloth wise).
 */
Route::get('/admin/orders-report', [
	'as' => 'admin.orders-report',
	'uses' => 'AdminReportController@index',
]);

Route::get('/admin/users-report', [
	'as' => 'admin.users-report',
	'uses' => 'AdminUsersReportController@index',
]);

Route::get('/orders-user-wise', [
	'as' => 'admin.orders-user-wise',
	'uses' => 'AdminReportController@orderSelectUserWise',
]);

Route::get('/orders-user-wise-unit', [
	'as' => 'admin.orders-user-wise-unit',
	'uses' => 'AdminReportController@orderSelectUserWiseUnit',
]);

Route::get('/users-unit-wise', [
	'as' => 'admin.users-unit-wise',
	'uses' => 'AdminUsersReportController@userSelectUnitWise',
]);

Route::get('/orders-uniform-wise', [
	'as' => 'admin.orders-uniform-wise',
	'uses' => 'AdminReportController@orderSelectUniformWise',
]);

Route::get('/orders-uniform-unit-wise', [
	'as' => 'admin.orders-uniform-unit-wise',
	'uses' => 'AdminReportController@orderSelectUniformUnitWise',
]);

Route::get('/orders-cloth-wise', [
	'as' => 'admin.orders-cloth-wise',
	'uses' => 'AdminReportController@orderSelectClothWise',
]);

Route::get('/orders-unit-wise', [
	'as' => 'admin.orders-unit-wise',
	'uses' => 'AdminReportController@orderSelectUnitWise',
]);

Route::post('/uniform-report', [
	'as' => 'admin.uniform-report',
	'uses' => 'AdminReportController@getReportUniformWise',
]);

Route::post('/uniform-unit-report', [
	'as' => 'admin.uniform-report',
	'uses' => 'AdminReportController@getReportUniformUnitWise',
]);

Route::get('/creat-excel-uniform/{id}', [
	'as' => 'admin.creat-excel-uniform',
	'uses' => 'AdminReportController@adminCreatExcelUniform',
]);

Route::get('/creat-excel-users-units', [
	'as' => 'admin.creat-excel-unitWise',
	'uses' => 'AdminUsersReportController@adminCreatExcelUnit',
]);

Route::get('/creat-excel-users-without-orders', [
	'as' => 'admin.creat-excel-without-orders',
	'uses' => 'AdminUsersReportController@adminCreatExcelWithoutOrders',
]);

Route::get('/creat-excel-users-uniform-tags', [
	'as' => 'admin.creat-excel-uniform-tags',
	'uses' => 'AdminUsersReportController@adminCreatExcelUniformTags',
]);
Route::get('/creat-excel-users-strength-units', [
	'as' => 'admin.creat-excel-strength-units',
	'uses' => 'AdminUsersReportController@adminCreatExcelStrengthUnits',
]);

Route::post('/uniform-report-with-user-details', [
	'as' => 'admin.user-details-uniform-report',
	'uses' => 'AdminReportController@getReportUserWise',
]);

Route::post('/uniform-report-with-user-details-unit', [
	'as' => 'admin.user-details-uniform-report-unit',
	'uses' => 'AdminReportController@getReportUserWiseUnit',
]);

Route::get('/user-report-with-unit', [
	'as' => 'admin.user-report-with-unit',
	'uses' => 'AdminUsersReportController@getReportUnitWise',
]);

Route::get('/user-report-with-uniform-name', [
	'as' => 'admin.user-report-with-uniform-name',
	'uses' => 'AdminUsersReportController@getReportUniformTag',
]);

Route::post('/user-report-with-uniform-name', [
	'as' => 'admin.user-report-with-uniform-name',
	'uses' => 'AdminUsersReportController@getReportUniformTag',
]);

Route::get('/user-report-without-orders', [
	'as' => 'admin.user-report-without-orders',
	'uses' => 'AdminUsersReportController@getReportWithoutOrders',
]);

Route::get('/user-report-strength-units', [
	'as' => 'admin.user-report-strength-units',
	'uses' => 'AdminUsersReportController@getReportStrengthUnits',
]);
	
Route::get('/excel-uniform-user-details/{id}', [
	'as' => 'admin.creat-excel-uniform-user-details',
	'uses' => 'AdminReportController@adminCreatExcelUniformWithUserDetails',
]);

Route::get('/excel-uniform-user-details-unit/{id}', [
	'as' => 'admin.creat-excel-uniform-user-details-unit',
	'uses' => 'AdminReportController@adminCreatExcelUniformWithUserDetailsUnit',
]);

Route::post('/load-cloth-ajax', [
	'as' => 'admin.load-cloth-ajax',
	'uses' => 'AdminReportController@loadClothAjax',
]);

Route::post('/cloth-report', [
	'as' => 'admin.cloth-report',
	'uses' => 'AdminReportController@getReportUniformTag',
]);

Route::post('/unit-report', [
	'as' => 'admin.unit-report',
	'uses' => 'AdminReportController@getReportunitWise',
]);

Route::get('/creat-excel-cloth/{id}/{slug}', [
	'as' => 'admin.creat-excel-cloth',
	'uses' => 'AdminReportController@adminCreatExcelCloth',
]);
/************	AdminReportController Ends	************/

/************	AdminPasswordController Starts	************/
/**
 * admin reset password, admin forgot password.
 */
Route::get('/admin/change-password', [
	'as' => 'admin.change-password',
	'uses' => 'AdminPasswordController@index',
]);
Route::get('/admin/announcements', [
	'as' => 'admin.announcements',
	'uses' => 'AnnouncementsController@index',
]);

Route::post('/admin/save-change-password', [
	'as' => 'admin.save-change-password',
	'uses' => 'AdminPasswordController@saveChangePassword',
]);

Route::get('/admin/forgot-password', [
	'as' => 'admin.forgot-password',
	'uses' => 'AdminPasswordController@adminForgotPassword',
]);

Route::post('/admin/if-validEmail', [
	'as' => 'admin.if-validEmail',
	'uses' => 'AdminPasswordController@ajaxCheckIfValidEmail',
]);

Route::post('/admin/send-code', [
	'as' => 'admin.send-code',
	'uses' => 'AdminPasswordController@ajaxEmailAdminNewPassword',
]);
/************	AdminPasswordController Ends	************/
});
