<?php

namespace GameCourse\Views;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Views\Expression\EvaluateVisitor;
use GameCourse\Views\Expression\ExpressionEvaluatorBase;
use GameCourse\Views\Expression\ValueNode;

/**
 * This class is responsible for handling views (not pages or templates!).
 * It has functions that deal with updating and rendering views
 * to/from database.
 */
class ViewHandler
{

    /*** ---------------------------------------------------- ***/
    /*** ------------------ Updating views ------------------ ***/
    /*** ---------------- ( -> to database ) ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Updates view in database.
     * This includes all aspects of the view and also children.
     *
     * @param $view
     * @return array
     */
    public static function updateView(&$view): array
    {
        $viewId = null; // used to set the same viewId for all aspects of the view
        $templateRoles = []; // used to get template view roles

        foreach ($view as &$aspect) {
            if (isset($aspect["id"])) { // Already in database, UPDATE
                $mode = 'UPDATE';
                self::prepareViewForDatabase($aspect);

                // Update view in 'view' table
                Core::$systemDB->update("view", [
                    "viewId" => $aspect["viewId"],
                    "type" => $aspect["type"],
                    "role" => $aspect["role"],
                    "style" => $aspect["style"] ?? null,
                    "cssId" => $aspect["cssId"] ?? null,
                    "class" => $aspect["class"] ?? null,
                    "label" => $aspect["label"] ?? null,
                    "visibilityType" => $aspect["visibilityType"] ?? null,
                    "visibilityCondition" => $aspect["visibilityCondition"] ?? null,
                    "loopData" => $aspect["loopData"] ?? null,
                    "variables" => $aspect["variables"] ?? null,
                    "events" => $aspect["events"] ?? null
                ], ["id" => $aspect["id"]]);

            } else { // Not in database, INSERT
                $mode = 'INSERT';
                if ($viewId) $aspect["viewId"] = $viewId;
                self::prepareViewForDatabase($aspect);

                // Insert into 'view' table
                Core::$systemDB->insert("view", [
                    "type" => $aspect["type"],
                    "role" => $aspect["role"],
                    "style" => $aspect["style"] ?? null,
                    "cssId" => $aspect["cssId"] ?? null,
                    "class" => $aspect["class"] ?? null,
                    "label" => $aspect["label"] ?? null,
                    "visibilityType" => $aspect["visibilityType"] ?? null,
                    "visibilityCondition" => $aspect["visibilityCondition"] ?? null,
                    "loopData" => $aspect["loopData"] ?? null,
                    "variables" => $aspect["variables"] ?? null,
                    "events" => $aspect["events"] ?? null
                ]);

                // Update ids
                $aspect["id"] = Core::$systemDB->getLastId();
                if (!isset($aspect["viewId"])) $aspect["viewId"] = $aspect["id"];
                Core::$systemDB->update("view", ["viewId" => $aspect["viewId"]], ["id" => $aspect["id"]]);

                if (!$viewId && isset($aspect["viewId"])) $viewId = $aspect["viewId"];
            }

            // Update view depending on type
            $viewRoles = [];
            if ($aspect["type"] == 'text') $viewRoles = self::updateViewText($aspect, $mode);
            if ($aspect["type"] == 'image') $viewRoles = self::updateViewImage($aspect, $mode);
            if ($aspect["type"] == 'header') $viewRoles = self::updateViewHeader($aspect, $mode);
            if ($aspect["type"] == 'table') $viewRoles = self::updateViewTable($aspect, $mode);
            if ($aspect["type"] == 'block') $viewRoles = self::updateViewBlock($aspect, $mode);
            if ($aspect["type"] == 'row') $viewRoles = self::updateViewRow($aspect, $mode);
            // NOTE: insert here other types of views

            // Add view roles to template roles
            foreach ($viewRoles as $role) {
                if (!in_array($role, $templateRoles)) $templateRoles[] = $role;
            }
        }
        return $templateRoles;
    }


    /**
     * Update a view of type 'text' in the database.
     *
     * @param $view
     * @param string $mode
     * @return array
     */
    private static function updateViewText($view, string $mode): array
    {
        if ($mode == 'INSERT') {
            Core::$systemDB->insert("view_text", [
                "id" => $view["id"],
                "value" => $view["value"],
                "link" => $view["link"] ?? null
            ]);

        } else if ($mode == 'UPDATE') {
            Core::$systemDB->update("view_text", [
                "value" => $view["value"],
                "link" => $view["link"] ?? null
            ], ["id" => $view["id"]]);
        }
        return [$view["role"]];
    }

