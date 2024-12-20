<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RestController;
use App\Http\Controllers\RrequestController;
use App\Http\Controllers\ApprovalController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/start', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end', [AttendanceController::class, 'endWork']);

    Route::post('/attendance/rest-start', [RestController::class, 'startRest']);
    Route::post('/attendance/rest-end', [RestController::class, 'endRest']);
});
