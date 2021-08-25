<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SetupController extends Controller
{
    public function doSetup(Request $request)
    {
        $courseName = $request->courseName;
        $courseColor = $request->courseColor;
        $teacherId = $request->teacherId;
        $teacherUsername = $request->teacherUsername;

        if (!$courseName) return 'Missing info - course name';
        if (!$courseColor) return 'Missing info - course color';
        if (!$teacherId) return 'Missing info - teacher ID';
        if (!$teacherUsername) return 'Missing info - teacher username';

        // Clean DB & create tables
        Artisan::call('migrate:fresh', ['--path' => 'database/migrations/setup']);

        // Init DB
        $courseID = DB::table('courses')->insertGetId([
            'name' => $courseName,
            'color' => $courseColor
        ]);

        $roleID = DB::table('roles')->insertGetId([
            'name' => 'Teacher',
            'course' => $courseID
        ]);

        DB::table('roles')->insert([
            ['name' => 'Student', 'course' => $courseID],
            ['name' => 'Watcher', 'course' => $courseID],
        ]);

        $roles = [["name" => "Teacher"], ["name" => "Student"], ["name" => "Watcher"]];
        DB::table('courses')
            ->where('id', $courseID)
            ->update([
                'role_hierarchy' => json_encode($roles)
            ]);

        $userID = DB::table('users')->insertGetId([
            'name' => 'Teacher',
            'student_number' => $teacherId,
            'is_admin' => true
        ]);

        DB::table('auth')->insert([
            'user_id' => $userID,
            'username' => $teacherUsername,
            'authentication_service' => 'fenix'
        ]);

        DB::table('course_users')->insert([
            'id' => $userID,
            'course' => $courseID
        ]);

        DB::table('user_roles')->insert([
            'id' => $userID,
            'course' => $courseID,
            'role' => $roleID
        ]);

        DB::table('autogame')->insert([
            'course' => $courseID,
        ]);

        Storage::disk('local')->put('setup.done', '');

        sleep(5);
        DB::table('courses')->where('id', $courseID)->update([
            'name' => 'changed',
        ]);

        return 'Setup done!';
    }
}
