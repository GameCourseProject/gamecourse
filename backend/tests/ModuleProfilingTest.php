<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use Modules\Profiling\Profiling;

use PHPUnit\Framework\TestCase;


class ModuleProfilingTest extends TestCase
{
    protected $profiling;

    public static function setUpBeforeClass():void {
        Core::init();
        Core::$systemDB->executeQuery(file_get_contents("modules/" . Profiling::ID . "/create.sql"));
    }

    protected function setUp():void {
        $this->profiling = new Profiling();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
        Core::$systemDB->deleteAll("game_course_user");
    }

    public static function tearDownAfterClass(): void {
        Core::$systemDB->executeQuery(file_get_contents("modules/" . Profiling::ID . "/delete.sql"));
    }

    //Data Providers

    public function invalidClusterListProvider(){
        return array(
            array([1 => "Hacker", 2 => "Spy", 3 => "Sleuth"], []),      //empty assignedClusters
            array([1 => "Hacker", 2 => "Spy", 3 => "Sleuth"], null),    //null assignedClusters
            array([], [3,2,3,1,1]),                                     //empty names
            array(null, [3,2,3,1,1]),                                   //null names
        );
    }

    public function getClusterNamesSuccessProvider(){
        return array(
            array(json_encode([["name" => "Student", "children" => [["name" => "Profiling"]]]]), ["Achiever", "Regular", "Halfhearted", "Underachiever"]),    //base names returned
            array(json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                           ["name" => "Regular"], 
                                                                                                           ["name" => "Underachiever"], 
                                                                                                           ["name" => "Halfhearted"]]]]]]), 
                              ["Achiever", "Regular", "Underachiever", "Halfhearted"]),                                                                       //defined roles returned in order
            array(json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "A"], 
                                                                                                           ["name" => "B"], 
                                                                                                           ["name" => "C"]]]]]]), 
                              ["A", "B", "C"]),                                                                                                               //random names, 3 clusters
            array(json_encode([["name" => "Observer"], ["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "A"],
                                                                                                                                    ["name" => "B"],
                                                                                                                                    ["name" => "C"]]]]]]),
                                ["A", "B", "C"]),                                                                                                                             //student in second position
            array(json_encode([["name" => "Observer"], ["name" => "Student", "children" => [["name" => "Other"], ["name" => "Profiling", "children" => [["name" => "A"],
                                                                                                                                                        ["name" => "B"],
                                                                                                                                                        ["name" => "C"]]]]]]),
                                ["A", "B", "C"])                                                                                                                              //profiling in second position
        );
    }

    public function invalidImportFileProvider(){
        return array(
            array(""),                                                                                              //empty file
            array(null),                                                                                            //null file
            array("username\n"),                                                                                    //empty file with incomplete header
            array("username;2020-06-20 14:30:00;2020-06-23 18:10:42\n"),                                            //empty file with header
            array("ist112122;Hacker;Spy\nist110001;Spy;Hacker\nist11101036;Sleuth;Sleuth\nist1100956;Sleuth;Spy\n") //no header
        );
    }


    public function testDeleteSavedOneCourseSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 2"]);
        
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster2]);

        //When
        $this->profiling->deleteSaved($courseId);

        //Then
        $records = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $courseId]);
        $courseUsers = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId]);
        $gamecourseUsers = Core::$systemDB->selectMultiple("game_course_user", []);

        $this->assertEmpty($records);
        $this->assertCount(3, $courseUsers);
        $this->assertCount(3, $gamecourseUsers);
    }

    public function testDeleteSavedTwoCoursesSuccess(){

        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course2]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course1]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 2"]);
        $clusterA = Core::$systemDB->insert("role", ["course" => $course2, "name" => "Cluster A"]);
        
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course2, "cluster" => $clusterA]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3, "course" => $course1, "cluster" => $cluster2]);

        //When
        $this->profiling->deleteSaved($course1);

        //Then
        $recordsCourse1 = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $course1]);
        $recordsCourse2 = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $course2]);
        $courseUsersCourse1 = Core::$systemDB->selectMultiple("course_user", ["course" => $course1]);
        $courseUsersCourse2 = Core::$systemDB->selectMultiple("course_user", ["course" => $course2]);
        $gamecourseUsers = Core::$systemDB->selectMultiple("game_course_user", []);

        $this->assertEmpty($recordsCourse1);
        $this->assertCount(1, $recordsCourse2);
        $this->assertEquals(array("user" => $user1, "course" => $course2, "cluster" => $clusterA), $recordsCourse2[0]);
        $this->assertCount(3, $courseUsersCourse1);
        $this->assertCount(1, $courseUsersCourse2);
        $this->assertCount(3, $gamecourseUsers);
    }

    public function testDeleteSavedInvalidCourse(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course1]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 2"]);
        
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2, "course" => $course1, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3, "course" => $course1, "cluster" => $cluster2]);

        //When
        $this->profiling->deleteSaved($course1 + 3);
        
        //Then
        $records = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $course1]);
        $courseUsers = Core::$systemDB->selectMultiple("course_user", ["course" => $course1]);
        $gamecourseUsers = Core::$systemDB->selectMultiple("game_course_user", []);

        $this->assertCount(3, $records);
        $this->assertCount(3, $courseUsers);
        $this->assertCount(3, $gamecourseUsers);
        
    }

    public function testDeleteSavedNullCourse(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course1]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 2"]);
        
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2, "course" => $course1, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3, "course" => $course1, "cluster" => $cluster2]);

        //When
        $this->profiling->deleteSaved(null);
        
        //Then
        $records = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $course1]);
        $courseUsers = Core::$systemDB->selectMultiple("course_user", ["course" => $course1]);
        $gamecourseUsers = Core::$systemDB->selectMultiple("game_course_user", []);

        $this->assertCount(3, $records);
        $this->assertCount(3, $courseUsers);
        $this->assertCount(3, $gamecourseUsers);
        
    }

    public function testGetSavedClustersSuccess(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $course2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course2]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course1]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 2"]);
        $clusterA = Core::$systemDB->insert("role", ["course" => $course2, "name" => "Cluster A"]);
        
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course2, "cluster" => $clusterA]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3, "course" => $course1, "cluster" => $cluster2]);

        //When
        $clusters = $this->profiling->getSavedClusters($course1);

        //Then
        $expectedClusters =  array($user1 => $cluster1, $user2 => $cluster1, $user3 => $cluster2);
        $this->assertEquals($expectedClusters, $clusters);

        $records = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $course1]);
        $this->assertCount(3, $records);

        $records = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, []);
        $this->assertCount(4, $records);
    }

    public function testGetSavedClustersInvalidCourse(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course1]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 2"]);
        
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3, "course" => $course1, "cluster" => $cluster2]);

        //When
        $clusters = $this->profiling->getSavedClusters($cluster1 + 4);

        //Then
        $this->assertEmpty($clusters);

        $records = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $course1]);
        $this->assertCount(3, $records);

    }

    public function testGetSavedClustersNullCourse(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $course1]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $course1]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $course1, "name" => "Cluster 2"]);
        
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2, "course" => $course1, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3, "course" => $course1, "cluster" => $cluster2]);

        //When
        $clusters = $this->profiling->getSavedClusters(null);

        //Then
        $this->assertEmpty($clusters);

        $records = Core::$systemDB->selectMultiple(Profiling::TABLE_SAVED_USER_PROFILE, ["course" => $course1]);
        $this->assertCount(3, $records);

    }

    public function testSaveClustersSuccess(){
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 2"]);

        $clusters = array($user1 => $cluster2, $user2 => $cluster2, $user3 => $cluster1);
        
        //When
        $this->profiling->saveClusters($courseId, $clusters);

        //Then
        $entries = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, []);
        $this->assertCount(3, $entries);

        $clusterUser1 = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1]);
        $clusterUser2 = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2]);
        $clusterUser3 = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3]);

        $expectedClusterUser1 = array("user" => $user1, "course" => $courseId, "cluster" => $cluster2);
        $expectedClusterUser2 = array("user" => $user2, "course" => $courseId, "cluster" => $cluster2);
        $expectedClusterUser3 = array("user" => $user3, "course" => $courseId, "cluster" => $cluster1);

        $this->assertEquals($expectedClusterUser1, $clusterUser1);
        $this->assertEquals($expectedClusterUser2, $clusterUser2);
        $this->assertEquals($expectedClusterUser3, $clusterUser3);

    }

    public function testSaveClustersSRepeatedEntries(){
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 2"]);

        Core::$systemDB->insert(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        
        $clusters = array($user1 => $cluster2, $user2 => $cluster2, $user3 => $cluster1);
        
        //When
        $this->profiling->saveClusters($courseId, $clusters);

        //Then
        $entries = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, []);
        $this->assertCount(3, $entries);

        $clusterUser1 = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user1]);
        $clusterUser2 = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user2]);
        $clusterUser3 = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, ["user" => $user3]);

        $expectedClusterUser1 = array("user" => $user1, "course" => $courseId, "cluster" => $cluster2);
        $expectedClusterUser2 = array("user" => $user2, "course" => $courseId, "cluster" => $cluster2);
        $expectedClusterUser3 = array("user" => $user3, "course" => $courseId, "cluster" => $cluster1);

        $this->assertEquals($expectedClusterUser1, $clusterUser1);
        $this->assertEquals($expectedClusterUser2, $clusterUser2);
        $this->assertEquals($expectedClusterUser3, $clusterUser3);

    }

    public function testSaveClustersEmptyClusters(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        //When
        $this->profiling->saveClusters($courseId, array());

        //Then
        $entries = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, []);
        $this->assertEmpty($entries);

    }

    public function testSaveClustersNullClusters(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        //When
        $this->profiling->saveClusters($courseId, null);

        //Then
        $entries = Core::$systemDB->select(Profiling::TABLE_SAVED_USER_PROFILE, []);
        $this->assertEmpty($entries);

    }

    /**
     * @dataProvider getClusterNamesSuccessProvider
     */
    public function testGetClusterNamesSuccess($hierarchy, $expectedNames){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => $hierarchy]);
        
        //When
        $names = $this->profiling->getClusterNames($courseId);

        //Then
        $this->assertEquals($expectedNames, $names);
    }

    public function testGetClusterNamesNoProfilingRole(){

        //Given
        $hierarchy = json_encode([["name" => "Student", "children" => []]]);
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => $hierarchy]);
        
        //When
        $names = $this->profiling->getClusterNames($courseId);

        //Then
        $expectedNames = array("Achiever", "Regular", "Halfhearted", "Underachiever");
        $this->assertEquals($expectedNames, $names);
    }

    public function testGetClusterNamesNullCourse(){

        //Given
        $hierarchy = json_encode([["name" => "Student", "children" => []]]);
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => $hierarchy]);
        
        //When
        $names = $this->profiling->getClusterNames(null);

        //Then
        $this->assertEmpty($names);
    }

    public function testGetClusterNamesInexistingCourse(){

        //Given
        $hierarchy = json_encode([["name" => "Student", "children" => []]]);
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => $hierarchy]);
        
        //When
        $names = $this->profiling->getClusterNames($courseId + 2);

        //Then
        $this->assertEmpty($names);
    }

    public function testCreateNamesArraySuccess(){
        
        //Given
        $clusters = array("Achiever", "Regular", "Halfhearted", "Underachiever");

        //When
        $names = $this->profiling->createNamesArray($clusters);

        //Then
        $expectedNames = array(["name" => "Achiever"], ["name" => "Regular"], ["name" => "Halfhearted"], ["name" => "Underachiever"]);
        $this->assertEquals($expectedNames, $names);

    }

    public function testCreateNamesArrayEmptyArray(){
        
        //Given
        $clusters = array();

        //When
        $names = $this->profiling->createNamesArray($clusters);

        //Then
        $this->assertEmpty($names);
    }

    public function testRemoveClusterRolesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 2"]);
        
        $time = date('Y-m-d H:i:s');
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user2, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $cluster1]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $cluster2]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $cluster2]);

        //When
        $this->profiling->removeClusterRoles($courseId);

        //Then
        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);
        $rolesUser2 = Core::$systemDB->selectMultiple("user_role", ["id" => $user2]);
        $rolesUser3 = Core::$systemDB->selectMultiple("user_role", ["id" => $user3]);

        $expectedRolesUser1 = array(["id" => $user1, "course" => $courseId, "role" => $student]);
        $expectedRolesUser2 = array(["id" => $user2, "course" => $courseId, "role" => $student]);
        $expectedRolesUser3 = array(["id" => $user3, "course" => $courseId, "role" => $student]);

        $this->assertEquals($expectedRolesUser1, $rolesUser1);
        $this->assertEquals($expectedRolesUser2, $rolesUser2);
        $this->assertEquals($expectedRolesUser3, $rolesUser3);

        $profilesUser1 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profilesUser2 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profilesUser3 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);

        $expectedProfilesUser1 = array(["user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time]);
        $expectedProfilesUser2 = array(["user" => $user2, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);
        $expectedProfilesUser3 = array(["user" => $user3, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);

        $this->assertEquals($expectedProfilesUser1, $profilesUser1);
        $this->assertEquals($expectedProfilesUser2, $profilesUser2);
        $this->assertEquals($expectedProfilesUser3, $profilesUser3);

    }

    public function testRemoveClusterRolesNoClusters(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        //When
        $this->profiling->removeClusterRoles($courseId);

        //Then
        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);
        $rolesUser2 = Core::$systemDB->selectMultiple("user_role", ["id" => $user2]);
        $rolesUser3 = Core::$systemDB->selectMultiple("user_role", ["id" => $user3]);

        $expectedRolesUser1 = array(["id" => $user1, "course" => $courseId, "role" => $student]);
        $expectedRolesUser2 = array(["id" => $user2, "course" => $courseId, "role" => $student]);
        $expectedRolesUser3 = array(["id" => $user3, "course" => $courseId, "role" => $student]);

        $this->assertEquals($expectedRolesUser1, $rolesUser1);
        $this->assertEquals($expectedRolesUser2, $rolesUser2);
        $this->assertEquals($expectedRolesUser3, $rolesUser3);

    }

    public function testRemoveClusterRolesNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 1"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Cluster 2"]);
        
        $time = date('Y-m-d H:i:s');
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user2, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $cluster1]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $cluster2]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $cluster2]);

        //When
        $this->profiling->removeClusterRoles(null);

        //Then
        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);
        $rolesUser2 = Core::$systemDB->selectMultiple("user_role", ["id" => $user2]);
        $rolesUser3 = Core::$systemDB->selectMultiple("user_role", ["id" => $user3]);

        $expectedRolesUser1 = array(["id" => $user1, "course" => $courseId, "role" => $student], ["id" => $user1, "course" => $courseId, "role" => $cluster1]);
        $expectedRolesUser2 = array(["id" => $user2, "course" => $courseId, "role" => $student], ["id" => $user2, "course" => $courseId, "role" => $cluster2]);
        $expectedRolesUser3 = array(["id" => $user3, "course" => $courseId, "role" => $student], ["id" => $user3, "course" => $courseId, "role" => $cluster2]);

        $this->assertEquals($expectedRolesUser1, $rolesUser1);
        $this->assertEquals($expectedRolesUser2, $rolesUser2);
        $this->assertEquals($expectedRolesUser3, $rolesUser3);

        $profilesUser1 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profilesUser2 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profilesUser3 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);

        $expectedProfilesUser1 = array(["user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time]);
        $expectedProfilesUser2 = array(["user" => $user2, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);
        $expectedProfilesUser3 = array(["user" => $user3, "course" => $courseId, "cluster" => $cluster2, "date" => $time]);

        $this->assertEquals($expectedProfilesUser1, $profilesUser1);
        $this->assertEquals($expectedProfilesUser2, $profilesUser2);
        $this->assertEquals($expectedProfilesUser3, $profilesUser3);

    }

    public function testDeleteClusterRolesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", 
                                                        "short" => "MCP", 
                                                        "year" => "2019-2020", 
                                                        "color" => "#79bf43", 
                                                        "isActive" => 1, 
                                                        "isVisible" => 1,
                                                        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                                                                    ["name" => "Regular"],
                                                                                                                                                                    ["name" => "Halfhearted"], 
                                                                                                                                                                    ["name" => "Underachiever"],
                                                                                                                                                                    ["name" => "Under Underachiever"]]]]]])
                                            ]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);
        $cluster5 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Under Underachiever"]);
        
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user2, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster3]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster4]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $cluster1]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $cluster2]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $cluster4]);

        //When
        $this->profiling->deleteClusterRoles($courseId);

        //Then
        $newProfiling = Core::$systemDB->select("role", ["name" => "Profiling"]);
        $newCluster1 = Core::$systemDB->select("role", ["name" => "Achiever"]);
        $newCluster2 = Core::$systemDB->select("role", ["name" => "Regular"]);
        $newCluster3 = Core::$systemDB->select("role", ["name" => "Halfhearted"]);
        $newCluster4 = Core::$systemDB->select("role", ["name" => "Underachiever"]);
        $newCluster5 = Core::$systemDB->select("role", ["name" => "Under Underachiever"]);

        $this->assertEmpty($newProfiling);
        $this->assertEmpty($newCluster1);
        $this->assertEmpty($newCluster2);
        $this->assertEmpty($newCluster3);
        $this->assertEmpty($newCluster4);
        $this->assertEmpty($newCluster5);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array();
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);
    }

    public function testDeleteClusterRolesNoStudentsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", 
                                                        "short" => "MCP", 
                                                        "year" => "2019-2020", 
                                                        "color" => "#79bf43", 
                                                        "isActive" => 1, 
                                                        "isVisible" => 1,
                                                        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                                                                    ["name" => "Regular"],
                                                                                                                                                                    ["name" => "Halfhearted"], 
                                                                                                                                                                    ["name" => "Underachiever"],
                                                                                                                                                                    ["name" => "Under Underachiever"]]]]]])
                                            ]);


        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        //When
        $this->profiling->deleteClusterRoles($courseId);

        //Then
        $newProfiling = Core::$systemDB->select("role", ["name" => "Profiling"]);
        $newCluster1 = Core::$systemDB->select("role", ["name" => "Achiever"]);
        $newCluster2 = Core::$systemDB->select("role", ["name" => "Regular"]);
        $newCluster3 = Core::$systemDB->select("role", ["name" => "Halfhearted"]);
        $newCluster4 = Core::$systemDB->select("role", ["name" => "Underachiever"]);

        $this->assertEmpty($newProfiling);
        $this->assertEmpty($newCluster1);
        $this->assertEmpty($newCluster2);
        $this->assertEmpty($newCluster3);
        $this->assertEmpty($newCluster4);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array();
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);
    }

    public function testDeleteClusterRolesNoClusters(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", 
                                                        "short" => "MCP", 
                                                        "year" => "2019-2020", 
                                                        "color" => "#79bf43", 
                                                        "isActive" => 1, 
                                                        "isVisible" => 1,
                                                        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling"]]]])
                                            ]);


        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

        //When
        $this->profiling->deleteClusterRoles($courseId);

        //Then
        $newProfiling = Core::$systemDB->select("role", ["name" => "Profiling"]);

        $this->assertEmpty($newProfiling);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array();
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);
    }

    public function testDeleteClusterRolesNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", 
                                                        "short" => "MCP", 
                                                        "year" => "2019-2020", 
                                                        "color" => "#79bf43", 
                                                        "isActive" => 1, 
                                                        "isVisible" => 1,
                                                        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                                                                    ["name" => "Regular"],
                                                                                                                                                                    ["name" => "Halfhearted"]]]]]])
                                            ]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user2, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster3]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $cluster1]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $cluster2]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $cluster3]);

        //When
        $this->profiling->deleteClusterRoles(null);

        //Then
        $newProfiling = Core::$systemDB->select("role", ["name" => "Profiling"]);
        $newCluster1 = Core::$systemDB->select("role", ["name" => "Achiever"]);
        $newCluster2 = Core::$systemDB->select("role", ["name" => "Regular"]);
        $newCluster3 = Core::$systemDB->select("role", ["name" => "Halfhearted"]);

        $expectedProfiling = array("landingPage" => "", "id" => $profiling, "course" => $courseId, "name" => "Profiling");
        $expectedCluster1 = array("landingPage" => "", "id" => $cluster1, "course" => $courseId, "name" => "Achiever");
        $expectedCluster2 = array("landingPage" => "", "id" => $cluster2, "course" => $courseId, "name" => "Regular");
        $expectedCluster3 = array("landingPage" => "", "id" => $cluster3, "course" => $courseId, "name" => "Halfhearted");

        $this->assertEquals($expectedProfiling, $newProfiling);
        $this->assertEquals($expectedCluster1, $newCluster1);
        $this->assertEquals($expectedCluster2, $newCluster2);
        $this->assertEquals($expectedCluster3, $newCluster3);

        $rolesCourse1 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $this->assertCount(6, $rolesCourse1);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        
        $expectedAchieverRole = new stdClass;
        $expectedAchieverRole->name = 'Achiever';

        $expectedRegularRole = new stdClass;
        $expectedRegularRole->name = 'Regular';

        $expectedHalfheartedRole = new stdClass;
        $expectedHalfheartedRole->name = 'Halfhearted';

        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedProfilingRole->children = array($expectedAchieverRole, $expectedRegularRole, $expectedHalfheartedRole);

        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);

        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);
    }

    public function testDeleteClusterRolesTwoCourses(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", 
                                                        "short" => "MCP", 
                                                        "year" => "2019-2020", 
                                                        "color" => "#79bf43", 
                                                        "isActive" => 1, 
                                                        "isVisible" => 1,
                                                        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                                                                    ["name" => "Regular"],
                                                                                                                                                                    ["name" => "Halfhearted"]]]]]])
                                            ]);

        $course2Id = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", 
                                                        "short" => "FCS", 
                                                        "year" => "2020-2021", 
                                                        "color" => "#329da8", 
                                                        "isActive" => 1, 
                                                        "isVisible" => 1,
                                                        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                                                                    ["name" => "Regular"],
                                                                                                                                                                    ["name" => "Halfhearted"]]]]]])
                                                        ]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Simão Patrício", "email" => "simpat98@gmail.com", "studentNumber" => "97046", "nickname" => "", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 0]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);
        
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $course2Id]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);

        $studentCopy = Core::$systemDB->insert("role", ["course" => $course2Id, "name" => "Student"]);
        $profilingCopy = Core::$systemDB->insert("role", ["course" => $course2Id, "name" => "Profiling"]);
        $cluster1Copy = Core::$systemDB->insert("role", ["course" => $course2Id, "name" => "Achiever"]);
        $cluster2Copy = Core::$systemDB->insert("role", ["course" => $course2Id, "name" => "Regular"]);
        $cluster3Copy = Core::$systemDB->insert("role", ["course" => $course2Id, "name" => "Halfhearted"]);
        
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user2, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster3]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $cluster1]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $cluster2]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $cluster3]);

        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $course2Id, "role" => $studentCopy]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $course2Id, "role" => $cluster1Copy]);


        //When
        $this->profiling->deleteClusterRoles($course2Id);

        //Then
        $newProfiling = Core::$systemDB->select("role", ["name" => "Profiling"]);
        $newCluster1 = Core::$systemDB->select("role", ["name" => "Achiever"]);
        $newCluster2 = Core::$systemDB->select("role", ["name" => "Regular"]);
        $newCluster3 = Core::$systemDB->select("role", ["name" => "Halfhearted"]);

        $expectedProfiling = array("landingPage" => "", "id" => $profiling, "course" => $courseId, "name" => "Profiling");
        $expectedCluster1 = array("landingPage" => "", "id" => $cluster1, "course" => $courseId, "name" => "Achiever");
        $expectedCluster2 = array("landingPage" => "", "id" => $cluster2, "course" => $courseId, "name" => "Regular");
        $expectedCluster3 = array("landingPage" => "", "id" => $cluster3, "course" => $courseId, "name" => "Halfhearted");

        $this->assertEquals($expectedProfiling, $newProfiling);
        $this->assertEquals($expectedCluster1, $newCluster1);
        $this->assertEquals($expectedCluster2, $newCluster2);
        $this->assertEquals($expectedCluster3, $newCluster3);

        $rolesCourse1 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId]);
        $this->assertCount(6, $rolesCourse1);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        
        $expectedAchieverRole = new stdClass;
        $expectedAchieverRole->name = 'Achiever';

        $expectedRegularRole = new stdClass;
        $expectedRegularRole->name = 'Regular';

        $expectedHalfheartedRole = new stdClass;
        $expectedHalfheartedRole->name = 'Halfhearted';

        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedProfilingRole->children = array($expectedAchieverRole, $expectedRegularRole, $expectedHalfheartedRole);

        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);

        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);
                    
        $newProfilingCopy = Core::$systemDB->select("role", ["course" => $course2Id, "name" => "Profiling"]);
        $newCluster1Copy = Core::$systemDB->select("role", ["course" => $course2Id, "name" => "Achiever"]);
        $newCluster2Copy = Core::$systemDB->select("role", ["course" => $course2Id, "name" => "Regular"]);
        $newCluster3Copy = Core::$systemDB->select("role", ["course" => $course2Id, "name" => "Halfhearted"]);

        $this->assertEmpty($newProfilingCopy);
        $this->assertEmpty($newCluster1Copy);
        $this->assertEmpty($newCluster2Copy);
        $this->assertEmpty($newCluster3Copy);

        $rolesCourse2 = Core::$systemDB->selectMultiple("user_role", ["course" => $course2Id]);
        $this->assertCount(1, $rolesCourse2);

        $newHierarchy2 = json_decode(Core::$systemDB->select("course", ["id" => $course2Id], "roleHierarchy"));

        $expectedStudentRole2 = new stdClass;
        $expectedStudentRole2->name = 'Student';
        $expectedStudentRole2->children = array();
        $expectedHierarchy2 = array($expectedStudentRole2);
        $this->assertEquals($expectedHierarchy2, $newHierarchy2);
    }

    /**
     * @depends testRemoveClusterRolesSuccess
     */
    public function testProcessClusterRolesEmptyClusters(){

        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling"]]]])
            ]
        );

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "1101036", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);

        //When
        $this->profiling->processClusterRoles($courseId, array());

        //Then
        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));

        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);

        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);
        $expectedRolesUser1 = array(array("id" => $user1, "course" => $courseId, "role" => $student));
        $this->assertEquals($expectedRolesUser1, $rolesUser1);

    }

    /**
     * @depends testRemoveClusterRolesSuccess
     */
    public function testProcessClusterRolesNullClusters(){

        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling"]]]])
            ]
        );

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "1101036", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);

        //When
        $this->profiling->processClusterRoles($courseId, null);

        //Then
        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));

        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);

        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);
        $expectedRolesUser1 = array(array("id" => $user1, "course" => $courseId, "role" => $student));
        $this->assertEquals($expectedRolesUser1, $rolesUser1);

    }

    /**
     * @depends testRemoveClusterRolesSuccess
     */
    public function testProcessClusterRolesDefaultNamesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling"]]]])
            ]
        );

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

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

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $clusters = array($user1 => "Underachiever", $user2 => "Regular", $user3 => "Regular", $user4 => "Halfhearted", $user5 => "Achiever");
        
        //When
        $this->profiling->processClusterRoles($courseId, $clusters);

        //Then
        $newCluster1 = Core::$systemDB->select("role", ["name" => "Achiever"]);
        $newCluster2 = Core::$systemDB->select("role", ["name" => "Regular"]);
        $newCluster3 = Core::$systemDB->select("role", ["name" => "Halfhearted"]);
        $newCluster4 = Core::$systemDB->select("role", ["name" => "Underachiever"]);

        $expectedCluster1 = array("landingPage" => "", "id" => $newCluster1["id"], "course" => $courseId, "name" => "Achiever");
        $expectedCluster2 = array("landingPage" => "", "id" => $newCluster2["id"], "course" => $courseId, "name" => "Regular");
        $expectedCluster3 = array("landingPage" => "", "id" => $newCluster3["id"], "course" => $courseId, "name" => "Halfhearted");
        $expectedCluster4 = array("landingPage" => "", "id" => $newCluster4["id"], "course" => $courseId, "name" => "Underachiever");

        $this->assertEquals($expectedCluster1, $newCluster1);
        $this->assertEquals($expectedCluster2, $newCluster2);
        $this->assertEquals($expectedCluster3, $newCluster3);
        $this->assertEquals($expectedCluster4, $newCluster4);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));

        $expectedAchieverRole = new stdClass;
        $expectedAchieverRole->name = 'Achiever';

        $expectedRegularRole = new stdClass;
        $expectedRegularRole->name = 'Regular';

        $expectedHalfheartedRole = new stdClass;
        $expectedHalfheartedRole->name = 'Halfhearted';

        $expectedUnderachieverRole = new stdClass;
        $expectedUnderachieverRole->name = 'Underachiever';
        
        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedProfilingRole->children = array($expectedAchieverRole, $expectedRegularRole, $expectedHalfheartedRole, $expectedUnderachieverRole);

        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);
        
        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);        
        $rolesUser2 = Core::$systemDB->selectMultiple("user_role", ["id" => $user2]);
        $rolesUser3 = Core::$systemDB->selectMultiple("user_role", ["id" => $user3]);
        $rolesUser4 = Core::$systemDB->selectMultiple("user_role", ["id" => $user4]);
        $rolesUser5 = Core::$systemDB->selectMultiple("user_role", ["id" => $user5]);
        
        $expectedRolesUser1 = array(array("id" => $user1, "course" => $courseId, "role" => $student), array("id" => $user1, "course" => $courseId, "role" => $newCluster4["id"]));
        $expectedRolesUser2 = array(array("id" => $user2, "course" => $courseId, "role" => $student), array("id" => $user2, "course" => $courseId, "role" => $newCluster2["id"]));
        $expectedRolesUser3 = array(array("id" => $user3, "course" => $courseId, "role" => $student), array("id" => $user3, "course" => $courseId, "role" => $newCluster2["id"]));
        $expectedRolesUser4 = array(array("id" => $user4, "course" => $courseId, "role" => $student), array("id" => $user4, "course" => $courseId, "role" => $newCluster3["id"]));
        $expectedRolesUser5 = array(array("id" => $user5, "course" => $courseId, "role" => $student), array("id" => $user5, "course" => $courseId, "role" => $newCluster1["id"]));
        
        $this->assertEquals($expectedRolesUser1, $rolesUser1);
        $this->assertEquals($expectedRolesUser2, $rolesUser2);
        $this->assertEquals($expectedRolesUser3, $rolesUser3);
        $this->assertEquals($expectedRolesUser4, $rolesUser4);
        $this->assertEquals($expectedRolesUser5, $rolesUser5);
        
        $profileUser1 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);
        $profileUser4 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user4]);
        $profileUser5 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user5]);

        unset($profileUser1["date"]);        
        unset($profileUser2["date"]);
        unset($profileUser3["date"]);
        unset($profileUser4["date"]);
        unset($profileUser5["date"]);

        $expectedProfileUser1 = array("user" => $user1, "course" => $courseId, "cluster" => $newCluster4["id"]);
        $expectedProfileUser2 = array("user" => $user2, "course" => $courseId, "cluster" => $newCluster2["id"]);
        $expectedProfileUser3 = array("user" => $user3, "course" => $courseId, "cluster" => $newCluster2["id"]);
        $expectedProfileUser4 = array("user" => $user4, "course" => $courseId, "cluster" => $newCluster3["id"]);
        $expectedProfileUser5 = array("user" => $user5, "course" => $courseId, "cluster" => $newCluster1["id"]);
        
        $this->assertEquals($expectedProfileUser1, $profileUser1);
        $this->assertEquals($expectedProfileUser2, $profileUser2);
        $this->assertEquals($expectedProfileUser3, $profileUser3);
        $this->assertEquals($expectedProfileUser4, $profileUser4);
        $this->assertEquals($expectedProfileUser5, $profileUser5);

        $dates = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["course" => $courseId], "distinct date");
        $this->assertCount(1, $dates);

    }

    /**
     * @depends testRemoveClusterRolesSuccess
     */
    public function testProcessClusterRolesBaseNamesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                            ["name" => "Regular"], 
                                                                                                                            ["name" => "Halfhearted"],
                                                                                                                            ["name" => "Underachiever"]]]]]])
            ]
        );

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

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

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $clusters = array($user1 => "Underachiever", $user2 => "Regular", $user3 => "Regular", $user4 => "Halfhearted", $user5 => "Achiever");
        
        //When
        $this->profiling->processClusterRoles($courseId, $clusters);

        //Then
        $newCluster1 = Core::$systemDB->select("role", ["name" => "Achiever"]);
        $newCluster2 = Core::$systemDB->select("role", ["name" => "Regular"]);
        $newCluster3 = Core::$systemDB->select("role", ["name" => "Halfhearted"]);
        $newCluster4 = Core::$systemDB->select("role", ["name" => "Underachiever"]);

        $expectedCluster1 = array("landingPage" => "", "id" => $cluster1, "course" => $courseId, "name" => "Achiever");
        $expectedCluster2 = array("landingPage" => "", "id" => $cluster2, "course" => $courseId, "name" => "Regular");
        $expectedCluster3 = array("landingPage" => "", "id" => $cluster3, "course" => $courseId, "name" => "Halfhearted");
        $expectedCluster4 = array("landingPage" => "", "id" => $cluster4, "course" => $courseId, "name" => "Underachiever");

        $this->assertEquals($expectedCluster1, $newCluster1);
        $this->assertEquals($expectedCluster2, $newCluster2);
        $this->assertEquals($expectedCluster3, $newCluster3);
        $this->assertEquals($expectedCluster4, $newCluster4);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));

        $expectedAchieverRole = new stdClass;
        $expectedAchieverRole->name = 'Achiever';

        $expectedRegularRole = new stdClass;
        $expectedRegularRole->name = 'Regular';

        $expectedHalfheartedRole = new stdClass;
        $expectedHalfheartedRole->name = 'Halfhearted';

        $expectedUnderachieverRole = new stdClass;
        $expectedUnderachieverRole->name = 'Underachiever';
        
        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedProfilingRole->children = array($expectedAchieverRole, $expectedRegularRole, $expectedHalfheartedRole, $expectedUnderachieverRole);

        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);

        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);        
        $rolesUser2 = Core::$systemDB->selectMultiple("user_role", ["id" => $user2]);
        $rolesUser3 = Core::$systemDB->selectMultiple("user_role", ["id" => $user3]);
        $rolesUser4 = Core::$systemDB->selectMultiple("user_role", ["id" => $user4]);
        $rolesUser5 = Core::$systemDB->selectMultiple("user_role", ["id" => $user5]);
        
        $expectedRolesUser1 = array(array("id" => $user1, "course" => $courseId, "role" => $student), array("id" => $user1, "course" => $courseId, "role" => $cluster4));
        $expectedRolesUser2 = array(array("id" => $user2, "course" => $courseId, "role" => $student), array("id" => $user2, "course" => $courseId, "role" => $cluster2));
        $expectedRolesUser3 = array(array("id" => $user3, "course" => $courseId, "role" => $student), array("id" => $user3, "course" => $courseId, "role" => $cluster2));
        $expectedRolesUser4 = array(array("id" => $user4, "course" => $courseId, "role" => $student), array("id" => $user4, "course" => $courseId, "role" => $cluster3));
        $expectedRolesUser5 = array(array("id" => $user5, "course" => $courseId, "role" => $student), array("id" => $user5, "course" => $courseId, "role" => $cluster1));
        
        $this->assertEquals($expectedRolesUser1, $rolesUser1);
        $this->assertEquals($expectedRolesUser2, $rolesUser2);
        $this->assertEquals($expectedRolesUser3, $rolesUser3);
        $this->assertEquals($expectedRolesUser4, $rolesUser4);
        $this->assertEquals($expectedRolesUser5, $rolesUser5);
        
        $profileUser1 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);
        $profileUser4 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user4]);
        $profileUser5 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user5]);

        unset($profileUser1["date"]);        
        unset($profileUser2["date"]);
        unset($profileUser3["date"]);
        unset($profileUser4["date"]);
        unset($profileUser5["date"]);

        $expectedProfileUser1 = array("user" => $user1, "course" => $courseId, "cluster" => $cluster4);
        $expectedProfileUser2 = array("user" => $user2, "course" => $courseId, "cluster" => $cluster2);
        $expectedProfileUser3 = array("user" => $user3, "course" => $courseId, "cluster" => $cluster2);
        $expectedProfileUser4 = array("user" => $user4, "course" => $courseId, "cluster" => $cluster3);
        $expectedProfileUser5 = array("user" => $user5, "course" => $courseId, "cluster" => $cluster1);
        
        $this->assertEquals($expectedProfileUser1, $profileUser1);
        $this->assertEquals($expectedProfileUser2, $profileUser2);
        $this->assertEquals($expectedProfileUser3, $profileUser3);
        $this->assertEquals($expectedProfileUser4, $profileUser4);
        $this->assertEquals($expectedProfileUser5, $profileUser5);

        $dates = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["course" => $courseId], "distinct date");
        $this->assertCount(1, $dates);

    }

    /**
     * @depends testRemoveClusterRolesSuccess
     */
    public function testProcessClusterRolesPersonalizedNamesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Red"], 
                                                                                                                            ["name" => "Blue"], 
                                                                                                                            ["name" => "Green"]]]]]])
            ]
        );

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Red"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Blue"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Green"]);

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

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $clusters = array($user1 => "Blue", $user2 => "Green", $user3 => "Red", $user4 => "Red", $user5 => "Blue");
        
        //When
        $this->profiling->processClusterRoles($courseId, $clusters);

        //Then
        $newCluster1 = Core::$systemDB->select("role", ["name" => "Red"]);
        $newCluster2 = Core::$systemDB->select("role", ["name" => "Blue"]);
        $newCluster3 = Core::$systemDB->select("role", ["name" => "Green"]);

        $expectedCluster1 = array("landingPage" => "", "id" => $cluster1, "course" => $courseId, "name" => "Red");
        $expectedCluster2 = array("landingPage" => "", "id" => $cluster2, "course" => $courseId, "name" => "Blue");
        $expectedCluster3 = array("landingPage" => "", "id" => $cluster3, "course" => $courseId, "name" => "Green");

        $this->assertEquals($expectedCluster1, $newCluster1);
        $this->assertEquals($expectedCluster2, $newCluster2);
        $this->assertEquals($expectedCluster3, $newCluster3);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));

        $expectedRole1 = new stdClass;
        $expectedRole1->name = 'Red';

        $expectedRole2 = new stdClass;
        $expectedRole2->name = 'Blue';

        $expectedRole3 = new stdClass;
        $expectedRole3->name = 'Green';

        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedProfilingRole->children = array($expectedRole1, $expectedRole2, $expectedRole3);

        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);
        
        $rolesUser1 = Core::$systemDB->selectMultiple("user_role", ["id" => $user1]);        
        $rolesUser2 = Core::$systemDB->selectMultiple("user_role", ["id" => $user2]);
        $rolesUser3 = Core::$systemDB->selectMultiple("user_role", ["id" => $user3]);
        $rolesUser4 = Core::$systemDB->selectMultiple("user_role", ["id" => $user4]);
        $rolesUser5 = Core::$systemDB->selectMultiple("user_role", ["id" => $user5]);
        
        $expectedRolesUser1 = array(array("id" => $user1, "course" => $courseId, "role" => $student), array("id" => $user1, "course" => $courseId, "role" => $cluster2));
        $expectedRolesUser2 = array(array("id" => $user2, "course" => $courseId, "role" => $student), array("id" => $user2, "course" => $courseId, "role" => $cluster3));
        $expectedRolesUser3 = array(array("id" => $user3, "course" => $courseId, "role" => $student), array("id" => $user3, "course" => $courseId, "role" => $cluster1));
        $expectedRolesUser4 = array(array("id" => $user4, "course" => $courseId, "role" => $student), array("id" => $user4, "course" => $courseId, "role" => $cluster1));
        $expectedRolesUser5 = array(array("id" => $user5, "course" => $courseId, "role" => $student), array("id" => $user5, "course" => $courseId, "role" => $cluster2));
        
        $this->assertEquals($expectedRolesUser1, $rolesUser1);
        $this->assertEquals($expectedRolesUser2, $rolesUser2);
        $this->assertEquals($expectedRolesUser3, $rolesUser3);
        $this->assertEquals($expectedRolesUser4, $rolesUser4);
        $this->assertEquals($expectedRolesUser5, $rolesUser5);
        
        $profileUser1 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);
        $profileUser4 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user4]);
        $profileUser5 = Core::$systemDB->select(Profiling::TABLE_USER_PROFILE, ["user" => $user5]);

        unset($profileUser1["date"]);        
        unset($profileUser2["date"]);
        unset($profileUser3["date"]);
        unset($profileUser4["date"]);
        unset($profileUser5["date"]);

        $expectedProfileUser1 = array("user" => $user1, "course" => $courseId, "cluster" => $cluster2);
        $expectedProfileUser2 = array("user" => $user2, "course" => $courseId, "cluster" => $cluster3);
        $expectedProfileUser3 = array("user" => $user3, "course" => $courseId, "cluster" => $cluster1);
        $expectedProfileUser4 = array("user" => $user4, "course" => $courseId, "cluster" => $cluster1);
        $expectedProfileUser5 = array("user" => $user5, "course" => $courseId, "cluster" => $cluster2);
        
        $this->assertEquals($expectedProfileUser1, $profileUser1);
        $this->assertEquals($expectedProfileUser2, $profileUser2);
        $this->assertEquals($expectedProfileUser3, $profileUser3);
        $this->assertEquals($expectedProfileUser4, $profileUser4);
        $this->assertEquals($expectedProfileUser5, $profileUser5);

        $dates = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["course" => $courseId], "distinct date");
        $this->assertCount(1, $dates);
    }

    /**
     * @depends testGetClusterNamesSuccess
     */
    public function testGetClusterEvolutionSuccess(){
            
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                            ["name" => "Regular"], 
                                                                                                                            ["name" => "Halfhearted"],
                                                                                                                            ["name" => "Underachiever"]]]]]])
            ]
        );

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        $time1 = "2021-06-20 14:30:00";
        $time2 = "2021-08-20 18:10:42";

        $history = array(
            array ('id' => $user1, 'name' => "Noël Miller", $time1 => "Achiever", $time2 => "Achiever"),
            array ('id' => $user2, 'name' => "Ana Gonçalves", $time1 => "Regular", $time2 => "Achiever"),
            array ('id' => $user3, 'name' => "Marcus Notø", $time1 => "Halfhearted", $time2 => "Halfhearted"),
            array ('id' => $user4, 'name' => "Simão Patrício", $time1 => "None", $time2 => "Regular"),
            array ('id' => $user5, 'name' => "Sabri M'Barki", $time1 => "Underachiever", $time2 => "Underachiever")
        );

        $days = array($time1, $time2);

        //When
        $result = $this->profiling->getClusterEvolution($courseId, $history, $days);

        //Then
        $expectedNodes = array(
            array("id" => "Achiever0", "name" => "Achiever", "color" => "#7cb5ec"),
            array("id" => "Achiever1", "name" => "Achiever", "color" => "#7cb5ec"),
            array("id" => "Regular0", "name" => "Regular", "color" => "#90ed7d"),
            array("id" => "Regular1", "name" => "Regular", "color" => "#90ed7d"),
            array("id" => "Halfhearted0", "name" => "Halfhearted", "color" => "#f7a35c"),
            array("id" => "Halfhearted1", "name" => "Halfhearted", "color" => "#f7a35c"),
            array("id" => "Underachiever0", "name" => "Underachiever", "color" => "#8085e9"),
            array("id" => "Underachiever1", "name" => "Underachiever", "color" => "#8085e9"),
            array("id" => "None0", "name" => "None", "color" => "#949494"),
            array("id" => "None1", "name" => "None", "color" => "#949494"),
            array("id" => "None2", "name" => "None", "color" => "#949494")
        );
        $this->assertEquals($expectedNodes, $result[0]);

        $expectedData = array(
            array("Achiever0", "Achiever1", 1), array("Regular0", "Achiever1", 1), array("Halfhearted0", "Halfhearted1", 1), array("Underachiever0", "Underachiever1", 1), array("None0", "Regular1", 1)
        );
        $this->assertEquals($expectedData, $result[1]);
    }

    /**
     * @depends testGetClusterNamesSuccess
     */
    public function testGetClusterEvolutionNoRecords(){
            
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                            ["name" => "Regular"], 
                                                                                                                            ["name" => "Halfhearted"],
                                                                                                                            ["name" => "Underachiever"]]]]]])
            ]
        );

        //When
        $result = $this->profiling->getClusterEvolution($courseId, [], []);

        //Then
        $expectedNodes = array(
            array(
                "id" => "None0",
                "name" => "None",
                "color" => "#949494"
            )
        );
        $expectedResult = array($expectedNodes, array());
        $this->assertEquals($expectedResult, $result); 
    }

    /**
     * @depends testGetClusterNamesNoProfilingRole
     */
    public function testGetClusterEvolutionNoProfilingChildren(){
            
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling"]]]])
            ]
        );

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

        $time1 = "2021-06-20 14:30:00";
        $time2 = "2021-08-20 18:10:42";

        $history = array(
            array ('id' => $user1, 'name' => "Noël Miller", $time1 => "Achiever", $time2 => "Achiever"),
            array ('id' => $user2, 'name' => "Ana Gonçalves", $time1 => "Regular", $time2 => "Achiever"),
            array ('id' => $user3, 'name' => "Marcus Notø", $time1 => "Halfhearted", $time2 => "Halfhearted"),
            array ('id' => $user4, 'name' => "Simão Patrício", $time1 => "None", $time2 => "Regular"),
            array ('id' => $user5, 'name' => "Sabri M'Barki", $time1 => "Underachiever", $time2 => "Underachiever")
        );

        $days = array($time1, $time2);

        //When
        $result = $this->profiling->getClusterEvolution($courseId, $history, $days);

        //Then
        $expectedNodes = array(
            array("id" => "Achiever0", "name" => "Achiever", "color" => "#7cb5ec"),
            array("id" => "Achiever1", "name" => "Achiever", "color" => "#7cb5ec"),
            array("id" => "Regular0", "name" => "Regular", "color" => "#90ed7d"),
            array("id" => "Regular1", "name" => "Regular", "color" => "#90ed7d"),
            array("id" => "Halfhearted0", "name" => "Halfhearted", "color" => "#f7a35c"),
            array("id" => "Halfhearted1", "name" => "Halfhearted", "color" => "#f7a35c"),
            array("id" => "Underachiever0", "name" => "Underachiever", "color" => "#8085e9"),
            array("id" => "Underachiever1", "name" => "Underachiever", "color" => "#8085e9"),
            array("id" => "None0", "name" => "None", "color" => "#949494"),
            array("id" => "None1", "name" => "None", "color" => "#949494"),
            array("id" => "None2", "name" => "None", "color" => "#949494")
        );
        $this->assertEquals($expectedNodes, $result[0]);

        $expectedData = array(
            array("Achiever0", "Achiever1", 1), array("Regular0", "Achiever1", 1), array("Halfhearted0", "Halfhearted1", 1), array("Underachiever0", "Underachiever1", 1), array("None0", "Regular1", 1)
        );
        $this->assertEquals($expectedData, $result[1]);
    }

    /**
     * @depends testGetClusterNamesInexistingCourse
     */
    public function testGetClusterEvolutionInexistingCourse(){
            
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => []]]]])
            ]
        );

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

        $time1 = "2021-06-20 14:30:00";
        $time2 = "2021-08-20 18:10:42";

        $history = array(
            array ('id' => $user1, 'name' => "Noël Miller", $time1 => "Achiever", $time2 => "Achiever"),
            array ('id' => $user2, 'name' => "Ana Gonçalves", $time1 => "Regular", $time2 => "Achiever"),
            array ('id' => $user3, 'name' => "Marcus Notø", $time1 => "Halfhearted", $time2 => "Halfhearted"),
            array ('id' => $user4, 'name' => "Simão Patrício", $time1 => "None", $time2 => "Regular"),
            array ('id' => $user5, 'name' => "Sabri M'Barki", $time1 => "Underachiever", $time2 => "Underachiever")
        );

        $days = array($time1, $time2);

        //When
        $result = $this->profiling->getClusterEvolution($courseId + 2, $history, $days);

        //Then
        $expectedNodes = array(
            array(
                "id" => "None0",
                "name" => "None",
                "color" => "#949494"
            ),
            array(
                "id" => "None1",
                "name" => "None",
                "color" => "#949494"
            ),
            array(
                "id" => "None2",
                "name" => "None",
                "color" => "#949494"
            )
        );
        $this->assertEquals($expectedNodes, $result[0]);

        $expectedData = array(
            array("Achiever0", "Achiever1", 1), array("Regular0", "Achiever1", 1), array("Halfhearted0", "Halfhearted1", 1), array("Underachiever0", "Underachiever1", 1), array("None0", "Regular1", 1)
        );
        $this->assertEquals($expectedData, $result[1]);
    }

        /**
     * @depends testGetClusterNamesNullCourse
     */
    public function testGetClusterEvolutionNullCourse(){
            
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => []]]]])
            ]
        );

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

        $time1 = "2021-06-20 14:30:00";
        $time2 = "2021-08-20 18:10:42";

        $history = array(
            array ('id' => $user1, 'name' => "Noël Miller", $time1 => "Achiever", $time2 => "Achiever"),
            array ('id' => $user2, 'name' => "Ana Gonçalves", $time1 => "Regular", $time2 => "Achiever"),
            array ('id' => $user3, 'name' => "Marcus Notø", $time1 => "Halfhearted", $time2 => "Halfhearted"),
            array ('id' => $user4, 'name' => "Simão Patrício", $time1 => "None", $time2 => "Regular"),
            array ('id' => $user5, 'name' => "Sabri M'Barki", $time1 => "Underachiever", $time2 => "Underachiever")
        );

        $days = array($time1, $time2);

        //When
        $result = $this->profiling->getClusterEvolution(null, $history, $days);

        //Then
        $expectedNodes = array(
            array(
                "id" => "None0",
                "name" => "None",
                "color" => "#949494"
            ),
            array(
                "id" => "None1",
                "name" => "None",
                "color" => "#949494"
            ),
            array(
                "id" => "None2",
                "name" => "None",
                "color" => "#949494"
            )
        );
        $this->assertEquals($expectedNodes, $result[0]);

        $expectedData = array(
            array("Achiever0", "Achiever1", 1), array("Regular0", "Achiever1", 1), array("Halfhearted0", "Halfhearted1", 1), array("Underachiever0", "Underachiever1", 1), array("None0", "Regular1", 1)
        );
        $this->assertEquals($expectedData, $result[1]);
    }


    /**
     * @depends testGetClusterNamesNoProfilingRole
     */
    public function testGetClusterEvolutionNoRoles(){
            
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => []]]]])
            ]
        );

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);

        $time1 = "2021-06-20 14:30:00";
        $time2 = "2021-08-20 18:10:42";

        $history = array(
            array ('id' => $user1, 'name' => "Noël Miller", $time1 => "Achiever", $time2 => "Achiever"),
            array ('id' => $user2, 'name' => "Ana Gonçalves", $time1 => "Regular", $time2 => "Achiever"),
            array ('id' => $user3, 'name' => "Marcus Notø", $time1 => "Halfhearted", $time2 => "Halfhearted"),
            array ('id' => $user4, 'name' => "Simão Patrício", $time1 => "None", $time2 => "Regular"),
            array ('id' => $user5, 'name' => "Sabri M'Barki", $time1 => "Underachiever", $time2 => "Underachiever")
        );

        $days = array($time1, $time2);

        //When
        $result = $this->profiling->getClusterEvolution($courseId, $history, $days);

        //Then
        $expectedNodes = array(
            array(
                "id" => "None0",
                "name" => "None",
                "color" => "#949494"
            ),
            array(
                "id" => "None1",
                "name" => "None",
                "color" => "#949494"
            ),
            array(
                "id" => "None2",
                "name" => "None",
                "color" => "#949494"
            )
        );
        $this->assertEquals($expectedNodes, $result[0]);

        $expectedData = array(
            array("Achiever0", "Achiever1", 1), array("Regular0", "Achiever1", 1), array("Halfhearted0", "Halfhearted1", 1), array("Underachiever0", "Underachiever1", 1), array("None0", "Regular1", 1)
        );
        $this->assertEquals($expectedData, $result[1]);
    }

    public function testGetClusterHistorySuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                            ["name" => "Regular"], 
                                                                                                                            ["name" => "Halfhearted"],
                                                                                                                            ["name" => "Underachiever"]]]]]])
            ]
        );

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $time1 = "2021-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user2, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user3, "course" => $courseId, "cluster" => $cluster3]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user5, "course" => $courseId, "cluster" => $cluster4]);

        $time2 = "2021-08-20 18:10:42";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user2, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user3, "course" => $courseId, "cluster" => $cluster3]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user5, "course" => $courseId, "cluster" => $cluster4]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user4, "course" => $courseId, "cluster" => $cluster2]);
        
        //When
        $result = $this->profiling->getClusterHistory($courseId);

        //Then
        $expectedDates = array($time1, $time2);
        $this->assertEquals($expectedDates, $result[0]);

        $expectedHistory = array(
            array ('id' => $user1, 'name' => "Noël Miller", $time1 => "Achiever", $time2 => "Achiever"),
            array ('id' => $user2, 'name' => "Ana Gonçalves", $time1 => "Regular", $time2 => "Achiever"),
            array ('id' => $user3, 'name' => "Marcus Notø", $time1 => "Halfhearted", $time2 => "Halfhearted"),
            array ('id' => $user4, 'name' => "Simão Patrício", $time1 => "None", $time2 => "Regular"),
            array ('id' => $user5, 'name' => "Sabri M'Barki", $time1 => "Underachiever", $time2 => "Underachiever")
        );
        $this->assertEquals($expectedHistory, $result[1]);

    }

    public function testGetClusterHistoryTwoCoursesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                            ["name" => "Regular"], 
                                                                                                                            ["name" => "Halfhearted"],
                                                                                                                            ["name" => "Underachiever"]]]]]])
            ]
        );

        $courseId2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", 
        "short" => "FCS", 
        "year" => "2020-2021", 
        "color" => "#329da8", 
        "isActive" => 1, 
        "isVisible" => 1,
        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                    ["name" => "Regular"],
                                                                                                                    ["name" => "Halfhearted"]]]]]])
        ]);

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

        Core::$systemDB->insert("course_user", ["id" => $user4, "course" => $courseId2]);
        Core::$systemDB->insert("course_user", ["id" => $user5, "course" => $courseId2]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        $studentCopy = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profilingCopy = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1Copy = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2Copy = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3Copy = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId2, "role" => $studentCopy]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId2, "role" => $studentCopy]);

        $time1 = "2021-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user2, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user3, "course" => $courseId, "cluster" => $cluster3]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user5, "course" => $courseId, "cluster" => $cluster4]);

        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user4, "course" => $courseId2, "cluster" => $cluster1Copy]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user5, "course" => $courseId2, "cluster" => $cluster3Copy]);

        $time2 = "2021-08-19 18:10:42";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user2, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user3, "course" => $courseId, "cluster" => $cluster3]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user5, "course" => $courseId, "cluster" => $cluster4]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user4, "course" => $courseId, "cluster" => $cluster2]);
        
        $time3 = "2021-08-20 19:00:00";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time3, "user" => $user4, "course" => $courseId2, "cluster" => $cluster1Copy]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time3, "user" => $user5, "course" => $courseId2, "cluster" => $cluster2Copy]);

        //When
        $result = $this->profiling->getClusterHistory($courseId);

        //Then
        $expectedDates = array($time1, $time2);
        $this->assertEquals($expectedDates, $result[0]);

        $expectedHistory = array(
            array ('id' => $user1, 'name' => "Noël Miller", $time1 => "Achiever", $time2 => "Achiever"),
            array ('id' => $user2, 'name' => "Ana Gonçalves", $time1 => "Regular", $time2 => "Achiever"),
            array ('id' => $user3, 'name' => "Marcus Notø", $time1 => "Halfhearted", $time2 => "Halfhearted"),
            array ('id' => $user4, 'name' => "Simão Patrício", $time1 => "None", $time2 => "Regular"),
            array ('id' => $user5, 'name' => "Sabri M'Barki", $time1 => "Underachiever", $time2 => "Underachiever")
        );
        $this->assertEquals($expectedHistory, $result[1]);

    }

    public function testGetClusterHistoryEmptyUserProfilesNoUsersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                            ["name" => "Regular"], 
                                                                                                                            ["name" => "Halfhearted"],
                                                                                                                            ["name" => "Underachiever"]]]]]])
            ]
        );

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        //When
        $result = $this->profiling->getClusterHistory($courseId);

        //Then
        $this->assertEmpty($result[0]);
        $this->assertEmpty($result[1]);

    }

    public function testGetClusterHistoryEmptyUserProfilesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert(
            "course", [
                "name" => "Multimedia Content Production", 
                "short" => "MCP", 
                "year" => "2019-2020", 
                "color" => "#79bf43", 
                "isActive" => 1, 
                "isVisible" => 1,
                "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Achiever"], 
                                                                                                                            ["name" => "Regular"], 
                                                                                                                            ["name" => "Halfhearted"],
                                                                                                                            ["name" => "Underachiever"]]]]]])
            ]
        );

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Achiever"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Regular"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Halfhearted"]);
        $cluster4 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Underachiever"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        //When
        $result = $this->profiling->getClusterHistory($courseId);

        //Then
        $this->assertEmpty($result[0]);

        $expectedHistory = array(
            array ('id' => $user1,'name' => "Noël Miller", "Current" => "None"),
            array ('id' => $user2,'name' => "Ana Gonçalves", "Current" => "None"),
            array ('id' => $user3,'name' => "Marcus Notø", "Current" => "None"),
            array ('id' => $user4,'name' => "Simão Patrício", "Current" => "None"),
            array ('id' => $user5,'name' => "Sabri M'Barki", "Current" => "None")
        );
        $this->assertEquals($expectedHistory, $result[1]);

    }

    public function testGetClusterHistoryInvalidCourse(){

        //When
        $result = $this->profiling->getClusterHistory(3);

        //Then
        $this->assertEmpty($result[0]);
        $this->assertEmpty($result[1]);

    }

    public function testGetClusterHistoryNullCourse(){

        //When
        $result = $this->profiling->getClusterHistory(null);

        //Then
        $this->assertEmpty($result[0]);
        $this->assertEmpty($result[1]);

    }

    public function testCreateClusterListSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", 
        "short" => "FCS", 
        "year" => "2020-2021", 
        "color" => "#329da8", 
        "isActive" => 1, 
        "isVisible" => 1,
        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                    ["name" => "Spy"],
                                                                                                                    ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);
        
        $assignedClusters = [3,2,3,1,1];
        $names = [1 => "Hacker" , 2 => "Spy", 3 => "Sleuth"];

        //When
        $result = $this->profiling->createClusterList($courseId, $names, $assignedClusters);

        //Then
        $expectedResult = array(
            $user1 => array("name" => "Noël Miller", "cluster" => "Sleuth"),
            $user2 => array("name" => "Ana Gonçalves", "cluster" => "Spy"),
            $user3 => array("name" => "Marcus Notø", "cluster" => "Sleuth"),
            $user4 => array("name" => "Simão Patrício", "cluster" => "Hacker"),
            $user5 => array("name" => "Sabri M'Barki", "cluster" => "Hacker")
        );

        $this->assertEquals($expectedResult, $result);

    }

    /**
     * @dataProvider invalidClusterListProvider
     */
    public function testCreateClusterListInvalidVariables($names, $assignedClusters){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", 
        "short" => "FCS", 
        "year" => "2020-2021", 
        "color" => "#329da8", 
        "isActive" => 1, 
        "isVisible" => 1,
        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                    ["name" => "Spy"],
                                                                                                                    ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        //When
        $result = $this->profiling->createClusterList($courseId, $names, $assignedClusters);

        //Then
        $this->assertEmpty($result);

    }

    public function testCreateClusterListInexistingCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", 
        "short" => "FCS", 
        "year" => "2020-2021", 
        "color" => "#329da8", 
        "isActive" => 1, 
        "isVisible" => 1,
        "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                    ["name" => "Spy"],
                                                                                                                    ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $assignedClusters = [3,2,3,1,1];
        $names = [1 => "Hacker" , 2 => "Spy", 3 => "Sleuth"];

        //When
        $result = $this->profiling->createClusterList(222, $names, $assignedClusters);

        //Then
        $this->assertEmpty($result);

    }

    public function testExportItemsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $time1 = "2021-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user1, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user2, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user3, "course" => $courseId, "cluster" => $cluster3]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time1, "user" => $user5, "course" => $courseId, "cluster" => $cluster3]);

        $time2 = "2021-08-20 18:10:42";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user1, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user2, "course" => $courseId, "cluster" => $cluster1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user3, "course" => $courseId, "cluster" => $cluster3]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user5, "course" => $courseId, "cluster" => $cluster2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["date" => $time2, "user" => $user4, "course" => $courseId, "cluster" => $cluster1]);
        
        //When
        $result = $this->profiling->exportItems();

        //Then
        $this->assertEquals("Profiles - Forensics Cyber-Security", $result[0]);

        $expectedFile = "username;" . $time1 . ";" . $time2 . "\n";
        $expectedFile .= "ist112122;Hacker;Spy\n";
        $expectedFile .= "ist110001;Spy;Hacker\n";
        $expectedFile .= "ist11101036;Sleuth;Sleuth\n";
        $expectedFile .= "ist197046;;Hacker\n";
        $expectedFile .= "ist1100956;Sleuth;Spy\n";
        
        $this->assertEquals($expectedFile, $result[1]);
    }


    public function testExportItemsEmptyRecords(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        //When
        $result = $this->profiling->exportItems();

        //Then
        $this->assertEquals("Profiles - Forensics Cyber-Security", $result[0]);
        $expectedFile = "username\n";
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testExportItemsInvalidCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        //When
        $result = $this->profiling->exportItems();

        //Then
        $this->assertEquals("Profiles - ", $result[0]);
        $expectedFile = "username\n";
        $this->assertEquals($expectedFile, $result[1]);

    }

    public function testExportItemsNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        //When
        $result = $this->profiling->exportItems();

        //Then
        $this->assertEquals("Profiles - ", $result[0]);
        $expectedFile = "username\n";
        $this->assertEquals($expectedFile, $result[1]);

    }

    public function testImportItemsNoReplaceSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $time1 = "2020-06-20 14:30:00";
        $time2 = "2020-06-23 18:10:42";

        $file = "username;" . $time1 . ";" . $time2 . "\n";
        $file .= "ist112122;Hacker;Spy\n";
        $file .= "ist110001;Spy;Hacker\n";
        $file .= "ist11101036;Sleuth;Sleuth\n";
        $file .= "ist197046;;Hacker\n";
        $file .= "ist1100956;Sleuth;Spy\n";

        //When
        $result = $this->profiling->importItems($file, false);

        //Then
        $profileUser1 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);
        $profileUser4 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user4]);
        $profileUser5 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user5]);

        $expectedProfileUser1 = array(
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time1),
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster2, "date" => $time2),
        );
        $expectedProfileUser2 = array(
            array("user" => $user2, "course" => $courseId, "cluster" => $cluster2, "date" => $time1),
            array("user" => $user2, "course" => $courseId, "cluster" => $cluster1, "date" => $time2)
        );
        $expectedProfileUser3 = array(
            array("user" => $user3, "course" => $courseId, "cluster" => $cluster3, "date" => $time1),
            array("user" => $user3, "course" => $courseId, "cluster" => $cluster3, "date" => $time2)
        );
        $expectedProfileUser4 = array(
            array("user" => $user4, "course" => $courseId, "cluster" => $cluster1, "date" => $time2)
        );
        $expectedProfileUser5 = array(
            array("user" => $user5, "course" => $courseId, "cluster" => $cluster3, "date" => $time1),
            array("user" => $user5, "course" => $courseId, "cluster" => $cluster2, "date" => $time2)
        );
        
        $this->assertEquals($expectedProfileUser1, $profileUser1);
        $this->assertEquals($expectedProfileUser2, $profileUser2);
        $this->assertEquals($expectedProfileUser3, $profileUser3);
        $this->assertEquals($expectedProfileUser4, $profileUser4);
        $this->assertEquals($expectedProfileUser5, $profileUser5);

        $newRoles1 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user1]);
        $newRoles2 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user2]);
        $newRoles3 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user3]);
        $newRoles4 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user4]);
        $newRoles5 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user5]);

        $expectedRoleUser1 = array(
            array("id" => $user1, "course" => $courseId, "role" => $student),
            array("id" => $user1, "course" => $courseId, "role" => $cluster2),
        );
        $expectedRoleUser2 = array(
            array("id" => $user2, "course" => $courseId, "role" => $student),
            array("id" => $user2, "course" => $courseId, "role" => $cluster1)
        );
        $expectedRoleUser3 = array(
            array("id" => $user3, "course" => $courseId, "role" => $student),
            array("id" => $user3, "course" => $courseId, "role" => $cluster3)
        );
        $expectedRoleUser4 = array(
            array("id" => $user4, "course" => $courseId, "role" => $student),
            array("id" => $user4, "course" => $courseId, "role" => $cluster1)
        );
        $expectedRoleUser5 = array(
            array("id" => $user5, "course" => $courseId, "role" => $student),
            array("id" => $user5, "course" => $courseId, "role" => $cluster2)
        );

    }

    /**
     * @dataProvider invalidImportFileProvider
     */
    public function testImportItemsInvalidFile($file){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        //When
        $result = $this->profiling->importItems($file, false);

        //Then
        $profileUser1 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);
        $profileUser4 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user4]);
        $profileUser5 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user5]);
        
        $this->assertEmpty($profileUser1);
        $this->assertEmpty($profileUser2);
        $this->assertEmpty($profileUser3);
        $this->assertEmpty($profileUser4);
        $this->assertEmpty($profileUser5);

        $newRoles1 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user1]);
        $newRoles2 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user2]);
        $newRoles3 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user3]);
        $newRoles4 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user4]);
        $newRoles5 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user5]);

        $expectedRoleUser1 = array(array("id" => $user1, "course" => $courseId, "role" => $student));
        $expectedRoleUser2 = array(array("id" => $user2, "course" => $courseId, "role" => $student));
        $expectedRoleUser3 = array(array("id" => $user3, "course" => $courseId, "role" => $student));
        $expectedRoleUser4 = array(array("id" => $user4, "course" => $courseId, "role" => $student));
        $expectedRoleUser5 = array(array("id" => $user5, "course" => $courseId, "role" => $student));

    }

    public function testImportItemsInexistingUser(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);

        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        $time1 = "2020-06-20 14:30:00";
        $time2 = "2020-06-23 18:10:42";

        $file = "username;" . $time1 . ";" . $time2 . "\n";
        $file .= "ist112122;Hacker;Spy\n";
        $file .= "ist110001;Spy;Hacker\n";
        $file .= "ist11101036;Sleuth;Sleuth\n";
        $file .= "ist1100956;Sleuth;Spy\n";

        //When
        $result = $this->profiling->importItems($file, false);

        //Then
        $profiles = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["course" => $courseId]);
        $this->assertCount(6, $profiles);

        $profileUser1 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);

        $expectedProfileUser1 = array(
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time1),
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster2, "date" => $time2),
        );
        $expectedProfileUser2 = array(
            array("user" => $user2, "course" => $courseId, "cluster" => $cluster2, "date" => $time1),
            array("user" => $user2, "course" => $courseId, "cluster" => $cluster1, "date" => $time2)
        );
        $expectedProfileUser3 = array(
            array("user" => $user3, "course" => $courseId, "cluster" => $cluster3, "date" => $time1),
            array("user" => $user3, "course" => $courseId, "cluster" => $cluster3, "date" => $time2)
        );
        
        $this->assertEquals($expectedProfileUser1, $profileUser1);
        $this->assertEquals($expectedProfileUser2, $profileUser2);
        $this->assertEquals($expectedProfileUser3, $profileUser3);

        $newRoles1 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user1]);
        $newRoles2 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user2]);
        $newRoles3 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user3]);

        $expectedRoleUser1 = array(
            array("id" => $user1, "course" => $courseId, "role" => $student),
            array("id" => $user1, "course" => $courseId, "role" => $cluster2),
        );
        $expectedRoleUser2 = array(
            array("id" => $user2, "course" => $courseId, "role" => $student),
            array("id" => $user2, "course" => $courseId, "role" => $cluster1)
        );
        $expectedRoleUser3 = array(
            array("id" => $user3, "course" => $courseId, "role" => $student),
            array("id" => $user3, "course" => $courseId, "role" => $cluster3)
        );

    }

    public function testImportItemsInexistingRole(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"]]]]]])
        ]);

        $user1 = Core::$systemDB->insert("game_course_user", ["name" => "Noël Miller", "email" => "noel_m@gmail", "studentNumber" => "12122", "nickname" => "Noël Miller", "major" => "MEIC-A", "isAdmin" => 0, "isActive" => 1]);
        $user2 = Core::$systemDB->insert("game_course_user", ["name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "studentNumber" => "10001", "nickname" => "Ana G", "major" =>  "MEIC-A", "isAdmin" => 1, "isActive" => 0]);
        $user3 = Core::$systemDB->insert("game_course_user", ["name" => "Marcus Notø", "email" => "marcus.n.hansen@gmail", "studentNumber" => "1101036", "nickname" => "Marcus Notø", "major" =>  "MEEC", "isAdmin" => 1, "isActive" => 0]);

        Core::$systemDB->insert("auth", ["game_course_user_id" => $user1, "username" => "ist112122", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user2, "username" => "ist110001", "authentication_service" => "fenix"]);
        Core::$systemDB->insert("auth", ["game_course_user_id" => $user3, "username" => "ist11101036", "authentication_service" => "fenix"]);

        Core::$systemDB->insert("course_user", ["id" => $user1, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user2, "course" => $courseId]);
        Core::$systemDB->insert("course_user", ["id" => $user3, "course" => $courseId]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);

        $time1 = "2020-06-20 14:30:00";
        $time2 = "2020-06-23 18:10:42";

        $file = "username;" . $time1 . ";" . $time2 . "\n";
        $file .= "ist112122;Hacker;Spy\n";
        $file .= "ist110001;Sleuth;Hacker\n";
        $file .= "ist11101036;Spy;Sleuth\n";

        //When
        $result = $this->profiling->importItems($file, false);

        //Then
        $profiles = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["course" => $courseId]);
        $this->assertCount(4, $profiles);

        $profileUser1 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);

        $expectedProfileUser1 = array(
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time1),
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster2, "date" => $time2),
        );
        $expectedProfileUser2 = array(
            array("user" => $user2, "course" => $courseId, "cluster" => $cluster1, "date" => $time2)
        );
        $expectedProfileUser3 = array(
            array("user" => $user3, "course" => $courseId, "cluster" => $cluster2, "date" => $time1),
        );
        
        $this->assertEquals($expectedProfileUser1, $profileUser1);
        $this->assertEquals($expectedProfileUser2, $profileUser2);
        $this->assertEquals($expectedProfileUser3, $profileUser3);

        $newRoles1 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user1]);
        $newRoles2 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user2]);
        $newRoles3 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user3]);

        $expectedRoleUser1 = array(
            array("id" => $user1, "course" => $courseId, "role" => $student),
            array("id" => $user1, "course" => $courseId, "role" => $cluster2),
        );
        $expectedRoleUser2 = array(
            array("id" => $user2, "course" => $courseId, "role" => $student),
            array("id" => $user2, "course" => $courseId, "role" => $cluster1)
        );
        $expectedRoleUser3 = array(
            array("id" => $user3, "course" => $courseId, "role" => $student),
            array("id" => $user3, "course" => $courseId, "role" => $cluster2)
        );

    }

    public function testImportItemsReplaceSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

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

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $time1 = "2020-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user1, "course" => $courseId, "cluster" => $cluster3, "date" => $time1]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user4, "course" => $courseId, "cluster" => $cluster2, "date" => $time1]);
        
        $time2 = "2020-06-23 18:10:42";
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user3, "course" => $courseId, "cluster" => $cluster1, "date" => $time2]);
        Core::$systemDB->insert(Profiling::TABLE_USER_PROFILE, ["user" => $user5, "course" => $courseId, "cluster" => $cluster2, "date" => $time2]);

        $file = "username;" . $time1 . ";" . $time2 . "\n";
        $file .= "ist112122;Hacker;Spy\n";
        $file .= "ist110001;Spy;Hacker\n";
        $file .= "ist11101036;Sleuth;Sleuth\n";
        $file .= "ist197046;;Hacker\n";
        $file .= "ist1100956;Sleuth;Spy\n";

        //When
        $result = $this->profiling->importItems($file);

        //Then
        $profileUser1 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user1]);
        $profileUser2 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user2]);
        $profileUser3 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user3]);
        $profileUser4 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user4]);
        $profileUser5 = Core::$systemDB->selectMultiple(Profiling::TABLE_USER_PROFILE, ["user" => $user5]);

        $expectedProfileUser1 = array(
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster1, "date" => $time1),
            array("user" => $user1, "course" => $courseId, "cluster" => $cluster2, "date" => $time2),
        );
        $expectedProfileUser2 = array(
            array("user" => $user2, "course" => $courseId, "cluster" => $cluster2, "date" => $time1),
            array("user" => $user2, "course" => $courseId, "cluster" => $cluster1, "date" => $time2)
        );
        $expectedProfileUser3 = array(
            array("user" => $user3, "course" => $courseId, "cluster" => $cluster3, "date" => $time1),
            array("user" => $user3, "course" => $courseId, "cluster" => $cluster3, "date" => $time2)
        );
        $expectedProfileUser4 = array(
            array("user" => $user4, "course" => $courseId, "cluster" => $cluster2, "date" => $time1),
            array("user" => $user4, "course" => $courseId, "cluster" => $cluster1, "date" => $time2)
        );
        $expectedProfileUser5 = array(
            array("user" => $user5, "course" => $courseId, "cluster" => $cluster3, "date" => $time1),
            array("user" => $user5, "course" => $courseId, "cluster" => $cluster2, "date" => $time2)
        );
        
        $this->assertEquals($expectedProfileUser1, $profileUser1);
        $this->assertEquals($expectedProfileUser2, $profileUser2);
        $this->assertEquals($expectedProfileUser3, $profileUser3);
        $this->assertEquals($expectedProfileUser4, $profileUser4);
        $this->assertEquals($expectedProfileUser5, $profileUser5);

        $newRoles1 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user1]);
        $newRoles2 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user2]);
        $newRoles3 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user3]);
        $newRoles4 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user4]);
        $newRoles5 = Core::$systemDB->selectMultiple("user_role", ["course" => $courseId, "id" => $user5]);

        $expectedRoleUser1 = array(
            array("id" => $user1, "course" => $courseId, "role" => $student),
            array("id" => $user1, "course" => $courseId, "role" => $cluster2),
        );
        $expectedRoleUser2 = array(
            array("id" => $user2, "course" => $courseId, "role" => $student),
            array("id" => $user2, "course" => $courseId, "role" => $cluster1)
        );
        $expectedRoleUser3 = array(
            array("id" => $user3, "course" => $courseId, "role" => $student),
            array("id" => $user3, "course" => $courseId, "role" => $cluster3)
        );
        $expectedRoleUser4 = array(
            array("id" => $user4, "course" => $courseId, "role" => $student),
            array("id" => $user4, "course" => $courseId, "role" => $cluster1)
        );
        $expectedRoleUser5 = array(
            array("id" => $user5, "course" => $courseId, "role" => $student),
            array("id" => $user5, "course" => $courseId, "role" => $cluster2)
        );

    }

    public function testCheckStatusNoFile(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $baseTime = "2020-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_CONFIG, ["course" => $courseId, "lastRun" => $baseTime]);

        //When
        $result = $this->profiling->checkStatus($courseId);

        //Then
        $newTime = Core::$systemDB->select(Profiling::TABLE_CONFIG, ["course" => $courseId], "lastRun");
        $this->assertEquals($baseTime, $newTime);
        $this->assertEmpty($result);
    }

    public function testCheckStatusEmptyFile(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $baseTime = "2020-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_CONFIG, ["course" => $courseId, "lastRun" => $baseTime]);

        $myfile = fopen($this->profiling->getLogPath($courseId), "w");

        //When
        $result = $this->profiling->checkStatus($courseId);

        //Then
        $this->assertEmpty($result);

        $newTime = Core::$systemDB->select(Profiling::TABLE_CONFIG, ["course" => $courseId], "lastRun");
        $this->assertEquals($baseTime, $newTime);

        unlink($this->profiling->getLogPath($courseId));
    }

    public function testCheckStatusErrorMessage(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $baseTime = "2020-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_CONFIG, ["course" => $courseId, "lastRun" => $baseTime]);

        $errorMessage = "Error: An error occured while running the profiler.";
        file_put_contents($this->profiling->getLogPath($courseId), $errorMessage);

        //When
        $result = $this->profiling->checkStatus($courseId);

        //Then
        $this->assertEquals(array("errorMessage" => $errorMessage), $result);

        $newTime = Core::$systemDB->select(Profiling::TABLE_CONFIG, ["course" => $courseId], "lastRun");
        $this->assertEquals($baseTime, $newTime);

        unlink($this->profiling->getLogPath($courseId));
    }

    /**
     * @depends testCreateClusterListSuccess
     */
    public function testCheckStatusSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $baseTime = "2020-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_CONFIG, ["course" => $courseId, "lastRun" => $baseTime]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

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

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $fileContent = "{8995: 0, 4578: 1, 6845: 2}+[0, 2, 2, 1, 0]";
        file_put_contents($this->profiling->getLogPath($courseId), $fileContent);

        //When
        $result = $this->profiling->checkStatus($courseId);

        //Then
        $expectedResult = array(
            $user1 => array("name" => "Noël Miller", "cluster" => "Hacker"),
            $user2 => array("name" => "Ana Gonçalves", "cluster" => "Spy"),
            $user3 => array("name" => "Marcus Notø", "cluster" => "Spy"),
            $user4 => array("name" => "Simão Patrício", "cluster" => "Sleuth"),
            $user5 => array("name" => "Sabri M'Barki", "cluster" => "Hacker")
        );
        $this->assertEquals($expectedResult, $result);

        $newTime = Core::$systemDB->select(Profiling::TABLE_CONFIG, ["course" => $courseId], "lastRun");
        $this->assertNotEquals($baseTime, $newTime);

        unlink($this->profiling->getLogPath($courseId));
    }

    /**
     * @depends testCreateClusterListInexistingCourse
     */
    public function testCheckStatusInexistingCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $baseTime = "2020-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_CONFIG, ["course" => $courseId, "lastRun" => $baseTime]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

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

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $fileContent = "{8995: 0, 4578: 1, 6845: 2}+[0, 2, 2, 1, 0]";
        file_put_contents($this->profiling->getLogPath($courseId), $fileContent);

        //When
        $result = $this->profiling->checkStatus($courseId + 1);

        //Then
        $this->assertEmpty($result);

        $newTime = Core::$systemDB->select(Profiling::TABLE_CONFIG, ["course" => $courseId], "lastRun");
        $this->assertEquals($baseTime, $newTime);

        $entry = Core::$systemDB->select(Profiling::TABLE_CONFIG, ["course" => $courseId + 1]);
        $this->assertEmpty($entry);

        unlink($this->profiling->getLogPath($courseId));
    }

    public function testCheckStatusNullCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $baseTime = "2020-06-20 14:30:00";
        Core::$systemDB->insert(Profiling::TABLE_CONFIG, ["course" => $courseId, "lastRun" => $baseTime]);

        $student = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Student"]);
        $profiling = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
        $cluster1 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Hacker"]);
        $cluster2 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Spy"]);
        $cluster3 = Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Sleuth"]);

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

        Core::$systemDB->insert("user_role", ["id" => $user1, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user2, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user3, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user4, "course" => $courseId, "role" => $student]);
        Core::$systemDB->insert("user_role", ["id" => $user5, "course" => $courseId, "role" => $student]);

        $fileContent = "{8995: 0, 4578: 1, 6845: 2}+[0, 2, 2, 1, 0]";
        file_put_contents($this->profiling->getLogPath($courseId), $fileContent);

        //When
        $result = $this->profiling->checkStatus(null);

        //Then
        $this->assertEmpty($result);

        $newTime = Core::$systemDB->select(Profiling::TABLE_CONFIG, ["course" => $courseId], "lastRun");
        $this->assertEquals($baseTime, $newTime);

        unlink($this->profiling->getLogPath($courseId));
    }

    public function testCheckPredictorStatusNoFile(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        //When
        $result = $this->profiling->checkPredictorStatus($courseId);

        //Then
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCheckPredictorStatusEmptyFile(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $myfile = fopen($this->profiling->getPredictorPath($courseId), "w");

        //When
        $result = $this->profiling->checkPredictorStatus($courseId);

        //Then
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        unlink($this->profiling->getPredictorPath($courseId));
    }

    public function testCheckPredictorStatusErrorMessage(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $errorMessage = "Error: No awards to analize while running predictor.";
        file_put_contents($this->profiling->getPredictorPath($courseId), $errorMessage);

        //When
        $result = $this->profiling->checkPredictorStatus($courseId);

        //Then
        $this->assertEquals(array("errorMessage" => $errorMessage), $result);

        unlink($this->profiling->getPredictorPath($courseId));
    }

    public function testCheckPredictorStatusSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $nClusters = 6;
        file_put_contents($this->profiling->getPredictorPath($courseId), $nClusters);

        //When
        $result = $this->profiling->checkPredictorStatus($courseId);

        //Then
        $this->assertEquals(array("nClusters" => $nClusters), $result);

        unlink($this->profiling->getPredictorPath($courseId));
    }

    public function testCheckPredictorStatusInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $nClusters = 4;
        file_put_contents($this->profiling->getPredictorPath($courseId), $nClusters);

        //When
        $result = $this->profiling->checkPredictorStatus($courseId + 1);

        //Then
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        unlink($this->profiling->getPredictorPath($courseId));
    }

    public function testCheckPredictorStatusNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", [
            "name" => "Forensics Cyber-Security", 
            "short" => "FCS", 
            "year" => "2020-2021", 
            "color" => "#329da8", 
            "isActive" => 1, 
            "isVisible" => 1,
            "roleHierarchy" => json_encode([["name" => "Student", "children" => [["name" => "Profiling", "children" => [["name" => "Hacker"], 
                                                                                                                        ["name" => "Spy"],
                                                                                                                        ["name" => "Sleuth"]]]]]])
        ]);

        $nClusters = 4;
        file_put_contents($this->profiling->getPredictorPath($courseId), $nClusters);

        //When
        $result = $this->profiling->checkPredictorStatus(null);

        //Then
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        unlink($this->profiling->getPredictorPath($courseId));
    }

}