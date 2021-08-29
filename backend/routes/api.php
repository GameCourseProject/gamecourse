<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



/* ------------------------
 * SETUP
 * ------------------------ */

Route::get('/requiresSetup', [SetupController::class, 'requiresSetup']);

Route::post('/setup', [SetupController::class, 'doSetup']);



/* ------------------------
 * USER RELATED
 * ------------------------ */

Route::post('/users', [UserController::class, 'newUser']);

Route::get('/users', [UserController::class, 'getAllUsers']);

Route::get('/users/{id}', [UserController::class, 'getUser']);

Route::get('/users/{id}/courses', [UserController::class, 'getAllCourses']);

Route::get('/users/{id}/roles', [UserController::class, 'getAllRoles']);



/* ------------------------
 * COURSE RELATED
 * ------------------------ */

Route::post('/courses', [CourseController::class, 'newCourse']);

Route::get('/courses', [CourseController::class, 'getAllCourses']);

Route::get('/courses/{id}', [CourseController::class, 'getCourse']);

Route::post('/courses/{id}', [CourseController::class, 'updateCourse']);

Route::get('/courses/{id}/students', [CourseController::class, 'getCourseStudents']);