    /**
     * Update a view of type 'image' in the database.
     *
     * @param $view
     * @param string $mode
     * @return array
     */
    private static function updateViewImage($view, string $mode): array
    {
        if ($mode == 'INSERT') {
            Core::$systemDB->insert("view_image", [
                "id" => $view["id"],
                "src" => $view["src"],
                "link" => $view["link"] ?? null
            ]);

        } else if ($mode == 'UPDATE') {
            Core::$systemDB->update("view_image", [
                "src" => $view["src"],
                "link" => $view["link"] ?? null
            ], ["id" => $view["id"]]);
        }
        return [$view["role"]];
    }

    /**
     * Update a view of type 'header' in the database.
     *
     * @param $view
     * @param string $mode
     * @return array
     */
    private static function updateViewHeader($view, string $mode): array
    {
        $imageRoles = self::updateView($view["image"]);
        $titleRoles = self::updateView($view["title"]);

        if ($mode == 'INSERT') {
            Core::$systemDB->insert("view_header", [
                "id" => $view["id"],
                "image" => $view["image"][0]["viewId"],
                "title" => $view["title"][0]["viewId"]
            ]);

        } else if ($mode == 'UPDATE') {
            Core::$systemDB->update("view_header", [
                "image" => $view["image"][0]["viewId"],
                "title" => $view["title"][0]["viewId"]
            ], ["id" => $view["id"]]);
        }

        // Merge view roles
        $viewRoles = [$view["role"]];
        foreach($imageRoles as $imageRole) {
            if (!in_array($imageRole, $viewRoles)) $viewRoles[] = $imageRole;
        }
        foreach($titleRoles as $titleRole) {
            if (!in_array($titleRole, $viewRoles)) $viewRoles[] = $titleRole;
        }
        return $viewRoles;
    }

    /**
     * Update a view of type 'table' in the database.
     *
     * @param $view
     * @param string $mode
     * @return array
     */
    private static function updateViewTable($view, string $mode): array
    {
        $viewRoles = [$view["role"]];

        // Clean header rows & body rows
        // This ensures the order is correct if changed
        if ($mode == 'UPDATE') {
            Core::$systemDB->delete("view_table_header", ["id" => $view["id"]]);
            Core::$systemDB->delete("view_table_row", ["id" => $view["id"]]);
        }

        // Update header rows
        foreach ($view["headerRows"] as &$headerRow) {
            $headerRoles = self::updateView($headerRow);
            foreach ($headerRoles as $headerRole){
                if (!in_array($headerRole, $viewRoles)) $viewRoles[] = $headerRole;
            }

            // Insert into 'view_table_header'
            Core::$systemDB->insert("view_table_header", [
                "id" => $view["id"],
                "headerRow" => $headerRow[0]["viewId"],
                "viewIndex" => count(Core::$systemDB->selectMultiple("view_table_header", ["id" => $view["id"]]))
            ]);
        }

        // Update body rows
        foreach ($view["rows"] as &$row) {
            $bodyRoles = self::updateView($row);
            foreach ($bodyRoles as $bodyRole){
                if (!in_array($bodyRole, $viewRoles)) $viewRoles[] = $bodyRole;
            }

            // Insert into 'view_table_row'
            Core::$systemDB->insert("view_table_row", [
                "id" => $view["id"],
                "row" => $row[0]["viewId"],
                "viewIndex" => count(Core::$systemDB->selectMultiple("view_table_row", ["id" => $view["id"]]))
            ]);
        }

        return $viewRoles;
    }

    /**
     * Update a view of type 'block' in the database.
     *
     * @param $view
     * @param string $mode
     * @return array
     */
    private static function updateViewBlock($view, string $mode): array
    {
        $viewRoles = [$view["role"]];

        if (isset($view["children"])) {
            // Clean children
            // This ensures the order is correct if changed
            if ($mode == 'UPDATE')
                Core::$systemDB->delete("view_parent", ["parentId" => $view["id"]]);

            foreach ($view["children"] as &$child) {
                $childRoles = self::updateView($child);
                foreach ($childRoles as $childRole){
                    if (!in_array($childRole, $viewRoles)) $viewRoles[] = $childRole;
                }

                // Insert into 'view_parent'
                Core::$systemDB->insert("view_parent", [
                    "parentId" => $view["id"],
                    "childId" => $child[0]["viewId"],   // viewId is the same for all aspects of child
                    "viewIndex" => count(Core::$systemDB->selectMultiple("view_parent", ["parentId" => $view["id"]]))
                ]);
            }
        }

        return $viewRoles;
    }

    /**
     * Update a view of type 'row' in the database.
     *
     * @param $view
     * @param string $mode
     * @return array
     */
    private static function updateViewRow($view, string $mode): array
    {
        // Similar to view type 'block',
        // they both only carry children views
        return self::updateViewBlock($view, $mode);
    }


