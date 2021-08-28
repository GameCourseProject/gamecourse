<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\User;
use GameCourse\Course;

use PHPUnit\Framework\TestCase;


class UserClassTest extends TestCase
{
    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function tearDown():void {
        //Core::$systemDB->delete("game_course_user", [], null, [["id", 1]]);
        Core::$systemDB->delete("game_course_user", [], null, [["id", 0]]);
    }
    
    //Data Providers
    public function addUserToDBSuccessProvider(){
        return array(
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1),   //normal case
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 0),   //not admin, not active
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 1, 0),   //admin, not active
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 1, 1),   //admin, active
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", null, "MEIC-A", 0, 1)            //null nickname
        );
    }

    public function addUserToDBFailProvider(){
        return array(
            array(null, "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1),                      //null name
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", null, 1),    //null admin
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, null),    //null active
            array("João Carlos Sousa", "ist123456", "twitter", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, null)   //invalid authentication service
        );
    }

    public function setDataSuccessProvider(){
        return array(
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Ana G", "MEIC-A", 0, 0),              //same data
            array("Rita Alves", "ana.goncalves@hotmail.com", "10000", "Ana G", "MEIC-A", 0, 0),                 //different name
            array("Ana Gonçalves", "ana.r.g@gmail.com", "10000", "Ana G", "MEIC-A", 0, 0),                  //different email
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "81829", "Ana G", "MEIC-A", 0, 0),              //different student number
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Rita Gonçalves", "MEIC-A", 0, 0),     //different nickname
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Rita Gonçalves", "MEIC-A", 0, 0),     //null nickname
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Ana G", "MEEC", 0, 0),                //different major
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Ana G", "MEIC-A", 1, 0),              //different isAdmin
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Ana G", "MEIC-A", 0, 1),              //different isActive
            array("Rita Alves", "ana.r.g@gmail.com", "81829", "Rita A", "MEEC", 1, 1)                       //all different
        );
    }

    public function setDataFailProvider(){
        return array(
            array(null, "ana.goncalves@hotmail.com", "10000", "Ana G", "MEIC-A", 0, 0),                      //null name
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Ana G", "MEIC-A", null, 0),        //null admin
            array("Ana Gonçalves", "ana.goncalves@hotmail.com", "10000", "Ana G", "MEIC-A", 0, null)         //null active
        );
    }

    public function editUserSuccessProvider(){
        return array(
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1),   //same data
            array("John Doe", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1),            //different name
            array("João Carlos Sousa", "ist122222", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1),   //different username
            array("João Carlos Sousa", "ist123456", "google", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1),  //different authentication service
            array("João Carlos Sousa", "ist123456", "fenix", "jcs@hotmail.com", "123456", "João Sousa", "MEIC-A", 0, 1),  //different email
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "12222", "João Sousa", "MEIC-A", 0, 1),    //different student number
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", null, "MEIC-A", 0, 1),           //null nickname
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "JCS", "MEIC-A", 0, 1),          //different nickname
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEMec", 0, 1),    //different major
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 1, 1),   //different isAdmin
            array("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 0),   //different isActive
            array("John Doe", "ist122222", "google", "jd@gmail.com", "12222", "JD", "MEIC-T", 1, 0)                       //all different
        );
    }

    /**
     * @dataProvider addUserToDBSuccessProvider
     */
    public function testAddUserToDBSuccess($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive){

        $id = User::addUserToDB($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
        
        $user = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $userData = array("id" => $id, "name" => $name, "email" => $email, "studentNumber" => $studentNumber, "nickname" => $nickname, "major" =>  $major, "isAdmin" => $isAdmin, "isActive" => $isActive);
        $authData = array("id" => $auth["id"], "game_course_user_id" => $id, "username" => $username, "authentication_service" => $authenticationService);
        $this->assertEquals($user, $userData);
        $this->assertEquals($auth, $authData);
    }

    /**
     * @dataProvider addUserToDBFailProvider
     */
    public function testAddUserToDBInvalidArgumentsFail($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive){
        try {

            User::addUserToDB($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
            $this->fail("PDOException should have been thrown for invalid argument on addUserToDB.");

        } catch (\PDOException $e) {
            $user = Core::$systemDB->select("game_course_user", ["studentNumber" => $studentNumber]);
            $this->assertEmpty($user);
        }
    }

    public function testAddUserToDBSameStudentNumberFail(){

        User::addUserToDB("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1);
        
        $this->expectException(PDOException::class);
        User::addUserToDB("Marcus Notø", "ist1101036", "fenix", "marcus.n.hansen@gmail", "123456", "Marcus Notø", "MEEC", 0, 1);
    }

    
    /**
     * @depends testAddUserToDBSuccess
     */
    public function testUserConstructor(){

        $id = User::addUserToDB("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1);
        $user = new User($id);

        $this->assertTrue($user->exists());
        $this->assertEquals($user->getId(), $id);
    }

    /**
     * @depends testAddUserToDBSuccess
     * @dataProvider addUserToDBSuccessProvider
     */
    public function testGetDataSuccess($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive){

        $id = User::addUserToDB($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
        $user = new User($id);

        $data = $user->getData();

        $expectedData = array("id" => $id, "name" => $name, "email" => $email, "studentNumber" => $studentNumber, "nickname" => $nickname, "major" =>  $major, "isAdmin" => $isAdmin, "isActive" => $isActive);
        $this->assertEquals($data, $expectedData);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetDataExistingFieldSuccess(){

        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 0, 0);
        $user = new User($id);

        $name = $user->getData("name");

        $this->assertEquals($name, "Sabri M'Barki");

    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetDataInexistingFieldFail(){

        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 0, 0);
        $user = new User($id);

        $this->expectException(PDOException::class);
        $data = $user->getData("potatoes");
    }

    /**
     * @depends testAddUserToDBSuccess
     * @dataProvider setDataSuccessProvider
     */
    public function testSetDataSuccess($name, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive){

        $id = User::addUserToDB("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com", "10000", "Ana G", "MEIC-A", 0, 0);
        $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $authData = array("id" => $auth["id"], "game_course_user_id" => $id, "username" =>  "ist100000", "authentication_service" => "fenix");
        $userData = array("id" => $id, "name" => $name, "email" => $email, "studentNumber" => $studentNumber, "nickname" => $nickname, "major" =>  $major, "isAdmin" => $isAdmin, "isActive" => $isActive);
        $user = new User($id);

        $user->setData($userData);

        $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $this->assertEquals($newUserData, $userData);
        $this->assertEquals($newAuthData, $authData);
        
    }

    /**
     * @depends testAddUserToDBSuccess
     * @dataProvider setDataFailProvider
     */
    public function testSetDataFail($name, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive){
        
        $id = User::addUserToDB("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com", "10000", "Ana G", "MEIC-A", 0, 0);
        $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $authData = array("id" => $auth["id"], "game_course_user_id" => $id, "username" =>  "ist100000", "authentication_service" => "fenix");
        $userData = array("id" => $id, "name" => "Ana Gonçalves", "email" => "ana.goncalves@gmail.com", "studentNumber" => "10000", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $user = new User($id);

        try {

            $user->setData(array("name" => $name, "email" => $email, "studentNumber" => $studentNumber, "nickname" => $nickname, "major" =>  $major, "isAdmin" => $isAdmin, "isActive" => $isActive));
            $this->fail("PDOException should have been thrown for invalid argument on setData.");

        } catch (\PDOException $e) {
            $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
            $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
            $this->assertEquals($newUserData, $userData);
            $this->assertEquals($newAuthData, $authData);
        }
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testSetDataDuplicateStudentNumberFail(){

        User::addUserToDB("Ana Nogueira", "ist182433", "fenix", "ana.b.nogueira@tecnico.ulisboa.pt", "82433", "Ana N", "MEIC-A", 0, 0);
        $id = User::addUserToDB("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com", "10000", "Ana G", "MEIC-A", 0, 0);
        $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $authData = array("id" => $auth["id"], "game_course_user_id" => $id, "username" =>  "ist100000", "authentication_service" => "fenix");
        $userData = array("id" => $id, "name" => "Ana Gonçalves", "email" => "ana.goncalves@gmail.com", "studentNumber" => "10000", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $user = new User($id);
        
        try {

            $user->setData(array("name" => "Ana Gonçalves", "email" => "ana.goncalves@gmail.com", "studentNumber" => "82433", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 0));
            $this->fail("PDOException should have been thrown for duplicate studentNumber on setData.");

        } catch (\PDOException $e) {
            $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
            $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
            $this->assertEquals($newUserData, $userData);
            $this->assertEquals($newAuthData, $authData);
        }
    }

    /**
     * @depends testAddUserToDBSuccess
     * @dataProvider addUserToDBSuccessProvider
     */
    public function testEditUserSuccess($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive){

        $id = User::addUserToDB("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1);
        $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $authData = array("id" => $auth["id"], "game_course_user_id" => $id, "username" =>  $username, "authentication_service" => $authenticationService);
        $userData = array("id" => $id, "name" => $name, "email" => $email, "studentNumber" => $studentNumber, "nickname" => $nickname, "major" =>  $major, "isAdmin" => $isAdmin, "isActive" => $isActive);
        $user = new User($id);

        $user->editUser($name, $username, $authenticationService,  $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);

        $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
        $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $this->assertEquals($newUserData, $userData);
        $this->assertEquals($newAuthData, $authData);
        
    }

    /**
     * @depends testAddUserToDBSuccess
     * @dataProvider addUserToDBFailProvider
     */
    public function testEditUserFail($name, $username, $authenticationService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive){
        
        $id = User::addUserToDB("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1);
        $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $authData = array("id" => $auth["id"], "game_course_user_id" => $id, "username" =>  "ist123456", "authentication_service" => "fenix");
        $userData = array("id" => $id, "name" => "João Carlos Sousa", "email" => "joao@gmail.com", "studentNumber" => "123456", "nickname" => "João Sousa", "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 1);
        $user = new User($id);

        try {

            $user->editUser($name, $username, $authenticationService,  $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
            $this->fail("PDOException should have been thrown for invalid argument on editUser.");

        } catch (\PDOException $e) {
            $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
            $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
            $this->assertEquals($newUserData, $userData);
            $this->assertEquals($newAuthData, $authData);
        }
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testEditUserDuplicateStudentNumberFail(){

        User::addUserToDB("Ana Nogueira", "ist182433", "fenix", "ana.b.nogueira@tecnico.ulisboa.pt", "82433", "Ana N", "MEIC-A", 0, 0);
        $id = User::addUserToDB("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com", "10000", "Ana G", "MEIC-A", 0, 0);
        $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
        $authData = array("id" => $auth["id"], "game_course_user_id" => $id, "username" =>  "ist100000", "authentication_service" => "fenix");
        $userData = array("id" => $id, "name" => "Ana Gonçalves", "email" => "ana.goncalves@gmail.com", "studentNumber" => "10000", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 0);
        $user = new User($id);
        
        try {

            $user->editUser("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com", "82433", "Ana G", "MEIC-A", 0, 0);
            $this->fail("PDOException should have been thrown for duplicate studentNumber on setData.");

        } catch (\PDOException $e) {
            $newUserData = Core::$systemDB->select("game_course_user", ["id" => $id]);
            $newAuthData = Core::$systemDB->select("auth", ["game_course_user_id" => $id]);
            $this->assertEquals($newUserData, $userData);
            $this->assertEquals($newAuthData, $authData);
        }
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetAdmins(){
        $user1 = User::addUserToDB("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com", "10000", "Ana G", "MEIC-A", 1, 0);
        $user2 = User::addUserToDB("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1);
        $user3 = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $admins = User::getAdmins();

        $this->assertCount(2, $admins); //3 if course is set up
        $this->assertContains($user1, $admins);
        $this->assertContains($user3, $admins);
        $this->assertNotContains($user2, $admins);

    }

    /**
     * @depends testUserConstructor
     */
    public function testGetUserByUsernameSuccess(){
        
        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $user = User::getUserByUsername("ist1100956");

        $this->assertTrue($user->exists());
        $this->assertEquals($id, $user->getId());
    }

    /**
     * @depends testUserConstructor
     */
    public function testGetUserByUsernameInexistingUsername(){
        
        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $user = User::getUserByUsername("potatoes123");

        $this->assertNull($user);
    }

    /**
     * @depends testUserConstructor
     */
    public function testGetUserByEmailSuccess(){
        
        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $user = User::getUserByEmail("sabri.m.barki@efrei.net");

        $this->assertTrue($user->exists());
        $this->assertEquals($id, $user->getId());
    }

    /**
     * @depends testUserConstructor
     */
    public function testGetUserByEmailInexistingEmail(){
        
        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $user = User::getUserByEmail("sabri@efrei.net");

        $this->assertNull($user);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetUserIdByUsernameSuccess(){
        
        $expectedId = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $id = User::getUserIdByUsername("ist1100956");

        $this->assertEquals($expectedId, $id);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetUserIdByUsernameInexistingUsername(){
        
        User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $id = User::getUserIdByUsername("ist1100955");

        $this->assertIsBool($id);
        $this->assertFalse($id);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetUserAuthenticationServiceSuccess(){
        
        $expectedService = "fenix";
        User::addUserToDB("Sabri M'Barki", "ist1100956", $expectedService, "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $actualService = User::getUserAuthenticationService("ist1100956");

        $this->assertEquals($expectedService, $actualService);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetUserAuthenticationServiceInexistingUsername(){
        
        $expectedService = "fenix";
        User::addUserToDB("Sabri M'Barki", "ist1100956", $expectedService, "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $actualService = User::getUserAuthenticationService("1100956");

        $this->assertIsBool($actualService);
        $this->assertFalse($actualService);
    }

    /**
     * @depends testUserConstructor
     */
    public function testGetUserByStudentNumberSuccess(){
        
        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $user = User::getUserByStudentNumber("100956");

        $this->assertTrue($user->exists());
        $this->assertEquals($id, $user->getId());
    }
  
    /**
     * @depends testAddUserToDBSuccess
     */
    public function testGetUserByStudentNumberInexistingStudentNumber(){
        
        $id = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        
        $user = User::getUserByStudentNumber("ist1100956");

        $this->assertNull($user);
    }
    
    /**
     * @depends testAddUserToDBSuccess
     */
    public function testExportUser(){

        User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        User::addUserToDB("Marcus Notø", "ist1101036", "fenix", "marcus.n.hansen@gmail", "1101036", "Marcus Notø", "MEEC", 1, 0);
        User::addUserToDB("Inês Albano", "ist187664", "fenix", "ines.albano@tecnico.ulisboa.pt", "87664", null, "MEIC-A", 0, 1);
        User::addUserToDB("Filipe José Zillo Colaço", "ist426015", "fenix", "fijozico@hotmail.com", "84715", null, "LEIC-T", 0, 1);
        User::addUserToDB("Mariana Wong Brandão", "ist186893", "fenix", "marianawbrandao@icloud.com", "86893", "Mariana Brandão", "MEMec", 0, 0);

        $expectedFile = "name,email,nickname,studentNumber,major,isAdmin,isActive,username,auth\n";
        $expectedFile .= "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,MEIC-T,1,1,ist1100956,fenix\n";
        $expectedFile .= "Marcus Notø,marcus.n.hansen@gmail,Marcus Notø,1101036,MEEC,1,0,ist1101036,fenix\n";
        $expectedFile .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,MEIC-A,0,1,ist187664,fenix\n";
        $expectedFile .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,LEIC-T,0,1,ist426015,fenix\n";
        $expectedFile .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,MEMec,0,0,ist186893,fenix";

        $file = User::exportUsers();

        $this->assertEquals($expectedFile, $file);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersWithHeaderUniqueUsersNoReplace(){
        
        //Given
        $file = "name,email,nickname,studentNumber,major,isAdmin,isActive,username,auth\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,MEIC-T,1,1,ist1100956,fenix\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,MEIC-A,0,1,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,LEIC-T,0,1,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,MEMec,0,0,ist186893,facebook";

        //When
        $newUsers = User::importUsers($file, false);

        //Then
        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $user1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "100956"]);
        $user2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "87664"]);
        $user3 = Core::$systemDB->select("game_course_user", ["studentNumber" => "84715"]);
        $user4 = Core::$systemDB->select("game_course_user", ["studentNumber" => "86893"]);

        $auth1 = Core::$systemDB->select("auth", ["game_course_user_id" => $user1["id"]]);
        $auth2 = Core::$systemDB->select("auth", ["game_course_user_id" => $user2["id"]]);
        $auth3 = Core::$systemDB->select("auth", ["game_course_user_id" => $user3["id"]]);
        $auth4 = Core::$systemDB->select("auth", ["game_course_user_id" => $user4["id"]]);

        $expectedUser1 = array("id" => $user1["id"],"name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1);
        $expectedUser2 = array("id" => $user2["id"],"name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "studentNumber" => "87664", "nickname" => null, "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 1);
        $expectedUser3 = array("id" => $user3["id"],"name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "studentNumber" => "84715", "nickname" => null, "major" =>  "LEIC-T", "isAdmin" => 0, "isActive" => 1);
        $expectedUser4 = array("id" => $user4["id"],"name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "studentNumber" => "86893", "nickname" => "Mariana Brandão", "major" =>  "MEMec", "isAdmin" => 0, "isActive" => 0);
        
        $expectedAuth1 = array("id" => $auth1["id"], "game_course_user_id" => $user1["id"], "username" => "ist1100956", "authentication_service" => "fenix");
        $expectedAuth2 = array("id" => $auth2["id"], "game_course_user_id" => $user2["id"], "username" => "ist187664", "authentication_service" => "linkedin");
        $expectedAuth3 = array("id" => $auth3["id"], "game_course_user_id" => $user3["id"], "username" => "ist426015", "authentication_service" => "fenix");
        $expectedAuth4 = array("id" => $auth4["id"], "game_course_user_id" => $user4["id"], "username" => "ist186893", "authentication_service" => "facebook");

        
        $this->assertCount(4, $users);
        $this->assertEquals(4, $newUsers);
        $this->assertEquals($expectedUser1, $user1);
        $this->assertEquals($expectedUser2, $user2);
        $this->assertEquals($expectedUser3, $user3);
        $this->assertEquals($expectedUser4, $user4);
        $this->assertEquals($expectedAuth1, $auth1);
        $this->assertEquals($expectedAuth2, $auth2);
        $this->assertEquals($expectedAuth3, $auth3);
        $this->assertEquals($expectedAuth4, $auth4);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersWithHeaderNonUniqueUsersNoReplace(){
        
        //Given
        $user0 = User::addUserToDB("Ana Rita Gonçalves", "ist110001", "fenix", "ana.goncalves@hotmail.com", "10001", "Ana G", "MEIC-A", 1, 0);
        
        $file = "name,email,nickname,studentNumber,major,isAdmin,isActive,username,auth\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,MEIC-T,1,1,ist1100956,fenix\n";
        $file .= "Marcus Notø,marcus.n.hansen@gmail,Marcus Notø,1101036,MEEC,1,0,ist1101036,google\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,10001,MEIC-A,0,1,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,LEIC-T,0,1,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,MEMec,0,0,ist186893,facebook";

        //When
        $newUsers = User::importUsers($file, false);

        //Then
        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $user1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "10001"]);
        $user2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "100956"]);
        $user3 = Core::$systemDB->select("game_course_user", ["studentNumber" => "1101036"]);
        $user4 = Core::$systemDB->select("game_course_user", ["studentNumber" => "84715"]);
        $user5 = Core::$systemDB->select("game_course_user", ["studentNumber" => "86893"]);


        $auth1 = Core::$systemDB->select("auth", ["game_course_user_id" => $user0]);
        $auth2 = Core::$systemDB->select("auth", ["game_course_user_id" => $user2["id"]]);
        $auth3 = Core::$systemDB->select("auth", ["game_course_user_id" => $user3["id"]]);
        $auth4 = Core::$systemDB->select("auth", ["game_course_user_id" => $user4["id"]]);
        $auth5 = Core::$systemDB->select("auth", ["game_course_user_id" => $user5["id"]]);

        $expectedUser1 = array("id" => $user0,"name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0);
        $expectedUser2 = array("id" => $user2["id"],"name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1);
        $expectedUser3 = array("id" => $user3["id"],"name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0);
        $expectedUser4 = array("id" => $user4["id"],"name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "studentNumber" => "84715", "nickname" => null, "major" =>  "LEIC-T", "isAdmin" => 0, "isActive" => 1);
        $expectedUser5 = array("id" => $user5["id"],"name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "studentNumber" => "86893", "nickname" => "Mariana Brandão", "major" =>  "MEMec", "isAdmin" => 0, "isActive" => 0);
        
        $expectedAuth1 = array("id" => $auth1["id"], "game_course_user_id" => $user0, "username" => "ist110001", "authentication_service" => "fenix");
        $expectedAuth2 = array("id" => $auth2["id"], "game_course_user_id" => $user2["id"], "username" => "ist1100956", "authentication_service" => "fenix");
        $expectedAuth3 = array("id" => $auth3["id"], "game_course_user_id" => $user3["id"], "username" => "ist1101036", "authentication_service" => "google");
        $expectedAuth4 = array("id" => $auth4["id"], "game_course_user_id" => $user4["id"], "username" => "ist426015", "authentication_service" => "fenix");
        $expectedAuth5 = array("id" => $auth5["id"], "game_course_user_id" => $user5["id"], "username" => "ist186893", "authentication_service" => "facebook");

        
        $this->assertCount(5, $users);
        $this->assertEquals(4, $newUsers);
        $this->assertEquals($expectedUser1, $user1);
        $this->assertEquals($expectedUser2, $user2);
        $this->assertEquals($expectedUser3, $user3);
        $this->assertEquals($expectedUser4, $user4);
        $this->assertEquals($expectedUser5, $user5);
        $this->assertEquals($expectedAuth1, $auth1);
        $this->assertEquals($expectedAuth2, $auth2);
        $this->assertEquals($expectedAuth3, $auth3);
        $this->assertEquals($expectedAuth4, $auth4);
        $this->assertEquals($expectedAuth5, $auth5);
    }
    
    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersWithHeaderNonUniqueUsersReplace(){
        
        //Given
        $user0 = User::addUserToDB("Ana Rita Gonçalves", "ist187664", "linkedin", "ana.goncalves@hotmail.com", "87664", "Ana G", "MEIC-A", 1, 0);
        
        $file = "name,email,nickname,studentNumber,major,isAdmin,isActive,username,auth\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,MEIC-T,1,1,ist1100956,fenix\n";
        $file .= "Marcus Notø,marcus.n.hansen@gmail,Marcus Notø,1101036,MEEC,1,0,ist1101036,google\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,MEIC-A,0,1,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,LEIC-T,0,1,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,MEMec,0,0,ist186893,facebook";

        //When
        $newUsers = User::importUsers($file);

        //Then
        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $user1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "100956"]);
        $user2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "1101036"]);
        $user3 = Core::$systemDB->select("game_course_user", ["studentNumber" => "87664"]);
        $user4 = Core::$systemDB->select("game_course_user", ["studentNumber" => "84715"]);
        $user5 = Core::$systemDB->select("game_course_user", ["studentNumber" => "86893"]);

        $auth1 = Core::$systemDB->select("auth", ["game_course_user_id" => $user1["id"]]);
        $auth2 = Core::$systemDB->select("auth", ["game_course_user_id" => $user2["id"]]);
        $auth3 = Core::$systemDB->select("auth", ["game_course_user_id" => $user3["id"]]);
        $auth4 = Core::$systemDB->select("auth", ["game_course_user_id" => $user4["id"]]);
        $auth5 = Core::$systemDB->select("auth", ["game_course_user_id" => $user5["id"]]);

        $expectedUser1 = array("id" => $user1["id"],"name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1);
        $expectedUser2 = array("id" => $user2["id"],"name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0);
        $expectedUser3 = array("id" => $user3["id"],"name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "studentNumber" => "87664", "nickname" => null, "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 1);
        $expectedUser4 = array("id" => $user4["id"],"name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "studentNumber" => "84715", "nickname" => null, "major" =>  "LEIC-T", "isAdmin" => 0, "isActive" => 1);
        $expectedUser5 = array("id" => $user5["id"],"name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "studentNumber" => "86893", "nickname" => "Mariana Brandão", "major" =>  "MEMec", "isAdmin" => 0, "isActive" => 0);
        
        $expectedAuth1 = array("id" => $auth1["id"], "game_course_user_id" => $user1["id"], "username" => "ist1100956", "authentication_service" => "fenix");
        $expectedAuth2 = array("id" => $auth2["id"], "game_course_user_id" => $user2["id"], "username" => "ist1101036", "authentication_service" => "google");
        $expectedAuth3 = array("id" => $auth3["id"], "game_course_user_id" => $user3["id"], "username" => "ist187664", "authentication_service" => "linkedin");
        $expectedAuth4 = array("id" => $auth4["id"], "game_course_user_id" => $user4["id"], "username" => "ist426015", "authentication_service" => "fenix");
        $expectedAuth5 = array("id" => $auth5["id"], "game_course_user_id" => $user5["id"], "username" => "ist186893", "authentication_service" => "facebook");

        
        $this->assertCount(5, $users);
        $this->assertEquals(4, $newUsers);
        $this->assertEquals($expectedUser1, $user1);
        $this->assertEquals($expectedUser2, $user2);
        $this->assertEquals($expectedUser3, $user3);
        $this->assertEquals($expectedUser4, $user4);
        $this->assertEquals($expectedUser5, $user5);
        $this->assertEquals($expectedAuth1, $auth1);
        $this->assertEquals($expectedAuth2, $auth2);
        $this->assertEquals($expectedAuth3, $auth3);
        $this->assertEquals($expectedAuth4, $auth4);
        $this->assertEquals($expectedAuth5, $auth5);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersNoHeaderUniqueUsersReplace(){
        
        //Given
        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,MEIC-T,1,1,ist1100956,fenix\n";
        $file .= "Marcus Notø,marcus.n.hansen@gmail,Marcus Notø,1101036,MEEC,1,0,ist1101036,google\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,MEIC-A,0,1,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,LEIC-T,0,1,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,MEMec,0,0,ist186893,facebook";

        //When
        $newUsers = User::importUsers($file);

        //Then
        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $user1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "100956"]);
        $user2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "1101036"]);
        $user3 = Core::$systemDB->select("game_course_user", ["studentNumber" => "87664"]);
        $user4 = Core::$systemDB->select("game_course_user", ["studentNumber" => "84715"]);
        $user5 = Core::$systemDB->select("game_course_user", ["studentNumber" => "86893"]);

        $auth1 = Core::$systemDB->select("auth", ["game_course_user_id" => $user1["id"]]);
        $auth2 = Core::$systemDB->select("auth", ["game_course_user_id" => $user2["id"]]);
        $auth3 = Core::$systemDB->select("auth", ["game_course_user_id" => $user3["id"]]);
        $auth4 = Core::$systemDB->select("auth", ["game_course_user_id" => $user4["id"]]);
        $auth5 = Core::$systemDB->select("auth", ["game_course_user_id" => $user5["id"]]);

        $expectedUser1 = array("id" => $user1["id"],"name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1);
        $expectedUser2 = array("id" => $user2["id"],"name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0);
        $expectedUser3 = array("id" => $user3["id"],"name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "studentNumber" => "87664", "nickname" => null, "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 1);
        $expectedUser4 = array("id" => $user4["id"],"name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "studentNumber" => "84715", "nickname" => null, "major" =>  "LEIC-T", "isAdmin" => 0, "isActive" => 1);
        $expectedUser5 = array("id" => $user5["id"],"name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "studentNumber" => "86893", "nickname" => "Mariana Brandão", "major" =>  "MEMec", "isAdmin" => 0, "isActive" => 0);
        
        $expectedAuth1 = array("id" => $auth1["id"], "game_course_user_id" => $user1["id"], "username" => "ist1100956", "authentication_service" => "fenix");
        $expectedAuth2 = array("id" => $auth2["id"], "game_course_user_id" => $user2["id"], "username" => "ist1101036", "authentication_service" => "google");
        $expectedAuth3 = array("id" => $auth3["id"], "game_course_user_id" => $user3["id"], "username" => "ist187664", "authentication_service" => "linkedin");
        $expectedAuth4 = array("id" => $auth4["id"], "game_course_user_id" => $user4["id"], "username" => "ist426015", "authentication_service" => "fenix");
        $expectedAuth5 = array("id" => $auth5["id"], "game_course_user_id" => $user5["id"], "username" => "ist186893", "authentication_service" => "facebook");

        
        $this->assertCount(5, $users);
        $this->assertEquals(5, $newUsers);
        $this->assertEquals($expectedUser1, $user1);
        $this->assertEquals($expectedUser2, $user2);
        $this->assertEquals($expectedUser3, $user3);
        $this->assertEquals($expectedUser4, $user4);
        $this->assertEquals($expectedUser5, $user5);
        $this->assertEquals($expectedAuth1, $auth1);
        $this->assertEquals($expectedAuth2, $auth2);
        $this->assertEquals($expectedAuth3, $auth3);
        $this->assertEquals($expectedAuth4, $auth4);
        $this->assertEquals($expectedAuth5, $auth5);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersNoHeaderNonUniqueUsersReplace(){
        
        //Given
        $user0 = User::addUserToDB("Ana Rita Gonçalves", "ist187664", "linkedin", "ana.goncalves@hotmail.com", "87664", "Ana G", "MEIC-A", 1, 0);
        
        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,MEIC-T,1,1,ist1100956,fenix\n";
        $file .= "Marcus Notø,marcus.n.hansen@gmail,Marcus Notø,1101036,MEEC,1,0,ist1101036,google\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,87664,MEIC-A,0,1,ist187664,linkedin\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,,84715,LEIC-T,0,1,ist426015,fenix\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,Mariana Brandão,86893,MEMec,0,0,ist186893,facebook";

        //When
        $newUsers = User::importUsers($file);

        //Then
        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $user1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "100956"]);
        $user2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "1101036"]);
        $user3 = Core::$systemDB->select("game_course_user", ["studentNumber" => "87664"]);
        $user4 = Core::$systemDB->select("game_course_user", ["studentNumber" => "84715"]);
        $user5 = Core::$systemDB->select("game_course_user", ["studentNumber" => "86893"]);

        $auth1 = Core::$systemDB->select("auth", ["game_course_user_id" => $user1["id"]]);
        $auth2 = Core::$systemDB->select("auth", ["game_course_user_id" => $user2["id"]]);
        $auth3 = Core::$systemDB->select("auth", ["game_course_user_id" => $user3["id"]]);
        $auth4 = Core::$systemDB->select("auth", ["game_course_user_id" => $user4["id"]]);
        $auth5 = Core::$systemDB->select("auth", ["game_course_user_id" => $user5["id"]]);

        $expectedUser1 = array("id" => $user1["id"],"name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1);
        $expectedUser2 = array("id" => $user2["id"],"name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0);
        $expectedUser3 = array("id" => $user3["id"],"name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "studentNumber" => "87664", "nickname" => null, "major" =>  "MEIC-A", "isAdmin" => 0, "isActive" => 1);
        $expectedUser4 = array("id" => $user4["id"],"name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "studentNumber" => "84715", "nickname" => null, "major" =>  "LEIC-T", "isAdmin" => 0, "isActive" => 1);
        $expectedUser5 = array("id" => $user5["id"],"name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "studentNumber" => "86893", "nickname" => "Mariana Brandão", "major" =>  "MEMec", "isAdmin" => 0, "isActive" => 0);
        
        $expectedAuth1 = array("id" => $auth1["id"], "game_course_user_id" => $user1["id"], "username" => "ist1100956", "authentication_service" => "fenix");
        $expectedAuth2 = array("id" => $auth2["id"], "game_course_user_id" => $user2["id"], "username" => "ist1101036", "authentication_service" => "google");
        $expectedAuth3 = array("id" => $auth3["id"], "game_course_user_id" => $user3["id"], "username" => "ist187664", "authentication_service" => "linkedin");
        $expectedAuth4 = array("id" => $auth4["id"], "game_course_user_id" => $user4["id"], "username" => "ist426015", "authentication_service" => "fenix");
        $expectedAuth5 = array("id" => $auth5["id"], "game_course_user_id" => $user5["id"], "username" => "ist186893", "authentication_service" => "facebook");

        
        $this->assertCount(5, $users);
        $this->assertEquals(4, $newUsers);
        $this->assertEquals($expectedUser1, $user1);
        $this->assertEquals($expectedUser2, $user2);
        $this->assertEquals($expectedUser3, $user3);
        $this->assertEquals($expectedUser4, $user4);
        $this->assertEquals($expectedUser5, $user5);
        $this->assertEquals($expectedAuth1, $auth1);
        $this->assertEquals($expectedAuth2, $auth2);
        $this->assertEquals($expectedAuth3, $auth3);
        $this->assertEquals($expectedAuth4, $auth4);
        $this->assertEquals($expectedAuth5, $auth5);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersNoHeaderNonUniqueUsersNoReplace(){
        
        //Given
        $user0 = User::addUserToDB("Ana Rita Gonçalves", "ist110001", "fenix", "ana.goncalves@hotmail.com", "10001", "Ana G", "MEIC-A", 1, 0);
        
        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,Sabri M'Barki,100956,MEIC-T,1,1,ist1100956,fenix\n";
        $file .= "Marcus Notø,marcus.n.hansen@gmail,Marcus Notø,1101036,MEEC,1,0,ist1101036,google\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,,10001,MEIC-A,0,1,ist110001,fenix\n";
        
        //When
        $newUsers = User::importUsers($file, false);

        //Then
        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $user1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "10001"]);
        $user2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "100956"]);
        $user3 = Core::$systemDB->select("game_course_user", ["studentNumber" => "1101036"]);

        $auth1 = Core::$systemDB->select("auth", ["game_course_user_id" => $user0]);
        $auth2 = Core::$systemDB->select("auth", ["game_course_user_id" => $user2["id"]]);
        $auth3 = Core::$systemDB->select("auth", ["game_course_user_id" => $user3["id"]]);

        $expectedUser1 = array("id" => $user0,"name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0);
        $expectedUser2 = array("id" => $user2["id"],"name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "studentNumber" => "100956", "nickname" => "Sabri M'Barki", "major" =>  "MEIC-T", "isAdmin" => 1, "isActive" => 1);
        $expectedUser3 = array("id" => $user3["id"],"name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0);

        $expectedAuth1 = array("id" => $auth1["id"], "game_course_user_id" => $user0, "username" => "ist110001", "authentication_service" => "fenix");
        $expectedAuth2 = array("id" => $auth2["id"], "game_course_user_id" => $user2["id"], "username" => "ist1100956", "authentication_service" => "fenix");
        $expectedAuth3 = array("id" => $auth3["id"], "game_course_user_id" => $user3["id"], "username" => "ist1101036", "authentication_service" => "google");
        
        $this->assertCount(3, $users);
        $this->assertEquals(2, $newUsers);
        $this->assertEquals($expectedUser1, $user1);
        $this->assertEquals($expectedUser2, $user2);
        $this->assertEquals($expectedUser3, $user3);
        $this->assertEquals($expectedAuth1, $auth1);
        $this->assertEquals($expectedAuth2, $auth2);
        $this->assertEquals($expectedAuth3, $auth3);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersEmptyFileNoUsers(){

        $file = "";

        $newUsers = User::importUsers($file);

        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $this->assertEquals(0, $newUsers);
        $this->assertEmpty($users);
    }

    /**
     * @depends testAddUserToDBSuccess
     */
    public function testImportUsersEmptyFileWithUsers(){

        $user1 = User::addUserToDB("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com", "10000", "Ana G", "MEIC-A", 1, 0);
        $user2 = User::addUserToDB("João Carlos Sousa", "ist123456", "fenix", "joao@gmail.com", "123456", "João Sousa", "MEIC-A", 0, 1);
        $user3 = User::addUserToDB("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net", "100956", "Sabri M'Barki", "MEIC-T", 1, 1);
        $file = "";

        $newUsers = User::importUsers($file);

        $users = Core::$systemDB->selectMultiple("game_course_user", []);
        $this->assertEquals(0, $newUsers);
        $this->assertCount(3, $users);
    }   

}