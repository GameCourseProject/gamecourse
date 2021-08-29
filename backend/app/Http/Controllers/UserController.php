<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    /**
     * Get all user courses.
     *
     * @param Request $request
     *
     * @return Collection
     */
    public function getAllCourses(Request $request): Collection
    {
        $userID = $request->route('id');

        $conditions = [];

        if ($request->exists('active')) {
            $active = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
            array_push($conditions, ['course_users.is_active', '=', $active]);
        }

        return DB::table('course_users')
            ->join('courses', 'courses.id', '=', 'course_users.course_id')
            ->where('user_id', $userID)
            ->where($conditions)
            ->select('courses.*')
            ->orderBy('name')
            ->get();
    }
}
