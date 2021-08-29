<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;


    /**
     * Get course data folder name.
     *
     * @param int $courseId
     * @param string|null $courseName
     *
     * @return string
     */
    public static function getCourseDataFolder(int $courseId, string $courseName = null): string
    {
        if ($courseName === null) {
            $courseName = Course::find($courseId)->name;
        }

        $courseName = iconv('UTF-8', 'ASCII//TRANSLIT', $courseName);

        return env('COURSE_DATA_FOLDER', 'course_data') . '/' . $courseId . '-' . $courseName;
    }
}
