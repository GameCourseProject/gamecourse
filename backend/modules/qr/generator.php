<html>
<head>
<style>
#tinyQR  { font-family: Arial, Helvetica; float: left; text-align:center; width: 160px;
          padding: 0px; border:1px dashed #CCC; margin: 0px 0px 0px 0px; }
</style>
</head>
<?php
//ini_set('display_errors','On');
include('../../config.php');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
include ('classes/ClassLoader.class.php');

use \GameCourse\Core;
use \GameCourse\Course;
use \GameCourse\API;
use Modules\QR\QR;

Core::denyCLI();
Core::requireLogin();
Core::init();
Core::checkAccess();

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

if (isset($_REQUEST["course"])){
    $course = new Course($_REQUEST["course"]);
    $courseAdmin = $course->getLoggedUser()->hasRole('Teacher');
    
    if (!Core::getLoggedUser()->isAdmin() && !$courseAdmin) {
        API::error('You don\'t have permission to request this!', 403);
    }
}

$tinyurl="";
if(isset($_REQUEST["quantos"]) && isset($_REQUEST["course"]) ){

        $courseId= $_REQUEST["course"];
        $max = intval($_REQUEST["quantos"]);
        $datagen=date('YmdHis');
        for ($i = 1; $i <= $max; $i++) {
            
            $uid=uniqid();

            $separator = ';';
            $key = $datagen.$separator.$uid;
            //$url = "http://web.ist.utl.pt/daniel.j.goncalves/pcm/index.php?key=".$key;
            $url = "http://".$_SERVER['HTTP_HOST'] .'/'. BASE . "/" . MODULES_FOLDER . "/" . QR::ID . "/index.php?course=".$courseId."&key=".$key;

            $tinyurl = getTinyURL($url);
            // Inserir Base de Dados
            Core::$systemDB->insert(QR::TABLE,["qrkey"=>$key,"course"=>$courseId]);
			
		
?>
        <div id="tinyQR"><img src="qrcode.php?url=<?=$url?>" alt="<?=$url?>" /><br/><?=substr($tinyurl,7);?></div>		
       <!--div id="tinyQR"><img src="modules/qr/qrcode.php?url=<?=$url?>" alt="<?=$url?>" /><br/><?=substr($tinyurl,7);?></div-->
<?php
        }
} else {
?>
    <p>Something went wrong with QR generation</p>
	
<?php 
}
?>
</body>
</html>

