<?php
use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;

class Profiling extends Module {

    private $scriptPath = "/var/www/html/gamecourse/modules/profiling/profiler.py";
    private $logPath = "/var/www/html/gamecourse/modules/profiling/results.txt";
    // cluster names
    private $baseNames = ["Achiever", "Regular", "Halfhearted", "Underachiever"];

    public function __construct() {
        parent::__construct('profiling', 'Profiling', '0.1', array(
            array('id' => 'views', 'mode' => 'hard')
        ));
    }
    public function setupResources() {
        parent::addResources('css/');
        parent::addResources('js/');
    }

    public function setupData($courseId){
        if ($this->addTables("profiling", "profiling_config")){
            Core::$systemDB->insert("profiling_config", ["course" => $courseId]);
        }
        $course = Course::getCourse($courseId);
        $role = $course->getRoleByName("Profiling");
        if (!$role){
            $names = [];
            Core::$systemDB->insert("role", ["course" => $courseId, "name" => "Profiling"]);
            $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));

            $courseRoles = $course->getRoles('name, id');
            foreach($courseRoles as $role){
                $names[$role['name']] = $role['id'];
            }

            $studentIndex = array_search("Student", array_keys($names));

            if(!isset($hierarchy[$studentIndex]->children))
                $hierarchy[$studentIndex]->children = array();
           
            $object = (object) ['name' => "Profiling"];
            array_push($hierarchy[$studentIndex]->children, $object);

