<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SetupController extends Controller
{
    public function doSetup(Request $request)
    {
        $courseName = $request->courseName;
        $courseColor = $request->courseColor;
        $teacherId = $request->teacherId;
        $teacherUsername = $request->teacherUsername;

        Artisan::call('db:wipe');
        Artisan::call('migrate:refresh', ['--path' => 'database/migrations/2021_08_21_220603_setup.php']);

        DB::table('courses')->insert([
            'name' => $courseName,
            'color' => $courseColor
        ]);

        $courseID = DB::table('courses')
            ->select('id')
            ->where('name', '=', $courseName)
            ->first()
            ->id;

        // TODO: create course data folder

        // TODO: insert basic course data

        DB::table('game_course_users')->insert([
            'name' => 'Teacher',
            'student_number' => $teacherId,
            'is_admin' => true
        ]);

        $userID = DB::table('game_course_users')
            ->select('id')
            ->where('student_number', '=', $teacherId)
            ->first()
            ->id;

        DB::table('auth')->insert([
            'game_course_user_id' => $userID,
            'username' => $teacherUsername,
            'authentication_service' => 'fenix'
        ]);

        DB::table('course_users')->insert([
            'id' => $userID,
            'course' => $courseID
        ]);

//        DB::table('user_roles')->insert([
//            'id' => $userID,
//            'course' => $courseID,
//            'role' => 1 // TODO: roleID
//        ]);

        DB::table('autogame')->insert([
            'course' => $courseID,
        ]);

        file_put_contents('setup.done', '');

        return 'Setup done!';
    }
}
