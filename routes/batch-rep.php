<?php

use App\Http\Controllers\BatchRep\AlumniController;
use App\Http\Controllers\BatchRep\AnnouncementController;
use App\Http\Controllers\BatchRep\EventController;
use App\Http\Controllers\BatchRep\GalleryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Batch Representative Routes
|--------------------------------------------------------------------------
| Uses the existing role system via the 'role' middleware alias
| (EnsureUserHasRole, registered in bootstrap/app.php in Phase 3 Step 1)
| — no new authorization mechanism. Every action inside AlumniController
| is additionally scoped to the representative's own assigned_batch_id
| and re-checked against AlumniPolicy, so this middleware is one layer
| of defense, not the only one.
*/

Route::middleware(['auth', 'role:batch-representative'])
    ->prefix('batch-rep')
    ->name('batch-rep.')
    ->group(function () {
        Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni.index');
        Route::get('/alumni/{alumni}/edit', [AlumniController::class, 'edit'])->name('alumni.edit');
        Route::put('/alumni/{alumni}', [AlumniController::class, 'update'])->name('alumni.update');

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
            Route::post('/', [AnnouncementController::class, 'store'])->name('store');
            Route::get('/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('edit');
            Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
            Route::patch('/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('publish');
            Route::patch('/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('unpublish');
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
        });

        Route::prefix('gallery/albums')->name('gallery.albums.')->group(function () {
            Route::get('/', [GalleryController::class, 'index'])->name('index');
            Route::get('/create', [GalleryController::class, 'create'])->name('create');
            Route::post('/', [GalleryController::class, 'store'])->name('store');
            Route::get('/{album}/edit', [GalleryController::class, 'edit'])->name('edit');
            Route::put('/{album}', [GalleryController::class, 'update'])->name('update');
            Route::patch('/{album}/publish', [GalleryController::class, 'publish'])->name('publish');
            Route::patch('/{album}/unpublish', [GalleryController::class, 'unpublish'])->name('unpublish');
            Route::post('/{album}/photos', [GalleryController::class, 'storePhoto'])->name('photos.store');
            Route::delete('/{album}/photos/{photo}', [GalleryController::class, 'destroyPhoto'])->name('photos.destroy');
        });
    });
