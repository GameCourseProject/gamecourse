<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\AutoGame\AutoGame;
use GameCourse\Course\Course;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class ParticipationsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "participations";    // NOTE: must match the name of the class
    const NAME = "Participations";
    const DESCRIPTION = "Provides access to information regarding participations.";

    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("getParticipations",
                "Gets participations on a given course based on a set of options.",
                ReturnType::ARRAY,
                $this
            ),
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Gets participations on a given course based on a set of options.
     *
     * @example participations.getParticipations() --> gets all participations
     * @example participations.getParticipations(123) --> gets all participations for user with ID = 123
     * @example participations.getParticipations(123, "initial bonus") --> gets all participations for user with ID = 123 of type 'initial bonus'
     * @example participations.getParticipations(null, null, null, null, "2022-12-01 00:00:00", "2022-12-04 23:59:59") --> gets all participations for user with ID = 123 of type 'initial bonus'
     *
     * @param bool $mockData
     * @param Course $course
     * @param int|null $userId
     * @param string|null $type
     * @param int|null $rating
     * @param int|null $evaluatorId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $source
     * @return ValueNode
     */
    public function getParticipations(bool $mockData, Course $course, int $userId = null, string $type = null,
                                      int $rating = null, int $evaluatorId = null, string $startDate = null,
                                      string $endDate = null, string $source = null): ValueNode
    {
        return new ValueNode(AutoGame::getParticipations($course->getId(), $userId, $type, $rating, $evaluatorId,
            $startDate, $endDate, $source));
    }
}
