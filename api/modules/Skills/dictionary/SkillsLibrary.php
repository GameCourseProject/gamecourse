<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Course\Course;
use InvalidArgumentException;

class SkillsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "skills";    // NOTE: must match the name of the class
    const NAME = "Skills";
    const DESCRIPTION = "Provides access to information regarding skills.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to obtain information of Skills. A skill has the following structure:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
            "id": 1,
            "course": 1,
            "tier": 6,
            "name": "Album Cover",
            "color": "#FF76BC",
            "page": "&lt;p&gt;Content of the page, html!&lt;/p&gt;",
            "isCollab": false,
            "isExtra": false,
            "isActive": false,
            "position": 0,
            "rule": 99,
            "dependencies": []
        }</code></pre>
        </div><br>
        <p>You can obtain a specific skill by its id or by its name, and then access its attributes. For example:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{skills.getSkillById(1).name}</code></pre>
        </div>
        <p>would return "Album Cover".</p><br>
        <p>With this namespace you can also obtain every single skill available in the course, using the function</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{skills.getSkills()}</code></pre>
        </div>
        <p>This returns every single skill in the course, however, the function allows you to filter by active, extra, and
        collaborative skills using optional arguments. This is detailed in the documentation of the function!</p>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockUser(int $id = null, string $email = null, string $studentNumber = null) : array
    {
        return [
            "id" => $id ?: Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => Core::dictionary()->faker()->name(),
            "email" => $email ?: Core::dictionary()->faker()->email(),
            "major" => Core::dictionary()->faker()->text(5),
            "nickname" => Core::dictionary()->faker()->text(10),
            "studentNumber" => $studentNumber ?: Core::dictionary()->faker()->numberBetween(11111, 99999),
            "theme" => null,
            "username" => $email ?: Core::dictionary()->faker()->email(),
            "image" => null,
            "lastActivity" => Core::dictionary()->faker()->dateTimeThisYear(),
            "landingPage" => null,
            "isActive" => true
        ];
    }

    private function mockSkill(int $id = null, string $name = null, bool $active = null, bool $extra = null, bool $collab = null) : array
    {
        return [
            "id" => $id ?: Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => $name ?: Core::dictionary()->faker()->text(20),
            "color" => Core::dictionary()->faker()->hexColor(),
            "isCollab" => $collab ?: Core::dictionary()->faker()->boolean(),
            "isExtra" => $extra ?: Core::dictionary()->faker()->boolean(),
            "isActive" => $active ?: Core::dictionary()->faker()->boolean(),
            "dependencies" => array_map(function () {
                return ["name" => Core::dictionary()->faker()->text(20)];
            }, range(1, Core::dictionary()->faker()->numberBetween(0, 3)))
        ];
    }

    private function mockAward($userId, $type = null) : array
    {
        return [
            "id" => Core::dictionary()->faker()->numberBetween(0, 100),
            "course" => 0,
            "user" => $userId,
            "description" => Core::dictionary()->faker()->text(20),
            "type" => $type ?: Core::dictionary()->faker()->randomElement(['assignment','badge','bonus','exam','labs','post','presentation','quiz','skill','streak','tokens']),
            "moduleInstance" => null,
            "reward" => Core::dictionary()->faker()->numberBetween(50, 500),
            "date" => Core::dictionary()->faker()->dateTimeThisYear()->format("Y-m-d H:m:s")
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("id",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's id.",
                ReturnType::NUMBER,
                $this,
            "%skill.id"
            ),
            new DFunction("name",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's name.",
                ReturnType::TEXT,
                $this,
                "%skill.name"
            ),
            new DFunction("color",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's color.",
                ReturnType::TEXT,
                $this,
                "%skill.color"
            ),
            new DFunction("dependencies",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's dependencies.",
                ReturnType::COLLECTION,
                $this,
                "%skill.dependencies"
            ),
            new DFunction("isCollab",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "True if the skill is collaborative.",
                ReturnType::TEXT,
                $this,
                "%skill.isCollab"
            ),
            new DFunction("isExtra",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "True if the skill is extra.",
                ReturnType::TEXT,
                $this,
                "%skill.isExtra"
            ),
            new DFunction("getSkillById",
                [["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets a skill by its ID.",
                ReturnType::OBJECT,
                $this,
                "skills.getSkillById(%skill.id)"
            ),
            new DFunction("getSkillByName",
                [["name" => "name", "optional" => false, "type" => "string"]],
                "Gets a skill by its name.",
                ReturnType::OBJECT,
                $this,
                "skills.getSkillByName('Audiobook')"
            ),
            new DFunction("getSkills",
                [["name" => "active", "optional" => true, "type" => "bool"],
                 ["name" => "extra", "optional" => true, "type" => "bool"],
                 ["name" => "collab", "optional" => true, "type" => "bool"]],
                "Gets skills of course. Option to filter by skill state, if it counts towards extra XP or not, and for collaborative.",
                ReturnType::SKILLS_COLLECTION,
                $this,
                "skills.getSkills(true)"
            ),
            new DFunction("getUserSkillAttempts",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets a skill's cost for a user by its ID.",
                ReturnType::OBJECT,
                $this,
            "skills.getUserSkillAttempts(%user, %skill.id)"
            ),
            new DFunction("getUserSkillCost",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets a skill's cost for a user by its ID.",
                ReturnType::OBJECT,
                $this,
                "skills.getUserSkillCost(%user, %skill.id)"
            ),
            new DFunction("isSkillAvailableForUser",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"],
                    ["name" => "skillTreeId", "optional" => false, "type" => "int"]],
                "Gets if a skill is available for a user given its ID.",
                ReturnType::BOOLEAN,
                $this,
                "skills.isSkillAvailableForUser(%user, %skill.id, %skillTree.id)"
            ),
            new DFunction("isSkillCompletedByUser",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets if a skill is completed by a user given its ID.",
                ReturnType::BOOLEAN,
                $this,
                "skills.isSkillCompletedByUser(%user, %skill.id)"
            ),
            new DFunction("getUserTotalAvailableWildcards",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillTreeId", "optional" => false, "type" => "int"]
                ],
                "Gets the number of available Wildcards of a student.",
                ReturnType::NUMBER,
                $this,
                "skills.getUserTotalAvailableWildcards(%user, %skillTree.id)"
            ),
            new DFunction("getUserSkillUsedWildcards",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillTreeId", "optional" => false, "type" => "int"]
                ],
                "Gets the number of used Wildcards by a student on a skill.",
                ReturnType::NUMBER,
                $this,
                "skills.getUserSkillUsedWildcards(%user, %skillTree.id)"
            ),
            new DFunction("getUsersWithSkill",
                [["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets users who have earned a given skill.",
                ReturnType::USERS_COLLECTION,
                $this,
                "skills.getUsersWithSkill(%skill.id)"
            ),
            new DFunction("getUserSkillsAwards",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "collab", "optional" => true, "type" => "bool"],
                    ["name" => "extra", "optional" => true, "type" => "bool"],
                    ["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets awards of type 'skill' obtained by a given user. Some options available.",
                ReturnType::AWARDS_COLLECTION,
                $this,
                "skills.getUserSkillsAwards(%user, false, false, true)"
            ),
            new DFunction("getUserSkillsTotalReward",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "collab", "optional" => true, "type" => "bool"],
                    ["name" => "extra", "optional" => true, "type" => "bool"],
                    ["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets total skills reward for a given user. Some options available.",
                ReturnType::NUMBER,
                $this,
                "skills.getUserSkillsTotalReward(%user, false, false, true)"
            )
        ];
    }

    /*** --------- Getters ---------- ***/

    /**
     * Gets skill's id.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function id($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $id = $skill["id"];
        elseif (is_object($skill) && method_exists($skill, 'getId')) $id = $skill->getId();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a skill.");
        return new ValueNode($id, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets skill's name.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function name($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $name = $skill["name"];
        elseif (is_object($skill) && method_exists($skill, 'getName')) $name = $skill->getName();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a skill.");
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets skill's name.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function color($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $color = $skill["color"];
        elseif (is_object($skill) && method_exists($skill, 'getColor')) $color = $skill->getColor();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a skill.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets skill's dependencies.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function dependencies($skill): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $dependencies = $skill["dependencies"];

        } else {
            if (is_array($skill)) $skill = new Skill($skill["id"]);
            elseif (is_object($skill) && method_exists($skill, 'getId')) $skill = new Skill($skill->getId());
            else throw new InvalidArgumentException("Invalid type for first argument: expected a skill.");

            $dependencies = [];
            foreach ($skill->getDependencies() as $combo) {
                $str = '';
                foreach ($combo as $index => $skill) {
                    $str .= $skill["name"] . ($index != count($combo) - 1 ? ' + ' : '');
                }
                $dependencies[] = ["name" => $str];
            }
        };
        return new ValueNode($dependencies, $this);
    }

    /**
     * Gets skill's isCollab.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function isCollab($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $isCollab = $skill["isCollab"];
        elseif (is_object($skill) && method_exists($skill, 'isCollab')) $isCollab = $skill->isCollab();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a skill.");
        return new ValueNode($isCollab, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Gets skill's isExtra.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function isExtra($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $isExtra = $skill["isExtra"];
        elseif (is_object($skill) && method_exists($skill, 'isExtra')) $isExtra = $skill->isExtra();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a skill.");
        return new ValueNode($isExtra, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    
    /*** --------- General ---------- ***/

    /**
     * Gets a skill by its ID.
     *
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function getSkillById(int $skillId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $skill = $this->mockSkill($skillId);

        } else $skill = Skill::getSkillById($skillId);
        return new ValueNode($skill, $this);
    }

    /**
     * Gets a skill by its name.
     *
     * @param string $name
     * @return ValueNode
     * @throws Exception
     */
    public function getSkillByName(string $name): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $skill = $this->mockSkill(null, $name);

        } else $skill = Skill::getSkillByName($courseId, $name);
        return new ValueNode($skill, $this);
    }

    /**
     * Gets skills of course.
     *
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getSkills(bool $active = null, bool $extra = null, bool $collab = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $skills = array_map(function () use ($active, $extra, $collab) {
                return $this->mockSkill(null, null, $active, $extra, $collab);
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else $skills = Skill::getSkills($courseId, $active, $extra, $collab);

        return new ValueNode($skills, $this);
    }

    /**
     * Gets a skill's attempts for a user by their IDs in the system.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillAttempts(int $userId, int $skillId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $cost = Core::dictionary()->faker()->numberBetween(0, 100);

        } else $cost = Skill::getSkillById($skillId)->getSkillAttemptsOfUser($userId);
        return new ValueNode($cost, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a skill's cost for a user by their IDs in the system.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillCost(int $userId, int $skillId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $cost = Core::dictionary()->faker()->numberBetween(0, 100);

        } else $cost = Skill::getSkillById($skillId)->getSkillCostForUser($userId);
        return new ValueNode($cost, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Indicates if a skill is available for a user.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function isSkillAvailableForUser(int $userId, int $skillId, int $skillTreeId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $available = Core::dictionary()->faker()->boolean();

        } else $available = Skill::getSkillById($skillId)->availableForUser($userId, $skillTreeId);

        return new ValueNode($available, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Indicates if a skill is completed by a user.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function isSkillCompletedByUser(int $userId, int $skillId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $available = Core::dictionary()->faker()->boolean();

        } else $available = Skill::getSkillById($skillId)->completedByUser($userId);

        return new ValueNode($available, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Gets the number of available Wildcards for a given user.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserTotalAvailableWildcards(int $userId, int $skillTreeId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $available = Core::dictionary()->faker()->numberBetween(0, 2);

        } else {
            $course = new Course($courseId);
            $skillsModule = new Skills($course);
            $available = $skillsModule->getUserTotalAvailableWildcards($userId, $skillTreeId);
        }

        return new ValueNode($available, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    
    /**
     * Gets the number of available Wildcards for a given user.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillUsedWildcards(int $userId, int $skillId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $used = Core::dictionary()->faker()->numberBetween(0, 2);

        } else {
            $used = Skill::getSkillById($skillId)->wildcardsUsed($userId);
        }

        return new ValueNode($used, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets users who have earned a skill.
     *
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function getUsersWithSkill(int $skillId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $users = array_map(function () {
                return $this->mockUser();
            }, range(1, Core::dictionary()->faker()->numberBetween(0, 5)));

        } else {
            $skillsModule = new Skills($course);
            $users = $skillsModule->getUsersWithSkill($skillId);
        }
        return new ValueNode($users, Core::dictionary()->getLibraryById(UsersLibrary::ID));
    }

    /**
     * Gets skill awards for a given user.
     * Option for collaborative:
     *  - if null --> gets total reward for all skills
     *  - if false --> gets total reward only for skills that are not collaborative
     *  - if true --> gets total reward only for skills that are collaborative
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $collab
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillsAwards(int $userId, bool $collab = null, bool $extra = null, bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $awards = array_map(function () use ($userId) {
                return $this->mockAward($userId, "skill");
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else {
            $awardsModule = new Awards($course);
            $awards = $awardsModule->getUserSkillsAwards($userId, $collab, $extra, $active);
        }
        return new ValueNode($awards, Core::dictionary()->getLibraryById(AwardsLibrary::ID));
    }

    /**
     * Gets total skills reward for a given user.
     * Option for collaborative:
     *  - if null --> gets total reward for all skills
     *  - if false --> gets total reward only for skills that are not collaborative
     *  - if true --> gets total reward only for skills that are collaborative
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $collab
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillsTotalReward(int $userId, bool $collab = null, bool $extra = null, bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards($course);
            $reward = $awardsModule->getUserSkillsTotalReward($userId, $collab, $extra, $active);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
