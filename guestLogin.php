<?php
error_reporting(E_ALL);
function printTrace() {
    echo '<pre>';
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
    echo '</pre>';
}
include 'classes/ClassLoader.class.php';
require_once 'google-api-php-client/vendor/autoload.php';
//require_once 'config.php';
use \SmartBoards\Core;
echo '<pre>';

echo "Guest Login\n";

Core::denyCLI();
if (!Core::requireSetup(false)) {
    API::error("SmartBoards is not yet setup.", 400);
}
$authorizedEmails = ['alice.dourado@campus.ul.pt'=>100,
                     'djvg@campus.ul.pt'=>101,
                     'fc@campus.ul.pt'=>102,
                     'javiana@campus.ul.pt'=>103,
                     'ezorzal@gmail.com'=>104];
Core::init();
Core::init();
ob_start();
session_start();
$result = ob_get_clean();
ob_end_clean();
if ($result !== '') {
     session_regenerate_id();
}
$client = new Google_Client();
$client->setAuthConfig('google-api-php-client/credentials.json');
$client->setAccessType("offline"); 
$client->setIncludeGrantedScopes(true);
$client->addScope('profile');
$client->addScope('email');
        
if (array_key_exists('method', $_GET)){
    if ($_GET['method']=='Google'){
        
        if (isset($_SESSION['accessToken'])){
            //echo "Already Logged In (Session token is set)";//todo add redirect
            header('Location: '.'/'.BASE);
            exit();
        }else{
            $auth_url = $client->createAuthUrl();
            header('Location: '.$auth_url);
            //header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        }
    }
}else{
   if (! isset($_GET['code'])) {
       echo "There was an issue with authentication, code not set";
   }else{
       $client->authenticate($_GET['code']);
       $plus = new Google_Service_Plus($client);
       

       $person=$plus->people->get('me');
       $email=$person['emails'][0]['value'];
       
       
       if (array_key_exists($email, $authorizedEmails)){
            $access_token = $client->getAccessToken();
       
            $_SESSION['accessToken'] = $access_token['access_token'];
            //$_SESSION['refreshToken'] = 
            $_SESSION['expires'] = $access_token['created']+$access_token['expires_in'];
       
            $name=$person['displayName'];
            $id=$authorizedEmails[$email];
            $username='guest'.$id;
            
            if (empty(Core::$sistemDB->select('user','id',['name'=>$name,'email'=>$email]))){
                Core::$sistemDB->insert('user',['name'=>$name,'email'=>$email,'id'=>$id,'username'=>$username]);
                
                //the google users are given the role of Watcher in all the courses
                if (empty( Core::$sistemDB->select('course_user','id',['id'=>$id]) )){
                    $coursesId = Core::$sistemDB->selectMultiple("course","id");
                    foreach($coursesId as $cid){
                        Core::$sistemDB->insert('course_user',['id'=>$id,'course'=>$cid['id'],'roles'=>'Watcher']);
                    }
                }
            }
            
            $_SESSION['username'] = $username;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            header('Location: '.'/'.BASE);
            exit();
       }else{
           echo "The email used to Log In is not authorized";
       }
   }
}
echo '</pre>';
