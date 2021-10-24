<?php

namespace GameCourse\Views;

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Views\Expression\EvaluateVisitor;
use GameCourse\Views\Expression\ExpressionEvaluatorBase;
use GameCourse\Views\Expression\ValueNode;

class ViewHandler
{
    // FIXME: put in separate class
    public static $registeredFunctions = array();
    public static $registeredPartTypes = array();


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Handling views ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Handles request to show a view.
     * Builds view from scratch.
     *
     * @param $view (is a page)
     * @param Course $course
     * @param $viewParams
     * @return array|mixed|void
     */
    public static function handle($view, Course $course, $viewParams)
    {
        $courseUser = $course->getLoggedUser();

        $userRolesHierarchy = $courseUser->getUserRolesByHierarchy(); // [0]=>role more specific, [1]=>role less specific...
        array_push($userRolesHierarchy, "Default"); // add Default as the last choice

        $viewType = $view["roleType"];
        $viewId = $view["viewId"];

        if ($viewType == "ROLE_INTERACTION") {
            $roleArray = [];
            $userRoles = $course->getUser($viewParams["user"])->getUserRolesByHierarchy();
            array_push($userRoles, "Default");
            $viewerRoles = $userRolesHierarchy;

            foreach ($viewerRoles as $vr) {
                foreach ($userRoles as $ur) {
                    $roleArray[] = $ur . '>' . $vr;
                }
            }
            $userView = ViewHandler::getViewWithParts($viewId, $roleArray);

        } else if ($viewType == "ROLE_SINGLE") {
            $userView = ViewHandler::getViewWithParts($viewId, $userRolesHierarchy);
        }

        ViewHandler::parseView($userView);
        ViewHandler::processView($userView, $viewParams);
        return $userView;
    }

    /**
     * Constructs an array of the view with all its children.
     * If there isn't a role, returns array of view arrays.
     *
     * @param $viewId
     * @param $userRoles
     * @param bool $edit
     * @return array|mixed|void
     */
    public static function getViewWithParts($viewId, $userRoles = null, bool $edit = false)
    {
        return ViewHandler::getViewContents($viewId, $userRoles, $edit);
    }

