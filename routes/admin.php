<?php

use App\Http\Controllers\Admin\AchievementCategoryController;
use App\Http\Controllers\Admin\AchievementController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\AlumniImportController;
use App\Http\Controllers\Admin\AlumniNetworkController as AdminAlumniNetworkController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CommitteeController;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Now enforced with the existing role system (User::hasRole(), backed by
| the roles/role_user tables from Phase 1) via EnsureUserIsAdmin, applied
| directly by class name — no new role system, and no bootstrap/app.php
| edit needed to register a middleware alias.
*/

Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('index');

    Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni.index');
    Route::get('/alumni/{alumni}', [AlumniController::class, 'show'])->name('alumni.show');

    Route::prefix('batches')->name('batches.')->group(function () {
        Route::get('/', [BatchController::class, 'index'])->name('index');
        Route::get('/create', [BatchController::class, 'create'])->name('create');
        Route::post('/', [BatchController::class, 'store'])->name('store');
        Route::get('/{batch}/edit', [BatchController::class, 'edit'])->name('edit');
        Route::put('/{batch}', [BatchController::class, 'update'])->name('update');
        Route::patch('/{batch}/toggle-status', [BatchController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::get('/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('edit');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
        Route::patch('/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('publish');
        Route::patch('/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('unpublish');
    });

    Route::get('/alumni-import', [AlumniImportController::class, 'index'])->name('alumni-import.index');
    Route::post('/alumni-import/preview', [AlumniImportController::class, 'preview'])->name('alumni-import.preview');
    Route::post('/alumni-import/confirm', [AlumniImportController::class, 'confirm'])->name('alumni-import.confirm');

    Route::prefix('alumni-network')->name('alumni-network.')->group(function () {
        Route::get('/', [AdminAlumniNetworkController::class, 'index'])->name('index');
        Route::get('/create', [AdminAlumniNetworkController::class, 'create'])->name('create');
        Route::post('/', [AdminAlumniNetworkController::class, 'store'])->name('store');
        Route::get('/{alumni}/edit', [AdminAlumniNetworkController::class, 'edit'])->name('edit');
        Route::put('/{alumni}', [AdminAlumniNetworkController::class, 'update'])->name('update');
    });

    Route::prefix('verifications')->name('verifications.')->group(function () {
        Route::get('/', [VerificationController::class, 'index'])->name('index');
        Route::get('/{verificationRequest}', [VerificationController::class, 'show'])->name('show');
        Route::post('/{verificationRequest}/approve', [VerificationController::class, 'approve'])->name('approve');
        Route::post('/{verificationRequest}/reject', [VerificationController::class, 'reject'])->name('reject');
    });

    Route::prefix('alumni-verifications')->name('alumni-verifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AlumniVerificationController::class, 'index'])->name('index');
        Route::get('/{alumni}', [\App\Http\Controllers\Admin\AlumniVerificationController::class, 'show'])->name('show');
        Route::post('/{alumni}/approve', [\App\Http\Controllers\Admin\AlumniVerificationController::class, 'approve'])->name('approve');
        Route::post('/{alumni}/reject', [\App\Http\Controllers\Admin\AlumniVerificationController::class, 'reject'])->name('reject');
    });

    // Mentorship Management (Section 5, 8, 12, 13)
    Route::prefix('mentorship')->name('mentorship.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'index'])->name('index');
        Route::get('/applications', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'applications'])->name('applications');
        Route::get('/applications/{mentorProfile}', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'showApplication'])->name('applications.show');
        Route::patch('/applications/{mentorProfile}/approve', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'approveApplication'])->name('applications.approve');
        Route::patch('/applications/{mentorProfile}/reject', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'rejectApplication'])->name('applications.reject');

        Route::get('/mentors', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'mentors'])->name('mentors');
        Route::patch('/mentors/{mentorProfile}/suspend', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'suspendMentor'])->name('mentors.suspend');
        Route::patch('/mentors/{mentorProfile}/reactivate', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'reactivateMentor'])->name('mentors.reactivate');

        Route::get('/requests', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'requests'])->name('requests');

        Route::get('/categories', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'categories'])->name('categories');
        Route::post('/categories', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'updateCategory'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'toggleCategory'])->name('categories.toggle');
        Route::post('/categories/reorder', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'reorderCategories'])->name('categories.reorder');

        Route::get('/settings', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'settings'])->name('settings');
        Route::put('/settings', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'updateSettings'])->name('settings.update');

        Route::get('/overrides', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'overrides'])->name('overrides');
        Route::post('/overrides', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'storeOverride'])->name('overrides.store');
        Route::delete('/overrides/{override}', [\App\Http\Controllers\Admin\AdminMentorshipController::class, 'deleteOverride'])->name('overrides.destroy');
    });

    // Student Verification Management (Section 11)
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminStudentController::class, 'index'])->name('index');
        Route::get('/{student}', [\App\Http\Controllers\Admin\AdminStudentController::class, 'show'])->name('show');
        Route::patch('/{student}/verify', [\App\Http\Controllers\Admin\AdminStudentController::class, 'verify'])->name('verify');
        Route::patch('/{student}/reject', [\App\Http\Controllers\Admin\AdminStudentController::class, 'reject'])->name('reject');
        Route::post('/{student}/transition', [\App\Http\Controllers\Admin\AdminStudentController::class, 'transitionToAlumnus'])->name('transition');
    });

    Route::prefix('achievements')->name('achievements.')->group(function () {
        Route::get('/', [AchievementController::class, 'index'])->name('index');
        Route::patch('/{achievement}/approve', [AchievementController::class, 'approve'])->name('approve');
        Route::patch('/{achievement}/reject', [AchievementController::class, 'reject'])->name('reject');
        Route::delete('/{achievement}', [AchievementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('achievement-categories')->name('achievement-categories.')->group(function () {
        Route::get('/', [AchievementCategoryController::class, 'index'])->name('index');
        Route::get('/create', [AchievementCategoryController::class, 'create'])->name('create');
        Route::post('/', [AchievementCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [AchievementCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [AchievementCategoryController::class, 'update'])->name('update');
        Route::patch('/{category}/approve', [AchievementCategoryController::class, 'approve'])->name('approve');
        Route::patch('/{category}/reject', [AchievementCategoryController::class, 'reject'])->name('reject');
        Route::patch('/{category}/toggle-active', [AchievementCategoryController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/{category}', [AchievementCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/', [JobController::class, 'index'])->name('index');
        Route::get('/create', [JobController::class, 'create'])->name('create');
        Route::post('/', [JobController::class, 'store'])->name('store');
        Route::get('/{job}/edit', [JobController::class, 'edit'])->name('edit');
        Route::put('/{job}', [JobController::class, 'update'])->name('update');
        Route::patch('/{job}/publish', [JobController::class, 'publish'])->name('publish');
        Route::patch('/{job}/close', [JobController::class, 'close'])->name('close');
        Route::delete('/{job}', [JobController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('/create', [EventController::class, 'create'])->name('create');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::patch('/{event}/publish', [EventController::class, 'publish'])->name('publish');
        Route::patch('/{event}/unpublish', [EventController::class, 'unpublish'])->name('unpublish');
        Route::patch('/{event}/cancel', [EventController::class, 'cancel'])->name('cancel');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('gallery/albums')->name('gallery.albums.')->group(function () {
        Route::get('/', [GalleryController::class, 'index'])->name('index');
        Route::get('/create', [GalleryController::class, 'create'])->name('create');
        Route::post('/', [GalleryController::class, 'store'])->name('store');
        Route::get('/{album}/edit', [GalleryController::class, 'edit'])->name('edit');
        Route::put('/{album}', [GalleryController::class, 'update'])->name('update');
        Route::patch('/{album}/publish', [GalleryController::class, 'publish'])->name('publish');
        Route::patch('/{album}/unpublish', [GalleryController::class, 'unpublish'])->name('unpublish');
        Route::delete('/{album}', [GalleryController::class, 'destroy'])->name('destroy');
        Route::post('/{album}/photos', [GalleryController::class, 'storePhoto'])->name('photos.store');
        Route::delete('/{album}/photos/{photo}', [GalleryController::class, 'destroyPhoto'])->name('photos.destroy');
    });

    Route::prefix('elections')->name('elections.')->group(function () {
        Route::get('/', [ElectionController::class, 'index'])->name('index');
        Route::get('/create', [ElectionController::class, 'create'])->name('create');
        Route::post('/', [ElectionController::class, 'store'])->name('store');
        Route::get('/{election}', [ElectionController::class, 'show'])->name('show');
        Route::get('/{election}/edit', [ElectionController::class, 'edit'])->name('edit');
        Route::put('/{election}', [ElectionController::class, 'update'])->name('update');
        Route::delete('/{election}', [ElectionController::class, 'destroy'])->name('destroy');
        Route::post('/{election}/positions', [ElectionController::class, 'storePosition'])->name('positions.store');
        Route::delete('/{election}/positions/{position}', [ElectionController::class, 'destroyPosition'])->name('positions.destroy');
        Route::post('/{election}/candidates', [ElectionController::class, 'storeCandidate'])->name('candidates.store');
        Route::put('/{election}/candidates/{candidate}', [ElectionController::class, 'updateCandidate'])->name('candidates.update');
        Route::delete('/{election}/candidates/{candidate}', [ElectionController::class, 'destroyCandidate'])->name('candidates.destroy');
        Route::patch('/{election}/start', [ElectionController::class, 'start'])->name('start');
        Route::patch('/{election}/close', [ElectionController::class, 'close'])->name('close');
        Route::post('/{election}/resolve-tie', [ElectionController::class, 'resolveTie'])->name('resolve-tie');
        Route::post('/{election}/select-physical-winner', [ElectionController::class, 'selectPhysicalWinner'])->name('select-physical-winner');
        Route::patch('/{election}/finalize', [ElectionController::class, 'finalize'])->name('finalize');
        Route::post('/{election}/archive', [ElectionController::class, 'archive'])->name('archive');
        Route::post('/{election}/unarchive', [ElectionController::class, 'unarchive'])->name('unarchive');
    });

    Route::prefix('committees')->name('committees.')->group(function () {
        Route::get('/', [CommitteeController::class, 'index'])->name('index');
        Route::get('/create', [CommitteeController::class, 'create'])->name('create');
        Route::post('/', [CommitteeController::class, 'store'])->name('store');
        Route::get('/{committee}', [CommitteeController::class, 'show'])->name('show');
        Route::get('/{committee}/edit', [CommitteeController::class, 'edit'])->name('edit');
        Route::put('/{committee}', [CommitteeController::class, 'update'])->name('update');
        Route::delete('/{committee}', [CommitteeController::class, 'destroy'])->name('destroy');
        Route::patch('/{committee}/activate', [CommitteeController::class, 'activate'])->name('activate');
        Route::post('/{committee}/members', [CommitteeController::class, 'storeMember'])->name('members.store');
        Route::put('/{committee}/members/{member}', [CommitteeController::class, 'updateMember'])->name('members.update');
        Route::delete('/{committee}/members/{member}', [CommitteeController::class, 'destroyMember'])->name('members.destroy');
    });

    // Super Admin only — nested on top of the group's existing
    // EnsureUserIsAdmin gate, so a plain Admin hitting these URLs
    // directly (not just via a hidden link) gets 403 from this
    // middleware, never reaching AdminManagementController at all.
    Route::middleware('role:super-admin')->prefix('system')->name('system.')->group(function () {
        Route::prefix('admins')->name('admins.')->group(function () {
            Route::get('/', [AdminManagementController::class, 'index'])->name('index');
            Route::get('/create', [AdminManagementController::class, 'create'])->name('create');
            Route::post('/', [AdminManagementController::class, 'store'])->name('store');
            Route::patch('/{target}/suspend', [AdminManagementController::class, 'suspend'])->name('suspend');
            Route::patch('/{target}/restore', [AdminManagementController::class, 'restore'])->name('restore');
            Route::delete('/{target}/role', [AdminManagementController::class, 'removeRole'])->name('remove-role');
        });
        Route::get('/audit-log', [AdminManagementController::class, 'auditLog'])->name('audit-log.index');
    });
});
