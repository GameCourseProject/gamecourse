<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class Example extends Module {

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function init() {
        $user = Core::getLoggedUser();
        if (($user != null && $user->isAdmin()) || $this->getParent()->getLoggedUser()->isTeacher())
            Core::addNavigation('images/gear.svg', 'Example', 'course.example', true);

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->registerView($this, 'example', 'Example View', array(
            'type' => ViewHandler::VT_SINGLE
        ));

        $names = array('Janita Willilams','Lorita Mcspadden','Moses Spence','Stefany Grossi','Minh Bustos',
            'Christinia Durrah','Moises Nevitt','Mercedez Odell','Star Bagg','Angelika Well','Clarine Visconti',
            'Denita Spicher','Sheridan Capp','Marcelina Beegle','Emilee Parcell','Detra Dalal','Ramon Dowdell',
            'Christine Mckennon','Karon Wessels','Bryan Lanni','Lean Starrett','Janis Contos','Leslee Hollinger',
            'Debra Mccown','Jayna Kogut','Leigha Smythe','Georgann Walkins','Concetta Soucie','Harvey Esparza',
            'Justa Stankiewicz','Lorenza Portillo','Marni Devito','Pearline Crago','Gaylord Hise','Keli Trowell',
            'Fumiko Conway','Daria Minton','Ben Younts','Isabel Tumlinson','Stephane Wofford','Rhoda Edlund',
            'Kemberly Oriol','Carlene Speno','Marcel Dolphin','Collin Tallent','Deeanna Rasheed','Benjamin Chess',
            'Tamatha Honaker','Apryl Carstensen','Markita Jenny');

        srand(10);
        rand(0, count($names)-1);
        rand(0, count($names)-1);
        rand(0, count($names)-1);
        $viewHandler->registerFunction("randomName", function() use ($names) {
            return new \Modules\Views\Expression\ValueNode($names[rand(0, count($names)-1)]);
        });
    }
}
ModuleLoader::registerModule(array(
    'id' => 'example',
    'name' => 'Example',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Example();
    }
));
?>
