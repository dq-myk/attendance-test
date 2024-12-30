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


Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    //スタッフ
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/start', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end', [AttendanceController::class, 'endWork']);
    Route::get('/attendance/list', [AttendanceController::class, 'listShow']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'detail']);
    // Route::put('/attendance/{id}', [AttendanceController::class, 'update']);

    Route::post('/attendance/rest-start', [RestController::class, 'startRest']);
    Route::post('/attendance/rest-end', [RestController::class, 'endRest']);



    //管理者
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'adminList']);
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'adminDetail']);
});


