<?php
define('CONNECTION_STRING', 'mysql:host=localhost;dbname=gamecourse');
define('CONNECTION_USERNAME', 'root');
define('CONNECTION_PASSWORD', '789456123');
define('MODULES_FOLDER', 'modules');
define('LEGACY_DATA_FOLDER', 'legacy_data');
define('BASE', 'gamecourse');
define('FENIX_CLIENT_ID', '288540197912657');
define('FENIX_CLIENT_SECRET', 'L2LYsSBMvNtq4SI/LxnjHiT68epSzojqeXTqYgfPeYQyA8LKsqznzy/j8lrDsfJFjGAOG5vi4SSrOlgopbkVuw==');
define('FENIX_REDIRECT_URL', 'http://localhost/gamecourse/auth/');
define('FENIX_API_BASE_URL', 'https://fenix.tecnico.ulisboa.pt');

$GLOBALS['theme']="default";

define('XP_PER_LEVEL', 1000);
define('MAX_BONUS_BADGES', 1000);//this is the default value but can be changed in connfig page
define('DEFAULT_MAX_TREE_XP', 5000);//this is the default value but can be changed in connfig page
?>