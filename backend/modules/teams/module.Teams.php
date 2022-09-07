<?php

namespace Modules\Teams;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\User;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\ValueNode;
use Modules\Charts\Charts;

class Teams extends Module
{
    const ID = 'teams';
    
    const TABLE = self::ID ;
    const TABLE_CONFIG = self::ID . '_config';
    const TABLE_XP = self::ID . '_xp';
    const TABLE_MEMBERS = self::ID . '_members';
    const TABLE_GC_USERS = 'game_course_user';

    const TEAM_LEADERBOARD_TEMPLATE = 'Team Leaderboard - by teams';

    static $teams;

    public function init() {
        $this->setupData($this->getCourseId());
        $this->initDictionary();
        $this->initTemplates();
    }

    public function initDictionary()
    {

        $courseId = $this->getCourseId();

        /*** ------------ Libraries ------------ ***/

        Dictionary::registerLibrary(self::ID, self::ID, "This library provides information regarding Teams. It is provided by the teams module.");


        /*** ------------ Functions ------------ ***/
        // teams.getAllTeams()
        Dictionary::registerFunction(
            self::ID,
            'getAllTeams',
            function (int $courseId) {
                return $this->getTeam(true, $courseId);
            },
            "Returns a collection with all the teams in the Course. The optional parameters can be used to find badges that specify a given combination of conditions:\nisActive: Streak is active.",
            'collection',
            'team',
            'library',
            null,
            true
        );

        //teams.getTeam(name)
        Dictionary::registerFunction(
            self::ID,
            'getTeam',
            function (string $name = null) {
                return $this->getTeam(false, ["name" => $name]);
            },
            "Returns the team object with the specific name.",
            'object',
            'team',
            'library',
            null,
            true
        );

        //$team.name, returns name
        Dictionary::registerFunction(
            self::ID,
            'name',
            function ($team) {
                return Dictionary::basicGetterFunction($team, "teamName");
            },
            'Returns the name of the team.',
            'string',
            null,
            'object',
            'team',
            true
        );

        //$team.getTeamMembers(team), returns all members of team
        Dictionary::registerFunction(
            self::ID,
            'getTeamMembers',
            function ($team) {
                return $this->getTeamMembers($team);
            },
            'Returns all the members of a certain team.',
            'collection',
            'teamMember',
            'library',
            null,
            true
        );

        //$team.getTeamMember(team, userNumber), returns a member of a team
        // eg.: userNumber = ist112345
        Dictionary::registerFunction(
            self::ID,
            'getTeamMember',
            function (string $name = null, int $memberId = null) {
                return $this->getTeamMember(false, ["teamName" => $name, "memberId" => $memberId]);
            },
            'Returns all the members of a certain team.',
            'collection',
            'teamMember',
            'library',
            null,
            true
        );
    }


