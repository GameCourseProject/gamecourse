<?php
namespace API;

use Exception;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Skills\SkillTree;
use GameCourse\Module\Skills\Tier;
use GameCourse\Module\Streaks\Streak;
use GameCourse\Module\Streaks\Streaks;

/**
 * This is the Skills controller, which holds API endpoints for
 * skills related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Skills",
 *     description="API endpoints for skills related actions"
 * )
 */
class SkillsController
{
    /*** --------------------------------------------- ***/
    /*** ---------------- SkillTrees ----------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets all Skill Trees of a given course.
     *
     * @throws Exception
     */
    public function getSkillTrees()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        API::response(SkillTree::getSkillTrees($courseId));
    }


    /*** --------------------------------------------- ***/
    /*** ------------------- Tiers ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets all tiers of a skill tree.
     * Option for 'active'.
     *
     * @param bool $isActive (optional)
     * @throws Exception
     */
    public function getTiersOfSkillTree()
    {
        API::requireValues("skillTreeId");

        $skillTreeId = API::getValue("skillTreeId", "int");
        $active = API::getValue("active", "bool");

        $course = SkillTree::getSkillTreeById($skillTreeId)->getCourse();
        API::requireCoursePermission($course);

        API::response(Tier::getTiersOfSkillTree($skillTreeId, $active));
    }

    /**
     * @throws Exception
     */
    public function createTier()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::requireValues("skillTreeId", "name", "reward", "costType", "cost", "increment", "minRating");

        // Get values
        $skillTreeId = API::getValue("skillTreeId", "int");
        $name = API::getValue("name");
        $reward = API::getValue("reward", "int");
        $costType = API::getValue("costType");
        $cost = API::getValue("cost", "int");
        $increment = API::getValue("increment", "int");
        $minRating = API::getValue("minRating", "int");

        // Add new tier
        Tier::addTier($skillTreeId, $name, $reward, $costType, $cost, $increment, $minRating);
    }

    /**
     * @throws Exception
     */
    public function editTier()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::requireValues("tierId", "name", "reward", "costType", "cost", "increment", "minRating", "position", "isActive");

        // Get values
        $tierId = API::getValue("tierId", "int");
        $name = API::getValue("name");
        $reward = API::getValue("reward", "int");
        $costType = API::getValue("costType");
        $cost = API::getValue("cost", "int");
        $increment = API::getValue("increment", "int");
        $minRating = API::getValue("minRating", "int");
        $position = API::getValue("position", "int");
        $isActive = API::getValue("isActive", "bool");

        // Edit skill
        $tier = Tier::getTierById($tierId);
        $tier->editTier($name, $reward, $position, $isActive, $costType, $cost, $increment, $minRating);
    }

    /**
     * @throws Exception
     */
    public function deleteTier()
    {
        API::requireValues("courseId", "tierId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $tierId = API::getValue("tierId", "int");

        // Delete tier
        Tier::deleteTier($tierId);
    }


    /*** --------------------------------------------- ***/
    /*** ------------------- Skills ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Get skill by its ID.
     *
     * @return void
     * @throws Exception
     */
    public function getSkillById()
    {
        API::requireValues("skillId");

        $skillId = API::getValue("skillId", "int");
        $skill = Skill::getSkillById($skillId);

        API::requireCoursePermission($skill->getCourse());

        $skillInfo = $skill->getData();
        $skillInfo["page"] = $skill->getPage();
        $skillInfo["dependencies"] = $skill->getDependencies();

        API::response($skillInfo);
    }

    /**
     * Get all skills of a skill tree.
     * Option for 'active', 'extra', 'collab'.
     *
     * @param bool $isActive (optional)
     * @param bool $isExtra (optional)
     * @param bool $isCollab (optional)
     * @throws Exception
     */
    public function getSkillsOfSkillTree()
    {
        API::requireValues("skillTreeId");

        $skillTreeId = API::getValue("skillTreeId", "int");
        $course = SkillTree::getSkillTreeById($skillTreeId)->getCourse();

        API::requireCoursePermission($course);
        $active = API::getValue("active", "bool");
        $extra = API::getValue("extra", "bool");
        $collab = API::getValue("collab", "bool");

        API::response(Skill::getSkillsOfSkillTree($skillTreeId, $active, $extra, $collab));
    }

    /**
     * @throws Exception
     */
    public function createSkill()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::requireValues('tierId', 'name', 'dependencies');

        // Get values
        $tierId = API::getValue("tierId", "int");
        $name = API::getValue("name");
        $color = API::getValue("color");
        $page = API::getValue("page");
        $dependencies = API::getValue("dependencies");

        // Add new skill
        Skill::addSkill($tierId, $name, $color, $page, false, false, $dependencies);
    }

    /**
     * @throws Exception
     */
    public function editSkill()
    {
        API::requireValues("courseId", "skillId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::requireValues('tierId', 'name', 'isCollab', 'isExtra', 'isActive', 'position', 'dependencies');

        // Get values
        $tierId = API::getValue("tierId", "int");
        $name = API::getValue("name");
        $color = API::getValue("color");
        $page = API::getValue("page");
        $isCollab = API::getValue("isCollab", "bool");
        $isExtra = API::getValue("isExtra", "bool");
        $isActive = API::getValue("isActive", "bool");
        $position = API::getValue("position", "int");
        $dependencies = API::getValue("dependencies");

        // Edit skill
        $skill = Skill::getSkillById(API::getValue("skillId", "int"));
        $skill->editSkill($tierId, $name, $color, $page, $isCollab, $isExtra, $isActive, $position, $dependencies);
    }

    /**
     * @throws Exception
     */
    public function deleteSkill()
    {
        API::requireValues("courseId", "skillId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $skillId = API::getValue("skillId", "int");
        Skill::deleteSkill($skillId);
    }


    // FIXME: hard-coded

    public function getUserTotalAvailableWildcards()
    {
        API::requireValues("courseId", "userId", "skillTreeId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $skillTreeId = API::getValue("skillTreeId", "int");

        $skillsModule = new Skills($course);
        API::response($skillsModule->getUserTotalAvailableWildcards($userId, $skillTreeId));
    }

    public function getSkillsExtraInfo()
    {
        API::requireValues("courseId", "userId", "skillTreeId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $skillTreeId = API::getValue("skillTreeId", "int");

        $skillsModule = new Skills($course);
        API::response($skillsModule->getUserSkills($userId, $skillTreeId));
    }

    public function getStreaks()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        API::response(Streak::getStreaks($courseId));
    }

    public function getUserStreaks()
    {
        API::requireValues("courseId", "userId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $streaksModule = new Streaks($course);
        API::response($streaksModule->getUserStreaks($userId));
    }
}
