<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RestController;
use App\Http\Controllers\RrequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AdminAttendanceController;

//スタッフ会員登録、ログイン
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

//管理者ログイン
Route::get('/admin/login', [UserController::class, 'adminShow']);
Route::post('/admin/login', [AuthenticatedSessionController::class, 'adminStore']);

//スタッフ
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/start', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end', [AttendanceController::class, 'endWork']);
    Route::get('/attendance/list', [AttendanceController::class, 'listShow']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'detail'])
        ->where('id', '[0-9]+')
        ->name('attendance_detail');
    Route::put('/attendance/{id}', [AttendanceController::class, 'update']);
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'staffList']);

    Route::post('/attendance/rest-start', [RestController::class, 'startRest']);
    Route::post('/attendance/rest-end', [RestController::class, 'endRest']);
});


//管理者
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'adminListShow']);
    Route::get('/admin/attendance/{id}', [AttendanceController::class, 'adminDetail'])
        ->where('id', '[0-9]+')
        ->name('admin_attendance_detail');
    Route::put('/admin/attendance/{id}', [AdminAttendanceController::class, 'adminUpdate']);
    Route::get('/admin/stamp_correction_request/list', [AdminAttendanceController::class, 'adminList']);
});


