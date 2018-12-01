<html>
<head>
<style>
table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
}
</style>
</head>
<body>
<div id="failedAttemps"><table style="margin:2px">
<?php
	include('config.php'); // configuracao base de dados
   $connection = pg_connect("host=$hostname port=$port user=$dbusername password=$dbpassword dbname=$dbusername") or die(pg_last_error());
	$sql = "SELECT student_id,student_name,campus,msg,datetime,ip FROM student NATURAL JOIN error;";
	$result = pg_query($connection, $sql) or die(pg_last_error());
	$sep = ";"; // separador
	$campus = "";
	while($row_array = pg_fetch_assoc($result)) {
		/* if($row_array['campus']=="T"){
			$campus="Taguspark";
		}else{ $campus="Alameda"; } */
		$student_name=utf8_decode($row_array['student_name']);

?>
        <tr>
            <th><?="{$row_array['student_id']}"?></th>
            <th><?="{$student_name}"?></th>
            <th><?="{$row_array['campus']}"?></th>
            <th><?="{$row_array['ip']}"?></th>
            <th><?="{$row_array['msg']}"?></th>
            <th><?="{$row_array['datetime']}"?></th>
        </tr>
<?php
    
		//echo("{$row_array['student_id']}".$sep."{$student_name}".$sep."{$row_array['campus']}".$sep."{$row_array['ip']}".$sep." {$row_array['msg']}".$sep."{$row_array['datetime']}\n"); 
	}
	pg_close($connection);
	// num;nome;A/T;ip;errorMsg;datetime
?>
    </table></div></body></html>
       