            Core::$systemDB->update("course", ["roleHierarchy" => json_encode($hierarchy)], ["id" => $courseId]);
        }
    }

    public function init() {

        $courseId = $this->getParent()->getId();
        $this->setupData($courseId);

        API::registerFunction('settings', 'getTime', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $time = Core::$systemDB->select("profiling_config", ["course" => $courseId], "lastRun");
            API::response(array('time' => $time));
        });
        API::registerFunction('settings', 'runProfiler', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $clusters = $this->runProfiler($courseId);
        });
        API::registerFunction('settings', 'checkRunningStatus', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            if(file_exists($this->logPath)){
                $clusters = $this->checkStatus($courseId);

                if(array_key_exists("errorMessage", $clusters)){
                    if (file_exists($this->logPath)){
                        unlink($this->logPath);
                    }
                    API::error($clusters["errorMessage"], 400);
                }
                if(empty($clusters)){
                    API::response(array('running' => true));
                }
                
                $names = $this->createNamesArray($this->getClusterNames($courseId));
                API::response(array('clusters' => $clusters, 'names' => $names));
            }
            else {
                API::response(array('running' => false));
            }
        });
        API::registerFunction('settings', 'commitClusters', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $clusters = API::getValue('clusters');
            if (file_exists($this->logPath)){
                unlink($this->logPath);
            }
            $this->processClusterRoles($courseId, $clusters);
            $this->deleteSaved($courseId);
        });
        API::registerFunction('settings', 'saveClusters', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $clusters = API::getValue('clusters');

            $this->saveClusters($courseId, $clusters);
        });
        API::registerFunction('settings', 'deleteSaved', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            if (file_exists($this->logPath)){
                unlink($this->logPath);
            }
            $this->deleteSaved($courseId);
        });
        API::registerFunction('settings', 'getHistory', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $history = $this->getClusterHistory($courseId);
            $evolution = $this->getClusterEvolution($courseId, $history[1], $history[0]);
            API::response(array('days' => $history[0],'history' => $history[1], 'nodes' => $evolution[0], 'data' => $evolution[1]));
        });
        API::registerFunction('settings', 'getSaved', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $saved = $this->getSavedClusters($courseId);
            $names = $this->createNamesArray($this->getClusterNames($courseId));
            API::response(array('saved' => $saved, 'names' => $names));
        });
    }

    public function deleteSaved($courseId){
        Core::$systemDB->delete("saved_user_profile", ["course" => $courseId]);
    }

    public function saveClusters($courseId, $clusters){
        foreach($clusters as $key => $value){
            $entry = Core::$systemDB->select("saved_user_profile", ["course" => $courseId, "user" => $key]);
            if (!$entry){
                Core::$systemDB->insert("saved_user_profile", ["cluster" => $value, "course" => $courseId, "user" => $key]);
            }
            else {
                Core::$systemDB->update("saved_user_profile", ["cluster" => $value], ["course" => $courseId, "user" => $key]);
            }
        }
    }

    public function getSavedClusters($courseId){
        $saved = Core::$systemDB->selectMultiple("saved_user_profile", ["course" => $courseId]);
        $result = [];
        foreach ($saved as $s){
            $result[$s['user']] = $s['cluster'];
        }
        return $result;
    }

    public function getClusterNames($courseId){
        // collect all cluster names: base names + roles inserte manually as Profiling role children
        $names = [];
        $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $studentIndex = 0;
        $profilingIndex = 0;
        $children = null;
        // get roles from hierarchy
        foreach ($hierarchy as $obj){
            if($obj->name == "Student"){
                foreach ($hierarchy[$studentIndex]->children as $children){
                    if($children->name == "Profiling"){
                        $children = $hierarchy[$studentIndex]->children[$profilingIndex]->children;
                        break;
                    }
                    $profilingIndex++;
                }
                break;
            }
            $studentIndex++;
        }

        if(!is_null($children)){
            foreach ($children as $child){
                if (!in_array($child->name, $names)){
                    array_push($names, $child->name);
                }
            }
        }
        return $names;
    }

    public function createNamesArray($clusters){
        $names = [];
        foreach ($clusters as $cluster){
            $names[] = array(
                "name" => $cluster
            );
        }
        return $names;
    }
    
    public function removeClusterRoles($courseId){
        $roleIds = Core::$systemDB->selectMultiple("user_profile", ["course" => $courseId], "distinct cluster");
        foreach($roleIds as $id){
            Core::$systemDB->delete("user_role", ["course" => $courseId, "role" => $id["cluster"]]);
        }
    }

    public function deleteClusterRoles($courseId){
        $roleIds = Core::$systemDB->selectMultiple("user_profile", ["course" => $courseId], "distinct cluster");
        foreach($roleIds as $id){
            Core::$systemDB->delete("role", ["course" => $courseId, "id" => $id["cluster"]]);
        }
        Core::$systemDB->delete("role", ["course" => $courseId, "name" => "Profiling"]);
       
        $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $studentIndex = 0;
        foreach ($hierarchy as $obj){
            if($obj->name == "Student"){
                break;
            }
            $studentIndex++;
        }
        $profilingIndex = 0;
        foreach ($hierarchy[$studentIndex]->children as $children){
            if($children->name == "Profiling"){
                unset($hierarchy[$studentIndex]->children[$profilingIndex]);
                break;
            }
            $profilingIndex++;
        }
        Core::$systemDB->update("course", ["roleHierarchy" => json_encode($hierarchy)], ["id" => $courseId]);
        
    }

    public function processClusterRoles($courseId, $clusters){
        $course = Course::getCourse($courseId);
        $names = [];
        $newRoles = [];
        // see which roles exist in the course to avoid repetition
        $courseRoles = $course->getRoles('name, id');
        foreach($courseRoles as $role){
            $names[$role['name']] = $role['id'];
        }

        // see which roles need to be created for the clusters
        foreach ($this->baseNames as $clusterName){
            if($clusterName and !array_key_exists($clusterName, $names)){
                $id = Core::$systemDB->insert("role", ["course" => $courseId, "name" => $clusterName]);
                $names[$clusterName] = $newRoles[$clusterName] = $id;
            }
        }
        $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $studentIndex = array_search("Student", array_keys($names));
        $profilingIndex = 0;

        foreach ($hierarchy[$studentIndex]->children as $children){
            if($children->name == "Profiling"){
                break;
            }
            $profilingIndex++;
        }

        if(!isset($hierarchy[$studentIndex]->children[$profilingIndex]->children))
            $hierarchy[$studentIndex]->children[$profilingIndex]->children = array();

        // roles belonging to clusters are children of the "Student" role
        foreach(array_keys($newRoles) as $newRole){
            $object = (object) [
                'name' => $newRole
            ];
            array_push($hierarchy[$studentIndex]->children[$profilingIndex]->children, $object);
        }

        // update role hierarchy in the course
        Core::$systemDB->update("course", ["roleHierarchy" => json_encode($hierarchy)], ["id" => $courseId]);

        // remove assigment of old cluster roles to students
        $this->removeClusterRoles($courseId);

        // assign new cluster roles to students
        $date = date('Y-m-d H:i:s');
        $students = $course->getUsersWithRole('Student');
        foreach ($students as $student){
            Core::$systemDB->insert("user_role", ["course" => $courseId, "id" => $student["id"], "role" => $names[$clusters[$student["id"]]]]);
            Core::$systemDB->insert("user_profile", ["course" => $courseId, "user" => $student["id"], "date" => $date, "cluster" => $names[$clusters[$student["id"]]]]);
        }

    }

    public function getClusterEvolution($courseId, $history, $days){
        $colors = ["#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];
        $colorNone = "#949494";
        $clusterNames = Core::$systemDB->selectMultiple("user_profile p left join role r on cluster = r.id and p.course = r.course", ["p.course" => $courseId], "distinct r.name");
        
        $nDays = count($days);
        $nodes = [];
        $data = [];
        $transitions = [];

        for($i = 0; $i < count($clusterNames); $i++){
            $color = $colors[$i];
            $name = $clusterNames[$i]['name'];
            for ($j = 0; $j < $nDays; $j++){
                $nodes[] = array(
                    "id" => $name . $j,
                    "name" => $name,
                    "color" => $color
                );
            }
        }

        if ($nDays == 1){
            $nodes[] = array("id" => "None", "color" => $colorNone);
        }

        foreach($history as $entry){
            if($nDays == 1){
                $from = "None";
                $to = $entry["history"][0]["cluster"] . 0;
                if(array_key_exists($from, $transitions) and array_key_exists($to, $transitions[$from])){
                    $transitions[$from][$to]++;
                }
                else {
                    $transitions[$from][$to] = 1;
                }
            }
            else {
                for ($i = 0; $i < $nDays - 1; $i++){
                    $from = $entry["history"][$i]["cluster"] . $i;
                    $to = $entry["history"][$i + 1]["cluster"] . ($i + 1);

                    if(array_key_exists($from, $transitions) and array_key_exists($to, $transitions[$from])){
                        $transitions[$from][$to]++;
                    }
                    else {
                        $transitions[$from][$to] = 1;
                    }
                }
            }  
        }
        
        foreach($transitions as $key => $value){
            foreach($value as $to => $weight){
                $data[] = [$key, $to, $weight];
            }
        }
        
        return array($nodes, $data);
    }

    public function getClusterHistory($courseId){
        $current = Core::$systemDB->selectMultiple("user_profile", ["course" => $courseId]);
        $days = Core::$systemDB->selectMultiple("user_profile", ["course" => $courseId], "distinct date");
        $clusters = [];
        
        if(!$current){
            $course = Course::getCourse($courseId);
            $students = $course->getUsersWithRole('Student');
            foreach ($students as $student){
                $exploded =  explode(' ', $student["name"]);
                $nickname = $exploded[0] . ' ' . end($exploded);
                $clusters[$student["id"]]['name'] = $nickname;
                $clusters[$student["id"]]['history'] = array(array('day' => date('Y-m-d H:i:s'), 'cluster' => "None"));
                
            }
        }
        else {
            $daysArray = [];
            foreach ($days as $day){
                $records = Core::$systemDB->selectMultiple("user_profile p left join game_course_user u on p.user = u.id left join role r on p.cluster = r.id", ["p.course" => $courseId, "r.course" => $courseId, "date" => $day["date"]], "u.name as name, r.name as cluster, p.user as id", "u.name");
                foreach ($records as $record){
                    $exploded =  explode(' ', $record["name"]);
                    $nickname = $exploded[0] . ' ' . end($exploded);
                    $clusters[$record["id"]]['name'] = $nickname;
                    $clusters[$record["id"]]['history'][] = array('day' => $day["date"], 'cluster' => $record["cluster"]);
                }
                array_push($daysArray, $day["date"]); // to return in a format that js can read easily
            }
            $days = $daysArray;
        }
        return array($days, $clusters);
    }

    public function createClusterList($courseId, $names, $assignedClusters){
        $course = Course::getCourse($courseId);
        $students = $course->getUsersWithRole('Student');
        $result = [];

        for ($i = 0; $i < count($students); $i++){
            $roles = $course->getUser($students[$i]["id"])->getRolesNames();
            $exploded =  explode(' ', $students[$i]["name"]);
            $nickname = $exploded[0] . ' ' . end($exploded);
            $result[$students[$i]["id"]]['name'] = $nickname;
            $result[$students[$i]["id"]]['cluster'] = $names[$assignedClusters[$i]];
        }
        return $result;
    }

    public function runProfiler($courseId) {
        $myfile = fopen($this->logPath, "w");
        $cmd = "python3 ". $this->scriptPath . " " . strval($courseId) ." > /dev/null &"; //python3
        exec($cmd);
    }
    
    public function checkStatus($courseId){
        clearstatcache();
        if(filesize($this->logPath)) {
            $file = fopen($this->logPath, 'r');
            $line = fgets($file);
            fclose($file);
            if (stripos($line,"error") !== false) {
                return array("errorMessage" => $line);
            }
            else {
                $exploded = explode('+', $line);
                $assignedClusters = explode(',', str_replace(["[", "]", " ","\n","\r"], "", $exploded[1]));
                $clusters = explode(',', str_replace(["{", "}"], "", $exploded[0]));

                // creating cluster array and sorting cluster indexes based on grade
                $array = [];
                foreach($clusters as $cluster) {
                    $pair = explode(':', $cluster);
                    $array[$pair[0]] = str_replace([" "], "", $pair[1]);
                }
                if(krsort($array)){
                    
                    $names = $this->getClusterNames($courseId);

                    // assign cluster names by replacing key with cluster name
                    $namedClusters = [];
                    $i = 0;
                    foreach($array as $entry) {
                        $namedClusters[$entry] = $names[$i];
                        $i++;
                    }
                    // update time of the last run on bd
                    Core::$systemDB->update("profiling_config", ["lastRun" => date('Y-m-d H:i:s')], ["course" => $courseId]);

                    return $this->createClusterList($courseId, $namedClusters, $assignedClusters);     
                }
            }
        
        }
        else {
            return array();
        }
    }

    public static function exportItems($courseId) {
        $courseInfo = Core::$systemDB->select("course", ["id"=>$courseId]);
        $profileList = Core::$systemDB->selectMultiple("user_profile p left join role r on cluster = r.id and p.course = r.course left join auth a on p.user = a.game_course_user_id", ["p.course" => $courseId], "user, name, username", "user, date");
        $days = Core::$systemDB->selectMultiple("user_profile", ["course" => $courseId], "distinct date");
        $nDays = count($days);
        $i = $nDays;
        $file = "name;username";
        foreach($days as $day){
            $file .= ";" . $day['date'];
        }
        foreach($profileList as $profile){
            if ($i >= $nDays){
                $i = 0;
                $file .= "\n";
                $file .= $profile['user'] . ";" . $profile['username'] ;
            }
            $file .= ';' . $profile['name'];
            $i++;
        }
        $file .= "\n";
        return ["Profiles - " . $courseInfo["name"], $file];
    }

    public static function importItems($courseId, $filedata, $replace = true) {
        $lines = explode("\n", $fileData);
        $has1stLine = false;
        $usernameIndex = 0;
        $clusterIndex = 1;
        $date = date('Y-m-d H:i:s');

        if ($lines[0]) {
            $lines[0] = trim($lines[0]);
            $firstLine = explode(";", $lines[0]);
            $firstLine = array_map('trim', $firstLine);
            if (in_array("username", $firstLine) && in_array("cluster", $firstLine)) {
                $has1stLine = true;
                $usernameIndex = array_search("username", $firstLine);
                $clusterIndex = array_search("cluster", $firstLine);
            }
        }
        foreach ($lines as $line) {
            $line = trim($line);
            $item = explode(";", $line);
            $item = array_map('trim', $item);
            if (count($item) > 1){
                $roleId = Core::$systemDB->select("role", ["course" => $courseId, "name" => $line[$clusterIndex]], "id");
                $studentId = Core::$systemDB->select("course_user u left join auth a on u.id = a.game_course_user_id", ["course" => $courseId, "username" => $line[$usernameIndex]], "u.id as id");
                if ($roleId and $studentId){
                    Core::$systemDB->insert("user_role", ["course" => $courseId, "id" => $studentId["id"], "role" => $roleId["id"]]);
                    Core::$systemDB->insert("user_profile", ["course" => $courseId, "user" => $studentId["id"], "date" => $date, "cluster" => $roleId["id"]]);  
                } 
            }
        }
    }

    public function is_configurable(){
        return true;
    }

    public function has_personalized_config (){ return true;}
    public function get_personalized_function(){ return "profilingPersonalizedConfig";}
    
    public function has_general_inputs (){ return false; }
    public function has_listing_items (){ return  false; }

    public function deleteDataRows($courseId){
        $this->deleteClusterRoles($courseId);
        Core::$systemDB->delete("profiling_config", ["course" => $courseId]);
        Core::$systemDB->delete("saved_user_profile", ["course" => $courseId]);
    }
    
    public function dropTables($moduleName) {
        $courseId = API::getValue('course');
        $this->deleteClusterRoles($courseId);
        parent::dropTables($moduleName);
    }

    public function update_module($compatibleVersions) {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'profiling',
    'name' => 'Profiling',
    'description' => 'Assigns students to clusters according to their profile.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard'),
        array('id' => 'badges', 'mode' => 'hard'),
        array('id' => 'xp', 'mode' => 'hard'),
        array('id' => 'plugin', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Profiling();
    }
));
?>
