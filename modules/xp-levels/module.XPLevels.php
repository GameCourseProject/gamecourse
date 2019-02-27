<?php
use SmartBoards\Module;
use SmartBoards\DataSchema;
use SmartBoards\ModuleLoader;

class XPLevels extends Module {

    public function setupResources() {
        parent::addResources('css/awards.css');
    }

    public function init() {
        /*
        DataSchema::register(array(
            DataSchema::courseUserDataFields(array(
                DataSchema::makeField('xp', 'XP of the student', 12500),
                DataSchema::makeField('level', 'Level of the student', 12),
                DataSchema::makeArray('awards', 'Awards',
                    DataSchema::makeObject('award', 'Award', array(
                        DataSchema::makeField('type', 'Award type', 'grade'),
                        DataSchema::makeField('reward', 'Award reward', 600),
                        DataSchema::makeField('date', 'Award date', 1234567890),
                        DataSchema::makeField('name', 'Award name', 'Quiz 1'),
                        DataSchema::makeField('subtype', 'Grade subtype', 'quiz'),
                        DataSchema::makeField('num', 'Lab or Quiz number', 1),
                        DataSchema::makeField('level', 'Badge level', 1),

                    ))
                )
            )),
            DataSchema::courseModuleDataFields($this, array(
                DataSchema::makeArray('levels', null,
                    DataSchema::makeObject('level', null, array(
                        DataSchema::makeField('minxp', 'Min XP', 2000),
                        DataSchema::makeField('title', 'Title of the Level', 'Self-Aware')
                    ))
                )
            ))
        ));
*/
        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->registerFunction('awardLatestImage', function($award, $skills) {
            $award = $award->getValue(); // get value of continuation

            switch ($award['type']) {
                case 'grade':
                    return new Modules\Views\Expression\ValueNode('<img src="images/quiz.svg">');
                case 'badge':
                    $imgName = str_replace(' ', '', $award['name']) . '-' . $award['level'];
                    return new Modules\Views\Expression\ValueNode('<img src="badges/' . $imgName . '.png">');
                    break;
                case 'skill':
                    $color = '#fff';
                    $skillsData = $skills->getValue();
                    foreach($skillsData as $tid => $tier) {
                        foreach ($tier['skills'] as $skillName => $skill) {
                            if ($skill['name'] == $award['name']) {
                                $color = $skill['color'];
                                break 2;
                            }
                        }
                    }
                    return new Modules\Views\Expression\ValueNode('<div class="skill" style="background-color: ' . $color . '">');
                case 'bonus':
                    return new Modules\Views\Expression\ValueNode('<img src="images/awards.svg">');
                default:
                    return new Modules\Views\Expression\ValueNode('<img src="images/quiz.svg">');
            }
        });

        $viewHandler->registerFunction('formatAward', function($award) {
            $award = $award->getValue(); // get value of continuation

            switch ($award['type']) {
                case 'grade':
                    return new Modules\Views\Expression\ValueNode('Grade from ' . $award['name']);
                case 'badge':
                    $imgName = str_replace(' ', '', $award['name']) . '-' . $award['level'];
                    return new Modules\Views\Expression\ValueNode('Earned ' . $award['name'] . ' (level ' . $award['level'] . ') <img src="badges/' . $imgName . '.png">');
                    break;
                case 'skill':
                    return new Modules\Views\Expression\ValueNode('Skill Tree ' . $award['name']);
                case 'bonus':
                default:
                    return new Modules\Views\Expression\ValueNode($award['name']);
            }
        });

        $viewHandler->registerFunction('formatAwardLatest', function($award) {
            $award = $award->getValue(); // get value of continuation

            switch ($award['type']) {
                case 'badge':
                    return new Modules\Views\Expression\ValueNode($award['name'] . ' (level ' . $award['level'] . ')');
                case 'skill':
                    return new Modules\Views\Expression\ValueNode('Skill Tree ' . $award['name']);
                default:
                    return new Modules\Views\Expression\ValueNode($award['name']);
            }
        });

        $viewHandler->registerFunction('awardsXP', function($userData) {
            $mandatory = $userData['xp'] - $userData['skills']['countedxp'] - min($userData['badges']['bonusxp'], 1000);
            return new Modules\Views\Expression\ValueNode($userData['xp'] . ' total, ' . $mandatory . ' mandatory, ' . $userData['skills']['countedxp'] .  ' from tree, ' . min($userData['badges']['bonusxp'], 1000) . ' bonus');
        });
    }
}

ModuleLoader::registerModule(array(
        'id' => 'xp',
        'name' => 'XP and Levels',
        'version' => '0.1',
        'dependencies' => array(
            array('id' => 'views', 'mode' => 'hard')
        ),
        'factory' => function() {
            return new XPLevels();
        }
    ));
?>
