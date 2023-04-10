<?php
namespace GameCourse\Module\Profiling;

use DateTime;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Role\Role;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use Utils\Utils;

/**
 * This is the Profiling module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Profiling extends Module
{
    const TABLE_PROFILING_CONFIG = "profiling_config";
    const TABLE_PROFILING_USER_PROFILE = "profiling_user_profile";
    const TABLE_PROFILING_SAVED_USER_PROFILE = "profiling_saved_user_profile";

    const PROFILING_ROLE = self::ID;
    const BASE_CLUSTER_NAMES = ["Achiever", "Regular", "Halfhearted", "Underachiever"];
    const COLOR_NONE = "#949494";

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Profiling";  // NOTE: must match the name of the class
    const NAME = "Profiling";
    const DESCRIPTION = "Assigns students to clusters according to their profile.";
    const TYPE = ModuleType::UTILITY;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Awards::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];

    const LOGS_FOLDER = "profiling";


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->initDatabase();

        // Init config
        Core::database()->insert(self::TABLE_PROFILING_CONFIG, ["course" => $this->course->getId()]);

        // Add main profiling role
        $this->course->addRole(self::PROFILING_ROLE, null, null, self::ID);
        $hierarchy = $this->course->getRolesHierarchy();
        $studentIndex = array_search("Student", Role::DEFAULT_ROLES);
        $hierarchy[$studentIndex]["children"][] = ["name" => self::PROFILING_ROLE];
        $this->course->setRolesHierarchy($hierarchy);

        // Create cluster roles
        $hierarchy = $this->course->getRolesHierarchy();
        //$profilingIndex = array_search(self::PROFILING_ROLE, $hierarchy[$studentIndex]["children"]);
        $clusterNames = $this->getClusterNames();
        $profilingInCourses = Core::database()->select(self::TABLE_COURSE_MODULE, ["module" => $this->id, "isEnabled" => true], "count(course)");

        foreach ($clusterNames as $name) {
            if (!$this->course->hasRole($name)) {
                $this->course->addRole($name, null, null, $this->id);

                // Update hierarchy
                foreach($hierarchy[$studentIndex]["children"] as $key => $value){
                    if ($value["name"] == self::PROFILING_ROLE){
                        $hierarchy[$studentIndex]["children"][$key]["children"][] = ["name" => $name];
                        break;
                    }
                }

                // add profiling clusters to system if not enabled in any course
                if (intval($profilingInCourses) <= 1) {
                    // NOTE: add role to system, otherwise transferring views w/
                    //       this role's aspects will fail
                    Core::database()->setForeignKeyChecks(false);
                    (new Course(0))->addRole($name, null, null, $this->id);
                    Core::database()->setForeignKeyChecks(true);
                }
            }
        }
        $this->course->setRolesHierarchy($hierarchy);

        // NOTE: add role to system, otherwise transferring views w/
        //       this role's aspects will fail
        Core::database()->setForeignKeyChecks(false);
        (new Course(0))->addRole(self::PROFILING_ROLE, null, null, self::ID);
        Core::database()->setForeignKeyChecks(true);

        // Setup logging
        $logsFile = self::getLogsFile($this->getCourse()->getId(), false);
        Utils::initLogging($logsFile);
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->cleanDatabase();
        // Remove all profiling roles
        $hierarchy = $this->course->getRolesHierarchy();
        $studentIndex = array_search("Student", Role::DEFAULT_ROLES);
        foreach ($hierarchy[$studentIndex]["children"] as $i => $child) {
            if ($child["name"] == self::PROFILING_ROLE) {
                array_splice($hierarchy[$studentIndex]["children"], $i, 1);
                break;
            }
        }
        $this->course->setRolesHierarchy($hierarchy);

        $this->course->removeRole(null, null, self::ID);

        // Remove profiling roles from system if not enabled in any course
        if(empty(Core::database()->select(self::TABLE_COURSE_MODULE, ["module" => $this->id, "isEnabled" => true]))) {
            (new Course(0))->removeRole(null, null, self::ID);
        }
        // Remove logs
        if (file_exists($this->getPredictorLogsPath())) unlink($this->getPredictorLogsPath());
        if (file_exists($this->getProfilerLogsPath())) unlink($this->getProfilerLogsPath());
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getPersonalizedConfig(): ?array
    {
        return ["position" => "before"];
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    // TODO


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** --------- Overview --------- ***/

    /**
     * @throws Exception
     */
    public function getClusterHistory():array
    {
        $courseId = $this->course->getId();
        $days = array_column(Core::database()->selectMultiple(self::TABLE_PROFILING_USER_PROFILE, ["course" => $courseId], "DISTINCT date"), "date");
        $clusters = [];

        if (!$days) { // no clusters yet
            $students = $this->course->getStudents(true);
            foreach ($students as $student) {
                $exploded = explode(" ", $student["name"]);
                $shortName = $exploded[0] . " " . end($exploded);

                // NOTE: if you change the name of the column when there are no clusters assigned, change "Current" to that exact name
                $clusters[] = ["id" => $student["id"], "name" => $shortName, "Current" => "None"];
            }

        } else { // has clusters
            foreach ($days as $day) {
                // Get records of day
                $table = "(SELECT u.name as name, cu.id as id FROM " . CourseUser::TABLE_COURSE_USER . " cu JOIN " .
                    User::TABLE_USER . " u on cu.id=u.id JOIN " . Role::TABLE_USER_ROLE . " ur on ur.user=u.id JOIN " .
                    Role::TABLE_ROLE . " r on ur.role = r.id WHERE r.name = \"Student\" and cu.course = $courseId and
                    ur.course = $courseId and cu.isActive=true) a LEFT JOIN (SELECT p.user as user, r1.name as cluster FROM " .
                    self::TABLE_PROFILING_USER_PROFILE . " p LEFT JOIN " . Role::TABLE_ROLE . " r1 on p.cluster = r1.id " .
                    "WHERE p.course = $courseId and r1.course = $courseId and date = \"$day\") b on a.id=b.user";
                $records = Core::database()->selectMultiple($table, [], "a.name, a.id, b.cluster");

                foreach ($records as $record) {
                    $exploded =  explode(" ", $record["name"]);
                    $shortName = $exploded[0] . " " . end($exploded);
                    $id = array_search($record["id"], array_column($clusters, "id"));

                    if (is_null($record["cluster"]))
                        $record["cluster"] = "None";

                    if ($id === false)
                        $clusters[] = ["id" => $record["id"], "name" => $shortName, $day => $record["cluster"]];
                    else
                        $clusters[$id][$day] = $record["cluster"];

                }
            }
        }

        return ["days" => $days, "clusters" => $clusters];
    }

    /**
     * @throws Exception
     */
    public function getClusterEvolution(array $history, array $days): array
    {
        $colors = ["#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];
        $clusterNames = $this->getClusterNames();

        $nDays = count($days);
        $nodes = [];
        $data = [];
        $transitions = [];
        $nameOrder = [];

        // Set order
        for ($i = 0; $i < count($clusterNames); $i++) {
            $color = $colors[$i];
            $name = $clusterNames[$i];
            for ($j = 0; $j < $nDays; $j++) {
                $nodes[] = ["id" => $name . $j, "name" => $name, "color" => $color];
                $nameOrder[] = $name . $j;
            }
        }
        for ($j = 0; $j < $nDays; $j++) {
            $nodes[] = ["id" => "None" . $j, "name" => "None", "color" => self::COLOR_NONE];
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

        foreach ($history as $entry) {
            if ($nDays == 1) {
                $from = "None0";
                $order = 0;

                if (strcmp($entry[array_keys($entry)[2]], "None") == 0)
                    $order = 1;

                $to = $entry[array_keys($entry)[2]] . $order;

                if (array_key_exists($from, $transitions) && array_key_exists($to, $transitions[$from]))
                    $transitions[$from][$to]++;
                else
                    $transitions[$from][$to] = 1;

            } else {
                $k = 0;
                for ($i = 2; $i < $nDays + 1; $i++) {
                    $from = $entry[array_keys($entry)[$i]] . $k;
                    $to = $entry[array_keys($entry)[$i + 1]] . ($k + 1);

                    if (array_key_exists($from, $transitions) && array_key_exists($to, $transitions[$from]))
                        $transitions[$from][$to]++;
                    else
                        $transitions[$from][$to] = 1;
                    $k++;
                }
            }
        }

        foreach ($transitions as $key => $value) {
            foreach ($value as $to => $weight) {
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

        return ["nodes" => $nodes, "data" => $data];
    }


    /*** -------- Predictor --------- ***/

    public function getPredictorLogsPath(bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/profiling_prediction_" . $this->course->getId() . ".txt";

        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        return $path;
    }

    public function runPredictor(string $method, string $endDate)
    {
        $dbHost = DB_HOST;
        $dbName = DB_NAME;
        $dbUser = DB_USER;
        $dbPass = DB_PASSWORD;

        $courseId = $this->course->getId();
        $predictorPath = MODULES_FOLDER . "/" . self::ID . "/scripts/predictor.py";
        $logsPath = $this->getPredictorLogsPath();

        $cmd = "python3 \"$predictorPath\" $courseId \"$method\" \"$endDate\" \"$logsPath\" \"$dbHost\" \"$dbName\" \"$dbUser\" \" $dbPass \" > /dev/null &";
        system($cmd);
    }

    public function checkPredictorStatus(): array
    {
        if (file_exists($this->getPredictorLogsPath())) {
            // Get result
            clearstatcache();
            if (filesize($this->getPredictorLogsPath())) { // done predicting
                $file = fopen($this->getPredictorLogsPath(), "r");
                $line = fgets($file);
                fclose($file);

                if (stripos($line,"error") !== false)
                    $result = ["errorMessage" => $line];
                else $result = ["nrClusters" => intval($line)];

            } else $result = []; // still predicting

            if (array_key_exists("errorMessage", $result)) { // error caught
                unlink($this->getPredictorLogsPath());
                return ["error" => $result["errorMessage"]];
            }

            if (empty($result))
                return ["predicting" => true];

            unlink($this->getPredictorLogsPath());
            return ["nrClusters" => $result["nrClusters"]];

        } else return ["predicting" => false];
    }


    /*** --------- Profiler --------- ***/

    public function getLastRun(): ?string
    {
        return Core::database()->select(self::TABLE_PROFILING_CONFIG, ["course" => $this->course->getId()], "lastRun");
    }

    public function getProfilerLogsPath(bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/profiling_results_" . $this->course->getId() . ".txt";

        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        return $path;
    }

    /**
     * @throws Exception
     */
    public function runProfiler(int $nrClusters, int $minClusterSize, string $endDate)
    {
        $dbHost = DB_HOST;
        $dbName = DB_NAME;
        $dbUser = DB_USER;
        $dbPass = DB_PASSWORD;

        $courseId = $this->course->getId();
        $profilerPath = MODULES_FOLDER . "/" . self::ID . "/scripts/profiler.py";
        $logsPath = $this->getProfilerLogsPath();

        $cmd = "python3 \"$profilerPath\" $courseId $nrClusters $minClusterSize \"$endDate\" \"$logsPath\" \"$dbHost\" \"$dbName\" \"$dbUser\" \" $dbPass \" > /dev/null &";
        system($cmd);

        // update time of the last run on bd
        $date = new DateTime($endDate);
        Core::database()->update(self::TABLE_PROFILING_CONFIG, ["lastRun" => $date->format("Y-m-d H:i:s")], ["course" => $this->course->getId()]);
    }

    /**
     * @throws Exception
     */
    public function checkProfilerStatus(): array
    {
        if (file_exists($this->getProfilerLogsPath())) {
            // Get result
            clearstatcache();
            if (filesize($this->getProfilerLogsPath())) { // done running
                $file = fopen($this->getProfilerLogsPath(), "r");
                $line = fgets($file);
                fclose($file);

                if (stripos($line,"error") !== false)
                    $result = ["errorMessage" => $line];
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
                        $names = $this->getClusterNames(false);

                        // assign cluster names by replacing key with cluster name
                        $namedClusters = [];
                        $i = 0;
                        if(!empty($names)){
                            foreach($array as $entry) {
                                $namedClusters[$entry] = $names[$i];
                                $i++;
                            }
                        }

                        return $this->createClusterList($namedClusters, $assignedClusters);
                    }
                }

            } else $result = []; // still running

            if (array_key_exists("errorMessage", $result)) { // error caught
                unlink($this->getProfilerLogsPath());
                return ["error" => $result["errorMessage"]];
            }

            if (empty($result))
                return ["running" => true];

            return ["clusters" => $result, "names" => $this->getClusterNames()];

        } else return ["running" => false];
    }


    /*** --------- Clusters --------- ***/

    public function getSavedClusters(): array
    {
        $clusters = Core::database()->selectMultiple(self::TABLE_PROFILING_SAVED_USER_PROFILE, ["course" => $this->course->getId()]);

        // Parse clusters
        $res = [];
        foreach ($clusters as $c) {
            $res[$c["user"]] = $c["cluster"];
        }

        return $res;
    }

    public function saveClusters(array $clusters)
    {
        if (!$clusters) return;

        foreach ($clusters as $key => $value) {
            $entry = Core::database()->select(self::TABLE_PROFILING_SAVED_USER_PROFILE, ["course" => $this->course->getId(), "user" => $key]);
            if (!$entry) // new
                Core::database()->insert(self::TABLE_PROFILING_SAVED_USER_PROFILE,
                    ["course" => $this->course->getId(), "user" => $key, "cluster" => $value]);
            else // update
                Core::database()->update(self::TABLE_PROFILING_SAVED_USER_PROFILE,
                    ["cluster" => $value], ["course" => $this->course->getId(), "user" => $key]);
        }

        // Delete profiling results
        $resultsPath = $this->getProfilerLogsPath();
        if (file_exists($resultsPath)) unlink($resultsPath);
    }

    public function deleteSavedClusters()
    {
        Core::database()->delete(self::TABLE_PROFILING_SAVED_USER_PROFILE, ["course" => $this->course->getId()]);

        // Delete profiling results
        $resultsPath = $this->getProfilerLogsPath();
        if (file_exists($resultsPath)) unlink($resultsPath);

        // Change last run date
        $date = Core::database()->select(self::TABLE_PROFILING_USER_PROFILE, [], "date", "date desc");
        Core::database()->update(self::TABLE_PROFILING_CONFIG, ["lastRun" => $date], ["course" => $this->course->getId()]);

    }

    /**
     * @throws Exception
     */
    public function commitClusters(array $clusters)
    {
        // Assign clusters to students
        $this->assignClusterRoles($clusters);

        // Clean clusters saved
        $this->deleteSavedClusters();

        // Delete profiling results
        $resultsPath = $this->getProfilerLogsPath();
        if (file_exists($resultsPath)) unlink($resultsPath);
    }

    /**
     * @throws Exception
     */
    private function assignClusterRoles(array $clusters)
    {
        if (empty($clusters)) return;

        // Update students cluster
        $students = $this->course->getStudents();

        foreach ($students as $student) {
            $date = $this->getLastRun();
            $student = $this->course->getCourseUserById($student["id"]);

            // Remove old cluster
            $oldCluster = Core::database()->select(self::TABLE_PROFILING_USER_PROFILE, [
                "course" => $this->getCourse()->getId(),
                "user" => $student->getId()
            ], "cluster");
            if ($oldCluster) $student->removeRole(null, $oldCluster);

            // Assign new cluster
            if ($student->isActive()) {
                $newCluster = $clusters[$student->getId()];
                $newClusterId = Role::getRoleId($newCluster, $this->course->getId());
                $student->addRole(null, $newClusterId);
                Core::database()->insert(self::TABLE_PROFILING_USER_PROFILE, [
                    "course" => $this->course->getId(),
                    "user" => $student->getId(),
                    "date" => $date,
                    "cluster" => $newClusterId
                ]);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function createClusterList(array $names, array $assignedClusters): array
    {
        $students = $this->course->getStudents(true);
        $result = [];

        if (!empty($assignedClusters) && !empty($names)) {
            for ($i = 0; $i < count($students); $i++) {
                $exploded =  explode(' ', $students[$i]["name"]);
                $shortName = $exploded[0] . ' ' . end($exploded);
                $result[$students[$i]["id"]]['name'] = $shortName;
                $result[$students[$i]["id"]]['cluster'] = $names[$assignedClusters[$i]];
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getClusterNames(bool $all = true): array
    {
        $hierarchy = $this->course->getRolesHierarchy();
        $children = Role::getChildrenNamesOfRole($hierarchy, self::PROFILING_ROLE, null, !$all);

        if (empty($children)) return self::BASE_CLUSTER_NAMES;
        return $children;
    }


    /*** --------- Logging ---------- ***/

    /**
     * Gets Profiling logs for a given course.
     *
     * @param int $courseId
     * @return string
     */
    public static function getRunningLogs(int $courseId): string
    {
        $logsFile = self::getLogsFile($courseId, false);
        return Utils::getLogs($logsFile);
    }

    /**
     * Creates a new Profiling log on a given course.
     *
     * @param int $courseId
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function log(int $courseId, string $message, string $type)
    {
        $logsFile = self::getLogsFile($courseId, false);
        Utils::addLog($logsFile, $message, $type);
    }

    /**
     * Gets Profiling logs file for a given course.
     *
     * @param int $courseId
     * @param bool $fullPath
     * @return string
     */
    private static function getLogsFile(int $courseId, bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/" . "profiling_$courseId.txt";
        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        else return $path;
    }
}
