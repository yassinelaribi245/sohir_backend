<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamQuestionController;
use App\Http\Controllers\Enseignant\CoursController;

/* ----------------------------------------------------------
   1.  AUTH  (no prefix)
---------------------------------------------------------- */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

/* ----------------------------------------------------------
   2.  PUBLIC  (no prefix)
---------------------------------------------------------- */
Route::get('/public-courses', [PublicController::class, 'courses']);
Route::get('/public-courses/{id}', [PublicController::class, 'show']);

/* ----------------------------------------------------------
   3.  STUDENT  (auth only)
---------------------------------------------------------- */
Route::middleware(['auth:sanctum', 'student'])->group(function () {
    Route::get('/student/profile', [StudentController::class, 'profile']);
    Route::put('/student/profile', [StudentController::class, 'updateProfile']);
    Route::post('/student/join-request/{class}', [StudentController::class, 'joinRequest']);
    Route::get('/student/my-requests', [StudentController::class, 'myRequests']);
    Route::post('/student/quiz/{quiz}/attempt', [StudentController::class, 'attemptQuiz']);
    Route::post('/student/exam/{exam}/attempt', [StudentController::class, 'attemptExam']);
    Route::get('/student/my-grades', [StudentController::class, 'myGrades']);
});

/* ----------------------------------------------------------
   4.  TEACHER  (auth only)
---------------------------------------------------------- */
Route::middleware(['auth:sanctum', 'teacher'])->group(function () {
    Route::get('/teacher/join-requests', [TeacherController::class, 'joinRequests']);
    Route::post('/teacher/join-requests/{request}/accept', [TeacherController::class, 'acceptRequest']);
    Route::post('/teacher/join-requests/{request}/reject', [TeacherController::class, 'rejectRequest']);
    Route::get('/teacher/my-classes', [TeacherController::class, 'myClasses']);
    Route::get('/teacher/class/{class}/students', [TeacherController::class, 'classStudents']);
    Route::delete('/teacher/class/{class}/student/{student}', [TeacherController::class, 'removeStudent']);
    Route::apiResource('teacher/quiz', QuizController::class);
    Route::apiResource('teacher/exam', ExamController::class);
    Route::apiResource('teacher/question.quiz', QuizQuestionController::class);
    Route::apiResource('teacher/question.exam', ExamQuestionController::class);
    Route::get('/teacher/quiz/{quiz}/results', [TeacherController::class, 'quizResults']);
    Route::get('/teacher/exam/{exam}/results', [TeacherController::class, 'examResults']);
});

/* ----------------------------------------------------------
   5.  ADMIN  (auth only)
---------------------------------------------------------- */
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('admin/users', AdminUserController::class);
    Route::get('/admin/stats', [AdminController::class, 'stats']);
});

/* ----------------------------------------------------------
   6.  ENSEIGNANT/COURS  (original teacher course CRUD)
---------------------------------------------------------- */
Route::prefix('enseignant/cours')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/', [CoursController::class, 'store']);
    Route::get('/', [CoursController::class, 'index']);
    Route::get('/recherche', [CoursController::class, 'search']);
    Route::get('/enseignant/{enseignantId}', [CoursController::class, 'byTeacher']);
    Route::get('/{id}', [CoursController::class, 'show']);
    Route::put('/{id}', [CoursController::class, 'update']);
    Route::delete('/{id}', [CoursController::class, 'destroy']);
});