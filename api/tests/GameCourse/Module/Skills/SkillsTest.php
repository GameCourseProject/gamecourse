<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Awards\AwardType;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use Utils\Utils;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class SkillsTest extends TestCase
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

        // Enable Skills module
        (new Awards($course))->setEnabled(true);
        $skills = new Skills($course);
        $skills->setEnabled(true);
        $this->module = $skills;
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
        if (Core::database()->tableExists(Skill::TABLE_SKILL))
            TestingUtils::resetAutoIncrement([SkillTree::TABLE_SKILL_TREE, Tier::TABLE_SKILL_TIER, Skill::TABLE_SKILL, Skill::TABLE_SKILL_DEPENDENCY]);
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Skills::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }
        $this->assertEquals(0, $this->module->getMaxExtraCredit());
        $this->assertTrue(file_exists($this->module->getDataFolder()));
        $this->assertTrue(Section::getSectionByName($this->course->getId(), $this->module::RULE_SECTION)->exists());
    }

    /**
     * @test
     * @throws Exception
     */
    public function copy()
    {
        // Given
        $copyTo = Course::addCourse("Course Copy", "CPY", "2021-2022", "#ffffff",
            null, null, false, false);

        (new Awards($copyTo))->setEnabled(true);
        $xpLevels = (new XPLevels($copyTo));
        $xpLevels->setEnabled(true);
        $xpLevels->updateMaxExtraCredit(1000);
        $skillsModule = new Skills($copyTo);
        $skillsModule->setEnabled(true);

        $this->module->updateMaxExtraCredit(500);

        $skillTree = SkillTree::addSkillTree($this->course->getId(), "Skill Tree", 1000);
        $tier1 = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $tier2 = Tier::addTier($skillTree->getId(), "Tier 2", 200);

        $skill1 = Skill::addSkill($tier1->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier1->getId(), "Skill2", null, null, false, false, []);

        $skill3 = Skill::addSkill($tier2->getId(), "Skill3", "#ffffff", null, false, false, [
            [$skill1->getId(), $skill2->getId()]
        ]);
        $courseDataFolder = API_URL . "/" . (new Course($this->course->getId()))->getDataFolder(false);
        $page = "<img src=\"https://some/random/image.png\"><img src=\"" . $courseDataFolder . "/" . $skill3->getDataFolder(false) . "/image.jpg\">";
        $skill3->setPage($page);
        file_put_contents($skill3->getDataFolder() . "/file.txt", "TEST");

        // When
        $this->module->copyTo($copyTo);

        // Then
        $this->assertEquals($this->module->getMaxExtraCredit(), $skillsModule->getMaxExtraCredit());

        $skillTrees = SkillTree::getSkillTrees($this->course->getId());
        $copiedSkillTrees = SkillTree::getSkillTrees($copyTo->getId());
        $this->assertSameSize($skillTrees, $copiedSkillTrees);
        foreach ($skillTrees as $i => $st) {
            $this->assertEquals($st["name"], $copiedSkillTrees[$i]["name"]);
            $this->assertEquals($st["maxReward"], $copiedSkillTrees[$i]["maxReward"]);

            $tiers = (new SkillTree($st["id"]))->getTiers();
            $copiedTiers = (new SkillTree($copiedSkillTrees[$i]["id"]))->getTiers();
            $this->assertSameSize($tiers, $copiedTiers);
            foreach ($tiers as $j => $t) {
                $this->assertEquals($t["name"], $copiedTiers[$j]["name"]);
                $this->assertEquals($t["reward"], $copiedTiers[$j]["reward"]);
                $this->assertEquals($t["position"], $copiedTiers[$j]["position"]);
                $this->assertEquals($t["isActive"], $copiedTiers[$j]["isActive"]);

                $skills = (new Tier($t["id"]))->getSkills();
                $copiedSkills = (new Tier($copiedTiers[$j]["id"]))->getSkills();
                $this->assertSameSize($skills, $copiedSkills);
                foreach ($skills as $k => $s) {
                    $this->assertEquals($s["name"], $copiedSkills[$k]["name"]);
                    $this->assertEquals($s["color"], $copiedSkills[$k]["color"]);
                    $this->assertEquals($s["isCollab"], $copiedSkills[$k]["isCollab"]);
                    $this->assertEquals($s["isExtra"], $copiedSkills[$k]["isExtra"]);
                    $this->assertEquals($s["isActive"], $copiedSkills[$k]["isActive"]);

                    $copiedSkill = new Skill($copiedSkills[$k]["id"]);
                    $courseDataFolder = API_URL . "/" . $copyTo->getDataFolder(false);
                    if ($s["id"] == $skill3->getId()) {
                        $this->assertEquals("<img src=\"https://some/random/image.png\"><img src=\"" . $courseDataFolder . "/" . $copiedSkill->getDataFolder(false) . "/image.jpg\">", $copiedSkills[$k]["page"]);
                        $this->assertTrue(file_exists($copiedSkill->getDataFolder() . "/file.txt"));
                        $this->assertEquals(file_get_contents((new Skill($s["id"]))->getDataFolder() . "/file.txt"), file_get_contents($copiedSkill->getDataFolder() . "/file.txt"));

                    } else {
                        $this->assertNull($copiedSkills[$k]["page"]);
                        $this->assertEmpty(Utils::getDirectoryContents($copiedSkill->getDataFolder()));
                    }

                    $this->assertSameSize($s["dependencies"], $copiedSkills[$k]["dependencies"]);
                    foreach ($s["dependencies"] as $dependencyId => $combo) {
                        foreach ($combo as $m => $sk) {
                            $this->assertEquals($sk["name"], $copiedSkills[$k]["dependencies"][$dependencyId + 1][$m]["name"]);
                        }
                    }

                    $this->assertEquals((new Rule($s["rule"]))->getText(), (new Rule($copiedSkills[$k]["rule"]))->getText());
                }
            }
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Skills::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
        $this->assertFalse(file_exists($this->module->getDataFolder()));
        $this->assertNull(Section::getSectionByName($this->course->getId(), $this->module::RULE_SECTION));
    }


    // Config

    /**
     * @test
     */
    public function getMaxXP()
    {
        $this->assertNull($this->module->getMaxXP());
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateMaxXP()
    {
        $this->module->updateMaxXP(1000);
        $this->assertEquals(1000, $this->module->getMaxXP());

        $this->module->updateMaxXP(null);
        $this->assertNull($this->module->getMaxXP());
    }

    /**
     * @test
     */
    public function getMaxExtraCredit()
    {
        $this->assertNull($this->module->getMaxExtraCredit());
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateMaxExtraCredit()
    {
        $this->module->updateMaxExtraCredit(1000);
        $this->assertEquals(1000, $this->module->getMaxExtraCredit());

        $this->module->updateMaxExtraCredit(null);
        $this->assertNull($this->module->getMaxExtraCredit());
    }


    // Skills

    /**
     * @test
     * @throws Exception
     */
    public function getUsersWithSkill()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skill1 = Skill::addSkill($tier->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier->getId(), "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($tier->getId(), "Skill3", null, null, false, false, []);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user2->getId(), $skill1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill2->getId(), "Award 3", 100);

        // Then
        $this->assertCount(2, $this->module->getUsersWithSkill($skill1->getId()));
        $this->assertCount(1, $this->module->getUsersWithSkill($skill2->getId()));
        $this->assertEmpty($this->module->getUsersWithSkill($skill3->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserSkills()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $skill1 = Skill::addSkill($tier->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier->getId(), "Skill2", null, null, false, false, []);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill2->getId(), "Award 3", 100);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule"];
        $nrKeys = count($keys);

        // Has skills
        $skills = $this->module->getUserSkills($user1->getId(), $skillTree->getId());
        $this->assertIsArray($skills);
        $this->assertCount(2, $skills);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                $this->assertEquals($skill[$key], ${"skill".($i+1)}->getData($key));
            }
        }

        // Doesn't have skills
        $this->assertEmpty($this->module->getUserSkills($user2->getId(), $skillTree->getId()));
    }


    // Wildcards

    /**
     * @test
     * @throws Exception
     */
    public function getUserTotalAvailableWildcards()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $wildcardTier = Tier::getWildcard($skillTree->getId());
        $skill1 = Skill::addSkill($tier->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier->getId(), "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($wildcardTier->getId(), "Skill3", null, null, false, false, []);
        $skill4 = Skill::addSkill($wildcardTier->getId(), "Skill4", null, null, false, false, []);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill3->getId(), "Award 1", 0);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill4->getId(), "Award 2", 0);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill1->getId(), "Award 3", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill2->getId(), "Award 4", 100, 1);
        $this->insertAward($this->course->getId(), $user2->getId(), $skill1->getId(), "Award 5", 100);

        // Then
        $this->assertEquals(1, $this->module->getUserTotalAvailableWildcards($user1->getId(), $skillTree->getId()));
        $this->assertEquals(0, $this->module->getUserTotalAvailableWildcards($user2->getId(), $skillTree->getId()));
     }

    /**
     * @test
     * @throws Exception
     */
    public function getUserTotalCompletedWildcards()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $wildcardTier = Tier::getWildcard($skillTree->getId());
        $skill1 = Skill::addSkill($tier->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier->getId(), "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($wildcardTier->getId(), "Skill3", null, null, false, false, []);
        $skill4 = Skill::addSkill($wildcardTier->getId(), "Skill4", null, null, false, false, []);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill3->getId(), "Award 1", 0);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill4->getId(), "Award 2", 0);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill1->getId(), "Award 3", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill2->getId(), "Award 4", 100, 1);
        $this->insertAward($this->course->getId(), $user2->getId(), $skill1->getId(), "Award 5", 100);

        // Then
        $this->assertEquals(2, $this->module->getUserTotalCompletedWildcards($user1->getId(), $skillTree->getId()));
        $this->assertEquals(0, $this->module->getUserTotalCompletedWildcards($user2->getId(), $skillTree->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserTotalUsedWildcards()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $wildcardTier = Tier::getWildcard($skillTree->getId());
        $skill1 = Skill::addSkill($tier->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier->getId(), "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($wildcardTier->getId(), "Skill3", null, null, false, false, []);
        $skill4 = Skill::addSkill($wildcardTier->getId(), "Skill4", null, null, false, false, []);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill3->getId(), "Award 1", 0);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill4->getId(), "Award 2", 0);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill1->getId(), "Award 3", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill2->getId(), "Award 4", 100, 1);
        $this->insertAward($this->course->getId(), $user2->getId(), $skill1->getId(), "Award 5", 100);

        // Then
        $this->assertEquals(1, $this->module->getUserTotalUsedWildcards($user1->getId(), $skillTree->getId()));
        $this->assertEquals(0, $this->module->getUserTotalUsedWildcards($user2->getId(), $skillTree->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function userHasWildcardAvailable()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $skillTree = SkillTree::addSkillTree($this->course->getId(), null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $wildcardTier = Tier::getWildcard($skillTree->getId());
        $skill1 = Skill::addSkill($tier->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier->getId(), "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($wildcardTier->getId(), "Skill3", null, null, false, false, []);
        $skill4 = Skill::addSkill($wildcardTier->getId(), "Skill4", null, null, false, false, []);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill3->getId(), "Award 1", 0);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill4->getId(), "Award 2", 0);

        $this->insertAward($this->course->getId(), $user1->getId(), $skill1->getId(), "Award 3", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $skill2->getId(), "Award 4", 100, 1);
        $this->insertAward($this->course->getId(), $user2->getId(), $skill1->getId(), "Award 5", 100);

        // Then
        $this->assertTrue($this->module->userHasWildcardAvailable($user1->getId(), $skillTree->getId()));
        $this->assertFalse($this->module->userHasWildcardAvailable($user2->getId(), $skillTree->getId()));
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function insertAward(int $courseId, int $userId, int $skillId, string $description, int $reward, int $nrWildcardsUsed = null)
    {
        $id = Core::database()->insert(Awards::TABLE_AWARD, [
            "user" => $userId,
            "course" => $courseId,
            "description" => $description,
            "type" => AwardType::SKILL,
            "moduleInstance" => $skillId,
            "reward" => $reward
        ]);

        if (!is_null($nrWildcardsUsed)) {
            Core::database()->insert(Skills::TABLE_AWARD_WILDCARD, [
                "award" => $id,
                "nrWildcardsUsed" => $nrWildcardsUsed
            ]);
        }
    }
}
