<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    /**
     * Create a new user.
     *
     * @param Request $request
     *
     * @return int
     */
    public function newUser(Request $request): int
    {
        return DB::table('users')
            ->updateOrInsert($request->all());
    }


    /**
     * Get all users.
     *
     * @param Request $request
     *
     * @return Collection
     */
    public function getAllUsers(Request $request): Collection
    {
        $conditions = [];

        if ($request->exists('name')) {
            $name = $request->get('name');
            array_push($conditions, ['name', '=', $name]);
        }

        if ($request->exists('email')) {
            $email = filter_var($request->get('email'), FILTER_VALIDATE_EMAIL);
            array_push($conditions, ['email', '=', $email]);
        }

        if ($request->exists('major')) {
            $major = $request->get('major');
            array_push($conditions, ['major', '=', $major]);
        }

        if ($request->exists('nickname')) {
            $nickname = $request->get('nickname');
            array_push($conditions, ['nickname', '=', $nickname]);
        }

        if ($request->exists('studentNumber')) {
            $studentNumber = filter_var($request->get('active'), FILTER_VALIDATE_INT);
            array_push($conditions, ['student_number', '=', $studentNumber]);
        }

        if ($request->exists('admin')) {
            $admin = filter_var($request->get('admin'), FILTER_VALIDATE_BOOLEAN);
            array_push($conditions, ['is_admin', '=', $admin]);
        }

        if ($request->exists('active')) {
            $active = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
            array_push($conditions, ['is_active', '=', $active]);
        }

        $sortBy = 'name';
        if ($request->exists('sort')) {
            $sortBy = $request->get('sort');
        }

        return DB::table('users')
            ->where($conditions)
            ->orderBy($sortBy)
            ->get();
    }


    /**
     * Get a user by ID.
     *
     * @param Request $request
     *
     * @return \Illuminate\Database\Query\Builder|mixed
     */
    public function getUser(Request $request)
    {
        $userID = $request->route('id');

        return DB::table('users')
            ->find($userID);
    }


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

        $sortBy = 'name';
        if ($request->exists('sort')) {
            $sortBy = $request->get('sort');
        }

        return DB::table('course_users')
            ->join('courses', 'courses.id', '=', 'course_users.course_id')
            ->where('user_id', $userID)
            ->where($conditions)
            ->select('courses.*')
            ->orderBy($sortBy)
            ->get();
    }


    /**
     * Get all user roles.
     *
     * @param Request $request
     *
     * @return Collection
     */
    public function getAllRoles(Request $request): Collection
    {
        $userID = $request->route('id');

        $sortBy = 'name';
        if ($request->exists('sort')) {
            $sortBy = $request->get('sort');
        }

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_id', $userID)
            ->select('roles.*')
            ->orderBy($sortBy)
            ->get();
    }
}
