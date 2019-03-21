<?php
use SmartBoards\Module;
use SmartBoards\DataSchema;
use SmartBoards\ModuleLoader;

class XPLevels extends Module {

    public function setupResources() {
        parent::addResources('css/awards.css');
    }

    public function init() {
       
        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->registerFunction('awardLatestImage', function($award, $skills) {
            //$award = $award->getValue(); // get value of continuation

            switch ($award['type']) {
                case 'grade':
                    return new Modules\Views\Expression\ValueNode('<img src="images/quiz.svg">');
                case 'badge':
                    $imgName = str_replace(' ', '', $award['name']) . '-' . $award['level'];
                    return new Modules\Views\Expression\ValueNode('<img src="badges/' . $imgName . '.png">');
                    break;
                case 'skill':
                    $color = '#fff';
                    //$skillsData = $skills->getValue();
                    foreach($skillsData as $skill) {
                        
                            if ($skill['name'] == $award['name']) {
                                $color = $skill['color'];
                                break 2;
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
            //$award = $award->getValue(); // get value of continuation

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
            //$award = $award->getValue(); // get value of continuation

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
            if (is_array($userData) && sizeof($userData)==1 && array_key_exists(0, $userData))
                $userData=$userData[0];
            $mandatory = $userData['XP'] - $userData['countedTreeXP'] - min($userData['extraBadgeXP'], 1000);
            return new Modules\Views\Expression\ValueNode($userData['XP'] . ' total, ' . $mandatory . ' mandatory, ' . $userData['countedTreeXP'] .  ' from tree, ' . min($userData['extraBadgeXP'], 1000) . ' bonus');
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
