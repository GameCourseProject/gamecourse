<?php

namespace Modules\Fenix;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use Modules\XP\XPLevels;

class Fenix extends Module
{
    const ID = 'fenix';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function initAPIEndpoints()
    {
        /**
         * Import students from Fenix into the course.
         *
         * @param int $courseId
         * @param $file
         */
        API::registerFunction(self::ID, 'importFenixStudents', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'file');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(['nrStudents' => $this->importFenixStudents($course, API::getValue('file'))]);
        });
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_personalized_config(): bool
    {
        return true;
    }

    public function get_personalized_function(): string
    {
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function importFenixStudents(Course $course, $file): int
    {
        $nrStudentsImported = 0;
        $separator = ";";
        $headers = ["Username", "Número", "Nome", "Email", "Turno Teórica", "Turno Laboratorial",
                    "Total de Inscrições", "Tipo de Inscrição", "Estado Matrícula", "Curso", "Estatutos"];
        $lines = array_filter(explode("\n", $file), function ($line) { return !empty($line); });

        if (count($lines) > 0) {
            // Check if has header to ignore it
            $firstLine = array_map('trim', explode($separator, trim($lines[0])));
            $hasHeaders = true;
            foreach ($headers as $header) {
                if (!in_array($header, $firstLine)) $hasHeaders = false;
            }
            if ($hasHeaders) array_shift($lines);

            // Import each student
            foreach ($lines as $line) {
                $student = array_map('trim', explode($separator, trim($line)));

                $name = $student[array_search("Nome", $headers)];
                $username = $student[array_search("Username", $headers)];
                $studentNumber = $student[array_search("Número", $headers)];
                $email = $student[array_search("Email", $headers)];

                // Find major
                $parts = explode(" - ", $student[array_search("Curso", $headers)]);
                $major = explode(" ", array_pop($parts))[0];

                // Add student to system and course
                $roleId = Core::$systemDB->select("role", ["name" => "Student", "course" => $course->getId()], "id");
                $user = User::getUserByStudentNumber($studentNumber) ?? User::getUserByUsername($username);
                if (!$user) { // create user
                    $userId = User::addUserToDB($name, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $courseUser = new CourseUser($userId, $course);
                    $courseUser->addCourseUserToDB($roleId);
                    Core::$systemDB->insert(XPLevels::TABLE_XP, ["course" => $course->getId(), "user" => $userId, "xp" => 0, "level" => 1]);
                    $nrStudentsImported++;

                } else { // edit user
                    $user->editUser($name, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $courseUser = new CourseUser($user->getId(), $course);
                    if (!CourseUser::userExists($course->getId(), $user->getId())) $courseUser->addCourseUserToDB($roleId);
                    else $courseUser->editCourseUser($user->getId(), $course->getId(), $major);
                    Core::$systemDB->update(XPLevels::TABLE_XP, ["xp" => 0, "level" => 1], ["course" => $course->getId(), "user" => $user->getId()]);
                }
            }
        }

        return $nrStudentsImported;
    }
}

ModuleLoader::registerModule(array(
    'id' => Fenix::ID,
    'name' => 'Fenix',
    'description' => 'Allows Fenix students to be imported into GameCourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new Fenix();
    }
));

