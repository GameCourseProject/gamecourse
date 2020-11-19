<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
include('classes/ClassLoader.class.php');
include('../../config.php');

use \GameCourse\Core;
use \GameCourse\CronJob;

Core::init();
if (isset($_GET["course"])) {
	$course = $_GET["course"];
	if (isset($_GET["number"]) && isset($_GET["time"])) {
		$number = $_GET["number"];
		$time = $_GET["time"];
		if ($_GET["number"] == 0) {
			echo "Please select a periodicity";
		} else {
			if (Core::$systemDB->select("config_qr", ["course" => $course])) {
				Core::$systemDB->update("config_qr", ["isEnabled" => 1, "periodicityNumber" => $number, "periodicityTime" => $time], ["course" => $course]);
			} else {
				Core::$systemDB->insert("config_qr", ["isEnabled" => 1, "periodicityNumber" => $number, "periodicityTime" => $time, "course" => $course]);
			}
			new CronJob("QR", $course, $number, $time);
		}
	} else if (isset($_GET["disable"])) {
		Core::$systemDB->update("config_qr", ["isEnabled" => 0, "periodicityNumber" => 0, "periodicityTime" => "Minutes"], ["course" => $course]);
		new CronJob("QR", $course, null, null, true);
	} else {
		$configQR = Core::$systemDB->select("config_qr", ["course" => $course]);
		if ($configQR) {
			echo $configQR["periodicityNumber"] . ";" . $configQR["periodicityTime"];
		} else {
			echo "0;Minutes";
		}
	}
}
