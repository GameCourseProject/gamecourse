<?php

namespace GameCourse;

use GameCourse\ModuleLoader;
use GameCourse\Core;
use ZipArchive;

class RuleSystem
{
    private $course;
    private $courseId;
    private $rulesdir;
    private $ruletestpath;
    private $ruletestoutput;
    private $templateRulePath = "modules/skills/rules/rule_skill_template_basic.txt";
    private $templateWildcardRulePath = "modules/skills/rules/rule_skill_template_wildcard.txt";
    private $rules = array();
    private $tags = array();

    private $ruleSeparator = "\n\n#########\n\n";
    const ROOT_FOLDER = "/var/www/html/gamecourse/";


    public function __construct($course)
    {
        $this->course = $course;
        $this->courseId = $course->getId();
        $this->rulesdir = $this->course->getCourseDataFolder($this->courseId) . "/rules/";

        $this->ruletestfolder = $this->course->getCourseDataFolder($this->courseId) . "/rule-tests/";
        $this->ruletestpath = $this->ruletestfolder . "rule.txt";
        $this->ruletestoutput = $this->ruletestfolder . "rule-test-output.txt";

        $this->metadatadir = self::ROOT_FOLDER . "autogame/config/config_" . strval($this->courseId) . ".txt";
        $this->availableModules = ModuleLoader::getModules();
        $this->logsfile = self::ROOT_FOLDER . "logs/log_" . strval($this->courseId) . ".txt";
    }

    // -------------- AUTOGAME --------------

    public function resetSocketStatus() {
        $socketInfo = Core::$systemDB->selectMultiple("autogame");
        $running = false;
        foreach ($socketInfo as $row) {
            if ($row["course"] != "0") {
                $running = $running || filter_var($row["isRunning"], FILTER_VALIDATE_BOOLEAN);
            }
        }
        if (!$running) {
            Core::$systemDB->update("autogame", ["isRunning" => "0"], ["course" => "0"]);
            return true;
        }
        return false;
    }

    public function resetCourseStatus() {
        Core::$systemDB->update("autogame", ["isRunning" => "0"], ["course" => $this->courseId]);
    }

    public function setMetadataVariables($variables) {
        $vars = array();
        foreach ($variables as $variable) {
            $line = $variable["var"] . ":" . $variable["val"];
            array_push($vars, $line);
        }
        $varstxt = implode("\n", $vars);
        file_put_contents($this->metadatadir, $varstxt);
    }


    // -------------- GETTERS --------------

    public function getRulesDir() {
        return $this->rulesdir;
    }

    public function getTemplateRulePath() {
        return $this->templateRulePath;
    }

    public function getTemplateWildcardRulePath() {
        return $this->templateWildcardRulePath;
    }

    public function getLastRunDate() {
        $date = Core::$systemDB->select("autogame", ["course" => $this->courseId], "startedRunning");
        return $date;
    }

    public function getTargets() {
        //SELECT user_role.id, role.name, game_course_user.name 
        // FROM user_role left join role on user_role.role=role.id left join game_course_user on user_role.id=game_course_user.id 
        //WHERE user_role.course ="1"
        $response = Core::$systemDB->selectMultiple("user_role left join role on user_role.role=role.id left join game_course_user on user_role.id=game_course_user.id ", ["user_role.course" => $this->courseId, "role.name" => "Student"], "user_role.id, role.name, game_course_user.name");
        return $response;
    }

    public function getGameRulesFuncs() {
        // Gets the information about imported GameRules Functions so that the information
        // can be displayed in the rule editor page
        
        $cmd = "python3 " . self::ROOT_FOLDER . "autogame/get_functions.py " . strval($this->courseId);
        $output = null;
        exec($cmd, $output);
        $funcs = array();
        if ($output != null && sizeof($output) > 0) { 
            $funcs = json_decode($output[0]);
        }
        return $funcs;
    }

