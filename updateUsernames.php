<?php
$courseUrls = array('https://fenix.tecnico.ulisboa.pt/disciplinas/PCM26/2017-2018/2-semestre/notas');
$BACKENDID = '';
$JSESSIONID = '';
include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\Course;
use \SmartBoards\User;

Core::init();
$isCLI = Core::isCLI();

if(!Core::requireSetup(false))
    die('Please perform setup first!');

if ($isCLI) {
    $courseId = (array_key_exists(1, $argv) ? $argv[1] : 1);
    $id = 2;
    while(array_key_exists($id, $argv)) {
        $courseUrls[] = $argv[$id];
        $id++;
    }
} else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 1);
    $id = 1;
    while(array_key_exists('courseurl' . $id, $_GET)) {
        $courseUrls[] = $_GET['courseurl' . $id];
        $id++;
    }
    if (array_key_exists('backendid', $_GET))
        $BACKENDID = $_GET['backendid'];
    if (array_key_exists('jsessionid', $_GET))
        $JSESSIONID = $_GET['jsessionid'];

}

$course = Course::getCourse($courseId);
$userIds = $course->getUsersIds();

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: JSESSIONID=' . $JSESSIONID . ';BACKENDID=' . $BACKENDID));

foreach($courseUrls as $url) {
    curl_setopt($ch, CURLOPT_URL, $url);
    $response = curl_exec($ch);

    if ($response === false) {
        die(curl_error($ch));
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    $dom = new DOMDocument(5, 'UTF-8');
    @$dom->loadHTML($body);
    $studentsTable = $dom->getElementsByTagName('table')[0];
    if ($studentsTable==null){
        echo "ERROR: Couldn't find user table, check if cookies are updated <br>";
        break;
    }
    foreach ($studentsTable->getElementsByTagName('tr') as $row) {
        $username = $row->childNodes[0]->nodeValue;
        $studentNumber = $row->childNodes[2]->nodeValue;
       
        if (preg_match('/^ist[0-9]{6}$/', $username)) {
            $user = User::getUser($studentNumber);
            if (!$user->exists())
                echo 'Student ' . $studentNumber . ' is registered on the fenix course, but not on smartboards.'. ($isCLI ? "\n" :  '<br>');
            else {
                $user->setUsername($username);
                echo 'Updated username for ' . $studentNumber. ($isCLI ? "\n" :  '<br>');;
            }
        }
    }
}

foreach($userIds as $id) {
    $user = User::getUser($id);
    if ($user->getUsername() == null) {
        if ($id < 100000) {
            echo 'Guessing username for user ' . $id . ' as ist1' . $id . ($isCLI ? "\n" :  '<br>');
            $user->setUsername('ist1' . $id);
        } else {
            echo 'ERROR: Can not get username for user ' . $id . ', please insert username manually, so this person can login in the system.' . ($isCLI ? "\n" :  '<br>');
        }
    }
}

curl_close($ch);
?>