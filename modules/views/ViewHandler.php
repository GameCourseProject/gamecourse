<?php

namespace Modules\Views;

use ArrayObject;
use Modules\Views\Expression\EvaluateVisitor;
use Modules\Views\Expression\ExpressionEvaluatorBase;
use Modules\Views\Expression\ValueNode;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\API;
use GameCourse\ModuleLoader;

class ViewHandler
{
    private $viewsModule;
    //private $registeredPages = array(); //this was used when all the pages where created by templates
    //it could still be usefull if we wanted to know what pages where create by templates  
    private $registeredFunctions = array();
    private $registeredPartTypes = array();

    public function parse($exp)
    {
        static $parser;
        if ($parser == null) {
            $parser = new ExpressionEvaluatorBase();
        }
        if (trim($exp) == '') {
            return new ValueNode('');
        }
        return $parser->parse($exp);
    }

    public function parseSelf(&$exp)
    {
        $exp = $this->parse($exp);
    }

    public function __construct($viewsModule)
    {
        $this->viewsModule = $viewsModule;
    }

    public function makeCleanViewCopy($viewPart)
    {
        $copy = $viewPart;
        unset($copy["children"]);
        unset($copy["id"]);
        unset($copy["parameters"]);
        unset($copy["edit"]);
        unset($copy["header"]);
        unset($copy["rows"]);
        unset($copy["headerRows"]);
        unset($copy["columns"]);
        unset($copy["templateId"]);
        unset($copy["aspectId"]);
        if ($copy["partType"] == "chart") {
            $copy["value"] = $copy["chartType"];
            unset($copy["chartType"]);
            $copy["info"] = json_encode($copy["info"]);
        }
        if (array_key_exists("variables", $copy))
            $copy["variables"] = json_encode($copy["variables"]);
        if (array_key_exists("events", $copy))
            $copy["events"] = json_encode($copy["events"]);
        return $copy;
    }
    //receives view and updates the DB with its info, propagates changes in the main view to all its children
    //$basicUpdate -> u only update basic view atributes(ignores view parameters and deletion of viewparts), used for change in aspectclass
    public function updateViewAndChildren($viewPart, $basicUpdate = false, $ignoreIds = false, &$partsInDB = null)
    {
        if ($viewPart["partType"] != "block" ||  $viewPart["parent"] != null) {
            //insert/update views
            $copy = $this->makeCleanViewCopy($viewPart);
            if (array_key_exists("id", $viewPart) && !$ignoreIds) { //already in DB, may need update

                Core::$systemDB->update("view", $copy, ["id" => $viewPart["id"]]);
                if (!$basicUpdate) {
                    unset($partsInDB[$viewPart["id"]]);
                }
            } else { //not in DB, insert it
                Core::$systemDB->insert("view", $copy);
                $viewPart["id"] = Core::$systemDB->getLastId();
                if ($viewPart["partType"] == "templateRef") {
                    Core::$systemDB->insert("view_template", ["viewId" => $viewPart["id"], "templateId" => $viewPart["templateId"]]);
                }
            }
        }
        if ($viewPart["partType"] == "table") {
            foreach ($viewPart["headerRows"] as $headRow) {
                $rowPart = $headRow;
                $rowPart["partType"] = "headerRow";
                foreach ($headRow["values"] as $rowElement) {
                    $rowPart["children"][] = $rowElement["value"];
                }
                unset($rowPart["values"]);
                $viewPart["children"][] = $rowPart;
            }
            foreach ($viewPart["rows"] as $row) {
                $rowPart = $row;
                $rowPart["partType"] = "row";
                foreach ($row["values"] as $rowElement) {
                    $rowPart["children"][] = $rowElement["value"];
                }
                unset($rowPart["values"]);
                $viewPart["children"][] = $rowPart;
            }
            unset($viewPart["rows"]);
            unset($viewPart["headerRows"]);
        }
        if ($viewPart["partType"] == "templateRef" && !$basicUpdate) { //update orignial template of the reference
            $type = $this->getRoleType($viewPart["role"]); //type of viewPart (templateRef)
            $aspects = $this->getAspects($viewPart["aspectId"]);
            $templateType = $this->getRoleType($aspects[0]["role"]); //type of template
            //if view role is a double role, get second part, if its special.own set to default
            if ($type == "ROLE_INTERACTION" && $templateType == "ROLE_SINGLE") {
                $viewPart["role"] = explode('>', $viewPart["role"])[1];
                if ($viewPart["role"] == "special.Own")
                    $viewPart["role"] = "role.Default";
            } else if ($templateType == "ROLE_INTERACTION" && $type == "ROLE_SINGLE") { //if the template need double role set 1st to default
                $viewPart["role"] = "role.Default>" . $viewPart["role"];
            }
            //check if aspect exists
            $foundAspect = null;
            foreach ($aspects as $asp) {
                if ($asp["role"] == $viewPart["role"]) {
                    $foundAspect = $asp;
                    break;
                }
            }
            $aspectClass = $aspects[0]["aspectClass"];

            if ($foundAspect === null) { //new aspect, add it
                if ($templateType == "ROLE_INTERACTION") {
                    $roles = explode('>', $viewPart["role"]);
                    $roleInfo = ["roleOne" => $roles[0], "roleTwo" => $roles[1]];
                } else {
                    $roleInfo = ["roleOne" => $viewPart["role"]];
                }
                $this->createAspect($templateType, $viewPart["aspectId"], $this->viewsModule->getParent(), $roleInfo);
            } else { //aspect exists, update its contents
                if (!$ignoreIds && !$basicUpdate) {
                    $viewPart["id"] = $foundAspect["id"];
                    $viewPart["partType"] = "block";
                    $viewPart["parent"] = null;
                    //when there is only one role created, aspect is null
                    if ($aspectClass == null) {
                        //add new aspect
                        Core::$systemDB->insert("aspect_class");
                        $aspectClass = Core::$systemDB->getLastId();

                        //update view and respective children with aspectClass
                        Core::$systemDB->update("view", ["aspectClass" => $aspectClass], ["id" => $foundAspect["id"]]);
                        Core::$systemDB->update("view", ["aspectClass" => $aspectClass], ["parent" => $foundAspect["id"]]);
                    }
                    $viewPart["aspectClass"] = $aspectClass;
                    $this->updateViewAndChildren($viewPart);
                }
            }
            return;
        }
        if (array_key_exists("children", $viewPart)) {
            $children = null;
            if (!$basicUpdate) {
                //all the children of the view except headers which are dealt with later
                $children = Core::$systemDB->selectMultiple("view", ["parent" => $viewPart["id"]], "*", null, [["partType", "header"]]);
                $children = array_combine(array_column($children, "id"), $children);
            }

            foreach ($viewPart["children"] as $key => &$child) {
                $child["role"] = $viewPart["role"];
                $child["aspectClass"] = $viewPart["aspectClass"];
                $child["parent"] = $viewPart["id"];
                $child["viewIndex"] = $key;
                $this->updateViewAndChildren($child, $basicUpdate, $ignoreIds, $children);
            }
            if (!$basicUpdate) {
                foreach ($children as $deleted) {
                    Core::$systemDB->delete("view", ["id" => $deleted["id"]]);
                }
            }
        }
        if ($viewPart["partType"] == "block") { //deal with header of block
            $header = Core::$systemDB->select("view", ["parent" => $viewPart["id"], "partType" => "header"], "id");
            if (array_key_exists("header", $viewPart)) { //if there is a header in the updated version
                if (!$basicUpdate && empty($header)) { //insert (header is not in DB)
                    Core::$systemDB->insert("view", [
                        "parent" => $viewPart["id"],
                        "partType" => "header", "role" => $viewPart["role"], "aspectClass" => $viewPart["aspectClass"]
                    ]);
                    $headerId = Core::$systemDB->getLastId();

                    $headerPart = [
                        "role" => $viewPart["role"], "parent" => $headerId,
                        "aspectClass" => $viewPart["aspectClass"], "viewIndex" => 0
                    ];
                    $image = array_merge($viewPart["header"]["image"], $headerPart);
                    unset($image["id"]);
               
                    $this->updateViewAndChildren($image, $basicUpdate, $ignoreIds);
                    $headerPart["viewIndex"] = 1;
                    $text = array_merge($viewPart["header"]["title"], $headerPart);
                    unset($text["id"]);

                    $this->updateViewAndChildren($text, $basicUpdate, $ignoreIds);
                } else if (!empty($header)) { //update (header is in DB)
                    //in most cases just updating parameters
                    $headerParts = Core::$systemDB->selectMultiple("view", ["parent" => $header]);
                    foreach ($headerParts as $part) {
                        if ($basicUpdate) {
                            Core::$systemDB->update(
                                "view",
                                ["role" => $viewPart["role"], "aspectClass" => $viewPart["aspectClass"]],
                                ["id" => $part["id"]]
                            );
                        } else {
                            if ($part["partType"] == "text")
                                $type = "title";
                            else
                                $type = "image";
                            $part = array_merge($part, $this->makeCleanViewCopy($viewPart["header"][$type]));
                            $partId = $part["id"];
                            unset($part["id"]);
                            Core::$systemDB->update("view", $part, ["id" => $partId]);
                        }
                    }
                }
            } else if (!empty($header) && !$basicUpdate) { //delete header in db
                Core::$systemDB->delete("view", ["parent" => $viewPart["id"], "partType" => "header"]);
            }
            if ($basicUpdate && !empty($header)) { //ToDo
                Core::$systemDB->update(
                    "view",
                    ["role" => $viewPart["role"], "aspectClass" => $viewPart["aspectClass"]],
                    ["id" => $header]
                );
            }
        }
    }
    //Find parent roles given a role like 'role.1'
    private function findParents($course, $roleToFind)
    {
        $finalParents = array();
        $parents = array();
        $course->goThroughRoles(function ($role, $hasChildren, $cont, &$parents) use ($roleToFind, &$finalParents) {
            if ('role.' . $role["id"] == $roleToFind) {
                $finalParents = $parents;
                return;
            }
            $parentCopy = $parents;
            $parentCopy[] = 'role.' . $role["id"];
            $cont($parentCopy);
        }, $parents);
        return array_merge(array('role.Default'), $finalParents);
    }

