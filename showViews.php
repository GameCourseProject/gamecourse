<?php
$starttime = microtime(true);
include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;

$isCLI = Core::isCLI();
echo '<pre>';

Core::init();

$queries=0;
$arr=[];
/*
function lookAtTemplateChildren($parent,$ident, &$queries){
    $children = Core::$systemDB->selectMultiple("view_template",["parent"=>$parent],"*","viewIndex");
    $queries++;
    foreach($children as $child){
        $params = Core::$systemDB->selectMultiple("template_parameter join parameter on id=parameterId",
                ["templateId"=>$child['id']]);
        $queries++;
        print_r(str_repeat("\t",$ident));
        print_r($child['partType']."-".$child['id']."  ");
        foreach($params as $param){
            print_r($param["type"].": ".$param["value"].", ");
        }
        print_r("<br>");
 
        lookAtTemplateChildren($child['id'],$ident+1, $queries);
    }
}
function lookAtChildren($parent,$ident, &$queries){
    $children = Core::$systemDB->selectMultiple("view",["parent"=>$parent],"*","viewIndex");
    $queries++;
    foreach($children as $child){
        $params = Core::$systemDB->selectMultiple("view_parameter join parameter on id=parameterId",
                ["viewId"=>$child['id']]);
        $queries++;
        print_r(str_repeat("\t",$ident));
        print_r($child['partType']."-".$child['id']."  ");
        foreach($params as $param){
            print_r($param["type"].": ".$param["value"].", ");
        }
        print_r("<br>");
        if($child['partType']=="instance"){
            //aspect class
            $template = Core::$systemDB->select("view_template",["id"=>$child['template']]);
            $queries++;
            //this is a simplification, is just looking for exact role, 
            //it should find alternatives if exact role isnt found
            $aspect = Core::$systemDB->select("view_template",["parent"=>$template['id'],"role"=>$child["role"]]);
            $queries++;
            lookAtTemplateChildren($aspect['id'], $ident+1,$queries);
        }
        else
            lookAtChildren($child['id'],$ident+1, $queries);
    }
}
function lookAtPages($courseId, &$queries){
    $pages = Core::$systemDB->selectMultiple("page",["course"=>$courseId]);
    $queries++;
    foreach($pages as $p){
        print_r("\tPage: ".$p["name"]."-".$p['id']."<br><br>");
        $aspects = Core::$systemDB->selectMultiple("view",["pageId"=>$p['id'],"partType"=>"aspect"]);
        $queries++;
        foreach($aspects as $asp){
            print_r("<br>\t\tAspect: ".$asp["role"]."-".$asp['id']."<br>");
            lookAtChildren($asp['id'],3, $queries);
    //select v.id,pageId as page,partType,parent,viewIndex as indx,p.id as pID, type, value from view v join view_parameter on v.id=viewId join parameter p on parameterId=p.id;
        }
        print_r("<br>");
    } 
}*/
function lookAtTemplateChildren($parent,$parts,$ident, &$queries, $template_params, &$arr) {
     if (!array_key_exists($parent, $parts))
            return;
    foreach($parts[$parent] as $child){
        print_r(str_repeat("\t",$ident));
        print_r($child['partType']."-".$child['id']."  ");
        
        if (array_key_exists($child['id'], $template_params)){
            $params = $template_params[$child['id']];
        
            foreach($params as $param){
                print_r($param["type"].": ".$param["value"].", ");
            }
        }
        print_r("<br>");
        lookAtTemplateChildren($child['id'],$parts,$ident+1, $queries, $template_params, $arr);
    }
}
function lookAtChildren($parent,$children,$ident, &$queries, $templates, $view_params, $template_params, &$arr){
    if (!array_key_exists($parent, $children))
            return;
    foreach($children[$parent] as $child){
        print_r(str_repeat("\t",$ident));
        print_r($child['partType']."-".$child['id']."  ");
        $arr[$child['id']]=["type"=>$child['partType'],"children"=>[]];
        if (array_key_exists($child['id'], $view_params)){
            $params = $view_params[$child['id']];
        
            foreach($params as $param){
                print_r($param["type"].": ".$param["value"].", ");
                $arr[$child['id']]["params"][]=$param;
            }
        }
        print_r("<br>");
        if($child['partType']=="instance"){
            $aspectClass=$templates[0][$child["template"]];
            $aspects = $templates[1][$aspectClass]["aspects"];
            //this is a simplification, is just looking for exact role, 
            //it should find alternatives if exact role isnt found
            if (array_key_exists($child["role"], $aspects)) {
                $asp=$aspects[$child["role"]];
                lookAtTemplateChildren($asp["id"],$asp["parts"],$ident+1, $queries, $template_params, $arr);
            }
        }
        else
            lookAtChildren($child['id'],$children,$ident+1, $queries, $templates, $view_params, $template_params, $arr[$child['id']]["children"]);
    }
}
function lookAtPages($courseId, &$queries, $templates, $view_params, $template_params, &$arr){
    $views = Core::$systemDB->selectMultiple("page p left join view v on p.id=pageId",
            ["course"=>$courseId],"pageId,name,v.id,role,partType,parent,viewIndex,template","viewIndex,id");
    $queries++;
    
    $pageViews=[];
    foreach ($views as $v){
        $pageViews[$v["pageId"]]["name"]= $v["name"];
        if ($v['partType']=="aspect")
            $pageViews[$v["pageId"]]["aspects"][$v['role']]= $v;
        else
            $pageViews[$v["pageId"]]["aspects"][$v['role']]['parts'][$v['parent']][$v['id']]= $v;
            //$pageViews[$v["pageId"]]["aspects"][$v['role']]['parts'][$v['id']]= $v;
    }
    
    foreach($pageViews as $id =>$p){
        print_r("\tPage: ".$p["name"]."-".$id."<br><br>");
        $arr[$id]=["name"=>$p["name"]];
        foreach($p['aspects'] as $role => $asp){
            print_r("<br>\t\tAspect: ".$role."-".$asp['id']."<br>");
            $arr[$id][$role]=[];
            if (array_key_exists("parts", $asp))
                lookAtChildren($asp['id'],$asp['parts'],3, $queries, $templates, $view_params, $template_params,$arr[$id][$role]);
        }
        print_r("<br>");
    } 
}
//type must be "view" or "template"
function getParameters($type="view", &$queries){
    $db_params = Core::$systemDB->selectMultiple($type."_parameter right join parameter on id=parameterId",
        [],$type."Id,id,type,value");
    $queries++;
    $params=[];
    foreach($db_params as $p){
        if ($p[$type."Id"]==null)
            continue;
        $params[$p[$type."Id"]][]=$p;
    }
    return $params;
}

