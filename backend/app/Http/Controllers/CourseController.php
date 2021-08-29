<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{

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

        if ($request->exists('active')) {
            $active = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
            array_push($conditions, ['is_active', '=', $active]);
        }

        return DB::table('courses')
            ->where($conditions)
            ->orderBy('name')
            ->get();
    }
}
