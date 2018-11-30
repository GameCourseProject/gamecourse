<html>
<head>
<style>
#tinyQR  { font-family: Arial, Helvetica; float: left; text-align:center; width: 160px;
          padding: 0px; border:1px dashed #CCC; margin: 0px 0px 0px 0px; }
</style>
</head>
<?php
ini_set('display_errors','On');
include('config.php'); // configuracao base de dados 
include('password.php'); // valor da password em md5 >>> md5 -s "password" (Mac)

function getTinyURL($url){  
	$ch = curl_init();  
	$timeout = 5;  
	curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);  
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);  
	$data = curl_exec($ch);  
	curl_close($ch);  
 
	return $data;  
}

$tinyurl="";
if(isset($_REQUEST["quantos"]) && isset($_REQUEST["palavra"]) ){
  $palavra = md5($_REQUEST["palavra"]);
  if($palavra==$password){
    $connection = pg_connect("host=$hostname port=$port user=$dbusername password=$dbpassword dbname=$dbusername") 
		or die(pg_last_error());
	
	$max = intval($_REQUEST["quantos"]);
	$datagen=date('YmdHis');
	for ($i = 1; $i <= $max; $i++) {
		$uid=uniqid();

		$separator = ';';
		$key = $datagen.$separator.$password.$separator.$uid;
		//$url = "http://web.ist.utl.pt/daniel.j.goncalves/pcm/index.php?key=".$key;
		$url = "http://localhost/qr/index.php?key=".$key;
		$tinyurl = getTinyURL($url);
		$sql="INSERT INTO qrcode(qrkey) VALUES ('{$key}');";
		//$result = pg_query($sql) or die(pg_last_error());
		
		// Inserir Base de Dados	
		
?>
        <div id="tinyQR"><img src="qrcode.php?url=<?=$url?>" alt="<?=$url?>" /><br/><?=substr($tinyurl,7);?></div>		
       <!--div id="tinyQR"><img src="modules/qr/qrcode.php?url=<?=$url?>" alt="<?=$url?>" /><br/><?=substr($tinyurl,7);?></div-->
<?php
	}
	pg_close($connection);
  } else echo "PASSWORD ERRADA!";
} else {
?>
    <p>Something went wrong with QR generation</p>
	
<?php 
}
?>
</body>
</html>