    public function getRules() {
        $directoryListing = scandir($this->rulesdir, SCANDIR_SORT_ASCENDING);
        $ruleFileList = preg_grep('~\.(txt)$~i', $directoryListing);

        $this->getTags();
        $this->rules = array();

        foreach ($ruleFileList as $file) {

            $title = explode(" - ", $file);
            if (sizeof($title) != 2) { // ignores rules in the incorrect format
                continue;
            }
            //$precedence = intval($title[0]);
            $ruleFile = trim($title[1]);
            $fileName = $this->rulesdir . $file;
            $txt = file_get_contents($fileName);


            $moduleFileObj = array();
            $moduleFileObj["id"] = trim($ruleFile, ".txt");
            //$moduleFileObj["precedence"] = $precedence;
            $moduleFileObj["filename"] = $file;
            $moduleFileObj["name"] = ucwords($moduleFileObj["id"]);
            $moduleFileObj["rules"] = array();

        
            $rules = $this->splitRules($txt);

            if (empty(trim((end($rules))))) {
                array_pop($rules);
            }

            if (empty(trim((reset($rules))))) {
                array_shift($rules);
            }

            foreach ($rules as $rule) {
                $rule = trim($rule);
                $rule = $this->ruleParser($rule, $ruleFile);
                $rule["rulefile"] = $file;
                array_push($moduleFileObj["rules"], $rule);
            }

            array_push($this->rules, $moduleFileObj);
        }
        return $this->rules;
    }

    public function getFilename($moduleName) {
        $directoryListing = scandir($this->rulesdir, SCANDIR_SORT_ASCENDING);
        $ruleList = preg_grep('~\.(txt)$~i', $directoryListing);
        $partialFilename = $moduleName . ".txt";
        foreach ($ruleList as $file) {
            $name = explode(" - ", $file);
            if ($name[1] == $partialFilename)
                return $file;
        }
        return null;
    }

    public function getNumOfRuleFiles() {
        $directoryListing = scandir($this->rulesdir, SCANDIR_SORT_ASCENDING);
        $ruleFileList = preg_grep('~\.(txt)$~i', $directoryListing);
        return sizeof($ruleFileList);
    }

    public function getRuleNamesFromFile($filename) {
        $txt = file_get_contents($this->rulesdir . $filename);
        $rules = $this->splitRules($txt);

        $names = array();
        foreach ($rules as $rule) {
            $ruletxt = explode("rule: ", $rule);
            if (sizeof($ruletxt) == 2) {
                $rulename = explode("\n", $ruletxt[1]);
                $name = trim($rulename[0]);
                //array_push($names, $name);
                $names[$name] = $rule;
            }
        }
        return $names;
    }

    public function getRuleNamesFromText($txt) {
        $rules = $this->splitRules($txt);

        $names = array();
        foreach ($rules as $rule) {
            $ruletxt = explode("rule: ", $rule);
            if (sizeof($ruletxt) == 2) {
                $rulename = explode("\n", $ruletxt[1]);
                $name = trim($rulename[0]);
                //array_push($names, $name);
                $names[$name] = $rule;
            }
        }
        return $names;
    }

    public function getRulePosition($rule) {
        
        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);
            $text = "rule: " . $rule["name"];
            $position = false; // default case

