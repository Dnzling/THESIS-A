<?php

use App\Http\Controllers\Api\JobPostingController;
use App\Http\Controllers\Api\JobApplicationController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\JobOfferController;
use Illuminate\Support\Facades\Route;

// ========== JOB HIRING SYSTEM ROUTES ==========

Route::prefix('job-postings')->group(function () {
    // Job Postings CRUD
    Route::get('/', [JobPostingController::class, 'index']);
    Route::post('/', [JobPostingController::class, 'store']);
    Route::get('/{posting}', [JobPostingController::class, 'show']);
    Route::put('/{posting}', [JobPostingController::class, 'update']);
    Route::delete('/{posting}', [JobPostingController::class, 'destroy']);

    // Screening Stages Management
    Route::put('/{posting}/screening-stages', [JobPostingController::class, 'updateScreeningStages']);

    // Applications for a specific job posting
    Route::prefix('{posting}/applications')->group(function () {
        Route::get('/', [JobApplicationController::class, 'index']);
        Route::post('/', [JobApplicationController::class, 'store']);
    });
});

Route::prefix('job-applications')->group(function () {
    // Application Details
    Route::get('/{application}', [JobApplicationController::class, 'show']);

    // Update Application Status
    Route::put('/{application}/status', [JobApplicationController::class, 'updateStatus']);

    // Document Management
    Route::get('/{application}/documents/{document}', [JobApplicationController::class, 'downloadDocument']);

    // Delete Application
    Route::delete('/{application}', [JobApplicationController::class, 'destroy']);
});

Route::prefix('interviews')->group(function () {
    // Get interviews for an application
    Route::get('/application/{application}', [InterviewController::class, 'indexByApplication']);

    // Interview CRUD
    Route::post('/', [InterviewController::class, 'store']);
    Route::get('/{interview}', [InterviewController::class, 'show']);
    Route::put('/{interview}', [InterviewController::class, 'update']);
    Route::delete('/{interview}', [InterviewController::class, 'destroy']);

    // Bulk update interviews
    Route::put('/application/{application}/bulk-update', [InterviewController::class, 'bulkUpdate']);
});

Route::prefix('job-offers')->group(function () {
    // Offer Management
    Route::post('/', [JobOfferController::class, 'store']);
    Route::get('/{offer}', [JobOfferController::class, 'show']);
    Route::put('/{offer}', [JobOfferController::class, 'update']);
    Route::delete('/{offer}', [JobOfferController::class, 'destroy']);

    // Offer Actions
    Route::post('/{offer}/accept', [JobOfferController::class, 'accept']);
    Route::post('/{offer}/decline', [JobOfferController::class, 'decline']);
});
