<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use Modules\Profiling\Profiling;

use PHPUnit\Framework\TestCase;


class ModuleProfilingSetupTest extends TestCase
{
    protected $profiling;

    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function setUp():void {
        $this->profiling = new Profiling();
    }

    protected function tearDown():void {
        Core::$systemDB->delete("course", [], null, [["id", 0]]);
        Core::$systemDB->delete("game_course_user", [], null, [["id", 0]]);
        Core::$systemDB->executeQuery(
            "drop table if exists profiling_config; 
             drop table if exists user_profile; 
             drop table if exists saved_user_profile;"
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
        $result = $this->profiling->addTables("profiling", "profiling_config");

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertTrue($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);
    }

    public function testAddTablesAlreadyExistsFail(){
        
        //Given
        Core::$systemDB->executeQuery(
            "create table profiling_config(
                lastRun timestamp NULL,
                course int unsigned primary key,
                foreign key(course) references course(id) on delete cascade
            );"
        );
        
        //When
        $result = $this->profiling->addTables("profiling", "profiling_config");

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertFalse($result);
        $this->assertCount(1, $table1);
        $this->assertEmpty($table2);
        $this->assertEmpty($table3);
    }

    public function testSetupDataOnlyCourseSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]]) ]);
        Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $this->profiling->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);

        $profilingRow = Core::$systemDB->select("profiling_config", ["course" => $courseId]);
        $expectedProfilingRow = array("course" => $courseId, "lastRun" => null);
        $this->assertEquals($expectedProfilingRow, $profilingRow);

        $newRole = Core::$systemDB->select("role", ["course" => $courseId, "name" => "Profiling"]);
        $expectedNewRole = array("id" => $newRole["id"], "name" => "Profiling", "course" => $courseId, "landingPage" => null);
        $this->assertEquals($expectedNewRole, $newRole);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        
        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);

    }

    public function testSetupDataTwoCoursesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]]) ]);
        Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        $courseId2 = Core::$systemDB->insert("course", ["name" => "Software Testing and Validation", "short" => "STV", "year" => "2018-2019", "color" => "#c180d1", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]]) ]);
        Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId2]);

        //When
        $this->profiling->setupData($courseId);
        $this->profiling->setupData($courseId2);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);

        $profilingRows = Core::$systemDB->selectMultiple("profiling_config");
        $expectedProfilingRows = array(
            array("course" => $courseId, "lastRun" => null),
            array("course" => $courseId2, "lastRun" => null)
        );
        $this->assertEquals($expectedProfilingRows, $profilingRows);

        $newRoles = Core::$systemDB->selectMultiple("role", ["name" => "Profiling"]);
        $expectedNewRoles = array(
            array("id" => $newRoles[0]["id"], "name" => "Profiling", "course" => $courseId, "landingPage" => null),
            array("id" => $newRoles[1]["id"], "name" => "Profiling", "course" => $courseId2, "landingPage" => null)
        );
        $this->assertEquals($expectedNewRoles, $newRoles);

        $newHierarchy1 = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $newHierarchy2 = json_decode(Core::$systemDB->select("course", ["id" => $courseId2], "roleHierarchy"));
        
        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy1);
        $this->assertEquals($expectedHierarchy, $newHierarchy2);
    }

    public function testSetupDataExixtingTablesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]]) ]);
        Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        Core::$systemDB->executeQuery(
            "create table profiling_config(
                lastRun timestamp NULL,
                course int unsigned primary key,
                foreign key(course) references course(id) on delete cascade
            );"
        );
        Core::$systemDB->executeQuery(
            "create table user_profile(
                user int unsigned not null,
                course int unsigned not null,
                date timestamp default CURRENT_TIMESTAMP,
                cluster int unsigned not null,
                primary key(user, date, cluster),
                foreign key(cluster) references role(id) on delete cascade,
                foreign key(user, course) references course_user(id, course) on delete cascade
            );"
        );
        Core::$systemDB->executeQuery(
            "create table saved_user_profile(
                user int unsigned not null,
                course int unsigned not null,
                cluster varchar(50) not null,
                primary key(user, course),
                foreign key(user, course) references course_user(id, course) on delete cascade
            );"
        );

        //When
        $this->profiling->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);

        $profilingRow = Core::$systemDB->select("profiling_config", ["course" => $courseId]);
        $expectedProfilingRow = array("course" => $courseId, "lastRun" => null);
        $this->assertEquals($expectedProfilingRow, $profilingRow);

        $newRole = Core::$systemDB->select("role", ["course" => $courseId, "name" => "Profiling"]);
        $expectedNewRole = array("id" => $newRole["id"], "name" => "Profiling", "course" => $courseId, "landingPage" => null);
        $this->assertEquals($expectedNewRole, $newRole);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        
        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);

    }

    public function testSetupDataExistingRoleSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]]) ]);
        Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);
        $profilingId = Core::$systemDB->insert("role", ["name" => "Profiling", "course" => $courseId]);

        //When
        $this->profiling->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);

        $profilingRow = Core::$systemDB->select("profiling_config", ["course" => $courseId]);
        $expectedProfilingRow = array("course" => $courseId, "lastRun" => null);
        $this->assertEquals($expectedProfilingRow, $profilingRow);

        $newRole = Core::$systemDB->select("role", ["course" => $courseId, "name" => "Profiling"]);
        $expectedNewRole = array("id" => $profilingId, "name" => "Profiling", "course" => $courseId, "landingPage" => null);
        $this->assertEquals($expectedNewRole, $newRole);

    }

    public function testSetupDataMultipleCallsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1, "roleHierarchy" => json_encode([["name" => "Student"]]) ]);
        Core::$systemDB->insert("role", ["name" => "Student", "course" => $courseId]);

        //When
        $this->profiling->setupData($courseId);
        $this->profiling->setupData($courseId);

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
        $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
        $this->assertCount(1, $table3);

        $profilingRow = Core::$systemDB->select("profiling_config", ["course" => $courseId]);
        $expectedProfilingRow = array("course" => $courseId, "lastRun" => null);
        $this->assertEquals($expectedProfilingRow, $profilingRow);

        $newRole = Core::$systemDB->select("role", ["course" => $courseId, "name" => "Profiling"]);
        $expectedNewRole = array("id" => $newRole["id"], "name" => "Profiling", "course" => $courseId, "landingPage" => null);
        $this->assertEquals($expectedNewRole, $newRole);

        $newHierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        
        $expectedProfilingRole = new stdClass;
        $expectedProfilingRole->name = 'Profiling';
        $expectedStudentRole = new stdClass;
        $expectedStudentRole->name = 'Student';
        $expectedStudentRole->children = array($expectedProfilingRole);
        $expectedHierarchy = array($expectedStudentRole);

        $this->assertEquals($expectedHierarchy, $newHierarchy);

    }

    /**
     * @dataProvider setupDataFailProvider
     */
    public function testSetupDataFail($courseId){
        
        try {

            $this->profiling->setupData($courseId);
            $this->fail("PDOException should have been thrown for invalid argument on setupData.");

        } catch (\PDOException $e) {
            $table1 = Core::$systemDB->executeQuery("show tables like 'profiling_config';")->fetchAll(\PDO::FETCH_ASSOC);
            $table2 = Core::$systemDB->executeQuery("show tables like 'user_profile';")->fetchAll(\PDO::FETCH_ASSOC);
            $table3 = Core::$systemDB->executeQuery("show tables like 'saved_user_profile';")->fetchAll(\PDO::FETCH_ASSOC);

            $this->assertCount(1, $table1);
            $this->assertCount(1, $table2);
            $this->assertCount(1, $table3);

            $profilingRow = Core::$systemDB->selectMultiple("profiling_config");
            $this->assertEmpty($profilingRow);

        }
        
    }

}