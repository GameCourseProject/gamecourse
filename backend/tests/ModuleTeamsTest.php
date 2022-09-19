<?php
chdir('/Applications/XAMPP/xamppfiles/htdocs/gamecourse-2/gamecourse/backend/');
//set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
echo __DIR__;

require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use \Modules\Teams\Teams;

use PHPUnit\Framework\TestCase;

class ModuleTeamsTest extends TestCase
{
    protected $teams;

    public static function setUpBeforeClass():void {
        Core::init();
        echo "yo";
        Core::$systemDB->executeQuery(file_get_contents("modules/" . Teams::ID . "/create.sql"));
    }

    protected function setUp():void {
        $this->teams = new Teams();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
    }

    public static function tearDownAfterClass(): void {
        Core::$systemDB->executeQuery(file_get_contents("modules/" . Teams::ID . "/delete.sql"));
    }

    public function invalidImportFileProvider(){
        return array(
            array(""),                                               //empty file
            array(null),                                             //null file
            array("username;group\n")               //empty file with header
        );
    }

    /**
     * @dataProvider saveMaxSuccessProvider
     */

    public function testSaveMaxStudentsSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2021-2022", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $teams = Core::$systemDB->insert(Teams::TABLE_CONFIG, ["course" => $courseId]);

        //When
        $this->teams->saveNumberOfTeamElements(4, $courseId);

        //Then
        $maxStudents = Core::$systemDB->selectMultiple(Teams::TABLE_CONFIG, []);
        $expectedMaxStudents = array(
            array( "course" => $maxStudents[0]["course"], "maxReward" => 4)
        );
        $this->assertEquals($expectedMaxStudents, $maxStudents);
    }

    public function testSaveMaxStudentsSameValueSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2021-2022", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $teams = Core::$systemDB->insert(Teams::TABLE_CONFIG, ["course" => $courseId]);

        //When
        $this->teams->saveNumberOfTeamElements(3, $courseId);

        //Then
        $maxStudents = Core::$systemDB->selectMultiple(Teams::TABLE_CONFIG, []);
        $expectedMaxStudents = array(
            array( "course" => $maxStudents[0]["course"], "maxReward" => 3)
        );
        $this->assertEquals($expectedMaxStudents, $maxStudents);
    }

    /**
     * @dataProvider invalidSaveMaxProvider
     */
    public function testSaveMaxRewardInvalidValuesFail(){

        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2021-2022", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $teams = Core::$systemDB->insert(Teams::TABLE_CONFIG, ["course" => $courseId]);

        try {
            $this->teams->saveNumberOfTeamElements(null, $courseId);
            $this->fail("PDOException should have been thrown for invalid value on saveMax.");

        } catch (\PDOException $e) {
            $maxStudents = Core::$systemDB->selectMultiple(Teams::TABLE_CONFIG, []);
            $expectedMaxStudents = array(
                array( "course" => $maxStudents[0]["course"], "maxReward" => 3)
            );
            $this->assertEquals($expectedMaxStudents, $maxStudents);
        }
    }

    /**
     * @dataProvider saveMaxSuccessProvider
     */
    public function testGetMaxStudentsSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $teams = Core::$systemDB->insert(Teams::TABLE_CONFIG, ["course" => $courseId, "maxReward" => 5]);

        //When
        $maxStudents = $this->teams->getNumberOfTeamMembers($courseId);
        //Then
        $this->assertEquals(5, $maxStudents);
    }
    
    /**
     */
    public function testImportItemsNoHeaderNoReplaceSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2021-2022", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        $this->teams->addCourseUsers($courseId);

        $file = "username;group
                ist199999;3 - PCML04
                ist199998;3 - PCML04
                ist199997;1 - PCML06
                ist199996;2 - PCML06
                ist199995;2 - PCML04
                ist199994;2 - PCML06
                ist199993;1 - PCML06";

        //When
        $newItems = $this->teams->importTeams($file, false);

        //Then
        $addedTeams = Core::$systemDB->selectMultiple(Teams::TABLE, [], "*", "id");
        $expectedTeams = array(
            array("id" => $addedTeams[0]["id"], "course" => $courseId, "teamNumber" => 1),
            array("id" => $addedTeams[1]["id"], "course" => $courseId, "teamNumber" => 2),
            array("id" => $addedTeams[3]["id"], "course" => $courseId, "teamNumber" => 3)
        );

        $members = Core::$systemDB->selectMultiple(Teams::TABLE_MEMBERS, [], "*", "id");
        $membersTeam1 = Core::$systemDB->selectMultiple(Teams::TABLE_MEMBERS, ["teamId" => $addedTeams[0]["id"]], "*", "id");
        $membersTeam2 = Core::$systemDB->selectMultiple(Teams::TABLE_MEMBERS, ["teamId" => $addedTeams[1]["id"]], "*", "id");
        $membersTeam3 = Core::$systemDB->selectMultiple(Teams::TABLE_MEMBERS, ["teamId" => $addedTeams[2]["id"]], "*", "id");
        $expectedMembers = array(
            array("teamId" => $membersTeam1[0]["id"],  "memberId" => $membersTeam1[0]["memberId"]),
            array("teamId" => $membersTeam1[0]["id"], "memberId" => $membersTeam1[0]["memberId"]),
            array("teamId" => $membersTeam2[0]["id"], "memberId" => $membersTeam2[0]["memberId"]),
            array("teamId" => $membersTeam2[0]["id"], "memberId" => $membersTeam2[0]["memberId"]),
            array("teamId" => $membersTeam3[0]["id"], "memberId" => $membersTeam3[0]["memberId"]),
            array("teamId" => $membersTeam3[0]["id"], "memberId" => $membersTeam3[0]["memberId"]),
            array("teamId" => $membersTeam3[0]["id"], "memberId" => $membersTeam3[0]["memberId"])
        );

        $this->assertEquals(3, $newItems);
        $this->assertEquals($expectedTeams, $addedTeams);
        $this->assertEquals($expectedMembers, $members);

        rmdir($folder);
    }



}