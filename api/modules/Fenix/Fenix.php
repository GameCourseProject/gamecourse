<?php
namespace GameCourse\Module\Fenix;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Course\Course;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use Utils\Utils;

/**
 * This is the Fenix module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Fenix extends Module
{
    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Fenix";  // NOTE: must match the name of the class
    const NAME = "Fénix";
    const DESCRIPTION = "Allows Fénix students to be imported into the course.";
    const TYPE = ModuleType::UTILITY;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        // Nothing to do here
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    public function disable()
    {
        // Nothing to do here
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getGeneralInputs(): array
    {
        return [
            [
                "name" => "Import students",
                "btnText" => "Import",
                "successMsg" => "Students imported into course successfully",
                "contents" => [
                    [
                        "contentType" => "container",
                        "classList" => "flex flex-wrap items-center",
                        "contents" => [
                            [
                                "contentType" => "item",
                                "width" => "full",
                                "type" => InputType::FILE,
                                "id" => "studentsFile",
                                "value" => null,
                                "options" => [
                                    "accept" => [".csv"],
                                    "size" => "sm",
                                    "color" => "primary",
                                    "label" => "Upload file",
                                    "required" => true
                                ],
                                "helper" => "File with students got from Fénix"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveGeneralInputs(array $inputs)
    {
        foreach ($inputs as $input) {
            if ($input["id"] == "studentsFile") $this->importFenixStudents($input["value"]);
        }
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * Imports students into the course from a .csv file got
     * from Fénix with all students enrolled.
     *
     * @param string $file
     * @return int
     * @throws Exception
     */
    public function importFenixStudents(string $file): int
    {
        $headers = ["Username", "Número", "Nome", "Email", "Agrupamento", "Turno Teórico", "Turno Laboratorial", "Total de Inscrições", "Tipo de Inscrição", "Estado Matrícula", "Curso"];
        return Utils::importFromCSV($headers, function ($user, $indexes) {
            $username = $user[$indexes["Username"]];
            $studentNumber = intval($user[$indexes["Número"]]);
            $name = $user[$indexes["Nome"]];
            $email = $user[$indexes["Email"]];

            // Parse major
            $parts = explode(" - ", $user[$indexes["Curso"]]);
            $major = explode(" ", array_pop($parts))[0];

            // Add user to the system
            $user = User::getUserByUsername($username, AuthService::FENIX) ?? User::getUserByStudentNumber($studentNumber);
            if ($user) {
                $user->setName($name);
                if (!$user->getEmail() && !empty(trim($email))) $user->setEmail($email);
                $user->setMajor($major);
                $user->setActive(true);

            } else $user = User::addUser($name, $username, AuthService::FENIX, $email, $studentNumber, null, $major, false, true);

            // Enroll user in the course
            $courseUser = new CourseUser($user->getId(), $this->course);
            if ($courseUser->exists()) {
                $courseUser->setRoles(["Student"]);
                $courseUser->setActive(true);

            } else {
                $this->course->addUserToCourse($user->getId(), "Student");
                return 1;
            }
            return 0;
        }, $file);
    }
}
