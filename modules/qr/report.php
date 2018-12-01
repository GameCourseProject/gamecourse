<?php
	include('config.php'); // configuracao base de dados
   $connection = pg_connect("host=$hostname port=$port user=$dbusername password=$dbpassword dbname=$dbusername") or die(pg_last_error());
	$sql = "SELECT student_id,student_name,class_type,class_number,campus FROM student NATURAL JOIN participation;";
	$result = pg_query($connection, $sql) or die(pg_last_error());
	$sep = ";"; // separador
	$campus = "";
	while($row_array = pg_fetch_assoc($result)) {
		/* if($row_array['campus']=="T"){
			$campus="Taguspark";
		}else{ $campus="Alameda"; } */
		$student_name=utf8_decode($row_array['student_name']);
		$s = $row_array['class_type'];

		if(preg_match_all('/\b(\w)/',strtoupper($s),$m)) {
    		$v = implode('',$m[1]); // Utilizacao das iniciais do class_type para o campo info adicional do CSV
		}

		echo("{$row_array['student_id']}".$sep."{$student_name}".$sep."{$row_array['campus']}".$sep."participated in {$row_array['class_type']}".$sep.$v."{$row_array['class_number']}\n"); 
	}
	pg_close($connection);
	// num;nome;A/T;"participated in lecture";;lectureid
	// SELECT student_id,class_type,class_number,campus FROM student  NATURAL JOIN participation;
	// Num aluno;"participated in lecture";num da aula;campus
?>