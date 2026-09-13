<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AhpController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\AlternativeController;
use App\Http\Controllers\Admin\AlternativeProfileController;
use App\Http\Controllers\TestController;

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

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tests', [TestController::class, 'index'])->name('tests.index');
    Route::get('/tests/create', [TestController::class, 'create'])->name('tests.create');
    Route::post('/tests', [TestController::class, 'store'])->name('tests.store');
    Route::get('/tests/{testSession}', [TestController::class, 'show'])->name('tests.show');
    Route::post('/tests/{testSession}/answers', [TestController::class, 'saveAnswer'])->name('tests.answers.save');
    Route::post('/tests/{testSession}/complete', [TestController::class, 'complete'])->name('tests.complete');
    Route::post('/tests/{testSession}/recalculate', [TestController::class, 'recalculate'])->name('tests.recalculate');
});

Route::middleware(['auth', 'verified','role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Modul Bobot AHP
    Route::get('/ahp/criteria', [AhpController::class, 'criteriaIndex'])->name('ahp.criteria.index');
    Route::post('/ahp/criteria', [AhpController::class, 'criteriaStore'])->name('ahp.criteria.store');
    Route::get('/ahp/criteria/{criteria}/sub-criteria', [AhpController::class, 'subCriteriaIndex'])->name('ahp.sub-criteria.index');
    Route::post('/ahp/criteria/{criteria}/sub-criteria', [AhpController::class, 'subCriteriaStore'])->name('ahp.sub-criteria.store');

    // Modul Bank Soal
    Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/sub-criteria/{subCriteria}', [QuestionController::class, 'manage'])->name('questions.manage');
    Route::post('/questions/sub-criteria/{subCriteria}', [QuestionController::class, 'store'])->name('questions.store');
    Route::patch('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::patch('/questions/{question}/toggle-active', [QuestionController::class, 'toggleActive'])->name('questions.toggle-active');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('/questions/sub-criteria/{subCriteria}/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');

    // Modul Alternatif
    Route::get('/alternatives', [AlternativeController::class, 'index'])->name('alternatives.index');
    Route::get('/alternatives/create', [AlternativeController::class, 'create'])->name('alternatives.create');
    Route::post('/alternatives', [AlternativeController::class, 'store'])->name('alternatives.store');
    Route::get('/alternatives/{alternative}/edit', [AlternativeController::class, 'edit'])->name('alternatives.edit');
    Route::put('/alternatives/{alternative}', [AlternativeController::class, 'update'])->name('alternatives.update');
    Route::patch('/alternatives/{alternative}/toggle-active', [AlternativeController::class, 'toggleActive'])->name('alternatives.toggle-active');
    Route::delete('/alternatives/{alternative}', [AlternativeController::class, 'destroy'])->name('alternatives.destroy');

    // Modul Profil Ideal Alternatif
    Route::get('/alternatives/{alternative}/profile', [AlternativeProfileController::class, 'edit'])->name('alternative-profiles.edit');
    Route::put('/alternatives/{alternative}/profile', [AlternativeProfileController::class, 'update'])->name('alternative-profiles.update');
});

require __DIR__.'/auth.php';
