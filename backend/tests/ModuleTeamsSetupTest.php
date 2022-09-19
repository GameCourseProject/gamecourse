<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use Modules\Teams\Teams;

use PHPUnit\Framework\TestCase;


class ModuleTeamsSetupTest extends TestCase
{
    protected $teams;

    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function setUp():void {
        $this->teams = new Teams();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
        Core::$systemDB->executeQuery(
            "drop table if exists teams;
            drop table if exists teams_members;
            drop table if exists teams_config;
            drop table if exists teams_xp;"
        );
    }

    //Data Providers
    public function setupDataFailProvider(){
        return array(
            array(null),    //null couse
            array(-1)        //inexisting course
        );
    }

    public function testAddTablesSuccess(){

        //When
        $result = $this->teams->addTables(Teams::ID, Teams::TABLE_CONFIG);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_XP . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_MEMBERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertTrue($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
    }

    public function testAddTablesAlreadyExistsFail(){
        
        //Given
        Core::$systemDB->executeQuery(
            "create table teams(
                id          int unsigned auto_increment primary key,
                course      int unsigned not null,
                teamNumber  int unsigned,
                teamName    varchar(70) ,
                foreign key(course) references course(id) on delete cascade
            );
            
            create table teams_members(
                id          int unsigned auto_increment primary key,
                teamId      int unsigned not null,
                memberId    int unsigned not null,
                foreign key(teamId) references teams(id) on delete cascade,
                foreign key(memberId) references game_course_user(id) on delete cascade
            );
            
            create table teams_xp(
                course  int unsigned not null,
                teamId 	    int unsigned not null,
                xp          int not null,
                level       int not null,
                primary key (course,teamId),
                foreign key(course) references course(id) on delete cascade,
                foreign key(teamId) references teams(id) on delete cascade
            );  "
        );
        
        //When
        $result = $this->teams->addTables(Teams::ID, Teams::TABLE);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_XP . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_MEMBERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertFalse($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertEmpty($table4);
    }

    public function testSetupDataOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2021-2022", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        //When
        $this->teams->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_XP . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_MEMBERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);

        $teamsConfig = Core::$systemDB->selectMultiple(Teams::TABLE_CONFIG);
        $expectedTeamsConfig = array(
            array("id" => $teamsConfig[0]["id"], "course" => $courseId, "nrTeamMembers" => 3)
        );
        $this->assertEquals($expectedTeamsConfig, $teamsConfig);

        $this->assertDirectoryExists($folder . "/teams");
        rmdir($folder . "/teams");
        rmdir($folder);
    }


    public function testSetupDataTwoCoursesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2021-2022", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder1 = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder1);

        $courseId2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2021-2022", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $folder2 = COURSE_DATA_FOLDER . '/' . $courseId2 . '-' . "Forensics CyberSecurity";
        mkdir($folder2);


        //When
        $this->teams->setupData($courseId);
        $this->teams->setupData($courseId2);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_XP . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_MEMBERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);

        $teamsConfig = Core::$systemDB->selectMultiple(Teams::TABLE_CONFIG);
        $expectedTeamsConfig = array(
            array("id" => $teamsConfig[0]["id"], "course" => $courseId, "nrTeamMembers" => 3),
            array("id" => $teamsConfig[0]["id"], "course" => $courseId2, "nrTeamMembers" => 3)

        );
        $this->assertEquals($expectedTeamsConfig, $teamsConfig);


        $this->assertDirectoryExists($folder1 . "/teams");
        $this->assertDirectoryExists($folder2 . "/teams");
        rmdir($folder1 . "/teams");
        rmdir($folder1);
        rmdir($folder2 . "/teams");
        rmdir($folder2);
    }

    /**
     * @dataProvider setupDataFailProvider
     */
    public function testSetupDataFail($courseId){
        
        try {

            $this->teams->setupData($courseId);
            $this->fail("PDOException should have been thrown for invalid argument on setupData.");

        } catch (\PDOException $e) {
            $table1 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table2 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_XP . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table3 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_MEMBERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table4 = Core::$systemDB->executeQuery("show tables like '" . Teams::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);

            $this->assertCount(1, $table1);
            $this->assertCount(1, $table2);
            $this->assertCount(1, $table3);
            $this->assertCount(1, $table4);

            $teamsConfig = Core::$systemDB->selectMultiple(Teams::TABLE_CONFIG);

            $this->assertEmpty($teamsConfig);

        }
        
    }
}