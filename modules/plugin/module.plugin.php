<?php

namespace Modules\Plugin;

use GameCourse\Module;
use GameCourse\ModuleLoader;

class Plugin extends Module
{
    private $fenix;
    private $moodle;
    private $classCheck;
    private $googleSheets;

    public function init()
    {
        //Fenix variables
        $courseId = "1971935449711106";

        //Moodle variables
        $dbserver = "localhost"; //"db.rnl.tecnico.ulisboa.pt";
        $dbuser = "root"; //"pcm_moodle";
        $dbpass = ""; //"Dkr1iRwEekJiPSHX9CeNznHlks";
        $db = "moodle"; //"pcm_moodle";
        $dbport = "3306";
        $prefix = "mdl_";
        $time = "1590790100";
        $course = null; //courseId no moodle
        $user = null;

        //ClassCheck variables
        $tsvCode = "f8c691b7fc14a0455386d4cb599958d3";

        //Google sheets variables
        $spreadsheetId = "1N8PKwi3jgQrCA8KJ1KSnj_MDk2-E_d_RWbVfnKzrpgs";//'1gznueqlXB9EK-tesPINJ4g2dxFkZsQoXWZvPsCaG7_U';
        $sheetName = 'Folha1';
        $range = 'A1:B2'; //$range = 'Folha1!A1:B2';

        // if fenix is enabled
        $this->fenix = new Fenix($this);
        $listOfStudents = $this->fenix->getStudents($courseId);
        $this->fenix->writeUsersToDB($listOfStudents);

        //if moodle is enabled
        // $this->addTables("plugin", "moodle_logs", "Logs");
        // $this->addTables("plugin", "moodle_votes", "Votes");
        // $this->addTables("plugin", "moodle_quiz_grades", "QuizGrades");
        // $this->moodle = new Moodle($this);

        // $logs = $this->moodle->getLogs($time, $user, $course, $prefix, $dbserver, $dbuser, $dbpass, $db, $dbport);
        // $this->moodle->writeLogsToDB($logs);

        // $votes = $this->moodle->getVotes($course, $prefix, $dbserver, $dbuser, $dbpass, $db, $dbport);
        // $this->moodle->writeVotesToDb($votes);

        // $quiz_grades = $this->moodle->getQuizGrades($course, $prefix, $dbserver, $dbuser, $dbpass, $db, $dbport);
        // $this->moodle->writeQuizGradesToDb($quiz_grades);

        // //if classcheck is enabled
        // $this->classCheck = new ClassCheck($this);
        // $this->addTables("plugin", "attendance", "Attendance");
        // $this->classCheck->readAttendance($tsvCode);

        //if googleSheets is enabled
        // $this->googleSheets = new GoogleSheets($this);
        // $this->googleSheets->readGoogleSheets($spreadsheetId, $sheetName, $range);
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
