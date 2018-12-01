<?php

include('config.php'); // configuracao base de dados 
    $connection = pg_connect("host=$hostname port=$port user=$dbusername password=$dbpassword dbname=$dbusername") or die(pg_last_error());
			
$disciplina_pt = "PCM - Produ&ccedil;&atilde;o de Conte&uacute;dos Multim&eacute;dia";
$disciplina_en = "Multimedia Content Production";
$ano_pt="2014/2015";
$ano_en="2015";
$semestre_pt="2o Semestre";
$semestre_en="Spring";
$titulo_pt="XP B&oacute;nus de Participa&ccedil;&atilde;o Activa na Aula";
$titulo_en="Bonus XP - Lecture Active Participation";
$error1_pt = "Lamento mas s&oacute; deve chegar a esta p&aacute;gina a partir de um URL correcto. O seu IP foi registado!";
$error1_en = "Sorry but you have arrived from an incorrect URL. Your IP was registered!";
$error2_en = "Sorry but you have an invalid key. Your IP was registered!";
$error_student_number_en = "";
$error_lecture_number_en =  "";

$error = FALSE;

function inclass($student_id, $connection){
	
	$sql = "SELECT student_id FROM student WHERE student_id='{$student_id}';";

	$result = pg_query($connection, $sql);
	if($result == NULL) $rows = 0;
	else $rows = pg_num_rows($result);
	if($rows==1){return TRUE;}else{return FALSE;}
	
}

if(isset($_REQUEST["key"]) && isset($_REQUEST["aluno"]) && isset($_REQUEST["submit"])){
  if(!is_numeric($_REQUEST["aluno"])){
    $error_student_number_en = "Student Number must be a number! Example: 48283";
    $error = TRUE;
  }else if(strlen($_REQUEST["aluno"])<5){
    $error_student_number_en = "Student Number must have 5 numbers! Example: 48283";
    $error = TRUE;
  }else if(!(inclass($_REQUEST["aluno"], $connection))){
    $error_student_number_en = "The student with that Student Number is not enrolled in class.";
    $error = TRUE;
  }else {$error_student_number_en ="";}
}

if(isset($_REQUEST["key"])  && isset($_REQUEST["aula"]) && isset($_REQUEST["submit"])){
  if(!is_numeric($_REQUEST["aula"])){
    $error_lecture_number_en = "Lecture Number must be a number! Example: 7";
    $error = TRUE;
  }else {$error_lecture_number_en ="";}
}

$valid = FALSE;
$used = TRUE;
?>

<html>
<head>
<title><?=$disciplina_en ?> - <?=$semestre_en ?> <?=$ano_en ?></title>
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
<h2><?=$disciplina_en?> - <?=$semestre_en?> <?=$ano_en ?></h2>
<h2><?=$titulo_en ?></h2>

<?php
if(isset($_REQUEST["key"]) && !empty($_REQUEST["aluno"]) && !empty($_REQUEST["aula"]) && isset($_REQUEST["submit"]) && !($error)){

	$sql="INSERT INTO participation(qrkey, student_id, class_number, class_type) VALUES ('{$_REQUEST['key']}','{$_REQUEST['aluno']}','{$_REQUEST['aula']}','{$_REQUEST['classtype']}');";
	$result = pg_query($connection, $sql);
	if (!$result) {
  		echo "<br/><span class='error'>Sorry. An error occured. Contact your class professor with your QRCode and this message. Your student ID and IP number was registered.</span>\n";
  		$erro = pg_last_error($connection);
  		$sql="INSERT INTO error(student_id, ip, qrcode, datetime, msg) VALUES ('{$_REQUEST['aluno']}','{$_SERVER['REMOTE_ADDR']}','{$_REQUEST['key']}',date_trunc('second', current_timestamp), '{$erro}');";
  		$result = pg_query($connection, $sql);
  	}else {
	  echo "<span class='success'>Your active participation was registered.<br />Congratulations! Keep participating. ;)</span>";
  	}

} else if(isset($_REQUEST["key"])){
	


// QRCode e valido?
	$sql = "SELECT qrkey FROM qrcode WHERE qrkey='".$_REQUEST["key"]."';";
	$result = pg_query($connection, $sql);
	$rows = pg_num_rows($result);
	if($rows==1){ $valid=TRUE; }else{ $valid=FALSE; }

// QRCode já foi atribuido?
	$sql = "SELECT qrkey FROM participation WHERE qrkey='".$_REQUEST["key"]."';";
	$result = pg_query($connection, $sql);
	$rows = pg_num_rows($result);
	if($rows==1){ $used=TRUE; }else{ $used=FALSE; }


?>
		<form action="<?=$_SERVER['PHP_SELF']?>" method="get">
			<input type="hidden" name="key" value="<?=$_REQUEST['key']?>">
			Your IST Student Number:<input name="aluno" maxlength="5" size="5" 
			<?php if(isset($_REQUEST["aluno"])){ ?>
			value="<?=$_REQUEST["aluno"]?>"
			<?php } ?>
			><span class="error"><?=$error_student_number_en?></span><br />
			Type of Class:
			<select name="classtype">
<?php
			$count = count($class_types);
			for ($i = 0; $i < $count; $i++) {
    			echo "<option value='{$class_types[$i]}'>{$class_types[$i]}</option>\n";
    		}

?>
			</select><br />
			Lecture Number:<input size="2" maxlength="2" name="aula" 			
			<?php if(isset($_REQUEST["aula"])){ ?>
			value="<?=$_REQUEST["aula"]?>"
			<?php } ?>
			><input type="submit" name="submit" value="Submit">
			<span class="error"><?=$error_lecture_number_en?></span><br />
			<br/><b>All fields are required.</b><br/>
		</form>
<?php

}else {
// Registar IP?
?>
<p class="error"><?=$error1_en?></p>
<?php
}
pg_close($connection);
?>
</body>
</html>