    //gets views of the class of $anAspectId, with the last matching role of $rolesWanted 
    private function findViews($anAspectId, $type, $rolesWanted)
    {
        $aspects = $this->getAspects($anAspectId);

        $viewRoles = array_column($aspects, 'role');
        $viewsFound = array();
        if ($type == "ROLE_INTERACTION") {
            $rolesFound = [];
            foreach ($viewRoles as $dualRole) {
                $role = substr($dualRole, 0, strpos($dualRole, '>'));
                if (in_array($role, $rolesWanted) && !in_array($role, $rolesFound)) {
                    $viewsFound[] = $this->getViewWithParts($anAspectId, $dualRole);
                    $rolesFound[] = $dualRole;
                }
            }
        } else {
            foreach (array_reverse($rolesWanted) as $role) {
                if (in_array($role, $viewRoles)) {
                    return [$this->getViewWithParts($anAspectId, $role)];
                }
            }
            return null;
        }
        return $viewsFound;
    }
    //gets the closest aspectview to the current
    public function getClosestAspect($course, $type, $roleOne, $viewId, $roleTwo = null)
    {
        $finalParents = $this->findParents($course, $roleOne); //parent roles of roleone
        //get the aspect view from which we will copy the contents
        $parentViews = $this->findViews($viewId, $type, array_merge($finalParents, [$roleOne]));
        if ($type == "ROLE_INTERACTION") {
            $parentsTwo = array_merge($this->findParents($course, $roleTwo), [$roleTwo]);
            $finalViews = [];
            foreach ($parentViews as $viewRoleOne) {
                $viewRoles = explode(">", $viewRoleOne['role']);
                $viewRoleTwo = $viewRoles[1];
                foreach ($parentsTwo as $parentRole) {
                    if ($parentRole == $viewRoleTwo) {
                        $finalViews[] = $viewRoleOne;
                    }
                }
            }
            $parentViews = $finalViews;
        }
        return end($parentViews);
    }
    public function createAspect($roleType, $aspectId, $course, $roleInfo)
    {
        if ($roleType == "ROLE_SINGLE") {
            $role = $roleInfo['roleOne'];
            $parentView = $this->getClosestAspect($course, $roleType, $roleInfo['roleOne'], $aspectId);
        } else if ($roleType == "ROLE_INTERACTION") {
            $role = $roleInfo['roleOne'] . '>' . $roleInfo['roleTwo'];
            $parentView = $this->getClosestAspect($course, $roleType, $roleInfo['roleOne'], $aspectId, $roleInfo['roleTwo']);
        }

        if ($parentView != null) {
            if ($parentView["aspectClass"] == null) {
                Core::$systemDB->insert("aspect_class");
                $aspectClass = Core::$systemDB->getLastId();
                $parentView["aspectClass"] = $aspectClass;
                Core::$systemDB->update("view", ["aspectClass" => $aspectClass], ["id" => $parentView["id"]]);
                //update aspect class of parent view
                $this->updateViewAndChildren($parentView, true);
            }
            $newView = ["role" => $role, "partType" => "block", "parent" => null, "aspectClass" => $parentView["aspectClass"]];
            Core::$systemDB->insert("view", $newView);
            $newView["id"] = Core::$systemDB->getLastId();
            $newView = array_merge($parentView, $newView);
            //add new aspect to db
            $this->updateViewAndChildren($newView, false, true);
        } else {
            $newView = [
                "role" => $role, "partType" => "block", "parent" => null,
                "aspectClass" => null, "parent" => null, "viewIndex" => null
            ];
            $this->updateViewAndChildren($newView);
        }
    }

