<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LifeEventController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RelationshipController;
use App\Http\Controllers\RelationshipPathController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TreeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::match(['get', 'post'], '/invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [FamilyController::class, 'dashboard'])->name('dashboard');

    Route::get('/families', [FamilyController::class, 'index'])->name('families.index');
    Route::get('/families/create', [FamilyController::class, 'create'])->name('families.create');
    Route::post('/families', [FamilyController::class, 'store'])->name('families.store');
    Route::post('/families/{family}/switch', [FamilyController::class, 'switch'])->name('families.switch');
    Route::patch('/families/{family}', [FamilyController::class, 'update'])->name('families.update');
    Route::post('/families/{family}/invite', [InvitationController::class, 'store'])->name('families.invite');

    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::get('/people/create', [PersonController::class, 'create'])->name('people.create');
    Route::get('/people/search', [PersonController::class, 'search'])->name('people.search');
    Route::post('/people', [PersonController::class, 'store'])->name('people.store');
    Route::get('/people/{person}', [PersonController::class, 'show'])->name('people.show');
    Route::get('/people/{person}/edit', [PersonController::class, 'edit'])->name('people.edit');
    Route::match(['patch', 'post'], '/people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::delete('/people/{person}', [PersonController::class, 'destroy'])->name('people.destroy');

    Route::post('/relationships', [RelationshipController::class, 'store'])->name('relationships.store');
    Route::patch('/relationships/{relationship}', [RelationshipController::class, 'update'])->name('relationships.update');
    Route::delete('/relationships/{relationship}', [RelationshipController::class, 'destroy'])->name('relationships.destroy');

    Route::get('/tree', [TreeController::class, 'show'])->name('tree.show');
    Route::get('/path', [RelationshipPathController::class, 'show'])->name('path.show');

    Route::get('/timeline', [LifeEventController::class, 'index'])->name('timeline.index');
    Route::post('/events', [LifeEventController::class, 'store'])->name('events.store');
    Route::delete('/events/{lifeEvent}', [LifeEventController::class, 'destroy'])->name('events.destroy');

    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/file/{path}', [MediaController::class, 'serve'])->where('path', '.*')->name('media.serve');

    Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
    Route::post('/sources', [SourceController::class, 'store'])->name('sources.store');
    Route::post('/citations', [SourceController::class, 'attach'])->name('citations.store');

    Route::get('/export', [ExportController::class, 'index'])->name('export.index');
    Route::get('/export/download', [ExportController::class, 'download'])->name('export.download');

    Route::get('/settings', [FamilyController::class, 'settings'])->name('settings.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
