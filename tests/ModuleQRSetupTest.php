<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use Modules\QR\QR;

use PHPUnit\Framework\TestCase;


class ModuleQRSetupTest extends TestCase
{
    protected $qr;

    public static function setUpBeforeClass():void {
        Core::init();
    }

    protected function setUp():void {
        $this->qr = new QR();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
        Core::$systemDB->executeQuery(
            "drop table if exists qr_code;
            drop table if exists qr_error;"
        );
    }

    public function testAddTablesSuccess(){

        //When
        $result = $this->qr->addTables("qr", "qr_code");

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'qr_code';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'qr_error';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertTrue($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
    }

    public function testAddTablesAlreadyExistsFail(){
        
        //Given
        Core::$systemDB->executeQuery(
            "create table qr_code(
                qrkey varchar(50) not null,
                course int unsigned not null,
                studentNumber int unsigned,
                classNumber int,
                classType  varchar(50),
                foreign key (course) references course(id) on delete cascade
            );
            create table qr_error(
                user int unsigned not null,
                course  int unsigned not null,
                ip varchar(50),
                qrkey varchar(50), 
                msg varchar(500),
                date timestamp default CURRENT_TIMESTAMP,
                foreign key (course) references course(id) on delete cascade,
                foreign key (user) references game_course_user(id) on delete cascade
            );"
        );
        
        //When
        $result = $this->qr->addTables("qr", "qr_code");

        //Then
        $table1 = Core::$systemDB->executeQuery("show tables like 'qr_code';")->fetchAll(\PDO::FETCH_ASSOC);
        $table2 = Core::$systemDB->executeQuery("show tables like 'qr_error';")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertFalse($result);
        $this->assertCount(1, $table1);
        $this->assertCount(1, $table2);
    }

    
}