<?php

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\VerificationRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication & Registration Routes (Phase 2)
|--------------------------------------------------------------------------
| Registration: Student ID lookup -> account details (new email/phone/
| password) -> email OTP -> account created & linked to the matched
| alumni record. No matching record -> verification request instead.
|
| GET routes carry the route name for a given URI; POST routes at the
| same URI are left unnamed (route() resolves the URI from the GET
| route's name, which is all Blade needs for form actions).
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'lookup'])->middleware('throttle:10,1');

    Route::get('/register/student', [\App\Http\Controllers\Auth\StudentRegistrationController::class, 'create'])->name('register.student');
    Route::post('/register/student', [\App\Http\Controllers\Auth\StudentRegistrationController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/register/details', [RegistrationController::class, 'showDetails'])->name('register.details');
    Route::post('/register/details', [RegistrationController::class, 'complete'])->middleware('throttle:10,1');

    Route::get('/register/verify-otp', [RegistrationController::class, 'showOtp'])->name('register.otp');
    Route::post('/register/verify-otp', [RegistrationController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('/register/resend-otp', [RegistrationController::class, 'resendOtp'])
        ->middleware('throttle:5,1')
        ->name('register.otp.resend');

    Route::get('/verify-request', [VerificationRequestController::class, 'create'])->name('verification.create');
    Route::post('/verify-request', [VerificationRequestController::class, 'store'])->name('verification.store');

    // Password Reset via Email OTP
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])
        ->middleware('throttle:10,1')
        ->name('password.email');

    Route::get('/forgot-password/verify-otp', [PasswordResetController::class, 'showOtp'])->name('password.otp');
    Route::post('/forgot-password/verify-otp', [PasswordResetController::class, 'verifyOtp'])
        ->middleware('throttle:10,1')
        ->name('password.otp.verify');
    Route::post('/forgot-password/resend-otp', [PasswordResetController::class, 'resendOtp'])
        ->middleware('throttle:5,1')
        ->name('password.otp.resend');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('password.update');

    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/admin/login/verify-otp', [SessionController::class, 'showAdminOtp'])->name('admin.login.otp');
    Route::post('/admin/login/verify-otp', [SessionController::class, 'verifyAdminOtp'])
        ->middleware('throttle:10,1')
        ->name('admin.login.otp.verify');
    Route::post('/admin/login/resend-otp', [SessionController::class, 'resendAdminOtp'])
        ->middleware('throttle:5,1')
        ->name('admin.login.otp.resend');
    Route::post('/admin/login/cancel-otp', [SessionController::class, 'cancelAdminOtp'])
        ->name('admin.login.otp.cancel');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
