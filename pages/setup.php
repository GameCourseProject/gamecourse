<?php
if (!defined('CONNECTION_STRING'))
    return;

use \MagicDB\SQLDB;
use GameCourse\Core;

if (array_key_exists('setup', $_GET) && array_key_exists('course-name', $_POST) && array_key_exists('teacher-id', $_POST)) {
    $courseName = $_POST['course-name'];
    $courseColor = $_POST['course-color'];
    $teacherId = $_POST['teacher-id'];
    $teacherUsername = $_POST['teacher-username'];

    $db = new SQLDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD);
    $sql = file_get_contents("gamecourse.sql");
    $db->executeQuery($sql);
    $courseId = 1;
    $db->insert("course", ["name" => $courseName, "id" => $courseId, "color" => $courseColor]);
    $dataFolder = \GameCourse\Course::createCourseDataFolder($courseId, $courseName);
    $roleId = \GameCourse\Course::insertBasicCourseData($db, $courseId);

    $userId = $db->insert("game_course_user", [
        "studentNumber" => $teacherId,
        "name" => "Teacher",
        "isAdmin" => true
    ]);
    $db->insert("auth", ["id" => 1, "game_course_user_id" => $userId, "username" => $teacherUsername, "authentication_service" => "fenix"]);
    $db->insert("course_user", [
        "id" => $userId,
        "course" => $courseId,
    ]);
    $db->insert("user_role", ["id" => $userId, "course" => $courseId, "role" => $roleId]);
    // insert line in AutoGame table
    $db->insert("autogame", ["course" => $courseId]);	
    
    // prepare autogame
    $rulesfolder = join("/", array($dataFolder, "rules"));
    $functionsFolder = "autogame/imported-functions/" . strval($courseId);
    $logsFolder = "logs";
    $functionsFileDefault = "autogame/imported-functions/defaults.py";
    $defaultFunctionsFile = "/defaults.py";
    $metadataFile = "autogame/config/config_" . strval($courseId) . ".txt";
    $logsFile = "logs/log_course_" . strval($courseId) . ".txt";
    mkdir($rulesfolder);
    mkdir($logsFolder);
    mkdir($functionsFolder);
    $defaults = file_get_contents($functionsFileDefault);
    file_put_contents($functionsFolder . $defaultFunctionsFile, $defaults);
    file_put_contents($metadataFile, "");
    file_put_contents($logsFile, "");

    file_put_contents('setup.done', '');

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
    <title>GameCourse</title>
    <link rel="stylesheet" type="text/css" href="css/simple-page.css" />
</head>

<body>
    <div class="big-title">GameCourse</div>
    <form class="middle-box" action="?setup" method="post" target="_self">
        <div class="header">First time setup!</div>
        <div class="content">
            <h2>Create a course!</h2>
            <label for="course-name" class="label">Course Name</label>
            <input type="text" id="course-name" class="input-text" name="course-name" placeholder="(ex: PCM 2015/2016)" required><br>
            <label for="course-color" class="label">Course Color</label>
            <input type="text" id="course-color" class="input-text" name="course-color" placeholder="(ex: #ffffff)" required><br>
            <label for="teacher-id" class="label">Teacher IST ID</label>
            <input type="number" id="teacher-id" class="input-number" name="teacher-id" placeholder="(ex: 12345)" min="0" max="999999" required><br>
            <label for="teacher-username" class="label">Teacher Username</label>
            <input type="text" id="teacher-username" class="input-text" name="teacher-username" placeholder="(ex: ist112345)" required><br>
        </div>
        <div class="footer"><input type="submit" class="button big" value="Finish!"></div>
    </form>
</body>

</html>