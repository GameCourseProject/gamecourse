<?php
namespace VirtualCurrency;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Views\Dictionary;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Expression\ValueNode;

class VirtualCurrency extends Module
{
    const ID = 'virtualcurrency';

    const TABLE_WALLET = 'user_wallet';
    const TABLE_CONFIG = 'virtual_currency_config';
    const TABLE = 'virtual_currency_to_award';

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {

        $this->setupData($this->getCourseId());
        $this->initDictionary();
    }

    public function initAPIEndpoints()
    {
        /**
         * Get user tokens.
         *
         * @param int $courseId
         * @param int $userId
         */
        API::registerFunction(self::ID, 'getUserTokens', function () {
            API::requireCoursePermission();
            API:: requireValues('courseId', 'userId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $userId = API::getValue('userId');
            $user = API::verifyCourseUserExists($courseId, $userId);

            API::response(['tokens' => $this->getUserTokens($userId)]);
        });
    }

    public function initDictionary(){

        Dictionary::registerLibrary(self::ID, self::ID, "This library provides information regarding Virtual Currency. It is provided by the Virtual Currency module.");

        //virtualcurrency.isEnabled
        Dictionary::registerFunction(
            self::ID,
            'isEnabled',
            function () {
                $isEnabled = filter_var(Core::$systemDB->select("course_module", ["course" => $this->getCourseId(), "moduleId" => VirtualCurrency::ID], "isEnabled"), FILTER_VALIDATE_BOOLEAN);
                return new ValueNode($isEnabled);
            },
            "Returns whether the Virtual Currency is enabled for the course.",
            'boolean',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //virtualcurrency.name
        Dictionary::registerFunction(
            self::ID,
            'name',
            function () {
                return new ValueNode(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $this->getCourseId()], "name"));
            },
            "Returns the Virtual Currency object with the specific name.",
            'object',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //virtualcurrency.skillCost
        Dictionary::registerFunction(
            self::ID,
            'skillCost',
            function () {
                return new ValueNode(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $this->getCourseId()], "skillCost"));
            },
            "Returns a string with the retry cost of a skill.",
            'object',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //virtualcurrency.attemptRating
        Dictionary::registerFunction(
            self::ID,
            'attemptRating',
            function () {
                return new ValueNode(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $this->getCourseId()], "attemptRating"));
            },
            "Returns a string with the minimum rating for a skill attempt to count.",
            'object',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //virtualcurrency.incrementCost
        Dictionary::registerFunction(
            self::ID,
            'incrementCost',
            function () {
                return new ValueNode(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $this->getCourseId()], "incrementCost"));
            },
            "Returns a string with increment cost for skill retries.",
            'object',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //virtualcurrency.costFormula
        Dictionary::registerFunction(
            self::ID,
            'costFormula',
            function () {
                return new ValueNode(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $this->getCourseId()], "costFormula"));
            },
            "Returns a string with increment formula.",
            'object',
            'virtualcurrency',
            'library',
            null,
            true
        );
         
        //virtualcurrency.tokensToXPRatio
        Dictionary::registerFunction(
            self::ID,
            'tokensToXPRatio',
            function () {
                return new ValueNode(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $this->getCourseId()], "tokensToXPRatio"));
            },
            "Returns a string with the ratio for exchanging tokens for xp.",
            'object',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //virtualcurrency.changeTokensForXP(user)
        Dictionary::registerFunction(
            self::ID,
            'changeTokensForXP',
            function ($user) {
                $userId = $this->getUserId($user);
                $currentTokens = $this->getUserTokens($userId);
                $ratio = $this->getTokensToXP($this->getCourseId());
                $converted = $currentTokens * $ratio;
                return new ValueNode($converted);
            },
            "Returns the xp converted from the existing tokens.",
            'integer',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //virtualcurrency.skillCost
        Dictionary::registerFunction(
            self::ID,
            'skillCost',
            function () {
                return new ValueNode(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $this->getCourseId()], "skillCost"));
            },
            "Returns a string with the retry cost of a skill.",
            'object',
            'virtualcurrency',
            'library',
            null,
            true
        );

        //%user.tokens
        Dictionary::registerFunction(
            'users',
            'tokens',
            function ($user) {
                $userId = $this->getUserId($user);
                $tokens = $this->getUserTokens($userId);
                return new ValueNode($tokens);
            },
            'Returns a number corresponding to the user\'s tokens.',
            'number',
            null,
            'object',
            'user',
            true
        );
    }

    public function setupResources()
    {
        parent::addResources('css/virtualcurrency.css');
        parent::addResources('imgs/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);

        if (empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId]))) {
            Core::$systemDB->insert(self::TABLE_CONFIG,
                [
                    "name" => "",
                    "course" => $courseId,
                    "tokensToXPRatio" => 0,
                    "skillCost" => DEFAULT_COST,
                    "wildcardCost" => DEFAULT_COST,
                    "attemptRating" => 0,
                    "costFormula" => "",
                    "incrementCost" => DEFAULT_COST
                ]);
        }

        $folder = Course::getCourseDataFolder($courseId, Course::getCourse($courseId, false)->getName());
        if (!file_exists($folder . "/" . self::ID))
            mkdir($folder . "/" . self::ID);
    }

    public function update_module($compatibleVersions)
    {
        /*
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = "modules/badges/config.json";
        $contents = array();
        if (file_exists($configFile)) {
            $contents = json_decode(file_get_contents($configFile));
            unlink($configFile);
        }
        */
        //verificar compatibilidade

    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson($courseId)
    {

        $currencyConfigArray = array();
        $currencyActionsArray = array();


        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $currencyConfigVarDB = Core::$systemDB->selectMultiple(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($currencyConfigVarDB) {
                $currencyArray = array();
                foreach ($currencyConfigVarDB as $currencyConfigVarDB) {
                    unset($currencyConfigVarDB["course"]);
                    unset($currencyConfigVarDB["id"]);
                    array_push($currencyArray, $currencyConfigVarDB);
                }
                $currencyConfigArray["config_moodle"] = $currencyArray;
            }
        }
        /*
        if (Core::$systemDB->tableExists(self::TABLE)) {
            $currencyActionsVarDB = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*");
            if ($currencyActionsVarDB) {
                unset($currencyConfigVarDB["course"]);
                unset($currencyConfigVarDB["id"]);
                foreach ($currencyActionsVarDB as $action) {
                    array_push($currencyActionsArray, $action);

                }
            }
        }
        */
        
        return $currencyConfigArray;
    }

    public function readConfigJson($courseId, $tables, $update = false)
    {
        $tableName = array_keys($tables);
        $i = 0;
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
                }
            }
            $i++;
        }
        return false;

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
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => "", 'current_val' => $this->getCurrencyName($courseId)),
            array('name' => "Tokens to XP Ratio", 'id' => 'tokenstoxp', 'type' => "number", 'options' => "", 'current_val' => intval($this->getTokensToXP($courseId))),
            /*array('name' => "Skill Initial Cost", 'id' => 'skillcost', 'type' => "number", 'options' => "", 'current_val' => intval($this->getSkillCost($courseId))),
            array('name' => "Wildcard Initial Cost", 'id' => 'wildcardcost', 'type' => "number", 'options' => "", 'current_val' => intval($this->getWildcardCost($courseId))),
            array('name' => "Min. Rating for Attempt", 'id' => 'attemptrating', 'type' => "number", 'options' => "", 'current_val' => intval($this->getAttemptRating($courseId))),
            array('name' => "Increment Cost", 'id' => 'incrementcost', 'type' => "number", 'options' => "", 'current_val' => intval($this->getIncrementCost($courseId))),
            array('name' => "Increment Formula", 'id' => 'formulacost', 'type' => "text", 'options' => ["A", "b", "c"], 'current_val' => "isto vai ser um select com opções" )
            */
            ];
        return $input;
    }
    public function save_general_inputs(array $generalInputs, int $courseId)
    {
        $currencyName = $generalInputs["name"];
        $this->saveCurrencyName($currencyName, $courseId);

        $skillCost = $generalInputs["skillcost"];
        if ($skillCost != "") {
            $this->saveSkillCost( $skillCost, $courseId);
        }
        $wildcardCost = $generalInputs["wildcardcost"];
        if ($wildcardCost != "") {
            $this->saveWildcardCost( $wildcardCost, $courseId);
        }

        $attemptRating = $generalInputs["attemptrating"];
        $this->saveAttemptRating($attemptRating, $courseId);

        $costFormula = $generalInputs["formulacost"];
        $this->saveCostFormula($costFormula, $courseId);

        $incrementCost = $generalInputs["incrementcost"];
        $this->saveIncrementCost($incrementCost, $courseId);

        $tokensToXPRatio = $generalInputs["tokenstoxp"];
        $this->saveTokensToXP($tokensToXPRatio, $courseId);

    }

    public function has_listing_items(): bool
    {
        return  true;
    }
    public function get_listing_items(int $courseId): array
    {

        $header = ['Name', 'Description', 'Type', 'Tokens', 'is Active'];
        $displayAtributes = [
            ['id' => 'name', 'type' => 'text'],
            ['id' => 'description', 'type' => 'text'],
            ['id' => 'type', 'type' => 'text'],
            ['id' => 'tokens', 'type' => 'number'],
            ['id' => 'isActive', 'type' => 'on_off button']
        ];
        $actions = ['duplicate', 'edit', 'delete', 'export'];

        $items = $this->getActions($courseId);

        // Arguments for adding/editing
        $allAtributes = [
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => ""),
            array('name' => "Description", 'id' => 'description', 'type' => "text", 'options' => ""),
            array('name' => "Type", 'id' => 'type', 'type' => "text", 'options' => ""),
            array('name' => "Tokens", 'id' => 'tokens', 'type' => "number", 'options' => ""),
            array('name' => "Is Active", 'id' => 'isActive', 'type' => "on_off button", 'options' => "")
        ];
        return array('listName' => 'To Award', 'itemName' => 'action', 'header' => $header, 'displayAttributes' => $displayAtributes, 'actions' => $actions, 'items' => $items, 'allAttributes' => $allAtributes);
    }
    public function save_listing_item(string $actiontype, array $listingItem, int $courseId)
    {
        if ($actiontype == 'new' || $actiontype == 'duplicate') {
            $this->newAction($listingItem, $courseId);
        } elseif ($actiontype == 'edit') {
            $this->editAction($listingItem, $courseId);
        } elseif ($actiontype == 'delete') {
            $this->deleteAction($listingItem);
        }
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

    public function deleteDataRows($courseId)
    {
        Core::$systemDB->delete(self::TABLE_WALLET, ["course" => $courseId]);
    }

    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getActions($courseId){
        $coisas = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*", "name");
        foreach ($coisas as &$coisa) {
            //information to match needing fields
            $coisas['isActive'] = boolval($coisa["isActive"]);
        }
        return $coisas;
    }

    public function getAction($selectMultiple, $where): ValueNode
    {
        $where["course"] = $this->getCourseId();
        if ($selectMultiple) {
            $actionArray = Core::$systemDB->selectMultiple(self::TABLE, $where);
            $type = "collection";
        } else {
            $actionArray = Core::$systemDB->select(self::TABLE, $where);
            if (empty($actionArray))
                throw new \Exception("In function actions.getAction(name): couldn't find action with name '" . $where["name"] . "'.");
            $type = "object";
        }
        return Dictionary::createNode($actionArray, self::ID, $type);

    }


    public function getCurrencyName($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "name");
    }
    public function saveCurrencyName($currencyName ,$courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["name" => $currencyName], ["course" => $courseId]);
    }

    public function getSkillCost($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "skillCost");

    }
    public function saveSkillCost($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["skillCost" => $value], ["course" => $courseId]);
    }

    public function getWildcardCost($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "wildcardCost");

    }
    public function saveWildcardCost($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["wildcardCost" => $value], ["course" => $courseId]);
    }

    public function getAttemptRating($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "attemptRating");

    }
    public function saveAttemptRating($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["attemptRating" => $value], ["course" => $courseId]);
    }

    public function getCostFormula($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "costFormula");

    }
    public function saveCostFormula($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["costFormula" => $value], ["course" => $courseId]);
    }

    public function getIncrementCost($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "incrementCost");

    }
    public function saveIncrementCost($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["incrementCost" => $value], ["course" => $courseId]);
    }

    public function getTokensToXP($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "tokensToXPRatio");

    }
    public function saveTokensToXP($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["tokensToXPRatio" => $value], ["course" => $courseId]);
    }

    public static function newAction($achievement, $courseId)
    {
        $actionData = [
            "name" => $achievement['name'],
            "course" => $courseId,
            "description" => $achievement['description'],
            "type" => $achievement['type'],
            "tokens" => $achievement['tokens'],
            "isActive" => ($achievement['isActive']) ? 1 : 0,

        ];

        Core::$systemDB->insert(self::TABLE, $actionData);
    }

    public static function editAction($achievement, $courseId)
    {
        $originalAction = Core::$systemDB->select(self::TABLE, ["course" => $courseId, 'id' => $achievement['id']], "*");

        if(!empty($originalAction)) {
            $actionData = [
                "name" => $achievement['name'],
                "course" => $courseId,
                "description" => $achievement['description'],
                "type" => $achievement['type'],
                "tokens" => $achievement['tokens'],
            ];

            Core::$systemDB->update(self::TABLE, $actionData, ["id" => $achievement["id"]]);
        }
    }

    public function deleteAction($action)
    {
        Core::$systemDB->delete(self::TABLE, ["id" => $action['id']]);
    }

    public function getUserTokens($userId): int
    {
        return intval(Core::$systemDB->select(self::TABLE_WALLET, ["course" => $this->getCourseId(), "user" => $userId], "tokens"));
    }

}

ModuleLoader::registerModule(array(
    'id' => VirtualCurrency::ID,
    'name' => 'Virtual Currency',
    'description' => 'Allows Virtual Currency to be automaticaly included on GameCourse.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new VirtualCurrency();
    }
));