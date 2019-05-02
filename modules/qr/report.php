<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
include ('classes/ClassLoader.class.php');
include('../../config.php');
use \SmartBoards\Core;

Core::init();
 
if (isset($_REQUEST["course"])){
    $results = Core::$systemDB->executeQuery("select student,name,classType,classNumber,campus "
                . "from participation p natural join course_user u natural join user "
                . "where p.student=u.id and course=".$_REQUEST["course"].";");
}else{
    $results = Core::$systemDB->executeQuery("select student,name,classType,classNumber,campus "
                . "from participation p natural join course_user u natural join user "
                . "where p.student=u.id;");
}
$results = $results->fetchAll(\PDO::FETCH_ASSOC);


	$sep = ";"; // separador
	$campus = "";
	//while($row_array = pg_fetch_assoc($result)) {
        foreach ($results as $result){
		/* if($row_array['campus']=="T"){
			$campus="Taguspark";
		}else{ $campus="Alameda"; } */
		$student_name=utf8_decode($result['name']);
		$s = $result['classType'];

		if(preg_match_all('/\b(\w)/',strtoupper($s),$m)) {
    		$v = implode('',$m[1]); // Utilizacao das iniciais do class_type para o campo info adicional do CSV
		}

		echo("{$result['student']}".$sep."{$student_name}".$sep."{$result['campus']}".$sep."participated in {$result['classType']}".$sep.$v."{$result['classNumber']}\n"); 
	}
	//pg_close($connection);
	// num;nome;A/T;"participated in lecture";;lectureids
?>