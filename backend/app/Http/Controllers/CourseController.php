<?php

namespace App\Http\Controllers;

use App\Models\Course;

class CourseController extends Controller
{
    public function getAllCourses()
    {
        return Course::all();
    }
}
