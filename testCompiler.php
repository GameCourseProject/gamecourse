<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/ClassLoader.class.php';

use SmartBoards\Core;
use SmartBoards\ModuleLoader;

Core::denyCLI();
Core::requireLogin();
Core::requireSetup();

Core::checkAccess();

ModuleLoader::scanModules();
ModuleLoader::initModules();

//print_r(\SmartBoards\DataSchema::getValueWithContinuation('users.user.name', array('users.user' => '73137'), array(), true));

/*$me = \SmartBoards\DataSchema::getValueWithContinuation('users.user', array('users.user' => '73967'), array(), true);
echo '<pre>';
print_r($me);
$name = $me->execute('name', array());
print_r($name);
echo '</pre>';
exit;*/

$ids = \SmartBoards\User::getAll();

$parser = new \Modules\Views\Expression\ExpressionEvaluatorBase();
//$v = $parser->parse('Hello {2 * -4} {users.user[{%u + 1}].name} {%user.name}');
/*$v1 = $parser->parse('{%user.name} {%user.name} {%user.name} {%user.name} {%user.name}');
$v2 = $parser->parse('{users.user[{%u}].name} {users.user[{%u}].name} {users.user[{%u}].name} {users.user[{%u}].name} {users.user[{%u}].name}');

$t = microtime(true);
foreach($ids as $id) {
    $cont = \SmartBoards\DataSchema::getValueWithContinuation('users.user', array('users.user' => (string)$id), array(), true);
    $visitor = new \SmartBoards\Expression\EvaluateVisitor(array('u' => (string)$id, 'user' => $cont));
    for($i = 0; $i < 100; ++$i)
        $o = $v2->accept($visitor);
    var_dump($o);
}
$e = microtime(true) - $t;*/

/*$cont = \SmartBoards\DataSchema::getValueWithContinuation('moduleData.skills.skills.tier', array('moduleData.skills.skills.tier' => 't1'), array('course' => 0), true);
$cont = $cont->execute('skills.skill', array('skills.skill' => 2));
$cont = $cont->execute('dependencies.dependencies', array('dependencies.dependencies' => 0));
$cont = $cont->execute('dependency', array('dependency' => 0));
echo '<pre>';
print_r($cont);
echo '</pre>';
//$cont = $cont->execute('dependency', array('dependency' => 0));
var_dump($cont->getValue());*/

/*$cont = \SmartBoards\DataSchema::getValueWithContinuation('course.users.user', array('course.users.user' => '73137'), array('course' => 0), true);
//$cont = $cont->execute('skills.skill', array('skills.skill' => 2));
echo '<pre>';
print_r($cont);
echo '</pre>';
$cont = $cont->execute('roles', array('roles.role' => 0));
echo '<pre>';
print_r($cont);
echo '</pre>';
exit;*/

$visitor = new \Modules\Views\Expression\EvaluateVisitor(array('course' => 0));
$v = $parser->parse('{course.users.user[73137]}');
echo '<pre>';
var_dump($v->accept($visitor));
echo '</pre>';

$v = $parser->parse('{course.users.user[73137].roles}');
echo '<pre>';
var_dump($v->accept($visitor));
echo '</pre>';

$v = $parser->parse('{moduleData.badges.badges.badge[Squire].xp.xp[3]}');
echo '<pre>';
var_dump($v->accept($visitor));
echo '</pre>';

/*$visitor = new \SmartBoards\Expression\EvaluateVisitor(array('course' => 0));
$v = $parser->parse('{moduleData.skills.skills.tier[t0].skills.skill[0].dependencies.dependencies[0].dependency[0]}');
echo '<pre>';
print_r($v);
echo '</pre>';
var_dump($v->accept($visitor));*/



//echo $e;
?>
