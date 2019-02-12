<?php
if (!defined('CONNECTION_STRING'))
    return;

use \SmartBoards\User;
use \MagicDB\MagicDB;
use \MagicDB\SQLDB;
use SmartBoards\Core;

if (array_key_exists('setup', $_GET) && array_key_exists('course-name', $_POST) && array_key_exists('teacher-id', $_POST)) {
    $courseName = $_POST['course-name'];
    $teacherId = $_POST['teacher-id'];
    $teacherUsername = $_POST['teacher-username'];
 
    $db = new SQLDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD);
    $sql = file_get_contents("gamecourse.sql"); 
    $db->executeQuery($sql);
    
    //Core::$active_courses = [1];
    //Core::$courses = [1=>['name' => $courseName, 'id' => 1, 'active' => true]];
    Core::$pending_invites = [$teacherUsername => ['id' => $teacherId, 'username' => $teacherUsername, 'isAdmin' => true]];
    
    $db->insert("course",["name" => $courseName]);
    
    //if you wish to remove these roles you should also remove them from .sql on course_user table
    $db->insert("role",["name"=>"Teacher","hierarchy" => 1,"course" =>1]);
    $db->insert("role",["name"=>"Student","hierarchy" => 2,"course" =>1]);

    
    $db->insert("user",["id" => $teacherId,
                        "name" => "Teacher",
                        "username"=> $teacherUsername,
                        "isAdmin"=> true]);
    $db->insert("course_user",["id" => $teacherId,
                                "course" => 1,
                              "roles"=> "Teacher"]);

    $db->insert("skill_tier",["tier"=>1,"reward"=>150,"course"=>1]);
    $db->insert("skill_tier",["tier"=>2,"reward"=>400,"course"=>1]);
    $db->insert("skill_tier",["tier"=>3,"reward"=>750,"course"=>1]);
    $db->insert("skill_tier",["tier"=>4,"reward"=>1150,"course"=>1]); 
    
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
