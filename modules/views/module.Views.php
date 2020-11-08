<?php

namespace Modules\Views;

use Modules\Views\Expression\ValueNode;
use Modules\Views\Expression\EvaluateVisitor;
use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Settings;

class Views extends Module
{
    private $viewHandler;

    public function setupResources()
    {
        parent::addResources('js/views.js');
        parent::addResources('js/views.service.js');
        parent::addResources('js/views.part.text.js');
        parent::addResources('Expression/GameCourseExpression.js');
        parent::addResources('js/');
        parent::addResources('css/views.css');
    }

    public function initSettingsTabs()
    {
        $childTabs = array();
        $pages = $this->viewHandler->getPages();
        $viewTabs=[];
        // foreach ($pages as $pageId => $page) {
        //     $childTabs[] = Settings::buildTabItem($page['name'], 'course.settings.views.view({pageOrTemp:\'page\',view:\'' . $pageId . '\'})', true);
        // }
        // $viewTabs[] = Settings::buildTabItem('Pages', 'course.settings.views', true, $childTabs);

        // $templates = $this->getTemplates();
        // $childTempTabs=[];
        // foreach ($templates as $template) {
        //     $childTempTabs[] = Settings::buildTabItem($template['name'], 'course.settings.views.view({pageOrTemp:\'template\',view:\'' . $template["id"] . '\'})', true);
        // }
        // $viewTabs[] = Settings::buildTabItem('Templates', 'course.settings.views', true, $childTempTabs);

        Settings::addTab(Settings::buildTabItem('Views', 'course.settings.views', true, $viewTabs));
    }