            $sectionRules = $this->splitRules($txt);
            foreach ($sectionRules as $key => $value) {
                $pos = strpos($value, $rule["name"]);
                if ($pos !== false) {
                    $position = intval($key);
                    break;
                }
            }
            return $position;
        }
    }

    public function getTags() {
        $this->tags = array();
        $tags = array();
        if (file_exists($this->rulesdir . "tags.csv")) {
            $tagstxt = file_get_contents($this->rulesdir . "tags.csv");
            if ($tagstxt != "") {
                $taglines = explode("\n", trim($tagstxt));
                foreach ($taglines as $tag) {
                    $line = explode(",", $tag);
                    $tagobj = array();
                    $tagobj["name"] = trim($line[0]);
                    $tagobj["color"] = trim($line[1]);
                    $tagobj["editing"] = false;
                    array_push($tags, $tagobj);
                }
                $this->tags = $tags;
            }
            return $this->tags;
        }
        else {
            file_put_contents($this->rulesdir . "tags.csv", "");
            return $this->tags;
        }
    } 

    public function getAutoGameStatus() {
        $autogameCourse = Core::$systemDB->select("autogame", ["course" => $this->courseId]);
        $autogameSocket = Core::$systemDB->select("autogame", ["course" => "0"]);
        $autogame = array();
        array_push($autogame, $autogameSocket);
        array_push($autogame, $autogameCourse);
        return $autogame;
    }

    public function getAutoGameMetadata() {
        $txt = file_get_contents($this->metadatadir); // TO DO move metadata to /course_data folder
        $vars = array();
        if (trim($txt) != "") {    
            $lines = explode("\n", $txt);
            
            foreach ($lines as $line) {
                $vals = explode(":", $line);
                $val = array();
                if (sizeof($vals) == 2) { 
                    $val["var"] = $vals[0];
                    $val["val"] = $vals[1];
                    array_push($vars, $val);
                }
            }
        } 
        return $vars;
    }

    public function getLogs() {
        $log = "Error: Log file does not exist.";
        if (file_exists($this->logsfile)) {
            $log = file_get_contents($this->logsfile);
        }
        return $log;
    }


    // -------------- EXTRA --------------

    public function ruleFileExists($rulefile, $strict = true) {
        // check if a rule file for a module or type exists
        $directoryListing = scandir($this->rulesdir, SCANDIR_SORT_ASCENDING);
        $ruleList = preg_grep('~\.(txt)$~i', $directoryListing);
        if ($strict) {
            // if the filename includes precedence
            return in_array($rulefile, array_values($ruleList));
        }
        else {
            // if filename does not include precedence but still want to search for a filename
            foreach ($ruleList as $index => $rule) {
                $name = explode(" - ", $rule);
                if (sizeof($name) == 2) {
                    if ($name[1] == $rulefile)
                        return $rule;
                }
            }
            return null;
        }
    }

    public function splitRules($text) { 
        return explode($this->ruleSeparator, $text);
    }

    public function joinRules($rules) {
        return implode($this->ruleSeparator, $rules);
    }

    public function fixPrecedences() {
        // fixes all precedences, eg when a rule is deleted
        $directoryListing = scandir($this->rulesdir, SCANDIR_SORT_ASCENDING);
        $ruleFileList = preg_grep('~\.(txt)$~i', $directoryListing);

        $precedence = 1;
        foreach ($ruleFileList as $file) {
            $filenameSplit = explode(" - ", $file);
            $filenameSplit[0] = strval($precedence);
            $filename = implode(" - ", $filenameSplit);

            rename($this->rulesdir . $file, $this->rulesdir . $filename);            
            $precedence += 1;
        }
    }

    public function fixSkillOrder() {
        // TO DO
    }


    public function ruleParser($txt, $fileName) {
        $rows = explode("\n", $txt);
        
        $ruleClean = $ruleDescription = $ruleWhen = $ruleThen = "";
        $ruleTags = [];
        $ruleNameFound = $ruleTagsFound = $ruleDescriptionFound = $ruleDescriptionEnded = $ruleWhenFound = $ruleThenFound = false;
        $ruleActive = true ;

        foreach ($rows as $row) {
            $row = trim($row);
            if ((substr($row, 0, 5) === "rule:")) {
                $ruleClean = $row . "\n";
                $line = explode(":", $row);
                $ruleName = trim($line[1]);
                $ruleNameFound = true;
                continue;
            }

            if ($ruleNameFound && !$ruleWhenFound && !$ruleDescriptionEnded) {
                $row = trim($row);
                if ((substr($row, 0, 8) === "INACTIVE")) {
                    $ruleActive = false;
                }

                if ((substr($row, 0, 5) === "tags:")) {
                    $ruleTagsFound = true;
                    $tagline = trim(substr($row, 5));
                    $ruleTags = array_map('trim', explode(",", $tagline));
                }

                if ((substr($row, 0, 1) === "#")) {
                    $ruleClean .= $row . "\n";
                    $ruleDescription .= trim($row, " \n\r\t\v\0#") . "\n";
                    $ruleDescriptionFound = true;
                    continue;
                }    
            }

            if ($ruleDescriptionFound && !$ruleDescriptionEnded) {
                if (!(substr($row, 0, 1) === "#")) {
                    $ruleDescriptionEnded = true;
                }
            }

            if ($ruleNameFound && !$ruleWhenFound) {
                $row = trim($row);
                if ((substr($row, 0, 5) === "when:")) {
                    $ruleClean .= $row . "\n";
                    $ruleWhenFound = true;
                    continue;
                }
            }

            if ($ruleNameFound && $ruleWhenFound && !$ruleThenFound) {
                $row = trim($row);
                if (strlen($row) != 0) {
                    if (!(substr($row, 0, 5) === "then:")) {
                        # if then clause hasn't been found
                        $ruleClean .= "\t" . $row . "\n";
                        $ruleWhen .=  $row . "\n";
                    }
                    else {
                        # if then clause has been found
                        $ruleClean .= $row . "\n";
                        $ruleThenFound = true;
                        continue;
                    }
                }
            }

            if ($ruleThenFound) {
                $row = trim($row);
                $ruleClean .= "\t" . $row . "\n";
                $ruleThen .=  $row . "\n";
            }
        }

        $ruleThen = trim($ruleThen, "\n");
        $ruleClean = trim($ruleClean);

        $ruleModule = str_replace(".txt", "", $fileName);

        if (sizeof($ruleTags) > 0) {
            $ruleTagsParsed = array();
            foreach ($ruleTags as $ruleTag) {
                if (in_array($ruleTag, array_column($this->tags, 'name'))) {
                    $pos = array_search($ruleTag, array_column($this->tags, 'name'));
                    $newTag = array();
                    $newTag["name"] = $this->tags[$pos]["name"];
                    $newTag["color"] = $this->tags[$pos]["color"]; // finish
                    array_push($ruleTagsParsed, $newTag);
                }
            }
        }
        else {
            $ruleTagsParsed = array();
        } 

        $rule = array(
            'name' => $ruleName,
            'active' => $ruleActive,
            'module' => $ruleModule,
            'tags' => $ruleTagsParsed,
            'description' => $ruleDescription,
            'when' => $ruleWhen,
            'then' => $ruleThen,
            'filename' => $fileName
        );
        return $rule;
    }


    // -------------- RULE TESTING --------------

    public function writeTestRule($rule) {
        // writes the rule to be tested to a txt file
        mkdir($this->ruletestfolder);
        file_put_contents($this->ruletestpath, $rule);
    }

    public function clearRuleOutput() {
        // clears the output file used by the rule testing mechanism to
        // return errors
        Module::rrmdir($this->ruletestfolder);
    }

    // -------------- RULE OPERATIONS --------------
   
    public function changeRuleName($rule, $newName) {
        $rows = explode("\n", $rule);
        foreach ($rows as $key => $row) {
            $row = trim($row);
            if ((substr($row, 0, 5) === "rule:")) {
                $ruleClean = $row . "\n";
                $line = explode(":", $row);
                $line[1] = $newName;
                $newLine = implode(": ", $line);
                $row = $newLine;
                break;
            }
        }
        $rows[$key] = $newLine;
        $newRule = implode("\n", $rows);
        return $newRule;
    }

    public function changeRuleStatus($rule, $active) {
        $rows = explode("\n", $rule);
        if ((substr($rows[1], 0, 8) === "INACTIVE")) {
            if (!$active) {
                return $rule;
            }
            else {
                array_splice($rows, 1, 1);
                $newRule = implode("\n", $rows);
                return $newRule;
            }
        }
        else {
            if ($active) {
                return $rule;
            }
            else {
                array_splice($rows, 1, 0, "INACTIVE");
                $newRule = implode("\n", $rows);
                return $newRule;
            }
        } 
    }

    public function updateTags($tags) {
        $this->getTags();
        foreach ($tags as $tag) {
            if (!in_array($tag["name"], array_column($this->tags, "name"))) {
                $this->addTag($tag);
            }
        }
    }

    public function editTags($tags) {
        $tagsArray = array();
        foreach ($tags as $tag) {
            $singleTag = trim($tag["name"]) . "," . trim($tag["color"]);
            array_push($tagsArray, $singleTag);
        }
        $tagsTxt = implode("\n", $tagsArray);
        file_put_contents($this->rulesdir . "tags.csv", $tagsTxt);
    }

    public function addTag($newTag) {
        $tagstxt = file_get_contents($this->rulesdir . "tags.csv");
        $tags = explode("\n", trim($tagstxt));
        array_push($tags, implode("," , $newTag));
        $txt = implode("\n", $tags);
        file_put_contents($this->rulesdir . "tags.csv", $txt);
    }

    public function swapTags($modules) {
        foreach ($modules as $i => $mod) {
            $this->removeRules($mod["filename"]);
            foreach ($mod["rules"] as $j => $rule) {
                $ruletxt = $this->generateRule($rule);
                $this->addRule($ruletxt, null, $rule);
            }
        }
    }

    public function generateRule($rule) {
        // generates a rule based on a rule object
        // retrieved from front end

        $ruletxt = "";
        $ruletxt .= "rule: " . $rule["name"] . "\n";
        if (!$rule["active"])
            $ruletxt .= "INACTIVE" . "\n";
        if (!empty($rule["tags"]))
            $ruletxt .= "tags: " . implode(", ", array_column($rule["tags"], "name")) . "\n";
        if ($rule["description"] != "") {
            $descLines = explode("\n", $rule["description"]);
            foreach ($descLines as $line) {
                if ($line != "")
                    $ruletxt .= "# " . $line . "\n";
            }
        }
        
            $ruletxt .= "\twhen:\n";
        if ($rule["when"] != "") {
            $whenLines = explode("\n", $rule["when"]);
            foreach ($whenLines as $line) {
                if ($line != "")
                    $ruletxt .= "\t\t" . $line . "\n";
            }
        }

        $ruletxt .= "\tthen:\n";
        if ($rule["then"] != "") {
            $thenLines = explode("\n", $rule["then"]);
            foreach ($thenLines as $line) {
                if ($line != "")
                    $ruletxt .= "\t\t" . $line . "\n";
            }
        }
        return $ruletxt;
    }

    public function replaceRule($ruletxt, $position, $rule) {
        
        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);

            $sectionRules = $this->splitRules($txt);
            $sectionRules[intval($position)] = $ruletxt;
            $content = $this->joinRules($sectionRules);

            $file = file_put_contents($this->rulesdir . $rule["rulefile"], $content);
        }
    }






    // Rule Actions

    public function addRule($ruleTxt, $position, $rule) {

        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);
            
            $sectionRules = $this->splitRules($txt);
            if ($position != null)
                array_splice($sectionRules, $position, 0, $ruleTxt);
            else
                array_push($sectionRules, $ruleTxt);
            $content = $this->joinRules($sectionRules);
            $file = file_put_contents($this->rulesdir . $rule["rulefile"], $content);
        }
    }


    public function removeRule($rule, $position) {
        
        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);

            $sectionRules = $this->splitRules($txt);
            array_splice($sectionRules, $position, 1);
            $content = $this->joinRules($sectionRules);

            $file = file_put_contents($this->rulesdir . $rule["rulefile"], $content);
        }
    }


    public function duplicateRule($rule, $position) {
        
        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);

            $sectionRules = $this->splitRules($txt);
            
            $newRule = $this->changeRuleName($sectionRules[intval($position)], $rule["name"]);
            $duplicateRule = $this->changeRuleStatus($newRule, false);
            array_splice($sectionRules, $position + 1, 0, $duplicateRule);
            
            $content = $this->joinRules($sectionRules);
            
            $file = file_put_contents($this->rulesdir . $rule["rulefile"], $content);
        }
    }

    public function toggleRule($rule, $position) {
        
        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);
            $sectionRules = $this->splitRules($txt);
            
            $ruleLines = explode("\n", $sectionRules[intval($position)]);
            if (substr($ruleLines[1], 0, 8) === "INACTIVE") {
                array_splice($ruleLines, 1, 1);
            }
            else {
                array_splice($ruleLines, 1, 0, "INACTIVE" );
            }
            $sectionRules[intval($position)] = implode("\n", $ruleLines);

            $content = $this->joinRules($sectionRules);
        
            $file = file_put_contents($this->rulesdir . $rule["rulefile"], $content);
        }

    }

    public function moveUpRule($rule, $position) {
        
        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);

            $sectionRules = $this->splitRules($txt);
            if ($position > 0) {
                // if the element is not in the first position then it can be moved up
                $swappedRule = $sectionRules[intval($position)];
                $sectionRules[intval($position)] = $sectionRules[intval($position) - 1];
                $sectionRules[intval($position) - 1] = $swappedRule;
            }  
            $content = $this->joinRules($sectionRules);
        
            $file = file_put_contents($this->rulesdir . $rule["rulefile"], $content);
        }
    }

    public function moveDownRule($rule, $position) {
        
        if ($this->ruleFileExists($rule["rulefile"])) {
            $txt = file_get_contents($this->rulesdir . $rule["rulefile"]);

            $sectionRules = $this->splitRules($txt);
            $maxPosition = sizeof($sectionRules) - 1;
            if ($position <= $maxPosition) {
                // if the element is not in the last position then it can be moved up
                $swappedRule = $sectionRules[intval($position)];
                $sectionRules[intval($position)] = $sectionRules[intval($position) + 1];
                $sectionRules[intval($position) +  1] = $swappedRule;
            }  
            $content = $this->joinRules($sectionRules);
            
            $file = file_put_contents($this->rulesdir . $rule["rulefile"], $content);
        }
    }


    // -------------- SECTION ACTIONS --------------

    public function newRule($module, $rule) {
        $fileName = $this->rulesdir . $module . ".txt";

        if ($this->ruleFileExists($module)) {
            $txt = file_get_contents($this->rulesdir . $module . ".txt");

            $sectionRules = $this->splitRules($txt);
            if ((sizeof($sectionRules) == 1) && (trim($sectionRules[0]) == "")) {
                $sectionRules[0] = $rule;
            }
            else {
                array_splice($sectionRules, 0, 0, $rule);
            }
            $content = $this->joinRules($sectionRules);
        
            $file = file_put_contents($this->rulesdir . $module . ".txt", $content);
        }   
    }

    public function exportRuleFile($module) {
        $fileName = $this->rulesdir . $module . ".txt";

        if ($this->ruleFileExists($module)) {
            $txt = file_get_contents($fileName);
            return $txt;
        }
    }

    public function increasePriority($filename) {
        $directoryListing = scandir($this->rulesdir, SCANDIR_SORT_ASCENDING);
        $ruleFileList = array_values(preg_grep('~\.(txt)$~i', $directoryListing));

        foreach ($ruleFileList as $index => $file) {
            if ($file == $filename && $index > 0) {
                $target = $ruleFileList[$index];
                $targetTitle = explode(" -", $target);
                $targetPrecedence = intval($targetTitle[0]);

                $coll = $ruleFileList[$index - 1];
                $collTitle = explode(" -", $coll);
                $collPrecedence = intval($collTitle[0]);

                $targetTitle[0] = $collPrecedence;
                $collTitle[0] = $targetPrecedence;

                $targetNew = implode(" -", $targetTitle);
                $collNew = implode(" -", $collTitle);

                rename($this->rulesdir . $ruleFileList[$index], $this->rulesdir . $targetNew);
                rename($this->rulesdir . $ruleFileList[$index - 1], $this->rulesdir . $collNew);

            }
        }
    }


    public function decreasePriority($filename) {
        $directoryListing = scandir($this->rulesdir, SCANDIR_SORT_ASCENDING);
        $ruleFileList = array_values(preg_grep('~\.(txt)$~i', $directoryListing));

        foreach ($ruleFileList as $index => $file) {
            if ($file == $filename && $index < sizeof($ruleFileList) - 1 ) {
                $target = $ruleFileList[$index];
                $targetTitle = explode(" -", $target);
                $targetPrecedence = intval($targetTitle[0]);

                $coll = $ruleFileList[$index + 1];
                $collTitle = explode(" -", $coll);
                $collPrecedence = intval($collTitle[0]);

                $targetTitle[0] = $collPrecedence;
                $collTitle[0] = $targetPrecedence;

                $targetNew = implode(" -", $targetTitle);
                $collNew = implode(" -", $collTitle);

                rename($this->rulesdir . $ruleFileList[$index], $this->rulesdir . $targetNew);
                rename($this->rulesdir . $ruleFileList[$index + 1], $this->rulesdir . $collNew);

            }
        }
    }

    public function deleteSection($filename) {
        unlink($this->rulesdir . $filename);
        $this->fixPrecedences();
    }


    // -------------- GENERAL ACTIONS --------------

    public function createNewRuleFile($sectionName, $sectionPrecedence) {
        $filename = strval($sectionPrecedence) . " - " . $sectionName . ".txt";
        $file = $this->rulesdir . $filename;
        file_put_contents($file, "");
        return $filename;
    }

    public function removeRules($rulefile) {
        $file = $this->rulesdir . $rulefile;
        file_put_contents($file , "");
        return $file;
    }

    public function deleteTag($deltag) {
        if (file_exists($this->rulesdir . "tags.csv")) {
            $tags = array();
            $tagstxt = file_get_contents($this->rulesdir . "tags.csv");
            $taglines = explode("\n", trim($tagstxt));
            foreach ($taglines as $tag) {
                $line = explode(",", $tag);
                $tagobj = array();
                $tagobj["name"] = $line[0];
                $tagobj["color"] = $line[1];
                if ($deltag["name"] != $tagobj["name"]) {
                    $line = implode(",", $tagobj);
                    array_push($tags, $line);    
                }
            }
            $tagtxt = implode("\n", $tags);
            file_put_contents($this->rulesdir . "tags.csv", $tagtxt);
        }
    }

    public function importFile($filename, $file, $replace) {

        if ($replace) {
            // if file with same name exists, delete
            $deleteFile = $this->ruleFileExists($filename, false);
            if ($deleteFile != null) {
                unlink($this->rulesdir . $deleteFile);
            }
            
            $splitFilename = explode(" - ", $filename);
            if (sizeof($splitFilename) == 1) {
                // if no precedence, put at bottom
                $precedence = $this->getNumOfRuleFiles() + 1; 
                $name = strval($precedence) . " - " . $filename;
                file_put_contents($this->rulesdir . $name, $file);
            }
            else if (sizeof($splitFilename) == 2) {
                // if has precedence, check if valid
                if (intval($splitFilename[0]) != 0) {
                    file_put_contents($this->rulesdir . $filename, $file);
                    $this->fixPrecedences();
                    // TO DO: this insert does not account for duplicates
                    // the correct way (alphab. order to be considered after
                    // duplicate precedences)
                }
            }
            return;
        }

        else {
            $existsFile = $this->ruleFileExists($filename, false);
            if ($existsFile != null && gettype($existsFile) == "string" ) { // if file already exists
                $newrules = $this->getRuleNamesFromText($file);
                $oldrules = $this->getRuleNamesFromFile($existsFile);

                $additions = array();
                foreach ($newrules as $key => $newrule) {
                    if (!in_array($key, array_keys($oldrules))) {
                        array_push($additions, $newrule);
                    }
                }
                $result = array_merge(array_values($oldrules), $additions);
                $content = $this->joinRules($result);
                file_put_contents($this->rulesdir . $existsFile, $content);
            }
            else { // just create
                $splitFilename = explode(" - ", $filename);
                if (sizeof($splitFilename) == 1) {
                    $precedence = $this->getNumOfRuleFiles() + 1; 
                    $name = strval($precedence) . " - " . $filename;
                    file_put_contents($this->rulesdir . $name, $file);
                }
                else if (sizeof($splitFilename) == 2) {
                    // if has precedence, check if valid
                    if (intval($splitFilename[0]) != 0) {
                        file_put_contents($this->rulesdir . $filename, $file);
                        $this->fixPrecedences();
                        // TO DO: this insert does not account for duplicates
                        // the correct way (alphab. order to be considered after
                        // duplicate precedences)
                    }
                }
            }
        }
    }

    public function exportRuleFiles($filename) {
        $zip = new ZipArchive();
        if ($zip->open($filename, \ZipArchive::CREATE) == TRUE) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->rulesdir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, 2 * strlen($this->rulesdir) + 3); // TEMPORARY HACK on the path, WONT WORK remotely TO DO
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        }
        return $filename;
    }



    // ---------- TEMP ----------

    public function getStatus() {
        $autogameCourse = Core::$systemDB->select("autogame", ["course" => $this->courseId]);
        return $autogameCourse;
    }


}