    /**
     * Deletes view from database.
     * This includes all aspects of the view and also children.
     *
     * @param $view
     */
    public static function deleteView($view)
    {
        foreach ($view as $aspect) {
            // Delete from database depending on type
            if ($aspect["type"] == 'text') self::deleteViewText($aspect);
            if ($aspect["type"] == 'image') self::deleteViewImage($aspect);
            if ($aspect["type"] == 'header') self::deleteViewHeader($aspect);
            if ($aspect["type"] == 'table') self::deleteViewTable($aspect);
            if ($aspect["type"] == 'block') self::deleteViewBlock($aspect);
            if ($aspect["type"] == 'row') self::deleteViewRow($aspect);
            // NOTE: insert here other types of views

            Core::$systemDB->delete('view', ["id" => $aspect["id"]]);
        }
    }

    /**
     * Delete view of type 'text' from database.
     *
     * @param $view
     */
    private static function deleteViewText($view)
    {
        Core::$systemDB->delete("view_text", ["id" => $view["id"]]);
    }

    /**
     * Delete view of type 'image' from database.
     *
     * @param $view
     */
    private static function deleteViewImage($view)
    {
        Core::$systemDB->delete("view_image", ["id" => $view["id"]]);
    }

    /**
     * Delete view of type 'header' from database.
     *
     * @param $view
     */
    private static function deleteViewHeader($view)
    {
        self::buildViewHeader($view);
        self::deleteView($view["image"]);
        self::deleteView($view["title"]);

        Core::$systemDB->delete("view_header", ["id" => $view["id"]]);
    }

    /**
     * Delete view of type 'table' from database.
     *
     * @param $view
     */
    private static function deleteViewTable($view)
    {
        self::buildViewTable($view);

        // Delete header rows
        foreach ($view["headerRows"] as $headerRow) {
            self::deleteView($headerRow);
            Core::$systemDB->delete("view_table_header", ["headerRow" => $headerRow[0]["viewId"]]);
        }

        // Delete body rows
        foreach ($view["rows"] as $row) {
            self::deleteView($row);
            Core::$systemDB->delete("view_table_row", ["row" => $row[0]["viewId"]]);
        }
    }

    /**
     * Delete view of type 'block' from database.
     *
     * @param $view
     */
    private static function deleteViewBlock($view)
    {
        $children = Core::$systemDB->selectMultiple(
            "view v left join view_parent vp on v.viewId=vp.childId",
            ["parentId" => $view["id"]],
            "v.*"
        );

        if (!empty($children)) {
            Core::$systemDB->delete("view_parent", ["parentId" => $view["id"]]);
            foreach ($children as $child) {
                self::deleteView([$child]); // NOTE: don't need to group per aspect
            }
        }
    }

    /**
     * Delete view of type 'row' from database.
     *
     * @param $view
     */
    private static function deleteViewRow($view)
    {
        // Similar to view type 'block',
        // they both only carry children views
        self::deleteViewBlock($view);
    }



    /*** ---------------------------------------------------- ***/
    /*** ----------------- Rendering views ------------------ ***/
    /*** -------------- ( <- from database ) ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Builds a view to be rendered, either on a page or on editor.
     *
     * @param $view
     * @param $rolesHierarchy
     * @param $viewParams (optional)
     * @param bool $edit (optional)
     */
    public static function renderView(&$view, $rolesHierarchy, $viewParams = null, bool $edit = false)
    {
        // Pick a specific aspect and build it
        self::buildView($view, false, $rolesHierarchy, $edit);
        if (count($view) == 1) $view = $view[0];
        else if (count($view) == 0) {
            if (!$edit) API::error('There\'s no aspect to render for current view and roles.');
            else $view = null;
        }
        else if (count($view) > 1) API::error('Should have only one aspect but got more.');

        if (!$edit) {
            // Populate view with actual data, not just view specifications.
            self::parseView($view);
            self::processView($view, $viewParams);
        }
    }