    /**
     * Constructs array of view with all its contents.
     * Receives aspect (and possibly role).
     *
     * @param $viewId
     * @param $role
     * @param bool $edit
     * @return array|mixed|void
     */
    private static function getViewContents($viewId, $role = null, bool $edit = false)
    {
        // Gets all the view aspects
        $aspectsOfView = Core::$systemDB->selectHierarchy(
            "view v left join view_parent vp on v.viewId=vp.childId",
            "view v join view_parent vp on v.viewId = vp.childId",
            ["viewId" => $viewId],
            "v.*, vp.parentId"
        );

        // View is a template saved as ref if its view has a parent
        if (!empty(Core::$systemDB->select("view_template", ["viewId" => $viewId]))) {
            $keys = array_keys(array_column($aspectsOfView, "viewId"), $viewId);
            foreach ($keys as $key => $value) {
                $aspectsOfView[$value]["parentId"] = null;
            }
        }

        $templateViews = [];
        $parts = [];
        foreach ($aspectsOfView as $v) {
            if ($v["parentId"] == null) {
                // View is a template saved as ref or a template used by ref if the template (main) view has a parent
                $isTemplateRef = !empty(Core::$systemDB->select("view_parent join view_template on childId=viewId", ["childId" => $v["viewId"]]));
                if ($isTemplateRef) $v["isTemplateRef"] = true;
                $templateViews[] = $v;

            } else {
                $viewAspects = Core::$systemDB->selectMultiple(
                    "view join view_parent on viewId=childId",
                    ["viewId" => $v["viewId"]]
                );

                // View is a template saved as ref if its view has a parent
                $isTemplateRef = !empty(Core::$systemDB->select("view_parent join view_template on childId=viewId", ["childId" => $v["viewId"]]));
                if (sizeof($viewAspects) > 1) {
                    if ($edit) {
                        foreach ($viewAspects as $asp) {
                            if ((empty($parts[$v['parentId']]) || !in_array($asp, $parts[$v['parentId']]))) {
                                if ($isTemplateRef) $asp["isTemplateRef"] = true;
                                $parts[$v['parentId']][] = $asp;
                            }
                        }
                    } else {
                        $key = ViewHandler::findViewForRole($role, $viewAspects);
                        if ($key !== false && empty($parts[$v['parentId']]) || !in_array($viewAspects[$key], $parts[$v['parentId']])) {
                            if ($isTemplateRef) $viewAspects[$key]["isTemplateRef"] = true;
                            $parts[$v['parentId']][] = $viewAspects[$key];
                        }
                    }

                } else {
                    if ($edit) {
                        if ($isTemplateRef) $v["isTemplateRef"] = true;
                        $parts[$v['parentId']][] = $v;

                    } else {
                        // Check if the aspect that exists is for this user
                        $key = ViewHandler::findViewForRole($role, $viewAspects);
                        if ($key !== false) {
                            if ($isTemplateRef) $v["isTemplateRef"] = true;
                            $parts[$v['parentId']][] = $v;
                        }
                    }
                }
            }
        }

        foreach ($templateViews as &$aspect) {
            $aspect['edit'] = $edit;
            $aspect["children"] = [];
            if (isset($parts[$aspect['id']])) {
                ViewHandler::lookAtChildren($aspect['id'], $parts, $aspect, $edit);
            }
        }

        if (!$edit) {
            $key = ViewHandler::findViewForRole($role, $templateViews);
            if ($key !== false) return $templateViews[$key];

        } else return $templateViews;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Modifying views ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Receives view and updates the database with its info.
     * Propagates changes in the main view to all its children.
     * $basicUpdate -> you only update basic view attributes
     * (ignores view parameters and deletion of view parts),
     * used for change in aspectclass.
     *
     * @param $viewPart
     * @param $courseId
     * @param bool $basicUpdate
     * @param bool $ignoreIds
     * @param string|null $templateName
     * @param bool $fromModule
     * @param null $partsInDB
     */
    public static function updateViewAndChildren($viewPart, $courseId, bool $basicUpdate = false, bool $ignoreIds = false, string $templateName = null, bool $fromModule = false, &$partsInDB = null)
    {
        if ($viewPart["parentId"] == null) {
            $copy = ViewHandler::makeCleanViewCopy($viewPart);

            if (array_key_exists("id", $viewPart) && !$ignoreIds) { //already in DB, may need update
                Core::$systemDB->update("view", $copy, ["id" => $viewPart["id"]]);
                if (!$basicUpdate) {
                    unset($partsInDB[$viewPart["id"]]);
                }
            } else if (array_key_exists("viewId", $viewPart) && !empty(Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]])) && !$fromModule) {
                // if we save twice in view editor, to not insert again
                $id = Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]], "id");
                Core::$systemDB->update("view", $copy, ["id" => $id]);
                $viewPart["id"] = $id;
            } else { //not in DB, insert it
                Core::$systemDB->insert("view", $copy);
                $viewId = Core::$systemDB->getLastId();
                $viewPart["id"] = $viewId;
                if (!isset($copy["viewId"])) {
                    $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $viewId], '*', null, [['id', $viewId]]));
                    if ($viewIdExists) {
                        $lastViewId = intval(end(Core::$systemDB->selectMultiple("view", null, 'viewId', 'viewId asc'))['viewId']) + 1;
                        Core::$systemDB->update("view", ["viewId" => $lastViewId], ['id' => $viewId]);
                        $viewPart["viewId"] = $lastViewId;
                    } else {
                        Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                        $viewPart["viewId"] = $viewId;
                    }
                } else {
                    $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $copy["viewId"]], '*', null, [['id', $viewId]]));
                    if ($viewIdExists && $templateName != null && !$fromModule) { //we only want to change viewId if we are saving as template
                        Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                        $viewPart["viewId"] = $viewId;
                    } else if ($viewIdExists  && $fromModule && empty(Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $copy['viewId'], 'course' => $courseId]))) {
                        // if we are creating in other course, we want to change
                        Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                        $viewPart["viewId"] = $viewId;
                    } else
                        $viewPart["viewId"] = $copy["viewId"];
                }
                if (($templateName != null && !$fromModule) || ($fromModule  && empty(Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $viewPart['viewId'], 'course' => $courseId])))) {
                    $templateId = Core::$systemDB->select("template", ["name" => $templateName, 'course' => $courseId], "id");
                    Core::$systemDB->insert("view_template", ["viewId" => $viewPart["viewId"], "templateId" => $templateId]);
                }
            }
        }
        if ($viewPart["parentId"] != null) {
            //insert/update views
            $copy = ViewHandler::makeCleanViewCopy($viewPart);

            if (array_key_exists("id", $viewPart) && !$ignoreIds) { //already in DB, may need update
                Core::$systemDB->update("view", $copy, ["id" => $viewPart["id"]]);
                if (!$basicUpdate) {
                    unset($partsInDB[$viewPart["id"]]);
                }
            } else if (array_key_exists("viewId", $viewPart) && !empty(Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]])) && !$fromModule) {
                // if we save twice in view editor, to not insert again
                $id = Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]], "id");
                Core::$systemDB->update("view", $copy, ["id" => $id]);
                $viewPart["id"] = $id;
            } else {
                if (!isset($viewPart["isTemplateRef"])) { //not in DB, insert it
                    Core::$systemDB->insert("view", $copy);
                    $viewId = Core::$systemDB->getLastId();
                    $viewPart["id"] = $viewId;
                    if (!isset($copy["viewId"])) {
                        $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $viewId], '*', null, [['id', $viewId]]));
                        if ($viewIdExists) {
                            $tmp = Core::$systemDB->selectMultiple("view", null, 'viewId', 'viewId asc');
                            $lastViewId = intval(end($tmp)['viewId']) + 1;
                            Core::$systemDB->update("view", ["viewId" => $lastViewId], ['id' => $viewId]);
                            $viewPart["viewId"] = $lastViewId;
                        } else {
                            Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                            $viewPart["viewId"] = $viewId;
                        }
                    } else {
                        $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $copy["viewId"]], '*', null, [['id', $viewId]]));
                        $parents = array_column(Core::$systemDB->selectMultiple("view_parent", ["childId" => $copy["viewId"]], "parentId"), "parentId");
                        $siblings = in_array($viewPart["parentId"], $parents);
                        if ($viewIdExists && (!$siblings || $viewPart["viewIndex"] != "0")) {
                            Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                            $viewPart["viewId"] = $viewId;
                        } else
                            //we can have several views with the same viewId if they are aspects of the same view so we want to keep it
                            $viewPart["viewId"] = $copy["viewId"];
                    }
                }
                if (empty(Core::$systemDB->select("view_parent", [
                    "parentId" => $viewPart["parentId"],
                    "childId" => $viewPart["viewId"]
                ])))
                    Core::$systemDB->insert("view_parent", [
                        "parentId" => $viewPart["parentId"],
                        "childId" => $viewPart["viewId"],
                        "viewIndex" => $viewPart["viewIndex"]
                    ]);

                // if ($viewPart["partType"] == "templateRef") {
                //     Core::$systemDB->insert("view_template", ["viewId" => $viewPart["id"], "templateId" => $viewPart["templateId"]]);
                // }

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

            //readjust keys to start at 0
            $viewPart["children"] = array_combine(range(0, count($viewPart["children"]) - 1), $viewPart["children"]);
        }

        if (array_key_exists("children", $viewPart)) {
            $children = null;
            if (!$basicUpdate) {
                //all the children of the view except headers which are dealt with later
                $children = Core::$systemDB->selectMultiple("view v join view_parent vp on v.viewId=vp.childId", ["parentId" => $viewPart["id"]], "*", null, [["partType", "header"]]);
                $children = array_combine(array_column($children, "id"), $children);
            }
            foreach ($viewPart["children"] as $key => &$child) {
                if ($key == 0) {
                    $currentViewId = $child["viewId"];
                    $currentIdx = 0;
                }

                $child["parentId"] = $viewPart["id"];
                if ($child["viewId"] != $currentViewId) {
                    $currentViewId = $child["viewId"];
                    $currentIdx += 1;
                }
                $child["viewIndex"] = $currentIdx;
                ViewHandler::updateViewAndChildren($child, $courseId, $basicUpdate, $ignoreIds, null, $fromModule, $children);
            }

            if (!$basicUpdate) {
                foreach ($children as $deleted) {
                    //Core::$systemDB->delete("view", ["id" => $deleted["id"]]);
                    ViewHandler::deleteViews($deleted, true);
                }
            }
        }
        if ($viewPart["partType"] == "block") { //deal with header of block
            $header = Core::$systemDB->select("view join view_parent on viewId=childId", ["parentId" => $viewPart["id"], "partType" => "header", "role" => $viewPart["role"]], "id,viewId");
            if (array_key_exists("header", $viewPart)) { //if there is a header in the updated version
                if (!$basicUpdate && empty($header)) { //insert (header is not in DB)
                    Core::$systemDB->insert("view", [
                        "partType" => "header", "role" => $viewPart["role"]
                    ]);
                    $headerId = Core::$systemDB->getLastId();
                    $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $headerId], '*', null, [['id', $headerId]]));
                    if ($viewIdExists) {
                        $tmp = Core::$systemDB->selectMultiple("view", null, 'viewId', 'viewId asc');
                        $lastViewId = intval(end($tmp)['viewId']) + 1;
                        Core::$systemDB->update("view", ["viewId" => $lastViewId], ['id' => $headerId]);
                        $headerViewId = $lastViewId;
                    } else {
                        Core::$systemDB->update("view", ["viewId" => $headerId], ['id' => $headerId]);
                        $headerViewId = $headerId;
                    }

                    Core::$systemDB->insert("view_parent", ["parentId" => $viewPart["id"], "childId" => $headerViewId]);

                    $headerPart = [
                        "role" => $viewPart["role"], "parentId" => $headerId, "viewIndex" => 0
                    ];
                    $image = array_merge($viewPart["header"]["image"], $headerPart);
                    unset($image["id"]);
                    unset($image["viewId"]);

                    ViewHandler::updateViewAndChildren($image, $courseId, $basicUpdate, $ignoreIds);
                    $headerPart["viewIndex"] = 1;
                    $text = array_merge($viewPart["header"]["title"], $headerPart);
                    unset($text["id"]);
                    unset($text["viewId"]);

                    ViewHandler::updateViewAndChildren($text, $courseId, $basicUpdate, $ignoreIds);
                } else if (!empty($header)) { //update (header is in DB)
                    //in most cases just updating parameters
                    $headerParts = Core::$systemDB->selectMultiple("view join view_parent on viewId=childId", ["parentId" => $header["id"]]);
                    foreach ($headerParts as $part) {
                        if ($basicUpdate) {
                            Core::$systemDB->update(
                                "view",
                                ["role" => $viewPart["role"]],
                                ["id" => $part["id"]],
                            );
                        } else {
                            if ($part["partType"] == "text")
                                $type = "title";
                            else
                                $type = "image";
                            $part = array_merge($part, ViewHandler::makeCleanViewCopy($viewPart["header"][$type]));
                            $partId = $part["id"];
                            unset($part["id"]);
                            unset($part["parentId"]);
                            unset($part["childId"]);
                            unset($part["viewIndex"]);
                            Core::$systemDB->update("view", $part, ["id" => $partId]);
                        }
                    }
                }
            } else if (!empty($header) && !$basicUpdate) { //delete header in db
                Core::$systemDB->delete("view", ["viewId" => $header["viewId"], "partType" => "header"]);
                //delete header from the view_parent table
                Core::$systemDB->delete("view_parent", ["childId" => $header["viewId"], "parentId" => $viewPart["id"]]);

                $childrenHeader = Core::$systemDB->selectMultiple("view join view_parent", ["parentId" => $header["id"]], "id");
                //delete image & title (from header) from the view_parent table and view table
                Core::$systemDB->delete("view_parent", ["parentId" => $header["id"]]);
                foreach ($childrenHeader as $c) {
                    Core::$systemDB->delete("view", ["id" => $c["id"]]);
                }
            }
            if ($basicUpdate && !empty($header)) { //ToDo
                Core::$systemDB->update(
                    "view",
                    ["role" => $viewPart["role"]],
                    ["viewId" => $header["viewId"]]
                );
            }
        }
    }

    /**
     * Deletes views.
     *
     * @param $view
     * @param bool $isRoot
     */
    public static function deleteViews($view, bool $isRoot = false)
    {
        $children = Core::$systemDB->selectMultiple("view join view_parent on viewId=childId", ["parentId" => $view["id"]], "*");
        if (count($children) > 0) {
            foreach ($children as $child)
                ViewHandler::deleteViews($child);
        }
        $isTemplateRef = !empty(Core::$systemDB->select("view_template", ["viewId" => $view["viewId"]])) && !$isRoot;
        if (!$isTemplateRef)
            Core::$systemDB->delete("view", ["id" => $view["id"]]);
        if (!$isRoot)
            Core::$systemDB->delete("view_parent", ["childId" => $view["viewId"], "parentId" => $view["parentId"]]);
    }

    /**
     * Resets parent and views IDs
     *
     * @param $view
     */
    public static function resetParentsAndViewIds(&$view)
    {
        if ($view['parentId'] != null)
            $view['parentId'] = 0;
        $view['viewId'] = null;
        if ($view["partType"] == "table") {
            foreach ($view["headerRows"] as &$headRow) {
                ViewHandler::resetParentsAndViewIds($headRow);
                foreach ($headRow["values"] as &$rowElement) {
                    ViewHandler::resetParentsAndViewIds($rowElement['value']);
                }
            }
            foreach ($view["rows"] as &$row) {
                ViewHandler::resetParentsAndViewIds($row);
                foreach ($row["values"] as &$rowElement) {
                    ViewHandler::resetParentsAndViewIds($rowElement['value']);
                }
            }
        }
        if (array_key_exists("children", $view)) {
            foreach ($view["children"] as &$child) {
                ViewHandler::resetParentsAndViewIds($child);
            }
        }
    }

    /**
     * Creates a clean view copy.
     *
     * @param $viewPart
     * @return mixed
     */
    private static function makeCleanViewCopy($viewPart)
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
        unset($copy["parentId"]);
        unset($copy["noHeader"]);
        unset($copy["isTemplateRef"]);
        unset($copy["childId"]);
        unset($copy["viewIndex"]);
        if ($copy["partType"] == "chart") {
            $copy["value"] = $copy["chartType"];
            unset($copy["chartType"]);
            $copy["info"] = json_encode($copy["info"]);
        }
        if (isset($copy["variables"]))
            $copy["variables"] = json_encode($copy["variables"]);
        if (isset($copy["events"]))
            $copy["events"] = json_encode($copy["events"]);
        return $copy;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Looking at views ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Go through views and update array with parameters info.
     * (it receives arrays with all the data, doesn't do more queries)
     *
     * @param $parent
     * @param $children
     * @param $organizedView
     * @param bool $edit
     */
    private static function lookAtChildren($parent, $children, &$organizedView, bool $edit = false)
    {
        if (!array_key_exists($parent, $children))
            return;

        for ($i = 0; $i < count($children[$parent]); $i++) {
            $child = $children[$parent][$i];
            $child['edit'] = $edit;
            ViewHandler::lookAtParameter($child, $organizedView);
            ViewHandler::lookAtChildren($child['id'], $children, $organizedView["children"][$i], $edit);
            if ($child["partType"] == "templateRef") {
                ViewHandler::lookAtTemplateReference($child, $organizedView["children"][$i]);
            }
        }

        ViewHandler::lookAtHeader($organizedView); // FIXME: refactor and remove (header is just another view
        ViewHandler::lookAtTable($organizedView);
    }

    /**
     * Get data of view parameter.
     *
     * @param $child
     * @param $organizedView
     */
    private static function lookAtParameter($child, &$organizedView)
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

    /**
     * Check if view is a block w/ header and get its data.
     * FIXME: refactor and remove header from block
     *
     * @param $organizedView
     */
    private static function lookAtHeader(&$organizedView)
    {
        if ($organizedView["partType"] == "block" && sizeof($organizedView["children"]) > 0) {
            foreach ($organizedView["children"] as $key => $child) {
                if ($child["partType"] == "header" && $child["role"] == $organizedView["role"]) {
                    $organizedView["header"] = $child;
                    unset($organizedView["header"]["children"]);
                    foreach ($child["children"] as $headerKey => $headerChild) {
                        if ($headerChild["role"] == $child["role"]) {
                            $element = $headerChild["partType"] == "image" ? $headerChild["partType"] : "title";
                            $organizedView["header"][$element] = $headerChild;
                        } else {
                            unset($organizedView["children"][$headerKey]);
                        }
                    }
                    unset($organizedView["children"][$key]);
                } else if ($child["partType"] == "header" && $child["role"] != $organizedView["role"]) {
                    unset($organizedView["children"][$key]);
                }
            }
            $organizedView["children"] = array_values($organizedView["children"]);
        }
    }

    /**
     * Get table data.
     *
     * @param $organizedView
     */
    private static function lookAtTable(&$organizedView)
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

    /**
     * Gets contents of template referenced by templateRef
     *
     * @param $templatRef
     * @param $organizedView
     */
    private static function lookAtTemplateReference($templatRef, &$organizedView)
    {
        // Get template and its aspect
        $aspect = Core::$systemDB->select(
            "view_template vt join template on templateId=id join view_template vt2 on id=vt2.templateId join view v on v.id=vt2.viewId",
            ["vt.viewId" => $templatRef["id"], "v.partType" => "block", "v.parent" => null],
            "v.id,v.viewId,vt.templateId,roleType,role,partType"
        );

        // Deal with roles of different types
        $roleType = ViewHandler::getRoleType($templatRef["role"]);
        if ($roleType == "ROLE_INTERACTION" && $aspect["roleType"] == "ROLE_SINGLE") {
            $role = explode(">", $templatRef["role"])[1];
        } else if ($roleType == "ROLE_SINGLE" && $aspect["roleType"] == "ROLE_INTERACTION") {
            $role = "role.Default>" . $templatRef["role"];
        } else $role = $templatRef["role"];

        $aspectView = ViewHandler::getViewContents($aspect['viewId'], $role);
        $organizedView["children"] = $aspectView["children"];
        $organizedView["aspectId"] = $aspect["id"];
        $organizedView["templateId"] = $aspect["templateId"];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Parsing views ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function parse($exp)
    {
        static $parser;
        if ($parser == null) $parser = new ExpressionEvaluatorBase();
        if (trim($exp) == '') return new ValueNode('');
        return $parser->parse($exp);
    }

    public static function parseView(&$view)
    {
        foreach ($view['children'] as &$part) {
            ViewHandler::parsePart($part);
        }
    }

    public static function parsePart(&$part)
    {
        ViewHandler::parseVariables($part);
        if (array_key_exists('style', $part)) ViewHandler::parseSelf($part['style']);
        if (array_key_exists('class', $part)) ViewHandler::parseSelf($part['class']);
        if (array_key_exists('cssId', $part)) ViewHandler::parseSelf($part['cssId']);
        if (array_key_exists("label", $part)) ViewHandler::parseSelf($part['label']);
        ViewHandler::parseEvents($part);
        ViewHandler::parseLoop($part);
        ViewHandler::parseVisibilityCondition($part);
        if ($part["partType"] === "templateRef") $part["partType"] = "block";
        ViewHandler::callPartParse($part['partType'], $part);
    }

    public static function parseSelf(&$exp)
    {
        $exp = ViewHandler::parse($exp);
    }

    public static function parseVariables(&$part)
    {
        if (array_key_exists('variables', $part) && $part["variables"] != null) {
            foreach ($part['variables'] as $k => &$v) {
                ViewHandler::parseSelf($v['value']);
            }
        }
    }

    public static function parseEvents(&$part)
    {
        if (array_key_exists('events', $part) && $part["events"] != null) {
            foreach ($part['events'] as $k => &$v) {
                ViewHandler::parseSelf($v);
            }
        }
    }

    public static function parseLoop(&$part)
    {
        if (array_key_exists("loopData", $part)) {
            if ($part['loopData'] == "{}" || $part['loopData'] == "")
                unset($part['loopData']);
            else {
                ViewHandler::parseSelf($part['loopData']);
            }
        }
    }

    public static function parseVisibilityCondition(&$part)
    {
        if (($part["visibilityType"] == "visible" && array_key_exists("loopData", $part))
            || $part['visibilityCondition'] == "{}" || $part['visibilityCondition'] == ""
        ) {
            unset($part["visibilityCondition"]);
        } else {
            ViewHandler::parseSelf($part['visibilityCondition']);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Calling functions ----------------- ***/
    /*** ---------------------------------------------------- ***/

    private static function callPartParse($partType, &...$args)
    {
        if (!array_key_exists($partType, ViewHandler::$registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');

        $func = ViewHandler::$registeredPartTypes[$partType][2];
        if ($func != null)
            $func(...$args);
    }

    private static function callPartProcess($partType, &...$args)
    {
        if (!array_key_exists($partType, ViewHandler::$registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = ViewHandler::$registeredPartTypes[$partType][3];
        if ($func != null)
            $func(...$args);
    }

    public static function callFunction($funcLib, $funcName, $args, $context = null)
    {
        if (!$funcLib) {
            $function = Core::$systemDB->select("dictionary_function", ["libraryId" => null, "keyword" => $funcName]);
            if ($function) {
                $fun = ViewHandler::$registeredFunctions[$function["id"]];
            } else {
                throw new \Exception("Function " . $funcName . " doesn't exist.");
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
                    $fun = ViewHandler::$registeredFunctions[$function["id"]];
                } else if ($funcLibrary["libraryId"] == NULL) {
                    $fun = ViewHandler::$registeredFunctions[$funcLibrary["id"]];
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


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Processing views ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function processView(&$view, $viewParams)
    {
        $visitor = new EvaluateVisitor($viewParams);
        ViewHandler::processLoop($view['children'], $viewParams, $visitor, function (&$part, $viewParams, $visitor) {
            ViewHandler::processPart($part, $viewParams, $visitor);
        });
    }

    public static function processPart(&$part, $viewParams, $visitor)
    {
        ViewHandler::processVariables($part, $viewParams, $visitor, function ($viewParams, $visitor) use (&$part) {
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
            if (array_key_exists('cssId', $part)) {
                $part['cssId'] = $part['cssId']->accept($visitor)->getValue();
            }
            ViewHandler::processEvents($part, $visitor);

            ViewHandler::callPartProcess($part['partType'], $part, $viewParams, $visitor);
        });
    }

    public static function processLoop(&$container, $viewParams, $visitor, $func)
    {
        $containerArr = array();
        foreach ($container as &$child) {
            if (!array_key_exists('loopData', $child)) {
                if (ViewHandler::processVisibilityCondition($child, $visitor)) {
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

                $repeatParams = array_values($repeatParams);

                for ($p = 0; $p < sizeof($repeatParams); $p++) {
                    $value = $repeatParams[$p][$repeatKey];
                    if (!is_array($value))
                        $loopParam = [$repeatKey => $value];
                    else
                        $loopParam = [$repeatKey => ["type" => "object", "value" => $value]];

                    $dupChild = $child;
                    $paramsforEvaluator = array_merge($viewParams, $loopParam, array("index" => $p));
                    $newvisitor = new EvaluateVisitor($paramsforEvaluator);

                    if (ViewHandler::processVisibilityCondition($dupChild, $newvisitor)) {
                        $func($dupChild, $paramsforEvaluator, $newvisitor);
                        $containerArr[] = $dupChild;
                    }
                }
            }
        }
        $container = $containerArr;
    }

    public static function processVisibilityCondition(&$part, $visitor): bool
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

    public static function processEvents(&$part, $visitor)
    {
        if (array_key_exists('events', $part) && $part["events"] != null) {
            foreach ($part['events'] as $k => &$v) {
                $v = $v->accept($visitor)->getValue();
            }
        }
    }

    public static function processVariables(&$part, $viewParams, $visitor, $func = null)
    {
        $actualVisitor = $visitor;
        $params = $viewParams;
        if (array_key_exists('variables', $part) && $part["variables"] != null) {

            foreach ($part['variables'] as $k => &$v) {
                $params[$k] = $v['value']->accept($actualVisitor)->getValue();
                if ($params != $viewParams)
                    $actualVisitor = new EvaluateVisitor($params);
            }
        }
        if ($func != null && is_callable($func)) {
            return $func($params, $actualVisitor);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Roles ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Receives a role string and returns the role type.
     * Input format: 'role.Default' or 'role.Default>role.Default'
     * Output options: ROLE_SINGLE & ROLE_INTERACTION
     *
     * @param string $role
     * @return string
     */
    public static function getRoleType(string $role): string
    {
        if (strpos($role, '>') !== false) return "ROLE_INTERACTION";
        else return "ROLE_SINGLE";
    }

    /**
     * Get roles for which there is (at least) one different (sub)view
     *
     * @param $id
     * @param $courseRoles
     * @param string $roleType
     * @return array|false
     */
    public static function getViewRoles($id, $courseRoles, string $roleType = 'ROLE_SINGLE')
    {

        $subviewsOfView = Core::$systemDB->selectHierarchy(
            "view v left join view_parent vp on v.viewId=vp.childId",
            "view v join view_parent vp on v.viewId = vp.childId",
            ["viewId" => $id],
            "v.*, vp.parentId"
        );

        $viewRoles = [];
        if ($roleType == 'ROLE_SINGLE') {
            foreach ($subviewsOfView as $v) {
                $viewAspects = Core::$systemDB->selectMultiple(
                    "view",
                    ["viewId" => $v["viewId"]],
                    "id,role as name"
                );
                foreach ($viewAspects as &$asp) {
                    $asp["name"] = explode(".", $asp["name"])[1]; //preg_replace("/\w+\./", "", $asp["name"]);
                }
                $viewRoles = array_merge($viewRoles, $viewAspects);
            }
            $roles = array_uintersect($courseRoles, $viewRoles, function ($a, $b) {
                if ($a["name"] === $b["name"]) {
                    return 0;
                }
                if ($a["name"] > $b["name"]) return 1;
                return -1;
            });

            $roles = array_combine(range(0, count($roles) - 1), $roles);
            return $roles;

        } else {
            $viewerRoles = [];
            $userRoles = [];
            foreach ($subviewsOfView as $v) {
                $viewAspects = Core::$systemDB->selectMultiple(
                    "view",
                    ["viewId" => $v["viewId"]],
                    "id,role as name"
                );
                foreach ($viewAspects as $key => $asp) {
                    $viewAspects[$key]["viewer"] = explode(".", explode(">", $viewAspects[$key]["name"])[1])[1];
                    $viewAspects[$key]["user"] = explode(".", explode(">", $viewAspects[$key]["name"])[0])[1];
                    unset($viewAspects[$key]["name"]);
                }

                $viewerRoles = array_merge($viewerRoles, $viewAspects);
                $userRoles = array_merge($userRoles, $viewAspects);
            }

            foreach ($viewerRoles as $key => $vr) {
                $viewerRoles[$key]['name'] = $viewerRoles[$key]['viewer'];
                unset($viewerRoles[$key]['user']);
                unset($viewerRoles[$key]['viewer']);
            }
            foreach ($userRoles as $key => $ur) {
                $userRoles[$key]['name'] = $userRoles[$key]['user'];
                unset($userRoles[$key]['user']);
                unset($userRoles[$key]['viewer']);
            }
            $vRoles = array_uintersect($courseRoles, $viewerRoles, function ($a, $b) {
                if ($a["name"] === $b["name"]) {
                    return 0;
                }
                if ($a["name"] > $b["name"]) return 1;
                return -1;
            });
            $uRoles = array_uintersect($courseRoles, $userRoles, function ($a, $b) {
                if ($a["name"] === $b["name"]) {
                    return 0;
                }
                if ($a["name"] > $b["name"]) return 1;
                return -1;
            });

            $vRoles = array_combine(range(0, count($vRoles) - 1), $vRoles);
            $uRoles = array_combine(range(0, count($uRoles) - 1), $uRoles);

            return [$uRoles, $vRoles];
        }
    }

    /**
     * Searches for a role.
     *
     * @param $userRoles
     * @param $viewAspects
     * @return false|int|string
     */
    private static function findViewForRole($userRoles, $viewAspects)
    {
        if (is_array($userRoles)) {
            // Search from the most specific role to the least one
            foreach ($userRoles as $role) {
                if (strpos($role, '>')) {
                    $user = explode('>', $role)[0];
                    $viewer = explode('>', $role)[1];
                    $fullRole = 'role.' . $user . '>' . 'role.' . $viewer;
                    $key = array_search($fullRole, array_column($viewAspects, 'role'));

                } else {
                    $key = array_search("role." . $role, array_column($viewAspects, 'role'));
                }

                if ($key !== false) return $key;
            }

        } else {
            if (strpos($userRoles, '>')) {
                $user = explode('>', $userRoles)[0];
                $viewer = explode('>', $userRoles)[1];
                $fullRole = 'role.' . $user . '>' . 'role.' . $viewer;
                return array_search($fullRole, array_column($viewAspects, 'role'));
            }
            return array_search("role." . $userRoles, array_column($viewAspects, 'role'));
        }
        return false;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Dictionary -------------------- ***/
    /*** ---------------------------------------------------- ***/
    // FIXME: put in separate class

    public static function registerLibrary($moduleId, $libraryName, $description)
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

    public static function unregisterLibrary($moduleId, $libraryName)
    {
        Core::$systemDB->delete("dictionary_library", ["moduleId" => $moduleId, "name" => $libraryName]);
    }

    public static function registerVariable($name, $returnType, $returnName, $libraryName = null, $description = null)
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

    public static function unregisterVariable($name)
    {
        Core::$systemDB->delete("dictionary_variable", ["name" => $name]);
    }

    public static function registerFunction($funcLib, $funcName, $processFunc, $description,  $returnType, $returnName = null,  $refersToType = "object", $refersToName = null)
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
        ViewHandler::$registeredFunctions[$functionId] = $processFunc;
    }

    public static function unregisterFunction($funcLib, $funcName)
    {
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

    public static function registerPartType($partType, $breakFunc, $putTogetherFunc, $parseFunc, $processFunc)
    {
        if (array_key_exists($partType, ViewHandler::$registeredPartTypes))
            new \Exception('Part ' . $partType . ' is already exists');

        ViewHandler::$registeredPartTypes[$partType] = array($breakFunc, $putTogetherFunc, $parseFunc, $processFunc);
    }

}