    //get table data
    function lookAtTable(&$organizedView)
    {
        if ($organizedView["partType"] == "table" && sizeof($organizedView["children"]) > 0) {
            $organizedView["rows"] = [];
            $organizedView["headerRows"] = [];
            foreach ($organizedView["children"] as $row) {
                $rowType = $row["partType"] . "s";
                $values = [];
                foreach ($row["children"] as $cell) {
                    $values[] = ["value" => $cell];
                }
                unset($row["children"]);

                $organizedView[$rowType][] = array_merge($row, ["values" => $values]);
            }
            for ($i = 0; $i < (sizeof($organizedView["rows"]) + sizeof($organizedView["headerRows"])); $i++) {
                unset($organizedView["children"][$i]);
            }
            $organizedView["columns"] = sizeof($organizedView["rows"][0]["values"]);
        }
    }
    //check if view is a block w header and get it's data
    function lookAtHeader(&$organizedView)
    {
        if ($organizedView["partType"] == "block" && $organizedView["parent"] != null && sizeof($organizedView["children"]) > 0) {
            if ($organizedView["children"][0]["partType"] == "header") {
                $organizedView["header"] = [];
                $organizedView["header"]["image"] = $organizedView["children"][0]["children"][0];
                $organizedView["header"]["title"] = $organizedView["children"][0]["children"][1];
                unset($organizedView["children"][0]);
                $organizedView["children"] = array_values($organizedView["children"]);
            }
        }
    }
    //get data of view parameter
    function lookAtParameter($child, &$organizedView)
    {
        if (array_key_exists("variables", $child)) {
            $child["variables"] = json_decode($child["variables"], true);
        }
        if ($child["partType"] == "chart") {
            if (array_key_exists("info", $child)) {
                $child["info"] = json_decode($child["info"], true);
            }

            $child["chartType"] = $child["value"];
        }
        if (array_key_exists("events", $child)) {
            $child["events"] = json_decode($child["events"], true);
        }

        $organizedView["children"][] = array_merge($child, ["children" => []]);
    }
    //receives aspect (and possibly role), contructs array of view with all its contents
    public function getViewContents($anAspect, $role = null)
    {
        if ($anAspect["aspectClass"] == null) { //view as only 1 aspect
            //this has a lot of queries (select all children, each of their params and children)
            //this happens because null aspectClass when there's only one aspect
            $organizedView = $anAspect;
            $organizedView["children"] = [];
            $this->lookAtChildrenWQueries($anAspect["id"], $organizedView);
            $aspectsViews = [$organizedView];
        } else { //multiple aspects exist
            $where = ["aspectClass" => $anAspect["aspectClass"]];
            if ($role)
                $where["role"] = $role;
            //gets all the views of the aspect (using aspectclass and role)
            $viewsOfAspect = Core::$systemDB->selectMultiple(
                "aspect_class natural join view",
                $where,
                "*",
                "parent,viewIndex,id"
            );

            $aspectsViews = [];
            $parts = [];
            foreach ($viewsOfAspect as $v) {
                if ($v['partType'] == "block" && $v["parent"] == null)
                    $aspectsViews[] = $v;
                else
                    $parts[$v['parent']][] = $v;
            }
            foreach ($aspectsViews as &$organizedView) {
                $organizedView["children"] = [];
                if (sizeof($parts) > 0) {
                    $this->lookAtChildren($organizedView['id'], $parts, $organizedView);
                }
            }
        }
        if ($role)
            return $aspectsViews[0];
         else 
            return $aspectsViews;
    }
    //gets contents of template referenced by templateref
    public function lookAtTemplateReference($templatRef, &$organizedView)
    {
        //gettemplate and its aspect
        $aspect = Core::$systemDB->select(
            "view_template vt join template on templateId=id join view_template vt2 on id=vt2.templateId join view v on v.id=vt2.viewId",
            ["vt.viewId" => $templatRef["id"], "v.partType" => "block", "v.parent" => null],
            "v.id,vt.templateId,roleType,aspectClass,role,partType"
        );

        //deal with roles of different types
        $roleType = $this->getRoleType($templatRef["role"]);
        if ($roleType == "ROLE_INTERACTION" && $aspect["roleType"] == "ROLE_SINGLE") {
            $role = explode(">", $templatRef["role"])[1];
        } else if ($roleType == "ROLE_SINGLE" && $aspect["roleType"] == "ROLE_INTERACTION") {
            $role = "role.Default>" . $templatRef["role"];
        } else $role = $templatRef["role"];

        //$this->getViewContents($aspect, $role);
        $aspectView = $this->getViewContents($aspect, $role);
        $organizedView["children"] = $aspectView["children"];
        $organizedView["aspectId"] = $aspect["id"];
        $organizedView["templateId"] = $aspect["templateId"];
    }
    //Go through views and update array with parameters info (it receives arrays with all the data, doesnt do more queries)
    public function lookAtChildren($parent, $children, &$organizedView)
    {
        if (!array_key_exists($parent, $children))
            return;

        for ($i = 0; $i < count($children[$parent]); $i++) {
            $child = $children[$parent][$i];
            $this->lookAtParameter($child, $organizedView);
            $this->lookAtChildren($child['id'], $children, $organizedView["children"][$i]);
            if ($child["partType"] == "templateRef") {
                $this->lookAtTemplateReference($child, $organizedView["children"][$i]);
            }
        }
        $this->lookAtHeader($organizedView);
        $this->lookAtTable($organizedView);
    }

