<?php
namespace API;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Awards\AwardType;
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
     * Get all skills of a course.
     *
     * @throws Exception
     */
    public function getSkillsOfCourse()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        API::response(Skill::getSkills($courseId));
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

        $info = [];
        $userSkillAwards = (new Awards($course))->getUserSkillsAwards($userId);
        foreach (SkillTree::getSkillTreeById($skillTreeId)->getSkills(true) as $skill) {
            $skill = Skill::getSkillById($skill["id"]);
            $dependencies = $skill->getDependencies();
            $skillAwards = array_values(array_filter($userSkillAwards, function ($award) use ($skill) {
                return $award["type"] === AwardType::SKILL && $award["description"] == $skill->getName() && $award["moduleInstance"] == $skill->getId();
            }));
            $completed = !empty($skillAwards);
            $wildcardsUsed = $completed ? intval(Core::database()->select(Skills::TABLE_AWARD_WILDCARD, ["award" => $skillAwards[0]["id"]], "IFNULL(SUM(nrWildcardsUsed), 0) as nrWildcardsUsed")["nrWildcardsUsed"]) : 0;
            $info[$skill->getId()] = [
                "available" => $completed || empty($dependencies) || $this->dependenciesMet($course, $userId, $skillTreeId, $userSkillAwards, $dependencies),
                "attempts" => count(array_filter(AutoGame::getParticipations($courseId, $userId, "graded post"),
                    function ($item) use ($skill) { return $item["description"] === "Skill Tree, Re: " . $skill->getName(); })),
                "cost" => $skill->getSkillCostForUser($userId),
                "completed" => $completed,
                "wildcardsUsed" => $wildcardsUsed
            ];
        }
        $info["total"] = (new Awards($course))->getUserSkillsTotalReward($userId);
        API::response($info);
    }

    private function dependenciesMet($course, $userId, $skillTreeId, $userSkillAwards, $dependencies) // FIXME: create proper function
    {
        $wildcardTier = Tier::getWildcard($skillTreeId)->getId();
        foreach ($dependencies as $dependency) {
            $completed = true;
            foreach ($dependency as $skill) {
                if ($skill["tier"] === $wildcardTier) {
                    $hasWildcard = (new Skills($course))->userHasWildcardAvailable($userId, $skillTreeId);
                    if (!$hasWildcard) {
                        $completed = false;
                        break;
                    }

                } else {
                    $skillCompleted = !empty(array_filter($userSkillAwards, function ($award) use ($skill) {
                        return $award["type"] === AwardType::SKILL && $award["description"] == $skill["name"] && $award["moduleInstance"] == $skill["id"];
                    }));
                    if (!$skillCompleted) {
                        $completed = false;
                        break;
                    }
                }
            }
            if ($completed) return true;
        }
        return false;
    }

    public function getStreaks()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        API::response(Streak::getStreaks($courseId));
    }

    public function getUserStreaksInfo()
    {
        API::requireValues("courseId", "userId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $awardsModule = new Awards($course);
        $userStreakAwards = $awardsModule->getUserStreaksAwards($userId);

        $userStreaks = [];
        $streaksModule = new Streaks($course);
        foreach (Streak::getStreaks($courseId, true) as $streak) {
            $streakId = $streak["id"];
            $nrCompletions = count(array_filter($userStreakAwards, function ($award) use ($streak) { return $award["moduleInstance"] === $streak["id"]; }));
            $progress = $streaksModule->getUserStreakProgression($userId, $streak["id"], $nrCompletions);
            $dealine = (new Streak($streakId))->isPeriodic() ? $streaksModule->getUserStreakDeadline($userId, $streakId) : null;

            $userStreaks[] = [
                "id" => $streak["id"],
                "nrCompletions" => $nrCompletions,
                "progress" => $progress,
                "deadline" => $dealine
            ];
        }

        $info = ["info" => $userStreaks, "total" => $awardsModule->getUserStreaksTotalReward($userId)];
        API::response($info);
    }

    /**
     * @throws Exception
     */
    public function setSkillTreeInView(){
        API::requireValues("courseId", "skillTreeId", "status");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $skillTreeId = API::getValue("skillTreeId", "int");
        $status = API::getValue("status", "bool");
        Skills::setSkillTreeInView($skillTreeId, $status);
}
}
