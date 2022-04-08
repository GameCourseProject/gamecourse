<?php
namespace Modules\Profiling;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use Modules\Badges\Badges;
use Modules\Skills\Skills;
use Modules\XP\XPLevels;

class Profiling extends Module
{
    const ID = 'profiling';

    const TABLE_CONFIG = "profiling_config";
    const TABLE_USER_PROFILE = "user_profile";
    const TABLE_SAVED_USER_PROFILE = "saved_user_profile";

    private $scriptPath = SERVER_PATH . "/" . MODULES_FOLDER . "/" . self::ID . "/profiler.py";
    private $logPath = SERVER_PATH . "/" . MODULES_FOLDER . "/" . self::ID . "/";
    private $predictorPath = SERVER_PATH . "/" . MODULES_FOLDER . "/" . self::ID . "/predictor.py";
    
    // cluster names
    private $baseNames = ["Achiever", "Regular", "Halfhearted", "Underachiever"];
    // colors
    private $colorNone = "#949494";


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->setupData($this->getCourseId());
    }

    public function initAPIEndpoints()
    {
        API::registerFunction(self::ID, 'getHistory', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $history = $this->getClusterHistory($courseId);
            $evolution = $this->getClusterEvolution($courseId, $history[1], $history[0]);

            API::response(array('days' => $history[0], 'history' => $history[1], 'nodes' => $evolution[0], 'data' => $evolution[1]));
        });

        API::registerFunction(self::ID, 'getTime', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $time = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "lastRun");

            API::response(array('time' => $time));
        });

        API::registerFunction(self::ID, 'getSaved', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $saved = $this->getSavedClusters($courseId);
            $names = $this->createNamesArray($this->getClusterNames($courseId, true));

            API::response(array('saved' => $saved, 'names' => $names));
        });

        API::registerFunction(self::ID, 'runPredictor', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'method');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $method = API::getValue('method');
            $this->runPredictor($courseId, $method);
        });

        API::registerFunction(self::ID, 'runProfiler', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'nrClusters', 'minSize', 'end');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $nrClusters = API::getValue('nrClusters');
            $minSize = API::getValue('minSize');
            $end = API::getValue('end');
            $this->runProfiler($courseId, $nrClusters, $minSize, $end);
        });

        API::registerFunction(self::ID, 'checkRunningStatus', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            if(file_exists($this->getLogPath($courseId))){
                $clusters = $this->checkStatus($courseId);

                if(array_key_exists("errorMessage", $clusters)){
                    if (file_exists($this->getLogPath($courseId))){
                        unlink($this->getLogPath($courseId));
                    }
                    API::error($clusters["errorMessage"], 400);
                }
                if(empty($clusters)){
                    API::response(array('running' => true));
                }

                $names = $this->createNamesArray($this->getClusterNames($courseId, true));
                API::response(array('clusters' => $clusters, 'names' => $names));
            }
            else {
                API::response(array('running' => false));
            }
        });

        API::registerFunction(self::ID, 'checkPredictorStatus', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            if(file_exists($this->getPredictorPath($courseId))){
                $result = $this->checkPredictorStatus($courseId);

                if(array_key_exists("errorMessage", $result)){
                    if (file_exists($this->getPredictorPath($courseId))){
                        unlink($this->getPredictorPath($courseId));
                    }
                    API::error($result["errorMessage"], 400);
                }
                if(empty($result)){
                    API::response(array('predicting' => true));
                }
                unlink($this->getPredictorPath($courseId));
                API::response(array('nrClusters' => $result["nrClusters"]));
            }
            else {
                API::response(array('predicting' => false));
            }
        });

        API::registerFunction(self::ID, 'saveClusters', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'clusters');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $clusters = API::getValue('clusters');
            $this->saveClusters($courseId, $clusters);
        });

        API::registerFunction(self::ID, 'commitClusters', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'clusters');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $clusters = API::getValue('clusters');
            if (file_exists($this->getLogPath($courseId))){
                unlink($this->getLogPath($courseId));
            }
            $this->processClusterRoles($courseId, $clusters);
            $this->deleteSaved($courseId);
        });

        API::registerFunction(self::ID, 'deleteSaved', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            if (file_exists($this->getLogPath($courseId))){
                unlink($this->getLogPath($courseId));
            }
            $this->deleteSaved($courseId);
        });
    }

    public function setupResources() {
        parent::addResources('css/');
    }

    public function setupData($courseId){
        // TODO: add baseNames as well on setup

        if ($this->addTables(self::ID, self::TABLE_CONFIG) || empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId])))
            Core::$systemDB->insert(self::TABLE_CONFIG, ["course" => $courseId]);

        $course = Course::getCourse($courseId, false);
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

    public function update_module($compatibleVersions) {
        //verificar compatibilidade
    }

    public function disable(int $courseId)
    {
        $this->deleteClusterRoles($courseId);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_personalized_config (): bool
    {
        return true;
    }

    public function get_personalized_function(): string
    {
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows($courseId){
        $this->deleteClusterRoles($courseId);
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
        Core::$systemDB->delete(self::TABLE_SAVED_USER_PROFILE, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    public function importItems(string $fileData, bool $replace = true): int
    {
        $courseId = $this->getCourseId();
        $lines = explode("\n", $fileData);

        if ($lines[0]) {
            $lines[0] = trim($lines[0]);
            $firstLine = explode(";", $lines[0]);
            $firstLine = array_map('trim', $firstLine);
            if (in_array("username", $firstLine) && count($firstLine) > 1) {
                $ndays = count($firstLine) - 1;
                foreach ($lines as $line) {
                    $line = trim($line);
                    $item = explode(";", $line);
                    $item = array_map('trim', $item);
                    if (count($item) == $ndays + 1){
                        $studentId = Core::$systemDB->select("course_user u join auth a on u.id = a.game_course_user_id", ["course" => $courseId, "username" => $item[0]], "u.id");
                        if (!empty($studentId)){
                            for($i = 1; $i < count($item); $i++){
                                $roleId = Core::$systemDB->select("role", ["course" => $courseId, "name" => $item[$i]], "id");
                                if($roleId){
                                    $record = Core::$systemDB->select(self::TABLE_USER_PROFILE, ["course" => $courseId, "user" => $studentId, "date" => $firstLine[$i]]);
                                    if ($record and $replace){
                                        Core::$systemDB->update(self::TABLE_USER_PROFILE, ["course" => $courseId, "user" => $studentId, "date" => $firstLine[$i], "cluster" => $roleId], ["course" => $courseId, "user" => $studentId, "date" => $firstLine[$i]]);
                                    }
                                    else {
                                        Core::$systemDB->insert(self::TABLE_USER_PROFILE, ["course" => $courseId, "user" => $studentId, "date" => $firstLine[$i], "cluster" => $roleId]);
                                    }

                                    if($i == count($item)){
                                        Core::$systemDB->insert("user_role", ["course" => $courseId, "id" => $studentId, "role" => $roleId]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return 0;
    }

    public function exportItems(int $itemId = null): array
    {
        $courseId = $this->getCourseId();
        $course = Course::getCourse($courseId, false);

        $profileList = Core::$systemDB->selectMultiple(self::TABLE_USER_PROFILE . " p left join role r on cluster = r.id and p.course = r.course left join auth a on p.user = a.game_course_user_id", ["p.course" => $courseId], "name, username, date", "user, date");
        $days = Core::$systemDB->selectMultiple(self::TABLE_USER_PROFILE, ["course" => $courseId], "distinct date");
        $nDays = count($days);
        $data = [];

        $file = "username";
        foreach($days as $day){
            $file .= ";" . $day['date'];
        }
        foreach($profileList as $profile){
            $data[$profile['username']][$profile["date"]] = $profile['name'];
        }

        foreach($data as $key => $value){
            $file .= "\n";
            $file .= $key ;
            foreach($days as $day){
                $file .= ';' . (array_key_exists($day['date'], $value) ? $value[$day['date']] : "");
            }
        }
        $file .= "\n";
        return ["Profiles - " . $course->getName(), $file];
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getLogPath($courseId): string
    {
        return $this->logPath . $courseId . "-results.txt";
    }

    public function getPredictorPath($courseId): string
    {
        return $this->logPath . $courseId . "-prediction.txt";
    }

    public function deleteSaved($courseId){
        Core::$systemDB->delete(self::TABLE_SAVED_USER_PROFILE, ["course" => $courseId]);
    }

    public function saveClusters($courseId, $clusters){
        if(!is_null($clusters)){
            foreach($clusters as $key => $value){
                $entry = Core::$systemDB->select(self::TABLE_SAVED_USER_PROFILE, ["course" => $courseId, "user" => $key]);
                if (!$entry){
                    Core::$systemDB->insert(self::TABLE_SAVED_USER_PROFILE, ["cluster" => $value, "course" => $courseId, "user" => $key]);
                }
                else {
                    Core::$systemDB->update(self::TABLE_SAVED_USER_PROFILE, ["cluster" => $value], ["course" => $courseId, "user" => $key]);
                }
            }
        }
    }

    public function getSavedClusters($courseId): array
    {
        $saved = Core::$systemDB->selectMultiple(self::TABLE_SAVED_USER_PROFILE, ["course" => $courseId]);
        $result = [];
        foreach ($saved as $s){
            $result[$s['user']] = $s['cluster'];
        }
        return $result;
    }

    public function getClusterNames(int $courseId, bool $all = false): array
    {
        // collect all cluster names
        $names = [];
        $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        $studentIndex = 0;
        $profilingIndex = 0;
        $children = [];
        if($hierarchy){
            // get roles from hierarchy
            foreach ($hierarchy as $obj){
                if($obj->name == "Student"){
                    foreach ($hierarchy[$studentIndex]->children as $child){
                        if($child->name == "Profiling"){
                            if ($all) {
                                // all children of Profiling role: direct and indirect
                                $this->traverse($child->children, $children);
                                break;

                            } else if (isset($hierarchy[$studentIndex]->children[$profilingIndex]->children)) {
                                // roles inserted manually as Profiling role direct children
                                $children = $hierarchy[$studentIndex]->children[$profilingIndex]->children;
                                break;
                            }
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
            else{
                $names = $this->baseNames;
            }
        }
        return $names;
    }

    public function createNamesArray($clusters): array
    {
        $names = [];
        foreach ($clusters as $cluster){
            $names[] = array(
                "name" => $cluster
            );
        }
        return $names;
    }
    
    public function removeClusterRoles($courseId){
        $roleIds = Core::$systemDB->selectMultiple(self::TABLE_USER_PROFILE, ["course" => $courseId], "distinct cluster");
        foreach($roleIds as $id){
            Core::$systemDB->delete("user_role", ["course" => $courseId, "role" => $id["cluster"]]);
        }
    }

    public function deleteClusterRoles($courseId){
        Core::$systemDB->delete("role", ["course" => $courseId, "name" => "Profiling"]);
       
        $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
        if($hierarchy){
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
                    if(isset($children->children)){
                        foreach ($children->children as $role){
                            Core::$systemDB->delete("role", ["course" => $courseId, "name" => $role->name]);
                        }
                    }
                    unset($hierarchy[$studentIndex]->children[$profilingIndex]);
                    break;
                }
                $profilingIndex++;
            }
            Core::$systemDB->update("course", ["roleHierarchy" => json_encode($hierarchy)], ["id" => $courseId]);
        }
    }

    public function processClusterRoles($courseId, $clusters){
        $course = Course::getCourse($courseId, false);
        $currentNames = [];

        if(!empty($clusters)){
            // see which roles exist in the course to avoid repetition
            $courseRoles = $course->getRoles('name, id');
            foreach($courseRoles as $role){
                $currentNames[$role['name']] = $role['id'];
            }

            // see which roles need to be created for the clusters
            $newRoles = [];
            $clusterNames = $this->getClusterNames($courseId, true);
            foreach ($clusterNames as $clusterName){
                if($clusterName and !array_key_exists($clusterName, $currentNames)){
                    $id = Core::$systemDB->insert("role", ["course" => $courseId, "name" => $clusterName]);
                    $newRoles[$clusterName] = $id;
                }
            }

            // update hierarchy
            if (count($newRoles) > 0) {
                $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $courseId], "roleHierarchy"));
                $studentIndex = array_search("Student", array_keys($currentNames));
                $profilingIndex = 0;

                foreach ($hierarchy[$studentIndex]->children as $child){
                    if ($child->name == "Profiling") break;
                    $profilingIndex++;
                }

                // new roles belonging to clusters are children of the "Profiling" role
                foreach($newRoles as $cluster){
                    $object = (object) [
                        'name' => $cluster
                    ];
                    array_push($hierarchy[$studentIndex]->children[$profilingIndex]->children, $object);
                }

                // update role hierarchy in the course
                Core::$systemDB->update("course", ["roleHierarchy" => json_encode($hierarchy)], ["id" => $courseId]);
            }

            // remove assigment of old cluster roles to students
            $this->removeClusterRoles($courseId);

            // assign new cluster roles to students
            $date = date('Y-m-d H:i:s');
            $students = $course->getUsersWithRole('Student', true);
            
            foreach ($students as $student){
                Core::$systemDB->insert("user_role", ["course" => $courseId, "id" => $student["id"], "role" => $currentNames[$clusters[$student["id"]]]]);
                Core::$systemDB->insert(self::TABLE_USER_PROFILE, ["course" => $courseId, "user" => $student["id"], "date" => $date, "cluster" => $currentNames[$clusters[$student["id"]]]]);
            }
        }
    }

    public function getClusterEvolution($courseId, $history, $days): array
    {
        $colors = ["#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];
        $clusterNames = $this->getClusterNames($courseId, true);

        $nDays = count($days);
        $nodes = [];
        $data = [];
        $transitions = [];
        $nameOrder = [];

        // Set order
        for($i = 0; $i < count($clusterNames); $i++){
            $color = $colors[$i];
            $name = $clusterNames[$i];
            for ($j = 0; $j < $nDays; $j++){
                $nodes[] = array(
                    "id" => $name . $j,
                    "name" => $name,
                    "color" => $color
                );
                $nameOrder[] = $name . $j;
            }
        }
        for ($j = 0; $j < $nDays; $j++){
            $nodes[] = array(
                "id" => "None" . $j,
                "name" => "None",
                "color" => $this->colorNone
            );
            $nameOrder[] = "None" . $j;
        }

        // Set all transitions possible (this forces the order defined previously)
        foreach ($nameOrder as $from) {
            $fromOrder = intval(substr($from, -1));
            if ($fromOrder < $nDays) {
                foreach ($nameOrder as $to) {
                    $toOrder = intval(substr($to, -1));
                    if ($from != $to && $toOrder == $fromOrder + 1) {
                        $transitions[$from][$to] = 0;
                    }
                }
            }
        }

        foreach($history as $entry){
            if($nDays == 1){
                $from = "None0";
                $order = 0;
                if(strcmp($entry[array_keys($entry)[2]], "None") == 0){
                    $order = 1;
                }
                $to = $entry[array_keys($entry)[2]] . $order;
                if(array_key_exists($from, $transitions) and array_key_exists($to, $transitions[$from])){
                    $transitions[$from][$to]++;
                }
                else {
                    $transitions[$from][$to] = 1;
                }
            }
            else {
                $k = 0;
                for ($i = 2; $i < $nDays + 1; $i++){
                    $from = $entry[array_keys($entry)[$i]] . $k;
                    $to = $entry[array_keys($entry)[$i + 1]] . ($k + 1);

                    if(array_key_exists($from, $transitions) and array_key_exists($to, $transitions[$from])){
                        $transitions[$from][$to]++;
                    }
                    else {
                        $transitions[$from][$to] = 1;
                    }
                    $k++;
                }
            }  
        }
        
        foreach($transitions as $key => $value){
            foreach($value as $to => $weight){
                $data[] = [$key, $to, $weight];
            }
        }

        usort($data, function ($a, $b) use ($nameOrder) {
            $pos_from_a = array_search($a[0], $nameOrder);
            $pos_to_a = array_search($a[1], $nameOrder);
            $pos_from_b = array_search($b[0], $nameOrder);
            $pos_to_b = array_search($b[1], $nameOrder);

            return $pos_from_a - $pos_from_b?: $pos_to_a - $pos_to_b;
        });
        
        return array($nodes, $data);
    }

    public function getClusterHistory($courseId): array
    {
        $days = Core::$systemDB->selectMultiple(self::TABLE_USER_PROFILE, ["course" => $courseId], "distinct date");
        $clusters = [];
        
        if(!$days){
            $course = Course::getCourse($courseId, false);
            $students = $course->getUsersWithRole('Student', true);
            foreach ($students as $student){
                $exploded =  explode(' ', $student["name"]);
                $nickname = $exploded[0] . ' ' . end($exploded);
               
                // if you change the name of the column when there are no clusters assigned, change "Current" to that exact name
                $clusters[] = array ('id' => $student["id"],'name' => $nickname, "Current" => "None");
                
            }
        }
        
        else {
            $daysArray = [];
            foreach ($days as $day){
                $records = Core::$systemDB->selectMultiple("(SELECT u.name as name, cu.id as id FROM course_user cu join game_course_user u on cu.id=u.id join user_role ur on ur.id=u.id join role r on ur.role = r.id where r.name = \"Student\" and cu.course =" . $courseId . " and ur.course =" . $courseId . " and cu.isActive=true) a left join (select p.user as user, r1.name as cluster from " . self::TABLE_USER_PROFILE . " p left join role r1 on p.cluster = r1.id where p.course = " . $courseId . " and r1.course = " . $courseId . " and date = \"" . $day["date"] . "\") b on a.id=b.user", [], "a.name, a.id, b.cluster");
                foreach ($records as $record){
                    $exploded =  explode(' ', $record["name"]);
                    $nickname = $exploded[0] . ' ' . end($exploded);
                    $id = array_search($record["id"], array_column($clusters, 'id'));

                    if($record["cluster"] === null){
                        $record["cluster"] = "None";
                    }
   
                    if ($id === false){
                        $clusters[] = array ('id' => $record["id"], 'name' => $nickname, $day["date"] => $record["cluster"]);
                    }
                    else {
                        $clusters[$id][$day["date"]] = $record["cluster"];
                    }
                
                }
                array_push($daysArray, $day["date"]); // to return in a format that js can read easily
            }
            $days = $daysArray;
        }
        return array($days, $clusters);
    }

    public function createClusterList($courseId, $names, $assignedClusters): array
    {
        $course = Course::getCourse($courseId, false);
        $students = $course->getUsersWithRole('Student', true);
        $result = [];

        if(!empty($assignedClusters) and !empty($names)){
            for ($i = 0; $i < count($students); $i++){
                $exploded =  explode(' ', $students[$i]["name"]);
                $nickname = $exploded[0] . ' ' . end($exploded);
                $result[$students[$i]["id"]]['name'] = $nickname;
                $result[$students[$i]["id"]]['cluster'] = $names[$assignedClusters[$i]];
            }
        }
        return $result;
    }

    public function runPredictor($courseId, $method) {
        $cmd = "python3 ". $this->predictorPath . " " . strval($courseId) . " " . $method . " > /dev/null &"; //python3
        system($cmd);
    }

    public function runProfiler($courseId, $nClusters, $minClusterSize, $end) {
        $cmd = "python3 ". $this->scriptPath . " " . strval($courseId) . " " . strval($nClusters) . " " . strval($minClusterSize) . " '" . $end . "' > /dev/null &"; //python3
        system($cmd);
    }
    
    public function checkStatus($courseId){
        clearstatcache();
        if(file_exists($this->getLogPath($courseId)) and filesize($this->getLogPath($courseId))) {
            $file = fopen($this->getLogPath($courseId), 'r');
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
                    if(!empty($names)){
                        foreach($array as $entry) {
                            $namedClusters[$entry] = $names[$i];
                            $i++;
                        }
                    }
                    // update time of the last run on bd
                    Core::$systemDB->update(self::TABLE_CONFIG, ["lastRun" => date('Y-m-d H:i:s')], ["course" => $courseId]);

                    return $this->createClusterList($courseId, $namedClusters, $assignedClusters);     
                }
            }
        
        }
        else {
            return array();
        }
    }

    public function checkPredictorStatus($courseId): array
    {
        clearstatcache();
        if(file_exists($this->getPredictorPath($courseId)) and filesize($this->getPredictorPath($courseId))) {
            $file = fopen($this->getPredictorPath($courseId), 'r');
            $line = fgets($file);
            fclose($file);
            if (stripos($line,"error") !== false) {
                return array("errorMessage" => $line);
            }

            return array("nClusters" => $line);
        }
        else{
            return array();
        }
    }

    private function traverse($hierarchy, &$children) {
        foreach ($hierarchy as $obj){
            $children[] = $obj;          // Add own
            if (isset($obj->children))   // If has children, add them
                $this->traverse($obj->children, $children);
        }
    }
}

ModuleLoader::registerModule(array(
    'id' => Profiling::ID,
    'name' => 'Profiling',
    'description' => 'Assigns students to clusters according to their profile.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => Badges::ID, 'mode' => 'hard'),
        array('id' => Skills::ID, 'mode' => 'hard'),
        array('id' => XPLevels::ID, 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Profiling();
    }
));