    /**
     * Builds a view coming from database.
     * This includes all aspects of the view and also children.
     *
     * If roles hierarchy are set, it will build a view with
     * only one aspect - the most specific one.
     *
     * @param $view
     * @param bool $toExport
     * @param null $rolesHierarchy
     * @param bool $edit
     */
    public static function buildView(&$view, bool $toExport = false, $rolesHierarchy = null, bool $edit = false) {
        if ($rolesHierarchy)
            self::filterViewByRoles($view, $rolesHierarchy);

        foreach ($view as &$aspect) {
            self::prepareViewFromDatabase($aspect);
            if ($aspect["type"] == 'text') self::buildViewText($aspect);
            if ($aspect["type"] == 'image') self::buildViewImage($aspect);
            if ($aspect["type"] == 'header') self::buildViewHeader($aspect, $toExport, $rolesHierarchy, $edit);
            if ($aspect["type"] == 'table') self::buildViewTable($aspect, $toExport, $rolesHierarchy, $edit);
            if ($aspect["type"] == 'block') self::buildViewBlock($aspect, $toExport, $rolesHierarchy, $edit);
            if ($aspect["type"] == 'row') self::buildViewRow($aspect, $toExport, $rolesHierarchy, $edit);
            // NOTE: insert here other types of views

            if ($toExport) {
                // Delete ids
                unset($aspect["id"]);
                unset($aspect["viewId"]);
                unset($aspect["parentId"]);
            }

            if ($edit) $aspect["edit"] = true;

            // Filter null values
            $aspect = array_filter($aspect, function ($value) { return !is_null($value); });
        }

        if ($rolesHierarchy && count($view) > 1)
            API::error('Should have only one aspect but got more.');
    }

    /**
     * Build a view of type 'text' from the database.
     *
     * @param $view
     */
    public static function buildViewText(&$view)
    {
        $viewText = Core::$systemDB->select("view_text", ["id" => $view["id"]]);
        $view = array_merge($view, $viewText);
    }

    /**
     * Build a view of type 'image' from the database.
     *
     * @param $view
     */
    public static function buildViewImage(&$view)
    {
        $viewImage = Core::$systemDB->select("view_image", ["id" => $view["id"]]);
        $view = array_merge($view, $viewImage);
    }

    /**
     * Build a view of type 'header' from the database.
     *
     * @param $view
     * @param bool $toExport
     * @param null $rolesHierarchy
     * @param bool $edit
     */
    public static function buildViewHeader(&$view, bool $toExport = false, $rolesHierarchy = null, bool $edit = false)
    {
        $viewHeader = Core::$systemDB->select("view_header", ["id" => $view["id"]]);
        $viewImage = Views::getViewByViewId($viewHeader["image"]);
        $viewTitle = Views::getViewByViewId($viewHeader["title"]);

        self::buildView($viewImage, $toExport, $rolesHierarchy, $edit);
        if (count($viewImage) == 0)
            API::error('There\'s no aspect for header image for current view and roles.');
        if ($rolesHierarchy) $viewImage = $viewImage[0];
        $view["image"] = $viewImage;

        self::buildView($viewTitle, $toExport, $rolesHierarchy, $edit);
        if (count($viewTitle) == 0)
            API::error('There\'s no aspect for header title for current view and roles.');
        if ($rolesHierarchy) $viewTitle = $viewTitle[0];
        $view["title"] = $viewTitle;
    }

    /**
     * Build a view of type 'table' from the database.
     *
     * @param $view
     * @param bool $toExport
     * @param null $rolesHierarchy
     * @param bool $edit
     */
    public static function buildViewTable(&$view, bool $toExport = false, $rolesHierarchy = null, bool $edit = false)
    {
        $viewTableHeaderRows = Core::$systemDB->selectMultiple(
            "view v left join view_table_header th on v.viewId=th.headerRow",
            ["th.id" => $view["id"]],
            "v.*, th.viewIndex",
            "viewIndex ASC"
        );
        $viewTableRows = Core::$systemDB->selectMultiple(
            "view v left join view_table_row tr on v.viewId=tr.row",
            ["tr.id" => $view["id"]],
            "v.*, tr.viewIndex",
            "viewIndex ASC"
        );

        $viewTableHeaderRows = self::groupViewsByAspect($viewTableHeaderRows, ["viewIndex"]);
        $viewTableRows = self::groupViewsByAspect($viewTableRows, ["viewIndex"]);

        foreach ($viewTableHeaderRows as &$headerRow) {
            self::buildView($headerRow, $toExport, $rolesHierarchy, $edit);
            if (count($headerRow) != 0) {
                if ($rolesHierarchy) $headerRow = $headerRow[0];
                $view["headerRows"][] = $headerRow;
            }
        }

        foreach ($viewTableRows as &$row) {
            self::buildView($row, $toExport, $rolesHierarchy, $edit);
            if (count($row) != 0) {
                if ($rolesHierarchy) $row = $row[0];
                $view["rows"][] = $row;
            }
        }
    }

