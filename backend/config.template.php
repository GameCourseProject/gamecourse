<?php
    define('CONNECTION_STRING', 'mysql:host=<DB_HOST>;dbname=<DB_NAME>');
    define('CONNECTION_USERNAME', '<DB_USERNAME>');
    define('CONNECTION_PASSWORD', '<DB_PASSWORD>');

    define('MODULES_FOLDER', 'modules');
    define('COURSE_DATA_FOLDER', 'course_data');
    define('BASE', 'gamecourse');

    define('FENIX_CLIENT_ID', '<FENIX_CLIENT_ID>');
    define('FENIX_CLIENT_SECRET', '<FENIX_CLIENT_SECRET>');
    define('FENIX_REDIRECT_URL', '<FENIX_REDIRECT_URL>');
    define('FENIX_API_BASE_URL', '<FENIX_API_BASE_URL>');

    define('GOOGLE_CLIENT_ID', '<GOOGLE_CLIENT_ID>');
    define('GOOGLE_CLIENT_SECRET', '<GOOGLE_CLIENT_SECRET>');
    define('GOOGLE_REDIRECT_URL', '<GOOGLE_REDIRECT_URL>');

    define('FACEBOOK_CLIENT_ID', '<FACEBOOK_CLIENT_ID>');
    define('FACEBOOK_CLIENT_SECRET', '<FACEBOOK_CLIENT_SECRET>');
    define('FACEBOOK_REDIRECT_URL', '<FACEBOOK_REDIRECT_URL>');

    define('LINKEDIN_CLIENT_ID', '<LINKEDIN_CLIENT_ID>');
    define('LINKEDIN_CLIENT_SECRET', '<LINKEDIN_CLIENT_SECRET>');
    define('LINKEDIN_REDIRECT_URL', '<LINKEDIN_REDIRECT_URL>');

    $GLOBALS['theme'] = "default";

    define('XP_PER_LEVEL', 1000);
    define('MAX_BONUS_BADGES', 1000); //this is the default value but can be changed in config page
    define('DEFAULT_MAX_TREE_XP', 5000); //this is the default value but can be changed in config page
?>