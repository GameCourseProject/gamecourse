<?php

namespace Modules\Plugin;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;

class Plugin extends Module
{
    private $fenix;
    private $moodle;
    private $classCheck;
    private $googleSheets;

    //passo 1 criacao da(s) tabela(s) com a info a registar
    //passo 2 substituir nas funcoes gets o acesso a variavel local pelo acesso a DB
    //passo 3 substituir nas funcoes sets o registo na variavel local pelo registo na DB
    //any question just ask and I'll help ^^


    //Fenix variables
    private $fenixCourseId = "1971935449711106";
    //Moodle variables
    private $dbserver = "localhost"; //"db.rnl.tecnico.ulisboa.pt";
    private $dbuser = "root"; //"pcm_moodle";
    private $dbpass = ""; //"Dkr1iRwEekJiPSHX9CeNznHlks";
    private $db = "moodle"; //"pcm_moodle";
    private $dbport = "3306";
    private $prefix = "mdl_";
    private $time = "1590790100";
    private $course = null; //courseId no moodle
    private $user = null;
    //ClassCheck variables
    private $tsvCode = "f8c691b7fc14a0455386d4cb599958d3";
    //Google sheets variables
    private $spreadsheetId = "1N8PKwi3jgQrCA8KJ1KSnj_MDk2-E_d_RWbVfnKzrpgs";//'1gznueqlXB9EK-tesPINJ4g2dxFkZsQoXWZvPsCaG7_U';
    private $sheetName = 'Folha1';
    private $range = 'A1:B2'; //$range = 'Folha1!A1:B2';
    

    //substituir o resultado do return por pedido à DB para ir buscar estes valores
    //this info will later come from the DB, something like:
    //$badges = Core::$systemDB->selectMultiple("badge",["course"=>$courseId],"*", "name");
    //function structure:
    //selectMultiple($table,$where=null,$field='*',$orderBy=null,$whereNot=[],$whereCompare=[],$group=null,$likeParams=null)
    private function getFenixVars(){
        $fenixVars = ["fenixCourseId" => $this->fenixCourseId]; 
        return $fenixVars;
    }
    private function getMoodleVars(){
        $moodleVars= [
            "dbserver" => $this->dbserver,
            "dbuser" => $this->dbuser,
            "dbpass" => $this->dbpass,
            "db" => $this->db,
            "dbport" => $this->dbport,
            "prefix" => $this->prefix,
            "time" => $this->time,
            "course" => $this->course,
            "user" => $this->user 
        ];
        return $moodleVars;
    }
    private function getClassCheckVars(){
        $classCheckVars = ["tsvCode" => $this->tsvCode];
        return  $classCheckVars;
    }
    private function getGoogleSheetsVars(){
        $googleSheetsVars= [
            "spreadsheetId" => $this->spreadsheetId,
            "sheetName" => $this->sheetName,
            "range" => $this->range
        ];
        return $googleSheetsVars;
    }

    //substituir cada uma das linhas de set da variavel local pelo pedido a DB para guardar
    //o update e feito por variavel

    //save on DB using someting like this:
    //Core::$systemDB->update("badges_config",["maxBonusReward"=>$max],["course"=>$courseId]);
    //function structure:
    //function updateAdd($table,$collumQuantity,$where,$whereNot=[],$whereCompare=[])
    private function setFenixVars($fenix){
        $this->fenixCourseId = $fenix['fenixCourseId'];
        return $this->fenixCourseId;
    }
    private function setMoodleVars($moodle){
         $this->dbserver = $moodle['dbserver']; 
         $this->dbuser = $moodle['dbuser'];
         $this->dbpass = $moodle['dbpass'];
         $this->db = $moodle['db'];
         $this->dbport = $moodle['dbport'];
         $this->prefix = $moodle['prefix'];
         $this->time = $moodle['time'];
         $this->course = $moodle['course'];
         $this->user = $moodle['user'];
    }
    private function setClassCheckVars($classCheck){
        $this->tsvCode = $classCheck['tsvCode'];
    }
    private function setGoogleSheetsVars($googleSheets){
        $this->spreadsheetId = $googleSheets["spreadsheetId"];
        $this->sheetName = $googleSheets["sheetName"];
        $this->range = $googleSheets["range"];
    }



