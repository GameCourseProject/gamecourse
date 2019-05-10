<?php
include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;

Core::init();

$courses = Core::$systemDB->selectMultiple("mdl_course","id,shortname,fullname");
$users = Core::$systemDB->selectMultiple("mdl_user");
$userMap = array_combine(array_column($users, "id"), $users);

$folder="moodleExport";
if (!file_exists($folder))
    mkdir($folder);
foreach($courses as $course){
    if (strpos( $course["shortname"], "PCM" )===false){
        continue;
    }
    echo "Looking at course " . $course['fullname'] . "<br>";
    $courseFolder=$folder .'/'. $course['id'].'-'.$course["shortname"];
    if (!file_exists($courseFolder))
        mkdir($courseFolder);
    
    $forums = Core::$systemDB->selectMultiple("mdl_forum","id,name",["course"=>$course['id']]);
    foreach($forums as $forum){
        //echo "Looking at forum " . $forum['name'] . "<br>";
        $forum["name"]= str_replace('/', '-', $forum["name"]);
        $forumFolder=$courseFolder .'/'. $forum['id'].'-'.preg_replace("/[^a-zA-Z0-9_ ]/","",$forum["name"]);
        if (!file_exists($forumFolder))
            mkdir($forumFolder);
        
        $discussions = Core::$systemDB->selectMultiple("mdl_forum_discussions","id,name",["forum"=>$forum['id']]);
        foreach($discussions as $discussion){
            //echo "Looking at discussion " . $discussion['name'] . "<br>";
            $discussion["name"]= str_replace('/', '-', $discussion["name"]);
            $discussionFolder=$forumFolder .'/'. $discussion['id'].'-'.preg_replace("/[^a-zA-Z0-9_ ]/","",$discussion["name"]);
            if (!file_exists($discussionFolder))
                mkdir($discussionFolder);
            
            $posts = Core::$systemDB->selectMultiple("mdl_forum_posts","id",["discussion"=>$discussion['id']]);
            //message,id,userid,parent,created
            foreach($posts as $post){
                if ($post["id"]==8262){//the content of this post makes an out of memory error
                    continue;
                }
                $post = Core::$systemDB->select("mdl_forum_posts","message,id,userid,parent,created",["id"=>$post['id']]);
                //echo "Looking at post " . $post['name'] . "<br>";

                $post["username"]=$userMap[$post["userid"]]["username"];
                $post["userFirstLastName"]=utf8_encode($userMap[$post["userid"]]['firstname'] .' '.$userMap[$post["userid"]]['lastname']);
                unset($post['userid']);
                
                $post["discussion"]=utf8_encode($discussion['name']);
                $post["forum"]=utf8_encode($forum['name']);
                $post["message"]=utf8_encode($post["message"]);
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
        }
    }
    $peerforums = Core::$systemDB->selectMultiple("mdl_peerforum","id,name",["course"=>$course['id']]);
    foreach($peerforums as $forum){
        echo "Looking at peerforum " . $forum['name'] . "<br>";
        $forum["name"]= str_replace('/', '-', $forum["name"]);
        $forumFolder=$courseFolder .'/'. $forum['id'].'-'.preg_replace("/[^a-zA-Z0-9_ ]/","",$forum["name"]);
        if (!file_exists($forumFolder))
            mkdir($forumFolder);
        
        $discussions = Core::$systemDB->selectMultiple("mdl_peerforum_discussions","id,name",["peerforum"=>$forum['id']]);
        foreach($discussions as $discussion){
            echo "Looking at discussion " . $discussion['name'] . "<br>";
            $discussion["name"]= str_replace('/', '-', $discussion["name"]);
            $discussionFolder=$forumFolder .'/'. $discussion['id'].'-'.preg_replace("/[^a-zA-Z0-9_ ]/","",$discussion["name"]);
            if (!file_exists($discussionFolder))
                mkdir($discussionFolder);
            
            $posts = Core::$systemDB->selectMultiple("mdl_peerforum_posts","id",["discussion"=>$discussion['id']]);
            //message,id,userid,parent,created
            foreach($posts as $post){
                $post = Core::$systemDB->select("mdl_peerforum_posts","message,id,userid,parent,created",["id"=>$post['id']]);
                //echo "Looking at post " . $post['name'] . "<br>";

                $post["username"]=$userMap[$post["userid"]]["username"];
                $post["userFirstLastName"]=utf8_encode($userMap[$post["userid"]]['firstname'] .' '.$userMap[$post["userid"]]['lastname']);
                unset($post['userid']);
                
                $post["discussion"]=utf8_encode($discussion['name']);
                $post["forum"]=utf8_encode($forum['name']);
                $post["message"]=utf8_encode($post["message"]);
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
        }
    }
}
echo "Done";
?>