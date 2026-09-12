<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AhpController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\AlternativeController;
use App\Http\Controllers\Admin\AlternativeProfileController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/ahp/criteria', [AhpController::class, 'criteriaIndex'])->name('ahp.criteria.index');
    Route::post('/ahp/criteria', [AhpController::class, 'criteriaStore'])->name('ahp.criteria.store');

    Route::get('/ahp/criteria/{criteria}/sub-criteria', [AhpController::class, 'subCriteriaIndex'])->name('ahp.sub-criteria.index');
    Route::post('/ahp/criteria/{criteria}/sub-criteria', [AhpController::class, 'subCriteriaStore'])->name('ahp.sub-criteria.store');

    Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/sub-criteria/{subCriteria}', [QuestionController::class, 'manage'])->name('questions.manage');
    Route::post('/questions/sub-criteria/{subCriteria}', [QuestionController::class, 'store'])->name('questions.store');
    Route::patch('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::patch('/questions/{question}/toggle-active', [QuestionController::class, 'toggleActive'])->name('questions.toggle-active');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('/questions/sub-criteria/{subCriteria}/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');

    Route::get('/alternatives', [AlternativeController::class, 'index'])->name('alternatives.index');
    Route::get('/alternatives/create', [AlternativeController::class, 'create'])->name('alternatives.create');
    Route::post('/alternatives', [AlternativeController::class, 'store'])->name('alternatives.store');
    Route::get('/alternatives/{alternative}/edit', [AlternativeController::class, 'edit'])->name('alternatives.edit');
    Route::put('/alternatives/{alternative}', [AlternativeController::class, 'update'])->name('alternatives.update');
    Route::patch('/alternatives/{alternative}/toggle-active', [AlternativeController::class, 'toggleActive'])->name('alternatives.toggle-active');
    Route::delete('/alternatives/{alternative}', [AlternativeController::class, 'destroy'])->name('alternatives.destroy');

    Route::get('/alternatives/{alternative}/profile', [AlternativeProfileController::class, 'edit'])->name('alternative-profiles.edit');
    Route::put('/alternatives/{alternative}/profile', [AlternativeProfileController::class, 'update'])->name('alternative-profiles.update');

      Route::get('/alternatives/{alternative}/profile', [AlternativeProfileController::class, 'edit'])->name('alternative-profiles.edit');
    Route::put('/alternatives/{alternative}/profile', [AlternativeProfileController::class, 'update'])->name('alternative-profiles.update');
});

require __DIR__.'/auth.php';