    public function setupResources() {
        parent::addResources('js/');
        //parent::addResources('css/plugins.css');
    }
    public function init()
    {

        // if fenix is enabled
        $this->fenix = new Fenix($this);
        $listOfStudents = $this->fenix->getStudents($this->fenixCourseId);
        $this->fenix->writeUsersToDB($listOfStudents);

        //if moodle is enabled
        // $this->addTables("plugin", "moodle_logs", "Logs");
        // $this->addTables("plugin", "moodle_votes", "Votes");
        // $this->addTables("plugin", "moodle_quiz_grades", "QuizGrades");
        // $this->moodle = new Moodle($this);

        // $logs = $this->moodle->getLogs($this->$time, $this->$user, $this->$course, $this->$prefix, $this->$dbserver, $this->$dbuser, $this->$dbpass, $this->$db, $this->$dbport);
        // $this->moodle->writeLogsToDB($logs);

        // $votes = $this->moodle->getVotes($this->$course, $this->$prefix, $this->$dbserver, $this->$dbuser, $this->$dbpass, $this->$db, $this->$dbport);
        // $this->moodle->writeVotesToDb($votes);

        // $quiz_grades = $this->moodle->getQuizGrades($this->$course, $this->$prefix, $this->$dbserver, $this->$dbuser, $this->$dbpass, $this->$db, $this->$dbport);
        // $this->moodle->writeQuizGradesToDb($quiz_grades);


        // //if classcheck is enabled
        // $this->classCheck = new ClassCheck($this);
        // $this->addTables("plugin", "attendance", "Attendance");
        // $this->classCheck->readAttendance($this->$tsvCode);


        //if googleSheets is enabled
        // $this->googleSheets = new GoogleSheets($this);
        // $this->googleSheets->readGoogleSheets($this->$spreadsheetId, $this->$sheetName, $this->$range);

        
        //do not touch bellow
        //settings page
        API::registerFunction('settings', 'coursePlugin', function() {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            
            if (API::hasKey('fenix')){
                $fenix = API::getValue('fenix');
                //place to verify input values
                $newVale = Plugin::setFenixVars($fenix);
                $answer = "Variables for fenix saved, new value: ". $newVale;
                API::response(["updatedData"=>[$answer] ] );
                return;
            }
            if (API::hasKey('moodle')) {
                $moodle = API::getValue('moodle');
                //place to verify input values
                Plugin::setMoodleVars($moodle);
                API::response(["updatedData"=>["Variables for moodle saved"] ]);
                return;
            }
            if (API::hasKey('classCheck')) {
                $classCheck = API::getValue('classCheck');
                //place to verify input values
                Plugin::setClassCheckVars($classCheck);
                API::response(["updatedData"=>["Variables for Class check saved"] ]);
                return;
            }
            if (API::hasKey('googleSheets')) {
                $googleSheets = API::getValue('googleSheets');
                //place to verify input values
                Plugin::setGoogleSheetsVars($googleSheets);
                API::response(["updatedData"=>["Variables for Google Sheets saved"] ]);
                return;
            }
            
            //All variables
            $fenixVars = Plugin::getFenixVars();
            $moodleVars= Plugin::getMoodleVars();
            $classCheckVars = Plugin::getClassCheckVars();
            $googleSheetsVars= Plugin::getGoogleSheetsVars();

            API::response(array('fenixVars' => $fenixVars, 'moodleVars' => $moodleVars, 'classCheckVars' => $classCheckVars, 'googleSheetsVars' => $googleSheetsVars));
        });
    }
}
ModuleLoader::registerModule(array(
    'id' => 'plugin',
    'name' => 'Plugin',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Plugin();
    }
));
