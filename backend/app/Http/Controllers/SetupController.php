<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SetupController extends Controller
{

    /**
     * Check if setup is required.
     *
     * @return bool
     */
    public function requiresSetup(): bool
    {
        return !Storage::disk('local')->exists('setup.done');
    }


    /**
     * Perform setup.
     *
     * @param Request $request
     *
     * @return string
     */
    public function doSetup(Request $request): string
    {
        $courseName = $request->courseName;
        $courseColor = $request->courseColor;
        $teacherId = $request->teacherId;
        $teacherUsername = $request->teacherUsername;

        if (!$courseName) abort(400, 'No course name.');
        if (!$courseColor) abort(400, 'No course color.');
        if (!$teacherId) abort(400, 'No teacher ID.');
        if (!$teacherUsername) abort(400, 'No teacher username.');

        // Clean DB & create tables
        Artisan::call('migrate:fresh', ['--path' => 'database/migrations/setup']);

        // Init DB
        $this->initDB($courseName, $courseColor, $teacherId, $teacherUsername);

        // Prepare autogame
        $this->prepAutogame($courseName);

        Storage::disk('local')->put('setup.done', '');

        // TODO: unset session
        return json_encode('Setup done!');
    }


    /**
     * Initialize database.
     *
     * @param string $courseName
     * @param string $courseColor
     * @param int $teacherId
     * @param string $teacherUsername
     */
    private function initDB(string $courseName, string $courseColor, int $teacherId, string $teacherUsername)
    {
        $courseID = DB::table('courses')->insertGetId([
            'name' => $courseName,
            'color' => $courseColor
        ]);

        $roleID = DB::table('roles')->insertGetId([
            'name' => 'Teacher',
            'course_id' => $courseID
        ]);

        DB::table('roles')->insert([
            ['name' => 'Student', 'course_id' => $courseID],
            ['name' => 'Watcher', 'course_id' => $courseID],
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
            'user_id' => $userID,
            'course_id' => $courseID
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $userID,
            'course_id' => $courseID,
            'role_id' => $roleID
        ]);

        DB::table('autogame')->insert([
            'course_id' => $courseID,
        ]);
    }


    /**
     * Prepare autogame.
     *
     * @param string $courseName
     */
    private function prepAutogame(string $courseName)
    {
        // Create course data folder
        Storage::disk('local')->deleteDirectory(env('COURSE_DATA_FOLDER', 'course_data'));
        $courseID = Course::where('name', $courseName)->first()->id;
        $dataFolder = Course::getCourseDataFolder($courseID, $courseName);
        Storage::disk('local')->makeDirectory($dataFolder);

        // Create rules folder
        $rulesFolder = 'rules';
        Storage::disk('local')->makeDirectory($dataFolder . '/' . $rulesFolder);

        // Create autogame folder
        // TODO
    }
}
