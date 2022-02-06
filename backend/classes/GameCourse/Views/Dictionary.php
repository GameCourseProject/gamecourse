<?php

namespace GameCourse\Views;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;
use GameCourse\Views\Expression\EvaluateVisitor;
use GameCourse\Views\Expression\ValueNode;
use ReflectionException;
use ReflectionFunction;


class Dictionary
{

    // NOTE: need these here because they have functions
    //       which are hard to put and retrieve from database
    public static $viewTypes = array();
    public static $viewFunctions = array();

    public static $courseId; // ID received on API request



    /*** ---------------------------------------------------- ***/
    /*** ------------------ Initialization ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Initializes the dictionary.
     * Makes view types, libraries, variables and functions
     * available on the system.
     */
    public static function init(bool $setup = false)
    {
        if ($setup) {
            // NOTE: these only need to be initialized once on setup because
            //       all their params fit in the database (non-functions)

            /*** ----------------------------------------------- ***/
            /*** ------------------ Libraries ------------------ ***/
            /*** ----------------------------------------------- ***/

            self::registerLibrary(null, "Object And Collection Manipulation", "Functions that can be called over collections,objects or other values of any library");
            self::registerLibrary(null, "system", "This library provides general functionalities that aren't related with getting info from the database");
            self::registerLibrary(null, "actions", "Library to be used only on EVENTS. These functions define the response to event triggers");
            self::registerLibrary(null, "users", "This library provides access to information regarding Users and their info.");
            self::registerLibrary(null, "courses", "This library provides access to information regarding Courses and their info.");
            self::registerLibrary(null, "awards", "This library provides access to information regarding Awards.");
            self::registerLibrary(null, "participations", "This library provides access to information regarding Participations.");


            /*** ----------------------------------------------- ***/
            /*** ------------------ Variables ------------------ ***/
            /*** ----------------------------------------------- ***/

            self::registerVariable("%index", "integer", null, "Represents the current index while iterating a collection");
            self::registerVariable("%item", "object", null, "Represents the object that is currently being iterated in that view");
            self::registerVariable("%user", "integer", "users", "Represents the user associated to the page which is being displayed");
            self::registerVariable("%viewer", "integer", "users", "Represents the user that is currently logged in watching the page");
            self::registerVariable("%course", "integer", "courses", "Represents the course that the user is manipulating");
        }


        /*** ----------------------------------------------- ***/
        /*** ------------------ View types ----------------- ***/
        /*** ----------------------------------------------- ***/

        self::registerViewType(
            'text',
            'This type displays text using expressions to show the output.',
            function (&$view) { //parse function
                if (isset($view["link"])) ViewHandler::parseSelf($view['link']);
                ViewHandler::parseSelf($view["value"]);
            },
            function (&$view, $visitor) { //processing function
                if (isset($view["link"])) ViewHandler::processSelf($view["link"], $visitor);
                ViewHandler::processSelf($view["value"], $visitor);
            },
            $setup
        );
        self::registerViewType(
            'image',
            'This type is similar to the Text type. However it produces an image instead of text.',
            function (&$view) { //parse function
                if (isset($view["link"])) ViewHandler::parseSelf($view['link']);
                ViewHandler::parseSelf($view["src"]);
            },
            function (&$view, $visitor) { //processing function
                if (isset($view["link"])) ViewHandler::processSelf($view["link"], $visitor);
                ViewHandler::processSelf($view["src"], $visitor);
            },
            $setup
        );
        self::registerViewType(
            'header',
            'Displays an image and a text.',
            function (&$view) { //parse function
                ViewHandler::parseView($view['image']);
                ViewHandler::parseView($view['title']);
            },
            function (&$view, $visitor) { //processing function
                ViewHandler::processView($view['image'], $visitor);
                ViewHandler::processView($view['title'], $visitor);
            },
            $setup
        );
        self::registerViewType(
            'table',
            'This type is a table with columns and rows. The row and column options appear after pressing the ‘edit layout’ button on the table part.',
            function (&$view) { //parse function
                if (isset($view["headerRows"])) {
                    foreach ($view["headerRows"] as &$headerRow) {
                        ViewHandler::parseView($headerRow);
                    }
                }
                if (isset($view["rows"])) {
                    foreach ($view["rows"] as &$row) {
                        ViewHandler::parseView($row);
                    }
                }
            },
            function (&$view, $visitor) { //processing function
                if (isset($view["headerRows"])) {
                    $processedHeaderRows = [];
                    for ($i = 0; $i < sizeof($view["headerRows"]); $i++) {
                        $headerRow = $view["headerRows"][$i];

                        if (isset($headerRow["loopData"])) {
                            ViewHandler::processLoop($headerRow, $visitor);
                            $processedHeaderRows = array_merge($processedHeaderRows, $headerRow);

                        } else {
                            ViewHandler::processView($headerRow, $visitor);
                            $processedHeaderRows[] = $headerRow;
                        }
                    }
                    $view["headerRows"] = $processedHeaderRows;
                }

                if (isset($view["rows"])) {
                    $processedRows = [];
                    for ($i = 0; $i < sizeof($view["rows"]); $i++) {
                        $row = $view["rows"][$i];

                        if (isset($row["loopData"])) {
                            ViewHandler::processLoop($row, $visitor);
                            $processedRows = array_merge($processedRows, $row);

                        } else {
                            ViewHandler::processView($row, $visitor);
                            $processedRows[] = $row;
                        }
                    }
                    $view["rows"] = $processedRows;
                }
            },
            $setup
        );
        self::registerViewType(
            'block',
            'This type is a view that can contain other views in a vertical order.',
            function (&$view) { //parse function
                if (isset($view["children"])) {
                    foreach ($view['children'] as &$child) {
                        ViewHandler::parseView($child);
                    }
                }
            },
            function (&$view, $visitor) { //processing function
                if (isset($view["children"])) {
                    $processedChildren = [];
                    for ($i = 0; $i < sizeof($view["children"]); $i++) {
                        $child = $view["children"][$i];

                        if (isset($child["loopData"])) {
                            ViewHandler::processLoop($child, $visitor);
                            $processedChildren = array_merge($processedChildren, $child);

                        } else {
                            ViewHandler::processView($child, $visitor);
                            $processedChildren[] = $child;
                        }
                    }
                    $view["children"] = $processedChildren;
                }
            },
            $setup
        );
        self::registerViewType(
            'row',
            'This type is a view that can contain other views in an horizontal order.',
            function (&$view) { //parse function
                if (isset($view["children"])) {
                    foreach ($view['children'] as &$child) {
                        ViewHandler::parseView($child);
                    }
                }
            },
            function (&$view, $visitor) { //processing function
                if (isset($view["children"])) {
                    $processedChildren = [];
                    for ($i = 0; $i < sizeof($view["children"]); $i++) {
                        $child = $view["children"][$i];

                        if (isset($child["loopData"])) {
                            ViewHandler::processLoop($child, $visitor);
                            $processedChildren = array_merge($processedChildren, $child);

                        } else {
                            ViewHandler::processView($child, $visitor);
                            $processedChildren[] = $child;
                        }
                    }
                    $view["children"] = $processedChildren;
                }
            },
            $setup
        );


        /*** ----------------------------------------------- ***/
        /*** ------------------ Functions ------------------ ***/
        /*** ----------------------------------------------- ***/
        // TODO: need to be checked (views' refactor)

        // Functions of Expression Language
        self::registerFunction('system', 'if', function (&$condition, &$ifTrue, &$ifFalse) {
            return new ValueNode($condition ? $ifTrue :  $ifFalse);
        }, "Checks the condition and returns the second argument if true, or the third, if false.", 'mixed', null, 'library', null, $setup);
        self::registerFunction('system', 'abs', function (int $val) {
            return new ValueNode(abs($val));
        },  'Returns the absolute value of an integer.', 'integer', null, 'library', null, $setup);
        self::registerFunction('system', 'min', function (int $val1, int $val2) {
            return new ValueNode(min($val1, $val2));
        }, 'Returns the smallest number between two integers.', 'integer', null, 'library', null, $setup);
        self::registerFunction('system', 'max', function (int $val1, int $val2) {
            return new ValueNode(max($val1, $val2));
        },  'Returns the greatest number between two integers.', 'integer', null, 'library', null, $setup);
        self::registerFunction(
            'system',
            'time',
            function () {
                return new ValueNode(time());
            },
            'Returns the time in seconds since the epoch as a floating point number. The specific date of the epoch and the handling of leap seconds is platform dependent. On Windows and most Unix systems, the epoch is January 1, 1970, 00:00:00 (UTC) and leap seconds are not counted towards the time in seconds since the epoch. This is commonly referred to as Unix time.',
            'integer',
            null,
            'library', null, $setup
        );

        // Functions without library
        //%string.strip -> removes spaces
        self::registerFunction(null, 'strip', function (string $val) {
            if (!is_string($val))
                throw new \Exception("'.strip' can only be called over an string.");
            return new ValueNode(str_replace(' ', '', $val));
        },  'Removes the string spaces', 'string', null, 'string', null, $setup);
        //%integer.abs
        self::registerFunction(null, 'abs', function (int $val) {
            if (!is_int($val))
                throw new \Exception("'.abs' can only be called over an int.");
            return new ValueNode(abs($val));
        }, 'Returns the absolute value of an integer.', 'integer', null, 'integer', null, $setup);
        //%string.integer or %string.int   converts string to int
        self::registerFunction(null, 'int', function (string $val) {
            if (!is_string($val))
                API::error('.int() can only be called over strings.');
            return new ValueNode(intval($val));
        },  'Returns an integer representation of the string.', 'integer', null, 'string', null, $setup);
        self::registerFunction(null, 'integer', function (string $val) {
            if (!is_string($val))
                API::error('.integer() can only be called over strings.');
            return new ValueNode(intval($val));
        },  'Returns an integer representation of the string.', 'integer', null, 'string', null, $setup);
        //%object.id
        self::registerFunction(null, 'id', function ($object) {
            return self::basicGetterFunction($object, "id");
        },  'Returns an integer that identifies the object.', 'integer', null, "object", null, $setup);
        //%item.parent returns the parent(aka the %item of the previous context)
        self::registerFunction(null, 'parent', function ($object) {
            return self::basicGetterFunction($object, "parent");
        },  'Returns an object in the next hierarchical level.', 'object', null, "object", null, $setup);

        // Functions to be called on %collection
        //%collection.item(index) returns item w the given index
        self::registerFunction(null, 'item', function ($collection, int $i) {
            self::checkArray($collection, "collection", "item()");
            if (is_array($collection["value"][$i]))
                return self::createNode($collection["value"][$i]);
            else
                return new ValueNode($collection["value"][$i]);
        },  'Returns the element x such that i is the index of x in the collection.', 'object', null, 'collection', null, $setup);
        //%collection.index(item)  returns the index of the item in the collection
        self::registerFunction(null, 'index', function ($collection, $x) {
            self::checkArray($collection, "collection", "index()");
            $result = array_search($x["value"]["id"], array_column($collection["value"], "id"));
            if ($result === false) {
                throw new \Exception("In function .index(x): Coudn't find the x in the collection");
            }
            return new ValueNode($result);
        },  'Returns the smallest i such that i is the index of the first occurrence of x in the collection.', 'integer', null, 'collection', null, $setup);
        //%collection.count  returns size of the collection
        self::registerFunction(null, 'count', function ($collection) {
            self::checkArray($collection, "collection", "count");
            return new ValueNode(sizeof($collection["value"]));
        },   'Returns the number of elements in the collection.', 'integer', null, 'collection', null, $setup);
        //%collection.crop(start,end) returns collection croped to start and end (inclusive)
        self::registerFunction(null, 'crop', function ($collection, int $start, int $end) {
            self::checkArray($collection, "collection", "crop()");
            $collection["value"] = array_slice($collection["value"], $start, $end - $start + 1);
            return new ValueNode($collection);
        },  "Returns the collection only with objects that have an index between start and end, inclusively.", 'collection', null, 'collection', null, $setup);
        //$collection.filter(key,val,op) returns collection w items that pass the condition of the filter
        self::registerFunction(null, 'filter', function ($collection, string $key, string $value, string $operation) {
            self::checkArray($collection, "collection", "filter()");

            self::evaluateKey($key, $collection, self::$courseId);
            $newCollectionVals = [];
            foreach ($collection["value"] as $item) {
                if (self::evalCondition($item[$key], $value, $operation)) {
                    $newCollectionVals[] = $item;
                }
            }
            $collection["value"] = $newCollectionVals;
            return new ValueNode($collection);
        },  'Returns the collection only with objects that have an index between start and end, inclusively.', 'collection', null, 'collection', null, $setup);
        //%collection.sort(order=(asc|des),keys) returns collection sorted by key
        self::registerFunction(
            null,
            'sort',
            function ($collection = null, string $order = null, string $keys = null) {
                if (empty($collection["value"]))
                    return new ValueNode($collection);

                self::checkArray($collection, "collection", "sort()");
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
                                'course' => (string)self::$courseId,
                                'viewer' => (string)Core::getLoggedUser()->getId(),
                                'item' => self::createNode($object, $object["libraryOfVariable"])->getValue(),
                                'index' => $i
                            );
                            $visitor = new EvaluateVisitor($viewParams);
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
            'collection', null, $setup
        );
        //%collection.getKNeighbors, returns k neighbors
        self::registerFunction(
            null,
            'getKNeighbors',
            function ($collection, $user, $k) {
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

                return self::createNode($result, 'users', "collection");

            },
            "Returns a collection with k neighbors.\nk: The number of neighbors to return. Ex: k = 3 will return the 3 users before and the 3 users after the user viewing the page.",
            'collection',
            null,
            'collection', null, $setup
        );

        // Functions of actions(events) library,
        //they don't really do anything, they're just here so their arguments can be processed
        self::registerFunction("actions", 'goToPage', function (string $pageId, $userId = null) {
            if ($userId !== null) $response = "goToPage(" . $pageId . "," . $userId;
            else $response = "goToPage(" . $pageId;
            $response .= ")";
            return new ValueNode($response);
        },  'Changes the current page to the page referred by name.', null, null, 'library', null, $setup);

        // Functions to change the visibility of a view element with the specified label
        //the $visitor parameter is provided by the visitor itself
        self::registerFunction("actions", 'hideView', function ($label, $visitor) {
            return new ValueNode("hideView('" . $label->accept($visitor)->getValue() . "')");
        },  'Changes the visibility of a view referred by label to make it invisible.', null, null, 'library', null, $setup);
        self::registerFunction("actions", 'showView', function ($label, $visitor) {
            return new ValueNode("showView('" . $label->accept($visitor)->getValue() . "')");
        },  'Changes the visibility of a view referred by label to make it invisible.', null, null, 'library', null, $setup);
        self::registerFunction("actions", 'toggleView', function ($label, $visitor) {
            ViewHandler::parseSelf($label);
            return new ValueNode("toggleView('" . $label->accept($visitor)->getValue() . "')");
        },  'Toggles the visibility of a view referred by label.', null, null, 'library', null, $setup);
        //call view handle template (parse and process its view)
        //the $params argument is provided by the visitor
        self::registerFunction("actions", 'showToolTip', function (string $templateName, $user, $params = [], $course) {
            return self::popUpOrToolTip($templateName, $params, "showToolTip", $course, $user);
        },   'Creates a template view referred by name in a form of a tooltip.', null, null, 'library', null, $setup);
        self::registerFunction("actions", 'showPopUp', function (string $templateName, $user, $params, $course) {
            return self::popUpOrToolTip($templateName, $params, "showPopUp", $course, $user);
        }, 'Creates a template view referred by name in a form of a pop-up.', null, null, 'library', null, $setup);

        // Functions of users library
        //users.getAllUsers(role,course) returns collection of users
        self::registerFunction(
            'users',
            'getAllUsers',
            function (string $role = null, int $courseId, bool $isActive = true) {
                $course = new Course($courseId);
                if ($role == null)
                    return self::createNode($course->getUsers($isActive), 'users', "collection");
                else
                    return self::createNode($course->getUsersWithRole($role, $isActive), 'users', "collection");
            },
            "Returns a collection with all users. The optional parameters can be used to find users that specify a given combination of conditions:\ncourse: The id of a Course.\nrole: The role the GameCourseUser has.\nisActive: Return all users (False), or only active users (True). Defaults to True.",
            'collection',
            'user',
            'library', null, $setup
        );
        //users.getUser(id) returns user object
        self::registerFunction(
            'users',
            'getUser',
            function (int $id) {
                $course = Course::getCourse(self::$courseId, false);
                $user = $course->getUser($id)->getAllData();
                if (empty($user)) {
                    throw new \Exception("In function getUser(id): The ID given doesn't match any user");
                }
                return self::createNode($user, 'users');
            },
            "Returns a collection with all GameCourseUsers. The optional parameters can be used to find GameCourseUsers that specify a given combination of conditions:\ncourse: The id of a Course.\nrole: The role the GameCourseUser has.",
            'object',
            'user',
            'library', null, $setup
        );
        //users.hasPicture(user) returns boolean
        self::registerFunction(
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
            'library', null, $setup
        );
        //%user.studentNumber
        self::registerFunction(
            'users',
            'studentNumber',
            function ($user) {
                $id = self::basicGetterFunction($user, "id")->getValue();
                $studentNumber = Core::$systemDB->select("game_course_user", ["id" => $id], "studentNumber");
                return new ValueNode($studentNumber);
            },
            'Returns a string with the student number of the GameCourseUser.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.major
        self::registerFunction(
            'users',
            'major',
            function ($user) {
                return self::basicGetterFunction($user, "major");
            },
            'Returns a string with the major of the GameCourseUser.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.email
        self::registerFunction(
            'users',
            'email',
            function ($user) {
                return self::basicGetterFunction($user, "email");
            },
            'Returns a string with the email of the GameCourseUser.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.isAdmin
        self::registerFunction(
            'users',
            'isAdmin',
            function ($user) {
                return self::basicGetterFunction($user, "isAdmin");
            },
            'Returns a boolean regarding whether the GameCourseUser has admin permissions.',
            'boolean',
            null,
            'object',
            'user', $setup
        );
        //%user.lastActivity
        self::registerFunction(
            'users',
            'lastActivity',
            function ($user) {
                return self::basicGetterFunction($user, "lastActivity");
            },
            'Returns a string with the timestamp with the last action of the GameCourseUser in the system.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.previousActivity
        self::registerFunction(
            'users',
            'previousActivity',
            function ($user) {
                $id = self::basicGetterFunction($user, "id")->getValue();
                $previousActivity = Core::$systemDB->select("course_user", ["id" => $id], "previousActivity");
                return new ValueNode($previousActivity);
            },
            'Returns a string with the timestamp with the second to last action of the GameCourseUser in the system.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.name
        self::registerFunction(
            'users',
            'name',
            function ($user) {
                return self::basicGetterFunction($user, "name");
            },
            'Returns a string with the name of the GameCourseUser.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.nickname
        self::registerFunction(
            'users',
            'nickname',
            function ($user) {
                $id = self::basicGetterFunction($user, "id")->getValue();
                $nickname = Core::$systemDB->select("game_course_user", ["id" => $id], "nickname");
                return new ValueNode($nickname);
            },
            'Returns a string with the nickname of the GameCourseUser.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.roles returns collection of role names
        self::registerFunction(
            'users',
            'roles',
            function ($user, $course) {
                self::checkArray($user, "object", "roles", "id");
                return self::createNode((new \GameCourse\CourseUser($user["value"]["id"], $course))->getRolesNames(),
                    null,
                    "collection"
                );
            },
            'Returns a collection with the roles of the GameCourseUser in the Course.',
            'collection',
            'integer',
            'object',
            'user', $setup
        );
        //%users.username
        self::registerFunction(
            'users',
            'username',
            function ($user) {
                $id = self::basicGetterFunction($user, "id")->getValue();
                $username = Core::$systemDB->select("auth", ["game_course_user_id" => $id], "username");
                return new ValueNode($username);
            },
            'Returns a string with the username of the GameCourseUser.',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%users.picture
        self::registerFunction(
            'users',
            'picture',
            function ($user) {
                self::checkArray($user, "object", "picture", "id");
                if (file_exists("photos/" . $user["value"]["username"] . ".png")) {
                    return new ValueNode("photos/" . $user["value"]["username"] . ".png");
                }
                return new ValueNode("photos/no-photo.png");
            },
            'Returns the picture of the profile of the GameCourseUser.',
            'picture',
            null,
            'object',
            'user', $setup
        );
        //%user.rank
        self::registerFunction(
            'users',
            'rank',
            function ($user) {
                return self::basicGetterFunction($user, "rank");
            },
            'Returns a string with the position of the user on a collection. To be used with getKNeighbors',
            'string',
            null,
            'object',
            'user', $setup
        );
        //%user.getAllCourses(role)
        self::registerFunction(
            'users',
            'getAllCourses',
            function ($user, string $role = null) {
                self::checkArray($user, "object", "getAllCourses");
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
                return self::createNode($courses, "courses", "collection", $user);
            },
            "Returns a collection of Courses to which the CourseUser is associated. Receives an optional specific role to search for Courses to which the CourseUser is associated with that role.",
            'collection',
            "course",
            'object',
            'user', $setup
        );

        // Functions of course library
        //courses.getAllCourses(isActive,isVisible) returns collection of courses
        self::registerFunction(
            'courses',
            'getAllCourses',
            function (bool $isActive = null, bool $isVisible = null) {
                $where = [];
                if ($isActive !== null)
                    $where["isActive"] = $isActive;
                if ($isVisible !== null)
                    $where["isVisible"] = $isVisible;
                return self::createNode(Core::$systemDB->selectMultiple("course", $where), "courses", "collection");
            },
            "Returns a collection with all the courses in the system. The optional parameters can be used to find courses that specify a given combination of conditions:\nisActive: active or inactive depending whether the course is active.\nisVisible: visible or invisible depending whether the course is visible.",
            'collection',
            "course",
            'library', null, $setup
        );
        //courses.getCourse(id) returns course object
        self::registerFunction(
            'courses',
            'getCourse',
            function (int $id) {
                $course = Core::$systemDB->select("course", ["id" => $id]);
                if (empty($course))
                    throw new \Exception("In function courses.getCourse(...): Coudn't find course with id=" . $id);
                return self::createNode($course, "courses", "object");
            },
            'Returns the object course with the specific id.',
            'object',
            'course',
            'library', null, $setup
        );
        //%course.isActive
        self::registerFunction(
            'courses',
            'isActive',
            function ($course) {
                return self::basicGetterFunction($course, "isActive");
            },
            'Returns a boolean on whether the course is active.',
            'boolean',
            null,
            "object",
            "course", $setup
        );
        //%course.isVisible
        self::registerFunction(
            'courses',
            'isVisible',
            function ($course) {
                return self::basicGetterFunction($course, "isVisible");
            },
            'Returns a boolean on whether the course is visible.',
            'boolean',
            null,
            "object",
            "course", $setup
        );
        //%course.name
        self::registerFunction(
            'courses',
            'name',
            function ($course) {
                return self::basicGetterFunction($course, "name");
            },
            'Returns a string with the name of the course.',
            'string',
            null,
            "object",
            "course", $setup
        );
        //%course.roles   returns collection of roles(which are just strings
        self::registerFunction(
            'courses',
            'roles',
            function ($course) {
                self::checkArray($course, "object", "roles");
                $roles = array_column(Core::$systemDB->selectMultiple("role", ["course" => $course["value"]["id"]], "name"), "name");
                return self::createNode($roles, null, "collection");
            },
            'Returns a collection with all the roles in the course.',
            'collection',
            'string',
            "object",
            "course", $setup
        );

        // Functions of awards library
        //awards.getAllAwards(user,type,moduleInstance,initialdate,finaldate, activeUser, activeItem)
        self::registerFunction(
            'awards',
            'getAllAwards',
            function (int $user = null, string $type = null, string $moduleInstance = null, string $initialDate = null, string $finalDate = null, bool $activeUser = true, bool $activeItem = true) {
                return self::getAwardOrParticipationAux(self::$courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, [], "award", $activeUser, $activeItem);
            },
            "Returns a collection with all the awards in the Course. The optional parameters can be used to find awards that specify a given combination of conditions:\nuser: id of a GameCourseUser.\ntype: Type of the event that led to the award.\nmoduleInstance: Name of an instance of an object from a Module.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.\nactiveUser: return data regarding active users only (true), or regarding all users(false).\nactiveItem: return data regarding active items only (true), or regarding all items (false).",
            'collection',
            "award",
            'library', null, $setup
        );
        //%award.renderPicture(item=(user|type)) returns the img or block ok the award (should be used on text views)
        self::registerFunction(
            'awards',
            'renderPicture',
            function ($award, string $item) {
                self::checkArray($award, "object", "renderPicture()");
                if ($item == "user") {
                    $username = Core::$systemDB->select("auth", ["game_course_user_id" => $award["value"]["user"]], "username");
                    if (empty($username))
                        throw new \Exception("In function renderPicture('user'): couldn't find username.");
                    return new ValueNode("photos/" . $username . ".png");
                } else if ($item == "type") {
                    switch ($award["value"]['type']) {
                        case 'grade':
                            return new ValueNode('modules/awardlist/imgs/quiz.svg');
                        case 'badge':
                            $name = self::getModuleNameOfAward($award);
                            if ($name === null)
                                throw new \Exception("In function renderPicture('type'): couldn't find badge.");
                            $level = substr($award["value"]["description"], -2, 1); //assuming that level are always single digit
                            $imgName = str_replace(' ', '', $name . '-' . $level);
                            return new ValueNode('modules/badges/imgs/' . $imgName . '.png');
                        case 'skill':
                            return new ValueNode('modules/skills/imgs/skills.svg');
                        case 'bonus':
                            return new ValueNode('modules/awardlist/imgs/awards.svg');
                        default:
                            return new ValueNode('modules/awardlist/imgs/quiz.svg');
                    }
                } else
                    throw new \Exception("In function renderPicture(item): item must be 'user' or 'type'");
            },
            "Renders the award picture. The item can only have these 2 values: 'user' or 'type'. If 'user', returns the user picture, if 'type', it is shown the picture related to the type of award.",
            'picture',
            null,
            "object",
            "award", $setup
        );
        //%award.description
        self::registerFunction(
            'awards',
            'description',
            function ($award) {
                return self::basicGetterFunction($award, "description");
            },
            'Returns a picture of the item associated to the award. item can refer to the GameCourseUser that won it ("user") and the type of the award ("type").',
            'string',
            null,
            "object",
            "award", $setup
        );
        //%award.moduleInstance
        self::registerFunction(
            'awards',
            'moduleInstance',
            function ($award) {
                self::checkArray($award, "object", "moduleInstance");
                return new ValueNode(self::getModuleNameOfAward($award));
            },
            'Returns a string with the name of the Module instance that provided the award.',
            'string',
            null,
            "object",
            "award", $setup
        );
        //%award.reward
        self::registerFunction(
            'awards',
            'reward',
            function ($award) {
                return self::basicGetterFunction($award, "reward");
            },
            'Returns a string with the reward provided by the award.',
            'string',
            null,
            "object",
            "award", $setup
        );
        //%award.type
        self::registerFunction(
            'awards',
            'type',
            function ($award) {
                return self::basicGetterFunction($award, "type");
            },
            'Returns a string with the type of the event that provided the award.',
            'string',
            null,
            "object",
            "award", $setup
        );
        //%award.date
        self::registerFunction(
            'awards',
            'date',
            function ($award) {
                return self::getDate($award);
            },
            'Returns a string in DD/MM/YYYY format of the date the award was created.',
            'string',
            null,
            "object",
            "award", $setup
        );
        //%award.user
        self::registerFunction(
            'awards',
            'user',
            function ($award) {
                return self::basicGetterFunction($award, "user");
            },
            'Returns a string with the id of the GameCourseUser that received the award.',
            'string',
            null,
            "object",
            "award", $setup
        );

        // Functions of the participation library
        //participations.getAllParticipations(user,type,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
        self::registerFunction(
            'participations',
            'getAllParticipations',
            function (int $user = null, string $type = null, int $rating = null, int $evaluator = null, string $initialDate = null, string $finalDate = null,  bool $activeUser = true, bool $activeItem = true) {
                $where = [];
                if ($rating !== null) {
                    $where["rating"] = $rating;
                }
                if ($evaluator !== null) {
                    $where["evaluator"] = $evaluator;
                }
                return self::getAwardOrParticipationAux(self::$courseId, $user, $type, null, $initialDate, $finalDate, $where, "participation",  $activeUser, $activeItem);
            },
            "Returns a collection with all the participations in the Course. The optional parameters can be used to find participations that specify a given combination of conditions:\nuser: id of a GameCourseUser that participated.\ntype: Type of participation.\nrating: Rate given to the participation.\nevaluator: id of a GameCourseUser that rated the participation.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.\nactiveUser: return data regarding active users only (true), or regarding all users(false).\nactiveItem: return data regarding active items only (true), or regarding all items (false).",
            'collection',
            'participation',
            'library', null, $setup
        );
        //participations.getParticipations(user,type,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
        self::registerFunction(
            'participations',
            'getParticipations',
            function (int $user = null, string $type = null, int $rating = null, int $evaluator = null, string $initialDate = null, string $finalDate = null, bool $activeUser = true, bool $activeItem = true) {
                $where = [];
                if ($rating !== null) {
                    $where["rating"] = (int) $rating;
                }
                if ($evaluator !== null) {
                    $where["evaluator"] = $evaluator;
                }
                return self::getAwardOrParticipationAux(self::$courseId, $user, $type, null, $initialDate, $finalDate, $where, "participation", $activeUser, $activeItem);
            },
            "Returns a collection with all the participations in the Course. The optional parameters can be used to find participations that specify a given combination of conditions:\nuser: id of a GameCourseUser that participated.\ntype: Type of participation.\nrating: Rate given to the participation.\nevaluator: id of a GameCourseUser that rated the participation.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.",
            'collection',
            'participation',
            'library', null, $setup
        );
        //participations.getParticipationsByDescription(user,type,description,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
        self::registerFunction(
            'participations',
            'getParticipationsByDescription',
            function (int $user = null, string $type = null, string $desc = null, int $rating = null, int $evaluator = null, string $initialDate = null, string $finalDate = null, bool $activeUser = true, bool $activeItem = true) {
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
                return self::getAwardOrParticipationAux(self::$courseId, $user, $type, null, $initialDate, $finalDate, $where, "participation", $activeUser, $activeItem);
            },
            "Returns a collection with all the participations in the Course. The optional parameters can be used to find participations that specify a given combination of conditions:\nuser: id of a GameCourseUser that participated.\ntype: Type of participation.\ndescription: Description of participation.\nrating: Rate given to the participation.\nevaluator: id of a GameCourseUser that rated the participation.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.",
            'collection',
            'participation',
            'library', null, $setup
        );
        //participations.getPeerGrades(user,rating)
        self::registerFunction(
            'participations',
            'getPeerGrades',
            function (int $user = null, int $rating = null, int $evaluator = null, bool $activeUser = true, bool $activeItem = true) {
                $where = [];
                $type = "peergraded post";
                $peerGrades = [];
                if ($evaluator !== null) {
                    $where["evaluator"] = (int) $evaluator;
                }
                if ($rating !== null) {
                    $where["rating"] = (int) $rating;
                }
                $allPeergradedPosts = self::getAwardOrParticipation(self::$courseId, $user, $type, null, null, null, $where, "participation", $activeUser, $activeItem);
                foreach ($allPeergradedPosts as $peergradedPost) {
                    $post = $peergradedPost["post"];
                    // see if there's a corresponding graded post for this peergrade
                    $gradedPost = Core::$systemDB->selectMultiple("participation", ["type" => "graded post", "post" => $post], '*');
                    if (sizeof($gradedPost) > 0) {
                        array_push($peerGrades, $peergradedPost);
                    }
                }
                return self::createNode($peerGrades, "participations", "collection");
            },
            "Returns a collection with all the valid peer graded posts (participations) in this Course. A peergrade is considered valid if the the post it refers to has already been graded by a professor. The optional parameters can be used to find peergraded posts that specify a given combination of conditions:\nuser: id of a GameCourseUser that authored the post being peergraded.\nrating: Rate given to the peergraded post.\nevaluator: id of a GameCourse user that rated/graded the post.",
            'collection',
            'participation',
            'library', null, $setup
        );
        //%participation.date
        self::registerFunction(
            'participations',
            'date',
            function ($participation) {
                return self::getDate($participation);
            },
            'Returns a string in DD/MM/YYYY format of the date of the participation.',
            'string',
            null,
            "object",
            "participation", $setup
        );
        //%participation.description
        self::registerFunction(
            'participations',
            'description',
            function ($participation) {
                return self::basicGetterFunction($participation, "description");
            },
            'Returns a string with the information of the participation.',
            'string',
            null,
            "object",
            "participation", $setup
        );
        //%participation.evaluator
        self::registerFunction(
            'participations',
            'evaluator',
            function ($participation) {
                return self::basicGetterFunction($participation, "evaluator");
            },
            'Returns a string with the id of the user that rated the participation.',
            'string',
            null,
            "object",
            "participation", $setup
        );
        //%participation.post
        self::registerFunction(
            'participations',
            'post',
            function ($participation) {
                return self::basicGetterFunction($participation, "post");
            },
            'Returns a string with the link to the post where the user participated.',
            'string',
            null,
            "object",
            "participation", $setup
        );
        //%participation.rating
        self::registerFunction(
            'participations',
            'rating',
            function ($participation) {
                return self::basicGetterFunction($participation, "rating");
            },
            'Returns a string with the rating of the participation.',
            'string',
            null,
            "object",
            "participation", $setup
        );
        //%participation.type
        self::registerFunction(
            'participations',
            'type',
            function ($participation) {
                return self::basicGetterFunction($participation, "type");
            },
            'Returns a string with the type of the participation.',
            'string',
            null,
            "object",
            "participation", $setup
        );
        //%participation.user
        self::registerFunction(
            'participations',
            'user',
            function ($participation) {
                return self::basicGetterFunction($participation, "user");
            },
            'Returns a string with the id of the user that participated.',
            'string',
            null,
            "object",
            "participation", $setup
        );
        //participations.getLinkViews(user, nameSubstring)
        self::registerFunction(
            'participations',
            'getLinkViews',
            function (int $user, $nameSubstring = null) {
                $table = "participation";

                $where = ["user" => $user, "type" => "url viewed", "course" => self::$courseId];
                if ($nameSubstring == null) {
                    $likeParams = ["description" => "%"];
                }
                else {
                    $likeParams = ["description" => $nameSubstring];
                }

                $participations = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], "description", $likeParams);
                return self::createNode($participations, "participation", "collection");
            },
            "Returns a collection of unique url views. The parameter can be used to find participations for a user:\nuser: id of a GameCourseUser that participated.\nnameSubstring: how to identify the url.Ex:'[Video]%'",
            'collection',
            'participation',
            'library', null, $setup
        );
        //participations.getResourceViews(user)
        self::registerFunction(
            'participations',
            'getResourceViews',

            function (int $user, $nameSubstring = null) {
                $table = "participation";

                $where = ["user" => $user, "type" => "resource view", "course" => self::$courseId];
                if ($nameSubstring == null) $likeParams = ["description" => "%"];
                else $likeParams = ["description" => $nameSubstring];
                $resourceViews = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], "description", $likeParams);

                return self::createNode($resourceViews, "participation", "collection");
            },
            "Returns a collection of unique resource views for Lecture Slides. The parameter can be used to find participations for a user:\nuser: id of a GameCourseUser that participated.\nnameSubstring: how to identify the resource.Ex:'Lecture $ Slides'",
            'collection',
            'participation',
            'library', null, $setup
        );
        //participations.getForumParticipations, forum)
        self::registerFunction(
            'participations',
            'getForumParticipations',

            function (int $user, string $forum, string $thread = null) {
                $table = "participation";

                if ($thread == null) {
                    # if the name of the thread is not relevant
                    # aka, if users are rewarded for creating posts + comments
                    $where = ["user" => $user, "type" => "graded post", "course" => self::$courseId];
                    $like = $forum . ",%";
                    $likeParams = ["description" => $like];

                    $forumParticipation = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], null, $likeParams);
                } else {
                    # Name of thread is important for the badge
                    $like = $forum . ", Re: " . $thread . "%";
                    $where = ["user" => $user, "type" => "graded post", "course" => self::$courseId];
                    $likeParams = ["description" => $like];
                    $forumParticipation = Core::$systemDB->selectMultiple($table, $where, '*', null, [], [], null, $likeParams);
                }
                return self::createNode($forumParticipation, "participations", "collection");
            },
            "Returns a collection with all the participations in a specific forum of the Course. The  parameter can be used to find participations for a user or forum:\nuser: id of a GameCourseUser that participated.\n
                forum: name of a moodle forum to filter participations by.",
            'collection',
            'participation',
            'library', null, $setup
        );
        //participations.getSkillParticipations(user, skill)
        self::registerFunction(
            'participations',
            'getSkillParticipations',

            function (int $user, string $skill) {
                // get users who are evaluators, aka, users who have the role "Teacher"
                // select user_role.id from user_role left join role on user_role.role=role.id where role.name = "Teacher" and role.course = 1;
                $table = "user_role left join role on user_role.role=role.id";
                $columns = "user_role.id";
                $where = ["role.name" => "Teacher", "role.course" => self::$courseId];

                $evaluators = Core::$systemDB->selectMultiple($table, $where, $columns, null, [], [], null, null);
                $teachers = [];
                foreach ($evaluators as $evaluator) {
                    array_push($teachers, $evaluator["id"]);
                }
                $table = "participation";
                $description = "Skill Tree, Re: " . $skill;
                $orderby = "rating desc";
                $where = ["user" => $user, "type" => "graded post", "description" => $description, "course" => self::$courseId];
                $forumParticipation = Core::$systemDB->selectMultiple($table, $where, '*', $orderby, [], [], null, null);
                $filteredParticipations = array();

                foreach ($forumParticipation as $participation) {
                    if (in_array($participation["evaluator"], $teachers)) {
                        array_push($filteredParticipations, $participation);
                        break;
                    }
                }
                return self::createNode($filteredParticipations, "participations", "collection");
            },
            "Returns a collection with all the skill tree participations for a user in the forums. The parameter can be used to find participations for a user:\nuser: id of a GameCourseUser that participated. \nuser: id of a GameCourseUser that participated.",
            'collection',
            'participation',
            'library', null, $setup
        );
        //participations.getRankings(user, type)
        self::registerFunction(
            'participations',
            'getRankings',

            function (int $user, string $type) {
                $table = "participation";
                $where = ["user" => $user, "type" => $type, "course" => self::$courseId];
                $forumParticipation = Core::$systemDB->selectMultiple($table, $where, 'description', null, [], [], null, null);

                $ranking = 0;
                if (count($forumParticipation) > 0) {
                    $ranking = 4 - intval($forumParticipation[0]['description']);
                }
                return self::createNode($ranking, "participations", "object");
            },
            "Returns rankings of student awarded rewards.",
            'collection',
            'participation',
            'library', null, $setup
        );


        /*** ----------------------------------------------- ***/
        /*** ------------------- Modules ------------------- ***/
        /*** ----------------------------------------------- ***/

        foreach (ModuleLoader::getModules() as $moduleInfo) {
            $module = $moduleInfo['factory']();
            ModuleLoader::setModuleInfo($module, $moduleInfo, Course::getCourse(self::$courseId, false));
            $module->initDictionary();
        }
    }



    /*** ---------------------------------------------------- ***/
    /*** -------------------- Registering ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Registers a new view type into the system.
     *
     * @param string $type
     * @param string $description
     * @param $breakFunc
     * @param $putTogetherFunc
     * @param $parseFunc
     * @param $processFunc
     * @param bool $init
     */
    public static function registerViewType(string $type, string $description, $parseFunc, $processFunc, bool $setup = false)
    {
        if ($setup) {
            if (Core::$systemDB->select("dictionary_view_type", ["name" => $type])) {
                Core::$systemDB->update("dictionary_view_type", ["name" => $type, "description" => $description], ["name" => $type]);
            } else {
                Core::$systemDB->insert("dictionary_view_type", ["name" => $type, "description" => $description]);
            }
        }
        self::$viewTypes[$type] = array($parseFunc, $processFunc);
    }

    /**
     * Registers a library into the system.
     *
     * @param string|null $moduleId
     * @param string $libraryName
     * @param string $description
     */
    public static function registerLibrary(?string $moduleId, string $libraryName, string $description)
    {
        $query = ["name" => $libraryName];
        if ($moduleId) $query = array_merge($query, ["moduleId" => $moduleId]);

        $data = [
            "moduleId" => $moduleId,
            "name" => $libraryName,
            "description" => $description
        ];

        if (!empty(Core::$systemDB->select("dictionary_library", $query)))
            Core::$systemDB->update("dictionary_library", $data, $query);
        else
            Core::$systemDB->insert("dictionary_library", $data);
    }

    /**
     * Unregisters a library from the system.
     *
     * @param string $moduleId
     * @param string $libraryName
     */
    public static function unregisterLibrary(string $moduleId, string $libraryName)
    {
        Core::$systemDB->delete("dictionary_library", ["moduleId" => $moduleId, "name" => $libraryName]);
    }

    /**
     * Registers a variable into the system.
     *
     * @param string $name
     * @param string $returnType
     * @param string|null $libraryName
     * @param string|null $description
     */
    public static function registerVariable(string $name, string $returnType, string $libraryName = null, string $description = null)
    {
        if ($libraryName) {
            $libraryId = Core::$systemDB->select("dictionary_library", ["name" => $libraryName], "id");
            if (!$libraryId)
                API::error('Library named ' . $libraryName . ' not found.');
        } else $libraryId = null;

        if (!Core::$systemDB->select("dictionary_variable", ["name" => $name])) {
            Core::$systemDB->insert(
                "dictionary_variable",
                array(
                    "name" => $name,
                    "libraryId" => $libraryId,
                    "returnType" => $returnType,
                    "description" => $description
                )
            );
        } else {
            Core::$systemDB->update(
                "dictionary_variable",
                array(
                    "name" => $name,
                    "libraryId" => $libraryId,
                    "returnType" => $returnType,
                    "description" => $description
                ),
                array(
                    "name" => $name
                )

            );
        }
    }

    /**
     * Unregisters a variable from the system.
     *
     * @param string $name
     */
    public static function unregisterVariable(string $name)
    {
        Core::$systemDB->delete("dictionary_variable", ["name" => $name]);
    }

    /**
     * Registers a function into the system.
     *
     * @param string|null $funcLib
     * @param string $funcName
     * @param $processFunc
     * @param string $description
     * @param string|null $returnType
     * @param string|null $returnName
     * @param string $refersToType
     * @param string|null $refersToName
     * @param bool $init
     * @throws ReflectionException
     */
    public static function registerFunction(?string $funcLib, string $funcName, $processFunc, string $description, ?string $returnType, string $returnName = null, string $refersToType = "object", string $refersToName = null, bool $setup = false)
    {
        if ($funcLib) {
            $libraryId = Core::$systemDB->select("dictionary_library", ["name" => $funcLib], "id");
            if (!$libraryId)
                API::error('Library named ' . $funcName . ' not found.');
        } else $libraryId = null;

        if ($processFunc) {
            $processFuncArr = (array)$processFunc;
            $reflection = new ReflectionFunction($processFuncArr[0]);
            $arg = null;
            $arguments  = $reflection->getParameters();
            if ($arguments) {
                $arg = [];
                $i = -1;
                foreach ($arguments as $argument) {
                    $i++;
                    if ($i == 0 && ($refersToType == "object" || $funcLib == null)) {
                        continue;
                    }
                    $optional = $argument->isOptional() ? "1" : "0";
                    $tempArr = [];
                    $tempArr["name"] = $argument->getName();
                    $type = (string)$argument->getType();
                    if ($type == "int") {
                        $tempArr["type"] = "integer";
                    } elseif ($type == "bool") {
                        $tempArr["type"] = "boolean";
                    } else {
                        $tempArr["type"] = $type;
                    }
                    $tempArr["optional"] = $optional;
                    array_push($arg, $tempArr);
                }
                if (empty($arg)) {
                    $arg = null;
                } else {
                    $arg = json_encode($arg);
                }
            }
        }

        if ($setup) {
            if (Core::$systemDB->select("dictionary_function", array("keyword" => $funcName))) {
                if ($funcLib) {
                    if (Core::$systemDB->select("dictionary_function", array("libraryId" => $libraryId, "keyword" => $funcName))) {
                        Core::$systemDB->update(
                            "dictionary_function",
                            array(
                                "libraryId" => $libraryId,
                                "returnType" => $returnType,
                                "returnName" => $returnName,
                                "refersToType" => $refersToType,
                                "refersToName" => $refersToName,
                                "keyword" => $funcName,
                                "args" => $arg,
                                "description" => $description
                            ),
                            array(
                                "libraryId" => $libraryId,
                                "keyword" => $funcName,

                            )
                        );
                    } else { //caso queira registar uma função com a mesma keyword, mas numa library diferente
                        Core::$systemDB->insert(
                            "dictionary_function",
                            array(
                                "libraryId" => $libraryId,
                                "returnType" => $returnType,
                                "returnName" => $returnName,
                                "refersToType" => $refersToType,
                                "refersToName" => $refersToName,
                                "keyword" => $funcName,
                                "args" => $arg,
                                "description" => $description
                            )
                        );
                    }
                } else {
                    if (!Core::$systemDB->select("dictionary_function", array("keyword" => $funcName, "libraryId" => null, "refersToType" => $refersToType, "refersToName" => $refersToName))) {
                        Core::$systemDB->insert(
                            "dictionary_function",
                            array(
                                "libraryId" => $libraryId,
                                "returnType" => $returnType,
                                "returnName" => $returnName,
                                "refersToType" => $refersToType,
                                "refersToName" => $refersToName,
                                "keyword" => $funcName,
                                "args" => $arg,
                                "description" => $description
                            )
                        );
                    } else {
                        Core::$systemDB->update(
                            "dictionary_function",
                            array(
                                "libraryId" => $libraryId,
                                "returnType" => $returnType,
                                "returnName" => $returnName,
                                "refersToType" => $refersToType,
                                "refersToName" => $refersToName,
                                "keyword" => $funcName,
                                "args" => $arg,
                                "description" => $description
                            ),
                            array(
                                "libraryId" => $libraryId,
                                "keyword" => $funcName,

                            )
                        );
                    }
                }
            } else {
                Core::$systemDB->insert("dictionary_function", [
                    "libraryId" => $libraryId,
                    "returnType" => $returnType,
                    "returnName" => $returnName,
                    "refersToType" => $refersToType,
                    "refersToName" => $refersToName,
                    "keyword" => $funcName,
                    "args" => $arg,
                    "description" => $description
                ]);
            }
        }

        $functionId = Core::$systemDB->select("dictionary_function", ["libraryId" => $libraryId, "keyword" => $funcName], "id");
        self::$viewFunctions[$functionId] = $processFunc;
    }

    /**
     * Unregisters a function from a system.
     *
     * @param string $funcLib
     * @param string $funcName
     */
    public static function unregisterFunction(string $funcLib, string $funcName)
    {
        if ($funcLib) {
            $libraryId = Core::$systemDB->select("dictionary_library", ["name" => $funcLib], "id");
            if (!$libraryId) {
                API::error('Library named ' . $funcName . ' not found.');
            }
        } else {
            $libraryId = null;
        }
        Core::$systemDB->delete("dictionary_function", array("libraryId" => $libraryId, "keyword" => $funcName));
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Utilities -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Return value node of the field of the object.
     *
     * @param $object
     * @param $field
     * @return ValueNode
     * @throws \Exception
     */
    public static function basicGetterFunction($object, $field): ValueNode
    {
        self::checkArray($object, "object", $field, $field);
        return new ValueNode($object["value"][$field]);
    }

    /**
     * Checks if object/collection array is correctly formatted.
     * May also check if a parameter belongs to an object.
     *
     * @param $array
     * @param $type
     * @param $functionName
     * @param null $parameter
     * @throws \Exception
     */
    public static function checkArray($array, $type, $functionName, $parameter = null)
    {
        if (!is_array($array) || !array_key_exists("type", $array) || $array["type"] != $type)
            API::error("The function '." . $functionName . "' must be called on " . $type);

        if ($parameter !== null && $type == "object" && !array_key_exists($parameter, $array["value"]))
            API::error("In function '." . $functionName . "': the object does not contain " . $parameter);
    }

    /**
     * Create value node of an object or collection.
     *
     * @param $value
     * @param null $lib
     * @param string $type
     * @param null $parent
     * @return ValueNode
     */
    public static function createNode($value, $lib = null, string $type = "object", $parent = null): ValueNode
    {
        if ($type == "collection") {
            foreach ($value as &$v) {
                if ($parent !== null)
                    $v["parent"] = $parent;
                if (is_array($v) && ($lib !== null || !array_key_exists("libraryOfVariable", $v)))
                    $v["libraryOfVariable"] = $lib;
            }
        } else if (is_array($value) && ($lib !== null || !array_key_exists("libraryOfVariable", $value))) {
            $value["libraryOfVariable"] = $lib;
        }
        return new ValueNode(["type" => $type, "value" => $value]);
    }

    private static function evaluateKey(&$key, &$collection, $courseId, $i = 0)
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
                    'item' => Dictionary::createNode($object, $object["libraryOfVariable"])->getValue(),
                    'index' => $i
                );
                $visitor = new EvaluateVisitor($viewParams);
                $value = $key->accept($visitor)->getValue();

                $object["sortVariable" . $i] = $value;
            }
            $key = "sortVariable" . $i;
        }
    }

    /**
     * Conditions for the filter function.
     *
     * @param $a
     * @param $b
     * @param $op
     * @return bool|void
     */
    private static function evalCondition($a, $b, $op)
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

    /**
     * Get award or participations from database, moduleInstance can be name or id.
     *
     * @param $courseId
     * @param $user
     * @param $type
     * @param null $moduleInstance
     * @param null $initialDate
     * @param null $finalDate
     * @param array $where
     * @param string $object
     * @param bool $activeUser
     * @param bool $activeItem
     * @return mixed
     */
    public static function getAwardOrParticipation($courseId, $user, $type, $moduleInstance = null, $initialDate = null, $finalDate = null, array $where = [], string $object = "award", bool $activeUser = true, bool $activeItem = true)
    {
        if ($user !== null) {
            $where["user"] = is_array($user) ? $user["value"]["id"] : $user;
        }
        //expected date format DD/MM/YYY needs to be converted to YYYY-MM-DD
        $whereDate = [];
        if ($initialDate !== null) {
            $date = implode("-", array_reverse(explode("/", $initialDate)));
            array_push($whereDate, ["date", ">", $date]);
        }
        if ($finalDate !== null) {
            //tecnically the final date on the results will be the day before the one given
            //because the timestamp is of that day at midnigth
            $date = implode("-", array_reverse(explode("/", $finalDate)));
            array_push($whereDate, ["date", "<", $date]);
        }

        if ($activeUser) {
            $where["cu.isActive"] = true;
        }

        if ($type !== null) {
            $where["type"] = $type;
            //should only use module instance if the type is specified (so we know if we should use skils or badges)
            if ($moduleInstance !== null && $object == "award" && ($type == "badge" || $type == "skill")) {
                if (is_numeric($moduleInstance)) {
                    $where["moduleInstance"] = $moduleInstance;
                } else {
                    $where["name"] = $moduleInstance;
                }

                if ($activeItem) {
                    $where["m.isActive"] = true;
                }

                $table = $object . " a join " . $type . " m on moduleInstance=m.id join course_user cu on cu.id=a.user and cu.course = a.course";
                $field = "a.*,m.name";
            } else {
                $field = "a.*";
                $table = $object . " a join course_user cu on cu.id=a.user and cu.course = a.course";
            }
        } else {
            $field = "*";
            $table = $object . " a join course_user cu on cu.id=a.user and cu.course = a.course";
        }

        $where["a.course"] = $courseId;
        return Core::$systemDB->selectMultiple($table, $where, $field, null, [], $whereDate);
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
    private static function getAwardOrParticipationAux($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, $where = [], $object = "award", $activeUser = true, $activeItem = true)
    {
        $awardParticipation = self::getAwardOrParticipation($courseId, $user, $type, $moduleInstance, $initialDate, $finalDate, $where, $object, $activeUser, $activeItem);
        return self::createNode($awardParticipation, $object . "s", "collection");
    }

    /**
     * Renders template view and returns it inside a function call for views.directive.js which deals w events
     *
     * @param $templateName
     * @param $params
     * @param $funcName
     * @param $course
     * @param $user
     * @return ValueNode
     * @throws \Exception
     */
    private static function popUpOrToolTip($templateName, $params, $funcName, $course, $user)
    {
        // TODO: needs refactoring
//        $template = $this->getTemplate(null, $templateName);
//        if ($user != null) { //rendering a user view
//            $userId = $this->getUserId($user);
//            $params["user"] = $userId;
//        }
//        $userView = ViewHandler::handle($template, $course, $params);
//        $encodedView = json_encode($userView);
//        if (strlen($encodedView) > 100000) //preventing the use of tooltips with big templates
//            throw new \Exception("Tooltips and PopUps can only be used with smaller templates, '" . $templateName . "' is too big.");
//        return new ValueNode($funcName . "('" . $encodedView . "')");
    }

    /**
     * Get module name of award.
     *
     * @param $object
     * @return mixed|null
     */
    private static function getModuleNameOfAward($object)
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
     * Gets timestamps and converts it to DD/MM/YYYY
     *
     * @param $object
     * @return ValueNode
     * @throws \Exception
     */
    private static function getDate($object): ValueNode
    {
        Dictionary::checkArray($object, "object", "date");
        $date = implode("/", array_reverse(explode("-", explode(" ", $object["value"]["date"])[0])));
        return new ValueNode($date);
    }

}