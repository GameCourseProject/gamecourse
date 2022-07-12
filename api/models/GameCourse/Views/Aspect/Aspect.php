<?php
namespace GameCourse\Views\Aspect;

use GameCourse\Core\Core;
use GameCourse\Course\Course;

/**
 * This is the Aspect model, which implements the necessary methods
 * to interact with view aspects in the MySQL database.
 */
class Aspect
{
    const TABLE_ASPECT = "aspect";

    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getCourse(): Course
    {
        return Course::getCourseById($this->getData("course"));
    }

    public function getViewerRoleId(): int
    {
        return $this->getData("viewerRole");
    }

    public function getUserRoleId(): int
    {
        return $this->getData("userRole");
    }

    /**
     * Gets aspect data from the database.
     *
     * @example getData() --> gets all aspect data
     * @example getData("course") --> gets aspect course ID
     * @example getData("course, viewerRole") --> gets aspect course ID & viewer role ID
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select(self::TABLE_ASPECT, ["id" => $this->id], $field);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets an aspect by its ID.
     * Returns null if aspect doesn't exist.
     *
     * @param int $id
     * @return Aspect|null
     */
    public static function getAspectById(int $id): ?Aspect
    {
        $aspect = new Aspect($id);
        if ($aspect->exists()) return $aspect;
        else return null;
    }

    /**
     * Gets an aspect by its specifications.
     * Returns null if aspect doesn't exist.
     *
     * @param int $courseId
     * @param int|null $viewerRoleId
     * @param int|null $userRoleId
     * @return Aspect|null
     */
    public static function getAspectBySpecs(int $courseId, ?int $viewerRoleId, ?int $userRoleId): ?Aspect
    {
        $aspectId = intval(Core::database()->select(self::TABLE_ASPECT, ["course" => $courseId, "viewerRole" => $viewerRoleId, "userRole" => $userRoleId], "id"));
        if (!$aspectId) return null;
        return new Aspect($aspectId);
    }

    /**
     * Gets aspects available in a given course.
     *
     * @param int $courseId
     * @return array
     */
    public static function getAspects(int $courseId): array
    {
        $aspects = Core::database()->selectMultiple(self::TABLE_ASPECT, ["course" => $courseId], "id, viewerRole, userRole");
        foreach ($aspects as &$aspect) { $aspect = self::parse($aspect); }
        return $aspects;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Aspects Manipulation --------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds an aspect to the database.
     * Returns the newly created aspect.
     *
     * @param int $courseId
     * @param int|null $viewerRoleId
     * @param int|null $userRoleId
     * @return Aspect
     */
    public static function addAspect(int $courseId, ?int $viewerRoleId, ?int $userRoleId): Aspect
    {
        $id = Core::database()->insert(self::TABLE_ASPECT, [
            "course" => $courseId,
            "viewerRole" => $viewerRoleId,
            "userRole" => $userRoleId
        ]);
        return new Aspect($id);
    }

    /**
     * Deletes an aspect from the database.
     *
     * @param int $aspectId
     * @return void
     */
    public static function deleteAspect(int $aspectId)
    {
        Core::database()->delete(self::TABLE_ASPECT, ["id" => $aspectId]);
    }

    /**
     * Deletes all aspects of a given course from the database.
     *
     * @param int $courseId
     * @return void
     */
    public static function deleteAllAspectsInCourse(int $courseId)
    {
        Core::database()->delete(self::TABLE_ASPECT, ["course" => $courseId]);
    }

    /**
     * Checks whether aspect exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses an aspect coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $aspect
     * @param $field
     * @param string|null $fieldName
     * @return array|int
     */
    public static function parse(array $aspect = null, $field = null, string $fieldName = null)
    {
        if ($aspect) {
            if (isset($aspect["id"])) $aspect["id"] = intval($aspect["id"]);
            if (isset($aspect["course"])) $aspect["course"] = intval($aspect["course"]);
            if (isset($aspect["viewerRole"])) $aspect["viewerRole"] = intval($aspect["viewerRole"]);
            if (isset($aspect["userRole"])) $aspect["userRole"] = intval($aspect["userRole"]);
            return $aspect;

        } else {
            if ($fieldName == "id" || $fieldName == "course" || $fieldName == "viewerRole" || $fieldName == "userRole")
                return intval($field);
            return $field;
        }
    }
}
