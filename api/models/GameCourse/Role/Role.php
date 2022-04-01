<?php
namespace GameCourse\Role;

use GameCourse\Core\Core;
use GameCourse\Course\Course;

/**
 * This is the Role model, which implements the necessary methods
 * to interact with roles in the MySQL database.
 */
class Role
{
    const TABLE_ROLE = "role";
    const TABLE_USER_ROLE = "user_role";

    const DEFAULT_ROLES = ["Teacher", "Student", "Watcher"];  // default roles of each course

    public static function addDefaultRolesToCourse(int $courseId): ?int
    {
        $teacherId = null;
        foreach (self::DEFAULT_ROLES as $role) {
            $id = Core::database()->insert(self::TABLE_ROLE, ["name" => $role, "course" => $courseId]);
            if ($role === "Teacher") $teacherId = $id;
        }

        $hierarchy = array_map(function ($role) {
            return ["name" => $role];
        }, self::DEFAULT_ROLES);
        Core::database()->update(Course::TABLE_COURSE, ["roleHierarchy" => json_encode($hierarchy)], ["id" => $courseId]);

        return $teacherId;
    }
}
