<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
include('classes/ClassLoader.class.php');
include('../../config.php');

use \GameCourse\Core;

Core::init();

if (isset($_REQUEST["course"])) {
	$results = Core::$systemDB->executeQuery("select studentNumber, name, major, type, description "
		. "from participation p natural join course_user u natural join game_course_user g "
		. "where p.user=g.id and u.id = g.id and course=" . $_REQUEST["course"] . ";");
} else {
	$results = Core::$systemDB->executeQuery("select studentNumber ,name, major, type, description "
		. "from participation p natural join course_user u natural join game_course_user g "
		. "where p.user=g.id and u.id = g.id ;");
}
$results = $results->fetchAll(\PDO::FETCH_ASSOC);


$sep = ";"; // separador
$major = "";
//while($row_array = pg_fetch_assoc($result)) {
foreach ($results as $result) {
	/* if($row_array['campus']=="T"){
			$campus="Taguspark";
		}else{ $campus="Alameda"; } */
	$student_name = utf8_decode($result['name']);
	$s = $result['type'];

	if (preg_match_all('/\b(\w)/', strtoupper($s), $m)) {
		$v = implode('', $m[1]); // Utilizacao das iniciais do class_type para o campo info adicional do CSV
	}

	echo ("{$result['studentNumber']}" . $sep . "{$student_name}" . $sep . "{$result['major']}" . $sep . "participated in {$result['type']}" . $sep . $v . "{$result['description']}\n");
}
	//pg_close($connection);
	// num;nome;A/T;"participated in lecture";;lectureids