    /**
     * Build a view of type 'block' from the database.
     *
     * @param $view
     * @param bool $toExport
     * @param null $rolesHierarchy
     * @param bool $edit
     */
    public static function buildViewBlock(&$view, bool $toExport = false, $rolesHierarchy = null, bool $edit = false)
    {
        $children = Core::$systemDB->selectMultiple(
            "view v left join view_parent vp on v.viewId=vp.childId",
            ["parentId" => $view["id"]],
            "v.*, vp.viewIndex",
            "viewIndex ASC"
        );

        if (!empty($children)) {
            $childrenViews = self::groupViewsByAspect($children, ["viewIndex"]);
            foreach ($childrenViews as &$childView) {
                self::buildView($childView, $toExport, $rolesHierarchy, $edit);
                if (count($childView) != 0) {
                    if ($rolesHierarchy) $childView = $childView[0];
                    $view["children"][] = $childView;
                }
            }
        }
    }

    /**
     * Build a view of type 'row' from the database.
     *
     * @param $view
     * @param bool $toExport
     * @param null $rolesHierarchy
     * @param bool $edit
     */
    public static function buildViewRow(&$view, bool $toExport = false, $rolesHierarchy = null, bool $edit = false)
    {
        // Similar to view type 'block',
        // they both only carry children views
        self::buildViewBlock($view, $toExport, $rolesHierarchy, $edit);
    }



    /*** ---------------------------------------------------- ***/
    /*** ------------------ Parsing views ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function parseView(&$view)
    {
        if (isset($view['style'])) self::parseSelf($view['style']);
        if (isset($view['cssId'])) self::parseSelf($view['cssId']);
        if (isset($view['class'])) self::parseSelf($view['class']);
        if (isset($view['label'])) self::parseSelf($view['label']);

        if (isset($view['events'])) self::parseEvents($view);
        if (isset($view['loopData'])) self::parseSelf($view['loopData']);
        if (isset($view['variables'])) self::parseVariables($view);
        if (isset($view['visibilityCondition'])) self::parseVisibilityCondition($view);

        self::parseViewType($view["type"], $view);
    }

    public static function parseViewType($type, &...$args)
    {
        if (!array_key_exists($type, Dictionary::$viewTypes))
            API::error('Part ' . $type . ' is not defined');

        $func = Dictionary::$viewTypes[$type][0];
        if ($func != null)
            $func(...$args);
    }

    public static function parse($exp)
    {
        static $parser;
        if ($parser == null) $parser = new ExpressionEvaluatorBase();
        if (trim($exp) == '') return new ValueNode('');

        return $parser->parse($exp);
    }

    public static function parseSelf(&$exp)
    {
        $exp = self::parse($exp);
    }

    public static function parseVariables(&$view)
    {
        foreach ($view['variables'] as $k => &$v) {
            self::parseSelf($v['value']);
        }
    }

    public static function parseEvents(&$view)
    {
        foreach ($view['events'] as $trigger => &$event) {
            self::parseSelf($event);
        }
    }

    public static function parseVisibilityCondition(&$view)
    {
        $visibilityType = $view['visibilityType'] ?? null;

        if (!$visibilityType || $visibilityType == 'visible' || isset($view['loopData'])) unset($view['visibilityCondition']);
        else self::parseSelf($view['visibilityCondition']);
    }



    /*** ---------------------------------------------------- ***/
    /*** ----------------- Processing views ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function processView(&$view, $viewParams)
    {
        $visitor = new EvaluateVisitor($viewParams);

        if (isset($view['style'])) self::processSelf($view['style'], $visitor);
        if (isset($view['cssId'])) self::processSelf($view['cssId'], $visitor);
        if (isset($view['class'])) self::processSelf($view['class'], $visitor);
        if (isset($view['label'])) self::processSelf($view['label'], $visitor);

        if (isset($view['events'])) self::processEvents($view, $visitor);
        if (isset($view['loopData'])) self::processLoop($view, $viewParams, $visitor);
        if (isset($view['variables'])) self::processVariables($view, $viewParams, $visitor);
        if (isset($view['visibilityCondition'])) self::processVisibilityCondition($view, $visitor);

        self::processViewType($view["type"], $view, $viewParams, $visitor);
    }

    public static function processViewType($type, &...$args)
    {
        if (!array_key_exists($type, Dictionary::$viewTypes))
            API::error('Part ' . $type . ' is not defined');

        $func = Dictionary::$viewTypes[$type][1];
        if ($func != null)
            $func(...$args);
    }

    public static function processSelf(&$view, $visitor)
    {
        $view = $view->accept($visitor)->getValue();
    }

    public static function processVariables(&$view, $viewParams, $visitor)
    {
        $actualVisitor = $visitor;
        $params = $viewParams;
        foreach ($view['variables'] as $k => &$v) {
            $params[$k] = $v['value']->accept($actualVisitor)->getValue();
            if ($params != $viewParams)
                $actualVisitor = new EvaluateVisitor($params);
        }
    }

    public static function processEvents(&$view, $visitor)
    {
        foreach ($view['events'] as $trigger => &$event) {
            $event = $event->accept($visitor)->getValue();
        }
    }

    public static function processVisibilityCondition(&$view, $visitor): bool
    {
        if (!$view["visibilityType"] || $view["visibilityType"] == 'visible') {
            return true;

        } else {
            $ret = false;
            if ($view['visibilityCondition']->accept($visitor)->getValue() == true)
                $ret = true;
            unset($view['visibilityCondition']);
            return $ret;
        }
    }

    public static function processLoop(&$container, $viewParams, $visitor)
    {
        foreach ($container as &$child) {
            $repeatKey = "item";
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

                $paramsForEvaluator = array_merge($viewParams, $loopParam, array("index" => $p));

                self::processView($child, $paramsForEvaluator);
            }
        }
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Utilities -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Prepare view params for database.
     * This is intended for params that are not basic types such
     * as string or integers and need to be encoded first.
     *
     * @param $view
     */
    private static function prepareViewForDatabase(&$view)
    {
        // Params that need to be encoded
        $toEncode = ['events', 'loopData', 'variables', 'visibilityCondition'];
        foreach ($toEncode as $param) {
            if (isset($view[$param]))
                $view[$param] = json_encode($view[$param]);
        }
    }

