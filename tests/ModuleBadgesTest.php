<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use Modules\Badges\Badges;

use PHPUnit\Framework\TestCase;


class ModuleBadgesTest extends TestCase
{
    protected $badges;

    public static function setUpBeforeClass():void {
        Core::init();
        Core::$systemDB->executeQuery(file_get_contents("modules/badges/create.sql"));
    }

    protected function setUp():void {
        $this->badges = new Badges();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
        Core::$systemDB->deleteAll("game_course_user");
    }

    public static function tearDownAfterClass(): void {
        Core::$systemDB->executeQuery(file_get_contents("modules/badges/delete.sql"));
    }

    //Data Providers

    public function newBadgeSuccessProvider(){
        return array(
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => 0, 'pointBased' => 0, 'extra' => 1, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12],
                  [array("number" => 1, "goal" => 2, "description" => "participate 2 times", "reward" => 100),
                  array("number" => 2, "goal" => 6, "description" => "participate 6 times", "reward" => 100),
                  array("number" => 3, "goal" => 12, "description" => "participate 12 times", "reward" => 100)]),       //standard case count based
                  
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 0, 'postBased' => 1, 'pointBased' => 0, 'extra' => 1, 'image' => "talkative.png", "count1" => 0, "count2" => 0, "count3" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "participate 2 times", "reward" => 100),
                  array("number" => 2, "goal" => 0, "description" => "participate 6 times", "reward" => 100),
                  array("number" => 3, "goal" => 0, "description" => "participate 12 times", "reward" => 100)]),        //standard case post based
                  
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "get 1 point", 'xp1' => 100, 'desc2' => "get 2 point", 'xp2' => 100,'desc3' => "get 3 point", 'xp3' => 100, 'countBased' => 0, 'postBased' => 0, 'pointBased' => 1, 'extra' => 1, 'image' => "talkative.png", "count1" => 1, "count2" => 2, "count3" => 3],
                  [array("number" => 1, "goal" => 1, "description" => "get 1 point", "reward" => 100),
                  array("number" => 2, "goal" => 2, "description" => "get 2 point", "reward" => 100),
                  array("number" => 3, "goal" => 3, "description" => "get 3 point", "reward" => 100)]),                 //standard case point based

            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 0, 'desc2' => "participate 6 times", 'xp2' => 0,'desc3' => "participate 12 times", 'xp3' => 0, 'countBased' => 1, 'postBased' => 0, 'pointBased' => 0, 'extra' => 0, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12],
                  [array("number" => 1, "goal" => 2, "description" => "participate 2 times", "reward" => 0),
                  array("number" => 2, "goal" => 6, "description" => "participate 6 times", "reward" => 0),
                  array("number" => 3, "goal" => 12, "description" => "participate 12 times", "reward" => 0)]),       //bragging all zero

            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 0, 'desc2' => "participate 6 times", 'xp2' => 10,'desc3' => "participate 12 times", 'xp3' => 20, 'countBased' => 1, 'postBased' => 0, 'pointBased' => 0, 'extra' => 0, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12],
                  [array("number" => 1, "goal" => 2, "description" => "participate 2 times", "reward" => 0),
                  array("number" => 2, "goal" => 6, "description" => "participate 6 times", "reward" => 10),
                  array("number" => 3, "goal" => 12, "description" => "participate 12 times", "reward" => 20)]),       //bragging only xp1 = 0

            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => 0, 'pointBased' => 0, 'extra' => 1, 'image' => null, "count1" => 2, "count2" => 6, "count3" => 12],
                  [array("number" => 1, "goal" => 2, "description" => "participate 2 times", "reward" => 100),
                  array("number" => 2, "goal" => 6, "description" => "participate 6 times", "reward" => 100),
                  array("number" => 3, "goal" => 12, "description" => "participate 12 times", "reward" => 100)]),       //null image

            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => 0, 'pointBased' => 0, 'extra' => 1, 'image' => "", "count1" => 2, "count2" => 6, "count3" => 12],
                  [array("number" => 1, "goal" => 2, "description" => "participate 2 times", "reward" => 100),
                  array("number" => 2, "goal" => 6, "description" => "participate 6 times", "reward" => 100),
                  array("number" => 3, "goal" => 12, "description" => "participate 12 times", "reward" => 100)]),       //empty image
            
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "get 1 point", 'xp1' => 100, 'desc2' => "get 2 point", 'xp2' => 100, 'desc3' => "", 'xp3' => 100, 'countBased' => 0, 'postBased' => 0, 'pointBased' => 1, 'extra' => 1, 'image' => "talkative.png", "count1" => 1, "count2" => 2, "count3" => 3],
                  [array("number" => 1, "goal" => 1, "description" => "get 1 point", "reward" => 100),
                  array("number" => 2, "goal" => 2, "description" => "get 2 point", "reward" => 100)]),                 //two levels, xp3 set
                  
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "get 1 point", 'xp1' => 100, 'desc2' => "get 2 point", 'xp2' => 100, 'countBased' => 0, 'postBased' => 0, 'pointBased' => 1, 'extra' => 1, 'image' => "talkative.png", "count1" => 1, "count2" => 2, "count3" => 3],
                  [array("number" => 1, "goal" => 1, "description" => "get 1 point", "reward" => 100),
                  array("number" => 2, "goal" => 2, "description" => "get 2 point", "reward" => 100)]),                 //two levels, xp3 not set
                  
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "get 1 point", 'xp1' => 100, 'desc2' => "", 'xp2' => 100, 'countBased' => 0, 'postBased' => 0, 'pointBased' => 1, 'extra' => 1, 'image' => "talkative.png", "count1" => 1, "count2" => 2, "count3" => 3],
                  [array("number" => 1, "goal" => 1, "description" => "get 1 point", "reward" => 100)]),                 //one level, xp2 set

            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "get 1 point", 'xp1' => 100, 'countBased' => 0, 'postBased' => 0, 'pointBased' => 1, 'extra' => 1, 'image' => "talkative.png", "count1" => 1, "count2" => 2, "count3" => 3],
                  [array("number" => 1, "goal" => 1, "description" => "get 1 point", "reward" => 100)]),                 //one level, xp2 not set
        );
    }

    public function newBadgeNullFieldsSuccessProvider(){
        return array(
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => 1, 'pointBased' => 1, 'extra' => null, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12], 0, 1, 1, 1),    //null isExtra
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => null, 'postBased' => 1, 'pointBased' => 1, 'extra' => 1, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12], 1, 0, 1, 1),    //null isCount
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => 1, 'pointBased' => null, 'extra' => 1, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12], 1, 1, 0, 1),    //null isPoint
            array(['name' => "Talkative", 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => null, 'pointBased' => 1, 'extra' => 1, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12], 1, 1, 1, 0)     //null isPost
        );
    }
    
    public function newBadgeFailProvider(){
        return array(
            array(['name' => null, 'description' => "Participate in Theoretical Lectures!", 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => 0, 'pointBased' => 0, 'extra' => 1, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12], "name"),               //null name
            array(['name' => "Talkative", 'description' => null, 'desc1' => "participate 2 times", 'xp1' => 100, 'desc2' => "participate 6 times", 'xp2' => 100,'desc3' => "participate 12 times", 'xp3' => 100, 'countBased' => 1, 'postBased' => 0, 'pointBased' => 0, 'extra' => 1, 'image' => "talkative.png", "count1" => 2, "count2" => 6, "count3" => 12], "description")                                    //null description
        );
    }

    public function editBadgeSuccessProvider(){
        return array(
            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),        //same data

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'desc3' => "make fifty posts", 'xp3' => 0, 'image' => null, "count1" => 0, "count2" => 0, "count3" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0),
                  array("number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0)]),         //add level
                  
            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'image' => null, "count1" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0)]),       //remove level

            array(["name" => "Post Lord", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),        //change name

            array(["name" => "Post Master", "description" => "Post something new in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),         //change description

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 1, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),         //change isExtra

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 0, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),         //change isCount
                  
            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 0, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),         //change isPost

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 1, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),         //change isPoint

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => "badge2.png", "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),         //change image

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 20, "count2" => 30],
                  [array("number" => 1, "goal" => 20, "description" => "make twenty posts", "reward" => 0),
                  array("number" => 2, "goal" => 30, "description" => "make thirty posts", "reward" => 0)]),        //change level goal

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "post twenty times", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "post twenty times", "reward" => 0),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0)]),        //change level description

            array(["name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 110, 'desc2' => "make thirty posts", 'xp2' => 110, 'image' => null, "count1" => 0, "count2" => 0],
                  [array("number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 110),
                  array("number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 110)]),      //change level reward + isBragging

        );
    }

    public function invalidImportFileProvider(){
        return array(
            array(""),                                                                                              //empty file
            array(null),                                                                                            //null file
            array("name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n")               //empty file with header
        );
    }

    public function testDeleteDataRowsOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $badgeId = Core::$systemDB->insert("badge", [ "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1]);
        $level1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        $level2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100]);
        $level3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100]);
        
        $badgeId2 = Core::$systemDB->insert("badge", [ "maxLevel" => 1, "name" => "Lab Master", "course" => $courseId, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0]);
        $level12 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        
        //When
        $this->badges->deleteDataRows($courseId);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $this->assertEmpty($levels);
        $this->assertEmpty($badges);
    }

    public function testDeleteDataRowsTwoCoursesSuccess(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge1 = Core::$systemDB->insert("badge", [ "maxLevel" => 3, "name" => "Quiz Master", "course" => $course1, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1]);
        $level11 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        $level12 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100]);
        $level13 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100]);
        
        $course2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $badge2 = Core::$systemDB->insert("badge", [ "maxLevel" => 1, "name" => "Lab Master", "course" => $course2, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0]);
        $level21 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        
        //When
        $this->badges->deleteDataRows($course1);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badge2, "maxLevel" => 1, "name" => "Lab Master", "course" => $course2, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0)
        );
        $expectedLevels = array(
            array("id" => $level21, "badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100)
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testDeleteLevelsOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        $badgeId = Core::$systemDB->insert("badge", [ "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1]);
        $level1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        $level2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100]);
        $level3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100]);
        
        $badgeId2 = Core::$systemDB->insert("badge", [ "maxLevel" => 1, "name" => "Lab Master", "course" => $courseId, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0]);
        $level12 = Core::$systemDB->insert("badge_level", ["badgeId" => $badgeId2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        
        //When
        $this->badges->deleteLevels($courseId);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $expectedBadges = array(
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badgeId, "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1),
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badgeId2, "maxLevel" => 1, "name" => "Lab Master", "course" => $courseId, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0)
        );
        $this->assertEquals($expectedBadges, $badges);

        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $this->assertEmpty($levels);
    }

    public function testDeleteLevelsTwoCoursesSuccess(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge1 = Core::$systemDB->insert("badge", [ "maxLevel" => 3, "name" => "Quiz Master", "course" => $course1, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1]);
        $level11 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        $level12 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100]);
        $level13 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100]);
        
        $course2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $badge2 = Core::$systemDB->insert("badge", [ "maxLevel" => 1, "name" => "Lab Master", "course" => $course2, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0]);
        $level21 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        
        //When
        $this->badges->deleteLevels($course1);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $expectedBadges = array(
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badge1, "maxLevel" => 3, "name" => "Quiz Master", "course" => $course1, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1),
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badge2, "maxLevel" => 1, "name" => "Lab Master", "course" => $course2, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0)
        );
        $this->assertEquals($expectedBadges, $badges);

        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $expectedLevels = array(
            array("id" => $level21, "badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100)
        );
        $this->assertEquals($expectedLevels, $levels);
    }

    public function testDeleteLevelsNoBadgesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        //When
        $this->badges->deleteLevels($courseId);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $this->assertEmpty($levels);
        $this->assertEmpty($badges);
    }

    public function testDeleteBadgeOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", [ "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1]);
        $level1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        $level2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100]);
        $level3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100]);
        
        $badge2 = Core::$systemDB->insert("badge", [ "maxLevel" => 1, "name" => "Lab Master", "course" => $courseId, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0]);
        $level12 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        
        //When
        $this->badges->deleteBadge(array("id" => $badge1));

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $expectedBadges = array(
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badge2, "maxLevel" => 1, "name" => "Lab Master", "course" => $courseId, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0)
        );
        $this->assertEquals($expectedBadges, $badges);

        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $expectedLevels = array(
            array("id" => $level12, "badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100)
        );
        $this->assertEquals($expectedLevels, $levels);
    }

    public function testDeleteBadgeInexistingBadge(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge = Core::$systemDB->insert("badge", [ "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1]);
        $level1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        $level2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100]);
        $level3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100]);

        //When
        $this->badges->deleteBadge(array("id" => $badge + 1));

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $expectedBadges = array(
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badge, "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1),
        );
        $this->assertEquals($expectedBadges, $badges);

        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $expectedLevels = array(
            array("id" => $level1, "badgeId" => $badge, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100),
            array("id" => $level2, "badgeId" => $badge, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100),
            array("id" => $level3, "badgeId" => $badge, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100)
        );
        $this->assertEquals($expectedLevels, $levels);
    }

    public function testDeleteBadgeNullBadge(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge = Core::$systemDB->insert("badge", [ "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1]);
        $level1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        $level2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100]);
        $level3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100]);

        //When
        $this->badges->deleteBadge(null);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $expectedBadges = array(
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badge, "maxLevel" => 3, "name" => "Quiz Master", "course" => $courseId, "description" => "Get the top grade in quizes", "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 1),
        );
        $this->assertEquals($expectedBadges, $badges);

        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $expectedLevels = array(
            array("id" => $level1, "badgeId" => $badge, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100),
            array("id" => $level2, "badgeId" => $badge, "number" => 2, "goal" => 1, "description" => "level 2", "reward" => 100),
            array("id" => $level3, "badgeId" => $badge, "number" => 3, "goal" => 1, "description" => "level 3", "reward" => 100)
        );
        $this->assertEquals($expectedLevels, $levels);
    }

    /**
     * @dataProvider newBadgeSuccessProvider
     */
    public function testNewBadgeSuccess($badgeInfo, $expectedLevels){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        //When
        $this->badges->newBadge($badgeInfo, $courseId);

        //Then
        $badge = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadge = array(
            array("id" => $badge[0]["id"], "course" => $courseId, "maxLevel" => count($expectedLevels), "name" => $badgeInfo["name"], "description" => $badgeInfo["description"], "isActive" => 1, "isCount" => $badgeInfo["countBased"], "isPost" => $badgeInfo["postBased"], "isPoint" => $badgeInfo["pointBased"], "isExtra" => $badgeInfo["extra"], "isBragging" => ($badgeInfo['xp1'] == 0) ? 1 : 0, "image" => $badgeInfo["image"])
        );

        foreach($levels as &$level){
            unset($level["id"]);
        }
        foreach($expectedLevels as &$expectedLevel){
            $expectedLevel["badgeId"] = $badge[0]["id"];
        }
            
        $this->assertEquals($expectedBadge, $badge);
        $this->assertEquals($expectedLevels, $levels);
        
    }

    /**
     * @dataProvider newBadgeNullFieldsSuccessProvider
     */
    public function testNewBadgeNullFieldsSuccess($badgeInfo, $isExtra, $isCount, $isPoint, $isPost){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        //When
        $this->badges->newBadge($badgeInfo, $courseId);

        //Then
        $badge = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadge = array(
            array("id" => $badge[0]["id"], "course" => $courseId, "maxLevel" => 3, "name" => $badgeInfo["name"], "description" => $badgeInfo["description"], "isActive" => 1, "isCount" => $isCount, "isPost" => $isPost, "isPoint" => $isPoint, "isExtra" => $isExtra, "isBragging" => 0, "image" => $badgeInfo["image"])
        );

        $expectedLevels = array(
            array("id" => $levels[0]["id"], "badgeId" => $badge[0]["id"], "number" => 1, "goal" => 2, "description" => "participate 2 times", "reward" => 100),
            array("id" => $levels[1]["id"], "badgeId" => $badge[0]["id"], "number" => 2, "goal" => 6, "description" => "participate 6 times", "reward" => 100),
            array("id" => $levels[2]["id"], "badgeId" => $badge[0]["id"], "number" => 3, "goal" => 12, "description" => "participate 12 times", "reward" => 100)
        );
            
        $this->assertEquals($expectedBadge, $badge);
        $this->assertEquals($expectedLevels, $levels);
        
    }

    /**
     * @dataProvider newBadgeFailProvider
     */
    public function testNewBadgeFail($badgeInfo, $missingField){
        
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        try {
            $this->badges->newBadge($badgeInfo, $courseId);
            $this->fail("PDOException should have been thrown for null " . $missingField . " on newBadge.");

        } catch (\PDOException $e) {
            $badge = Core::$systemDB->selectMultiple("badge", []);
            $levels = Core::$systemDB->selectMultiple("badge_level", []);
                
            $this->assertEmpty($badge);
            $this->assertEmpty($levels);
        } 
    }

    public function testGetBadgesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", ["course" => $courseId, "maxLevel" => 3, "name" => "Post Master", "description" => "Post something in the forums", "isActive" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isBragging" => 1, "image" => "someImage.jpeg"]);
        $badge1L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0]);                                      
        $badge1L2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0]); 
        $badge1L3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0]); 

        $badge2 = Core::$systemDB->insert("badge", ["course" => $courseId, "maxLevel" => 1, "name" => "Course Emperor", "description" => "Take the course, be the best", "isActive" => 1, "isExtra" => 1, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isBragging" => 0]);
        $badge2L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 0, "description" => "Have the highest course grade!", "reward" => 80]);                                      
 
        //When
        $badges = $this->badges->getBadges($courseId);

        //Then
        $expectedBadges = array(
            array("id" => $badge2, "course" => $courseId, "maxLevel" => 1, "name" => "Course Emperor", "description" => "Take the course, be the best", "isActive" => 1, "isExtra" => 1, "extra" => 1, "isCount" => 0, "countBased" => 0, "isPost" => 0, "postBased" => 0, "isPoint" => 0, "pointBased" => 0, "isBragging" => 0, "image" => null, "desc1" => "Have the highest course grade!", "xp1" => 80, "count1" => 0),
            array("id" => $badge1, "course" => $courseId, "maxLevel" => 3, "name" => "Post Master", "description" => "Post something in the forums", "isActive" => 0, "isExtra" => 0, "extra" => 0, "isCount" => 1, "countBased" => 1, "isPost" => 1, "postBased" => 1, "isPoint" => 0, "pointBased" => 0, "isBragging" => 1, "image" => "someImage.jpeg", "desc1" => "make twenty posts", "xp1" => 0, "count1" => 0, "desc2" => "make thirty posts", "xp2" => 0, "count2" => 0, "desc3" => "make fifty posts", "xp3" => 0, "count3" => 0)
        );
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testGetBadgesNoBadgesSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        //When
        $badges = $this->badges->getBadges($courseId);

        //Then
        $this->assertEmpty($badges);
    }

    public function testGetBadgesTwoCoursesSuccess(){

        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge1 = Core::$systemDB->insert("badge", ["course" => $course1, "maxLevel" => 3, "name" => "Post Master", "description" => "Post something in the forums", "isActive" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isBragging" => 1, "image" => "someImage.jpeg"]);
        $badge1L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0]);                                      
        $badge1L2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0]); 
        $badge1L3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0]); 

        $course2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $badge2 = Core::$systemDB->insert("badge", ["course" => $course2, "maxLevel" => 1, "name" => "Course Emperor", "description" => "Take the course, be the best", "isActive" => 1, "isExtra" => 1, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isBragging" => 0]);
        $badge2L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 0, "description" => "Have the highest course grade!", "reward" => 80]);                                      
 
        //When
        $badges = $this->badges->getBadges($course2);

        //Then
        $expectedBadges = array(
            array("id" => $badge2, "course" => $course2, "maxLevel" => 1, "name" => "Course Emperor", "description" => "Take the course, be the best", "isActive" => 1, "isExtra" => 1, "extra" => 1, "isCount" => 0, "countBased" => 0, "isPost" => 0, "postBased" => 0, "isPoint" => 0, "pointBased" => 0, "isBragging" => 0, "image" => null, "desc1" => "Have the highest course grade!", "xp1" => 80, "count1" => 0)
        );
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testGetBadgesNoBadgesInexistingCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge1 = Core::$systemDB->insert("badge", ["course" => $courseId, "maxLevel" => 3, "name" => "Post Master", "description" => "Post something in the forums", "isActive" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isBragging" => 1, "image" => "someImage.jpeg"]);
        $badge1L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0]);                                      
        $badge1L2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0]); 
        $badge1L3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0]); 

        //When
        $badges = $this->badges->getBadges($courseId + 1);

        //Then
        $this->assertEmpty($badges);
    }

    public function testGetBadgesNullCourse(){

        //When
        $badges = $this->badges->getBadges(null);

        //Then
        $this->assertEmpty($badges);
    }

    /**
     * @dataProvider editBadgeSuccessProvider
     */
    public function testEditBadgeSuccess($badgeInfo, $expectedNewLevels){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badge2 = Core::$systemDB->insert("badge", ["name" => "Post Master", "course" => $courseId, "description" => "Post something in the forums", "maxLevel" => 2, "isExtra" => 0, "isBragging" => 1, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1]);
        
        $badge1L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badge1L2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badge1L3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0]);

        $badgeInfo["id"] = $badge2;
        foreach($expectedNewLevels as &$expectedNewLevel){
            $expectedNewLevel["badgeId"] = $badge2;
        }

        //When
        $result = $this->badges->editBadge($badgeInfo, $courseId);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        foreach($levels as &$level){
            unset($level["id"]);
        }

        $expectedBadges = array(
            array("id" => $badge1, "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
            array("id" => $badge2,  "course" => $courseId, "maxLevel" => count($expectedNewLevels), "name" => $badgeInfo["name"], "description" => $badgeInfo["description"], "isActive" => 1, "isCount" => $badgeInfo["countBased"], "isPost" => $badgeInfo["postBased"], "isPoint" => $badgeInfo["pointBased"], "isExtra" => $badgeInfo["extra"], "isBragging" => ($badgeInfo['xp1'] == 0) ? 1 : 0, "image" => $badgeInfo["image"]), 
        );
        $expectedLevels = array(
            array("badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
        );

        $this->assertEquals(array_merge($expectedLevels, $expectedNewLevels), $levels);
        $this->assertEquals($expectedBadges, $badges);

    }

    public function testEditBadgeInexistingBadge(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);

        $badge1L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badge1L2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badge1L3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);

        $badgeInfo = array("id" => $badge1 + 2, "name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0);
        
        //When
        $result = $this->badges->editBadge($badgeInfo, $courseId);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badge1, "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
        );
        $expectedLevels = array(
            array("id" => $badge1L1, "badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $badge1L2, "badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $badge1L3, "badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testEditBadgeEmptyStrings(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        
        $badge1L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badge1L2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badge1L3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);

        $badgeInfo = array("id" => $badge1, "name" => "", "description" => "", "extra" => 0, "countBased" => 0, "postBased" => 0, "pointBased" => 0, 'desc1' => "", 'xp1' => 0, 'desc2' => "", 'xp2' => 0, 'image' => "", "count1" => 0, "count2" => 0);
       
        //When
        $result = $this->badges->editBadge($badgeInfo, $courseId);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        foreach($levels as &$level){
            unset($level["id"]);
        }

        $expectedBadges = array(
            array("id" => $badge1, "name" => "", "course" => $courseId, "description" => "", "maxLevel" => 1, "isExtra" => 0, "isBragging" => 1, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => "")
        );
        $expectedLevels = array(
            array("badgeId" => $badge1, "number" => 1, "goal" => 0, "description" => "", "reward" => 0)
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testEditBadgeWrongCourse(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge1 = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $course1, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badge1L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badge1L2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badge1L3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);

        $course2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $badge2 = Core::$systemDB->insert("badge", [ "maxLevel" => 1, "name" => "Lab Master", "course" => $course2, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0]);
        $badge2L1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100]);
        
        $badgeInfo = array("id" => $badge1, "name" => "Post Master", "description" => "Post something in the forums", "extra" => 0, "countBased" => 1, "postBased" => 1, "pointBased" => 0, 'desc1' => "make twenty posts", 'xp1' => 0, 'desc2' => "make thirty posts", 'xp2' => 0, 'image' => null, "count1" => 0, "count2" => 0);
        
        //When
        $result = $this->badges->editBadge($badgeInfo, $course2);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badge1, "name" => "Class Annotator", "course" => $course1, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
            array("isActive" => 1, "isExtra" => 0, "image" => null, "id" => $badge2, "maxLevel" => 1, "name" => "Lab Master", "course" => $course2, "description" => "Get the top grade in labs", "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0)
        );
        $expectedLevels = array(
            array("id" => $badge1L1, "badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $badge1L2, "badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $badge1L3, "badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
            array("id" => $badge2L1, "badgeId" => $badge2, "number" => 1, "goal" => 1, "description" => "level 1", "reward" => 100),
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testActiveItemActivateSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 0]);
        $badgeL1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badgeL2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badgeL3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        
        //When
        $this->badges->activeItem($badge);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badge, "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
        );
        $expectedLevels = array(
            array("id" => $badgeL1, "badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $badgeL2, "badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $badgeL3, "badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testActiveItemDeactivateSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badgeL1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badgeL2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badgeL3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        
        //When
        $this->badges->activeItem($badge);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badge, "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 0, "image" => null),
        );
        $expectedLevels = array(
            array("id" => $badgeL1, "badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $badgeL2, "badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $badgeL3, "badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testActiveItemInexistingBadge(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badgeL1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badgeL2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badgeL3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        
        //When
        $this->badges->activeItem($badge + 1);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badge, "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
        );
        $expectedLevels = array(
            array("id" => $badgeL1, "badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $badgeL2, "badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $badgeL3, "badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    public function testActiveItemNullBadge(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $badge = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badgeL1 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        $badgeL2 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        $badgeL3 = Core::$systemDB->insert("badge_level", ["badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        
        //When
        $this->badges->activeItem(null);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badge, "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
        );
        $expectedLevels = array(
            array("id" => $badgeL1, "badgeId" => $badge, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $badgeL2, "badgeId" => $badge, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $badgeL3, "badgeId" => $badge, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
        );

        $this->assertEquals($expectedLevels, $levels);
        $this->assertEquals($expectedBadges, $badges);
    }

    /**
     * @depends testNewBadgeSuccess
     */
    public function testImportItemsNoHeaderNoReplaceSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $file = "Class Annotator;Find related resources, more information, about class subjects;get four points;get eight points;get twelve points;300;300;300;True;True;True;4;8;12
                Apprentice;Give answers in the 'questions' or 'Labs' forums;get four points;get eight points;get twelve points;-50;-50;-50;True;True;True;4;8;12
                Replier Extraordinaire;Respond to the gamification questionnaires;respond to first questionnaire;respond to both the weekly questionnaires;respond to all the questionnaires;200;200;200;True;False;False;;;
                Focused;Participate in the Focus Group Interviews;participate in the interviews;;;-100;;;False;False;False;;;
                Artist;Show creativity and quality;get four posts of four points;get six posts of four points;get twelve posts of four points;300;300;300;True;True;False;;;
                Grader;Peergrade your colleague's posts in the Skill Tree;Peergrade 10 posts;Peergrade 20 posts;Peergrade 30 posts;-300;-300;-300;True;True;False;;;
                Hall of Fame;Get into the Hall of Fame;one entry;two entries;three entries;-50;-50;-50;False;False;False;;;
                Lab Lover;Show up for labs!;be there for 50% of labs;be there for 75% of labs;be there for all of the labs;-50;-50;-50;True;False;False;;;
                Wild Imagination;Suggest presentation subjects;suggest a new subject for your presentation;;;600;;;False;True;False;;;
                Suggestive;Give useful suggestions for the course (new skills, etc.);get four points;get eight points;get twelve points;-50;-50;-50;True;True;True;4;8;12
                Quiz Master;Excel at the quizzes;top grade in four quizzes;top grade in six quizzes;top grade in eight quizzes;0;0;0;True;False;False;4;6;8
                Post Master;Post something in the forums;make twenty posts;make thirty posts;make fifty posts;0;0;0;True;True;False;;;
                Book Master;Read class slides;read slides for 50% of lectures;read slides for 75% of lectures;read all lectures slides;0;0;0;True;False;False;;;
                Tree Climber;Reach higher levels of the skill tree;reach level two;reach level three;reach level four;-100;-100;-100;False;True;False;;;";

        //When
        $newBadges = $this->badges->importItems($courseId, $file);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badges[0]["id"], "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
            array("id" => $badges[1]["id"], "name" => "Apprentice", "course" => $courseId, "description" => "Give answers in the 'questions' or 'Labs' forums", "maxLevel" => 3, "isExtra" => 1, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
            array("id" => $badges[2]["id"], "name" => "Replier Extraordinaire", "course" => $courseId, "description" => "Respond to the gamification questionnaires", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[3]["id"], "name" => "Focused", "course" => $courseId, "description" => "Participate in the Focus Group Interviews", "maxLevel" => 1, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[4]["id"], "name" => "Artist", "course" => $courseId, "description" => "Show creativity and quality", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[5]["id"], "name" => "Grader", "course" => $courseId, "description" => "Peergrade your colleague's posts in the Skill Tree", "maxLevel" => 3, "isExtra" => 1, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[6]["id"], "name" => "Hall of Fame", "course" => $courseId, "description" => "Get into the Hall of Fame", "maxLevel" => 3, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[7]["id"], "name" => "Lab Lover", "course" => $courseId, "description" => "Show up for labs!", "maxLevel" => 3, "isExtra" => 1, "isBragging" => 0, "isCount" => 1, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[8]["id"], "name" => "Wild Imagination", "course" => $courseId, "description" => "Suggest presentation subjects", "maxLevel" => 1, "isExtra" => 0, "isBragging" => 0, "isCount" => 0, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[9]["id"], "name" => "Suggestive", "course" => $courseId, "description" => "Give useful suggestions for the course (new skills, etc.)", "maxLevel" => 3, "isExtra" => 1, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
            array("id" => $badges[10]["id"], "name" => "Quiz Master", "course" => $courseId, "description" => "Excel at the quizzes", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null), 
            array("id" => $badges[11]["id"], "name" => "Post Master", "course" => $courseId, "description" => "Post something in the forums", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 1, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null), 
            array("id" => $badges[12]["id"], "name" => "Book Master", "course" => $courseId, "description" => "Read class slides", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 1, "isCount" => 1, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null), 
            array("id" => $badges[13]["id"], "name" => "Tree Climber", "course" => $courseId, "description" => "Reach higher levels of the skill tree", "maxLevel" => 3, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null)
        );
        $expectedLevels = array(
            array("id" => $levels[0]["id"], "badgeId" => $badges[0]["id"], "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $levels[1]["id"], "badgeId" => $badges[0]["id"], "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $levels[2]["id"], "badgeId" => $badges[0]["id"], "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
            array("id" => $levels[3]["id"], "badgeId" => $badges[1]["id"], "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 50),
            array("id" => $levels[4]["id"], "badgeId" => $badges[1]["id"], "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 50),
            array("id" => $levels[5]["id"], "badgeId" => $badges[1]["id"], "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 50),
            array("id" => $levels[6]["id"], "badgeId" => $badges[2]["id"], "number" => 1, "goal" => 0, "description" => "respond to first questionnaire", "reward" => 200),
            array("id" => $levels[7]["id"], "badgeId" => $badges[2]["id"], "number" => 2, "goal" => 0, "description" => "respond to both the weekly questionnaires", "reward" => 200),
            array("id" => $levels[8]["id"], "badgeId" => $badges[2]["id"], "number" => 3, "goal" => 0, "description" => "respond to all the questionnaires", "reward" => 200),
            array("id" => $levels[9]["id"], "badgeId" => $badges[3]["id"], "number" => 1, "goal" => 0, "description" => "participate in the interviews", "reward" => 100),
            array("id" => $levels[10]["id"], "badgeId" => $badges[4]["id"], "number" => 1, "goal" => 0, "description" => "get four posts of four points", "reward" => 300),
            array("id" => $levels[11]["id"], "badgeId" => $badges[4]["id"], "number" => 2, "goal" => 0, "description" => "get six posts of four points", "reward" => 300),
            array("id" => $levels[12]["id"], "badgeId" => $badges[4]["id"], "number" => 3, "goal" => 0, "description" => "get twelve posts of four points", "reward" => 300),
            array("id" => $levels[13]["id"], "badgeId" => $badges[5]["id"], "number" => 1, "goal" => 0, "description" => "Peergrade 10 posts", "reward" => 300),
            array("id" => $levels[14]["id"], "badgeId" => $badges[5]["id"], "number" => 2, "goal" => 0, "description" => "Peergrade 20 posts", "reward" => 300),
            array("id" => $levels[15]["id"], "badgeId" => $badges[5]["id"], "number" => 3, "goal" => 0, "description" => "Peergrade 30 posts", "reward" => 300),
            array("id" => $levels[16]["id"], "badgeId" => $badges[6]["id"], "number" => 1, "goal" => 0, "description" => "one entry", "reward" => 50),
            array("id" => $levels[17]["id"], "badgeId" => $badges[6]["id"], "number" => 2, "goal" => 0, "description" => "two entries", "reward" => 50),
            array("id" => $levels[18]["id"], "badgeId" => $badges[6]["id"], "number" => 3, "goal" => 0, "description" => "three entries", "reward" => 50),
            array("id" => $levels[19]["id"], "badgeId" => $badges[7]["id"], "number" => 1, "goal" => 0, "description" => "be there for 50% of labs", "reward" => 50),
            array("id" => $levels[20]["id"], "badgeId" => $badges[7]["id"], "number" => 2, "goal" => 0, "description" => "be there for 75% of labs", "reward" => 50),
            array("id" => $levels[21]["id"], "badgeId" => $badges[7]["id"], "number" => 3, "goal" => 0, "description" => "be there for all of the labs", "reward" => 50),
            array("id" => $levels[22]["id"], "badgeId" => $badges[8]["id"], "number" => 1, "goal" => 0, "description" => "suggest a new subject for your presentation", "reward" => 600),
            array("id" => $levels[23]["id"], "badgeId" => $badges[9]["id"], "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 50),
            array("id" => $levels[24]["id"], "badgeId" => $badges[9]["id"], "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 50),
            array("id" => $levels[25]["id"], "badgeId" => $badges[9]["id"], "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 50),
            array("id" => $levels[26]["id"], "badgeId" => $badges[10]["id"], "number" => 1, "goal" => 4, "description" => "top grade in four quizzes", "reward" => 0),
            array("id" => $levels[27]["id"], "badgeId" => $badges[10]["id"], "number" => 2, "goal" => 6, "description" => "top grade in six quizzes", "reward" => 0),
            array("id" => $levels[28]["id"], "badgeId" => $badges[10]["id"], "number" => 3, "goal" => 8, "description" => "top grade in eight quizzes", "reward" => 0),
            array("id" => $levels[29]["id"], "badgeId" => $badges[11]["id"], "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
            array("id" => $levels[30]["id"], "badgeId" => $badges[11]["id"], "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0),
            array("id" => $levels[31]["id"], "badgeId" => $badges[11]["id"], "number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0),
            array("id" => $levels[32]["id"], "badgeId" => $badges[12]["id"], "number" => 1, "goal" => 0, "description" => "read slides for 50% of lectures", "reward" => 0),
            array("id" => $levels[33]["id"], "badgeId" => $badges[12]["id"], "number" => 2, "goal" => 0, "description" => "read slides for 75% of lectures", "reward" => 0),
            array("id" => $levels[34]["id"], "badgeId" => $badges[12]["id"], "number" => 3, "goal" => 0, "description" => "read all lectures slides", "reward" => 0),
            array("id" => $levels[35]["id"], "badgeId" => $badges[13]["id"], "number" => 1, "goal" => 0, "description" => "reach level two", "reward" => 100),
            array("id" => $levels[36]["id"], "badgeId" => $badges[13]["id"], "number" => 2, "goal" => 0, "description" => "reach level three", "reward" => 100),
            array("id" => $levels[37]["id"], "badgeId" => $badges[13]["id"], "number" => 3, "goal" => 0, "description" => "reach level four", "reward" => 100)
        );

        $this->assertEquals(14, $newBadges);
        $this->assertEquals($expectedBadges, $badges);
        $this->assertEquals($expectedLevels, $levels);
    }

    /**
     * @depends testNewBadgeSuccess
     */
    public function testImportItemsWithHeaderNoReplaceSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $file = "name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n";
        $file .= "Class Annotator;Find related resources, more information, about class subjects;1;1;1;get four points;300;4;get eight points;300;8;get twelve points;300;12\n";
        $file .= "Focused;Participate in the Focus Group Interviews;0;0;0;participate in the interviews;-100;;;;;;;\n";
        $file .= "Artist;Show creativity and quality;1;1;0;get four posts of four points;300;;get six posts of four points;300;;get twelve posts of four points;300;\n";
        $file .= "Post Master;Post something in the forums;1;1;0;make twenty posts;0;;make thirty posts;0;;make fifty posts;0;";;

        //When
        $newBadges = $this->badges->importItems($courseId, $file);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $badges[0]["id"], "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
            array("id" => $badges[1]["id"], "name" => "Focused", "course" => $courseId, "description" => "Participate in the Focus Group Interviews", "maxLevel" => 1, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[2]["id"], "name" => "Artist", "course" => $courseId, "description" => "Show creativity and quality", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[3]["id"], "name" => "Post Master", "course" => $courseId, "description" => "Post something in the forums", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 1, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null), 
        );
        $expectedLevels = array(
            array("id" => $levels[0]["id"], "badgeId" => $badges[0]["id"], "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $levels[1]["id"], "badgeId" => $badges[0]["id"], "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $levels[2]["id"], "badgeId" => $badges[0]["id"], "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
            array("id" => $levels[3]["id"], "badgeId" => $badges[1]["id"], "number" => 1, "goal" => 0, "description" => "participate in the interviews", "reward" => 100),
            array("id" => $levels[4]["id"], "badgeId" => $badges[2]["id"], "number" => 1, "goal" => 0, "description" => "get four posts of four points", "reward" => 300),
            array("id" => $levels[5]["id"], "badgeId" => $badges[2]["id"], "number" => 2, "goal" => 0, "description" => "get six posts of four points", "reward" => 300),
            array("id" => $levels[6]["id"], "badgeId" => $badges[2]["id"], "number" => 3, "goal" => 0, "description" => "get twelve posts of four points", "reward" => 300),
            array("id" => $levels[7]["id"], "badgeId" => $badges[3]["id"], "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
            array("id" => $levels[8]["id"], "badgeId" => $badges[3]["id"], "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0),
            array("id" => $levels[9]["id"], "badgeId" => $badges[3]["id"], "number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0),
        );

        $this->assertEquals(4, $newBadges);
        $this->assertEquals($expectedBadges, $badges);
        $this->assertEquals($expectedLevels, $levels);
    }

    /**
     * @depends testEditBadgeSuccess
     */
    public function testImportItemsReplaceSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $artistBadge = Core::$systemDB->insert("badge", ["name" => "Artist", "course" => $courseId, "description" => "Show creativity and quality", "maxLevel" => 1, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1]);
        $artistBadgeL1 = Core::$systemDB->insert("badge_level", ["badgeId" => $artistBadge,  "number" => 1, "goal" => 5, "description" => "this is the first description", "reward" => 150]);                                      

        $focusedBadge = Core::$systemDB->insert("badge", ["name" => "Focused", "course" => $courseId, "description" => "Participate in the Focus Group Interviews", "maxLevel" => 2, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1]);
        $focusedBadgeL1 = Core::$systemDB->insert("badge_level", ["badgeId" => $focusedBadge, "number" => 1, "goal" => 0, "description" => "participate in the interviews", "reward" => 100]);                                      
        $focusedBadgeL2 = Core::$systemDB->insert("badge_level", ["badgeId" => $focusedBadge, "number" => 2, "goal" => 0, "description" => "this level is supposed to be deleted", "reward" => 200]);                                      

        $file = "name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n";
        $file .= "Class Annotator;Find related resources, more information, about class subjects;1;1;1;get four points;300;4;get eight points;300;8;get twelve points;300;12\n";
        $file .= "Focused;Participate in the Focus Group Interviews;0;0;0;participate in the interviews;-100;;;;;;;\n";
        $file .= "Artist;Show creativity and quality;1;1;0;get four posts of four points;300;;get six posts of four points;300;;get twelve posts of four points;300;\n";
        $file .= "Post Master;Post something in the forums;1;1;0;make twenty posts;0;;make thirty posts;0;;make fifty posts;0;";;

        //When
        $newBadges = $this->badges->importItems($courseId, $file);

        //Then
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);

        $expectedBadges = array(
            array("id" => $artistBadge, "name" => "Artist", "course" => $courseId, "description" => "Show creativity and quality", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $focusedBadge, "name" => "Focused", "course" => $courseId, "description" => "Participate in the Focus Group Interviews", "maxLevel" => 1, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1, "image" => null),
            array("id" => $badges[2]["id"], "name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1, "image" => null),
            array("id" => $badges[3]["id"], "name" => "Post Master", "course" => $courseId, "description" => "Post something in the forums", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 1, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1, "image" => null), 
        );
        $expectedLevels = array(
            array("id" => $artistBadgeL1, "badgeId" => $artistBadge, "number" => 1, "goal" => 0, "description" => "get four posts of four points", "reward" => 300),
            array("id" => $levels[1]["id"], "badgeId" => $badges[2]["id"], "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300),
            array("id" => $levels[2]["id"], "badgeId" => $badges[2]["id"], "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300),
            array("id" => $levels[3]["id"], "badgeId" => $badges[2]["id"], "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300),
            array("id" => $levels[4]["id"], "badgeId" => $focusedBadge, "number" => 1, "goal" => 0, "description" => "participate in the interviews", "reward" => 100),
            array("id" => $levels[5]["id"], "badgeId" => $artistBadge, "number" => 2, "goal" => 0, "description" => "get six posts of four points", "reward" => 300),
            array("id" => $levels[6]["id"], "badgeId" => $artistBadge, "number" => 3, "goal" => 0, "description" => "get twelve posts of four points", "reward" => 300),
            array("id" => $levels[7]["id"], "badgeId" => $badges[3]["id"], "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0),
            array("id" => $levels[8]["id"], "badgeId" => $badges[3]["id"], "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0),
            array("id" => $levels[9]["id"], "badgeId" => $badges[3]["id"], "number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0),
        );

        $this->assertEquals(2, $newBadges);
        $this->assertEquals($expectedBadges, $badges);
        $this->assertEquals($expectedLevels, $levels);
    }

    /**
     * @dataProvider invalidImportFileProvider
     */
    public function testImportItemsInvalidFile($file){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        
        //When
        $newBadges = $this->badges->importItems($courseId, $file);

        //Then
        $this->assertEquals(0, $newBadges);
        $badges = Core::$systemDB->selectMultiple("badge", []);
        $levels = Core::$systemDB->selectMultiple("badge_level", []);
        $this->assertEmpty($levels);
        $this->assertEmpty($badges);
    }

    public function testExportItemsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badge2 = Core::$systemDB->insert("badge", ["name" => "Focused", "course" => $courseId, "description" => "Participate in the Focus Group Interviews", "maxLevel" => 1, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1]);
        $badge3 = Core::$systemDB->insert("badge", ["name" => "Artist", "course" => $courseId, "description" => "Show creativity and quality", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1]);
        $badge4 = Core::$systemDB->insert("badge", ["name" => "Post Master", "course" => $courseId, "description" => "Post something in the forums", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 1, "isCount" => 1, "isPost" => 1, "isPoint" => 0, "isActive" => 1]);
            
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 0, "description" => "participate in the interviews", "reward" => 100]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge3, "number" => 1, "goal" => 0, "description" => "get four posts of four points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge3, "number" => 2, "goal" => 0, "description" => "get six posts of four points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge3, "number" => 3, "goal" => 0, "description" => "get twelve posts of four points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge4, "number" => 1, "goal" => 0, "description" => "make twenty posts", "reward" => 0]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge4, "number" => 2, "goal" => 0, "description" => "make thirty posts", "reward" => 0]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge4, "number" => 3, "goal" => 0, "description" => "make fifty posts", "reward" => 0]);
        
        //When
        $result = $this->badges->exportItems($courseId);

        //Then
        $expectedFile = "name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n";
        $expectedFile .= "Class Annotator;Find related resources, more information, about class subjects;1;1;1;get four points;300;4;get eight points;300;8;get twelve points;300;12\n";
        $expectedFile .= "Focused;Participate in the Focus Group Interviews;0;0;0;participate in the interviews;-100;;;;;;;\n";
        $expectedFile .= "Artist;Show creativity and quality;1;1;0;get four posts of four points;300;;get six posts of four points;300;;get twelve posts of four points;300;\n";
        $expectedFile .= "Post Master;Post something in the forums;1;1;0;make twenty posts;0;;make thirty posts;0;;make fifty posts;0;";
        
        $this->assertIsArray($result);
        $this->assertEquals("Badges - Multimedia Content Production", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testExportItemsNoBadgesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        //When
        $result = $this->badges->exportItems($courseId);

        //Then
        $expectedFile = "name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n";
        $this->assertIsArray($result);
        $this->assertEquals("Badges - Multimedia Content Production", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testExportItemsInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badge2 = Core::$systemDB->insert("badge", ["name" => "Focused", "course" => $courseId, "description" => "Participate in the Focus Group Interviews", "maxLevel" => 1, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1]);
          
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 0, "description" => "participate in the interviews", "reward" => 100]);
       
        //When
        $result = $this->badges->exportItems($courseId + 1);

        //Then
        $expectedFile = "name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n";
        $this->assertIsArray($result);
        $this->assertEquals("Badges - ", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testExportItemsNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        
        $badge1 = Core::$systemDB->insert("badge", ["name" => "Class Annotator", "course" => $courseId, "description" => "Find related resources, more information, about class subjects", "maxLevel" => 3, "isExtra" => 0, "isBragging" => 0, "isCount" => 1, "isPost" => 1, "isPoint" => 1, "isActive" => 1]);
        $badge2 = Core::$systemDB->insert("badge", ["name" => "Focused", "course" => $courseId, "description" => "Participate in the Focus Group Interviews", "maxLevel" => 1, "isExtra" => 1, "isBragging" => 0, "isCount" => 0, "isPost" => 0, "isPoint" => 0, "isActive" => 1]);
          
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 1, "goal" => 4, "description" => "get four points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 2, "goal" => 8, "description" => "get eight points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge1, "number" => 3, "goal" => 12, "description" => "get twelve points", "reward" => 300]);
        Core::$systemDB->insert("badge_level", ["badgeId" => $badge2, "number" => 1, "goal" => 0, "description" => "participate in the interviews", "reward" => 100]);
       
        //When
        $result = $this->badges->exportItems(null);

        //Then
        $expectedFile = "name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n";
        $this->assertIsArray($result);
        $this->assertEquals("Badges - ", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }



}