    //Go through views and update array with parameters info (receives parents and uses queries to get the rest)
    public function lookAtChildrenWQueries($parentId, &$organizedView)
    {
        $children = Core::$systemDB->selectMultiple("view", ["parent" => $parentId], "*", "viewIndex");

        for ($i = 0; $i < count($children); $i++) {
            $child = $children[$i];
            $this->lookAtParameter($child, $organizedView);
            $organizedView["children"][$i]["aspectClass"] = $organizedView["aspectClass"];
            $this->lookAtChildrenWQueries($child["id"], $organizedView["children"][$i]);
            if ($child["partType"] == "templateRef") {
                $this->lookAtTemplateReference($child, $organizedView["children"][$i]);
            }
        }
        $this->lookAtHeader($organizedView);
        $this->lookAtTable($organizedView);
    }
    //gets aspect view 
    public function getAspect($aspectId)
    {
        $where = ["id" => $aspectId];
        $asp = Core::$systemDB->select("aspect_class natural join view", $where);
        if (empty($asp)) {
            //aspect class hasnt' been assigned because it has only 1 aspect
            $asp = Core::$systemDB->select("view", $where);
        }
        return $asp;
    }
    //receives an aspect id and returns all aspects of that aspectClass, if role is specified returns aspect of that role
    public function getAspects($anAspeptId)
    {
        $asp = $this->getAspect($anAspeptId);
        if ($asp["aspectClass"] != null) {
            //there are other aspects
            $aspects = Core::$systemDB->selectMultiple(
                "aspect_class natural join view",
                ["aspectClass" => $asp["aspectClass"], "partType" => "block", "parent" => null]
            );
            return $aspects;
        }
        return [$asp];
    }
    //contructs an array of the view with all it's children, if there isn't a role returns array of view arrays
    public function getViewWithParts($anAspectId, $role = null)
    {
        $anAspect = $this->getAspect($anAspectId);
        return $this->getViewContents($anAspect, $role);
    }

