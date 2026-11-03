<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AlumniDirectoryController;
use App\Http\Controllers\AlumniNetworkController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\ElectionResultController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Web Routes
|--------------------------------------------------------------------------
| Phase 1: static/foundation pages. Batches and Events still render
| placeholder views — full functionality for those is a later phase.
| Alumni Directory is real as of Phase 4 (AlumniDirectoryController),
| reachable by guests — no auth middleware.
*/

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/alumni', [AlumniDirectoryController::class, 'index'])->name('alumni.directory');
Route::get('/alumni-network', [AlumniNetworkController::class, 'index'])->name('alumni.network');
Route::get('/network', [AlumniNetworkController::class, 'index']);
Route::get('/alumni/{alumni}', [AlumniDirectoryController::class, 'show'])->name('alumni.show');
Route::get('/batches', [PageController::class, 'batches'])->name('batches.index');
Route::get('/batches/{batch}', [PageController::class, 'batch'])->name('batches.show');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/gallery/{album}', [GalleryController::class, 'show'])->name('gallery.show');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
Route::get('/jobs', [JobListingController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [JobListingController::class, 'show'])->name('jobs.show');
Route::get('/elections/results', [ElectionResultController::class, 'index'])->name('elections.results.index');
Route::get('/elections/results/{election}', [ElectionResultController::class, 'show'])->name('elections.results.show');
Route::get('/committees', [CommitteeController::class, 'index'])->name('committees.index');
Route::get('/committees/history', [CommitteeController::class, 'history'])->name('committees.history');
Route::get('/committees/{committee}', [CommitteeController::class, 'show'])->name('committees.show');
Route::get('/verify-alumni/{token}', [\App\Http\Controllers\PublicAlumniVerificationController::class, 'verify'])->name('alumni.verify.qr');

require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/admin.php';
require __DIR__.'/batch-rep.php';
