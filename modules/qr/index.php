<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
include('classes/ClassLoader.class.php');
include('../../config.php');

use \GameCourse\Core;
use \GameCourse\User;
use \GameCourse\Course;

Core::init();

$disciplina_pt = "PCM - Produ&ccedil;&atilde;o de Conte&uacute;dos Multim&eacute;dia";
$disciplina_en = "Multimedia Content Production";
$ano_pt = "2020/2021";
$ano_en = "2021";
$semestre_pt = "2o Semestre";
$semestre_en = "Spring";
$titulo_pt = "XP B&oacute;nus de Participa&ccedil;&atilde;o Activa na Aula";
$titulo_en = "Bonus XP - Lecture Active Participation";
$error1_pt = "Lamento mas s&oacute; deve chegar a esta p&aacute;gina a partir de um URL correcto. O seu IP foi registado!";
$error1_en = "Sorry but you have arrived from an incorrect URL. Your IP was registered!";
$error2_en = "Sorry but you have an invalid key. Your IP was registered!";
$error_student_number_en = "";
$error_lecture_number_en =  "";
$class_types = array("Lecture", "Invited Lecture");

$error = FALSE;

function inclass($studentNumber, $courseId)
{
  $studentId = Core::$systemDB->selectMultiple("course_user left join game_course_user on game_course_user.id=course_user.id", ["studentNumber" => $studentNumber, "course" => $courseId], "course_user.id");
  return !empty(Core::$systemDB->select("course_user", ["id" => $studentId[0]["id"], "course" => $courseId]));
}

if (isset($_REQUEST["key"]) && isset($_REQUEST["aluno"]) && isset($_REQUEST["course"]) && isset($_REQUEST["submit"])) {
  if (!is_numeric($_REQUEST["aluno"])) {
    $error_student_number_en = "Student Number must be a number! Example: 48283";
    $error = TRUE;
  } else if (strlen($_REQUEST["aluno"]) < 5) {
    $error_student_number_en = "Student Number must have 5 numbers! Example: 48283";
    $error = TRUE;
  } else if (!(inclass($_REQUEST["aluno"], $_REQUEST["course"]))) {
    $error_student_number_en = "The student with that Student Number is not enrolled in class.";
    $error = TRUE;
  } else {
    $error_student_number_en = "";
  }
}

if (isset($_REQUEST["key"])  && isset($_REQUEST["aula"]) && isset($_REQUEST["submit"])) {
  if (!is_numeric($_REQUEST["aula"])) {
    $error_lecture_number_en = "Lecture Number must be a number! Example: 7";
    $error = TRUE;
  } else {
    $error_lecture_number_en = "";
  }
}

$valid = FALSE;
$used = TRUE;
?>

<html>

<head>
  <title><?= $disciplina_en ?> - <?= $semestre_en ?> <?= $ano_en ?></title>
  <style>
    p.error {
      color: red;
      font-weight: bold;
    }

    .error {
      color: red;
      font-weight: bold;
    }

    .success {
      color: green;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <h3>IST - DEI - CGM</h3>

  <?php
  $course = Core::$systemDB->select("course",["id"=> $_REQUEST["course"]], "name , year");
  $disciplina_en = $course["name"];
  $ano_en = $course["year"];
  if (isset($_REQUEST["key"]) && !empty($_REQUEST["aluno"]) && !empty($_REQUEST["aula"]) && isset($_REQUEST["submit"]) && !($error)) {

    $user = User::getUserByStudentNumber($_REQUEST["aluno"]);
    try {
        Core::$systemDB->update("qr_code", ["studentNumber" => $user->getId(), "classNumber" =>  $_REQUEST['aula'], "classType" => $_REQUEST['classtype'] ], ["qrkey" => $_REQUEST["key"]]);
        $type = "";
        if ($_REQUEST['classtype'] == "Lecture") {
            $type = "participated in lecture";
        } else if ($_REQUEST['classtype'] == "Invited Lecture") {
            $type = "participated in lecture (invited)";
        }
        Core::$systemDB->insert("participation", ["user" => $user->getId(), "course" => $_REQUEST["course"], "description" => $_REQUEST['aula'], "type" => $type]);
      echo "<span class='success'>Your active participation was registered.<br />Congratulations! Keep participating. ;)</span>";
    } catch (PDOException $e) {
      echo "<br/><span class='error'>Sorry. An error occured. Contact your class professor with your QRCode and this message. Your student ID and IP number was registered.</span>\n";
      $erro = $e->getMessage();
      $sql = "INSERT INTO error(student_id, ip, qrcode, datetime, msg) VALUES ('{$_REQUEST['aluno']}','{$_SERVER['REMOTE_ADDR']}','{$_REQUEST['key']}',date_trunc('second', current_timestamp), '{$erro}');";
      Core::$systemDB->insert("qr_error", [
        "studentNumber" => $_REQUEST["aluno"], "course" => $_REQUEST["course"],
        "ip" => $_SERVER['REMOTE_ADDR'], "qrkey" => $_REQUEST["key"], "msg" => $erro
      ]);
    }
  } else if (isset($_REQUEST["key"]) && isset($_REQUEST["course"])) {
    // QRCode e valido?
    $valid = !empty(Core::$systemDB->select("qr_code", ["qrkey" => $_REQUEST["key"]], "qrkey"));

    // QRCode já foi atribuido?
    $used = !empty(Core::$systemDB->select("qr_code", ["qrkey" => $_REQUEST["key"]], "studentNumber"));

  ?>
      <h2><?= $disciplina_en ?> - <?= $semestre_en ?> <?= $ano_en ?></h2>
      <h2><?= $titulo_en ?></h2>

      <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get">
      <input type="hidden" name="course" value="<?= $_REQUEST['course'] ?>">
      <input type="hidden" name="key" value="<?= $_REQUEST['key'] ?>">
      Your IST Student Number:<input name="aluno" maxlength="5" size="5" <?php if (isset($_REQUEST["aluno"])) { ?> value="<?= $_REQUEST["aluno"] ?>" <?php } ?>><span class="error"><?= $error_student_number_en ?></span><br />
      Type of Class:
      <select name="classtype">
        <?php
        $count = count($class_types);
        for ($i = 0; $i < $count; $i++) {
          echo "<option value='{$class_types[$i]}'>{$class_types[$i]}</option>\n";
        }

        ?>
      </select><br />
      Lecture Number:<input size="2" maxlength="2" name="aula" <?php if (isset($_REQUEST["aula"])) { ?> value="<?= $_REQUEST["aula"] ?>" <?php } ?>><input type="submit" name="submit" value="Submit">
      <span class="error"><?= $error_lecture_number_en ?></span><br />
      <br /><b>All fields are required.</b><br />
    </form>
  <?php

  } else {
    // Registar IP?
  ?>
    <p class="error"><?= $error1_en ?></p>
  <?php
  }
  ?>
</body>

</html>