<?php
namespace GameCourse\Views\Aspect;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;

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

    public function getViewerRoleId(): ?int
    {
        return $this->getData("viewerRole");
    }

    public function getUserRoleId(): ?int
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
     * Gets a view's aspect in a given course.
     *
     * @param array $view
     * @param int $courseId
     * @return Aspect
     * @throws Exception
     */
    public static function getAspectInView(array $view, int $courseId): Aspect
    {
        $viewerRole = isset($view["aspect"]) ? $view["aspect"]["viewerRole"] ?? null : null;
        $userRole = isset($view["aspect"]) ? $view["aspect"]["userRole"] ?? null : null;

        try {
            $aspect = self::getAspectBySpecs($courseId,
                $viewerRole ? Role::getRoleId($viewerRole, $courseId) : null,
                $userRole ? Role::getRoleId($userRole, $courseId) : null);

        } catch (Exception $e) {
            throw new Exception("No aspect with viewer role = '" . $viewerRole . "' & user role = '" .
                $userRole . "' found for course with ID = " . $courseId . ".");
        }

        return $aspect;
    }

    /**
     * Gets aspects available in a given course or user.
     * Option to sort them by most specific.
     *
     * @param int $courseId
     * @param int|null $userId
     * @param bool $sortByMostSpecific
     * @param bool $IDsOnly
     * @return array
     */
    public static function getAspects(int $courseId, int $userId = null, bool $sortByMostSpecific = false, bool $IDsOnly = false): array
    {
        $aspects = [];

        if ($sortByMostSpecific) {
            $roleIdsByMostSpecific = array_map(function ($roleName) use ($courseId) {
                return Role::getRoleId($roleName, $courseId);
            }, $userId ? Role::getUserRoles($userId, $courseId, true, true) : Role::getCourseRoles($courseId, true, true));
            $roleIdsByMostSpecific[] = null;

            foreach ($roleIdsByMostSpecific as $userRoleId) {
                foreach ($roleIdsByMostSpecific as $viewerRoleId) {
                    $aspect = Aspect::getAspectBySpecs($courseId, $viewerRoleId, $userRoleId);
                    $aspects[] = $IDsOnly ? $aspect->id : $aspect->getData("id, viewerRole, userRole");
                }
            }

        } else {
            $aspects = Core::database()->selectMultiple(self::TABLE_ASPECT, ["course" => $courseId], "id, viewerRole, userRole");
            if ($userId) {
                $userRoleIds = array_map(function ($roleName) use ($courseId) {
                    return Role::getRoleId($roleName, $courseId);
                }, Role::getUserRoles($userId, $courseId));
                $userRoleIds[] = null;
                $aspects = array_filter($aspects, function ($aspect) use ($userRoleIds) {
                    return in_array($aspect["viewerRole"], $userRoleIds) && in_array($aspect["userRole"], $userRoleIds);
                });
            }
            if ($IDsOnly) $aspects = array_map(function ($aspect) { return $aspect["id"]; }, $aspects);
        }

        if (!$IDsOnly) foreach ($aspects as &$aspect) { $aspect = self::parse($aspect); }
        return $aspects;
    }

    /**
     * Gets all aspects of a given course for a specific viewer and user.
     * Option to sort them by most specific.
     *
     * @param int $courseId
     * @param int|null $viewerId
     * @param int|null $userId
     * @param bool $sortByMostSpecific
     * @param array|null $viewerRoleIds
     * @param array|null $userRoleIds
     * @return array
     * @throws Exception
     */
    public static function getAspectsByViewerAndUser(int $courseId, ?int $viewerId, ?int $userId, bool $sortByMostSpecific = false,
                                                     array $viewerRoleIds = null, array $userRoleIds = null): array
    {
        if ((!$viewerRoleIds || !$userRoleIds) && (!$viewerId || !$userId))
            throw new Exception("Can't get aspects by viewer and user: need either viewer and user's IDs or role IDs.");

        $aspects = [];

        // Get viewer role IDs
        if (!$viewerRoleIds) {
            $viewerRoleIds = array_map(function ($roleName) use ($courseId) {
                return Role::getRoleId($roleName, $courseId);
            }, Role::getUserRoles($viewerId, $courseId, true, $sortByMostSpecific));
            $viewerRoleIds[] = null;
        }

        // Get user role IDs
        if (!$userRoleIds) {
            $userRoleIds = array_map(function ($roleName) use ($courseId) {
                return Role::getRoleId($roleName, $courseId);
            }, Role::getUserRoles($userId, $courseId, true, $sortByMostSpecific));
            $userRoleIds[] = null;
        }

        // Combine them
        foreach ($userRoleIds as $userRoleId) {
            foreach ($viewerRoleIds as $viewerRoleId) {
                $aspects[] = Aspect::getAspectBySpecs($courseId, $viewerRoleId, $userRoleId)->getData("id, viewerRole, userRole");
            }
        }

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

    /**
     * Check whether two aspects are the same.
     *
     * @param Aspect $aspect
     * @return bool
     */
    public function equals(Aspect $aspect): bool
    {
        return $this->getViewerRoleId() == $aspect->getViewerRoleId() &&
            $this->getUserRoleId() == $aspect->getUserRoleId();
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
                return is_numeric($field) ? intval($field) : $field;
            return $field;
        }
    }
}
