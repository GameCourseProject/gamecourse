<?php

namespace GameCourse\Views;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Module;
use GameCourse\Views\Expression\ValueNode;
use GameCourse\Views\Expression\EvaluateVisitor;


class Views
{

    /*** ---------------------------------------------------- ***/
    /*** --------------------- Templates -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Receives the template name, its encoded contents, and puts it in the database
     *
     * @param string $name
     * @param string $contents
     * @param int $courseId
     * @param bool $fromModule
     */
    public static function setTemplate(string $name, string $contents, int $courseId, bool $fromModule = false): void
    {
        $aspects = json_decode($contents, true);
        $roleType = ViewHandler::getRoleType($aspects[0]["role"]);
        Views::setTemplateHelper($aspects, $courseId, $name, $roleType, $fromModule);
    }

    /**
     * Inserts data into 'template' and 'view_template' tables
     *
     * @param $aspects
     * @param int $courseId
     * @param string $name
     * @param string $roleType
     * @param false $fromModule
     * @return array
     */
    private static function setTemplateHelper($aspects, int $courseId, string $name, string $roleType, bool $fromModule = false): array
    {
        Core::$systemDB->insert("template", ["course" => $courseId, "name" => $name, "roleType" => $roleType]);
        $templateId = Core::$systemDB->getLastId();

        foreach ($aspects as &$aspect) {
            ViewHandler::updateViewAndChildren($aspect, $courseId, false, true, $name, $fromModule);
        }

        $viewId = Core::$systemDB->select("view_template", ["templateId" => $templateId], "viewId");
        return array($templateId, $viewId);
    }

    /**
     * Gets template contents.
     *
     * @param int $templateId
     * @return mixed
     */
    public static function getTemplateContents(int $templateId)
    {
        $template = Core::$systemDB->select(
            "view_template vt join view v on vt.viewId=v.viewId",
            ["templateId" => $templateId]
        );

        // It returns the 'container' block and we want to return only the inner views
        return ViewHandler::getViewWithParts($template["viewId"], null, true);
    }

    /**
     * Gets templates of the course.
     *s
     * @param int $courseId
     * @param bool $includeGlobals
     * @return array
     */
    public static function getTemplates(int $courseId, bool $includeGlobals = false): array
    {
        $temps = Core::$systemDB->selectMultiple(
            'template t join view_template vt on templateId=id join view v on v.viewId=vt.viewId',
            ['course' => $courseId],
            "t.id,name,course,isGlobal,roleType,vt.viewId,role",
            null,
            [],
            [],
            "t.id"
        );

        if ($includeGlobals) {
            $globalTemp = Core::$systemDB->selectMultiple("template", ["isGlobal" => true]);
            return [$temps, $globalTemp];
        }
        return $temps;
    }

    /**
     * Gets template by ID
     *
     * @param int $courseId
     * @param int|null $templateId
     * @param string|null $name
     * @return mixed
     */
    public static function getTemplate(int $courseId, int $templateId = null, string $name = null)
    {
        $tables = "template t join view_template vt on templateId=id join view v on v.viewId=vt.viewId";
        $where = ['course' => $courseId];
        if ($templateId) $where["t.id"] = $templateId;
        else $where["name"] = $name;
        $fields = "t.id,name,course,isGlobal,roleType,vt.viewId,role";
        return Core::$systemDB->select($tables, $where, $fields);
    }

    /**
     * Checks if a template with a given name exists in the database
     *
     * @param string $name
     * @param int $courseId
     * @return bool
     */
    public static function templateExists(string $name, int $courseId): bool
    {
        return !empty(Core::$systemDB->select('template', ['name' => $name, 'course' => $courseId]));
    }

