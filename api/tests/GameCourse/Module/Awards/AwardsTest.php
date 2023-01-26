<?php
namespace GameCourse\Module\Awards;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Badges\Badge;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Skills\SkillTree;
use GameCourse\Module\Skills\Tier;
use GameCourse\Module\Streaks\Streak;
use GameCourse\Module\Streaks\Streaks;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class AwardsTest extends TestCase
{
    private $course;
    private $module;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["modules"], ["CronJob"]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->course = $course;

        // Set students
        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student", null, false);

        // Enable Awards module
        $awards = new Awards($course);
        $awards->setEnabled(true);
        $this->module = $awards;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER]);
        if (Core::database()->tableExists(Awards::TABLE_AWARD)) TestingUtils::resetAutoIncrement([Awards::TABLE_AWARD]);
        TestingUtils::cleanFileStructure();
        TestingUtils::cleanEvents();
    }

    protected function onNotSuccessfulTest(Throwable $t): void
    {
        $this->tearDown();
        parent::onNotSuccessfulTest($t);
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        TestingUtils::tearDownAfterClass();
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // Setup

    /**
     * @test
     * @throws Exception
     */
    public function init()
    {
        // Given
        $this->module->setEnabled(false);

        // When
        $this->module->init();

        // Then
        $sql = file_get_contents(MODULES_FOLDER . "/" . Awards::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function disable()
    {
        // When
        $this->module->setEnabled(false);

        // Then
        $sql = file_get_contents(MODULES_FOLDER . "/" . Awards::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
    }


    // Awards

    /**
     * @test
     * @throws Exception
     */
    public function getUserAwards()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::PRESENTATION, null, "Presentation", 3000);

        // When
        $awards = $this->module->getUserAwards($user->getId());

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(2, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($awards as $award) {
                $this->assertCount($nrKeys, array_keys($award));
                $this->assertArrayHasKey($key, $award);
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserAwardsNoAwards()
    {
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->assertEmpty($this->module->getUserAwards($user->getId()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUserAwardsByType()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::PRESENTATION, null, "Presentation", 3000);

        // When
        $awards = $this->module->getUserAwardsByType($user->getId(), AwardType::BONUS);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "description" => "Bonus",
                "type" => AwardType::BONUS,
                "moduleInstance" => null,
                "reward" => 500
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserAwardsByTypeNoAwards()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::PRESENTATION, null, "Presentation", 3000);

        // When
        $awards = $this->module->getUserAwardsByType($user->getId(), AwardType::BONUS);

        // Then
        $this->assertIsArray($awards);
        $this->assertEmpty($awards);
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesAwards()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badge = Badge::addBadge($this->course->getId(), "Bagde", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badge->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badge->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserBadgesAwards($user->getId());

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(2, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            if ($key === "date") continue;
            foreach ($awards as $i => $award) {
                $this->assertCount($nrKeys, array_keys($award));
                $this->assertArrayHasKey($key, $award);
                if ($key === "description") $this->assertEquals($award[$key], "Award " . ($i+1));
                else $this->assertEquals($award[$key], [
                    "id" => $i + 1,
                    "user" => $user->getId(),
                    "course" => $this->course->getId(),
                    "type" => AwardType::BADGE,
                    "moduleInstance" => $badge->getId(),
                    "reward" => 100
                ][$key]);
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesAwardsExtraCredit()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badgeExtra = Badge::addBadge($this->course->getId(), "Bagde Extra", "Perform action", true, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badgeNotExtra = Badge::addBadge($this->course->getId(), "Bagde Not Extra", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserBadgesAwards($user->getId(), true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::BADGE,
                "moduleInstance" => $badgeExtra->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesAwardsNotExtraCredit()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badgeExtra = Badge::addBadge($this->course->getId(), "Bagde Extra", "Perform action", true, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badgeNotExtra = Badge::addBadge($this->course->getId(), "Bagde Not Extra", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserBadgesAwards($user->getId(), false);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 2", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 2,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::BADGE,
                "moduleInstance" => $badgeNotExtra->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesAwardsOnlyActive()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badgeActive = Badge::addBadge($this->course->getId(), "Bagde Active", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badgeNotActive = Badge::addBadge($this->course->getId(), "Bagde Not Active", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeActive->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeNotActive->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $badgeNotActive->setActive(false);
        $awards = $this->module->getUserBadgesAwards($user->getId(), null, null, null, null, null, true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::BADGE,
                "moduleInstance" => $badgeActive->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesAwardsBadgesNotEnabled()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->expectException(Exception::class);
        $this->module->getUserBadgesAwards($user->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsAwards()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skill = Skill::addSkill($tier->getId(), "Skill", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skill->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skill->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserSkillsAwards($user->getId());

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(2, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            if ($key === "date") continue;
            foreach ($awards as $i => $award) {
                $this->assertCount($nrKeys, array_keys($award));
                $this->assertArrayHasKey($key, $award);
                if ($key === "description") $this->assertEquals($award[$key], "Award " . ($i+1));
                else $this->assertEquals($award[$key], [
                    "id" => $i + 1,
                    "user" => $user->getId(),
                    "course" => $this->course->getId(),
                    "type" => AwardType::SKILL,
                    "moduleInstance" => $skill->getId(),
                    "reward" => 100
                ][$key]);
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsAwardsOnlyCollab()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillCollab = Skill::addSkill($tier->getId(), "Skill Collab", null, null, true, false, []);
        $skillNotCollab = Skill::addSkill($tier->getId(), "Skill Not Collab", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillCollab->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotCollab->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserSkillsAwards($user->getId(), true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::SKILL,
                "moduleInstance" => $skillCollab->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsAwardsNotCollab()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillCollab = Skill::addSkill($tier->getId(), "Skill Collab", null, null, true, false, []);
        $skillNotCollab = Skill::addSkill($tier->getId(), "Skill Not Collab", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillCollab->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotCollab->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserSkillsAwards($user->getId(), false);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 2", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 2,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::SKILL,
                "moduleInstance" => $skillNotCollab->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsAwardsExtraCredit()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillExtra = Skill::addSkill($tier->getId(), "Skill Extra", null, null, false, true, []);
        $skillNotExtra = Skill::addSkill($tier->getId(), "Skill Not Extra", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserSkillsAwards($user->getId(), null, true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::SKILL,
                "moduleInstance" => $skillExtra->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsAwardsNotExtraCredit()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillExtra = Skill::addSkill($tier->getId(), "Skill Extra", null, null, false, true, []);
        $skillNotExtra = Skill::addSkill($tier->getId(), "Skill Not Extra", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserSkillsAwards($user->getId(), null, false);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 2", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 2,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::SKILL,
                "moduleInstance" => $skillNotExtra->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsAwardsOnlyActive()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillActive = Skill::addSkill($tier->getId(), "Skill Active", null, null, false, false, []);
        $skillNotActive = Skill::addSkill($tier->getId(), "Skill Not Active", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillActive->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotActive->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $skillNotActive->setActive(false);
        $awards = $this->module->getUserSkillsAwards($user->getId(), null, null, true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::SKILL,
                "moduleInstance" => $skillActive->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsAwardsSkillsNotEnabled()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->expectException(Exception::class);
        $this->module->getUserSkillsAwards($user->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksAwards()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streak = Streak::addStreak($this->course->getId(), "Streak", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streak->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streak->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserStreaksAwards($user->getId());

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(2, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            if ($key === "date") continue;
            foreach ($awards as $i => $award) {
                $this->assertCount($nrKeys, array_keys($award));
                $this->assertArrayHasKey($key, $award);
                if ($key === "description") $this->assertEquals($award[$key], "Award " . ($i+1));
                else $this->assertEquals($award[$key], [
                    "id" => $i + 1,
                    "user" => $user->getId(),
                    "course" => $this->course->getId(),
                    "type" => AwardType::STREAK,
                    "moduleInstance" => $streak->getId(),
                    "reward" => 100
                ][$key]);
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksAwardsRepeatable()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakRepeatable = Streak::addStreak($this->course->getId(), "Streak Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, true);
        $streakNotRepeatable = Streak::addStreak($this->course->getId(), "Streak Not Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakRepeatable->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotRepeatable->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserStreaksAwards($user->getId(), true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::STREAK,
                "moduleInstance" => $streakRepeatable->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksAwardsNotRepeatable()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakRepeatable = Streak::addStreak($this->course->getId(), "Streak Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, true);
        $streakNotRepeatable = Streak::addStreak($this->course->getId(), "Streak Not Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakRepeatable->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotRepeatable->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserStreaksAwards($user->getId(), false);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 2", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 2,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::STREAK,
                "moduleInstance" => $streakNotRepeatable->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksAwardsExtraCredit()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakExtra = Streak::addStreak($this->course->getId(), "Streak Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            true, false);
        $streakNotExtra = Streak::addStreak($this->course->getId(), "Streak Not Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserStreaksAwards($user->getId(), null, true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::STREAK,
                "moduleInstance" => $streakExtra->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksAwardsNotExtraCredit()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakExtra = Streak::addStreak($this->course->getId(), "Streak Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            true, false);
        $streakNotExtra = Streak::addStreak($this->course->getId(), "Streak Not Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $awards = $this->module->getUserStreaksAwards($user->getId(), null, false);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 2", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 2,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::STREAK,
                "moduleInstance" => $streakNotExtra->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksAwardsOnlyActive()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakActive = Streak::addStreak($this->course->getId(), "Streak Active", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streakNotActive = Streak::addStreak($this->course->getId(), "Streak Not Active", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streakNotActive->setActive(false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakActive->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotActive->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $streakNotActive->setActive(false);
        $awards = $this->module->getUserStreaksAwards($user->getId(), null, null, true);

        // Then
        $this->assertIsArray($awards);
        $this->assertCount(1, $awards);

        $keys = ["id", "user", "course", "description", "type", "moduleInstance", "reward", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($awards[0]));
            $this->assertArrayHasKey($key, $awards[0]);

            if ($key === "date") continue;
            if ($key === "description") $this->assertEquals("Award 1", $awards[0][$key]);
            else $this->assertEquals($awards[0][$key], [
                "id" => 1,
                "user" => $user->getId(),
                "course" => $this->course->getId(),
                "type" => AwardType::STREAK,
                "moduleInstance" => $streakActive->getId(),
                "reward" => 100
            ][$key]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksAwardsStreaksNotEnabled()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->expectException(Exception::class);
        $this->module->getUserStreaksAwards($user->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function giveAward()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);

        // When
        $this->module->giveAward($user->getId(), "Award", AwardType::BONUS);

        // Then
        $userAwards = $this->module->getUserAwards($user->getId());
        $this->assertCount(1, $userAwards);
    }

    /**
     * @test
     * @throws Exception
     */
    public function giveAwardUnavailableAwardType()
    {
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->expectException(Exception::class);
        $this->module->giveAward($user->getId(), "Award", "award_type");
    }


    // Rewards

    /**
     * @test
     * @throws Exception
     */
    public function getUserTotalRewardByType()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::PRESENTATION, null, "Presentation", 3000);

        // Then
        $this->assertEquals(500, $this->module->getUserTotalRewardByType($user->getId(), AwardType::BONUS));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserTotalRewardByTypeNoAwards()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::PRESENTATION, null, "Presentation", 3000);

        // Then
        $this->assertEquals(0, $this->module->getUserTotalRewardByType($user->getId(), AwardType::BONUS));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesTotalReward()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badge = Badge::addBadge($this->course->getId(), "Bagde", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badge->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badge->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(200, $this->module->getUserBadgesTotalReward($user->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesTotalRewardExtraCredit()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badgeExtra = Badge::addBadge($this->course->getId(), "Bagde Extra", "Perform action", true, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badgeNotExtra = Badge::addBadge($this->course->getId(), "Bagde Not Extra", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeExtra->getId(), "Award 1", 200);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(200, $this->module->getUserBadgesTotalReward($user->getId(), true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesTotalRewardNotExtraCredit()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badgeExtra = Badge::addBadge($this->course->getId(), "Bagde Extra", "Perform action", true, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badgeNotExtra = Badge::addBadge($this->course->getId(), "Bagde Not Extra", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeExtra->getId(), "Award 1", 200);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserBadgesTotalReward($user->getId(), false));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesTotalRewardOnlyActive()
    {
        // Given
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badgeActive = Badge::addBadge($this->course->getId(), "Bagde Active", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badgeNotActive = Badge::addBadge($this->course->getId(), "Bagde Not Active", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeActive->getId(), "Award 1", 200);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BADGE, $badgeNotActive->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $badgeNotActive->setActive(false);

        // Then
        $this->assertEquals(200, $this->module->getUserBadgesTotalReward($user->getId(), null, null, null, null, null, true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgesTotalRewardBadgesNotEnabled()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->expectException(Exception::class);
        $this->module->getUserBadgesTotalReward($user->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsTotalReward()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skill = Skill::addSkill($tier->getId(), "Skill", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skill->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skill->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(200, $this->module->getUserSkillsTotalReward($user->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsTotalRewardCollab()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillCollab = Skill::addSkill($tier->getId(), "Skill Collab", null, null, true, false, []);
        $skillNotCollab = Skill::addSkill($tier->getId(), "Skill Not Collab", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillCollab->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotCollab->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserSkillsTotalReward($user->getId(), true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsTotalRewardNotCollab()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillCollab = Skill::addSkill($tier->getId(), "Skill Collab", null, null, true, false, []);
        $skillNotCollab = Skill::addSkill($tier->getId(), "Skill Not Collab", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillCollab->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotCollab->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserSkillsTotalReward($user->getId(), false));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsTotalRewardExtraCredit()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillExtra = Skill::addSkill($tier->getId(), "Skill Extra", null, null, false, true, []);
        $skillNotExtra = Skill::addSkill($tier->getId(), "Skill Not Extra", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserSkillsTotalReward($user->getId(), null, true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsTotalRewardNotExtraCredit()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillExtra = Skill::addSkill($tier->getId(), "Skill Extra", null, null, false, true, []);
        $skillNotExtra = Skill::addSkill($tier->getId(), "Skill Not Extra", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserSkillsTotalReward($user->getId(), null, false));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsTotalRewardOnlyActive()
    {
        // Given
        $skillsModule = new Skills($this->course);
        $skillsModule->setEnabled(true);
        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skillActive = Skill::addSkill($tier->getId(), "Skill Active", null, null, false, false, []);
        $skillNotActive = Skill::addSkill($tier->getId(), "Skill Not Active", null, null, false, false, []);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillActive->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::SKILL, $skillNotActive->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $skillNotActive->setActive(false);

        // Then
        $this->assertEquals(100, $this->module->getUserSkillsTotalReward($user->getId(), null, null, true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkillsTotalRewardSkillsNotEnabled()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->expectException(Exception::class);
        $this->module->getUserSkillsTotalReward($user->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksTotalReward()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streak = Streak::addStreak($this->course->getId(), "Streak", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streak->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streak->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(200, $this->module->getUserStreaksTotalReward($user->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksTotalRewardRepeatable()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakRepeatable = Streak::addStreak($this->course->getId(), "Streak Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, true);
        $streakNotRepeatable = Streak::addStreak($this->course->getId(), "Streak Not Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakRepeatable->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotRepeatable->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserStreaksTotalReward($user->getId(), true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksTotalRewardNotRepeatable()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakRepeatable = Streak::addStreak($this->course->getId(), "Streak Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, true);
        $streakNotRepeatable = Streak::addStreak($this->course->getId(), "Streak Not Repeatable", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakRepeatable->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotRepeatable->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserStreaksTotalReward($user->getId(), false));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksTotalRewardExtraCredit()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakExtra = Streak::addStreak($this->course->getId(), "Streak Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            true, false);
        $streakNotExtra = Streak::addStreak($this->course->getId(), "Streak Not Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserStreaksTotalReward($user->getId(), null, true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksTotalRewardNotExtraCredit()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakExtra = Streak::addStreak($this->course->getId(), "Streak Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            true, false);
        $streakNotExtra = Streak::addStreak($this->course->getId(), "Streak Not Extra", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakExtra->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotExtra->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->assertEquals(100, $this->module->getUserStreaksTotalReward($user->getId(), null, false));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksTotalRewardOnlyActive()
    {
        // Given
        $streaksModule = new Streaks($this->course);
        $streaksModule->setEnabled(true);
        $streakActive = Streak::addStreak($this->course->getId(), "Streak Active", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streakNotActive = Streak::addStreak($this->course->getId(), "Streak Not Active", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streakNotActive->setActive(false);

        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakActive->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::STREAK, $streakNotActive->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // When
        $streakNotActive->setActive(false);

        // Then
        $this->assertEquals(100, $this->module->getUserStreaksTotalReward($user->getId(), null, null, true));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaksTotalRewardStreaksNotEnabled()
    {
        // Given
        $user = CourseUser::getCourseUserById($this->course->getStudents(true)[0]["id"], $this->course);
        $this->insertAward($this->course->getId(), $user->getId(), AwardType::BONUS, null, "Bonus", 500);

        // Then
        $this->expectException(Exception::class);
        $this->module->getUserStreaksTotalReward($user->getId());
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function insertAward(int $courseId, int $userId, string $type, ?int $moduleInstance, string $description, int $reward)
    {
        Core::database()->insert(Awards::TABLE_AWARD, [
            "user" => $userId,
            "course" => $courseId,
            "description" => $description,
            "type" => $type,
            "moduleInstance" => $moduleInstance,
            "reward" => $reward
        ]);
    }
}
