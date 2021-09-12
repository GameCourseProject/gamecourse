<?php
    define('URL', '<WEBSITE_URL>');
    define('API_URL', '<API_URL>');

    /** Database Connection */
    define('CONNECTION_STRING', 'mysql:host=<HOST>;dbname=<DD_NAME>');
    define('CONNECTION_USERNAME', '<DB_USER>');
    define('CONNECTION_PASSWORD', '<DB_PASSWORD>');

    /** Fénix Auth */
    define('FENIX_CLIENT_ID', '<FENIX_CLIENT_ID>');
    define('FENIX_CLIENT_SECRET', '<FENIX_CLIENT_SECRET>');
    define('FENIX_REDIRECT_URL', API_URL . '/auth/');
    define('FENIX_API_BASE_URL', 'https://fenix.tecnico.ulisboa.pt');

    /** Google Auth */
    define('GOOGLE_CLIENT_ID', '<GOOGLE_CLIENT_ID>');
    define('GOOGLE_CLIENT_SECRET', '<GOOGLE_CLIENT_SECRET>');
    define('GOOGLE_REDIRECT_URL', API_URL . '/auth?google');

    /** Facebook Auth */
    define('FACEBOOK_CLIENT_ID', '<FACEBOOK_CLIENT_ID>');
    define('FACEBOOK_CLIENT_SECRET', '<FACEBOOK_CLIENT_SECRET>');
    define('FACEBOOK_REDIRECT_URL', API_URL . '/auth?facebook');

    /** Linkedin Auth */
    define('LINKEDIN_CLIENT_ID', '<LINKEDIN_CLIENT_ID>');
    define('LINKEDIN_CLIENT_SECRET', '<LINKEDIN_CLIENT_SECRET>');
    define('LINKEDIN_REDIRECT_URL', API_URL . '/auth?linkedin');

    /** Folders */
    define('MODULES_FOLDER', 'modules');
    define('COURSE_DATA_FOLDER', 'course_data');

    /** Themes */
    $GLOBALS['theme'] = "default";

    /**
     * XP default values
     * Can be changed in the config page
     */
    define('XP_PER_LEVEL', 1000);
    define('MAX_BONUS_BADGES', 1000);
    define('DEFAULT_MAX_TREE_XP', 5000);
?>