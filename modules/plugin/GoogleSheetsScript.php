<?php

namespace Modules\Plugin;

chdir('../..');
include 'classes/ClassLoader.class.php';
include 'modules/plugin/GoogleSheets.php';

use GameCourse\Core;

Core::init();

$moodle = new GoogleSheets(1);
$moodle->readGoogleSheets();
