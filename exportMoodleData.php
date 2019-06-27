<?php
//This script extracts all the posts from the moodle DataBase and inserts it into folders
//This version uses SmartBoards classes, it only works if Smartboards and Moodle use the same Database
include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;

Core::init();

$courses = Core::$systemDB->selectMultiple("mdl_course","id,shortname,fullname");
$users = Core::$systemDB->selectMultiple("mdl_user");
$userMap = array_combine(array_column($users, "id"), $users);

$folder="moodleExport";
function computePost($post,$userMap,$discussion,$forum,$course, $discussionFolder){
    //echo "Looking at post " . $post['name'] . "<br>";

    $post["username"]=$userMap[$post["userid"]]["username"];
    $post["userFirstLastName"]=utf8_encode($userMap[$post["userid"]]['firstname'] .' '.$userMap[$post["userid"]]['lastname']);
    unset($post['userid']);

    $post["discussion"]=utf8_encode($discussion['name']);
    $post["forum"]=utf8_encode($forum['name']);
    
    
    //file_put_contents($discussionFolder . '/'. $post['id'].".html",$post["message"]);
    //file_put_contents($discussionFolder . '/'. $post['id'].".txt",$post["message"]);
    
    //Removing some html tags to make a "cleaner" output
    $post["message"]=(preg_replace('#<[/]?p>#','',$post["message"]));
    $post["message"]=(preg_replace('#<[/]?span[^>]*>#','',$post["message"]));
    $post["message"]=(preg_replace('#<[/]?strong[^>]*>#','',$post["message"]));
    $post["message"]=(preg_replace('#<[/]?ul[^>]*>#','',$post["message"]));
    $post["message"]=(preg_replace('#<[/]?li[^>]*>#','',$post["message"]));
    $post["message"]=(preg_replace('#<[/]?em>#','',$post["message"]));
    $post["message"]=(preg_replace('#<br[^>]*>#','',$post["message"]));
    $post["message"]=(preg_replace('#<[/]?pre>#','',$post["message"]));
    //$post["message"]=(preg_replace('#\s:[pP]\s#','',$post["message"]));
    //$post["message"]=(preg_replace('#\.{3,4}#','',$post["message"]));
    //$post["message"]=(preg_replace('#;#','',$post["message"]));
    
    $post["message"]=(preg_replace('/<img[^>]*>/',' IMAGE ',$post["message"]));
    //$post["message"]=(preg_replace('#<a[^>]*href=\"([^> \"]*)\"[^>]*>[^<]*</a>#','$1',$post["message"]));
    $post["message"]=(preg_replace('#<a[^>]*>[^<]*</a>#',' WEBLINK ',$post["message"]));
    //file_put_contents($discussionFolder . '/'. $post['id']."After.txt",$post["message"]);
     
    $post["message"] = utf8_encode($post["message"]);
    
    //$post["message"]=(preg_replace('#\r\n#','',$post["message"]));/
    
    $post["course"]=utf8_encode($course["shortname"]);

    $content = json_encode($post);

    $error = json_last_error_msg();
    if (json_last_error_msg()!="No error"){
        print_r($error . "<br> On json encoding of the following info: <br>");
        print_r($post);
        echo "<br>";
    }
    file_put_contents($discussionFolder . '/'. $post['id'].".json",$content);
    //echo "added file to ". $discussionFolder . "<br>";
}

if (!file_exists($folder))
    mkdir($folder);
foreach($courses as $course){
    if (strpos( $course["shortname"], "PCM" )===false){
        continue;
    }
    echo "Extracting course " . $course['fullname'] . "<br>";
    $course["shortname"]= str_replace('/', '-', $course["shortname"]);
    $courseFolder=$folder .'/'. $course['id'].'-'.$course["shortname"];
    $courseFolder=preg_replace("/\s+$/","",$courseFolder);
    if (!file_exists($courseFolder))
        mkdir($courseFolder);
    
    $forums = Core::$systemDB->selectMultiple("mdl_forum","id,name",["course"=>$course['id']]);
    foreach($forums as $forum){
        //echo "Looking at forum " . $forum['name'] . "<br>";
        $forum["name"]= str_replace('/', '-', $forum["name"]);
        $forumFolder=$courseFolder .'/'. $forum['id'].'-'.preg_replace("/[^-A-z0-9_ ]/","",$forum["name"]);
        $forumFolder=preg_replace("/\s+$/","",$forumFolder);
        if (!file_exists($forumFolder))
            mkdir($forumFolder);
        
        $discussions = Core::$systemDB->selectMultiple("mdl_forum_discussions","id,name",["forum"=>$forum['id']]);
        foreach($discussions as $discussion){
            //echo "Looking at discussion " . $discussion['name'] . "<br>";
            $discussion["name"]= str_replace('/', '-', $discussion["name"]);
            $discussionFolder=$forumFolder .'/'. $discussion['id'].'-'.preg_replace("/[^-A-z0-9_ ]/","",$discussion["name"]);
            $discussionFolder=preg_replace("/\s+$/","",$discussionFolder);
            if (!file_exists($discussionFolder))
                mkdir($discussionFolder);
            
            $posts = Core::$systemDB->selectMultiple("mdl_forum_posts","id",["discussion"=>$discussion['id']]);
            //message,id,userid,parent,created
            foreach($posts as $post){
                if ($post["id"]==8262){//the content of this post makes an out of memory error
                    continue;
                }
                $post = Core::$systemDB->select("mdl_forum_posts","message,id,userid,parent,created",["id"=>$post['id']]);
                computePost($post, $userMap, $discussion, $forum, $course, $discussionFolder);
            }
        }
    }
    try {
        $peerforums = Core::$systemDB->selectMultiple("mdl_peerforum","id,name",["course"=>$course['id']]);
    } catch (Exception $ex) {
        $peerforums=[];
    }
    
    foreach($peerforums as $forum){
        //echo "Looking at peerforum " . $forum['name'] . "<br>";
        $forum["name"]= str_replace('/', '-', $forum["name"]);
        $forumFolder=$courseFolder .'/'. $forum['id'].'-'.preg_replace("/[^-A-z0-9_ ]/","",$forum["name"]);
        $forumFolder=preg_replace("/\s+$/","",$forumFolder);
        if (!file_exists($forumFolder))
            mkdir($forumFolder);
        
        $discussions = Core::$systemDB->selectMultiple("mdl_peerforum_discussions","id,name",["peerforum"=>$forum['id']]);
        foreach($discussions as $discussion){
            //echo "Looking at discussion " . $discussion['name'] . "<br>";
            $discussion["name"]= str_replace('/', '-', $discussion["name"]);
            $discussionFolder=$forumFolder .'/'. $discussion['id'].'-'.preg_replace("/[^-A-z0-9_ ]/","",$discussion["name"]);
            $discussionFolder=preg_replace("/\s+$/","",$discussionFolder);
            if (!file_exists($discussionFolder))
                mkdir($discussionFolder);
            
            $posts = Core::$systemDB->selectMultiple("mdl_peerforum_posts","id",["discussion"=>$discussion['id']]);
            //message,id,userid,parent,created
            foreach($posts as $post){
                $post = Core::$systemDB->select("mdl_peerforum_posts","message,id,userid,parent,created",["id"=>$post['id']]);
                computePost($post, $userMap, $discussion, $forum, $course, $discussionFolder);
            }
        }
    }
    //break;//Todo delete
}
echo "Done";
?>