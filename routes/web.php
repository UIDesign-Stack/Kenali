<?php

use App\Http\Controllers\Admin\AhpController;
use App\Http\Controllers\Admin\AlternativeController;
use App\Http\Controllers\Admin\AlternativeProfileController;
use App\Http\Controllers\Admin\PsychologistManagementController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PsychologistConsultationController;
use App\Http\Controllers\TestController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Schedule::command('consultations:revert-overdue')->hourly();

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:user'])->group(function () {

    Route::get('/tests', [TestController::class, 'index'])->name('tests.index');
    Route::get('/tests/create', [TestController::class, 'create'])->name('tests.create');
    Route::post('/tests', [TestController::class, 'store'])->name('tests.store');
    Route::get('/tests/{testSession}', [TestController::class, 'show'])->name('tests.show');
    Route::post('/tests/{testSession}/answers', [TestController::class, 'saveAnswer'])->name('tests.answers.save');
    Route::post('/tests/{testSession}/complete', [TestController::class, 'complete'])->name('tests.complete');
    Route::post('/tests/{testSession}/recalculate', [TestController::class, 'recalculate'])->name('tests.recalculate');

    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
    Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
    Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');
    Route::post('/consultations/{consultation}/messages', [ConsultationController::class, 'sendMessage'])->name('consultations.messages.send');
});


Route::middleware(['auth', 'verified', 'role:psikolog'])->prefix('psikolog')->name('psikolog.')->group(function () {
    Route::get('/consultations', [PsychologistConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/consultations/{consultation}', [PsychologistConsultationController::class, 'show'])->name('consultations.show');
    Route::patch('/consultations/{consultation}/status', [PsychologistConsultationController::class, 'updateStatus'])->name('consultations.update-status');
    Route::post('/consultations/{consultation}/messages', [PsychologistConsultationController::class, 'sendMessage'])->name('consultations.messages.send');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Bobot AHP
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

    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create-staff', [UserManagementController::class, 'createStaff'])->name('users.create-staff');
    Route::post('/users/create-staff', [UserManagementController::class, 'storeStaff'])->name('users.store-staff');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');

    Route::get('/psychologists', [PsychologistManagementController::class, 'index'])->name('psychologists.index');
    Route::patch('/psychologists/{psychologistProfile}/toggle-verified', [PsychologistManagementController::class, 'toggleVerified'])->name('psychologists.toggle-verified');
    Route::patch('/psychologists/{psychologistProfile}/toggle-available', [PsychologistManagementController::class, 'toggleAvailable'])->name('psychologists.toggle-available');

    Route::get('/psychologists/{psychologistProfile}/consultations', [PsychologistManagementController::class, 'consultations'])->name('psychologists.consultations');
    Route::patch('/consultations/{consultation}/force-cancel', [PsychologistManagementController::class, 'forceCancelConsultation'])->name('consultations.force-cancel');
});

require __DIR__.'/auth.php';