    public function initTemplates()
    {
        /*$courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::TEAM_LEADERBOARD_TEMPLATE))
            Views::createTemplateFromFile(self::TEAM_LEADERBOARD_TEMPLATE, file_get_contents(__DIR__ . '/leaderboard.txt'), $courseId, self::ID);
        */
        }


        
    public function initAPIEndpoints()
    {

        /**
         * Gets all teams in course.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getTeams', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(["teams" => $this->getTeams($courseId)]);
        });

        /**
         * Gets all members of a team.
         *
         * @param int $courseId
         */
        
        API::registerFunction(self::ID, 'getTeamMembers', function () {
            API::requireCourseAdminPermission();
            API::requireValues('teamId');

            $teamId = API::getValue('teamId');

            API::response(["teams" => $this->getTeamMembers($teamId)]);
        });

        /**
         * Gets all users that belong to a team in the course.
         * 
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getAllUsersInTeams', function () {

            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $role = API::getValue('role');

            $users = $this->getAllUsersInTeams($courseId, $role);

            $usersInfo = [];

            // For security reasons, we only send what is needed
            foreach ($users as $userData) {
                $id = $userData['id'];
                $user = new CourseUser($id, $course);
                $usersInfo[] = array(
                    'id' => $id,
                    'name' => $user->getName(),
                    'nickname' => $user->getNickname(),
                    'studentNumber' => $user->getStudentNumber(),
                    'roles' => $user->getRolesNames(),
                    'major' => $user->getMajor(),
                    'email' => $user->getEmail(),
                    'lastLogin' => $user->getLastLogin(),
                    'username' => $user->getUsername(),
                    'authenticationService' => User::getUserAuthenticationService($user->getUsername()),
                    'isActive' => $user->isActive(),
                    'hasImage' => User::hasImage($user->getUsername())
                );
            }

            API::response(array('userList' => $usersInfo));
        });
        
        /**
         * Creates a new team in the course.
         *
         * @param int $courseId
         * @param $team
         */
        API::registerFunction(self::ID, 'createTeam', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'team');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->newTeam(API::getValue('team'), $courseId);
        });

        /**
         * Edit an existing skill in the course skill tree.
         *
         * @param int $courseId
         * @param $skill
         */
        API::registerFunction(self::ID, 'editTeam', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'team');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->editTeam(API::getValue('team'), $courseId);
        });

        /**
         * Deletes a team from the course.
         *
         * @param int $courseId
         * @param int $teamId
         */
        API::registerFunction(self::ID, 'deleteTeam', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'teamId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->deleteTeam(API::getValue('teamId'), $courseId);
        });

        /**
         * Gets isTeamNameActive from teams_config table.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getIsTeamNameActive', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(["isTeamNameActive" => intval($this->getIsTeamNameActive($courseId))]);
        });
        
    }

    public function setupResources() {
        parent::addResources('css/leaderboard.css');
        parent::addResources('imgs/');
    }

    public function setupData(int $courseId)
    {
        /*
        if ($this->addTables(self::ID, self::TABLE) || empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId]))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, ["course" => $courseId, "nrTeamMembers" => 3]);
        }             */
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson(int $courseId)
    {
        $teamsConfigArray = array();
        $teamsArray = array();

        $teamsArr = array();
        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $teamsConfigVarDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($teamsConfigVarDB) {
                unset($teamsConfigVarDB["course"]);
                array_push($teamsConfigArray, $teamsConfigVarDB);
            }
        }
        if (Core::$systemDB->tableExists(self::TABLE)) {
            $teamsVarDB = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*");
            if ($teamsVarDB) {
                unset($teamsConfigVarDB["course"]);
                foreach ($teamsVarDB as $team) {
                    array_push($teamsArray, $team);

                }
            }
        }

        $teamsArr[self::TABLE_CONFIG] = $teamsConfigArray;
        $teamsArr[self::TABLE] = $teamsArray;

        if ($teamsConfigArray || $teamsArray ) {
            return $teamsArr;
        } else {
            return false;
        }
    }

    public function readConfigJson(int $courseId, array $tables, bool $update = false): array
    {
        $tableName = array_keys($tables);
        $i = 0;
        $teamIds = array();
        $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                if ($tableName[$i] == self::TABLE_CONFIG) {
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        Core::$systemDB->insert($tableName[$i], $entry);
                    }
                } else  if ($tableName[$i] == self::TABLE) {
                    $importId = $entry["id"];
                    unset($entry["id"]);
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                    }
                    $teamIds[$importId] = $newId;
                }
            }
            $i++;
        }
        return $teamIds;
    }

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_general_inputs(): bool
    {
        return true;
    }

    public function get_general_inputs(int $courseId): array
    {
        $input = [
            array('name' => "Number of team members", 'id' => 'nrTeamMembers', 'type' => "number", 'options' => "", 'current_val' => intval($this->getNumberOfTeamMembers($courseId))),
            array('name' => "Allow Team Names", 'id' => 'isTeamNameActive', 'type' => "on_off button", 'options' => "", 'current_val' => intval($this->getIsTeamNameActive($courseId)))
        ];
        return $input;
    }

    public function save_general_inputs(array $generalInputs, int $courseId)
    {
        $nrElements = $generalInputs["nrTeamMembers"];
        $this->saveNumberOfTeamElements($nrElements, $courseId);
        $isTeamNameActive = $generalInputs["isTeamNameActive"];
        $this->saveIsTeamNameActive(intval($isTeamNameActive), $courseId);
    }

    public function has_personalized_config(): bool
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

    public function deleteDataRows(int $courseId)
    {
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }

    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    // TODO
    // import teams onto the system with a file.
    public function importItems($fileData, $replace = true){

    }

    public function exportItems(){

    }

    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getTeams($courseId)
    {
        $teamsArray = array();
        $teams = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*", "teamName");
        foreach ($teams as &$team) {
            $teamMembers = Core::$systemDB->selectMultiple(self::TABLE_MEMBERS, ["teamId" => $team["id"]], "memberId");;
            $xp = Core::$systemDB->select(self::TABLE_XP, ["teamId" => $team["id"]], "xp");
            //information to match needing fields
            $team['teamName'] = $team["teamName"];
            $team['teamNumber'] = $team["teamNumber"];
            $team['members'] = '';
            $team['teamMembers'] = [];
            if(!empty($xp)){
                $team['xp'] = $xp;
            } else{
                $team['xp'] = 0;
            }

            if(!empty($teamMembers)){
                for ($i = 0; $i < sizeof($teamMembers); $i++) {
                    if ($i == sizeof($teamMembers) -1){
                        $team['members'] .= $teamMembers[$i]['memberId'];
                    }else{
                        $team['members'] .= $teamMembers[$i]['memberId'] . "|";
                    }

                    $memberId = $teamMembers[$i]['memberId'];
                    $member = Core::$systemDB->select(self::TABLE_GC_USERS, ["id" => $memberId], "name, major, studentNumber");

                    array_push($team['teamMembers'], $member);
                }

            }


        }
        return $teams;
    }

    public function getTeam($selectMultiple, $where): ValueNode
    {
        $where["course"] = $this->getCourseId();
        if ($selectMultiple) {
            $teamArray = Core::$systemDB->selectMultiple(self::TABLE, $where);
            $type = "collection";
        } else {
            $teamArray = Core::$systemDB->select(self::TABLE, $where);
            if (empty($teamArray))
                throw new \Exception("In function teams.getTeam(teamName): couldn't find badge with name '" . $where["name"] . "'.");
            $type = "object";
        }
        return Dictionary::createNode($teamArray, self::ID, $type);
    }

    public function getAllUsersInTeams($courseId, $role, $active = true){
        if (!$active) {
            $where = ["r.course" => $courseId, "r.name" => $role];
        } else {
            $where = ["r.course" => $courseId, "r.name" => $role, "cu.isActive" => true];
        }
        $result = Core::$systemDB->selectMultiple(
            "course_user cu JOIN game_course_user u ON cu.id=u.id JOIN user_role ur ON ur.id=u.id JOIN role r ON r.id=ur.role AND r.course=cu.course JOIN auth a ON u.id=a.game_course_user_id JOIN teams_members tm ON u.id = tm.memberId",
            $where,
            "u.*,cu.lastActivity, cu.previousActivity,a.username,r.name as role"
        );
        return $result;

    }

    public function getTeamMembers($teamId)
    {
        return Core::$systemDB->selectMultiple(self::TABLE_MEMBERS, ["teamId" => $teamId], "*", "memberId");
    }
    
    public function getTeamMember($memberId, $courseId)
    {
        // TODO
    }

    public function getNumberOfTeamMembers($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "nrTeamMembers");
    }

    public function saveNumberOfTeamElements($nr, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["nrTeamMembers" => $nr], ["course" => $courseId]);
    }

    public function getIsTeamNameActive($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "isTeamNameActive");
    }

    public function saveIsTeamNameActive($val, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["isTeamNameActive" => $val], ["course" => $courseId]);
    }

    public function newTeam($team, $courseId){
        // "teamNumber" => $team['teamNumber'],
        $teamData = [
            "teamName" => $team['teamName'],
            "course" => $courseId
        ];

        Core::$systemDB->insert(self::TABLE, $teamData);
        $teamId = Core::$systemDB->getLastId();

        if ($team['members'] != "") {
            // eg.: $team['members] = "120 | 125 | 130"
            $members = explode("|", str_replace(" | ", "|", $team["members"]));
            foreach ($members as $m) {
                $memberId = (int)$m;
                Core::$systemDB->insert(self::TABLE_MEMBERS, [ 'teamId' => $teamId, "memberId" => $memberId ]);
            }

            $team['members'] = $team['members'];

        }

    }

    public function editTeam($team, int $courseId){

        $originalTeam = Core::$systemDB->select(self::TABLE, ["course" => $courseId, 'id' => $team['id']], "*");
        $originalTeamMembers = Core::$systemDB->select(self::TABLE_MEMBERS, ["course" => $courseId, 'teamId' => $team['id']], "*");

        // "teamNumber" => $team['teamNumber'],
        if(!empty($originalTeam)){
            $teamData = [
                "teamName" => $team['name'],
                "course" => $courseId
            ];
            Core::$systemDB->update(self::TABLE, $teamData, ["id" => $team["id"]]);
        }
        
        if(!empty($originalTeamMembers)){
            Core::$systemDB->delete(self::TABLE_MEMBERS, ["teamId" => $team["id"]]);

            $members = explode("|", str_replace(" | ", "|", $team["members"]));
            foreach ($members as $m) {
                $memberId = (int)$m;
                Core::$systemDB->insert(self::TABLE_MEMBERS, [ 'teamId' => $team["id"], "memberId" => $memberId ]);
            }

        }
    }

    public function deleteTeam(int $teamId)
    {
        Core::$systemDB->delete(self::TABLE, ["id" => $teamId]);
    }


}

ModuleLoader::registerModule(array(
    'id' => Teams::ID,
    'name' => 'Teams',
    'description' => 'Creates a view template with a leaderboard of the students teams progress on the course.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => Charts::ID, 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Teams();
    }
));
