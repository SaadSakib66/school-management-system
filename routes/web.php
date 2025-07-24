<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->group(function () {
    Route::get('login', [AdminController::class, 'loginPage'])->name('admin.login.page');
    Route::post('login', [AdminController::class, 'login'])->name('admin.login');
    Route::get('logout', [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('forgot-password', [AdminController::class, 'forgotPassword'])->name('admin.forgotPassword');
});

/*
|--------------------------------------------------------------------------
| Admin Protected Routes (Requires admin middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('admin/list', [AdminController::class, 'list'])->name('admin.admin.list');
    Route::get('admin/add', [AdminController::class, 'add'])->name('admin.admin.add');
});

/*
|--------------------------------------------------------------------------
| Teacher Protected Routes
|--------------------------------------------------------------------------
*/
Route::prefix('teacher')->middleware(['auth', 'teacher'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('teacher.dashboard');
});

/*
|--------------------------------------------------------------------------
| Student Protected Routes
|--------------------------------------------------------------------------
*/
Route::prefix('student')->middleware(['auth', 'student'])->group(function () {
 Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('student.dashboard');
});

/*
|--------------------------------------------------------------------------
| Parent Protected Routes
|--------------------------------------------------------------------------
*/
Route::prefix('parent')->middleware(['auth', 'parent'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('parent.dashboard');
});
