<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RestController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AdminAttendanceController;

// メール認証の通知を再送信
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth'])->name('verification.send');

// メール認証確認
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance');
})->middleware(['auth', 'signed'])->name('verification.verify');

// メール認証が必要なページ
Route::get('/attendance', function () {
    return view('/attendance');
})->middleware(['auth', 'verified']);

//スタッフ会員登録、ログイン
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

//管理者ログイン
Route::get('/admin/login', [UserController::class, 'adminShow']);
Route::post('/admin/login', [AuthenticatedSessionController::class, 'adminStore']);

//ログアウト
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

//管理者、スタッフ共通処理
Route::get('/stamp_correction_request/list', [RequestController::class, 'requestList']);

//スタッフ
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/start', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end', [AttendanceController::class, 'endWork']);
    Route::get('/attendance/list', [AttendanceController::class, 'listShow']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'detail'])
        ->where('id', '[0-9]+')
        ->name('attendance_detail');
    Route::put('/attendance/{id}', [AttendanceController::class, 'update']);

    Route::post('/attendance/rest-start', [RestController::class, 'startRest']);
    Route::post('/attendance/rest-end', [RestController::class, 'endRest']);
});


//管理者
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'adminListShow']);
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'adminDetail'])
        ->where('id', '[0-9]+')
        ->name('admin_attendance_detail');
    Route::put('/admin/attendance/{id}', [AdminAttendanceController::class, 'adminUpdate']);
    Route::get('/admin/staff/list', [AdminAttendanceController::class, 'adminStaffList']);
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'adminAttendanceStaff']);
    Route::get('/admin/attendance/staff/{id}/output', [AdminAttendanceController::class, 'output']);
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [ApprovalController::class, 'showApprove']);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [ApprovalController::class, 'adminApprove']);

});


