<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use Modules\Skills\Skills;

use PHPUnit\Framework\TestCase;


class ModuleSkillsSetupTest extends TestCase
{
    protected $skills;

    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function setUp():void {
        $this->skills = new Skills();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
        Core::$systemDB->executeQuery(
            "drop table if exists skill_dependency;
            drop table if exists dependency;
            drop table if exists skill;
            drop table if exists award_wildcard;
            drop table if exists skill_tier;
            drop table if exists skill_tree;"
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
        $result = $this->skills->addTables(Skills::ID, Skills::TABLE);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertTrue($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
        $this->assertCount(1, $table5);
        $this->assertCount(1, $table6);
    }

    public function testAddTablesAlreadyExistsFail(){
        
        //Given
        Core::$systemDB->executeQuery(
            "create table skill_tree(
                id 		int unsigned auto_increment primary key,
                course int unsigned not null,
                maxReward int unsigned,
                foreign key(course) references course(id) on delete cascade
            );
            create table skill_tier(
                id 	int unsigned auto_increment unique,
                tier varchar(50) not null,
                seqId int unsigned not null,
                reward int unsigned not null,
                treeId int unsigned not null,
                primary key(treeId,tier),
                foreign key(treeId) references skill_tree(id) on delete cascade
            );
            create table skill(
                id 	int unsigned auto_increment primary key,
                seqId int unsigned not null,
                name varchar(50) not null,
                color varchar(10),
                page TEXT,
                tier varchar(50) not null,
                treeId int unsigned not null,
                isActive boolean not null default true,
                foreign key(treeId,tier) references skill_tier(treeId, tier) on delete cascade
            );"
        );
        
        //When
        $result = $this->skills->addTables(Skills::ID, Skills::TABLE);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertFalse($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertEmpty($table4);
        $this->assertEmpty($table5);
        $this->assertEmpty($table6);
    }

    public function testSetupDataOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        //When
        $this->skills->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
        $this->assertCount(1, $table5);
        $this->assertCount(1, $table6);

        $skillTrees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES);
        $expectedSkillTrees = array(
            array("id" => $skillTrees[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedSkillTrees, $skillTrees);

        $this->assertDirectoryExists($folder . "/skills");
        rmdir($folder . "/skills");
        rmdir($folder);
    }

    public function testSetupDataMultipleCallsSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        //When
        $this->skills->setupData($courseId);
        $this->skills->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
        $this->assertCount(1, $table5);
        $this->assertCount(1, $table6);

        $skillTrees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES);
        $expectedSkillTrees = array(
            array("id" => $skillTrees[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedSkillTrees, $skillTrees);

        $this->assertDirectoryExists($folder . "/skills");
        rmdir($folder . "/skills");
        rmdir($folder);
    }

    public function testSetupDataExistingTablesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);

        Core::$systemDB->executeQuery(
            "create table skill_tree(
                id 		int unsigned auto_increment primary key,
                course int unsigned not null,
                maxReward int unsigned,
                foreign key(course) references course(id) on delete cascade
            );
            create table skill_tier(
                id 	int unsigned auto_increment unique,
                tier varchar(50) not null,
                seqId int unsigned not null,
                reward int unsigned not null,
                treeId int unsigned not null,
                primary key(treeId,tier),
                foreign key(treeId) references skill_tree(id) on delete cascade
            );
            create table skill(
                id 	int unsigned auto_increment primary key,
                seqId int unsigned not null,
                name varchar(50) not null,
                color varchar(10),
                page TEXT,
                tier varchar(50) not null,
                treeId int unsigned not null,
                isActive boolean not null default true,
                foreign key(treeId,tier) references skill_tier(treeId, tier) on delete cascade
            );
            create table dependency(
                id 	int unsigned auto_increment primary key,
                superSkillId int unsigned not null,
                foreign key(superSkillId) references skill(id) on delete cascade
            );
            create table skill_dependency(
                dependencyId int unsigned not null,
                normalSkillId int unsigned not null,
                isTier boolean not null default false,
                primary key(dependencyId, normalSkillId, isTier),
                foreign key (dependencyId) references dependency(id) on delete cascade
            );
            create table award_wildcard(
                awardId int unsigned not null,
                tierId int unsigned not null,
                primary key(awardId,tierId),
                foreign key (awardId) references award(id) on delete cascade,
                foreign key (tierId) references skill_tier(id) on delete cascade
            );"
        );
        
        //When
        $this->skills->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
        $this->assertCount(1, $table5);
        $this->assertCount(1, $table6);

        $skillTrees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES);
        $expectedSkillTrees = array(
            array("id" => $skillTrees[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedSkillTrees, $skillTrees);

        $this->assertDirectoryExists($folder . "/skills");
        rmdir($folder . "/skills");
        rmdir($folder);
    }

    public function testSetupDataExistingSKillsDirectorySuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        mkdir($folder);
        mkdir($folder . "/skills");

        //When
        $this->skills->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
        $this->assertCount(1, $table5);
        $this->assertCount(1, $table6);

        $skillTrees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES);
        $expectedSkillTrees = array(
            array("id" => $skillTrees[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedSkillTrees, $skillTrees);

        $this->assertDirectoryExists($folder . "/skills");
        rmdir($folder . "/skills");
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
        $this->skills->setupData($courseId);
        $this->skills->setupData($courseId2);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
        $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
        $this->assertCount(1, $table4);
        $this->assertCount(1, $table5);
        $this->assertCount(1, $table6);
        
        $skillTrees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES);
        $expectedSkillTrees = array(
            array("id" => $skillTrees[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP),
            array("id" => $skillTrees[1]["id"], "course" => $courseId2, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedSkillTrees, $skillTrees);

        $this->assertDirectoryExists($folder1 . "/skills");
        $this->assertDirectoryExists($folder2 . "/skills");
        rmdir($folder1 . "/skills");
        rmdir($folder1);
        rmdir($folder2 . "/skills");
        rmdir($folder2);
    }

    /**
     * @dataProvider setupDataFailProvider
     */
    public function testSetupDataFail($courseId){
        
        try {

            $this->skills->setupData($courseId);
            $this->fail("PDOException should have been thrown for invalid argument on setupData.");

        } catch (\PDOException $e) {
            $table1 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TREES . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table2 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_TIERS . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table3 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table4 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_SUPER_SKILLS . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table5 = Core::$systemDB->executeQuery("show tables like '" . Skills::TABLE_DEPENDENCIES . "';")->fetchAll(\PDO::FETCH_ASSOC);
            $table6 = Core::$systemDB->executeQuery("show tables like 'award_wildcard';")->fetchAll(\PDO::FETCH_ASSOC);
    
            $this->assertCount(1, $table1);
            $this->assertCount(1, $table2);
            $this->assertCount(1, $table3);
            $this->assertCount(1, $table4);
            $this->assertCount(1, $table5);
            $this->assertCount(1, $table6);

            $skillTree = Core::$systemDB->selectMultiple(Skills::TABLE_TREES);
            $this->assertEmpty($skillTree);

        }
        
    }
}