    private function breakTableRows(&$rows, &$savePart)
    {
        ViewEditHandler::breakRepeat($rows, $savePart, function (&$row) use (&$savePart) {
            foreach ($row['values'] as &$cell) {
                ViewEditHandler::breakPart($cell['value'], $savePart);
            }
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
    //auxiliar functions for the expression language functions
    public function getModuleNameOfAwardOrParticipation($object, $award = true)
    {
        if (array_key_exists("name", $object["value"]))
            return $object["value"]["name"];
        $type = $object["value"]["type"];
        if ($type == "badge") {
            return Core::$systemDB->select($type, ["id" => $object["value"]["moduleInstance"]], "name");
        }
        if ($type == "skill") {
            if ($award)
                return $object["value"]["description"];
            else
                return Core::$systemDB->select($type, ["id" => $object["value"]["moduleInstance"]], "name");
        }
        return null;
    }
    //gets timestamps and converts it to DD/MM/YYYY
    public function getDate($object)
    {
        $this->checkArray($object, "object", "date");
        $date = implode("/", array_reverse(explode("-", explode(" ", $object["value"]["date"])[0])));
        return new ValueNode($date);
    }
    //get award or participations from DB
    public function getAwardOrParticipationAux($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, $where = [], $object = "award")
    {
        $awardParticipation = $this->getAwardOrParticipation($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, $where = [], $object);
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
            $this->viewHandler->parseSelf($key);
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

    public function init()
    {
        $this->viewHandler = new ViewHandler($this);
        $course = $this->getParent();
        $courseId = $course->getId();
        $this->viewHandler->registerVariable("%index", "integer", null, null, "Represents the current index while iterating a collection");
        $this->viewHandler->registerVariable("%item", "object", null, null, "Represents the object that is currently being iterated in that view");


        $this->viewHandler->registerLibrary(null, "Object And Collection Manipulation", "Functions that can be called over collections,objects or other values of any library");
        $this->viewHandler->registerLibrary("views", "system", "This library provides general functionalities that aren't related with getting info from the database");
        //functions of views' expression language
        $this->viewHandler->registerFunction('system', 'if', function (&$condition, &$ifTrue, &$ifFalse) {
            return new ValueNode($condition ? $ifTrue :  $ifFalse);
        }, "Checks the condition and returns the second argument if true, or the third, if false.", 'mixed', null, 'library');
        $this->viewHandler->registerFunction('system', 'abs', function (int $val) {
            return new ValueNode(abs($val));
        },  'Returns the absolute value of an integer.', 'integer', null, 'library');
        $this->viewHandler->registerFunction('system', 'min', function (int $val1, int $val2) {
            return new ValueNode(min($val1, $val2));
        }, 'Returns the smallest number between two integers.', 'integer', null, 'library');
        $this->viewHandler->registerFunction('system', 'max', function (int $val1, int $val2) {
            return new ValueNode(max($val1, $val2));
        },  'Returns the greatest number between two integers.', 'integer', null, 'library');
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(null, 'strip', function (string $val) {
            if (!is_string($val))
                throw new \Exception("'.strip' can only be called over an string.");
            return new ValueNode(str_replace(' ', '', $val));
        },  'Removes the string spaces', 'string', null, 'string');
        //%integer.abs
        $this->viewHandler->registerFunction(null, 'abs', function (int $val) {
            if (!is_int($val))
                throw new \Exception("'.abs' can only be called over an int.");
            return new ValueNode(abs($val));
        }, 'Returns the absolute value of an integer.', 'integer', null, 'integer');
        //%string.integer or %string.int   converts string to int
        $this->viewHandler->registerFunction(null, 'int', function (string $val) {
            return $this->toInt($val, "int");
        },  'Returns an integer representation of the string.', 'integer', null, 'string');
        $this->viewHandler->registerFunction(null, 'integer', function (string $val) {
            return $this->toInt($val, "integer");
        },  'Returns an integer representation of the string.', 'integer', null, 'string');
        //%object.id
        $this->viewHandler->registerFunction(null, 'id', function ($object) {
            return $this->basicGetterFunction($object, "id");
        },  'Returns an integer that identifies the object.', 'integer', null, "object");
        //%item.parent returns the parent(aka the %item of the previous context)
        $this->viewHandler->registerFunction(null, 'parent', function ($object) {
            return $this->basicGetterFunction($object, "parent");
        },  'Returns an object in the next hierarchical level.', 'object', null, "object");
        //functions to be called on %collection
        //%collection.item(index) returns item w the given index
        $this->viewHandler->registerFunction(null, 'item', function ($collection, int $i) {
            $this->checkArray($collection, "collection", "item()");
            if (is_array($collection["value"][$i]))
                return $this->createNode($collection["value"][$i]);
            else
                return new ValueNode($collection["value"][$i]);
        },  'Returns the element x such that i is the index of x in the collection.', 'object', null, 'collection');
        //%collection.index(item)  returns the index of the item in the collection
        $this->viewHandler->registerFunction(null, 'index', function ($collection, $x) {
            $this->checkArray($collection, "collection", "index()");
            $result = array_search($x["value"]["id"], array_column($collection["value"], "id"));
            if ($result === false) {
                throw new \Exception("In function .index(x): Coudn't find the x in the collection");
            }
            return new ValueNode($result);
        },  'Returns the smallest i such that i is the index of the first occurrence of x in the collection.', 'integer', null, 'collection');
        //%collection.count  returns size of the collection
        $this->viewHandler->registerFunction(null, 'count', function ($collection) {
            $this->checkArray($collection, "collection", "count");
            return new ValueNode(sizeof($collection["value"]));
        },   'Returns the number of elements in the collection.', 'integer', null, 'collection');
        //%collection.crop(start,end) returns collection croped to start and end (inclusive)
        $this->viewHandler->registerFunction(null, 'crop', function ($collection, int $start, int $end) {
            $this->checkArray($collection, "collection", "crop()");
            $collection["value"] = array_slice($collection["value"], $start, $end - $start + 1);
            return new ValueNode($collection);
        },  "Returns the collection only with objects that have an index between start and end, inclusively.", 'collection', null, 'collection');
        //$collection.filter(key,val,op) returns collection w items that pass the condition of the filter
        $this->viewHandler->registerFunction(null, 'filter', function ($collection, string $key, string $value, string $operation) use ($courseId) {
            $this->checkArray($collection, "collection", "filter()");

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
        $this->viewHandler->registerFunction(
            null,
            'sort',
            function ($collection = null, string $order = null, string $keys = null) use ($courseId) {
                if (empty($collection["value"]))
                    return new ValueNode($collection);

                $this->checkArray($collection, "collection", "sort()");
                if ($order === null) throw new \Exception("On function .sort(order,keys), no order was given.");
                if ($keys === null) throw new \Exception("On function .sort(order,keys), no keys were given.");
                $keys = explode(";", $keys);
                $i = 0;
                foreach ($keys as &$key) {
                    if (!array_key_exists($key, $collection["value"][0])) {
                        //key is not a parameter of objects in collection, it should be an expression of the language
                        if (strpos($key, "{") !== 0)
                            $key = "{" . $key . "}";

                        $this->viewHandler->parseSelf($key);
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
        //functions of actions(events) library, 
        //they don't really do anything, they're just here so their arguments can be processed 
        $this->viewHandler->registerLibrary("views", "actions", "Library to be used only on EVENTS. These functions define the response to event triggers");
        $this->viewHandler->registerFunction("actions", 'goToPage', function (string $page, $user = null) {
            $id = $this->viewHandler->getPages(null, $page)["id"];
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
        $this->viewHandler->registerFunction("actions", 'hideView', function ($label, $visitor) {
            return new ValueNode("hideView('" . $label->accept($visitor)->getValue() . "')");
        },  'Changes the visibility of a view referred by label to make it invisible.', null, null, 'library');
        $this->viewHandler->registerFunction("actions", 'showView', function ($label, $visitor) {
            return new ValueNode("showView('" . $label->accept($visitor)->getValue() . "')");
        },  'Changes the visibility of a view referred by label to make it invisible.', null, null, 'library');
        $this->viewHandler->registerFunction("actions", 'toggleView', function ($label, $visitor) {
            $this->viewHandler->parseSelf($label);
            return new ValueNode("toggleView('" . $label->accept($visitor)->getValue() . "')");
        },  'Toggles the visibility of a view referred by label.', null, null, 'library');
        //call view handle template (parse and process its view)
        //the $params argument is provided by the visitor
        $this->viewHandler->registerFunction("actions", 'showToolTip', function (string $templateName, $user, $params) use ($course) {
            return $this->popUpOrToolTip($templateName, $params, "showToolTip", $course, $user);
        },   'Creates a template view referred by name in a form of a tooltip.', null, null, 'library');
        $this->viewHandler->registerFunction("actions", 'showPopUp', function (string $templateName, $user, $params) use ($course) {
            return $this->popUpOrToolTip($templateName, $params, "showPopUp", $course, $user);
        }, 'Creates a template view referred by name in a form of a pop-up.', null, null, 'library');

        $this->viewHandler->registerLibrary("views", "users", "This library provides access to information regarding Users and their info.");
        $this->viewHandler->registerVariable("%user", "integer", null, "users", "Represents the user associated to the page which is being displayed");
        $this->viewHandler->registerVariable("%viewer", "integer", null, "users", "Represents the user that is currently logged in watching the page");
        //functions of users library
        //users.getAllUsers(role,course) returns collection of users
        $this->viewHandler->registerFunction(
            'users',
            'getAllUsers',
            function (string $role = null, int $courseId = null) use ($course) {
                if ($courseId !== null) {
                    $course = new Course($courseId);
                }
                if ($role == null)
                    return $this->createNode($course->getUsers(), 'users', "collection");
                else
                    return $this->createNode($course->getUsersWithRole($role), 'users', "collection");
            },
            "Returns a collection with all users. The optional parameters can be used to find users that specify a given combination of conditions:\ncourse: The id of a Course.\nrole: The role the GameCourseUser has.",
            'collection',
            'user',
            'library'
        );
        //users.getUser(id) returns user object
        $this->viewHandler->registerFunction(
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
        //%user.campus
        $this->viewHandler->registerFunction(
            'users',
            'campus',
            function ($user) {
                return $this->basicGetterFunction($user, "campus");
            },
            'Returns a string with the campus of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%user.email
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        //%user.name 
        $this->viewHandler->registerFunction(
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
        //%user.roles returns collection of role names
        $this->viewHandler->registerFunction(
            'users',
            'roles',
            function ($user) use ($course) {
                $this->checkArray($user, "object", "roles", "id");
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
        $this->viewHandler->registerFunction(
            'users',
            'username',
            function ($user) {
                return $this->basicGetterFunction($user, "username");
            },
            'Returns a string with the username of the GameCourseUser.',
            'string',
            null,
            'object',
            'user'
        );
        //%users.picture
        $this->viewHandler->registerFunction(
            'users',
            'picture',
            function ($user) {
                $this->checkArray($user, "object", "picture", "id");
                return new ValueNode("photos/" . $user["value"]["id"] . ".png");
            },
            'Returns the picture of the profile of the GameCourseUser.',
            'picture',
            null,
            'object',
            'user'
        );
        //%user.getAllCourses(role)
        $this->viewHandler->registerFunction(
            'users',
            'getAllCourses',
            function ($user, string $role = null) {
                $this->checkArray($user, "object", "getAllCourses");
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

        $this->viewHandler->registerLibrary("views", "courses", "This library provides access to information regarding Courses and their info.");
        $this->viewHandler->registerVariable("%course", "integer", null, "courses", "Represents the course that the user is manipulating");

        //functions of course library
        //courses.getAllCourses(isActive,isVisible) returns collection of courses
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
            'courses',
            'roles',
            function ($course) {
                $this->checkArray($course, "object", "roles");
                $roles = array_column(Core::$systemDB->selectMultiple("role", ["course" => $course["value"]["id"]], "name"), "name");
                return $this->createNode($roles, null, "collection");
            },
            'Returns a collection with all the roles in the course.',
            'collection',
            'string',
            "object",
            "course"
        );

        $this->viewHandler->registerLibrary("views", "awards", "This library provides access to information regarding Awards.");

        //functions of awards library
        //awards.getAllAwards(user,type,moduleInstance,initialdate,finaldate)
        $this->viewHandler->registerFunction(
            'awards',
            'getAllAwards',
            function (int $user = null, string $type = null, string $moduleInstance = null, string $initialDate = null, string $finalDate = null) use ($courseId) {
                return $this->getAwardOrParticipationAux($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate);
            },
            "Returns a collection with all the awards in the Course. The optional parameters can be used to find awards that specify a given combination of conditions:\nuser: id of a GameCourseUser.\ntype: Type of the event that led to the award.\nmoduleInstance: Name of an instance of an object from a Module.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.",
            'collection',
            "award",
            'library'
        );
        //%award.renderPicture(item=(user|type)) returns the img or block ok the award (should be used on text views)
        $this->viewHandler->registerFunction(
            'awards',
            'renderPicture',
            function ($award, string $item) {
                $this->checkArray($award, "object", "renderPicture()");
                if ($item == "user") {
                    $gameCourseId = Core::$systemDB->select("game_course_user", ["id" => $award["value"]["user"]], "id");
                    if (empty($gameCourseId))
                        throw new \Exception("In function renderPicture('user'): couldn't find user.");
                    return new ValueNode("photos/" . $gameCourseId . ".png");
                } else if ($item == "type") {
                    switch ($award["value"]['type']) {
                        case 'grade':
                            return new ValueNode('<img src="images/quiz.svg">');
                        case 'badge':
                            $name = $this->getModuleNameOfAwardOrParticipation($award);
                            if ($name === null)
                                throw new \Exception("In function renderPicture('type'): couldn't find badge.");
                            $level = substr($award["value"]["description"], -2, 1); //assuming that level are always single digit
                            $imgName = str_replace(' ', '', $name . '-' . $level);
                            return new ValueNode('<img src="badges/' . $imgName . '.png">');
                        case 'skill':
                            $color = '#fff';
                            $skillColor = Core::$systemDB->select("skill", ["id" => $award['value']["moduleInstance"]], "color");
                            if ($skillColor)
                                $color = $skillColor;
                            //needs width and height , should have them if it has latest-awards class in a profile
                            return new ValueNode('<div class="skill" style="background-color: ' . $color . '">');
                        case 'bonus':
                            return new ValueNode('<img src="images/awards.svg">');
                        default:
                            return new ValueNode('<img src="images/quiz.svg">');
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
            'awards',
            'moduleInstance',
            function ($award) {
                $this->checkArray($award, "object", "moduleInstance");
                return new ValueNode($this->getModuleNameOfAwardOrParticipation($award));
            },
            'Returns a string with the name of the Module instance that provided the award.',
            'string',
            null,
            "object",
            "award"
        );
        //%award.reward
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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

        $this->viewHandler->registerLibrary("views", "participations", "This library provides access to information regarding Participations.");

        //functions of the participation library
        //participations.getAllParticipations(user,type,moduleInstance,rating,evaluator,initialDate,finalDate)
        $this->viewHandler->registerFunction(
            'participations',
            'getAllParticipations',
            function (int $user = null, string $type = null, string $moduleInstance = null, int $rating = null, int $evaluator = null, string $initialDate = null, string $finalDate = null) use ($courseId) {
                $where = [];
                if ($rating !== null) {
                    $where["rating"] = $rating;
                }
                if ($evaluator !== null) {
                    $where["evaluator"] = $evaluator;
                }
                return $this->getAwardOrParticipationAux($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, $where, "participation");
            },
            "Returns a collection with all the participations in the Course. The optional parameters can be used to find participations that specify a given combination of conditions:\nuser: id of a GameCourseUser that participated.\ntype: Type of participation.\nmoduleInstance: Name of an instance of an object from a Module. Note that moduleInstance only needs a value if type is badge or skill.\nrating: Rate given to the participation.\nevaluator: id of a GameCourseUser that rated the participation.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.",
            'collection',
            'participation',
            'library'
        );
        //%participation.date
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        //%participation.moduleInstance
        $this->viewHandler->registerFunction(
            'participations',
            'moduleInstance',
            function ($participation) {
                return new ValueNode($this->getModuleNameOfAwardOrParticipation($participation, false));
            },
            'Returns a string with the name of the Module instance where the user participated.',
            'string',
            null,
            "object",
            "participation"
        );
        //%participation.post
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerFunction(
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
        $this->viewHandler->registerPartType(
            'text',
            null,
            null,
            function (&$value) { //parse function
                if (array_key_exists('link', $value)) {
                    $this->viewHandler->parseSelf($value['link']);
                }
                $this->viewHandler->parseSelf($value["value"]);
            },
            function (&$value, $viewParams, $visitor) { //processing function
                if (array_key_exists('link', $value)) {
                    $value['link'] = $value['link']->accept($visitor)->getValue();
                }
                $value['valueType'] = 'text';
                $value["value"] = $value["value"]->accept($visitor)->getValue();
            }
        );

        $this->viewHandler->registerPartType(
            'image',
            null,
            null,
            function (&$image) { //parse function
                if (array_key_exists('link', $image)) {
                    $this->viewHandler->parseSelf($image['link']);
                }
                $this->viewHandler->parseSelf($image["value"]);
                $image['edit'] = false;
            },
            function (&$image, $viewParams, $visitor) { //processing function
                if (array_key_exists('link', $image))
                    $image['link'] = $image['link']->accept($visitor)->getValue();

                $image["value"] = $image["value"]->accept($visitor)->getValue();
            }
        );

        $this->viewHandler->registerPartType(
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
                    $block['header']['title']['type'] = 'value';
                    $block['header']['image']['type'] = 'image';
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
        //API functions (functions called in js)

        //gets a parsed and processed view
        API::registerFunction('views', 'view', function () { //this is just being used for pages but can also deal with templates
            $data = $this->getViewSettings();
            $course = $data["course"];
            $courseUser = $course->getLoggedUser();
            $courseUser->refreshActivity();
            if (API::hasKey('needPermission') && API::getValue('needPermission') == true) {
                $user = Core::getLoggedUser();
                $isAdmin = (($user != null && $user->isAdmin()) || $courseUser->isTeacher());
                if (!$isAdmin) {
                    API::error("This page can only be acessd by Adminis or Teachers, you don't have permission");
                }
            }
            $viewParams = [
                'course' => (string)$data["courseId"],
                'viewer' => (string)$courseUser->getId()
            ];
            if ($data["viewSettings"]["roleType"] == "ROLE_INTERACTION") {
                API::requireValues('user');
                $viewParams['user'] = (string) API::getValue('user');
            }

            API::response([ //'fields' => ,//not beeing user currently
                'view' => $this->viewHandler->handle($data["viewSettings"], $course, $viewParams)
            ]);
        });
        //gets list of pages and templates for the views page
        API::registerFunction('views', 'listViews', function () {
            API::requireCourseAdminPermission();
            API::requireValues('course');
            $templates = $this->getTemplates(true);
            $response = [
                'pages' => $this->viewHandler->getPages(),
                'templates' => $templates[0], "globals" => $templates[1]
            ];
            $response['types'] = array(
                ['id' => "ROLE_SINGLE", 'name' => 'Role - Single'],
                ['id' => "ROLE_INTERACTION", 'name' => 'Role - Interaction']
            );
            API::response($response);
        });
        //creates a page or template
        API::registerFunction('views', 'createView', function () {
            API::requireCourseAdminPermission();
            API::requireValues('course', 'name', 'pageOrTemp', 'roleType');

            $roleType = API::getValue('roleType');
            if ($roleType == "ROLE_INTERACTION") {
                $defaultRole = "role.Default>role.Default";
            } else {
                $defaultRole = "role.Default";
            }

            //create new aspectClass
            Core::$systemDB->insert("aspect_class");
            $aspectClass = Core::$systemDB->getLastId();

            //insert default aspect view
            Core::$systemDB->insert("view", ["aspectClass" => $aspectClass, "partType" => "block", "parent" => null, "role" => $defaultRole]);
            $viewId = Core::$systemDB->getLastId();
            //page or template to insert in db
            $newView = ["name" => API::getValue('name'), "course" => API::getValue('course'), "roleType" => $roleType];
            if (API::getValue('pageOrTemp') == "page") {
                $newView["viewId"] = $viewId;
                Core::$systemDB->insert("page", $newView);
            } else {
                Core::$systemDB->insert("template", $newView);
                $templateId = Core::$systemDB->getLastId();
                Core::$systemDB->insert("view_template", ["viewId" => $viewId, "templateId" => $templateId]);
            }
        });
        //creates a new aspect for the page/template, copies content of closest aspect
        API::registerFunction('views', 'createAspectView', function () {
            $data = $this->getViewSettings();
            API::requireValues('info');
            $this->viewHandler->createAspect(
                $data["viewSettings"]["roleType"],
                $data["viewSettings"]["viewId"],
                $data["course"],
                API::getValue('info')
            );

            http_response_code(201);
            return;
        });
        //Delete an aspect view of a page or template
        API::registerFunction('views', 'deleteAspectView', function () {
            $data = $this->getViewSettings();
            $viewSettings = $data["viewSettings"];
            $type = $viewSettings['roleType'];
            API::requireValues('info');
            $info = API::getValue('info');

            if (!array_key_exists('roleOne', $info)) {
                API::error('Missing roleOne in info');
            }
            $aspects = $this->viewHandler->getAspects($viewSettings["viewId"]);

            $isTemplate = $data["pageOrTemp"] == "template";
            if ($type == "ROLE_INTERACTION" && !array_key_exists('roleTwo', $info)) {
                $role = ["role" => $info['roleOne'] . '>%'];
                $this->deleteTemplateRefs($isTemplate, $data["viewId"], $info['roleOne'] . '>%', false);
                //This is assuming that there is always an undeletable default aspect
                Core::$systemDB->delete("view", ["aspectClass" => $aspects[0]["aspectClass"], "partType" => "block", "parent" => null], $role);
            } else {
                $aspectsByRole = array_combine(array_column($aspects, "role"), $aspects);
                if ($type == "ROLE_SINGLE") {
                    $role = $info["roleOne"];
                    $this->deleteTemplateRefs($isTemplate, $data["viewId"], "%>" . $role, false);
                } else if ($type == "ROLE_INTERACTION") {
                    $role = $info['roleOne'] . '>' . $info['roleTwo'];
                    $this->deleteTemplateRefs($isTemplate, $data["viewId"], $info['roleTwo'], true);
                }
                $aspect = $aspectsByRole[$role];
                $this->deleteTemplateRefs($isTemplate, $data["viewId"], $role, true);

                Core::$systemDB->delete("view", ["id" => $aspect["id"]]);
            }
            // if(sizeof($aspects)==2){//only 1 aspect after deletion -> aspectClass becomes null
            //     Core::$systemDB->delete("aspect_class",["aspectClass"=>$aspects[0]["aspectClass"]]);
            // }
            http_response_code(200);
            return;
        });

        //gets page/template info, show aspects (for the page/template settings page)
        API::registerFunction('views', 'getInfo', function () {
            $data = $this->getViewSettings();
            $viewSettings = $data["viewSettings"];
            $course = $data["course"];
            $response = ['viewSettings' => $viewSettings];
            $type = $viewSettings['roleType'];
            $aspects = $this->viewHandler->getAspects($viewSettings["viewId"]);
            $result = [];

            //function to get role details from the role in aspect
            $parseRoleName = function ($aspectRole) {
                $roleInfo = explode(".", $aspectRole); //e.g: role.Default
                $roleSpecification = $roleInfo[1];
                return ["id" => $aspectRole, "name" => $roleSpecification];
            };

            $doubleRoles = []; //for views w role interaction
            foreach ($aspects as $aspects) {
                $aspectRole = $aspects['role']; //string like 'role.Default'
                if ($type == "ROLE_INTERACTION") {
                    $roleTwo = substr($aspectRole, strpos($aspectRole, '>') + 1, strlen($aspectRole));
                    $roleOne = substr($aspectRole, 0, strpos($aspectRole, '>'));
                    $doubleRoles[$roleOne][] = $roleTwo;
                } else {
                    $result[] = $parseRoleName($aspectRole);
                }
            }

            if ($type == "ROLE_INTERACTION") {
                foreach ($doubleRoles as $roleOne => $rolesTwo) {
                    $viewedBy = [];
                    foreach ($rolesTwo as $roleTwo) {
                        $viewedBy[] = $parseRoleName($roleTwo);
                    }
                    $result[] = array_merge($parseRoleName($roleOne), ['viewedBy' => $viewedBy]);
                }
            }

            $response['viewSpecializations'] = $result;
            $response['allIds'] = array();
            $roles = array_merge([["name" => 'Default', "id" => "Default"]], $course->getRolesData());
            $users = $course->getUsersIds();
            $response['allIds'][] = array('id' => 'special.Own', 'name' => 'Own (special)');
            foreach ($roles as $role) {
                $response['allIds'][] = array('id' => 'role.' . $role["name"], 'name' => $role["name"]);
            }
            foreach ($users as $user) {
                $response['allIds'][] = array('id' => 'user.' . $user, 'name' => $user);
            }
            $response["pageOrTemp"] = $data["pageOrTemp"];
            API::response($response);
        });

        //gets contents of template to put it in the view being edited
        API::registerFunction('views', 'getTemplateContent', function () {
            API::requireCourseAdminPermission();
            API::requireValues('role', 'id', 'roleType', 'course');
            $templateView = $this->getTemplateContents(API::getValue("role"), API::getValue("id"), API::getValue("course"), API::getValue("roleType"));
            //the template needs to be contained in 1 view part, if there are multiple we put everything in a block
            if (sizeOf($templateView["children"]) > 1) {
                $block = $templateView;
                $block["partType"] = "block";
                unset($block["id"]);
            } else {
                $block = $templateView["children"][0];
            }
            API::response(array('template' => $block));
        });
        //gets 
        API::registerFunction('views', 'getTemplateReference', function () {
            API::requireCourseAdminPermission(); //course/id/isglobal/name/role/roletype/viewid
            API::requireValues('role', 'id', 'roleType', 'course');
            //get content of template to put in the view
            $templateId = API::getValue("id");
            $templateView = $this->getTemplateContents(API::getValue("role"), $templateId, API::getValue("course"), API::getValue("roleType"));
            $templateView["partType"] = "templateRef";
            $templateView["templateId"] = $templateId;
            $templateView["aspectId"] = $templateView["id"];
            API::response(array('template' => $templateView));
        });


        //save a part of the view as a template or templateRef while editing the view
        API::registerFunction('views', 'saveTemplate', function () {
            API::requireCourseAdminPermission();
            API::requireValues('course', 'name', 'part');
            $templateName = API::getValue('name');
            $content = API::getValue('part');
            $courseId = API::getValue("course");

            $roleType = $this->viewHandler->getRoleType($content["role"]);
            if ($roleType == "ROLE_INTERACTION") {
                $defaultRole = "role.Default>role.Default";
            } else {
                $defaultRole = "role.Default";
            }
            $aspects = [];
            $aspects[] = ["role" => $content["role"], "partType" => "block", "parent" => null];
            Core::$systemDB->insert("aspect_class");
            $aspectClass = Core::$systemDB->getLastId();
            $templateId = $this->setTemplateHelper($aspects, $aspectClass, $courseId, $templateName, $roleType, $content);
            API::response(array('templateId' => $templateId));
        });
        //toggle isGlobal parameter of a template
        API::registerFunction('views', "globalizeTemplate", function () {
            API::requireCourseAdminPermission();
            API::requireValues('id', 'isGlobal');
            Core::$systemDB->update("template", ["isGlobal" => !API::getValue("isGlobal")], ["id" => API::getValue("id")]);
            http_response_code(201);
            return;
        });
        //make copy of global template for the current course
        API::registerFunction('views', "copyGlobalTemplate", function () {
            API::requireCourseAdminPermission();
            API::requireValues('template', 'course');
            $template = API::getValue("template");

            $aspect = Core::$systemDB->select(
                "view_template join view on viewId=id",
                ["partType" => "block", "parent" => null, "templateId" => $template["id"]]
            );
            $aspect["aspectClass"] = null;
            $views = $this->viewHandler->getViewWithParts($aspect["id"]);

            //just coppying the default aspect because we don't know if the other course has the same roles
            $aspectClass = null;
            $views = [$views[0]];
            $this->setTemplateHelper($views, $aspectClass, API::getValue("course"), $template["name"], $template["roleType"]);
            http_response_code(201);
            return;
        });
        //delete page/template
        API::registerFunction('views', 'deleteView', function () {
            API::requireCourseAdminPermission();
            API::requireValues('id', 'course', 'pageOrTemp');
            $id = API::getValue('id');

            if (API::getValue("pageOrTemp") == "template") {
                $pageOrTemplates = Core::$systemDB->selectMultiple("view_template", ["templateId" => $id]);
            } else {
                $pageOrTemplates = Core::$systemDB->selectMultiple("page", ["id" => $id]);
            }

            foreach ($pageOrTemplates as $pageTemp) { //aspect views of pages or template or templateReferences
                $aspectView = Core::$systemDB->select("view", ["id" => $pageTemp["viewId"]]);
                if ($aspectView["partType"] == "block" && $aspectView["parent"] == null && $aspectView["aspectClass"] != null) {
                    Core::$systemDB->delete("view", ["aspectClass" => $aspectView["aspectClass"]]);
                    Core::$systemDB->delete("aspect_class", ["aspectClass" => $aspectView["aspectClass"]]);
                }
                Core::$systemDB->delete("view", ["id" => $pageTemp["viewId"]]);
            }
            Core::$systemDB->delete(API::getValue("pageOrTemp"), ["id" => $id]);
        });
        //export template to a txt file on main project folder, it needs to be moved to a module folder to be used
        API::registerFunction('views', 'exportTemplate', function () {
            API::requireCourseAdminPermission();
            API::requireValues('id', "name", 'course');
            $templateId = API::getValue('id');
            //get aspect
            $aspect = Core::$systemDB->select(
                "view_template join view on viewId=id",
                ["partType" => "block", "parent" => null, "templateId" => $templateId]
            );
            //will get all the aspects (and contents) of the template
            $views = $this->viewHandler->getViewWithParts($aspect["id"]);
            $filename = "Template-" . preg_replace("/[^a-zA-Z0-9-]/", "", API::getValue('name')) . "-" . $templateId . ".txt";
            file_put_contents($filename, json_encode($views));
            API::response(array('filename' => $filename));
        });
        //get contents of a view with a specific aspect, for the edit page
        API::registerFunction('views', 'getEdit', function () {
            API::requireCourseAdminPermission();
            $data = $this->getViewSettings();
            $viewSettings = $data["viewSettings"];
            $viewType = $viewSettings['roleType'];
            API::requireValues('info');
            $info = API::getValue('info');
            if ($viewType == "ROLE_SINGLE") {
                if (!array_key_exists('role', $info)) {
                    API::error('Missing role');
                }
                $view = $this->viewHandler->getViewWithParts($viewSettings["viewId"], $info['role']);
            } else if ($viewType == "ROLE_INTERACTION") {
                if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info)) {
                    API::error('Missing roleOne and/or roleTwo in info');
                }
                $view = $this->viewHandler->getViewWithParts($viewSettings["viewId"], $info['roleOne'] . '>' . $info['roleTwo']);
            }
            $templates = $this->getTemplates();
            API::response(array('view' => $view, 'fields' => [], 'templates' => $templates));
        });
        //getDictionary
        API::registerFunction('views', 'getDictionary', function () {
            API::requireCourseAdminPermission();
            API::requireValues('course');
            $courseId = API::getValue('course');
            //get course libraries
            $course = new Course($courseId);

            API::response([$course->getEnabledLibraries(), $course->getEnabledVariables()]);
        });
        //save the view being edited
        API::registerFunction('views', 'saveEdit', function () {
            $this->saveOrPreview(true);
        });
        //gets data to show preview of the view being edited
        API::registerFunction('views', 'previewEdit', function () {
            $this->saveOrPreview(false);
        });
    }
    //tests view parsing and processing
    function testView($course, $courseId, &$testDone, &$view, $viewerRole, $userRole = null)
    {
        try { //ToDo: for preview viewer should be the current user if they have the role
            $viewerId = $this->getUserIdWithRole($course, $viewerRole);
            $params = ['course' => (string)$courseId];

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

        API::requireValues('info');
        $info = API::getValue('info');
        if ($viewType == "ROLE_SINGLE") {
            if (!array_key_exists('role', $info)) {
                API::error('Missing role');
            }
        } else if ($viewType == "ROLE_INTERACTION") {
            if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info)) {
                API::error('Missing roleOne and/or roleTwo in info');
            }
        }
        $testDone = false;
        $warning = false;
        $viewCopy = $viewContent;
        try {
            $this->viewHandler->parseView($viewCopy);
            if ($viewType == "ROLE_SINGLE") {
                $this->testView($course, $courseId, $testDone, $viewCopy, $info['role']);
            } else if ($viewType == "ROLE_INTERACTION") {
                $this->testView($course, $courseId, $testDone, $viewCopy, $info['roleTwo'], $info['roleOne']);
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
            API::requireValues('sreenshoot', 'pageOrTemp', 'view');
            $pageOrTemplate = API::getValue('pageOrTemp');
            $viewId = API::getValue('view');
            $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('sreenshoot')));
            $this->saveScreensoot($img, $viewId, $pageOrTemplate);

            //print_R($viewContent);
            $this->viewHandler->updateViewAndChildren($viewContent);
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
            API::response(array('view' => $viewCopy));
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
            $users = $course->getUsersWithRole($role);
            if (count($users) != 0)
                $uid = $users[0]['id'];
        } else if (strpos($role, 'user.') === 0) {
            $uid = substr($role, 5);
        }
        return $uid;
    }


    public function getTemplateContents($role, $templateId, $courseId, $templateRoleType)
    {
        $course = new Course($courseId);
        $anAspect = Core::$systemDB->select(
            "view_template join view on viewId=id",
            ["partType" => "block", "parent" => null, "templateId" => $templateId]
        );
        $referenceRoleType = $this->viewHandler->getRoleType($role);

        if ($templateRoleType == "ROLE_INTERACTION") {
            if ($referenceRoleType == "ROLE_SINGLE") {
                $role = "role.Default>" . $role;
            }
            $roles = explode(">", $role);
            $view = $this->viewHandler->getClosestAspect($course, $templateRoleType, $roles[0], $anAspect["id"], $roles[1]);
        } else {
            if ($referenceRoleType == "ROLE_INTERACTION") {
                $role = explode(">", $role)[1];
            }
            $view = $this->viewHandler->getClosestAspect($course, $templateRoleType, $role, $anAspect["id"]);
        }
        return $view;
    }
    public function &getViewHandler()
    {
        return $this->viewHandler;
    }
    public function deleteTemplateRefs($isTemplate, $templateId, $role, $isRoleExact = true)
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
    //gets templates of this course
    public function getTemplates($includeGlobals = false)
    {
        $temps = Core::$systemDB->selectMultiple(
            'template t join view_template on templateId=id join view v on v.id=viewId',
            ['course' => $this->getCourseId(), "partType" => "block", "parent" => null],
            "t.id,name,course,isGlobal,roleType,viewId,role"
        );
        if ($includeGlobals) {
            $globalTemp = Core::$systemDB->selectMultiple("template", ["isGlobal" => true]);
            return [$temps, $globalTemp];
        }
        return $temps;
    }
    //gets template by id
    public function getTemplate($id = null, $name = null)
    {
        $tables = "template t join view_template on templateId=id join view v on v.id=viewId";
        $where = ['course' => $this->getCourseId(), "partType" => "block", "parent" => null];
        if ($id) {
            $where["t.id"] = $id;
        } else {
            $where["name"] = $name;
        }
        $fields = "t.id,name,course,isGlobal,roleType,viewId,role";
        return Core::$systemDB->select($tables, $where, $fields);
    }

    //checks if a template with a given name exists in the DB
    public function templateExists($name)
    {
        return !empty(Core::$systemDB->select('template', ['name' => $name, 'course' => $this->getCourseId()]));
    }

    //receives the template name, its encoded contents, and puts it in the database
    public function setTemplate($name, $template)
    {
        $aspects = json_decode($template, true);
        $aspectClass = null;
        if (sizeof($aspects) > 1) {
            Core::$systemDB->insert("aspect_class");
            $aspectClass = Core::$systemDB->getLastId();
        }
        $roleType = $this->viewHandler->getRoleType($aspects[0]["role"]);
        $this->setTemplateHelper($aspects, $aspectClass, $this->getCourseId(), $name, $roleType);
    }
    //inserts data into template and view_template tables
    function setTemplateHelper($aspects, $aspectClass, $courseId, $name, $roleType, $content = null)
    {
        foreach ($aspects as &$aspect) {
            $aspect["aspectClass"] = $aspectClass;
            Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"], "aspectClass" => $aspectClass]);
            $aspect["id"] = Core::$systemDB->getLastId();
            //print_r($aspect);
            if ($content) {
                $aspect["children"][] = $content;
            }
            $this->viewHandler->updateViewAndChildren($aspect, false, true);
        }
        Core::$systemDB->insert("template", ["course" => $courseId, "name" => $name, "roleType" => $roleType]);
        $templateId = Core::$systemDB->getLastId();
        Core::$systemDB->insert("view_template", ["viewId" => $aspects[0]["id"], "templateId" => $templateId]);
        return $templateId;
    }
    //get settings of page/template 
    function getViewSettings()
    {
        API::requireValues('view', 'pageOrTemp', 'course');
        $id = API::getValue('view'); //page or template id
        $pgOrTemp = API::getValue('pageOrTemp');
        if ($pgOrTemp == "page") {
            if (is_numeric($id)) {
                $viewSettings = $this->viewHandler->getPages($id);
            } else { //for pages, the value of 'view' could be a name instead of an id
                $viewSettings = $this->viewHandler->getPages(null, $id);
            }
        } else { //template
            $viewSettings = $this->getTemplate($id);
        }
        if (empty($viewSettings)) {
            API::error('Unknown ' . $pgOrTemp . ' ' . $id);
        }
        $courseId = API::getValue('course');
        $course = Course::getCourse($courseId);
        return [
            "courseId" => $courseId, "course" => $course, "viewId" => $id,
            "pageOrTemp" => $pgOrTemp, "viewSettings" => $viewSettings
        ];
    }
    public static function saveScreensoot($img, $viewId, $pageOrTemplate){
        file_put_contents("screenshoots/". $pageOrTemplate . "/". $viewId . ".png", $img);
    }
    public function is_configurable(){
        return false;
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'views',
    'name' => 'Views',
    'description' => 'Enables views and the view editor to create pages with expression language.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'factory' => function () {
        return new Views();
    }
));
