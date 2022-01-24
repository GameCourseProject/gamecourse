<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\User;
use GameCourse\Course;
use GameCourse\CourseUser;

use PHPUnit\Framework\TestCase;


class CourseUserClassTest extends TestCase
{
    protected $course;

    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function setUp():void {
        $this->course = $this->createMock(Course::class);
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("game_course_user");
        Core::$systemDB->deleteAll("course");
    }

    public function testCourseUserConstructorGetCourseGetId(){
        
        $id = 1;

        $user = new CourseUser($id, $this->course);

        $this->assertEquals($this->course, $user->getCourse());
        $this->assertEquals($id, $user->getId());

    }

    public function testAddCourseUserToDBNoRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $this->course->method("getId")->willReturn($courseId);
        
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $user = new CourseUser($id, $this->course);
        
        //When
        $user->addCourseUserToDB();
            

        //Then
        $roleData = Core::$systemDB->select("user_role", ["id" => $id]);
        $courseUserData = Core::$systemDB->select("course_user", ["id" => $id]);
        $userData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);

        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $expectedCourseUserData =  array("id" => $id,"course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $this->assertEmpty($roleData);
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
        $this->assertEquals($expectedCourseUserData, $courseUserData);
    }

    public function testAddCourseUserToDBNullRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $this->course->method("getId")->willReturn($courseId);
        
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $user = new CourseUser($id, $this->course);
        
        //When
        $user->addCourseUserToDB(null);
            

        //Then
        $roleData = Core::$systemDB->select("user_role", ["id" => $id]);
        $courseUserData = Core::$systemDB->select("course_user", ["id" => $id]);
        $userData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);

        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $expectedCourseUserData =  array("id" => $id,"course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $this->assertEmpty($roleData);
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
        $this->assertEquals($expectedCourseUserData, $courseUserData);
    }

    public function testAddCourseUserToDBWithRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $this->course->method("getId")->willReturn($courseId);
        
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $roleId = Core::$systemDB->insert("role", [
            "name" => "Professor",
            "course" => $courseId
        ]);

        $user = new CourseUser($id, $this->course);
        
        //When
        $user->addCourseUserToDB($roleId);
            

        //Then
        $roleData = Core::$systemDB->select("user_role", ["id" => $id]);
        $courseUserData = Core::$systemDB->select("course_user", ["id" => $id]);
        $userData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);

        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $expectedCourseUserData =  array("id" => $id,"course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedRoleData =  array("id" => $id, "course" => $courseId, "role" => $roleId);
        
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
        $this->assertEquals($expectedCourseUserData, $courseUserData);
        $this->assertEquals($expectedRoleData, $roleData);
    }

    public function testAddCourseUserToDBInexistingCourseFail(){

        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);
        $authData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $userData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $user = new CourseUser($id, $this->course);

        try {

            $user->addCourseUserToDB();
            $this->fail("PDOException should have been thrown for inexisting course on addCourseUserToDB.");

        } catch (\PDOException $e) {
            $courseUser = Core::$systemDB->select("course_user", ["id" => $id]);
            $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
            $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
            $this->assertEmpty($courseUser);
            $this->assertEquals($newUserData, $userData);
            $this->assertEquals($newAuthData, $authData);
        }
    }

    public function testAddCourseUserToDBInexistingUserFail(){

        Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $id = 20;
        $user = new CourseUser($id, $this->course);

        try {

            $user->addCourseUserToDB();
            $this->fail("PDOException should have been thrown for inexisting game course user on addCourseUserToDB.");

        } catch (\PDOException $e) {
            $courseUser = Core::$systemDB->select("course_user", ["id" => $id]);
            $this->assertEmpty($courseUser);
        }
    }

    public function testAddCourseUserToDBInexistingRoleFail(){

        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $this->course->method("getId")->willReturn($courseId);
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $user = new CourseUser($id, $this->course);
        
        try {

            $user->addCourseUserToDB(1);
            $this->fail("PDOException should have been thrown for inexisting role on addCourseUserToDB.");

        } catch (\PDOException $e) {
            $courseUser = Core::$systemDB->select("course_user", ["id" => $id]);
            $role = Core::$systemDB->select("user_role", ["id" => $id]);
            $expectedData =  array("id" => $id, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
            $this->assertEmpty($role);
            $this->assertEquals($expectedData, $courseUser);
        }
    }

    public function testAddCourseUserNoRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $user = new CourseUser($id, $this->course);
        
        //When
        $user->addCourseUser($courseId, $id);
            

        //Then
        $roleData = Core::$systemDB->select("user_role", ["id" => $id]);
        $courseUserData = Core::$systemDB->select("course_user", ["id" => $id]);
        $userData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);

        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $expectedCourseUserData =  array("id" => $id,"course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $this->assertEmpty($roleData);
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
        $this->assertEquals($expectedCourseUserData, $courseUserData);
    }

    public function testAddCourseUserNullRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $user = new CourseUser($id, $this->course);
        
        //When
        $user->addCourseUser($courseId, $id, null);
            

        //Then
        $roleData = Core::$systemDB->select("user_role", ["id" => $id]);
        $courseUserData = Core::$systemDB->select("course_user", ["id" => $id]);
        $userData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);

        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $expectedCourseUserData =  array("id" => $id,"course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $this->assertEmpty($roleData);
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
        $this->assertEquals($expectedCourseUserData, $courseUserData);
    }

    public function testAddCourseUserWithRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $roleId = Core::$systemDB->insert("role", [
            "name" => "Professor",
            "course" => $courseId
        ]);

        $user = new CourseUser($id, $this->course);
        
        //When
        $user->addCourseUser($courseId, $id, $roleId);
            

        //Then
        $roleData = Core::$systemDB->select("user_role", ["id" => $id]);
        $courseUserData = Core::$systemDB->select("course_user", ["id" => $id]);
        $userData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);

        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $expectedCourseUserData =  array("id" => $id,"course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedRoleData =  array("id" => $id, "course" => $courseId, "role" => $roleId);
        
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
        $this->assertEquals($expectedCourseUserData, $courseUserData);
        $this->assertEquals($expectedRoleData, $roleData);
    }

    public function testAddCourseUserInexistingCourseFail(){

        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);
        $authData = array("id" => $authId, "game_course_user_id" => $id, "username" =>  "ist197046", "authentication_service" => "fenix");
        $userData = array("id" => $id, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $user = new CourseUser($id, $this->course);
        $courseId = 2;

        try {

            $user->addCourseUser($courseId, $id);
            $this->fail("PDOException should have been thrown for inexisting course on addCourseUser.");

        } catch (\PDOException $e) {
            $courseUser = Core::$systemDB->select("course_user", ["id" => $id]);
            $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
            $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
            $this->assertEmpty($courseUser);
            $this->assertEquals($newUserData, $userData);
            $this->assertEquals($newAuthData, $authData);
        }
    }

    public function testAddCourseUserInexistingUserFail(){

        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $id = 20;
        $user = new CourseUser($id, $this->course);

        try {

            $user->addCourseUser($courseId, $id);
            $this->fail("PDOException should have been thrown for inexisting game course user on addCourseUserToDB.");

        } catch (\PDOException $e) {
            $courseUser = Core::$systemDB->select("course_user", ["id" => $id]);
            $this->assertEmpty($courseUser);
        }
    }
    
    public function testAddCourseUserInexistingRoleFail(){

        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $user = new CourseUser($id, $this->course);
        
        try {

            $user->addCourseUser($courseId, $id, 1);
            $this->fail("PDOException should have been thrown for inexisting role on addCourseUserToDB.");

        } catch (\PDOException $e) {
            $courseUser = Core::$systemDB->select("course_user", ["id" => $id]);
            $role = Core::$systemDB->select("user_role", ["id" => $id]);
            $expectedData =  array("id" => $id, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
            $this->assertEmpty($role);
            $this->assertEquals($expectedData, $courseUser);
        }
    }

    public function testAddRoleInexistingRoleFail(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $this->course->method("getRolesData")->willReturn(array());
        $this->course->method("getId")->willReturn($courseId);
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);

        $user = new CourseUser($id, $this->course);

        //When
        $result = $user->addRole("potatoes");

        //Then
        $this->assertFalse($result);        
    }

    /**
     * @depends testAddCourseUserNoRoleSuccess
     */
    public function testAddRoleUniqueExistingRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);
        $roleId = Core::$systemDB->insert("role", [
            "name" => "Student",
            "course" => $courseId
        ]);
        $this->course->method("getRolesData")->willReturn(array(array("name" => "Student", "id" => $roleId)));
        $this->course->method("getId")->willReturn($courseId);
        $user = new CourseUser($id, $this->course);
        $user->addCourseUserToDB();

        //When
        $result = $user->addRole("Student");

        //Then
        $this->assertTrue($result);
        $role = Core::$systemDB->select("user_role", ["id" => $id]);
        $expectedRole = array("id" =>$id, "role" => $roleId, "course" => $courseId);
        $this->assertEquals($expectedRole, $role);
    
    }

    /**
     * @depends testAddRoleUniqueExistingRoleSuccess
     */
    public function testAddRoleDuplicateExistingRoleSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);
        $roleId = Core::$systemDB->insert("role", [
            "name" => "Student",
            "course" => $courseId
        ]);
        $this->course->method("getRolesData")->willReturn(array(array("name" => "Student", "id" => $roleId)));
        $this->course->method("getId")->willReturn($courseId);
        $user = new CourseUser($id, $this->course);
        $user->addCourseUserToDB();
        $result1 = $user->addRole("Student");

        //When
        $result2 = $user->addRole("Student");

        //Then
        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $role = Core::$systemDB->select("user_role", ["id" => $id]);
        $expectedRole = array("id" => $id, "role" => $roleId, "course" => $courseId);
        $this->assertEquals($expectedRole, $role);
      
    }

        /**
     * @depends testAddRoleUniqueExistingRoleSuccess
     */
    public function testAddRoleTwoRolesNoReplaceSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $id = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => "ist197046",
            "authentication_service" => "fenix"
        ]);
        $coordenatorRoleId = Core::$systemDB->insert("role", [
            "name" => "Coordenator",
            "course" => $courseId
        ]);
        $teacherRoleId = Core::$systemDB->insert("role", [
            "name" => "Teacher",
            "course" => $courseId
        ]);
        $this->course->method("getRolesData")->willReturn(array(array("name" => "Coordenator", "id" => $coordenatorRoleId), array("name" => "Teacher", "id" => $teacherRoleId)));
        $this->course->method("getId")->willReturn($courseId);
        $user = new CourseUser($id, $this->course);
        $user->addCourseUserToDB();

        //When
        $result1 = $user->addRole("Coordenator");
        $result2 = $user->addRole("Teacher");

        //Then
        $roles = Core::$systemDB->selectMultiple("user_role", ["id" => $id]);
        $expectedRole1 = array("id" => $id, "role" => $coordenatorRoleId, "course" => $courseId);
        $expectedRole2 = array("id" => $id, "role" => $teacherRoleId, "course" => $courseId);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertCount(2, $roles);
        $this->assertEquals($expectedRole1, $roles[0]);
        $this->assertEquals($expectedRole2, $roles[1]);
    }

    /**
     * @depends testAddCourseUserWithRoleSuccess
     */
    public function testExportCourseUsersSuccess(){

        //Given
        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "studentNumber" => "87664", "nickname" => null, "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "studentNumber" => "84715", "nickname" => null, "major" =>  "LEIC-T", "isAdmin" => 0, "isActive" => 1]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "studentNumber" => "86893", "nickname" => "Mariana Brandão", "major" =>  "MEMec", "isAdmin" => 0, "isActive" => 0]);
        
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user1, "username" => "ist1100956", "authentication_service" => "fenix"]);
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user2, "username" => "ist187664", "authentication_service" => "linkedin"]);
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user3, "username" => "ist426015", "authentication_service" => "fenix"]);
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user4, "username" => "ist186893", "authentication_service" => "facebook"]);

        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $this->course->method("getId")->willReturn($courseId);
        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $coordenatorId = Core::$systemDB->insert("role", ["name" => "Coordenator", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        $this->course->method("getRolesData")->willReturn(array(array("name" => "Professor", "id" => $professorId), array("name" => "Coordenator", "id" => $coordenatorId), array("name" => "Student", "id" => $studentId)));
        
        $user = new CourseUser($user1, $this->course);
        $user->addCourseUserToDB($professorId);
        $user->addRole("Coordenator");
        CourseUser::addCourseUser($courseId, $user2, $professorId);
        CourseUser::addCourseUser($courseId, $user3, $studentId);
        CourseUser::addCourseUser($courseId, $user4, $studentId);

        $expectedFile = "name,email,nickname,studentNumber,isAdmin,isActive,major,roles,username,auth\n";
        $expectedFile .= "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $expectedFile .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $expectedFile .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $expectedFile .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        //When
        $result = CourseUser::exportCourseUsers($courseId);

        //Then
        $this->assertEquals("Users - Produção de Conteudo Multimédia 2019-2020", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    /**
     * @depends testAddCourseUserWithRoleSuccess
     */
    public function testExportCourseUsersNullCourseFail(){

        //Given
        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "studentNumber" => "87664", "nickname" => null, "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "studentNumber" => "84715", "nickname" => null, "major" =>  "LEIC-T", "isAdmin" => 0, "isActive" => 1]);
        $user4 = Core::$systemDB->insert("game_course_user", ["name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "studentNumber" => "86893", "nickname" => "Mariana Brandão", "major" =>  "MEMec", "isAdmin" => 0, "isActive" => 0]);
        
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user1, "username" => "ist1100956", "authentication_service" => "fenix"]);
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user2, "username" => "ist187664", "authentication_service" => "linkedin"]);
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user3, "username" => "ist426015", "authentication_service" => "fenix"]);
        Core::$systemDB->insert( "auth", ["game_course_user_id" => $user4, "username" => "ist186893", "authentication_service" => "facebook"]);

        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        
        CourseUser::addCourseUser($courseId, $user1, $professorId);
        CourseUser::addCourseUser($courseId, $user2, $studentId);
        CourseUser::addCourseUser($courseId, $user3, $studentId);
        CourseUser::addCourseUser($courseId, $user4, $studentId);

        $expectedFile = "name,email,nickname,studentNumber,isAdmin,isActive,major,roles,username,auth\n";
        
        //When
        $result = CourseUser::exportCourseUsers(null);

        //Then
        $this->assertEquals("Users -  ", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testImportCourseUsersWithHeaderNoReplaceInexistingRolesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $file = "name,email,nickname,studentNumber,isAdmin,isActive,major,roles,username,auth\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, false);

        //Then
        $users = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);

        $this->assertCount(4, $users);
        $this->assertEquals(4, $newCourseUsers);

        $expectedCourseUser1 = array("id" => $users[0]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser2 = array("id" => $users[1]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser3 = array("id" => $users[2]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser4 = array("id" => $users[3]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $users[0]["id"]]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $users[1]["id"]]);
        $courseUser3 = Core::$systemDB->select("course_user", ["id" => $users[2]["id"]]);
        $courseUser4 = Core::$systemDB->select("course_user", ["id" => $users[3]["id"]]);

        $this->assertEquals($expectedCourseUser1, $courseUser1);
        $this->assertEquals($expectedCourseUser2, $courseUser2);
        $this->assertEquals($expectedCourseUser3, $courseUser3);
        $this->assertEquals($expectedCourseUser4, $courseUser4);
        
        $roles = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);

        $this->assertEmpty($roles);
    }

    public function testImportCourseUsersWithHeaderNoReplaceUniqueUsersSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $file = "name,email,nickname,studentNumber,isAdmin,isActive,major,roles,username,auth\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $coordenatorId = Core::$systemDB->insert("role", ["name" => "Coordenator", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, false);

        //Then
        $users = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);

        $this->assertCount(4, $users);
        $this->assertEquals(4, $newCourseUsers);

        $expectedCourseUser1 = array("id" => $users[0]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser2 = array("id" => $users[1]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser3 = array("id" => $users[2]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser4 = array("id" => $users[3]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $users[0]["id"]]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $users[1]["id"]]);
        $courseUser3 = Core::$systemDB->select("course_user", ["id" => $users[2]["id"]]);
        $courseUser4 = Core::$systemDB->select("course_user", ["id" => $users[3]["id"]]);

        $this->assertEquals($expectedCourseUser1, $courseUser1);
        $this->assertEquals($expectedCourseUser2, $courseUser2);
        $this->assertEquals($expectedCourseUser3, $courseUser3);
        $this->assertEquals($expectedCourseUser4, $courseUser4);

        $expectedProfessorRole1 = array("id" => $users[0]["id"], "course" => $courseId, "role" => $professorId);
        $expectedCoordenatorRole1 = array("id" => $users[0]["id"], "course" => $courseId, "role" => $coordenatorId);
        $expectedRole2 = array("id" => $users[1]["id"], "course" => $courseId, "role" => $professorId);
        $expectedRole3 = array("id" => $users[2]["id"], "course" => $courseId, "role" => $studentId);
        $expectedRole4 = array("id" => $users[3]["id"], "course" => $courseId, "role" => $studentId);

        $roles = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $role1 = Core::$systemDB->selectMultiple("user_role", ["id" => $users[0]["id"]]);
        $role2 = Core::$systemDB->select("user_role", ["id" => $users[1]["id"]]);
        $role3 = Core::$systemDB->select("user_role", ["id" => $users[2]["id"]]);
        $role4 = Core::$systemDB->select("user_role", ["id" => $users[3]["id"]]);

        $this->assertCount(5, $roles);
        $this->assertCount(2, $role1);
        $this->assertTrue(in_array($expectedProfessorRole1, $role1));
        $this->assertTrue(in_array($expectedCoordenatorRole1, $role1));
        $this->assertEquals($expectedRole2, $role2);
        $this->assertEquals($expectedRole3, $role3);
        $this->assertEquals($expectedRole4, $role4);
    }

    public function testImportCourseUsersNoHeaderNoReplaceUniqueUsersSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $coordenatorId = Core::$systemDB->insert("role", ["name" => "Coordenator", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, false);

        //Then
        $users = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);

        $this->assertCount(4, $users);
        $this->assertEquals(4, $newCourseUsers);

        $expectedCourseUser1 = array("id" => $users[0]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser2 = array("id" => $users[1]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser3 = array("id" => $users[2]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser4 = array("id" => $users[3]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $users[0]["id"]]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $users[1]["id"]]);
        $courseUser3 = Core::$systemDB->select("course_user", ["id" => $users[2]["id"]]);
        $courseUser4 = Core::$systemDB->select("course_user", ["id" => $users[3]["id"]]);

        $this->assertEquals($expectedCourseUser1, $courseUser1);
        $this->assertEquals($expectedCourseUser2, $courseUser2);
        $this->assertEquals($expectedCourseUser3, $courseUser3);
        $this->assertEquals($expectedCourseUser4, $courseUser4);

        $expectedProfessorRole1 = array("id" => $users[0]["id"], "course" => $courseId, "role" => $professorId);
        $expectedCoordenatorRole1 = array("id" => $users[0]["id"], "course" => $courseId, "role" => $coordenatorId);
        $expectedRole2 = array("id" => $users[1]["id"], "course" => $courseId, "role" => $professorId);
        $expectedRole3 = array("id" => $users[2]["id"], "course" => $courseId, "role" => $studentId);
        $expectedRole4 = array("id" => $users[3]["id"], "course" => $courseId, "role" => $studentId);

        $roles = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $role1 = Core::$systemDB->selectMultiple("user_role", ["id" => $users[0]["id"]]);
        $role2 = Core::$systemDB->select("user_role", ["id" => $users[1]["id"]]);
        $role3 = Core::$systemDB->select("user_role", ["id" => $users[2]["id"]]);
        $role4 = Core::$systemDB->select("user_role", ["id" => $users[3]["id"]]);

        $this->assertCount(5, $roles);
        $this->assertCount(2, $role1);
        $this->assertTrue(in_array($expectedProfessorRole1, $role1));
        $this->assertTrue(in_array($expectedCoordenatorRole1, $role1));
        $this->assertEquals($expectedRole2, $role2);
        $this->assertEquals($expectedRole3, $role3);
        $this->assertEquals($expectedRole4, $role4);
    }

    public function testImportCourseUsersNoHeaderNoReplaceDuplicateUsersSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $coordenatorId = Core::$systemDB->insert("role", ["name" => "Coordenator", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, false);

        //Then
        $users = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);

        $this->assertCount(4, $users);
        $this->assertEquals(4, $newCourseUsers);

        $expectedCourseUser1 = array("id" => $users[0]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser2 = array("id" => $users[1]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser3 = array("id" => $users[2]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser4 = array("id" => $users[3]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $users[0]["id"]]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $users[1]["id"]]);
        $courseUser3 = Core::$systemDB->select("course_user", ["id" => $users[2]["id"]]);
        $courseUser4 = Core::$systemDB->select("course_user", ["id" => $users[3]["id"]]);

        $this->assertEquals($expectedCourseUser1, $courseUser1);
        $this->assertEquals($expectedCourseUser2, $courseUser2);
        $this->assertEquals($expectedCourseUser3, $courseUser3);
        $this->assertEquals($expectedCourseUser4, $courseUser4);

        $expectedProfessorRole1 = array("id" => $users[0]["id"], "course" => $courseId, "role" => $professorId);
        $expectedCoordenatorRole1 = array("id" => $users[0]["id"], "course" => $courseId, "role" => $coordenatorId);
        $expectedRole2 = array("id" => $users[1]["id"], "course" => $courseId, "role" => $professorId);
        $expectedRole3 = array("id" => $users[2]["id"], "course" => $courseId, "role" => $studentId);
        $expectedRole4 = array("id" => $users[3]["id"], "course" => $courseId, "role" => $studentId);

        $roles = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $role1 = Core::$systemDB->selectMultiple("user_role", ["id" => $users[0]["id"]]);
        $role2 = Core::$systemDB->select("user_role", ["id" => $users[1]["id"]]);
        $role3 = Core::$systemDB->select("user_role", ["id" => $users[2]["id"]]);
        $role4 = Core::$systemDB->select("user_role", ["id" => $users[3]["id"]]);

        $this->assertCount(5, $roles);
        $this->assertCount(2, $role1);
        $this->assertTrue(in_array($expectedProfessorRole1, $role1));
        $this->assertTrue(in_array($expectedCoordenatorRole1, $role1));
        $this->assertEquals($expectedRole2, $role2);
        $this->assertEquals($expectedRole3, $role3);
        $this->assertEquals($expectedRole4, $role4);
    }

    public function testImportCourseUsersNoHeaderNoReplaceRepeatUsersSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $user0 = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $user0,
            "username" => "ist1100956",
            "authentication_service" => "fenix"
        ]);

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $coordenatorId = Core::$systemDB->insert("role", ["name" => "Coordenator", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, false);

        //Then
        $users = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);

        $this->assertCount(4, $users);
        $this->assertEquals(4, $newCourseUsers);

        $expectedCourseUser1 = array("id" => $user0, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser2 = array("id" => $users[1]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser3 = array("id" => $users[2]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser4 = array("id" => $users[3]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $user0]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $users[1]["id"]]);
        $courseUser3 = Core::$systemDB->select("course_user", ["id" => $users[2]["id"]]);
        $courseUser4 = Core::$systemDB->select("course_user", ["id" => $users[3]["id"]]);

        $this->assertEquals($expectedCourseUser1, $courseUser1);
        $this->assertEquals($expectedCourseUser2, $courseUser2);
        $this->assertEquals($expectedCourseUser3, $courseUser3);
        $this->assertEquals($expectedCourseUser4, $courseUser4);

        $expectedProfessorRole1 = array("id" => $user0, "course" => $courseId, "role" => $professorId);
        $expectedCoordenatorRole1 = array("id" => $user0, "course" => $courseId, "role" => $coordenatorId);
        $expectedRole2 = array("id" => $users[1]["id"], "course" => $courseId, "role" => $professorId);
        $expectedRole3 = array("id" => $users[2]["id"], "course" => $courseId, "role" => $studentId);
        $expectedRole4 = array("id" => $users[3]["id"], "course" => $courseId, "role" => $studentId);

        $roles = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $role1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user0]);
        $role2 = Core::$systemDB->select("user_role", ["id" => $users[1]["id"]]);
        $role3 = Core::$systemDB->select("user_role", ["id" => $users[2]["id"]]);
        $role4 = Core::$systemDB->select("user_role", ["id" => $users[3]["id"]]);

        $this->assertCount(5, $roles);
        $this->assertCount(2, $role1);
        $this->assertTrue(in_array($expectedProfessorRole1, $role1));
        $this->assertTrue(in_array($expectedCoordenatorRole1, $role1));
        $this->assertEquals($expectedRole2, $role2);
        $this->assertEquals($expectedRole3, $role3);
        $this->assertEquals($expectedRole4, $role4);

        $userData = Core::$systemDB->select("game_course_user", ["id" => $user0]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $user0]);
        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $user0, "username" =>  "ist1100956", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $user0, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
    }

    public function testImportCourseUsersNoHeaderReplaceRepeatUsersSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $user0 = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $user0,
            "username" => "ist1100956",
            "authentication_service" => "fenix"
        ]);

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $coordenatorId = Core::$systemDB->insert("role", ["name" => "Coordenator", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, true);

        //Then
        $users = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);

        $this->assertCount(4, $users);
        $this->assertEquals(4, $newCourseUsers);

        $expectedCourseUser1 = array("id" => $user0, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser2 = array("id" => $users[1]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser3 = array("id" => $users[2]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser4 = array("id" => $users[3]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $user0]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $users[1]["id"]]);
        $courseUser3 = Core::$systemDB->select("course_user", ["id" => $users[2]["id"]]);
        $courseUser4 = Core::$systemDB->select("course_user", ["id" => $users[3]["id"]]);

        $this->assertEquals($expectedCourseUser1, $courseUser1);
        $this->assertEquals($expectedCourseUser2, $courseUser2);
        $this->assertEquals($expectedCourseUser3, $courseUser3);
        $this->assertEquals($expectedCourseUser4, $courseUser4);

        $expectedProfessorRole1 = array("id" => $user0, "course" => $courseId, "role" => $professorId);
        $expectedCoordenatorRole1 = array("id" => $user0, "course" => $courseId, "role" => $coordenatorId);
        $expectedRole2 = array("id" => $users[1]["id"], "course" => $courseId, "role" => $professorId);
        $expectedRole3 = array("id" => $users[2]["id"], "course" => $courseId, "role" => $studentId);
        $expectedRole4 = array("id" => $users[3]["id"], "course" => $courseId, "role" => $studentId);

        $roles = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $role1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user0]);
        $role2 = Core::$systemDB->select("user_role", ["id" => $users[1]["id"]]);
        $role3 = Core::$systemDB->select("user_role", ["id" => $users[2]["id"]]);
        $role4 = Core::$systemDB->select("user_role", ["id" => $users[3]["id"]]);

        $this->assertCount(5, $roles);
        $this->assertCount(2, $role1);
        $this->assertTrue(in_array($expectedProfessorRole1, $role1));
        $this->assertTrue(in_array($expectedCoordenatorRole1, $role1));
        $this->assertEquals($expectedRole2, $role2);
        $this->assertEquals($expectedRole3, $role3);
        $this->assertEquals($expectedRole4, $role4);

        $userData = Core::$systemDB->select("game_course_user", ["id" => $user0]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $user0]);
        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $user0, "username" =>  "ist1100956", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $user0, "name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
    }

    /**
     * @depends testAddCourseUserWithRoleSuccess
     */
    public function testImportCourseUsersNoHeaderReplaceRepeatCourseUsersSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $user0 = Core::$systemDB->insert("game_course_user", [
            "name" => "Simão Patrício",
            "email" => "simpat98@gmail.com",
            "studentNumber" => "97046",
            "nickname" => "",
            "major" => "MEIC-A",
            "isAdmin" => 0,
            "isActive" => 0
        ]);
        $authId = Core::$systemDB->insert("auth", [
            "game_course_user_id" => $user0,
            "username" => "ist1100956",
            "authentication_service" => "fenix"
        ]);

        $professorId = Core::$systemDB->insert("role", ["name" => "Professor", "course" => $courseId]);
        $coordenatorId = Core::$systemDB->insert("role", ["name" => "Coordenator", "course" => $courseId]);
        $studentId = Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        CourseUser::addCourseUser($courseId, $user0, $professorId);

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,1,1,MEIC-T,Professor-Coordenator,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,0,1,MEIC-A,Professor,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,0,1,LEIC-T,Student,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,0,0,MEMec,Student,ist186893,facebook";

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, true);

        //Then
        $users = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);

        $this->assertCount(4, $users);
        $this->assertEquals(3, $newCourseUsers);

        $expectedCourseUser1 = array("id" => $user0, "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser2 = array("id" => $users[1]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser3 = array("id" => $users[2]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        $expectedCourseUser4 = array("id" => $users[3]["id"], "course" => $courseId, "lastActivity" => null, "previousActivity" => null, "isActive" => 1);
        
        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $user0]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $users[1]["id"]]);
        $courseUser3 = Core::$systemDB->select("course_user", ["id" => $users[2]["id"]]);
        $courseUser4 = Core::$systemDB->select("course_user", ["id" => $users[3]["id"]]);

        $this->assertEquals($expectedCourseUser1, $courseUser1);
        $this->assertEquals($expectedCourseUser2, $courseUser2);
        $this->assertEquals($expectedCourseUser3, $courseUser3);
        $this->assertEquals($expectedCourseUser4, $courseUser4);

        $expectedRole1 = array("id" => $user0, "course" => $courseId, "role" => $professorId);
        $expectedRole2 = array("id" => $users[1]["id"], "course" => $courseId, "role" => $professorId);
        $expectedRole3 = array("id" => $users[2]["id"], "course" => $courseId, "role" => $studentId);
        $expectedRole4 = array("id" => $users[3]["id"], "course" => $courseId, "role" => $studentId);

        $roles = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $role1 = Core::$systemDB->select("user_role", ["id" => $user0]);
        $role2 = Core::$systemDB->select("user_role", ["id" => $users[1]["id"]]);
        $role3 = Core::$systemDB->select("user_role", ["id" => $users[2]["id"]]);
        $role4 = Core::$systemDB->select("user_role", ["id" => $users[3]["id"]]);

        $this->assertCount(4, $roles);
        $this->assertEquals($expectedRole1, $role1);
        $this->assertEquals($expectedRole2, $role2);
        $this->assertEquals($expectedRole3, $role3);
        $this->assertEquals($expectedRole4, $role4);

        $userData = Core::$systemDB->select("game_course_user", ["id" => $user0]);
        $authData = Core::$systemDB->select("auth", ["game_course_user_id" => $user0]);
        $expectedAuthData = array("id" => $authId, "game_course_user_id" => $user0, "username" => "ist1100956", "authentication_service" => "fenix");
        $expectedUserData = array("id" => $user0, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1);
        
        $this->assertEquals($expectedAuthData, $authData);
        $this->assertEquals($expectedUserData, $userData);
    }

    public function testImportCourseUsersNoReplaceEmptyFile(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $file = "";

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId, false);

        //Then
        $courseUsers = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);
        $users = Core::$systemDB->selectMultiple("game_course_user", []);

        $this->assertEquals(0, $newCourseUsers);
        $this->assertEmpty($users);
        $this->assertEmpty($courseUsers);
    } 
    
    public function testImportCourseUsersReplaceEmptyFile(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteudo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $file = "";

        //When
        $newCourseUsers = CourseUser::importCourseUsers($file, $courseId);

        //Then
        $courseUsers = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);
        $users = Core::$systemDB->selectMultiple("game_course_user", []);

        $this->assertEquals(0, $newCourseUsers);
        $this->assertEmpty($users);
        $this->assertEmpty($courseUsers);
    } 

    
}