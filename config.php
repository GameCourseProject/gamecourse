    <?php
    define('CONNECTION_STRING', 'mysql:host=localhost;dbname=gamecourse');
    define('CONNECTION_USERNAME', 'root');
    define('CONNECTION_PASSWORD', '');
    define('MODULES_FOLDER', 'modules');
    define('COURSE_DATA_FOLDER', 'course_data');
    define('BASE', 'gamecourse');

    define('FENIX_CLIENT_ID', '1977390058176612');
    define('FENIX_CLIENT_SECRET', 'YGNmsVmrW/60VZJolC6xk8LQsTjDtg3W9/BWrQrrra9cdGvFOjVjzTbRRtIwYcaCHPPGeuXmEcSgNt0vZnEHUg==');
    define('FENIX_REDIRECT_URL', 'http://localhost/gamecourse/auth/');
    define('FENIX_API_BASE_URL', 'https://fenix.tecnico.ulisboa.pt');

    define('GOOGLE_CLIENT_ID', '370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com');
    define('GOOGLE_CLIENT_SECRET', 'hC4zsuwH1fVIWi5k0C4zjOub');
    define('GOOGLE_REDIRECT_URL', 'http://localhost/gamecourse/auth?google');

    define('FACEBOOK_CLIENT_ID', '2373275799648058');
    define('FACEBOOK_CLIENT_SECRET', 'b0405dc8de6f9512b736cb895b7642ef');
    define('FACEBOOK_REDIRECT_URL', 'http://localhost/gamecourse/auth?facebook');

    define('LINKEDIN_CLIENT_ID', '78wgzc5jr7hrde');
    define('LINKEDIN_CLIENT_SECRET', 'WoFLt4UdWtk8Lkyz');
    define('LINKEDIN_REDIRECT_URL', 'http://localhost/gamecourse/auth?linkedin');

    $GLOBALS['theme'] = "default";

    define('XP_PER_LEVEL', 1000);
    define('MAX_BONUS_BADGES', 1000); //this is the default value but can be changed in connfig page
    define('DEFAULT_MAX_TREE_XP', 5000); //this is the default value but can be changed in connfig page
    ?>