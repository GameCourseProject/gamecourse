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
        /*
        DataSchema::register(array(
            DataSchema::courseUserDataFields(array(
                DataSchema::makeObject('skills', null, array(
                    DataSchema::makeField('totalxp', 'Total XP', 1000),
                    DataSchema::makeField('countedxp', 'XP that counts toward final grade', 1000),
                    DataSchema::makeField('count', 'Number of complete skills', 4),
                    DataSchema::makeMap('list', 'Awarded skills', DataSchema::makeField('skillName', 'Skill Name', 'Alien Invasion'),
                        DataSchema::makeObject('skill', 'Skill', array(
                            DataSchema::makeField('post', 'Post of awarded skill', 'http://moodle/post'),
                            DataSchema::makeField('quality', 'Quality of awarded skill', 4),
                            DataSchema::makeField('time', 'Time of awarded skill', 1234567890)
                        )),
                        function() {
                            return array('abc');
                        }
                    )
                ))
            )),
            DataSchema::courseModuleDataFields($this, array(
                DataSchema::makeMap('skills', null, DataSchema::makeField('tierNum', 'Tier Number', 1),
                    DataSchema::makeObject('tier', 'Tier', array(
                        DataSchema::makeField('reward', 'Reward of Skill in Tier', 100),
                        DataSchema::makeArray('skills', null,
                            DataSchema::makeObject('skill', null, array(
                                DataSchema::makeField('name', 'Skill name', 'Alien Invasion'),
                                DataSchema::makeArray('dependencies', 'All Skill Dependencies',
                                    DataSchema::makeArray('dependencies', 'Skill Dependencies',
                                        DataSchema::makeField('dependency', 'Skill Dependency', 'Alien Invasion'))
                                    ),
                                DataSchema::makeField('color', 'Skill color', '#f4b300'),
                                DataSchema::makeField('page', 'Skill page', 'This is a long description of my skill')
                            ))
                        )
                    )),
                    function() {
                        return array('abc');
                    }
                )
            ))
        ));
*/
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->registerFunction('skillStyle', function($skill, $user) {
            //print_r($skill);
            //$skill = $skill->getValue();
            $courseId = $this->getParent()->getId();
            $unlockedSkills=array_column(Core::$sistemDB->selectMultiple("user_skill","name",["course"=>$courseId,"student"=> $user]),"name");
            // user_skill [user,course]
            //$unlockedSkills = DataSchema::getValue('course.users.user.data.skills.list', array('course.users.user' => $user), array('course' => $this->getParent()->getId()), false);
            
            if ($unlockedSkills == null)
                $unlockedSkills = array();
            $dependencies = Core::$sistemDB->selectMultiple("skill_dependency",'*',["course"=>$courseId,"skillName"=>$skill['name']]);
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
            $studentsSkills = array();
            $studentsUsernames = array();
            $studentsArray=[];
            foreach ($students as $student) {
                $studentsArray[$student['id']]=$student;
                $studentsSkills[$student['id']] = Core::$sistemDB->selectMultiple("user_skill",'*',["student"=>$student['id'],"course"=>$course->getId()]);
                //$course->getUserData($id)->getWrapped('skills')->get('list');
                $studentsUsernames[$student['id']] = \SmartBoards\User::getUser($student['id'])->getUsername();
            }

            //$students = $students->getValue();
            //$tiers = $course->getModuleData('skills');
            $tiers = Core::$sistemDB->selectMultiple("skill_tier",'*',["course"=>$course->getId()]);

            $skillsCache = array();
            foreach ($tiers as $tier) {
                $skills = Core::$sistemDB->selectMultiple("skill",'*',["course"=>$course->getId(),"tier"=>$tier['tier']]);
                foreach ($skills as $skill) {
                    $skillName = $skill['name'];
                    $skillsCache[$skillName] = array();
                    foreach($studentsSkills as $id => $studentSkills) {
                        $studentSkillsNames = array_column($studentSkills, "name");
                        if (is_array($studentSkills) && array_key_exists($skillName, $studentSkillsNames)) {
                            $skillsCache[$skillName][] = array(
                                'id' => $id,
                                'name' => $studentsArray[$id]['name'],
                                'campus' => $studentsArray[$id]['campus'],
                                'username' => $studentsUsernames[$id],
                                'timestamp' => $studentSkills['skillTime'],
                                'when' => date('d-M-Y', $studentSkills['skillTime'])
                            );
                        }
                    }

                    usort($skillsCache[$skillName], function($v1, $v2) {
                        return $v1['timestamp'] - $v2['timestamp'];
                    });

                    //$final = array();
                    //foreach($skillsCache[$skillName] as $skillArr) {
                    //    $final[] = \SmartBoards\DataRetrieverContinuation::buildForArray($skillArr);
                    //}
                    //$skillsCache[$skillName] = $final;
                }
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
            $viewsModule->setTemplate(self::SKILL_TREE_TEMPLATE, unserialize(file_get_contents(__DIR__ . '/skill_tree.vt')),$this->getId());

        if ($viewsModule->getTemplate(self::SKILLS_OVERVIEW_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::SKILLS_OVERVIEW_TEMPLATE, unserialize(file_get_contents(__DIR__ . '/skills_overview.vt')),$this->getId());
        
        API::registerFunction('skills', 'page', function() {
            API::requireValues('skillName');
            $skillName = API::getValue('skillName');
            $courseId=$this->getParent()->getId();
            $tiers = Core::$sistemDB->selectMultiple("skill_tier",'*',["course"=>$courseId]);
            //$tiers = $this->getParent()->getModuleData('skills')->get('skills');
            if ($skillName) {
                foreach($tiers as $tier) {
                    $skills = Core::$sistemDB->selectMultiple("skill",'*',["course"=>$courseId,"tier"=>$tier['tier']]);
                    foreach($skills as $skill) {
                        $compressedName = str_replace(' ', '', $skill['name']);
                        if (str_replace(' ', '', $skill['name']) == $skillName) {
                            $page = htmlspecialchars_decode($skill['page']);
                            // to support legacy, TODO: Remove this when skill editing is supported in SmartBoards
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
