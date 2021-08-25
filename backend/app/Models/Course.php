<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;


    public static function getCourseDataFolder($courseId, $courseName = null): string
    {
        if ($courseName === null) {
            $courseName = Course::find($courseId)->name;
        }

        $courseName = preg_replace("/[^a-zA-Z0-9_ ]/", "", $courseName);

        return env('COURSE_DATA_FOLDER', 'course_data') . '/' . $courseId . '-' . $courseName;
    }
}