$view_params=getParameters("view",$queries);
$template_params=getParameters("template",$queries);


$templates = Core::$systemDB->selectMultiple("view_template",null,"*","viewIndex,id");
$templateAaspectClasses= array_column($templates,"aspectClass", 'id');
$queries++;

$templateViews=[];
foreach ($templates as $t){
    if ($t["role"]==null)
        continue;
    if ($t['partType']=="aspect")
        $templateViews[$t["aspectClass"]]["aspects"][$t['role']]= $t;
    else
        $templateViews[$t["aspectClass"]]["aspects"][$t['role']]['parts'][$t['parent']][]= $t;
}
$templates = [$templateAaspectClasses, $templateViews];

print_r("Pages Of Course 1:<br>");
$arr["pages course 1"]=[];
lookAtPages(1, $queries, $templates, $view_params, $template_params,$arr["pages course 1"]);
print_r("Pages Of Course 2:<br>");
$arr["pages course 2"]=[];
lookAtPages(2, $queries, $templates, $view_params, $template_params,$arr["pages course 2"]);
print_r($arr);

//print_r("Pages Of Course 1:<br>");
//lookAtPages(1, $queries);
//print_r("Pages Of Course 2:<br>");
//lookAtPages(2, $queries);


print_R($queries . " queries");
echo "<br>";
$endtime = microtime(true);
$total=$endtime-$starttime;
echo "seconds: ".$total . '<br>';
echo '</pre>';
?>