    /**
     * Prepare view params from database.
     * This is intended for params that are not basic types such
     * as string or integers and need to be decoded.
     *
     * @param $view
     */
    private static function prepareViewFromDatabase(&$view)
    {
        // Params that need to be decoded
        $toDecode = ['events', 'loopData', 'variables', 'visibilityCondition'];
        foreach ($toDecode as $param) {
            if (isset($view[$param]))
                $view[$param] = json_decode($view[$param]);
        }
    }

    /**
     * Filters view by rolesHierarchy, where the more specific
     * roles come first than the more general ones
     * ([0]=>role more specific, [1]=>role less specific...).
     *
     * @example ['Teacher_Assistant', 'Teacher', 'Default']
     * @example ['Teacher_Assistant>Teacher_Assistant',
     *           'Teacher>Teacher_Assistant',
     *           'Default>Teacher_Assistant',
     *           'Teacher_Assistant>Teacher',
     *           'Teacher>Teacher',
     *           'Default>Teacher',
     *           'Teacher_Assistant>Default',
     *           'Teacher>Default',
     *           'Default>Default'
     *          ]
     *
     * @param $view
     * @param array $rolesHierarchy
     */
    private static function filterViewByRoles(&$view, array $rolesHierarchy)
    {
        $roleType = Views::getRoleType($rolesHierarchy[0]);

        // Pick the most specific aspect available on view
        $foundAspect = false;
        foreach ($rolesHierarchy as $role) {
            foreach ($view as $aspect) {
                $viewerRole = Views::splitRole($aspect["role"])["viewerRole"];
                $aspectRole = $viewerRole;

                if ($roleType == 'ROLE_INTERACTION') {
                    $userRole = Views::splitRole($aspect["role"])["userRole"];
                    $aspectRole = $userRole . '>' . $viewerRole;
                }

                if ($aspectRole == $role) {
                    $foundAspect = true;
                    $view = [$aspect];
                    break;
                }
            }
            if ($foundAspect) break;
        }

        // No aspect available w/ current rolesHierarchy
        if (!$foundAspect) $view = [];
    }

    /**
     * Group views from same aspect.
     *
     * @param $views
     * @param array $unsetParams
     * @return array
     */
    private static function groupViewsByAspect(&$views, array $unsetParams = []): array
    {
        $groupedViews = [];
        foreach ($views as &$view) {
            foreach ($unsetParams as $param) {
                unset($view[$param]);
            }
            $groupedViews[$view["viewId"]][] = $view;
        }
        return $groupedViews;
    }

