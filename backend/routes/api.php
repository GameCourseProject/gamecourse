<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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
 Route::get('/users/{id}/courses', [UserController::class, 'getAllCourses']);



/* ------------------------
 * COURSE RELATED
 * ------------------------ */

Route::get('/courses', [CourseController::class, 'getAllCourses']);
