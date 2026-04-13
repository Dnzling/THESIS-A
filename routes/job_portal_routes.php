<?php

use App\Http\Controllers\Api\Hr\ApplicantPortalController;
use App\Http\Controllers\Api\Hr\ApplicantProfileController;
use App\Http\Controllers\Api\Hr\JobPortalAuthController;
use App\Http\Controllers\Api\Hr\JobPortalController;
use App\Http\Controllers\Api\Hr\RecruitmentController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('job-portal')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [JobPortalAuthController::class, 'register']);
        Route::post('/login', [JobPortalAuthController::class, 'login']);
        Route::post('/verify-otp', [VerifyEmailController::class, 'verifyOtpApi']);
        Route::post('/resend-otp', [VerifyEmailController::class, 'resendOtpApi']);
    });

    Route::get('/postings', [JobPortalController::class, 'index']);
    Route::get('/postings/{posting}', [JobPortalController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('job-portal')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/me', [JobPortalAuthController::class, 'me']);
        Route::post('/logout', [JobPortalAuthController::class, 'logout']);
    });

    Route::get('/applications', [ApplicantPortalController::class, 'index']);
    Route::get('/applications/{application}', [ApplicantPortalController::class, 'show']);
    Route::get('/applications/{application}/documents/{document}', [ApplicantPortalController::class, 'downloadDocument']);
    Route::post('/postings/{posting}/apply', [ApplicantPortalController::class, 'apply']);

    Route::get('/profile', [ApplicantProfileController::class, 'show']);
    Route::put('/profile', [ApplicantProfileController::class, 'upsert']);
    Route::post('/profile/documents', [ApplicantProfileController::class, 'uploadDocument']);
    Route::delete('/profile/documents/{document}', [ApplicantProfileController::class, 'deleteDocument']);
    Route::get('/profile/documents/{document}/download', [ApplicantProfileController::class, 'downloadDocument']);
    Route::post('/profile/email-change', [ApplicantProfileController::class, 'requestEmailChange']);
    Route::post('/profile/email-verify', [ApplicantProfileController::class, 'verifyEmailChange']);

    Route::post('/recruitment/applications/{application}/schedule-interview', [RecruitmentController::class, 'scheduleInterview']);
    Route::post('/recruitment/applications/{application}/reject', [RecruitmentController::class, 'rejectApplicant']);
    Route::post('/recruitment/applications/{application}/hire', [RecruitmentController::class, 'hireApplicant']);
});
