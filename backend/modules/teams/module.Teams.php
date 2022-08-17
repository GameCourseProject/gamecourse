<?php

namespace Modules\Teams;

use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\ValueNode;
use GameCourse\Views\Views;
use Modules\Charts\Charts;
use Modules\Leaderboard\Leaderboard;
use Modules\Moodle\Moodle;

class Teams extends Module
{
    const ID = 'teams';
    
    const TABLE = self::ID ;
    const TABLE_CONFIG = self::ID . '_config';
    const TABLE_XP = self::ID . '_xp';

    const TEAM_LEADERBOARD_TEMPLATE = 'Team Leaderboard - by teams';

    static $teams;

    public function init() {
        $this->initTemplates();
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::TEAM_LEADERBOARD_TEMPLATE))
            Views::createTemplateFromFile(self::TEAM_LEADERBOARD_TEMPLATE, file_get_contents(__DIR__ . '/leaderboard.txt'), $courseId, self::ID);
    }

    public function setupResources() {
        parent::addResources('css/leaderboard.css');
        parent::addResources('imgs/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);
        self::$teams = new Teams($courseId);
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

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
        ];
        return $input;
    }

    public function save_general_inputs(array $generalInputs, int $courseId)
    {
        $nrElements = $generalInputs["nrTeamMembers"];
        $this->saveNumberOfTeamElements($nrElements, $courseId);
    }

    public function has_listing_items(): bool
    {
        return  true;
    }

    public function get_listing_items(int $courseId): array
    {
        $header = ['Team', 'Member'];
        $displayAtributes = [
            ['id' => 'teamName', 'type' => 'text'],
            ['id' => 'memberName', 'type' => 'text'],
        ];
        $actions = ['duplicate', 'edit', 'delete', 'export'];

        $items = $this->getTeams($courseId);

        // Arguments for adding/editing
        $allAtributes = [
            array('name' => "Team", 'id' => 'teamName', 'type' => "text", 'options' => ""),
            array('name' => "Member", 'id' => 'memberName', 'type' => "text", 'options' => ""),
        ];

        return array('listName' => 'Teams', 'itemName' => 'team', 'header' => $header, 'displayAttributes' => $displayAtributes, 'actions' => $actions, 'items' => $items, 'allAttributes' => $allAtributes);
    }

    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows(int $courseId)
    {
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }

    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getTeams($courseId)
    {
        $teams = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*", "name");
        foreach ($teams as &$team) {
            //information to match needing fields
            $team['memberName'] = $team["memberName"];
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
                throw new \Exception("In function teams.getTeam(name): couldn't find badge with name '" . $where["name"] . "'.");
            $type = "object";
        }
        return Dictionary::createNode($teamArray, self::ID, $type);
    }

    public function getNumberOfTeamMembers($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "nrTeamMembers");
    }

    public function saveNumberOfTeamElements($nr, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["nrTeamMembers" => $nr], ["course" => $courseId]);
    }


}

ModuleLoader::registerModule(array(
    'id' => Teams()::ID,
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
