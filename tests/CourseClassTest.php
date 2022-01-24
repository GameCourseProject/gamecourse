<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';

use GameCourse\User;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\NullCourseUser;

use PHPUnit\Framework\TestCase;


class CourseClassTest extends TestCase
{
    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("game_course_user");
        Core::$systemDB->deleteAll("course");
    }

    public function testCourseConstructorSuccess(){
        
        //When
        $course = new Course(1);
        
        //Then
        $this->assertEquals(1, $course->getId());
        $this->assertEmpty($course->getModules());
    }

    public function testGetDataAllFieldsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "lastUpdate" => "2021-08-23 19:42:28"]);
        $course = new Course($courseId);

        //When
        $data = $course->getData();
        
        //Then
        $expectedData = array(
            "id" => $courseId, 
            "name" => "Multimedia Content Production", 
            "short" => "MCP", 
            "year" => "2019-2020", 
            "color" => "#79bf43", 
            "isActive" => 1, 
            "isVisible" => 1, 
            "roleHierarchy" => null,
            "theme" => null,
            "defaultLandingPage" => "",
            "lastUpdate" => "2021-08-23 19:42:28"
        );
        $this->assertEquals($expectedData, $data);
    }

    public function testGetDataNameFieldSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "lastUpdate" => "2021-08-23 19:42:28"]);
        $course = new Course($courseId);

        //When
        $data = $course->getData("name");
        
        //Then
        $expectedData = "Multimedia Content Production";
        $this->assertEquals($expectedData, $data);
    }

    public function testGetDataNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "lastUpdate" => "2021-08-23 19:42:28"]);
        $course = new Course(null);

        //When
        $data = $course->getData();
        
        //Then
        $this->assertEmpty($data);
    }

    public function testGetDataInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "lastUpdate" => "2021-08-23 19:42:28"]);
        $course = new Course($courseId + 1);

        //When
        $data = $course->getData();
        
        //Then
        $this->assertEmpty($data);
    }

    public function testSetDataSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "lastUpdate" => "2021-08-23 19:42:28"]);
        $course = new Course($courseId);

        $newName = "Produção de Conteúdo Multimédia";
        $newShort = "PCM";
        $newYear = "2017-2018";
        $newColor = "#f7a35c";
        $newIsActive = 0;
        $newIsVisible = 0;
        $newLastUpdate = "2021-08-20 19:00:45";
        $newDefaultLandingPage = "Main Page";
        $newTheme = "Dark";

        //When
        $course->setData("name", $newName);
        $course->setData("short", $newShort);
        $course->setData("year", $newYear);
        $course->setData("color", $newColor);
        $course->setData("isActive", $newIsActive);
        $course->setData("isVisible", $newIsVisible);
        $course->setData("lastUpdate", $newLastUpdate);
        $course->setData("theme", $newTheme);
        $course->setData("defaultLandingPage", $newDefaultLandingPage);

        //Then
        $newData = Core::$systemDB->selectMultiple("course", []);
        $expectedData = array( array(
            "id" => $courseId, 
            "name" => "Produção de Conteúdo Multimédia", 
            "short" => "PCM", 
            "year" => "2017-2018", 
            "color" => "#f7a35c", 
            "isActive" => 0, 
            "isVisible" => 0, 
            "roleHierarchy" => null,
            "theme" => "Dark",
            "defaultLandingPage" => "Main Page",
            "lastUpdate" => "2021-08-20 19:00:45"
        ));
        $this->assertEquals($expectedData, $newData);
        
    }

    public function testSetDataNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "lastUpdate" => "2021-08-23 19:42:28"]);
        $course = new Course(null);

        //When
        $course->setData("name", "Something Else");

        //Then
        $newData = Core::$systemDB->selectMultiple("course", []);
        $expectedData = array(array("id" => $courseId, "name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => null,"theme" => null,"defaultLandingPage" => "","lastUpdate" => "2021-08-23 19:42:28"));
        $this->assertEquals($expectedData, $newData);
    }

    public function testSetDataInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "lastUpdate" => "2021-08-23 19:42:28"]);
        $course = new Course($courseId + 2);

        //When
        $course->setData("name", "Something Else");

        //Then
        $newData = Core::$systemDB->selectMultiple("course", []);
        $expectedData = array(array("id" => $courseId, "name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => null,"theme" => null,"defaultLandingPage" => "","lastUpdate" => "2021-08-23 19:42:28"));
        $this->assertEquals($expectedData, $newData);
    }

    public function testGetUsersAllUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course = new Course($courseId);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);

        //When
        $users = $course->getUsers(false);
        
        //Then
        $this->assertCount(3, $users);

        $expectedUsers = array(
            array("id" => $user1, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1),
            array("id" => $user2, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0),
            array("id" => $user3, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0)
        );
        $this->assertEquals($expectedUsers, $users);

    }

    public function testGetUsersActiveUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course = new Course($courseId);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);

        //When
        $users = $course->getUsers();
        
        //Then
        $this->assertCount(2, $users);

        $expectedUsers = array(
            array("id" => $user1, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1),
            array("id" => $user3, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0)
        );
        $this->assertEquals($expectedUsers, $users);
    }

    public function testGetUsersActiveUsersTwoCoursesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course2Id = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,]);
        $course = new Course($course2Id);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);

        //When
        $users = $course->getUsers();
        
        //Then
        $this->assertEmpty($users);
    }

    public function testGetUsersWithRoleAllUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRole("Student", false);

        //Then
        $this->assertCount(3, $users);

        $expectedUsers = array(
            array("username" => "ist112122", "role" => "Student", "id" => $user1, "lastActivity" => null, "previousActivity" => null, "name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1),
            array("username" => "ist110001", "role" => "Student", "id" => $user2, "lastActivity" => null, "previousActivity" => null, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0),
            array("username" => "ist11101036", "role" => "Student", "id" => $user3, "lastActivity" => null, "previousActivity" => null, "name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0)
        );
        $this->assertEquals($expectedUsers, $users);
    }

    public function testGetUsersWithRoleActiveUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRole("Student");

        //Then
        $this->assertCount(2, $users);

        $expectedUsers = array(
            array("username" => "ist110001", "role" => "Student", "id" => $user2, "lastActivity" => null, "previousActivity" => null, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0),
            array("username" => "ist11101036", "role" => "Student", "id" => $user3, "lastActivity" => null, "previousActivity" => null, "name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0)
        );
        $this->assertEquals($expectedUsers, $users);
    }

    public function testGetUsersWithRoleNoUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $users = $course->getUsersWithRole("Student");

        //Then
        $this->assertEmpty($users);
    }

    public function testGetUsersWithRoleInexistingRole(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRole("Potato", false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersWithRoleNullRole(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRole(null, false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersWithRoleInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId + 1);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRole("Student", false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersWithRoleNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course(null);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRole("Student", false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersWithRoleIdAllUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRoleId($student, false);

        //Then
        $this->assertCount(3, $users);

        $expectedUsers = array(
            array("role" => $student, "id" => $user1, "lastActivity" => null, "previousActivity" => null, "name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1),
            array("role" => $student, "id" => $user2, "lastActivity" => null, "previousActivity" => null, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0),
            array("role" => $student, "id" => $user3, "lastActivity" => null, "previousActivity" => null, "name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0)
        );
        $this->assertEquals($expectedUsers, $users);
    }

    public function testGetUsersWithRoleIdActiveUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user5 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRoleId($student);

        //Then
        $this->assertCount(1, $users);

        $expectedUsers = array(array("role" => $student, "id" => $user2, "lastActivity" => null, "previousActivity" => null, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0));
        $this->assertEquals($expectedUsers, $users);
    }

    public function testGetUsersWithRoleIdNoUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $users = $course->getUsersWithRoleId($student);

        //Then
        $this->assertEmpty($users);
    }

    public function testGetUsersWithRoleIdInexistingRole(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRoleId($student + 1, false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersWithRoleIdNullRole(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRoleId(null, false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersWithRoleIdInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId + 1);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRoleId($student, false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersWithRoleIdNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course(null);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $users = $course->getUsersWithRoleId($student, false);

        //Then
        $this->assertEmpty($users);

    }

    public function testGetUsersIdsSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $ids = $course->getUsersIds();

        //Then
        $expectedIds = array($user1, $user2, $user4, $user5);
        $this->assertEquals($expectedIds, $ids);
    }

    public function testGetUsersIdsTwoCoursesSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course2Id = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,]);
        $course = new Course($course2Id);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $course2Id, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course2Id, "isActive" => 0]);

        //When
        $ids = $course->getUsersIds();

        //Then
        $expectedIds = array($user3, $user5);
        $this->assertEquals($expectedIds, $ids);
    }

    public function testGetUsersIdsNoUsersSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        //When
        $ids = $course->getUsersIds();

        //Then
        $this->assertEmpty($ids);
    }

    public function testGetUsersIdsInexistingCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId + 1);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $ids = $course->getUsersIds();

        //Then
        $this->assertEmpty($ids);
    }

    public function testGetUsersIdsNullCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course(null);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $ids = $course->getUsersIds();

        //Then
        $this->assertEmpty($ids);
    }

    public function testGetUsersNamesAllUsersSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $names = $course->getUsersNames();

        //Then
        $expectedNames = array("Noël Miller", "Ana Rita Gonçalves", "Simão Patrício", "Sabri M'Barki");
        $this->assertEquals($expectedNames, $names);
    }

    public function testGetUsersNamesActiveUsersSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $names = $course->getUsersNames(true);

        //Then
        $expectedNames = array("Ana Rita Gonçalves", "Sabri M'Barki");
        $this->assertEquals($expectedNames, $names);
    }

    public function testGetUsersNamesTwoCoursesSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course2Id = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,]);
        $course = new Course($course2Id);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $course2Id, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course2Id, "isActive" => 0]);

        //When
        $names = $course->getUsersNames();

        //Then
        $expectedNames = array("Marcus Notø", "Sabri M'Barki");
        $this->assertEquals($expectedNames, $names);
    }

    public function testGetUsersNamesNoUsers(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        //When
        $names = $course->getUsersNames();

        //Then
        $this->assertEmpty($names);
    }

    public function testGetUsersNamesNullCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course(null);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $names = $course->getUsersNames();

        //Then
        $this->assertEmpty($names);
    }

    public function testGetUsersNamesInexistingCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId + 1);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $names = $course->getUsersNames();

        //Then
        $this->assertEmpty($names);
    }

    public function testGetUserSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

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

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId, "isActive" => 1]);

        //When
        $user = $course->getUser($user1);

        //Then
        $this->assertInstanceOf(CourseUser::class, $user);

    }

    public function testGetUserInexistingUser(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user4, "username" => "ist197046", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);

        //When
        $user = $course->getUser($user4);

        //Then
        $this->assertInstanceOf(NullCourseUser::class, $user);

    }

    public function testGetUserInexistingCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course($courseId + 1);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);

        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);

        //When
        $user = $course->getUser($user3);

        //Then
        $this->assertInstanceOf(NullCourseUser::class, $user);

    }

    public function testGetUserNullCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Student"]])]);
        $course = new Course(null);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);

        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId, "isActive" => 0]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId, "isActive" => 1]);

        //When
        $user = $course->getUser($user3);

        //Then
        $this->assertInstanceOf(NullCourseUser::class, $user);

    }

    public function testGetRolesDataAllFieldsSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course($courseId);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRolesData();

        //Then
        $expectedRoles = array(
            array("landingPage" => "", "id" => $professor, "course" => $courseId, "name" => "Professor"),
            array("landingPage" => "", "id" => $student, "course" => $courseId, "name" => "Student")
        );
        $this->assertEquals($expectedRoles, $roles);
    }

    public function testGetRolesDataNameFieldSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course($courseId);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRolesData("name");

        //Then
        $expectedRoles = array( array("name" => "Professor"), array("name" => "Student"));
        $this->assertEquals($expectedRoles, $roles);
    }

    public function testGetRolesDataTwoCoursesSuccess(){
        //Given
        $courseId1 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $courseId2 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $course = new Course($courseId1);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId1, "name" => "Professor"]);
        $student1 = Core::$systemDB->insert("role", ["course" => $courseId1, "name" => "Student"]);

        $teacher = Core::$systemDB->insert("role", ["course" => $courseId2, "name" => "Teacher"]);
        $student2 = Core::$systemDB->insert("role", ["course" => $courseId2, "name" => "Student"]);

        //When
        $roles = $course->getRolesData();

        //Then
        $expectedRoles = array(
            array("landingPage" => "", "id" => $professor, "course" => $courseId1, "name" => "Professor"),
            array("landingPage" => "", "id" => $student1, "course" => $courseId1, "name" => "Student")
        );
        $this->assertEquals($expectedRoles, $roles);
    }

    public function testGetRolesDataNoRoles(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $course = new Course($courseId);

        //When
        $roles = $course->getRolesData();

        //Then
        $this->assertEmpty($roles);
    }

    public function testGetRolesDataInexistingCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course($courseId + 1);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRolesData();

        //Then
        $this->assertEmpty($roles);
    }

    public function testGetRolesDataNullCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course(null);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRolesData();

        //Then
        $this->assertEmpty($roles);
    }

    /**
     * @depends testGetRolesDataAllFieldsSuccess
     */
    public function testGetRolesAllFieldsSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course($courseId);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRoles();

        //Then
        $expectedRoles = array(
            array("landingPage" => "", "id" => $professor, "course" => $courseId, "name" => "Professor"),
            array("landingPage" => "", "id" => $student, "course" => $courseId, "name" => "Student")
        );
        $this->assertEquals($expectedRoles, $roles);
    }

    /**
     * @depends testGetRolesDataNameFieldSuccess
     */
    public function testGetRolesNameFieldSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course($courseId);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRoles("name");

        //Then
        $expectedRoles = array( array("name" => "Professor"), array("name" => "Student"));
        $this->assertEquals($expectedRoles, $roles);
    }

    /**
     * @depends testGetRolesDataTwoCoursesSuccess
     */
    public function testGetRolesTwoCoursesSuccess(){
        //Given
        $courseId1 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $courseId2 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $course = new Course($courseId1);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId1, "name" => "Professor"]);
        $student1 = Core::$systemDB->insert("role", ["course" => $courseId1, "name" => "Student"]);

        $teacher = Core::$systemDB->insert("role", ["course" => $courseId2, "name" => "Teacher"]);
        $student2 = Core::$systemDB->insert("role", ["course" => $courseId2, "name" => "Student"]);

        //When
        $roles = $course->getRoles();

        //Then
        $expectedRoles = array(
            array("landingPage" => "", "id" => $professor, "course" => $courseId1, "name" => "Professor"),
            array("landingPage" => "", "id" => $student1, "course" => $courseId1, "name" => "Student")
        );
        $this->assertEquals($expectedRoles, $roles);
    }

    /**
     * @depends testGetRolesDataNoRoles
     */
    public function testGetRolesNoRoles(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $course = new Course($courseId);

        //When
        $roles = $course->getRoles();

        //Then
        $this->assertEmpty($roles);
    }

    /**
     * @depends testGetRolesDataInexistingCourse
     */
    public function testGetRolesInexistingCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course($courseId + 1);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRoles();

        //Then
        $this->assertEmpty($roles);
    }

    /**
     * @depends testGetRolesDataNullCourse
     */
    public function testGetRolesNullCourse(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $course = new Course(null);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        //When
        $roles = $course->getRoles();

        //Then
        $this->assertEmpty($roles);
    }

    public function testSetRolesNoRolesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course = new Course($courseId);

        $roles = array(
            array("id" => null, "name" => "Teacher", "landingPage" => "Student List"),
            array("id" => null, "name" => "Student", "landingPage" => "Leaderboard"),
            array("id" => null, "name" => "Observer", "landingPage" => "")
        );

        //When
        $course->setRoles($roles);

        //Then
        $newRoles = Core::$systemDB->selectMultiple("role", ["course" => $courseId]);
        $expectedRoles = array(
            array("id" => $newRoles[0]["id"], "course" => $courseId, "name" => "Teacher", "landingPage" => "Student List"),
            array("id" => $newRoles[1]["id"], "course" => $courseId, "name" => "Student", "landingPage" => "Leaderboard"),
            array("id" => $newRoles[2]["id"], "course" => $courseId, "name" => "Observer", "landingPage" => "")
        );



        $this->assertEquals($expectedRoles, $newRoles);
    }

    public function testSetRolesUpdateRolesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course = new Course($courseId);

        $professor = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Professor"]);
        $toBeDeleted = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Headmaster"]);

        $roles = array(
            array("id" => $professor, "name" => "Teacher", "landingPage" => "Student List"),
            array("id" => null, "name" => "Student", "landingPage" => "Leaderboard"),
            array("id" => null, "name" => "Observer", "landingPage" => "")
        );

        //When
        $course->setRoles($roles);

        //Then
        $newRoles = Core::$systemDB->selectMultiple("role", ["course" => $courseId]);
        $expectedRoles = array(
            array("id" => $professor, "course" => $courseId, "name" => "Professor", "landingPage" => "Student List"),
            array("id" => $newRoles[1]["id"], "course" => $courseId, "name" => "Student", "landingPage" => "Leaderboard"),
            array("id" => $newRoles[2]["id"], "course" => $courseId, "name" => "Observer", "landingPage" => "")
        );

        $this->assertEquals($expectedRoles, $newRoles);
    }

    public function testGetRolesHierarchySuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Multimedia Content Production", 
            "short" => "MCP", "year" => "2019-2020", 
            "color" => "#79bf43", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [
                ["name" => "Achiever"], 
                ["name" => "Regular"],
                ["name" => "Halfhearted"], 
                ["name" => "Underachiever"]]
            ]])
        ]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $role1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $role2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $role3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $role4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        $course = new Course($courseId);

        //When
        $hierarchy = $course->getRolesHierarchy();

        //Then
        $expectedHierarchy = array(
            array("id" => $student, "name" => "Student", "children" => array(
                    array("id" => $role1, "name" => "Achiever"),
                    array("id" => $role2, "name" => "Regular"),
                    array("id" => $role3, "name" => "Halfhearted"),
                    array("id" => $role4, "name" => "Underachiever")
                )
            )
        );

        $this->assertEquals($expectedHierarchy, $hierarchy);


    }

    public function testGetRolesHierarchyTwoCoursesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Multimedia Content Production", 
            "short" => "MCP", "year" => "2019-2020", 
            "color" => "#79bf43", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [
                ["name" => "Achiever"], 
                ["name" => "Regular"],
                ["name" => "Halfhearted"], 
                ["name" => "Underachiever"]]
            ]])
        ]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $role1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $role2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $role3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $role4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        $course = new Course($courseId);

        $courseId2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1,"roleHierarchy" => json_encode([["name" => "Professor"], ["name" => "Student"]])]);
        $student2 = Core::$systemDB->insert("role", ["course" => $courseId2, "name" => "Student"]);
        $professor = Core::$systemDB->insert("role", ["course" => $courseId2, "name" => "Professor"]);

        //When
        $hierarchy = $course->getRolesHierarchy();

        //Then
        $expectedHierarchy = array(
            array("id" => $student, "name" => "Student", "children" => array(
                    array("id" => $role1, "name" => "Achiever"),
                    array("id" => $role2, "name" => "Regular"),
                    array("id" => $role3, "name" => "Halfhearted"),
                    array("id" => $role4, "name" => "Underachiever")
                )
            )
        );

        $this->assertEquals($expectedHierarchy, $hierarchy);


    }

    public function testGetRolesHierarchyNoRoles(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Multimedia Content Production", 
            "short" => "MCP", "year" => "2019-2020", 
            "color" => "#79bf43", 
            "isActive" => 1, 
            "isVisible" => 1,
        ]);

        $course = new Course($courseId);

        //When
        $hierarchy = $course->getRolesHierarchy();

        //Then
        $this->assertEmpty($hierarchy);
    }

    public function testGetRolesHierarchyEmptyHierarchy(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Multimedia Content Production", 
            "short" => "MCP", "year" => "2019-2020", 
            "color" => "#79bf43", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([])
        ]);

        $course = new Course($courseId);

        //When
        $hierarchy = $course->getRolesHierarchy();

        //Then
        $this->assertEmpty($hierarchy);
    }

    public function testInsertBasicCourseDataSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Multimedia Content Production", 
            "short" => "MCP", "year" => "2019-2020", 
            "color" => "#79bf43", 
            "isActive" => 1, 
            "isVisible" => 1
        ]);

        //When
        $teacherId = Course::insertBasicCourseData(Core::$systemDB, $courseId);

        //Then
        $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $expectedTeacherRole = new stdClass;
        $expectedTeacherRole->name = 'Teacher';
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedWatcherRole = new stdClass;
        $expectedWatcherRole->name = 'Watcher';
        $expectedHierarchy = [$expectedTeacherRole, $expectedStudentRole, $expectedWatcherRole];
        $this->assertEquals($expectedHierarchy, $hierarchy);

        $roles = Core::$systemDB->selectMultiple("role", ["course" => $courseId]);
        $expectedRoles = array(
            array("name" => "Teacher"),
            array("name" => "Student"),
            array("name" => "Watcher")
        );
        
    }

    //ToDo: implement tests for the rest of the methods

}