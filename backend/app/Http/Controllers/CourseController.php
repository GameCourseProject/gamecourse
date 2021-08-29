<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{

    /**
     * Create a new course.
     *
     * @param Request $request
     *
     * @return int
     */
    public function newCourse(Request $request): int
    {
        return DB::table('courses')
            ->updateOrInsert($request->all());
    }


    /**
     * Get all courses.
     *
     * @param Request $request
     *
     * @return Collection
     */
    public function getAllCourses(Request $request): Collection
    {
        $conditions = [];

        if ($request->exists('name')) {
            $name = $request->get('name');
            array_push($conditions, ['name', '=', $name]);
        }

        if ($request->exists('short')) {
            $short = $request->get('short');
            array_push($conditions, ['short', '=', $short]);
        }

        if ($request->exists('color')) {
            $color = $request->get('color');
            array_push($conditions, ['color', '=', $color]);
        }

        if ($request->exists('year')) {
            $year = $request->get('year');
            array_push($conditions, ['year', '=', $year]);
        }

        if ($request->exists('defaultLandingPage')) {
            $defaultLandingPage = $request->get('defaultLandingPage');
            array_push($conditions, ['default_landing_page', '=', $defaultLandingPage]);
        }

        if ($request->exists('active')) {
            $active = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
            array_push($conditions, ['is_active', '=', $active]);
        }

        if ($request->exists('visible')) {
            $visible = filter_var($request->get('visible'), FILTER_VALIDATE_BOOLEAN);
            array_push($conditions, ['is_visible', '=', $visible]);
        }

        if ($request->exists('roleHierarchy')) {
            $roleHierarchy = $request->get('roleHierarchy');
            array_push($conditions, ['role_hierarchy', '=', $roleHierarchy]);
        }

        if ($request->exists('theme')) {
            $theme = $request->get('theme');
            array_push($conditions, ['theme', '=', $theme]);
        }

        $sortBy = 'name';
        if ($request->exists('sort')) {
            $sortBy = $request->get('sort');
        }

        return DB::table('courses')
            ->where($conditions)
            ->orderBy($sortBy)
            ->get();
    }


    /**
     * Get a course by ID.
     *
     * @param Request $request
     *
     * @return \Illuminate\Database\Query\Builder|mixed
     */
    public function getCourse(Request $request)
    {
        $courseID = $request->route('id');

        return DB::table('courses')
            ->find($courseID);
    }


    /**
     * Update a course.
     *
     * @param Request $request
     *
     * @return int
     */
    public function updateCourse(Request $request): int
    {
        $courseID = $request->route('id');

        $updates = $request->all();

        return DB::table('courses')
            ->where('id', $courseID)
            ->update($updates);
    }


    /**
     * Get all students enrolled in course.
     *
     * @param Request $request
     *
     * @return Collection
     */
    public function getCourseStudents(Request $request): Collection
    {
        $courseID = $request->route('id');

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where([
                ['user_roles.course_id', $courseID],
                ['roles.name', 'Student']
            ])
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->select('users.*')
            ->get();
    }
}
