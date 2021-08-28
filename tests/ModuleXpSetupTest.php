<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use Modules\XP\XPLevels;

use PHPUnit\Framework\TestCase;


class ModuleXPSetupTest extends TestCase
{
    protected $xp;

    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function setUp():void {
        $this->xp = new XPLevels();
    }

    protected function tearDown():void {
        Core::$systemDB->delete("course", [], null, [["id", 0]]);
        Core::$systemDB->delete("game_course_user", [], null, [["id", 0]]);
        Core::$systemDB->executeQuery(
            "drop table if exists user_xp; 
            drop table if exists level;"
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
        $result = $this->xp->addTables("xp", "level");

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertTrue($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
    }

    public function testAddTablesAlreadyExistsFail(){
        
        //Given
        Core::$systemDB->executeQuery(
            "create table level(
                id int unsigned auto_increment primary key,
                number int not null,
                course int unsigned not null,
                goal int not null,
                description varchar(200),
                foreign key(course) references course(id) on delete cascade,
                unique(number,course)
            );"
        );
        
        //When
        $result = $this->xp->addTables("xp", "level");

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertFalse($result);
        $this->assertCount(1, $table1);
        $this->assertEmpty($table2);
    }

    public function testSetupDataOnlyCourseNoUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]])]);
        Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $this->xp->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);

        $levels = Core::$systemDB->selectMultiple("level", ["course" => $courseId]);
        $expectedLevels = array(
            array("id" => $levels[0]["id"], "course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL")
        );
        $this->assertEquals($expectedLevels, $levels);

        $userXp = Core::$systemDB->selectMultiple("user_xp", ["course" => $courseId]);

        $this->assertEmpty($userXp);
    }

    public function testSetupDataOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]])]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user4, "username" => "ist197046", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user5, "username" => "ist1100956", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);


        //When
        $this->xp->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);

        $levels = Core::$systemDB->selectMultiple("level", ["course" => $courseId]);
        $expectedLevels = array(
            array("id" => $levels[0]["id"], "course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL")
        );
        $this->assertEquals($expectedLevels, $levels);

        $userXp = Core::$systemDB->selectMultiple("user_xp", ["course" => $courseId]);
        $expectedUserXp = array(
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user1),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user2),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user3),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user5)
        );
        $this->assertEquals($expectedUserXp, $userXp);
    }

    public function testSetupDataMultipleCallsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]])]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user4, "username" => "ist197046", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user5, "username" => "ist1100956", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);


        //When
        $this->xp->setupData($courseId);
        $this->xp->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);

        $levels = Core::$systemDB->selectMultiple("level", ["course" => $courseId]);
        $expectedLevels = array(
            array("id" => $levels[0]["id"], "course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL")
        );
        $this->assertEquals($expectedLevels, $levels);

        $userXp = Core::$systemDB->selectMultiple("user_xp", ["course" => $courseId]);
        $expectedUserXp = array(
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user1),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user2),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user3),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user5)
        );
        $this->assertEquals($expectedUserXp, $userXp);
    }

    public function testSetupDataExistingTablesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]])]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user4, "username" => "ist197046", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user5, "username" => "ist1100956", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        Core::$systemDB->executeQuery(
            "create table level(
                id int unsigned auto_increment primary key,
                number int not null,
                course int unsigned not null,
                goal int not null,
                description varchar(200),
                foreign key(course) references course(id) on delete cascade,
                unique(number,course)
            );
            create table user_xp(
                course  int unsigned not null,
                user int unsigned not null,
                xp int not null,
                level int unsigned not null,
                primary key (course,user),
                foreign key(course) references course(id) on delete cascade,
                foreign key(user) references course_user(id) on delete cascade,
                foreign key(level) references level(id)
            );"
        );


        //When
        $this->xp->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);

        $levels = Core::$systemDB->selectMultiple("level", ["course" => $courseId]);
        $expectedLevels = array(
            array("id" => $levels[0]["id"], "course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL")
        );
        $this->assertEquals($expectedLevels, $levels);

        $userXp = Core::$systemDB->selectMultiple("user_xp", ["course" => $courseId]);
        $expectedUserXp = array(
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user1),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user2),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user3),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user4),
            array("course" => $courseId, "xp" => 0, "level" => $levels[0]["id"], "user" => $user5)
        );
        $this->assertEquals($expectedUserXp, $userXp);
    }

    public function testSetupDataExistingLevelZeroSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]])]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user4, "username" => "ist197046", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user5, "username" => "ist1100956", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        Core::$systemDB->executeQuery(
            "create table level(
                id int unsigned auto_increment primary key,
                number int not null,
                course int unsigned not null,
                goal int not null,
                description varchar(200),
                foreign key(course) references course(id) on delete cascade,
                unique(number,course)
            );
            create table user_xp(
                course  int unsigned not null,
                user int unsigned not null,
                xp int not null,
                level int unsigned not null,
                primary key (course,user),
                foreign key(course) references course(id) on delete cascade,
                foreign key(user) references course_user(id) on delete cascade,
                foreign key(level) references level(id)
            );"
        );

        $levelZero = Core::$systemDB->insert("level", ["course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL"]);

        //When
        $this->xp->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);

        $levels = Core::$systemDB->selectMultiple("level", ["course" => $courseId]);
        $expectedLevels = array(
            array("id" => $levelZero, "course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL")
        );
        $this->assertEquals($expectedLevels, $levels);

        $userXp = Core::$systemDB->selectMultiple("user_xp", ["course" => $courseId]);
        $expectedUserXp = array(
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user1),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user2),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user3),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user4),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user5)
        );
        $this->assertEquals($expectedUserXp, $userXp);
    }

    public function testSetupDataExistingUserXpSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]])]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user4, "username" => "ist197046", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user5, "username" => "ist1100956", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        Core::$systemDB->executeQuery(
            "create table level(
                id int unsigned auto_increment primary key,
                number int not null,
                course int unsigned not null,
                goal int not null,
                description varchar(200),
                foreign key(course) references course(id) on delete cascade,
                unique(number,course)
            );
            create table user_xp(
                course  int unsigned not null,
                user int unsigned not null,
                xp int not null,
                level int unsigned not null,
                primary key (course,user),
                foreign key(course) references course(id) on delete cascade,
                foreign key(user) references course_user(id) on delete cascade,
                foreign key(level) references level(id)
            );"
        );
        
        $levelZero = Core::$systemDB->insert("level", ["course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL"]);
        Core::$systemDB->insert("user_xp", ["course" => $courseId, "user" => $user2, "xp" => 0 ,"level" => $levelZero]);
        Core::$systemDB->insert("user_xp", ["course" => $courseId, "user" => $user5, "xp" => 0 ,"level" => $levelZero]);
        
        //When
        $this->xp->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);

        $levels = Core::$systemDB->selectMultiple("level", ["course" => $courseId]);
        $expectedLevels = array(
            array("id" => $levelZero, "course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL")
        );
        $this->assertEquals($expectedLevels, $levels);

        $userXp = Core::$systemDB->selectMultiple("user_xp", ["course" => $courseId]);
        $expectedUserXp = array(
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user1),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user2),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user3),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user4),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user5)
        );
        $this->assertEquals($expectedUserXp, $userXp);
    }

    public function testSetupDataTwoCoursesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]])]);
        $courseId2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user4, "username" => "ist197046", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user5, "username" => "ist1100956", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId]);

        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId2]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId2]);

        $student = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $student2 = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId2]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student2]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student2]);

        Core::$systemDB->executeQuery(
            "create table level(
                id int unsigned auto_increment primary key,
                number int not null,
                course int unsigned not null,
                goal int not null,
                description varchar(200),
                foreign key(course) references course(id) on delete cascade,
                unique(number,course)
            );
            create table user_xp(
                course  int unsigned not null,
                user int unsigned not null,
                xp int not null,
                level int unsigned not null,
                primary key (course,user),
                foreign key(course) references course(id) on delete cascade,
                foreign key(user) references course_user(id) on delete cascade,
                foreign key(level) references level(id)
            );"
        );
        
        $levelZero = Core::$systemDB->insert("level", ["course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL"]);
        Core::$systemDB->insert("user_xp", ["course" => $courseId, "user" => $user2, "xp" => 0 ,"level" => $levelZero]);
        Core::$systemDB->insert("user_xp", ["course" => $courseId, "user" => $user5, "xp" => 0 ,"level" => $levelZero]);
        
        //When
        $this->xp->setupData($courseId);
        $this->xp->setupData($courseId2);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);

        $levels = Core::$systemDB->selectMultiple("level", []);
        $expectedLevels = array(
            array("id" => $levelZero, "course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL"),
            array("id" => $levels[1]["id"], "course" => $courseId2, "number" => 0, "goal" => 0, "description" => "AWOL")
        );
        $this->assertEquals($expectedLevels, $levels);

        $userXp = Core::$systemDB->selectMultiple("user_xp", []);
        $expectedUserXp = array(
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user1),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user2),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user3),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user4),
            array("course" => $courseId, "xp" => 0, "level" => $levelZero, "user" => $user5),
            array("course" => $courseId2, "xp" => 0, "level" => $levels[1]["id"], "user" => $user3),
            array("course" => $courseId2, "xp" => 0, "level" => $levels[1]["id"], "user" => $user4),
        );
        $this->assertEquals($expectedUserXp, $userXp);
    }

    /**
     * @dataProvider setupDataFailProvider
     */
    public function testSetupDataFail($courseId){
        
        try {

            $this->xp->setupData($courseId);
            $this->fail("PDOException should have been thrown for invalid argument on setupData.");

        } catch (\PDOException $e) {
            $table1 = Core::$systemDB->executeQuery("show tables like 'level';")->fetchAll(\PDO::FETCH_ASSOC);
            $table2 = Core::$systemDB->executeQuery("show tables like 'user_xp';")->fetchAll(\PDO::FETCH_ASSOC);

            $this->assertCount(1, $table1);
            $this->assertCount(1, $table2);

            $xpUser = Core::$systemDB->selectMultiple("user_xp");
            $this->assertEmpty($xpUser);

            $levelZero = Core::$systemDB->selectMultiple("level");
            $this->assertEmpty($levelZero);
        }
        
    }

}