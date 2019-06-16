<?php
use SmartBoards\API;
use SmartBoards\Module;
use SmartBoards\DataSchema;
use SmartBoards\ModuleLoader;
use SmartBoards\Core;

class Skills extends Module {

    const SKILL_TREE_TEMPLATE = 'Skill Tree - by skills';
    const SKILLS_OVERVIEW_TEMPLATE = 'Skills Overview - by skills';

    public function __construct() {
        parent::__construct('skills', 'Skills', '0.1', array(
            array('id' => 'views', 'mode' => 'hard')
        ));
    }

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/skills.css');
    }

    public function init() {
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->registerFunction('skillStyle', function($skill, $user) {
            $courseId = $this->getParent()->getId();
            $unlockedSkills=array_column(Core::$systemDB->selectMultiple("user_skill","name",["course"=>$courseId,"student"=> $user]),"name");
                  
            if ($unlockedSkills == null)
                $unlockedSkills = array();
            $dependencies = Core::$systemDB->selectMultiple("skill_dependency",'*',["course"=>$courseId,"skillName"=>$skill['name']]);
            $unlocked = (count($dependencies) == 0);

            foreach($dependencies as $dependency) {
                $unlock = true;
                if (!in_array($dependency['dependencyA'], $unlockedSkills) || !in_array($dependency['dependencyB'], $unlockedSkills)) {
                    $unlock = false;  
                }
                if ($unlock) {
                    $unlocked = true;
                    break;
                }
            }
            $val = 'background-color: ' . ($unlocked ? $skill['color'] : '#6d6d6d') . '; ';

            if (in_array($skill['name'], $unlockedSkills)) {
                $val .= 'box-shadow: 0 0 30px 5px green;';
            }

            return new \Modules\Views\Expression\ValueNode($val);
        });

        $skillsCache = array();
        $viewHandler->registerFunction('usersSkillsCache', function() use (&$skillsCache) {
            $course = $this->getParent();
            $students = $course->getUsersWithRole('Student');
            $studentsArray=[];
            foreach ($students as $student) {
                $studentsArray[$student['id']]=$student;
            }
            //$studentsArray = array_combine(array_column($students,"id"),$students);
                    
            $skillsCache = array();
            $skills = Core::$systemDB->selectMultiple("skill_tier natural join skill",
                                                    '*',["course"=>$course->getId()]);
            foreach ($skills as $skill) {
                $skillName = $skill['name'];
                $skillsCache[$skillName] = array();
                $skillStudents = Core::$systemDB->selectMultiple("user_skill",'*',["name"=>$skillName,"course"=>$course->getId()]);
               
                foreach($skillStudents as $skillStudent) {
                    $id= $skillStudent['student'];
                    $timestamp=  strtotime($skillStudent['skillTime']);
                    $skillsCache[$skillName][] = array(
                        'id' => $id,
                        'name' => $studentsArray[$id]['name'],
                        'campus' => $studentsArray[$id]['campus'],
                        'username' => $studentsArray[$id]['username'],
                        'timestamp' => $timestamp,
                        'when' => date('d-M-Y', $timestamp)
                    );    
                }

                usort($skillsCache[$skillName], function($v1, $v2) {
                        return $v1['timestamp'] - $v2['timestamp'];
                });
                
            }
            return new Modules\Views\Expression\ValueNode('');
        });

        $viewHandler->registerFunction('numStudentsWithSkill', function($skillName) use (&$skillsCache) {
            return new \Modules\Views\Expression\ValueNode(count($skillsCache[$skillName]));
        });

        $viewHandler->registerFunction('studentsWithSkill', function($skillName) use (&$skillsCache) {
            return new \Modules\Views\Expression\ValueNode($skillsCache[$skillName]);
        });

        if ($viewsModule->getTemplate(self::SKILL_TREE_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::SKILL_TREE_TEMPLATE, file_get_contents(__DIR__ . '/skillTree.txt'),$this->getId());
        if ($viewsModule->getTemplate(self::SKILLS_OVERVIEW_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::SKILLS_OVERVIEW_TEMPLATE, file_get_contents(__DIR__ . '/skillsOverview.txt'),$this->getId());
    
        API::registerFunction('skills', 'page', function() {
            API::requireValues('skillName');
            $skillName = API::getValue('skillName');
            $courseId=$this->getParent()->getId();
            
            if ($skillName) {
                $skills = Core::$systemDB->selectMultiple("skill_tier natural join skill",
                                '*',["course"=>$courseId]);
                foreach($skills as $skill) {
                    $compressedName = str_replace(' ', '', $skill['name']);
                    if ($compressedName == $skillName) {
                        $page = htmlspecialchars_decode($skill['page']);
                        //to support legacy, TODO: Remove this when skill editing is supported in SmartBoards
                        preg_match_all('/\shref="([A-z]+)[.]html/', $page, $matches);
                        foreach($matches[0] as $id => $match) {
                            $linkSkillName = $matches[1][$id];
                            $page = str_replace($match, ' ui-sref="skill({skillName:\'' . $linkSkillName . '\'})', $page);
                        }
                        $page = str_replace('src="http:', 'src="https:', $page);
                        $page = str_replace(' href="' . $compressedName, ' target="_self" ng-href="' . $this->getDir() . 'resources/' . $compressedName, $page);
                        $page = str_replace(' src="' . $compressedName, ' src="' . $this->getDir() . 'resources/' . $compressedName, $page);
                        API::response(array('name' => $skill['name'], 'description' => $page));
                    }
                }
            }
            API::error('Skill ' . $skillName . ' not found.', 404);
        });
    }
}

ModuleLoader::registerModule(array(
    'id' => 'skills',
    'name' => 'Skills',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Skills();
    }
));
?>
