<?php
namespace GameCourse\Module\Awards;

use ReflectionClass;
use Utils\Utils;

/**
 * This class holds all award types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class AwardType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const ASSIGNMENT = "assignment";
    const BADGE = "badge";
    const BONUS = "bonus";
    const EXAM = "exam";
    const LAB = "labs";
    const POST = "post";
    const PRESENTATION = "presentation";
    const QUIZ = "quiz";
    const SKILL = "skill";
    const STREAK = "streak";
    const TOKENS = "tokens";
    // NOTE: insert here new types of awards & update awards tables


    /**
     * Gets an award type description.
     *
     * @param string $type
     * @return string
     */
    public static function description(string $type): string
    {
        switch ($type) {
            case AwardType::ASSIGNMENT: return "Assignment";
            case AwardType::BADGE: return "Badge";
            case AwardType::BONUS: return "Bonus";
            case AwardType::EXAM: return "Exam";
            case AwardType::LAB: return "Lab Assignment";
            case AwardType::POST: return "Post";
            case AwardType::PRESENTATION: return "Presentation";
            case AwardType::QUIZ: return "Quiz";
            case AwardType::SKILL: return "Skill Tree";
            case AwardType::STREAK: return "Streak";
            case AwardType::TOKENS: return "Token(s)";
            default: return ucfirst($type);
        }
    }

    /**
     * Gets an award type image URL.
     * Option for image style as 'outline' or 'solid'.
     *
     * @param string $type
     * @param string $style
     * @param string $extension
     * @return string
     */
    public static function image(string $type, string $style = 'outline' | 'solid', string $extension = "svg" | "jpg"): string
    {
        return API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . Awards::ID . "/assets/award-types/" .
            $type . "_" . $style . "." . $extension;
    }

    /**
     * Checks if a given award type is available in the system.
     *
     * @param string $type
     * @return bool
     */
    public static function exists(string $type): bool
    {
        $awardTypeClass = new ReflectionClass(AwardType::class);
        $awardTypes = array_values($awardTypeClass->getConstants());
        return in_array($type, $awardTypes);
    }
}