    //returns all pages or page of the name or id given
    public function getPages($id = null, $pageName = null)
    {
        return $this->getPagesOfCourse($this->getCourseId(), false, $id, $pageName);
    }
    public static function getPagesOfCourse($courseId, $forNavBar = false, $id = null, $pageName = null)
    {
        $fields = "course,id,name,theme,viewId,roleType,isEnabled";
        if ($pageName == null && $id == null) {
            if ($forNavBar) {
                $pages = Core::$systemDB->selectMultiple("page", ['course' => $courseId, "isEnabled" => 1], $fields);
            } else {
                $pages = Core::$systemDB->selectMultiple("page", ['course' => $courseId], $fields);
            }
            return array_combine(array_column($pages, "id"), $pages);
        } else if ($id !== null) {
            return Core::$systemDB->select("page", ["id" => $id, 'course' => $courseId], $fields);
        } else {
            return Core::$systemDB->select("page", ["name" => $pageName, 'course' => $courseId], $fields);
        }
    }
    public function getCourseId()
    {
        return $this->viewsModule->getParent()->getId();
    }
    public function createPageOrTemplateIfNew($name, $pageOrTemp, $enabled = false, $roleType = "ROLE_SINGLE")
    {
        if (empty($this->getPages(null, $name))) {
            $this->createPageOrTemplate($name, $pageOrTemp, $enabled, $roleType);
        }
    }
    public function createPageOrTemplate($name, $pageOrTemp, $enabled = false, $roleType = "ROLE_SINGLE")
    {
        $courseId = $this->getCourseId();

        if ($roleType == "ROLE_SINGLE"){
            if ($name == 'QR')
                $role = 'role.Teacher';
            else
                $role = 'role.Default';
        } 
        else if ($roleType == "ROLE_INTERACTION")
            $role = 'role.Default>role.Default';

        Core::$systemDB->insert("view", ["partType" => "block", "parent" => null, "role" => $role]);
        $viewId = Core::$systemDB->getLastId();

        //page or template to insert in db
        $newView = ["name" => $name, "course" => $courseId, "roleType" => $roleType];
        if ($pageOrTemp == "page") {
            $newView["viewId"] = $viewId;
            $newView['isEnabled'] = $enabled;
            Core::$systemDB->insert("page", $newView);
        } else {
            Core::$systemDB->insert("template", $newView);
            $templateId = Core::$systemDB->getLastId();
            Core::$systemDB->insert("view_template", ["viewId" => $viewId, "templateId" => $templateId]);
        }
        //return $id;
    }
    public function getRoleType($role)
    {
        if (strpos($role, '>') !== false) {
            return "ROLE_INTERACTION";
        } else return "ROLE_SINGLE";
    }
    public function registerLibrary($moduleId, $libraryName, $description)
    {
        if (!Core::$systemDB->select("dictionary_library", ["moduleId" => $moduleId, "name" => $libraryName])) {
            Core::$systemDB->insert(
                "dictionary_library",
                array(
                    "moduleId" => $moduleId,
                    "name" => $libraryName,
                    "description" => $description
                )
            );
        } else {
            Core::$systemDB->update(
                "dictionary_library",
                array(
                    "moduleId" => $moduleId,
                    "name" => $libraryName,
                    "description" => $description
                ),
                array(
                    "moduleId" => $moduleId,
                    "name" => $libraryName
                )
            );
        }
    }
    
