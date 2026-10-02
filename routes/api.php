<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Autentikasi publik dengan rate limiting 5/menit untuk mencegah brute-force
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Buat testing CP 1
    // Versi raw atau model mentah
    Route::get('/test-user-raw', function(){
        return \App\Models\User::first();
    });
    // Versi resource API
    Route::get('/test-user-resource', function(){
        return new \App\Http\Resources\UserResource(
            \App\Models\User::first()
            );
    });


    // Endpoint terlindungi dengan Sanctum dan rate limiting umum 60/menit
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Courses
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('/courses/{course}/materials', [CourseController::class, 'materials']);
        Route::get('/courses/{course}/assignments', [CourseController::class, 'assignments']);

        // Assignments
        Route::post('/assignments', [AssignmentController::class, 'store']);
        Route::match(['put', 'patch'], '/assignments/{assignment}', [AssignmentController::class, 'update']);
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);
        Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions']);
        Route::post('/assignments/{assignment}/submissions', [AssignmentController::class, 'storeSubmission']);

        // Submissions & Grading
        Route::put('/submissions/{submission}/grade', [SubmissionController::class, 'grade']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    });
});
