<?php
if (!defined('CONNECTION_STRING'))
    return;

use \SmartBoards\User;
use \MagicDB\MagicDB;

if (array_key_exists('setup', $_GET) && array_key_exists('course-name', $_POST) && array_key_exists('teacher-id', $_POST)) {
    $courseName = $_POST['course-name'];
    $teacherId = $_POST['teacher-id'];
    $teacherUsername = $_POST['teacher-username'];

    $config = new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'config');
    $config->set('active-courses', array(0));
    $config->set('courses', array($courseName));
    $config->set('theme', 'default');
    $config->set('pending-invites', array($teacherUsername => array('id' => $teacherId, 'username' => $teacherUsername, 'isAdmin' => true)));

    $course = (new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'courses'))->get(0);
    $course->set('name', $courseName);
    $course->set('headerLink', '');
    $course->set('defaultRoleSettings', array('landingPage' => ''));
    $course->set('roles', array('Teacher', 'Student'));
    $course->set('rolesSettings', array(
            'Teacher' => array('landingPage' => '/'),
            'Student' => array('landingPage' => '/'))
    );
    $course->set('rolesHierarchy', array(
        array('name' => 'Teacher'),
        array('name' => 'Student')));
    $course->set('modules', array());
    $course->set('users', array($teacherId => array('id' => $teacherId, 'name' => 'Teacher', 'roles' => array('Teacher'), 'data' => array())));

    file_put_contents('setup.done','');
    //User::getUser($teacherId)->initialize('Teacher', 'teacher@smartboards')->setAdmin(true);

    unset($_SESSION['user']); // if the user was logged and the config folder was destroyed..
    return 'setup-done';
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1">
        <base href="<?php echo Utils::createBase(); ?>" target="_blank">
        <title>SmartBoards</title>
        <link rel="stylesheet" type="text/css" href="css/simple-page.css" />
    </head>
    <body>
        <div class="big-title">SmartBoards</div>
        <form class="middle-box" action="?setup" method="post" target="_self">
            <div class="header">First time setup!</div>
            <div class="content">
                <h2>Create a course!</h2>
                <label for="course-name" class="label">Course Name</label>
                <input type="text" id="course-name" class="input-text" name="course-name" placeholder="(ex: PCM 2015/2016)" required><br>
                <label for="teacher-id" class="label">Teacher IST ID</label>
                <input type="number" id="teacher-id" class="input-number" name="teacher-id" placeholder="(ex: 12345)" min="0" max="999999" required><br>
                <label for="teacher-username" class="label">Teacher Username</label>
                <input type="text" id="teacher-username" class="input-text" name="teacher-username" placeholder="(ex: ist112345)" required><br>
            </div>
            <div class="footer"><input type="submit" class="button big" value="Finish!"></div>
        </form>
    </body>
</html>
