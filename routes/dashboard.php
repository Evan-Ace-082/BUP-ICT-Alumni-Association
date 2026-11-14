<?php

use App\Http\Controllers\Dashboard\AchievementController;
use App\Http\Controllers\Dashboard\AnnouncementController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ElectionVotingController;
use App\Http\Controllers\Dashboard\JobController;
use App\Http\Controllers\Dashboard\MentorshipController;
use App\Http\Controllers\Dashboard\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
| The /dashboard route is named 'dashboard' to match what
| SessionController::store() redirects to, and dispatches directly to
| the existing DashboardController@index — no redirect, no closure,
| no duplicate route.
*/

Route::middleware('auth')->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware('auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/digital-id', [\App\Http\Controllers\Dashboard\DigitalIdController::class, 'show'])->name('digital-id');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/batch', [DashboardController::class, 'batch'])->name('batch');
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/notifications/{notification}/read', [AnnouncementController::class, 'markRead'])->name('notifications.read');

    // Mentorship Routes (Redesigned)
    Route::prefix('mentorship')->name('mentorship.')->group(function () {
        Route::get('/', [MentorshipController::class, 'index'])->name('index');
        Route::get('/become-a-mentor', [MentorshipController::class, 'becomeMentor'])->name('become-mentor');
        Route::post('/become-a-mentor', [MentorshipController::class, 'storeMentorProfile'])->name('store-profile');
        Route::get('/mentor-hub', [MentorshipController::class, 'mentorDashboard'])->name('mentor');
        Route::get('/mentee-hub', [MentorshipController::class, 'menteeDashboard'])->name('mentee');
        Route::put('/availability', [MentorshipController::class, 'updateAvailability'])->name('update-availability');
        Route::put('/profile', [MentorshipController::class, 'updateProfile'])->name('update-profile');
        Route::get('/mentors/{mentor}', [MentorshipController::class, 'show'])->name('show');
        Route::post('/mentors/{mentor}/request', [MentorshipController::class, 'sendRequest'])->name('request.mentor');

        // Legacy route compatibility for older tests
        Route::post('/{alumni}/request', [MentorshipController::class, 'sendRequest'])->name('request');

        Route::prefix('requests')->name('requests.')->group(function () {
            Route::patch('/{mentorshipRequest}/accept', [MentorshipController::class, 'accept'])->name('accept');
            Route::patch('/{mentorshipRequest}/decline', [MentorshipController::class, 'decline'])->name('decline');
            Route::patch('/{mentorshipRequest}/reject', [MentorshipController::class, 'reject'])->name('reject');
            Route::patch('/{mentorshipRequest}/cancel', [MentorshipController::class, 'cancel'])->name('cancel');
            Route::patch('/{mentorshipRequest}/complete', [MentorshipController::class, 'complete'])->name('complete');
        });
    });

    Route::prefix('achievements')->name('achievements.')->group(function () {
        Route::get('/', [AchievementController::class, 'index'])->name('index');
        Route::get('/create', [AchievementController::class, 'create'])->name('create');
        Route::post('/', [AchievementController::class, 'store'])->name('store');
        Route::get('/{achievement}/edit', [AchievementController::class, 'edit'])->name('edit');
        Route::put('/{achievement}', [AchievementController::class, 'update'])->name('update');
        Route::delete('/{achievement}', [AchievementController::class, 'destroy'])->name('destroy');
        Route::post('/suggest-category', [AchievementController::class, 'suggestCategory'])->name('suggest-category');
    });

    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/', [JobController::class, 'index'])->name('index');
        Route::get('/create', [JobController::class, 'create'])->name('create');
        Route::post('/', [JobController::class, 'store'])->name('store');
        Route::get('/{job}/edit', [JobController::class, 'edit'])->name('edit');
        Route::put('/{job}', [JobController::class, 'update'])->name('update');
        Route::patch('/{job}/close', [JobController::class, 'close'])->name('close');
    });

    Route::prefix('elections')->name('elections.')->group(function () {
        Route::get('/', [ElectionVotingController::class, 'index'])->name('index');
        Route::get('/{election}', [ElectionVotingController::class, 'show'])->name('show');
        Route::post('/{election}/vote', [ElectionVotingController::class, 'vote'])->name('vote');
    });
});
