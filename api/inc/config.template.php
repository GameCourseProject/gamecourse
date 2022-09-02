<?php
/**
 * This is the configuration file template. Make a copy of it in the same
 * directory, rename it to config.php and fill-in the necessary information.
 */

    const URL = '<WEBSITE_URL>';
    const API_URL = '<API_URL>';

    /** Versioning **/
    const PROJECT_VERSION = '<PROJECT_VERSION>';
    const API_VERSION = '<API_VERSION>';

    /** Database **/
    const DB_HOST = '<DB_HOST>';
    const DB_NAME = '<DB_NAME>';
    const DB_NAME_TEST = '<DB_NAME_TEST>';
    const DB_USER = '<DB_USER>';
    const DB_PASSWORD = '<DB_PASSWORD>';

    /** Fénix Auth **/
    const FENIX_CLIENT_ID = '<FENIX_CLIENT_ID>';
    const FENIX_CLIENT_SECRET = '<FENIX_CLIENT_SECRET>';
    const FENIX_REDIRECT_URL = API_URL . '/auth/';
    const FENIX_API_BASE_URL = 'https://fenix.tecnico.ulisboa.pt';

    /** Google Auth **/
    const GOOGLE_CLIENT_ID = '<GOOGLE_CLIENT_ID>';
    const GOOGLE_CLIENT_SECRET = '<GOOGLE_CLIENT_SECRET>';
    const GOOGLE_REDIRECT_URL = API_URL . '/auth?google';

    /** Facebook Auth **/
    const FACEBOOK_CLIENT_ID = '<FACEBOOK_CLIENT_ID>';
    const FACEBOOK_CLIENT_SECRET = '<FACEBOOK_CLIENT_SECRET>';
    const FACEBOOK_REDIRECT_URL = API_URL . '/auth?facebook';

    /** Linkedin Auth **/
    const LINKEDIN_CLIENT_ID = '<LINKEDIN_CLIENT_ID>';
    const LINKEDIN_CLIENT_SECRET = '<LINKEDIN_CLIENT_SECRET>';
    const LINKEDIN_REDIRECT_URL = API_URL . '/auth?linkedin';

    /** Google Account **/
    const GOOGLE_ACCOUNT_EMAIL = '<GOOGLE_ACCOUNT_EMAIL>';
    const GOOGLE_ACCOUNT_PASSWORD = '<GOOGLE_ACCOUNT_PASSWORD>';

    /** Tiny URL **/
    const TINY_API_TOKEN = '<TINY_API_TOKEN>';
    const TINY_API_URL = '<TINY_API_URL>';

    /** File Structure **/
    const AUTOGAME_FOLDER = ROOT_PATH . '<AUTOGAME_FOLDER>';
    const CACHE_FOLDER = ROOT_PATH . '<CACHE_FOLDER>>';
    const COURSE_DATA_FOLDER = ROOT_PATH . '<COURSE_DATA_FOLDER>';
    const LOGS_FOLDER = ROOT_PATH . '<LOGS_FOLDER>';
    const MODULES_FOLDER = ROOT_PATH . '<MODULES_FOLDER>';
    const USER_DATA_FOLDER = ROOT_PATH . '<USER_DATA_FOLDER>';