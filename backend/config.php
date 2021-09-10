<?php
    define('BASE', 'gamecourse'); // FIXME: can prob delete
    define('URL', 'http://localhost:4200');
    define('API_URL', 'http://localhost/gamecourse-v2/backend');

    /** Database Connection */
    define('CONNECTION_STRING', 'mysql:host=localhost;dbname=gamecourse-v2');
    define('CONNECTION_USERNAME', 'root');
    define('CONNECTION_PASSWORD', '');

    /** Fénix Auth */
    define('FENIX_CLIENT_ID', '1132965128044839');
    define('FENIX_CLIENT_SECRET', 'Gnes4mKQ1tHnczp4eyy59SItoXIRTHFih1pMFV7PrJD+zbTzb+MWuPhFdeVszycz82AiR0jLhPYO19glxk/Fyg==');
    define('FENIX_REDIRECT_URL', API_URL . '/auth/');
    define('FENIX_API_BASE_URL', 'https://fenix.tecnico.ulisboa.pt');

    /** Google Auth */
    define('GOOGLE_CLIENT_ID', '370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com');
    define('GOOGLE_CLIENT_SECRET', 'hC4zsuwH1fVIWi5k0C4zjOub');
    define('GOOGLE_REDIRECT_URL', API_URL . '/auth?google');

    /** Facebook Auth */
    define('FACEBOOK_CLIENT_ID', '2373275799648058');
    define('FACEBOOK_CLIENT_SECRET', 'b0405dc8de6f9512b736cb895b7642ef');
    define('FACEBOOK_REDIRECT_URL', API_URL . '/auth?facebook');

    /** Linkedin Auth */
    define('LINKEDIN_CLIENT_ID', '78wgzc5jr7hrde');
    define('LINKEDIN_CLIENT_SECRET', 'WoFLt4UdWtk8Lkyz');
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