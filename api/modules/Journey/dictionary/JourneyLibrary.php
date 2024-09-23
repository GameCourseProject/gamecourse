<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Journey\Journey;
use GameCourse\Module\Journey\JourneyPath;
use GameCourse\Module\Skills\Skill;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

class JourneyLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "journey";    // NOTE: must match the name of the class
    const NAME = "Journey";
    const DESCRIPTION = "Provides access to information regarding journeys.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to obtain information of the Journey paths. A path has the following structure:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
            "id": 3,
            "course": 1,
            "name": "Influencer",
            "color": "#E91E63",
            "isActive": true
        }</code></pre>
        </div>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockJourney(int $id = null, string $name = null, bool $active = null) : array
    {
        return [
            "id" => $id ?: Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => $name ?: Core::dictionary()->faker()->text(20),
            "color" => Core::dictionary()->faker()->hexColor(),
            "isActive" => $active ?: Core::dictionary()->faker()->boolean(),
            "skills" => array_map(function () {
                return $this->mockSkill();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 7)))
        ];
    }

    private function mockSkill(int $id = null, string $name = null, bool $active = null, bool $extra = null, bool $collab = null) : array
    {
        return [
            "id" => $id ?: Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => $name ?: Core::dictionary()->faker()->text(20),
            "color" => Core::dictionary()->faker()->hexColor(),
            "reward" => Core::dictionary()->faker()->numberBetween(100, 1000),
            "isCollab" => $collab ?: Core::dictionary()->faker()->boolean(),
            "isExtra" => $extra ?: Core::dictionary()->faker()->boolean(),
            "isActive" => $active ?: Core::dictionary()->faker()->boolean(),
            "dependencies" => array_map(function () {
                return ["name" => Core::dictionary()->faker()->text(20)];
            }, range(1, Core::dictionary()->faker()->numberBetween(0, 3)))
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("id",
                [["name" => "journey", "optional" => false, "type" => "journey"]],
                "Gets journey's id.",
                ReturnType::NUMBER,
                $this,
                "journey.id(%journey)\nor (shorthand notation):\n%journey.id"
            ),
            new DFunction("name",
                [["name" => "journey", "optional" => false, "type" => "journey"]],
                "Gets journey's name.",
                ReturnType::TEXT,
                $this,
                "journey.name(%journey)\nor (shorthand notation):\n%journey.name"
            ),
            new DFunction("color",
                [["name" => "journey", "optional" => false, "type" => "journey"]],
                "Gets journey's color.",
                ReturnType::TEXT,
                $this,
                "journey.color(%journey)\nor (shorthand notation):\n%journey.color"
            ),
            new DFunction("skills",
                [["name" => "journey", "optional" => false, "type" => "journey"]],
                "Gets journey's skills.",
                ReturnType::SKILLS_COLLECTION,
                $this,
                "journey.skills(%journey)\nor (shorthand notation):\n%journey.skills"
            ),
            new DFunction("getMaxXP",
                [],
                "Gets maximum XP each student can earn with journeys.",
                ReturnType::NUMBER,
                $this,
                "journey.getMaxXP()"
            ),
            new DFunction("getJourneyById",
                [["name" => "journeyId", "optional" => false, "type" => "int"]],
                "Gets a journey by its ID.",
                ReturnType::OBJECT,
                $this,
                "journey.getJourneyById(%journey.id)"
            ),
            new DFunction("getJourneyByName",
                [["name" => "name", "optional" => false, "type" => "string"]],
                "Gets a journey by its name.",
                ReturnType::OBJECT,
                $this,
                "journey.getJourneyByName('YouTuber')"
            ),
            new DFunction("getJourneys",
                [["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets journeys of course. Option to filter by state (active or not).",
                ReturnType::JOURNEYS_COLLECTION,
                $this,
                "journey.getJourneys(true)"
            ),
            new DFunction("getJourneyXP",
                [["name" => "journey", "optional" => false, "type" => "int"]],
                "Gets the total earnable XP from the journey.",
                ReturnType::NUMBER,
                $this,
                "journey.getJourneyXP(%journeyId)"
            ),
        ];
    }

    /*** --------- Getters ---------- ***/

    /**
     * Gets journey's id.
     *
     * @param $journey
     * @return ValueNode
     * @throws Exception
     */
    public function id($journey): ValueNode
    {
        // NOTE: on mock data, journey will be mocked
        if (is_array($journey)) $id = $journey["id"];
        elseif (is_object($journey) && method_exists($journey, 'getId')) $id = $journey->getId();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a journey.");
        return new ValueNode($id, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets journey's name.
     *
     * @param $journey
     * @return ValueNode
     * @throws Exception
     */
    public function name($journey): ValueNode
    {
        // NOTE: on mock data, journey will be mocked
        if (is_array($journey)) $name = $journey["name"];
        elseif (is_object($journey) && method_exists($journey, 'getName')) $name = $journey->getName();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a journey.");
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets journey's name.
     *
     * @param $journey
     * @return ValueNode
     * @throws Exception
     */
    public function color($journey): ValueNode
    {
        // NOTE: on mock data, journey will be mocked
        if (is_array($journey)) $color = $journey["color"];
        elseif (is_object($journey) && method_exists($journey, 'getColor')) $color = $journey->getColor();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a journey.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets journey's skills.
     *
     * @param $journey
     * @return ValueNode
     * @throws Exception
     */
    public function skills($journey): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $skills = $journey["skills"];
        } elseif (is_array($journey)) {
            $skills = JourneyPath::getJourneyPathById($journey["id"])->getSkills(true);
        } elseif (is_object($journey) && method_exists($journey, 'getSkills')) {
            $skills = $journey->getSkills(true);
        } else {
            throw new InvalidArgumentException("Invalid type for first argument: expected a journey.");
        }
        return new ValueNode($skills, Core::dictionary()->getLibraryById(SkillsLibrary::ID));
    }

    /*** ---------- Config ---------- ***/

    /**
     * Gets maximum XP each student can earn with journeys.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxXP(): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $maxXP = Core::dictionary()->faker()->numberBetween(5000, 20000);

        } else {
            $journeyModule = new Journey($course);
            $maxXP = $journeyModule->getMaxXP();
        }
        return new ValueNode($maxXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /*** --------- General ---------- ***/

    /**
     * Gets a journey by its ID.
     *
     * @param int $journeyId
     * @return ValueNode
     * @throws Exception
     */
    public function getJourneyById(int $journeyId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $journey = $this->mockJourney($journeyId);

        } else $journey = JourneyPath::getJourneyPathById($journeyId);
        return new ValueNode($journey, $this);
    }

    /**
     * Gets a journey by its name.
     *
     * @param string $name
     * @return ValueNode
     * @throws Exception
     */
    public function getJourneyByName(string $name): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $journey = $this->mockJourney(null, $name);

        } else $journey = JourneyPath::getJourneyPathByName($courseId, $name);
        return new ValueNode($journey, $this);
    }

    /**
     * Gets journeys of course.
     *
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getJourneys(bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $journeys = array_map(function () use ($active) {
                return $this->mockJourney(null, null, $active);
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else $journeys = JourneyPath::getJourneyPaths($courseId, $active);

        return new ValueNode($journeys, $this);
    }

    /**
     * Gets the total earnable XP from the journey.
     *
     * @param int $journeyId
     * @return ValueNode
     * @throws Exception
     */
    public function getJourneyXP(int $journeyId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $XP = Core::dictionary()->faker()->numberBetween(1000, 10000);

        } else {
            $journey = JourneyPath::getJourneyPathById($journeyId);
            $XP = $journey->getTotalXP();
        }
        return new ValueNode($XP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

}
