<?php

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

Auth::routes(['verify' => true, 'register' => config('anonaddy.enable_registration')]);

Route::post('/login/2fa', 'TwoFactorAuthController@authenticateTwoFactor')->name('login.2fa')->middleware(['2fa', 'throttle', 'auth']);

Route::get('/login/backup-code', 'BackupCodeController@index')->name('login.backup_code.index')->middleware('auth');
Route::post('/login/backup-code', 'BackupCodeController@login')->name('login.backup_code.login')->middleware('auth');


Route::middleware(['auth', 'verified', '2fa'])->group(function () {
    Route::get('/', 'AliasController@index')->name('aliases.index');
    Route::post('/aliases', 'AliasController@store')->name('aliases.store');
    Route::patch('/aliases/{id}', 'AliasController@update')->name('aliases.update');
    Route::delete('/aliases/{id}', 'AliasController@destroy')->name('aliases.destroy');

    Route::post('/active-aliases', 'ActiveAliasController@store')->name('active_aliases.store');
    Route::delete('/active-aliases/{id}', 'ActiveAliasController@destroy')->name('active_aliases.destroy');

    Route::get('/recipients', 'RecipientController@index')->name('recipients.index');
    Route::post('/recipients', 'RecipientController@store')->name('recipients.store');
    Route::delete('/recipients/{id}', 'RecipientController@destroy')->name('recipients.destroy');

    Route::post('/recipients/email/resend', 'RecipientVerificationController@resend')->name('recipient_verification.resend');

    Route::patch('/recipient-keys/{id}', 'RecipientKeyController@update')->name('recipient_keys.update');
    Route::delete('/recipient-keys/{id}', 'RecipientKeyController@destroy')->name('recipient_keys.destroy');

    Route::post('/encrypted-recipients', 'EncryptedRecipientController@store')->name('encrypted_recipients.store');
    Route::delete('/encrypted-recipients/{id}', 'EncryptedRecipientController@destroy')->name('encrypted_recipients.destroy');

    Route::post('/alias-recipients', 'AliasRecipientController@store')->name('alias_recipients.store');

    Route::get('/domains', 'DomainController@index')->name('domains.index');
    Route::post('/domains', 'DomainController@store')->name('domains.store');
    Route::patch('/domains/{id}', 'DomainController@update')->name('domains.update');
    Route::delete('/domains/{id}', 'DomainController@destroy')->name('domains.destroy');
    Route::patch('/domains/{id}/default-recipient', 'DomainDefaultRecipientController@update')->name('domains.default_recipient');

    Route::get('/domains/{id}/recheck', 'DomainVerificationController@recheck')->name('domain_verification.recheck');

    Route::post('/active-domains', 'ActiveDomainController@store')->name('active_domains.store');
    Route::delete('/active-domains/{id}', 'ActiveDomainController@destroy')->name('active_domains.destroy');

    Route::get('/usernames', 'AdditionalUsernameController@index')->name('usernames.index');
    Route::post('/usernames', 'AdditionalUsernameController@store')->name('usernames.store');
    Route::patch('/usernames/{id}', 'AdditionalUsernameController@update')->name('usernames.update');
    Route::delete('/usernames/{id}', 'AdditionalUsernameController@destroy')->name('usernames.destroy');

    Route::post('/active-usernames', 'ActiveAdditionalUsernameController@store')->name('active_usernames.store');
    Route::delete('/active-usernames/{id}', 'ActiveAdditionalUsernameController@destroy')->name('active_usernames.destroy');

    Route::get('/deactivate/{alias}', 'DeactivateAliasController@deactivate')->name('deactivate');
});


Route::group([
    'middleware' => ['auth', '2fa'],
    'prefix' => 'settings'
], function () {
    Route::get('/', 'SettingController@show')->name('settings.show');
    Route::post('/account', 'SettingController@destroy')->name('account.destroy');

    Route::post('/default-recipient', 'DefaultRecipientController@update')->name('settings.default_recipient');
    Route::post('/edit-default-recipient', 'DefaultRecipientController@edit')->name('settings.edit_default_recipient');

    Route::post('/from-name', 'FromNameController@update')->name('settings.from_name');

    Route::post('/email-subject', 'EmailSubjectController@update')->name('settings.email_subject');

    Route::post('/banner-location', 'BannerLocationController@update')->name('settings.banner_location');

    Route::post('/password', 'PasswordController@update')->name('settings.password');

    Route::post('/2fa/enable', 'TwoFactorAuthController@store')->name('settings.2fa_enable');
    Route::post('/2fa/regenerate', 'TwoFactorAuthController@update')->name('settings.2fa_regenerate');
    Route::post('/2fa/disable', 'TwoFactorAuthController@destroy')->name('settings.2fa_disable');
});