    public function unregisterLibrary($moduleId, $libraryName){
        Core::$systemDB->delete("dictionary_library", ["moduleId" => $moduleId, "name" => $libraryName]);
    }

    public function registerVariable($name, $returnType, $returnName, $libraryName = null, $description = null)
    {
        if ($libraryName) {
            $libraryId = Core::$systemDB->select("dictionary_library", ["name" => $libraryName], "id");
            if (!$libraryId) {
                new \Exception('Library named ' . $libraryName . ' not found.');
            }
        } else {
            $libraryId = null;
        }
        if (!Core::$systemDB->select("dictionary_variable", ["name" => $name])) {
            Core::$systemDB->insert(
                "dictionary_variable",
                array(
                    "name" => $name,
                    "libraryId" => $libraryId,
                    "returnName" => $returnName,
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
                    "returnName" => $returnName,
                    "returnType" => $returnType,
                    "description" => $description
                ),
                array(
                    "name" => $name
                )

            );
        }
    }

    public function unregisterVariable($name){
        Core::$systemDB->delete("dictionary_variable", ["name" => $name]);
    }

    public function registerFunction($funcLib, $funcName, $processFunc, $description,  $returnType, $returnName = null,  $refersToType = "object", $refersToName = null)
    {
        if ($funcLib) {
            $libraryId = Core::$systemDB->select("dictionary_library", ["name" => $funcLib], "id");
            if (!$libraryId) {
                new \Exception('Library named ' . $funcName . ' not found.');
            }
        } else {
            $libraryId = null;
        }
        if ($processFunc) {
            $processFuncArr = (array)$processFunc;
            $reflection = new \ReflectionFunction($processFuncArr[0]);
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
        $functionId = Core::$systemDB->select("dictionary_function", ["libraryId" => $libraryId, "keyword" => $funcName], "id");
        $this->registeredFunctions[$functionId] = $processFunc;
    }
    
    public function unregisterFunction($funcLib, $funcName){
        if ($funcLib) {
            $libraryId = Core::$systemDB->select("dictionary_library", ["name" => $funcLib], "id");
            if (!$libraryId) {
                new \Exception('Library named ' . $funcName . ' not found.');
            }
        } else {
            $libraryId = null;
        }
        Core::$systemDB->delete("dictionary_function", array("libraryId" => $libraryId, "keyword" => $funcName));
    }

    public function callFunction($funcLib, $funcName, $args, $context = null)
    {
        if (!$funcLib) {
            $function = Core::$systemDB->select("dictionary_function", ["libraryId" => null, "keyword" => $funcName]);
            if ($function) {
                $fun = $this->registeredFunctions[$function["id"]];
            } else {
                throw new \Exception("Function " . $funcName . " doesn't exists.");
            }
        } else {
            //ver se esta associado
            $library = Core::$systemDB->select("dictionary_library", ["name" => $funcLib]);
            if (!$library) {
                throw new \Exception('Called function ' . $funcName . ' on an unexistent library ' . $funcLib);
            } else {
                $function = Core::$systemDB->select("dictionary_function", ["libraryId" => $library["id"], "keyword" => $funcName]);
                $funcLibrary = Core::$systemDB->select("dictionary_function", ["keyword" => $funcName]);
                if ($function) {
                    $fun = $this->registeredFunctions[$function["id"]];
                } else if ($funcLibrary["libraryId"] == NULL) {
                    $fun = $this->registeredFunctions[$funcLibrary["id"]];
                } else {
                    throw new \Exception('Function ' . $funcName . ' is not defined in library ' . $funcLib);
                }
            }
        }
        if ($context !== null) {
            array_unshift($args, $context);
        }
        return $fun(...$args);
    }


    public function registerPartType($partType, $breakFunc, $putTogetherFunc, $parseFunc, $processFunc)
    {
        if (array_key_exists($partType, $this->registeredPartTypes))
            new \Exception('Part ' . $partType . ' is already exists');

        $this->registeredPartTypes[$partType] = array($breakFunc, $putTogetherFunc, $parseFunc, $processFunc);
    }

    public function callPartBreak($partType, &...$args)
    {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][0];
        if ($func != null)
            $func(...$args);
    }