    public static function callFunction($funcLib, $funcName, $args, $context = null)
    {
        if (!$funcLib) {
            $function = Core::$systemDB->select("dictionary_function", ["libraryId" => null, "keyword" => $funcName]);
            if ($function) {
                $fun = Dictionary::$viewFunctions[$function["id"]];
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
                    $fun = Dictionary::$viewFunctions[$function["id"]];
                } else if ($funcLibrary["libraryId"] == NULL) {
                    $fun = Dictionary::$viewFunctions[$funcLibrary["id"]];
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




    // TODO: refactor to new structure (everything underneath)

    /**
     * Receives view and updates the database with its info.
     * Propagates changes in the main view to all its children.
     * $basicUpdate -> you only update basic view attributes
     * (ignores view parameters and deletion of view parts),
     * used for change in aspectclass.
     *
     * @param $view
     * @param $courseId
     * @param bool $basicUpdate
     * @param bool $ignoreIds
     * @param string|null $templateName
     * @param bool $fromModule
     * @param null $partsInDB
     */
    public static function updateViewAndChildren($view, $courseId, bool $basicUpdate = false, bool $ignoreIds = false, string $templateName = null, bool $fromModule = false, &$partsInDB = null)
    {
        if ($view["parentId"] == null) {
            $copy = self::makeCleanViewCopy($view);

            if (array_key_exists("id", $view) && !$ignoreIds) { //already in DB, may need update
                Core::$systemDB->update("view", $copy, ["id" => $view["id"]]);
                if (!$basicUpdate) {
                    unset($partsInDB[$view["id"]]);
                }
            } else if (array_key_exists("viewId", $view) && !empty(Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]])) && !$fromModule) {
                // if we save twice in view editor, to not insert again
                $id = Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]], "id");
                Core::$systemDB->update("view", $copy, ["id" => $id]);
                $view["id"] = $id;
            } else { //not in DB, insert it
                Core::$systemDB->insert("view", $copy);
                $viewId = Core::$systemDB->getLastId();
                $view["id"] = $viewId;
                if (!isset($copy["viewId"])) {
                    $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $viewId], '*', null, [['id', $viewId]]));
                    if ($viewIdExists) {
                        $lastViewId = intval(end(Core::$systemDB->selectMultiple("view", null, 'viewId', 'viewId asc'))['viewId']) + 1;
                        Core::$systemDB->update("view", ["viewId" => $lastViewId], ['id' => $viewId]);
                        $view["viewId"] = $lastViewId;
                    } else {
                        Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                        $view["viewId"] = $viewId;
                    }
                } else {
                    $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $copy["viewId"]], '*', null, [['id', $viewId]]));
                    if ($viewIdExists && $templateName != null && !$fromModule) { //we only want to change viewId if we are saving as template
                        Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                        $view["viewId"] = $viewId;
                    } else if ($viewIdExists  && $fromModule && empty(Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $copy['viewId'], 'course' => $courseId]))) {
                        // if we are creating in other course, we want to change
                        Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                        $view["viewId"] = $viewId;
                    } else
                        $view["viewId"] = $copy["viewId"];
                }
                if (($templateName != null && !$fromModule) || ($fromModule  && empty(Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $view['viewId'], 'course' => $courseId])))) {
                    $templateId = Core::$systemDB->select("template", ["name" => $templateName, 'course' => $courseId], "id");
                    Core::$systemDB->insert("view_template", ["viewId" => $view["viewId"], "templateId" => $templateId]);
                }
            }
        }
        if ($view["parentId"] != null) {
            //insert/update views
            $copy = self::makeCleanViewCopy($view);

            if (array_key_exists("id", $view) && !$ignoreIds) { //already in DB, may need update
                Core::$systemDB->update("view", $copy, ["id" => $view["id"]]);
                if (!$basicUpdate) {
                    unset($partsInDB[$view["id"]]);
                }
            } else if (array_key_exists("viewId", $view) && !empty(Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]])) && !$fromModule) {
                // if we save twice in view editor, to not insert again
                $id = Core::$systemDB->select("view", ["viewId" => $copy["viewId"], "role" => $copy["role"]], "id");
                Core::$systemDB->update("view", $copy, ["id" => $id]);
                $view["id"] = $id;
            } else {
                if (!isset($view["isTemplateRef"])) { //not in DB, insert it
                    Core::$systemDB->insert("view", $copy);
                    $viewId = Core::$systemDB->getLastId();
                    $view["id"] = $viewId;
                    if (!isset($copy["viewId"])) {
                        $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $viewId], '*', null, [['id', $viewId]]));
                        if ($viewIdExists) {
                            $tmp = Core::$systemDB->selectMultiple("view", null, 'viewId', 'viewId asc');
                            $lastViewId = intval(end($tmp)['viewId']) + 1;
                            Core::$systemDB->update("view", ["viewId" => $lastViewId], ['id' => $viewId]);
                            $view["viewId"] = $lastViewId;
                        } else {
                            Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                            $view["viewId"] = $viewId;
                        }
                    } else {
                        $viewIdExists = !empty(Core::$systemDB->selectMultiple("view", ["viewId" => $copy["viewId"]], '*', null, [['id', $viewId]]));
                        $parents = array_column(Core::$systemDB->selectMultiple("view_parent", ["childId" => $copy["viewId"]], "parentId"), "parentId");
                        $siblings = in_array($view["parentId"], $parents);
                        if ($viewIdExists && (!$siblings || $view["viewIndex"] != "0")) {
                            Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
                            $view["viewId"] = $viewId;
                        } else
                            //we can have several views with the same viewId if they are aspects of the same view so we want to keep it
                            $view["viewId"] = $copy["viewId"];
                    }
                }
                if (empty(Core::$systemDB->select("view_parent", [
                    "parentId" => $view["parentId"],
                    "childId" => $view["viewId"]
                ])))
                    Core::$systemDB->insert("view_parent", [
                        "parentId" => $view["parentId"],
                        "childId" => $view["viewId"],
                        "viewIndex" => $view["viewIndex"]
                    ]);

                // if ($view["partType"] == "templateRef") {
                //     Core::$systemDB->insert("view_template", ["viewId" => $view["id"], "templateId" => $view["templateId"]]);
                // }

            }
        }

        if ($view["partType"] == "table") {
            foreach ($view["headerRows"] as $headRow) {
                $rowPart = $headRow;
                $rowPart["partType"] = "headerRow";
                foreach ($headRow["values"] as $rowElement) {
                    $rowPart["children"][] = $rowElement["value"];
                }
                unset($rowPart["values"]);
                $view["children"][] = $rowPart;
            }
            foreach ($view["rows"] as $row) {
                $rowPart = $row;
                $rowPart["partType"] = "row";
                foreach ($row["values"] as $rowElement) {
                    $rowPart["children"][] = $rowElement["value"];
                }
                unset($rowPart["values"]);
                $view["children"][] = $rowPart;
            }
            unset($view["rows"]);
            unset($view["headerRows"]);

            //readjust keys to start at 0
            $view["children"] = array_combine(range(0, count($view["children"]) - 1), $view["children"]);
        }
        if (array_key_exists("children", $view)) {
            $children = null;
            if (!$basicUpdate) {
                //all the children of the view except headers which are dealt with later
                $children = Core::$systemDB->selectMultiple("view v join view_parent vp on v.viewId=vp.childId", ["parentId" => $view["id"]], "*", null, [["partType", "header"]]);
                $children = array_combine(array_column($children, "id"), $children);
            }
            foreach ($view["children"] as $key => &$child) {
                if ($key == 0) {
                    $currentViewId = $child["viewId"];
                    $currentIdx = 0;
                }

                $child["parentId"] = $view["id"];
                if ($child["viewId"] != $currentViewId) {
                    $currentViewId = $child["viewId"];
                    $currentIdx += 1;
                }
                $child["viewIndex"] = $currentIdx;
                self::updateViewAndChildren($child, $courseId, $basicUpdate, $ignoreIds, null, $fromModule, $children);
            }

            if (!$basicUpdate) {
                foreach ($children as $deleted) {
                    //Core::$systemDB->delete("view", ["id" => $deleted["id"]]);
                    self::deleteViews($deleted, true);
                }
            }
        }
        if ($view["partType"] == "block") { //deal with header of block
            $header = Core::$systemDB->select("view join view_parent on viewId=childId", ["parentId" => $view["id"], "partType" => "header", "role" => $view["role"]], "id,viewId");
            if (array_key_exists("header", $view)) { //if there is a header in the updated version
                if (!$basicUpdate && empty($header)) { //insert (header is not in DB)
                    Core::$systemDB->insert("view", [
                        "partType" => "header", "role" => $view["role"]
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

                    Core::$systemDB->insert("view_parent", ["parentId" => $view["id"], "childId" => $headerViewId]);

                    $headerPart = [
                        "role" => $view["role"], "parentId" => $headerId, "viewIndex" => 0
                    ];
                    $image = array_merge($view["header"]["image"], $headerPart);
                    unset($image["id"]);
                    unset($image["viewId"]);

                    self::updateViewAndChildren($image, $courseId, $basicUpdate, $ignoreIds);
                    $headerPart["viewIndex"] = 1;
                    $text = array_merge($view["header"]["title"], $headerPart);
                    unset($text["id"]);
                    unset($text["viewId"]);

                    self::updateViewAndChildren($text, $courseId, $basicUpdate, $ignoreIds);
                } else if (!empty($header)) { //update (header is in DB)
                    //in most cases just updating parameters
                    $headerParts = Core::$systemDB->selectMultiple("view join view_parent on viewId=childId", ["parentId" => $header["id"]]);
                    foreach ($headerParts as $part) {
                        if ($basicUpdate) {
                            Core::$systemDB->update(
                                "view",
                                ["role" => $view["role"]],
                                ["id" => $part["id"]],
                            );
                        } else {
                            if ($part["partType"] == "text")
                                $type = "title";
                            else
                                $type = "image";
                            $part = array_merge($part, self::makeCleanViewCopy($view["header"][$type]));
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
                Core::$systemDB->delete("view_parent", ["childId" => $header["viewId"], "parentId" => $view["id"]]);

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
                    ["role" => $view["role"]],
                    ["viewId" => $header["viewId"]]
                );
            }
        }
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

}
