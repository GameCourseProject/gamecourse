<?php
//$BACKENDID = 'backend_CbTA8|W6Iz/|W6ImP';
$BACKENDID = 'backend_CbTA8|W/MbZ|W/MWC';
//$JSESSIONID = '35CC1D3C136267F95D40398712725EBD.as2';
$JSESSIONID = '11C6DDFFA9700E5893DD8A4D68EB9A55.as2';
include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\Course;
use \SmartBoards\User;

$isCLI = Core::isCLI();

if(!Core::requireSetup(false))
    die('Please perform setup first!');

Core::init();

$noPhotoHash = md5(file_get_contents('photos/no-photo.png'));

if ($isCLI) {
    $courseId = (array_key_exists(1, $argv) ? $argv[1] : 0);
    if (array_key_exists(2, $argv))
        $BACKENDID = $argv[2];
    if (array_key_exists(3, $argv))
        $JSESSIONID = $argv[3];

} else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 0);
    if (array_key_exists('backendid', $_GET))
        $BACKENDID = $_GET['backendid'];
    if (array_key_exists('jsessionid', $_GET))
        $JSESSIONID = $_GET['jsessionid'];
}

$course = Course::getCourse($courseId);
$users = $course->getUsers()->getKeys();

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: JSESSIONID=' . $JSESSIONID . ';BACKENDID=' . $BACKENDID));

foreach($users as $id) {
    $username = User::getUser($id)->getUsername();
    if ($username != null) {
        curl_setopt($ch, CURLOPT_URL, 'https://fenix.tecnico.ulisboa.pt/user/photo/' . $username);
        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        if ($response === false) {
            die('CURL ERROR: ' . curl_error($ch) . ($isCLI ? "\n" :  '<br>'));
        } else if (strpos($header, '404 Not Found') != 0) {
            echo 'Photo for ' . $id . ' do not found. Do you have the right username?? (' . $username . ') (' . $users->getWrapped($id)->get('name') . ') ' . ($isCLI ? "\n" :  '<br>');
            continue;
        }

        if (!file_exists('photos/'))
            mkdir('photos/', 0777, true);

        $photoPath = 'photos/' . $username . '.png';
        
        // ignore photo, if new photo is a no-photo, and user already has a photo
        if (file_exists($photoPath) && (md5($body) == $noPhotoHash))
            continue;
        file_put_contents($photoPath, $body);
    }
}
echo 'Done';
curl_close($ch);
?>