    /**
     * Delete templates.
     *
     * @param bool $isTemplate
     * @param int $templateId
     * @param $role
     * @param bool $isRoleExact
     */
    public static function deleteTemplateRefs(bool $isTemplate, int $templateId, $role, bool $isRoleExact = true)
    {
        if ($isTemplate) {
            $deleteTempRefTable = "view_template left join view on viewId=id";
            if ($isRoleExact) {
                $viewDelete = Core::$systemDB->selectMultiple($deleteTempRefTable, ["templateId" => $templateId, "partType" => "templateRef", "role" => $role], "id");
            } else {
                $viewDelete = Core::$systemDB->selectMultiple($deleteTempRefTable, ["templateId" => $templateId, "partType" => "templateRef"], "id", null, [], [], null, ["role" => $role]);
            }
            foreach ($viewDelete as $view) {
                Core::$systemDB->delete("view", ["id" => $view["id"]]);
            }
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Views editor ------------------- ***/
    /*** ---------------------------------------------------- ***/


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Miscellaneous ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets timestamps and converts it to DD/MM/YYYY
     *
     * @param $object
     * @return ValueNode
     * @throws \Exception
     */
    private static function getDate($object): ValueNode
    {
        Module::checkArray($object, "object", "date");
        $date = implode("/", array_reverse(explode("-", explode(" ", $object["value"]["date"])[0])));
        return new ValueNode($date);
    }

    /**
     * Get module name of award.
     *
     * @param $object
     * @return mixed|null
     */
    public static function getModuleNameOfAward($object)
    {
        if (array_key_exists("name", $object["value"]))
            return $object["value"]["name"];

        $type = $object["value"]["type"];
        if ($type == "badge") {
            return Core::$systemDB->select($type, ["id" => $object["value"]["moduleInstance"]], "name");
        }
        if ($type == "skill") {
            return $object["value"]["description"];
        }
        return null;
    }

    /**
     * Get award or participations from database
     *
     * @param $courseId
     * @param $user
     * @param $type
     * @param $moduleInstance
     * @param $initialDate
     * @param $finalDate
     * @param array $where
     * @param string $object
     * @param bool $activeUser
     * @param bool $activeItem
     * @return mixed
     */
    public function getAwardOrParticipationAux($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, $where = [], $object = "award", $activeUser = true, $activeItem = true)
    {
        $awardParticipation = $this->getAwardOrParticipation($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, $where, $object, $activeUser, $activeItem);
        return $this->createNode($awardParticipation, $object . "s", "collection");
    }




    //expression lang function, convert string to int
    public function toInt($val, $funName)
    {
        if (is_array($val))
            throw new \Exception("'." + $funName + "' can only be called over string.");
        return new ValueNode(intval($val));
    }
    function evaluateKey(&$key, &$collection, $courseId, $i = 0)
    {
        if (!array_key_exists($key, $collection["value"][0])) {
            //key is not a parameter of objects in collection, it should be an expression of the language
            if (strpos($key, "{") !== 0) {
                $key = "{" . $key . "}";
            }
            ViewHandler::parseSelf($key);
            foreach ($collection["value"] as &$object) {
                $viewParams = array(
                    'course' => (string)$courseId,
                    'viewer' => (string)Core::getLoggedUser()->getId(),
                    'item' => $this->createNode($object, $object["libraryOfVariable"])->getValue(),
                    'index' => $i
                );
                $visitor = new EvaluateVisitor($viewParams);
                $value = $key->accept($visitor)->getValue();

                $object["sortVariable" . $i] = $value;
            }
            $key = "sortVariable" . $i;
        }
    }
    //conditions for the filter function
    public function evalCondition($a, $b, $op)
    {
        switch ($op) {
            case '=':
            case '==':
                return $a == $b;
            case '===':
                return           $a === $b;
            case '!==':
                return           $a !== $b;
            case '!=':
                return           $a != $b;
            case '>':
                return           $a > $b;
            case '>=':
                return           $a >= $b;
            case '<':
                return           $a < $b;
            case '<=':
                return           $a <= $b;
        }
    }


    //tests view parsing and processing
    function testView($course, $courseId, &$testDone, &$view, $viewerRole, $userRole = null)
    {
        try { //ToDo: for preview viewer should be the current user if they have the role
            $viewerId = $this->getUserIdWithRole($course, $viewerRole);
            $params = ['course' => (string)$courseId];
            //print_r("test");

            if ($userRole !== null) { //if view has role interaction
                $userId = $this->getUserIdWithRole($course, $userRole);
                if ($userId == -1) {
                    return;
                }
                $params["user"] = (string)$userId;
            }
            if ($viewerId != -1) {
                $params['viewer'] = $viewerId;
                $this->viewHandler->processView($view, $params);
                $testDone = true;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    //test view edit and save it or show preview
    function saveOrPreview($saving = true)
    {
        API::requireCourseAdminPermission();
        $data = $this->getViewSettings();
        $courseId = $data["courseId"];
        $course = $data["course"];
        $viewContent = API::getValue('content');
        $viewType = $data["viewSettings"]['roleType'];

        API::requireValues('roles');
        $roles = API::getValue('roles');
        // if ($viewType == "ROLE_SINGLE") {
        //     if (!array_key_exists('role', $info)) {
        //         API::error('Missing role');
        //     }
        // } else if ($viewType == "ROLE_INTERACTION") {
        //     if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info)) {
        //         API::error('Missing roleOne and/or roleTwo in info');
        //     }
        // }
        $testDone = false;
        $warning = false;
        $viewCopy = $viewContent;
        try {
            foreach ($viewCopy as $aspect) {
                if ($saving) {
                    $this->viewHandler->parseView($aspect);
                    if ($viewType == "ROLE_SINGLE") {
                        //TODO: change this to be the role selected by user (that is presented on the edit tollbar)
                        //$this->testView($course, $courseId, $testDone, $viewCopy, $roles['viewerRole']);
                        $this->testView($course, $courseId, $testDone, $aspect, $aspect['role']);
                    } else if ($viewType == "ROLE_INTERACTION") {
                        $viewer = explode(">", $aspect['role'])[1];
                        $user = explode(">", $aspect['role'])[0];
                        $this->testView($course, $courseId, $testDone, $aspect, $viewer, $user);
                    }
                } else {
                    if ($viewType == "ROLE_SINGLE") {
                        if ($aspect['role'] == 'role.' . $roles['viewerRole']) {
                            //TODO: change this to be the role selected by user (that is presented on the edit tollbar)
                            //$this->testView($course, $courseId, $testDone, $viewCopy, $roles['viewerRole']);
                            $this->viewHandler->parseView($aspect);
                            $this->testView($course, $courseId, $testDone, $aspect, $aspect['role']);
                        }
                    } else if ($viewType == "ROLE_INTERACTION") {
                        $viewer = explode(">", $aspect['role'])[1];
                        $user = explode(">", $aspect['role'])[0];
                        if ($viewer == 'role.' . $roles['viewerRole'] && $user == 'role.' . $roles['userRole']) {
                            $this->viewHandler->parseView($aspect);
                            $this->testView($course, $courseId, $testDone, $aspect, $viewer, $user);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (!$saving) {
                API::error('Error in preview: ' . $msg);
            } else if ($data["pageOrTemp"] == "page" || strpos($msg, 'Unknown variable') === null) {
                API::error('Error saving view: ' . $msg);
            } else { //template with variable error, probably because it belong to an unknow context, save anyway
                $msgArr = explode(": ", $msg);
                $varName = end($msgArr);
                $warning = true;
                $warningMsg = "Warning: Template was saved but not tested because of the unknow variable: " . $varName;
            }
        }
        if ($saving) {
            API::requireValues('screenshoot',/*,'pageOrTemp', */ 'view');
            //$pageOrTemplate = API::getValue('pageOrTemp');
            $viewId = API::getValue('view');

            //print_r($viewContent);
            foreach ($viewContent as $aspect) {
                ViewHandler::updateViewAndChildren($aspect, $courseId);
            }
            $aspects = Core::$systemDB->selectMultiple("view", ["viewId" => $viewContent[0]["viewId"]]);
            //it means that some (whole) aspect has been deleted
            if (count($aspects) > count($viewContent)) {
                $rolesSaved = array_column($viewContent, 'role');
                foreach ($aspects as $asp) {
                    if (!in_array($asp['role'], $rolesSaved))
                        $this->viewHandler->deleteViews($asp, true);
                }
            }
            $errorMsg = "Saved, but skipping test (no users in role to test or special role";
        } else {
            $errorMsg = "Previewing of Views for Roles with no users or Special Roles is not implemented.";
        }
        if (!$testDone) {
            if ($warning) {
                API::response($warningMsg);
            }
            API::error($errorMsg);
        }
        if (!$saving) {
            $viewParams = [
                'course' => (string)$data["courseId"],
            ];
            if ($roles['viewerRole'] == 'Default')
                $viewParams['viewer'] = Core::$systemDB->select(
                    "course_user",
                    ["course" => $course->getId()],
                )['id'];
            else {
                $viewParams['viewer'] = Core::$systemDB->select(
                    "user_role ur join role r on ur.course=r.course and ur.role=r.id",
                    ["ur.course" => $course->getId(), 'r.name' => $roles['viewerRole']],
                )['ur.id'];
            }

            if ($viewType == "ROLE_SINGLE") {
                $userView = $this->viewHandler->getViewWithParts($viewCopy[0]["viewId"], $roles['viewerRole']);
            } else if ($viewType == "ROLE_INTERACTION") {
                if ($roles['userRole'] == 'Default')
                    $viewParams['user'] = Core::$systemDB->select(
                        "course_user",
                        ["course" => $course->getId()],
                    )['id'];
                else {
                    $viewParams['user'] = Core::$systemDB->select(
                        "user_role ur join role r on ur.course=r.course and ur.role=r.id",
                        ["ur.course" => $course->getId(), 'r.name' => $roles['userRole']],
                    )['ur.id'];
                }
                $userView = $this->viewHandler->getViewWithParts($viewCopy[0]["viewId"], $roles['userRole'] . '>' . $roles['viewerRole']);
            }
            $this->viewHandler->parseView($userView);
            $this->viewHandler->processView($userView, $viewParams);
            API::response(array('view' => $userView));
        }
        return;
    }
    //receives roles like 'role.Default','role.1',etc and get a user of that role
    function getUserIdWithRole($course, $role)
    {
        $uid = -1;
        if (strpos($role, 'role.') === 0) {
            $role = substr($role, 5);
            if ($role == 'Default')
                return $course->getUsersIds()[0];
            $loggedUserId = Core::getLoggedUser()->getId();
            $loggedUser = new \GameCourse\CourseUser($loggedUserId, $course);
            if (in_array($role, $loggedUser->getRolesNames()))
                return $loggedUserId;
            $users = $course->getUsersWithRole($role, false);

            if (count($users) != 0)
                $uid = $users[0]['id'];
        } else if (strpos($role, 'user.') === 0) {
            $uid = substr($role, 5);
        }
        return $uid;
    }

    //get settings of page/template 
    public static function getViewSettings($courseId, $viewId, $pgOrTemp, $id): array
    {
        $course = Course::getCourse($courseId, false);

        if ($pgOrTemp == "page") {
            $page =  Core::$systemDB->select('page', ['id' => $id]);
            $viewSettings = $page;
            $viewSettings["roleType"] = Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $viewId, "course" => $courseId], "roleType");

        } else { //template
            $viewSettings = Views::getTemplate($courseId, $id);
        }

        if (empty($viewSettings)) {
            API::error('Unknown ' . $pgOrTemp . ' ' . $id);
        }

        return [
            "courseId" => $courseId,
            "course" => $course,
            "id" => $id,
            "pageOrTemp" => $pgOrTemp,
            "viewSettings" => $viewSettings
        ];
    }



    private function breakTableRows(&$rows, &$savePart)
    {
        ViewEditHandler::breakRepeat($rows, $savePart, function (&$row) use (&$savePart) {
            foreach ($row['values'] as &$cell) {
                ViewEditHandler::breakPart($cell['value'], $savePart);
            }
        });
    }

    //receives templateName, current view parameters, funcNAme= showPopUp or showToolTip,course, and user
    //renders template view and returns it inside a function call for views.directive.js which deals w events
    private function popUpOrToolTip($templateName, $params, $funcName, $course, $user)
    {
        $template = $this->getTemplate(null, $templateName);
        if ($user != null) { //rendering a user view
            $userId = $this->getUserId($user);
            $params["user"] = $userId;
        }
        $userView = $this->viewHandler->handle($template, $course, $params);
        $encodedView = json_encode($userView);
        if (strlen($encodedView) > 100000) //preventing the use of tooltips with big templates
            throw new \Exception("Tooltips and PopUps can only be used with smaller templates, '" . $templateName . "' is too big.");
        return new ValueNode($funcName . "('" . $encodedView . "')");
    }

    private function parseTableRows(&$rows)
    {
        for ($i = 0; $i < count($rows); ++$i) {
            $row = &$rows[$i];
            if (array_key_exists('style', $row))
                $this->viewHandler->parseSelf($row['style']);
            if (array_key_exists('class', $row))
                $this->viewHandler->parseSelf($row['class']);

            $this->viewHandler->parseVariables($row);
            $this->viewHandler->parseEvents($row);
            foreach ($row['values'] as &$cell) {
                $this->viewHandler->parsePart($cell['value']);
            }
            $this->viewHandler->parseLoop($row);
            $this->viewHandler->parseVisibilityCondition($row);
        }
    }

    private function processTableRows(&$rows, $viewParams, $visitor)
    {
        $this->viewHandler->processLoop($rows, $viewParams, $visitor, function (&$row, $viewParams, $visitor) {
            $this->viewHandler->processVariables($row, $viewParams, $visitor, function ($viewParams, $visitor) use (&$row) {
                if (array_key_exists('style', $row))
                    $row['style'] = $row['style']->accept($visitor)->getValue();
                if (array_key_exists('class', $row))
                    $row['class'] = $row['class']->accept($visitor)->getValue();
                $this->viewHandler->processEvents($row, $visitor);
                foreach ($row['values'] as &$cell) {
                    $this->viewHandler->processPart($cell['value'], $viewParams, $visitor);
                }
            });
        });
    }

    function putTogetherRows(&$rows, &$getPart)
    {
        ViewEditHandler::putTogetherRepeat($rows, $getPart, function (&$row) use (&$getPart) {
            foreach ($row['values'] as &$cell) {
                ViewEditHandler::putTogetherPart($cell['value'], $getPart);
            }
        });
    }


    public function init()
    {
        $course = $this->getParent();
        $courseId = $course->getId();
        ViewHandler::registerVariable("%index", "integer", null, null, "Represents the current index while iterating a collection");
        ViewHandler::registerVariable("%item", "object", null, null, "Represents the object that is currently being iterated in that view");


        ViewHandler::registerLibrary(null, "Object And Collection Manipulation", "Functions that can be called over collections,objects or other values of any library");
        ViewHandler::registerLibrary("views", "system", "This library provides general functionalities that aren't related with getting info from the database");
        //functions of views' expression language
        ViewHandler::registerFunction('system', 'if', function (&$condition, &$ifTrue, &$ifFalse) {
            return new ValueNode($condition ? $ifTrue :  $ifFalse);
        }, "Checks the condition and returns the second argument if true, or the third, if false.", 'mixed', null, 'library');
        ViewHandler::registerFunction('system', 'abs', function (int $val) {
            return new ValueNode(abs($val));
        },  'Returns the absolute value of an integer.', 'integer', null, 'library');
        ViewHandler::registerFunction('system', 'min', function (int $val1, int $val2) {
            return new ValueNode(min($val1, $val2));
        }, 'Returns the smallest number between two integers.', 'integer', null, 'library');
        ViewHandler::registerFunction('system', 'max', function (int $val1, int $val2) {
            return new ValueNode(max($val1, $val2));
        },  'Returns the greatest number between two integers.', 'integer', null, 'library');
        ViewHandler::registerFunction(
            'system',
            'time',
            function () {
                return new ValueNode(time());
            },
            'Returns the time in seconds since the epoch as a floating point number. The specific date of the epoch and the handling of leap seconds is platform dependent. On Windows and most Unix systems, the epoch is January 1, 1970, 00:00:00 (UTC) and leap seconds are not counted towards the time in seconds since the epoch. This is commonly referred to as Unix time.',
            'integer',
            null,
            'library'
        );
        //functions without library
        //%string.strip  -> removes spaces
        ViewHandler::registerFunction(null, 'strip', function (string $val) {
            if (!is_string($val))
                throw new \Exception("'.strip' can only be called over an string.");
            return new ValueNode(str_replace(' ', '', $val));
        },  'Removes the string spaces', 'string', null, 'string');
        //%integer.abs
        ViewHandler::registerFunction(null, 'abs', function (int $val) {
            if (!is_int($val))
                throw new \Exception("'.abs' can only be called over an int.");
            return new ValueNode(abs($val));
        }, 'Returns the absolute value of an integer.', 'integer', null, 'integer');
        //%string.integer or %string.int   converts string to int
        ViewHandler::registerFunction(null, 'int', function (string $val) {
            return $this->toInt($val, "int");
        },  'Returns an integer representation of the string.', 'integer', null, 'string');
        ViewHandler::registerFunction(null, 'integer', function (string $val) {
            return $this->toInt($val, "integer");
        },  'Returns an integer representation of the string.', 'integer', null, 'string');
        //%object.id
        ViewHandler::registerFunction(null, 'id', function ($object) {
            return $this->basicGetterFunction($object, "id");
        },  'Returns an integer that identifies the object.', 'integer', null, "object");
        //%item.parent returns the parent(aka the %item of the previous context)
        ViewHandler::registerFunction(null, 'parent', function ($object) {
            return $this->basicGetterFunction($object, "parent");
        },  'Returns an object in the next hierarchical level.', 'object', null, "object");
        //functions to be called on %collection
        //%collection.item(index) returns item w the given index
        ViewHandler::registerFunction(null, 'item', function ($collection, int $i) {
            Module::checkArray($collection, "collection", "item()");
            if (is_array($collection["value"][$i]))
                return $this->createNode($collection["value"][$i]);
            else
                return new ValueNode($collection["value"][$i]);
        },  'Returns the element x such that i is the index of x in the collection.', 'object', null, 'collection');
        //%collection.index(item)  returns the index of the item in the collection
        ViewHandler::registerFunction(null, 'index', function ($collection, $x) {
            Module::checkArray($collection, "collection", "index()");
            $result = array_search($x["value"]["id"], array_column($collection["value"], "id"));
            if ($result === false) {
                throw new \Exception("In function .index(x): Coudn't find the x in the collection");
            }
            return new ValueNode($result);
        },  'Returns the smallest i such that i is the index of the first occurrence of x in the collection.', 'integer', null, 'collection');
        //%collection.count  returns size of the collection
        ViewHandler::registerFunction(null, 'count', function ($collection) {
            Module::checkArray($collection, "collection", "count");
            return new ValueNode(sizeof($collection["value"]));
        },   'Returns the number of elements in the collection.', 'integer', null, 'collection');
        //%collection.crop(start,end) returns collection croped to start and end (inclusive)
        ViewHandler::registerFunction(null, 'crop', function ($collection, int $start, int $end) {
            Module::checkArray($collection, "collection", "crop()");
            $collection["value"] = array_slice($collection["value"], $start, $end - $start + 1);
            return new ValueNode($collection);
        },  "Returns the collection only with objects that have an index between start and end, inclusively.", 'collection', null, 'collection');
        //$collection.filter(key,val,op) returns collection w items that pass the condition of the filter
        ViewHandler::registerFunction(null, 'filter', function ($collection, string $key, string $value, string $operation) use ($courseId) {
            Module::checkArray($collection, "collection", "filter()");

            $this->evaluateKey($key, $collection, $courseId);
            $newCollectionVals = [];
            foreach ($collection["value"] as $item) {
                if ($this->evalCondition($item[$key], $value, $operation)) {
                    $newCollectionVals[] = $item;
                }
            }
            $collection["value"] = $newCollectionVals;
            return new ValueNode($collection);
        },  'Returns the collection only with objects that have an index between start and end, inclusively.', 'collection', null, 'collection');
        //%collection.sort(order=(asc|des),keys) returns collection sorted by key
        ViewHandler::registerFunction(
            null,
            'sort',
            function ($collection = null, string $order = null, string $keys = null) use ($courseId) {
                if (empty($collection["value"]))
                    return new ValueNode($collection);

                Module::checkArray($collection, "collection", "sort()");
                if ($order === null) throw new \Exception("On function .sort(order,keys), no order was given.");
                if ($keys === null) throw new \Exception("On function .sort(order,keys), no keys were given.");
                $keys = explode(";", $keys);
                $i = 0;
                foreach ($keys as &$key) {
                    if (!array_key_exists($key, $collection["value"][0])) {
                        //key is not a parameter of objects in collection, it should be an expression of the language
                        if (strpos($key, "{") !== 0)
                            $key = "{" . $key . "}";

                        ViewHandler::parseSelf($key);
                        foreach ($collection["value"] as &$object) {
                            $viewParams = array(
                                'course' => (string)$courseId,
                                'viewer' => (string)Core::getLoggedUser()->getId(),
                                'item' => $this->createNode($object, $object["libraryOfVariable"])->getValue(),
                                'index' => $i
                            );
                            $visitor = new EvaluateVisitor($viewParams, $this->viewHandler);
                            $value = $key->accept($visitor)->getValue();

                            $object["sortVariable" . $i] = $value;
                        }
                        $key = "sortVariable" . $i;
                    }
                    $i++;
                }
                if ($order == "asc" || $order == "ascending") {
                    usort($collection["value"], function ($a, $b) use ($keys) {
                        foreach ($keys as $key) {
                            if ($a[$key] > $b[$key]) return 1;
                            else if ($a[$key] < $b[$key]) return -1;
                        }
                        return 1;
                    });
                } else if ($order == "des" || $order == "descending") {
                    usort($collection["value"], function ($a, $b)  use ($keys) {
                        foreach ($keys as $key) {
                            if ($a[$key] < $b[$key]) return 1;
                            else if ($a[$key] > $b[$key]) return -1;
                        }
                        return 1;
                    });
                } else {
                    throw new \Exception("On function .sort(order,keys), the order must be ascending or descending.");
                }
                return new ValueNode($collection);
            },
            'Returns the collection with objects sorted in a specific order by variables keys, from left to right separated by a ;. Any key may be an expression.',
            'collection',
            null,
            'collection'
        );
        //%collection.getKNeighbors, returns k neighbors
        ViewHandler::registerFunction(
            null,
            'getKNeighbors',
            function ($collection, $user, $k) use ($courseId) {
                $key = array_search($user, array_column($collection['value'], 'id'));
                $nElements = count($collection['value']);
                $result = [];
                // elements before
                for ($i = $k; $i > 0; $i--){
                    if($key - $i >= 0){
                        $collection['value'][$key - $i]["rank"] = $key - $i;
                        $result[] = $collection['value'][$key - $i];
                    }
                }
                // add student
                $collection['value'][$key]["rank"] = $key;
                $result[] = $collection['value'][$key];


                // elements after
                for ($i = 1; $i <= $k and $key + $i < $nElements; $i++){
                    $collection['value'][$key + $i]["rank"] = $key + $i;
                    $result[] = $collection['value'][$key + $i];
                }

                return $this->createNode($result, 'users', "collection");

            },
            "Returns a collection with k neighbors.\nk: The number of neighbors to return. Ex: k = 3 will return the 3 users before and the 3 users after the user viewing the page.",
            'collection',
            null,
            'collection'
        );
        //functions of actions(events) library,
        //they don't really do anything, they're just here so their arguments can be processed
        ViewHandler::registerLibrary("views", "actions", "Library to be used only on EVENTS. These functions define the response to event triggers");
        ViewHandler::registerFunction("actions", 'goToPage', function (string $page, $user = null) {
            $id = ViewHandler::getPages(null, $page)["id"];
            if ($id !== null) {
                $response = "goToPage('" . $page . "'," . $id;
            } else {
                $response = "goToPage('" . $page . "',null";
            }
            if ($user !== null) { //if user is specified get its value
                $userId = $this->getUserId($user);
                $response .= "," . $userId . ")";
            } else {
                $response .= ")";
            }
            return new ValueNode($response);
        },  'Changes the current page to the page referred by name.', null, null, 'library');

        //fucntions to change the visibility of a view element with the specified label
        //the $visitor parameter is provided by the visitor itself
        ViewHandler::registerFunction("actions", 'hideView', function ($label, $visitor) {
            return new ValueNode("hideView('" . $label->accept($visitor)->getValue() . "')");
        },  'Changes the visibility of a view referred by label to make it invisible.', null, null, 'library');
        ViewHandler::registerFunction("actions", 'showView', function ($label, $visitor) {
            return new ValueNode("showView('" . $label->accept($visitor)->getValue() . "')");
        },  'Changes the visibility of a view referred by label to make it invisible.', null, null, 'library');
        ViewHandler::registerFunction("actions", 'toggleView', function ($label, $visitor) {
            ViewHandler::parseSelf($label);
            return new ValueNode("toggleView('" . $label->accept($visitor)->getValue() . "')");
        },  'Toggles the visibility of a view referred by label.', null, null, 'library');
        //call view handle template (parse and process its view)
        //the $params argument is provided by the visitor
        ViewHandler::registerFunction("actions", 'showToolTip', function (string $templateName, $user, $params = []) use ($course) {
            return $this->popUpOrToolTip($templateName, $params, "showToolTip", $course, $user);
        },   'Creates a template view referred by name in a form of a tooltip.', null, null, 'library');
        ViewHandler::registerFunction("actions", 'showPopUp', function (string $templateName, $user, $params) use ($course) {
            return $this->popUpOrToolTip($templateName, $params, "showPopUp", $course, $user);
        }, 'Creates a template view referred by name in a form of a pop-up.', null, null, 'library');

        ViewHandler::registerLibrary("views", "users", "This library provides access to information regarding Users and their info.");
        ViewHandler::registerVariable("%user", "integer", null, "users", "Represents the user associated to the page which is being displayed");
        ViewHandler::registerVariable("%viewer", "integer", null, "users", "Represents the user that is currently logged in watching the page");
        //functions of users library
        //users.getAllUsers(role,course) returns collection of users
        ViewHandler::registerFunction(
            'users',
            'getAllUsers',
            function (string $role = null, int $courseId = null, bool $isActive = true) use ($course) {
                if ($courseId !== null) {
                    $course = new Course($courseId);
                }
                if ($role == null)
                    return $this->createNode($course->getUsers($isActive), 'users', "collection");
                else
                    return $this->createNode($course->getUsersWithRole($role, $isActive), 'users', "collection");
            },
            "Returns a collection with all users. The optional parameters can be used to find users that specify a given combination of conditions:\ncourse: The id of a Course.\nrole: The role the GameCourseUser has.\nisActive: Return all users (False), or only active users (True). Defaults to True.",
            'collection',
            'user',
            'library'
        );
        //users.getUser(id) returns user object
        ViewHandler::registerFunction(
            'users',
            'getUser',
            function (int $id) use ($course) {
                $user = $course->getUser($id)->getAllData();
                if (empty($user)) {
                    throw new \Exception("In function getUser(id): The ID given doesn't match any user");
                }
                return $this->createNode($user, 'users');
            },
            "Returns a collection with all GameCourseUsers. The optional parameters can be used to find GameCourseUsers that specify a given combination of conditions:\ncourse: The id of a Course.\nrole: The role the GameCourseUser has.",
            'object',
            'user',
            'library'
        );
        //users.hasPicture(user) returns boolean
        ViewHandler::registerFunction(
            'users',
            'hasPicture',
            function (int $user) {
                $username = Core::$systemDB->select("auth", ["game_course_user_id" => $user], "username");
                if (file_exists("photos/" . $username . ".png")) {
                    return new ValueNode(true);
                }
                return new ValueNode(false);
            },
            "Returns a boolean whether the user has a picture in the system or not.",
            'boolean',
            'user',
            'library'
        );
        //%user.studentnumber
        ViewHandler::registerFunction(
            'users',
            'studentNumber',
            function ($user) {
                return $this->basicGetterFunction($user, "studentNumber");
            },
            'Returns a string with the student number of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.major
        ViewHandler::registerFunction(
            'users',
            'major',
            function ($user) {
                return $this->basicGetterFunction($user, "major");
            },
            'Returns a string with the major of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.email
        ViewHandler::registerFunction(
            'users',
            'email',
            function ($user) {
                return $this->basicGetterFunction($user, "email");
            },
            'Returns a string with the email of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.isAdmin
        ViewHandler::registerFunction(
            'users',
            'isAdmin',
            function ($user) {
                return $this->basicGetterFunction($user, "isAdmin");
            },
            'Returns a boolean regarding whether the GameCourseUser has admin permissions.',
            'boolean',
            null,
            'object',
            'user'
        );
        //%user.lastActivity
        ViewHandler::registerFunction(
            'users',
            'lastActivity',
            function ($user) {
                return $this->basicGetterFunction($user, "lastActivity");
            },
            'Returns a string with the timestamp with the last action of the GameCourseUser in the system.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.previousActivity
        ViewHandler::registerFunction(
            'users',
            'previousActivity',
            function ($user) {
                $id = $this->basicGetterFunction($user, "id")->getValue();
                $previousActivity = Core::$systemDB->select("course_user", ["id" => $id], "previousActivity");
                return new ValueNode($previousActivity);
            },
            'Returns a string with the timestamp with the second to last action of the GameCourseUser in the system.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.name
        ViewHandler::registerFunction(
            'users',
            'name',
            function ($user) {
                return $this->basicGetterFunction($user, "name");
            },
            'Returns a string with the name of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.nickname
        ViewHandler::registerFunction(
            'users',
            'nickname',
            function ($user) {
                $id = $this->basicGetterFunction($user, "id")->getValue();
                $nickname = Core::$systemDB->select("game_course_user", ["id" => $id], "nickname");
                return new ValueNode($nickname);
            },
            'Returns a string with the nickname of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.studentNumber
        ViewHandler::registerFunction(
            'users',
            'studentNumber',
            function ($user) {
                $id = $this->basicGetterFunction($user, "id")->getValue();
                $studentNumber = Core::$systemDB->select("game_course_user", ["id" => $id], "studentNumber");
                return new ValueNode($studentNumber);
            },
            'Returns a string with the student number of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.roles returns collection of role names
        ViewHandler::registerFunction(
            'users',
            'roles',
            function ($user) use ($course) {
                Module::checkArray($user, "object", "roles", "id");
                return $this->createNode((new \GameCourse\CourseUser($user["value"]["id"], $course))->getRolesNames(),
                    null,
                    "collection"
                );
            },
            'Returns a collection with the roles of the GameCourseUser in the Course.',
            'collection',
            'integer',
            'object',
            'user'
        );
        //%users.username
        ViewHandler::registerFunction(
            'users',
            'username',
            function ($user) {
                $id = $this->basicGetterFunction($user, "id")->getValue();
                $username = Core::$systemDB->select("auth", ["game_course_user_id" => $id], "username");
                return new ValueNode($username);
            },
            'Returns a string with the username of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%users.picture
        ViewHandler::registerFunction(
            'users',
            'picture',
            function ($user) {
                Module::checkArray($user, "object", "picture", "id");
                if (file_exists("photos/" . $user["value"]["username"] . ".png")) {
                    return new ValueNode("photos/" . $user["value"]["username"] . ".png");
                }
                return new ValueNode("photos/no-photo.png");
            },
            'Returns the picture of the profile of the GameCourseUser.',
            'picture',
            null,
            'object',
            'user'
        );
        //%user.rank
        ViewHandler::registerFunction(
            'users',
            'rank',
            function ($user) {
                return $this->basicGetterFunction($user, "rank");
            },
            'Returns a string with the position of the user on a collection. To be used with getKNeighbors',
            'string',
            null,
            'object',
            'user'
        );
        //%user.getAllCourses(role)
        ViewHandler::registerFunction(
            'users',
            'getAllCourses',
            function ($user, string $role = null) {
                Module::checkArray($user, "object", "getAllCourses");
                if ($role == null) {
                    $courses = Core::$systemDB->selectMultiple(
                        "course c join course_user u on course=c.id",
                        ["u.id" => $user["value"]["id"]],
                        "c.*"
                    );
                } else {
                    $courses = Core::$systemDB->selectMultiple(
                        "course_user u natural join user_role join role r on r.id=role " .
                        "join course c on u.course=c.id",
                        ["u.id" => $user["value"]["id"], "r.name" => $role],
                        "c.*"
                    );
                }
                return $this->createNode($courses, "courses", "collection", $user);
            },
            "Returns a collection of Courses to which the CourseUser is associated. Receives an optional specific role to search for Courses to which the CourseUser is associated with that role.",
            'collection',
            "course",
            'object',
            'user'
        );

        ViewHandler::registerLibrary("views", "courses", "This library provides access to information regarding Courses and their info.");
        ViewHandler::registerVariable("%course", "integer", null, "courses", "Represents the course that the user is manipulating");

        //functions of course library
        //courses.getAllCourses(isActive,isVisible) returns collection of courses
        ViewHandler::registerFunction(
            'courses',
            'getAllCourses',
            function (bool $isActive = null, bool $isVisible = null) {
                $where = [];
                if ($isActive !== null)
                    $where["isActive"] = $isActive;
                if ($isVisible !== null)
                    $where["isVisible"] = $isVisible;
                return $this->createNode(Core::$systemDB->selectMultiple("course", $where), "courses", "collection");
            },
            "Returns a collection with all the courses in the system. The optional parameters can be used to find courses that specify a given combination of conditions:\nisActive: active or inactive depending whether the course is active.\nisVisible: visible or invisible depending whether the course is visible.",
            'collection',
            "course",
            'library'
        );
        //courses.getCourse(id) returns course object
        ViewHandler::registerFunction(
            'courses',
            'getCourse',
            function (int $id) {
                $course = Core::$systemDB->select("course", ["id" => $id]);
                if (empty($course))
                    throw new \Exception("In function courses.getCourse(...): Coudn't find course with id=" . $id);
                return $this->createNode($course, "courses", "object");
            },
            'Returns the object course with the specific id.',
            'object',
            'course',
            'library'
        );
        //%course.isActive
        ViewHandler::registerFunction(
            'courses',
            'isActive',
            function ($course) {
                return $this->basicGetterFunction($course, "isActive");
            },
            'Returns a boolean on whether the course is active.',
            'boolean',
            null,
            "object",
            "course"
        );
        //%course.isVisible
        ViewHandler::registerFunction(
            'courses',
            'isVisible',
            function ($course) {
                return $this->basicGetterFunction($course, "isVisible");
            },
            'Returns a boolean on whether the course is visible.',
            'boolean',
            null,
            "object",
            "course"
        );
        //%course.name
        ViewHandler::registerFunction(
            'courses',
            'name',
            function ($course) {
                return $this->basicGetterFunction($course, "name");
            },
            'Returns a string with the name of the course.',
            'string',
            null,
            "object",
            "course"
        );
        //%course.roles   returns collection of roles(which are just strings
        ViewHandler::registerFunction(
            'courses',
            'roles',
            function ($course) {
                Module::checkArray($course, "object", "roles");
                $roles = array_column(Core::$systemDB->selectMultiple("role", ["course" => $course["value"]["id"]], "name"), "name");
                return $this->createNode($roles, null, "collection");
            },
            'Returns a collection with all the roles in the course.',
            'collection',
            'string',
            "object",
            "course"
        );

        ViewHandler::registerLibrary("views", "awards", "This library provides access to information regarding Awards.");

        //functions of awards library
        //awards.getAllAwards(user,type,moduleInstance,initialdate,finaldate, activeUser, activeItem)
        ViewHandler::registerFunction(
            'awards',
            'getAllAwards',
            function (int $user = null, string $type = null, string $moduleInstance = null, string $initialDate = null, string $finalDate = null, bool $activeUser = true, bool $activeItem = true) use ($courseId) {
                return $this->getAwardOrParticipationAux($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, [], "award", $activeUser, $activeItem);
            },
            "Returns a collection with all the awards in the Course. The optional parameters can be used to find awards that specify a given combination of conditions:\nuser: id of a GameCourseUser.\ntype: Type of the event that led to the award.\nmoduleInstance: Name of an instance of an object from a Module.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.\nactiveUser: return data regarding active users only (true), or regarding all users(false).\nactiveItem: return data regarding active items only (true), or regarding all items (false).",
            'collection',
            "award",
            'library'
        );
        //%award.renderPicture(item=(user|type)) returns the img or block ok the award (should be used on text views)
        ViewHandler::registerFunction(
            'awards',
            'renderPicture',
            function ($award, string $item) {
                Module::checkArray($award, "object", "renderPicture()");
                if ($item == "user") {
                    $gameCourseId = Core::$systemDB->select("game_course_user", ["id" => $award["value"]["user"]], "id");
                    if (empty($gameCourseId))
                        throw new \Exception("In function renderPicture('user'): couldn't find user.");
                    return new ValueNode("photos/" . $gameCourseId . ".png");
                } else if ($item == "type") {
                    switch ($award["value"]['type']) {
                        case 'grade':
                            return new ValueNode('<img class="img" src="images/quiz.svg">');
                        case 'badge':
                            $name = $this->getModuleNameOfAward($award);
                            if ($name === null)
                                throw new \Exception("In function renderPicture('type'): couldn't find badge.");
                            $level = substr($award["value"]["description"], -2, 1); //assuming that level are always single digit
                            $imgName = str_replace(' ', '', $name . '-' . $level);
                            return new ValueNode('<img class="img" src="badges/' . $imgName . '.png">');
                        case 'skill':
                            $color = '#fff';
                            $skillColor = Core::$systemDB->select("skill", ["id" => $award['value']["moduleInstance"]], "color");
                            if ($skillColor)
                                $color = $skillColor;
                            //needs width and height , should have them if it has latest-awards class in a profile
                            return new ValueNode('<div class="img" style="background-color: ' . $color . '">');
                        case 'bonus':
                            return new ValueNode('<img class="img" src="images/awards.svg">');
                        default:
                            return new ValueNode('<img class="img" src="images/quiz.svg">');
                    }
                } else
                    throw new \Exception("In function renderPicture(item): item must be 'user' or 'type'");
            },
            "Renders the award picture. The item can only have these 2 values: 'user' or 'type'. If 'user', returns the user picture, if 'type', it is shown the picture related to the type of award.",
            'picture',
            null,
            "object",
            "award"
        );
        //%award.description
        ViewHandler::registerFunction(
            'awards',
            'description',
            function ($award) {
                return $this->basicGetterFunction($award, "description");
            },
            'Returns a picture of the item associated to the award. item can refer to the GameCourseUser that won it ("user") and the type of the award ("type").',
            'string',
            null,
            "object",
            "award"
        );
        //%award.moduleInstance
        ViewHandler::registerFunction(
            'awards',
            'moduleInstance',
            function ($award) {
                Module::checkArray($award, "object", "moduleInstance");
                return new ValueNode($this->getModuleNameOfAward($award));
            },
            'Returns a string with the name of the Module instance that provided the award.',
            'string',
            null,
            "object",
            "award"
        );
        //%award.reward
        ViewHandler::registerFunction(
            'awards',
            'reward',
            function ($award) {
                return $this->basicGetterFunction($award, "reward");
            },
            'Returns a string with the reward provided by the award.',
            'string',
            null,
            "object",
            "award"
        );
        //%award.type
        ViewHandler::registerFunction(
            'awards',
            'type',
            function ($award) {
                return $this->basicGetterFunction($award, "type");
            },
            'Returns a string with the type of the event that provided the award.',
            'string',
            null,
            "object",
            "award"
        );
        //%award.date
        ViewHandler::registerFunction(
            'awards',
            'date',
            function ($award) {
                return $this->getDate($award);
            },
            'Returns a string in DD/MM/YYYY format of the date the award was created.',
            'string',
            null,
            "object",
            "award"
        );
        //%award.user
        ViewHandler::registerFunction(
            'awards',
            'user',
            function ($award) {
                return $this->basicGetterFunction($award, "user");
            },
            'Returns a string with the id of the GameCourseUser that received the award.',
            'string',
            null,
            "object",
            "award"
        );

        ViewHandler::registerLibrary("views", "participations", "This library provides access to information regarding Participations.");

        //functions of the participation library
        //participations.getAllParticipations(user,type,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
        ViewHandler::registerFunction(
            'participations',
            'getAllParticipations',
            function (int $user = null, string $type = null, int $rating = null, int $evaluator = null, string $initialDate = null, string $finalDate = null,  bool $activeUser = true, bool $activeItem = true) use ($courseId) {
                $where = [];
                if ($rating !== null) {
                    $where["rating"] = $rating;
                }
                if ($evaluator !== null) {
                    $where["evaluator"] = $evaluator;
                }
                return $this->getAwardOrParticipationAux($courseId, $user, $type, null, $initialDate, $finalDate, $where, "participation",  $activeUser, $activeItem);
            },
            "Returns a collection with all the participations in the Course. The optional parameters can be used to find participations that specify a given combination of conditions:\nuser: id of a GameCourseUser that participated.\ntype: Type of participation.\nrating: Rate given to the participation.\nevaluator: id of a GameCourseUser that rated the participation.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.\nactiveUser: return data regarding active users only (true), or regarding all users(false).\nactiveItem: return data regarding active items only (true), or regarding all items (false).",
            'collection',
            'participation',
            'library'
        );

        //participations.getParticipations(user,type,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
        ViewHandler::registerFunction(
            'participations',
            'getParticipations',
            function (int $user = null, string $type = null, int $rating = null, int $evaluator = null, string $initialDate = null, string $finalDate = null, bool $activeUser = true, bool $activeItem = true) use ($courseId) {
                $where = [];
                if ($rating !== null) {
                    $where["rating"] = (int) $rating;
                }
                if ($evaluator !== null) {
                    $where["evaluator"] = $evaluator;
                }
                return $this->getAwardOrParticipationAux($courseId, $user, $type, null, $initialDate, $finalDate, $where, "participation", $activeUser, $activeItem);
            },
            "Returns a collection with all the participations in the Course. The optional parameters can be used to find participations that specify a given combination of conditions:\nuser: id of a GameCourseUser that participated.\ntype: Type of participation.\nrating: Rate given to the participation.\nevaluator: id of a GameCourseUser that rated the participation.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.",
            'collection',
            'participation',
            'library'
        );

        //participations.getParticipationsByDescription(user,type,description,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
        ViewHandler::registerFunction(
            'participations',
            'getParticipationsByDescription',
            function (int $user = null, string $type = null, string $desc = null, int $rating = null, int $evaluator = null, string $initialDate = null, string $finalDate = null, bool $activeUser = true, bool $activeItem = true) use ($courseId) {
                $where = [];
                if ($rating !== null) {
                    $where["rating"] = (int) $rating;
                }
                if ($evaluator !== null) {
                    $where["evaluator"] = $evaluator;
                }
                if ($desc !== null) {
                    $where["description"] = $desc;
                }
                return $this->getAwardOrParticipationAux($courseId, $user, $type, null, $initialDate, $finalDate, $where, "participation", $activeUser, $activeItem);
            },
            "Returns a collection with all the participations in the Course. The optional parameters can be used to find participations that specify a given combination of conditions:\nuser: id of a GameCourseUser that participated.\ntype: Type of participation.\ndescription: Description of participation.\nrating: Rate given to the participation.\nevaluator: id of a GameCourseUser that rated the participation.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.",
            'collection',
            'participation',
            'library'
        );

        //participations.getPeerGrades(user,rating)
        ViewHandler::registerFunction(
            'participations',
            'getPeerGrades',
            function (int $user = null, int $rating = null, int $evaluator = null, bool $activeUser = true, bool $activeItem = true) use ($courseId) {
                $where = [];
                $type = "peergraded post";
                $peerGrades = [];
                if ($evaluator !== null) {
                    $where["evaluator"] = (int) $evaluator;
                }
                if ($rating !== null) {
                    $where["rating"] = (int) $rating;
                }
                $allPeergradedPosts = $this->getAwardOrParticipation($courseId, $user, $type, null, null, null, $where, "participation", $activeUser, $activeItem);
                foreach ($allPeergradedPosts as $peergradedPost) {
                    $post = $peergradedPost["post"];
                    // see if there's a corresponding graded post for this peergrade
                    $gradedPost = Core::$systemDB->selectMultiple("participation", ["type" => "graded post", "post" => $post], '*');
                    if (sizeof($gradedPost) > 0) {
                        array_push($peerGrades, $peergradedPost);
                    }
                }
                return $this->createNode($peerGrades, "participations", "collection");
            },
            "Returns a collection with all the valid peer graded posts (participations) in this Course. A peergrade is considered valid if the the post it refers to has already been graded by a professor. The optional parameters can be used to find peergraded posts that specify a given combination of conditions:\nuser: id of a GameCourseUser that authored the post being peergraded.\nrating: Rate given to the peergraded post.\nevaluator: id of a GameCourse user that rated/graded the post.",
            'collection',
            'participation',
            'library'
        );

        //%participation.date
        ViewHandler::registerFunction(
            'participations',
            'date',
            function ($participation) {
                return $this->getDate($participation);
            },
            'Returns a string in DD/MM/YYYY format of the date of the participation.',
            'string',
            null,
            "object",
            "participation"
        );
        //%participation.description
        ViewHandler::registerFunction(
            'participations',
            'description',
            function ($participation) {
                return $this->basicGetterFunction($participation, "description");
            },
            'Returns a string with the information of the participation.',
            'string',
            null,
            "object",
            "participation"
        );

        //%participation.evaluator
        ViewHandler::registerFunction(
            'participations',
            'evaluator',
            function ($participation) {
                return $this->basicGetterFunction($participation, "evaluator");
            },
            'Returns a string with the id of the user that rated the participation.',
            'string',
            null,
            "object",
            "participation"
        );
        //%participation.post
        ViewHandler::registerFunction(
            'participations',
            'post',
            function ($participation) {
                return $this->basicGetterFunction($participation, "post");
            },
            'Returns a string with the link to the post where the user participated.',
            'string',
            null,
            "object",
            "participation"
        );
        //%participation.rating
        ViewHandler::registerFunction(
            'participations',
            'rating',
            function ($participation) {
                return $this->basicGetterFunction($participation, "rating");
            },
            'Returns a string with the rating of the participation.',
            'string',
            null,
            "object",
            "participation"
        );
        //%participation.type
        ViewHandler::registerFunction(
            'participations',
            'type',
            function ($participation) {
                return $this->basicGetterFunction($participation, "type");
            },
            'Returns a string with the type of the participation.',
            'string',
            null,
            "object",
            "participation"
        );
        //%participation.user
        ViewHandler::registerFunction(
            'participations',
            'user',
            function ($participation) {
                return $this->basicGetterFunction($participation, "user");
            },
            'Returns a string with the id of the user that participated.',
            'string',
            null,
            "object",
            "participation"
        );

        //parts
        ViewHandler::registerPartType(
            'text',
            null,
            null,
            function (&$value) { //parse function
                if (array_key_exists('link', $value)) {
                    ViewHandler::parseSelf($value['link']);
                }
                ViewHandler::parseSelf($value["value"]);
            },
            function (&$value, $viewParams, $visitor) { //processing function
                if (array_key_exists('link', $value)) {
                    $value['link'] = $value['link']->accept($visitor)->getValue();
                }
                $value['valueType'] = 'text';
                $value["value"] = $value["value"]->accept($visitor)->getValue();
            }
        );

        ViewHandler::registerPartType(
            'image',
            null,
            null,
            function (&$image) { //parse function
                if (array_key_exists('link', $image)) {
                    ViewHandler::parseSelf($image['link']);
                }
                ViewHandler::parseSelf($image["value"]);
                $image['edit'] = false;
            },
            function (&$image, $viewParams, $visitor) { //processing function
                if (array_key_exists('link', $image))
                    $image['link'] = $image['link']->accept($visitor)->getValue();

                $image["value"] = $image["value"]->accept($visitor)->getValue();
            }
        );

        ViewHandler::registerPartType(
            'header',
            null,
            null,
            function (&$header) { //parse function
                if (array_key_exists('image', $header)) {
                    ViewHandler::parsePart($header['image']);
                }

                if (array_key_exists('title', $header)) {
                    ViewHandler::parsePart($header['title']);
                }
            },
            function (&$header, $viewParams, $visitor) { //processing function
                if (array_key_exists('image', $header)) {
                    ViewHandler::processPart($header['image'], $viewParams, $visitor);
                }

                if (array_key_exists('title', $header)) {
                    ViewHandler::processPart($header['title'], $viewParams, $visitor);
                }
            }
        );

        ViewHandler::registerPartType(
            'table',
            function (&$table, &$savePart) {
                $this->breakTableRows($table['headerRows'], $savePart);
                $this->breakTableRows($table['rows'], $savePart);
            },
            function (&$table, &$getPart) {
                // $this->putTogetherTableRows($table['headerRows'], $getPart);
                // $this->putTogetherTableRows($table['rows'], $getPart);
                $this->putTogetherRows($table['headerRows'], $getPart);
                $this->putTogetherRows($table['rows'], $getPart);
            },
            function (&$table) { //parse function
                $this->parseTableRows($table['headerRows']);
                $this->parseTableRows($table['rows']);
            },
            function (&$table, $viewParams, $visitor) { //processing function
                $this->processTableRows($table['headerRows'], $viewParams, $visitor);
                $this->processTableRows($table['rows'], $viewParams, $visitor);
            }
        );

        $this->viewHandler->registerPartType(
            'block',
            null,
            null,
            function (&$block) { //parse function
                if (array_key_exists('header', $block)) {
                    $this->viewHandler->parsePart($block['header']['title']);
                    $this->viewHandler->parsePart($block['header']['image']);
                }

                if (array_key_exists('children', $block)) {
                    foreach ($block['children'] as &$child) {
                        $this->viewHandler->parsePart($child);
                    }
                }
            },
            function (&$block, $viewParams, $visitor) { //processing function
                if (array_key_exists('header', $block)) {
                    $this->viewHandler->processPart($block['header']['title'], $viewParams, $visitor);
                    $this->viewHandler->processPart($block['header']['image'], $viewParams, $visitor);
                }

                if (array_key_exists('children', $block)) {
                    $this->viewHandler->processLoop($block['children'], $viewParams, $visitor, function (&$child, $params, $visitor) {
                        $this->viewHandler->processPart($child, $params, $visitor);
                    });
                }
            }
        );

        //participations.getVideoViews(user, nameSubstring)
        $this->viewHandler->registerFunction(
            'participations',
            'getVideoViews',
            function (int $user, $nameSubstring) use ($courseId) {
                $table = "participation";
                $where = ["user" => $user, "type" => "url viewed", "course" => $courseId];
                $likeParams = ["description" => $nameSubstring];
                $participations = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], "description", $likeParams);
                return $this->createNode($participations, "participation", "collection");
            },
            "Returns a collection of unique url views for videos. The parameter can be used to find participations for a user:\nuser: id of a GameCourseUser that participated.\nnameSubstring: how to identify videos.Ex:'[Video]%'",
            'collection',
            'participation',
            'library'
        );

        //participations.getResourceViews(user)
        $this->viewHandler->registerFunction(
            'participations',
            'getResourceViews',

            function (int $user) use ($courseId) {
                $table = "participation";

                $where = ["user" => $user, "type" => "resource view", "course" => $courseId];
                $likeParams = ["description" => "Lecture % Slides"];

                $skillTreeParticipation = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], "description", $likeParams);

                return $this->createNode($skillTreeParticipation, "participation", "collection");
            },
            "Returns a collection of unique resource views for Lecture Slides. The parameter can be used to find participations for a user:\nuser: id of a GameCourseUser that participated.",
            'collection',
            'participation',
            'library'
        );


        //participations.getForumParticipationsuser, forum)
        $this->viewHandler->registerFunction(
            'participations',
            'getForumParticipations',

            function (int $user, string $forum, string $thread = null) use ($courseId) {
                $table = "participation";

                if ($thread == null) {
                    # if the name of the thread is not relevant
                    # aka, if users are rewarded for creating posts + comments
                    $where = ["user" => $user, "type" => "graded post", "course" => $courseId];
                    $like = $forum . ",%";
                    $likeParams = ["description" => $like];

                    $forumParticipation = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], null, $likeParams);
                } else {
                    # Name of thread is important for the badge
                    $like = $forum . ", Re: " . $thread . "%";
                    $where = ["user" => $user, "type" => "graded post", "course" => $courseId];
                    $likeParams = ["description" => $like];
                    $forumParticipation = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], null, $likeParams);
                }
                return $this->createNode($forumParticipation, "participations", "collection");
            },
            "Returns a collection with all the participations in a specific forum of the Course. The  parameter can be used to find participations for a user or forum:\nuser: id of a GameCourseUser that participated.\n
                forum: name of a moodle forum to filter participations by.",
            'collection',
            'participation',
            'library'
        );


        //participations.getSkillParticipations(user, skill)
        $this->viewHandler->registerFunction(
            'participations',
            'getSkillParticipations',

            function (int $user, string $skill) use ($courseId) {
                // get users who are evaluators, aka, users who have the role "Teacher"
                // select user_role.id from user_role left join role on user_role.role=role.id where role.name = "Teacher" and role.course = 1;
                $table = "user_role left join role on user_role.role=role.id";
                $columns = "user_role.id";
                $where = ["role.name" => "Teacher", "role.course" => $courseId];

                $evaluators = Core::$systemDB->selectMultiple($table, $where, $columns, null, [], [], null, null);
                $teachers = [];
                foreach ($evaluators as $evaluator) {
                    array_push($teachers, $evaluator["id"]);
                }
                $table = "participation";
                $description = "Skill Tree, Re: " . $skill;
                $orderby = "rating desc";
                $where = ["user" => $user, "type" => "graded post", "description" => $description, "course" => $courseId];
                $forumParticipation = Core::$systemDB->selectMultiple($table, $where, '*', $orderby, [], [], null, null);
                $filteredParticipations = array();

                foreach ($forumParticipation as $participation) {
                    if (in_array($participation["evaluator"], $teachers)) {
                        array_push($filteredParticipations, $participation);
                        break;
                    }
                }
                return $this->createNode($filteredParticipations, "participations", "collection");
            },
            "Returns a collection with all the skill tree participations for a user in the forums. The parameter can be used to find participations for a user:\nuser: id of a GameCourseUser that participated. \nuser: id of a GameCourseUser that participated.",
            'collection',
            'participation',
            'library'
        );


        //participations.getRankings(user, type)
        $this->viewHandler->registerFunction(
            'participations',
            'getRankings',

            function (int $user, string $type) use ($courseId) {
                $table = "participation";
                $where = ["user" => $user, "type" => $type, "course" => $courseId];
                $forumParticipation = Core::$systemDB->selectMultiple($table, $where, 'description', null, [], [], null, null);

                $ranking = 0;
                if (count($forumParticipation) > 0) {
                    $ranking = 4 - intval($forumParticipation[0]['description']);
                }
                return $this->createNode($ranking, "participations", "object");
            },
            "Returns rankings of student awarded rewards.",
            'collection',
            'participation',
            'library'
        );
    }
}