    public function callPartPutTogether($partType, &...$args)
    {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][1];
        if ($func != null)
            $func(...$args);
    }

    public function callPartParse($partType, &...$args)
    {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][2];
        if ($func != null)
            $func(...$args);
    }

    public function callPartProcess($partType, &...$args)
    {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][3];
        if ($func != null)
            $func(...$args);
    }

    public function processEvents(&$part, $visitor)
    {
        if (array_key_exists('events', $part) && $part["events"] != null) {
            foreach ($part['events'] as $k => &$v) {
                $v = $v->accept($visitor)->getValue();
            }
        }
    }

    public function processVariables(&$part, $viewParams, $visitor, $func = null)
    {
        $actualVisitor = $visitor;
        $params = $viewParams;
        if (array_key_exists('variables', $part) && $part["variables"] != null) {

            foreach ($part['variables'] as $k => &$v) {
                $params[$k] = $v['value']->accept($actualVisitor)->getValue();
                if ($params != $viewParams)
                    $actualVisitor = new EvaluateVisitor($params, $this);
            }
        }
        if ($func != null && is_callable($func)) {
            return $func($params, $actualVisitor);
        }
    }

    public function processLoop(&$container, $viewParams, $visitor, $func)
    {
        $containerArr = array();
        foreach ($container as &$child) {
            if (!array_key_exists('loopData', $child)) {
                if ($this->processVisibilityCondition($child, $visitor)) {
                    $func($child, $viewParams, $visitor);
                    $containerArr[] = $child;
                }
            } else {
                $repeatKey = "item";
                $repeatParams = array();
                $keys = null;
                $value = $child['loopData']->accept($visitor)->getValue();

                if (is_null($value))
                    $value = [];
                if (!is_array($value)) {
                    throw new \Exception('Data Loop must have an object or collection.');
                }
                $value = $value["value"];
                //if the $value array is associative it will be put in a sequential array
                $isNumericArray = true;
                foreach (array_keys($value) as $key) {
                    if (!is_int($key)) {
                        $isNumericArray = false;
                        break;
                    }
                }
                if (!$isNumericArray)
                    $value = [$value];

                $repeatParams = $value;

                $i = 0;
                foreach ($repeatParams as &$params) {
                    $params = [$repeatKey => $params];
                    $i++;
                }
                
                //unset($child['repeat']);
                $repeatParams = array_values($repeatParams);
               
                for ($p = 0; $p < sizeof($repeatParams); $p++) {
                    $value = $repeatParams[$p][$repeatKey];
                    if (!is_array($value))
                        $loopParam = [$repeatKey => $value];
                    else
                        $loopParam = [$repeatKey => ["type" => "object", "value" => $value]];
                    
                    $dupChild = $child;
                    $paramsforEvaluator = array_merge($viewParams, $loopParam, array("index" => $p));
                    $newvisitor = new EvaluateVisitor($paramsforEvaluator, $this);

                    if ($this->processVisibilityCondition($dupChild, $newvisitor)) {
                        $func($dupChild, $paramsforEvaluator, $newvisitor);
                        $containerArr[] = $dupChild;
                    }
                    //print_r($containerArr);
                }
            }
        }
        $container = $containerArr;
    }

    public function processVisibilityCondition(&$part, $visitor)
    {
        if (!array_key_exists('visibilityCondition', $part))
            return true;
        else {
            $ret = false;
            if ($part['visibilityCondition']->accept($visitor)->getValue() == true)
                $ret = true;
            unset($part['visibilityCondition']);
            return $ret;
        }
    }

    public function processPart(&$part, $viewParams, $visitor)
    {
        $this->processVariables($part, $viewParams, $visitor, function ($viewParams, $visitor) use (&$part) {
            $style = "";
            if (array_key_exists("visibilityType", $part) && $part["visibilityType"] == "invisible") {
                $style .= " display: none; ";
            }
            if (array_key_exists("label", $part)) {
                $part['label'] = $part['label']->accept($visitor)->getValue();
            }
            if (array_key_exists('style', $part)) {
                $style .= $part['style']->accept($visitor)->getValue();
            }
            $part["style"] = $style;
            if (array_key_exists('class', $part)) {
                $part['class'] = $part['class']->accept($visitor)->getValue();
            }
            $this->processEvents($part, $visitor);

            $this->callPartProcess($part['partType'], $part, $viewParams, $visitor);
        });
    }

    public function processView(&$view, $viewParams)
    {
        $visitor = new EvaluateVisitor($viewParams, $this);
        $this->processLoop($view['children'], $viewParams, $visitor, function (&$part, $viewParams, $visitor) {
            $this->processPart($part, $viewParams, $visitor);
        });
    }

    public function parseEvents(&$part)
    {
        if (array_key_exists('events', $part) && $part["events"] != null) {
            foreach ($part['events'] as $k => &$v) {
                $this->parseSelf($v);
            }
        }
    }

    public function parseVariables(&$part)
    {
        if (array_key_exists('variables', $part) && $part["variables"] != null) {
            foreach ($part['variables'] as $k => &$v) {
                $this->parseSelf($v['value']);
            }
        }
    }

    public function parseLoop(&$part)
    {
        if (array_key_exists("loopData", $part)) {
            if ($part['loopData'] == "{}" || $part['loopData'] == "")
                unset($part['loopData']);
            else {
                $this->parseSelf($part['loopData']);
            }
        }
    }

    public function parseVisibilityCondition(&$part)
    {
        if (($part["visibilityType"] == "visible" && array_key_exists("loopData", $part))
            || $part['visibilityCondition'] == "{}" || $part['visibilityCondition'] == ""
        ) {
            unset($part["visibilityCondition"]);
        } else {
            $this->parseSelf($part['visibilityCondition']);
        }
    }

    public function parsePart(&$part)
    {
        $this->parseVariables($part);
        if (array_key_exists('style', $part)) {
            $this->parseSelf($part['style']);
        }
        if (array_key_exists('class', $part)) {
            $this->parseSelf($part['class']);
        }
        if (array_key_exists("label", $part)) {
            $this->parseSelf($part['label']);
        }
        $this->parseEvents($part);
        $this->parseLoop($part);
        $this->parseVisibilityCondition($part);
        if ($part["partType"] === "templateRef") {
            $part["partType"] = "block";
        }
        $this->callPartParse($part['partType'], $part);
    }

    public function parseView(&$view)
    {
        foreach ($view['children'] as &$part) {
            $this->parsePart($part);
        }
    }

    //go throgh roles of a view to find the role of the user
    public function handleHelper($roleArray, $course, $userRoles)
    {
        $roleFound = null;
        $userSpecificView = 'user.' . (string)API::getValue('user');
        if (in_array($userSpecificView, $roleArray)) {
            $roleFound = $userSpecificView;
        } else {
            if (in_array('role.Default', $roleArray)) {
                $roleFound = 'role.Default';
            }

            //this is choosing a role with low hirearchy (maybe change)
            $course->goThroughRoles(function ($role, $hasChildren, $continue) use ($userRoles, $roleArray, &$roleFound) {
                if (in_array('role.' . $role["name"], $roleArray) && in_array($role["name"], $userRoles)) {

                    $roleFound = 'role.' . $role["name"];
                }
                if ($hasChildren)
                    $continue();
            });
            
        }
        return $roleFound;
    }

    //handles requests to show a page
    public function handle($view, $course, $viewParams)
    { //receives page/template     
        $viewRoles = array_column($this->getAspects($view["viewId"]), 'role');
        $viewType = $view["roleType"];
        $roleOne = $roleTwo = null;

        //TODO check if everything works with the roles in the handle helper (test w user w multiple roles, and child roles)
        if ($viewType == "ROLE_INTERACTION") {
            $roleArray = []; //role1=>[roleA,roleB],role2=>[roleA],...
            foreach ($viewRoles as $roleInteraction) {
                $roles = explode('>', $roleInteraction);
                $roleArray[$roles[0]][] = $roles[1];
            }
            $userRoles = $course->getUser($viewParams["user"])->getRolesNames();
            $roleOne = $this->handleHelper(array_keys($roleArray), $course, $userRoles);
            $roleArray = $roleArray[$roleOne];

            if (in_array('special.Own', $roleArray) && $viewParams["user"] == (string)Core::getLoggedUser()->getId()) {
                $roleTwo = 'special.Own';
            } else {
                $loggedUserRoles = $course->getLoggedUser()->getRolesNames();
                $roleTwo = $this->handleHelper($roleArray, $course, $loggedUserRoles);
            }
            $userView = $this->getViewWithParts($view["viewId"], $roleOne . '>' . $roleTwo);
        } else if ($viewType == "ROLE_SINGLE") {
            $userRoles = $course->getLoggedUser()->getRolesNames();
            $roleOne = $this->handleHelper($viewRoles, $course, $userRoles);
            $userView = $this->getViewWithParts($view["viewId"], $roleOne);
        }
        
        $this->parseView($userView);
        $this->processView($userView, $viewParams);

        return $userView;
    }
}
