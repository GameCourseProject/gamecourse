<?php
namespace GameCourse\Module\Fenix;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class FenixTest extends TestCase
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

        // Enable Fenix module
        $FenixModule = new Fenix($course);
        $FenixModule->setEnabled(true);
        $this->module = $FenixModule;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE]);
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

    /**
     * @test
     * @throws Exception
     */
    public function importFenixStudents()
    {
        // Given
        $file = "Username,Número,Nome,Email,Agrupamento PCM Labs,Turno Teórica,Turno Laboratorial,Total de Inscrições,Tipo de Inscrição,Estado Matrícula,Curso\n";
        $file .= "ist11111,11111,João Silva,js@tecnico.ulisboa.pt,33 - PCM264L05,PCM264T02,,1,Normal,Matriculado,Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006\n";
        $file .= "ist122222,22222,Joana Silva,jos@tecnico.ulisboa.pt,34 - PCM264L06,PCM264T01,,1,Normal,Matriculado,Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015\n";
        $file .= "ist133333,33333,José Silva,jose@tecnico.ulisboa.pt,34 - PCM264L06,PCM264T01,,2,Normal,Matriculado,Mestrado Bolonha em Engenharia Aeroespacial - MEAER 2021";

        // When
        $nrStudentsImported = $this->module->importFenixStudents($file);

        // Then
        $this->assertEquals(3, $nrStudentsImported);

        $student1 = $this->course->getCourseUserByUsername("ist11111", AuthService::FENIX);
        $this->assertEquals("ist11111", $student1->getUsername());
        $this->assertEquals(11111, $student1->getStudentNumber());
        $this->assertEquals("João Silva", $student1->getName());
        $this->assertEquals("js@tecnico.ulisboa.pt", $student1->getEmail());
        $this->assertEquals("LEIC-A", $student1->getMajor());

        $student2 = $this->course->getCourseUserByUsername("ist122222", AuthService::FENIX);
        $this->assertEquals("ist122222", $student2->getUsername());
        $this->assertEquals(22222, $student2->getStudentNumber());
        $this->assertEquals("Joana Silva", $student2->getName());
        $this->assertEquals("jos@tecnico.ulisboa.pt", $student2->getEmail());
        $this->assertEquals("MEIC-T", $student2->getMajor());

        $student3 = $this->course->getCourseUserByUsername("ist133333", AuthService::FENIX);
        $this->assertEquals("ist133333", $student3->getUsername());
        $this->assertEquals(33333, $student3->getStudentNumber());
        $this->assertEquals("José Silva", $student3->getName());
        $this->assertEquals("jose@tecnico.ulisboa.pt", $student3->getEmail());
        $this->assertEquals("MEAER", $student3->getMajor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function importFenixStudentsAlreadyInSystem()
    {
        // Given
        User::addUser("Joana Silva", "ist122222", AuthService::FENIX, null, 22222, null, "LEIC-A", false, false);

        $file = "Username,Número,Nome,Email,Agrupamento PCM Labs,Turno Teórica,Turno Laboratorial,Total de Inscrições,Tipo de Inscrição,Estado Matrícula,Curso\n";
        $file .= "ist11111,11111,João Silva,js@tecnico.ulisboa.pt,33 - PCM264L05,PCM264T02,,1,Normal,Matriculado,Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006\n";
        $file .= "ist122222,22222,Joana Silva,jos@tecnico.ulisboa.pt,34 - PCM264L06,PCM264T01,,1,Normal,Matriculado,Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015\n";
        $file .= "ist133333,33333,José Silva,jose@tecnico.ulisboa.pt,34 - PCM264L06,PCM264T01,,2,Normal,Matriculado,Mestrado Bolonha em Engenharia Aeroespacial - MEAER 2021";

        // When
        $nrStudentsImported = $this->module->importFenixStudents($file);

        // Then
        $this->assertEquals(3, $nrStudentsImported);

        $student1 = $this->course->getCourseUserByUsername("ist11111", AuthService::FENIX);
        $this->assertEquals("ist11111", $student1->getUsername());
        $this->assertEquals(11111, $student1->getStudentNumber());
        $this->assertEquals("João Silva", $student1->getName());
        $this->assertEquals("js@tecnico.ulisboa.pt", $student1->getEmail());
        $this->assertEquals("LEIC-A", $student1->getMajor());

        $student2 = $this->course->getCourseUserByUsername("ist122222", AuthService::FENIX);
        $this->assertEquals("ist122222", $student2->getUsername());
        $this->assertEquals(22222, $student2->getStudentNumber());
        $this->assertEquals("Joana Silva", $student2->getName());
        $this->assertEquals("jos@tecnico.ulisboa.pt", $student2->getEmail());
        $this->assertEquals("MEIC-T", $student2->getMajor());

        $student3 = $this->course->getCourseUserByUsername("ist133333", AuthService::FENIX);
        $this->assertEquals("ist133333", $student3->getUsername());
        $this->assertEquals(33333, $student3->getStudentNumber());
        $this->assertEquals("José Silva", $student3->getName());
        $this->assertEquals("jose@tecnico.ulisboa.pt", $student3->getEmail());
        $this->assertEquals("MEAER", $student3->getMajor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function importFenixStudentsAlreadyInCourse()
    {
        // Given
        $user = User::addUser("Joana Silva", "ist122222", AuthService::FENIX, null, 22222, null, "LEIC-A", false, false);
        $this->course->addUserToCourse($user->getId(), "Teacher", null, false);

        $file = "Username,Número,Nome,Email,Agrupamento PCM Labs,Turno Teórica,Turno Laboratorial,Total de Inscrições,Tipo de Inscrição,Estado Matrícula,Curso\n";
        $file .= "ist11111,11111,João Silva,js@tecnico.ulisboa.pt,33 - PCM264L05,PCM264T02,,1,Normal,Matriculado,Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006\n";
        $file .= "ist122222,22222,Joana Silva,jos@tecnico.ulisboa.pt,34 - PCM264L06,PCM264T01,,1,Normal,Matriculado,Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015\n";
        $file .= "ist133333,33333,José Silva,jose@tecnico.ulisboa.pt,34 - PCM264L06,PCM264T01,,2,Normal,Matriculado,Mestrado Bolonha em Engenharia Aeroespacial - MEAER 2021";

        // When
        $nrStudentsImported = $this->module->importFenixStudents($file);

        // Then
        $this->assertEquals(2, $nrStudentsImported);

        $student1 = $this->course->getCourseUserByUsername("ist11111", AuthService::FENIX);
        $this->assertEquals("ist11111", $student1->getUsername());
        $this->assertEquals(11111, $student1->getStudentNumber());
        $this->assertEquals("João Silva", $student1->getName());
        $this->assertEquals("js@tecnico.ulisboa.pt", $student1->getEmail());
        $this->assertEquals("LEIC-A", $student1->getMajor());

        $student2 = $this->course->getCourseUserByUsername("ist122222", AuthService::FENIX);
        $this->assertEquals("ist122222", $student2->getUsername());
        $this->assertEquals(22222, $student2->getStudentNumber());
        $this->assertEquals("Joana Silva", $student2->getName());
        $this->assertEquals("jos@tecnico.ulisboa.pt", $student2->getEmail());
        $this->assertEquals("MEIC-T", $student2->getMajor());

        $student3 = $this->course->getCourseUserByUsername("ist133333", AuthService::FENIX);
        $this->assertEquals("ist133333", $student3->getUsername());
        $this->assertEquals(33333, $student3->getStudentNumber());
        $this->assertEquals("José Silva", $student3->getName());
        $this->assertEquals("jose@tecnico.ulisboa.pt", $student3->getEmail());
        $this->assertEquals("MEAER", $student3->getMajor());
    }
}
