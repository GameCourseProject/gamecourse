<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use Modules\Badges\Badges;

use PHPUnit\Framework\TestCase;


class ModuleBadgesSetupTest extends TestCase
{
    protected $badges;

    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function setUp():void {
        $this->badges = new Badges();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
        Core::$systemDB->executeQuery(
            "drop table if exists badge_level;
            drop table if exists badge_progression;
            drop table if exists badge;
            drop table if exists badges_config;"
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
        $result = $this->badges->addTables(Badges::ID, Badges::TABLE);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertTrue($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
    }

    public function testAddTablesAlreadyExistsFail(){
        
        //Given
        Core::$systemDB->executeQuery(
            "create table badges_config(
                maxBonusReward 	int not null,
                imageExtra varchar(50),
                imageBragging varchar(50),
                imageLevel2 varchar(50),
                imageLevel3 varchar(50),
                course int unsigned primary key,
                foreign key(course) references course(id) on delete cascade
            );
            
            create table badge(
                id 		int unsigned auto_increment primary key,
                name varchar(70) not null,
                course int unsigned not null,
                description  varchar(200) not null,
                maxLevel int not null,
                isExtra boolean not null default false,
                isBragging boolean not null default false,
                isCount boolean not null default false,
                isPost boolean not null default false,
                isPoint boolean not null default false,
                isActive boolean not null default true,
                image varchar(50),
                foreign key(course) references course(id) on delete cascade
            );
            
            create table badge_level(
                id 	        int unsigned auto_increment primary key,
                badgeId 	int unsigned,
                number      int not null,
                goal        int not null,
                description varchar(200),
                reward 		int unsigned,
                foreign key(badgeId) references badge(id) on delete cascade
            );
            
            create table badge_progression(
                course int unsigned not null,
                user int unsigned not null,
                badgeId 	int unsigned,
                participationId 	int unsigned,
                foreign key(course) references course(id) on delete cascade,
                foreign key(user) references game_course_user(id) on delete cascade,
                foreign key(badgeId) references badge(id) on delete cascade,
                foreign key(participationId) references participation(id) on delete cascade
            );"
        );
        
        //When
        $result = $this->badges->addTables("badges", "badge");

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertFalse($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
    }

    
    public function testSetupDataOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        //When
        $this->badges->setupData($courseId);
        $this->badges->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);

        $badgesConfig = Core::$systemDB->selectMultiple(Badges::TABLE_CONFIG);
        $expectedBadgesConfig = array(
            array("maxBonusReward" => MAX_BONUS_BADGES, "course" => $courseId, "imageExtra" => "", "imageBragging" => "" , "imageLevel2" => "", "imageLevel3" => "")
        );
        $this->assertEquals($expectedBadgesConfig, $badgesConfig);

        $this->assertDirectoryExists($folder . "/badges");
        $this->assertDirectoryExists($folder . "/badges/Extra");
        $this->assertDirectoryExists($folder . "/badges/Bragging");
        $this->assertDirectoryExists($folder . "/badges/Level2");
        $this->assertDirectoryExists($folder . "/badges/Level3");
        rmdir($folder . "/badges/Extra");
        rmdir($folder . "/badges/Bragging");
        rmdir($folder . "/badges/Level2");
        rmdir($folder . "/badges/Level3");
        rmdir($folder . "/badges");
        rmdir($folder);
    }

    public function testSetupDataMultipleCallsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        //When
        $this->badges->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);

        $badgesConfig = Core::$systemDB->selectMultiple(Badges::TABLE_CONFIG);
        $expectedBadgesConfig = array(
            array("maxBonusReward" => MAX_BONUS_BADGES, "course" => $courseId, "imageExtra" => "", "imageBragging" => "" , "imageLevel2" => "", "imageLevel3" => "")
        );
        $this->assertEquals($expectedBadgesConfig, $badgesConfig);

        $this->assertDirectoryExists($folder . "/badges");
        $this->assertDirectoryExists($folder . "/badges/Extra");
        $this->assertDirectoryExists($folder . "/badges/Bragging");
        $this->assertDirectoryExists($folder . "/badges/Level2");
        $this->assertDirectoryExists($folder . "/badges/Level3");
        rmdir($folder . "/badges/Extra");
        rmdir($folder . "/badges/Bragging");
        rmdir($folder . "/badges/Level2");
        rmdir($folder . "/badges/Level3");
        rmdir($folder . "/badges");
        rmdir($folder);
    }

    public function testSetupDataExistingTablesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        Core::$systemDB->executeQuery(
            "create table badges_config(
                maxBonusReward 	int not null,
                imageExtra varchar(50),
                imageBragging varchar(50),
                imageLevel2 varchar(50),
                imageLevel3 varchar(50),
                course int unsigned primary key,
                foreign key(course) references course(id) on delete cascade
            );
            
            create table badge(
                id 		int unsigned auto_increment primary key,
                name varchar(70) not null,
                course int unsigned not null,
                description  varchar(200) not null,
                maxLevel int not null,
                isExtra boolean not null default false,
                isBragging boolean not null default false,
                isCount boolean not null default false,
                isPost boolean not null default false,
                isPoint boolean not null default false,
                isActive boolean not null default true,
                image varchar(50),
                foreign key(course) references course(id) on delete cascade
            );
            
            create table badge_level(
                id 	        int unsigned auto_increment primary key,
                badgeId 	int unsigned,
                number      int not null,
                goal        int not null,
                description varchar(200),
                reward 		int unsigned,
                foreign key(badgeId) references badge(id) on delete cascade
            );
            
            create table badge_progression(
                course int unsigned not null,
                user int unsigned not null,
                badgeId 	int unsigned,
                participationId 	int unsigned,
                foreign key(course) references course(id) on delete cascade,
                foreign key(user) references game_course_user(id) on delete cascade,
                foreign key(badgeId) references badge(id) on delete cascade,
                foreign key(participationId) references participation(id) on delete cascade
            );"
        );

        //When
        $this->badges->setupData($courseId);
        $this->badges->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);

        $badgesConfig = Core::$systemDB->selectMultiple(Badges::TABLE_CONFIG);
        $expectedBadgesConfig = array(
            array("maxBonusReward" => MAX_BONUS_BADGES, "course" => $courseId, "imageExtra" => "", "imageBragging" => "" , "imageLevel2" => "", "imageLevel3" => "")
        );
        $this->assertEquals($expectedBadgesConfig, $badgesConfig);

        $this->assertDirectoryExists($folder . "/badges");
        $this->assertDirectoryExists($folder . "/badges/Extra");
        $this->assertDirectoryExists($folder . "/badges/Bragging");
        $this->assertDirectoryExists($folder . "/badges/Level2");
        $this->assertDirectoryExists($folder . "/badges/Level3");
        rmdir($folder . "/badges/Extra");
        rmdir($folder . "/badges/Bragging");
        rmdir($folder . "/badges/Level2");
        rmdir($folder . "/badges/Level3");
        rmdir($folder . "/badges");
        rmdir($folder);
    }

    public function testSetupDataExistingDirectoriesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);
        mkdir($folder . "/badges");
        mkdir($folder . "/badges" . "/Extra");
        mkdir($folder . "/badges" . "/Bragging");
        mkdir($folder . "/badges" . "/Level2");
        mkdir($folder . "/badges" . "/Level3");

        //When
        $this->badges->setupData($courseId);
        $this->badges->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);

        $badgesConfig = Core::$systemDB->selectMultiple(Badges::TABLE_CONFIG);
        $expectedBadgesConfig = array(
            array("maxBonusReward" => MAX_BONUS_BADGES, "course" => $courseId, "imageExtra" => "", "imageBragging" => "" , "imageLevel2" => "", "imageLevel3" => "")
        );
        $this->assertEquals($expectedBadgesConfig, $badgesConfig);

        $this->assertDirectoryExists($folder . "/badges");
        $this->assertDirectoryExists($folder . "/badges/Extra");
        $this->assertDirectoryExists($folder . "/badges/Bragging");
        $this->assertDirectoryExists($folder . "/badges/Level2");
        $this->assertDirectoryExists($folder . "/badges/Level3");
        rmdir($folder . "/badges/Extra");
        rmdir($folder . "/badges/Bragging");
        rmdir($folder . "/badges/Level2");
        rmdir($folder . "/badges/Level3");
        rmdir($folder . "/badges");
        rmdir($folder);
    }

    public function testSetupDataTwoCoursesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder1 = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder1);

        $courseId2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $folder2 = COURSE_DATA_FOLDER . '/' . $courseId2 . '-' . "Forensics CyberSecurity";
        mkdir($folder2);


        //When
        $this->badges->setupData($courseId);
        $this->badges->setupData($courseId2);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);

        
        $badgesConfig = Core::$systemDB->selectMultiple(Badges::TABLE_CONFIG);
        $expectedBadgesConfig = array(
            array("maxBonusReward" => MAX_BONUS_BADGES, "course" => $courseId, "imageExtra" => "", "imageBragging" => "" , "imageLevel2" => "", "imageLevel3" => ""),
            array("maxBonusReward" => MAX_BONUS_BADGES, "course" => $courseId2, "imageExtra" => "", "imageBragging" => "" , "imageLevel2" => "", "imageLevel3" => "")
        );
        $this->assertEquals($expectedBadgesConfig, $badgesConfig);

        $this->assertDirectoryExists($folder1 . "/badges");
        $this->assertDirectoryExists($folder1 . "/badges/Extra");
        $this->assertDirectoryExists($folder1 . "/badges/Bragging");
        $this->assertDirectoryExists($folder1 . "/badges/Level2");
        $this->assertDirectoryExists($folder1 . "/badges/Level3");
        $this->assertDirectoryExists($folder2 . "/badges");
        $this->assertDirectoryExists($folder2 . "/badges/Extra");
        $this->assertDirectoryExists($folder2 . "/badges/Bragging");
        $this->assertDirectoryExists($folder2 . "/badges/Level2");
        $this->assertDirectoryExists($folder2 . "/badges/Level3");
        
        rmdir($folder1 . "/badges/Extra");
        rmdir($folder1 . "/badges/Bragging");
        rmdir($folder1 . "/badges/Level2");
        rmdir($folder1 . "/badges/Level3");
        rmdir($folder1 . "/badges");
        rmdir($folder1);
        rmdir($folder2 . "/badges/Extra");
        rmdir($folder2 . "/badges/Bragging");
        rmdir($folder2 . "/badges/Level2");
        rmdir($folder2 . "/badges/Level3");
        rmdir($folder2 . "/badges");
        rmdir($folder2);
    }

    /**
     * @dataProvider setupDataFailProvider
     */
    public function testSetupDataFail($courseId){
        
        try {

            $this->badges->setupData($courseId);
            $this->fail("PDOException should have been thrown for invalid argument on setupData.");

        } catch (\PDOException $e) {
            $table1 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_CONFIG . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table2 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table3 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_LEVEL . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table4 = Core::$systemDB->executeQuery("show tables like '" . Badges::TABLE_PROGRESSION . "';")->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->assertCount(1, $table1);
            $this->assertCount(1, $table2);
            $this->assertCount(1, $table3);
            $this->assertCount(1, $table4);

            $badgesConfig = Core::$systemDB->selectMultiple(Badges::TABLE_CONFIG);
            $this->assertEmpty($badgesConfig);
        }

    }

}