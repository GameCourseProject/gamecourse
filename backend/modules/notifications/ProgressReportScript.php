<?php
namespace Modules\Notifications;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';

include 'lib/PHPMailer.php';
include 'lib/SMTP.php';
include 'lib/Exception.php';

require_once 'config.php';

use DateTime;
use GameCourse\Core;
use GameCourse\Course;
use Modules\AwardList\AwardList;
use Modules\XP\XPLevels;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Utils;

Core::init();

// To run manually, use the www-data user:
// sudo -u www-data php /var/www/html/gamecourse/backend/modules/notifications/ProgressReportScript.php 1
// Replace "1" in the above command with the appropriate course ID

$courseId = $argv[1];
$course = Course::getCourse($courseId);
$courseName = $course->getName();
$courseYear = $course->getData("year");

$profilePageId = Core::$systemDB->select("page", ["course" => $courseId, "name" => "Profile"], "id");
$imgsPath = API_URL . "/" . MODULES_FOLDER . "/" . Notifications::ID . "/imgs";

$config = Core::$systemDB->select(Notifications::TABLE_PROGRESS_REPORT_CONFIG, ["course" => $courseId]);
$seqNr = Core::$systemDB->select(Notifications::TABLE_PROGRESS_REPORT, ["course" => $courseId], "count(*)") + 1;
$isWeekly = $config["periodicityTime"] === "Weekly";

date_default_timezone_set('Europe/Lisbon');
$currentDate = date_create()->format("Y-m-d H:i:s");
$endDate = $config["endDate"];
$startPeriodDate = date_sub(date_create($currentDate), date_interval_create_from_date_string($isWeekly ? "1 weeks" : "1 days"))->format("Y-m-d H:i:s");
$endPeriodDate = $currentDate;
$startPreviousPeriodDate = date_sub(date_create($startPeriodDate), date_interval_create_from_date_string($isWeekly ? "1 weeks" : "1 days"))->format("Y-m-d H:i:s");
$endPreviousPeriodDate = $startPeriodDate;

$timeLeft = datediff(date_create($currentDate), date_create($endDate), $isWeekly ? "weeks" : "days");

$pieChartURL = "https://quickchart.io/chart/render/zm-f7746c74-abbb-4250-a0c2-11451cb8f7d6"; // editor: https://quickchart.io/chart-maker/edit/zm-7f2783e0-a82c-41b0-920b-9ce72210fd0a
$areaChartURL = "https://quickchart.io/chart/render/zm-a1ad2eb1-9680-4fc1-9e83-008080e79c6b"; // editor: https://quickchart.io/chart-maker/edit/zm-72a28b6b-b495-4c9d-a4e8-ce3e5ce5b013

$subject    = $courseName . " - " . $config["periodicityTime"] . " Report #" . $seqNr;
$error      = false;

// Send e-mail to each course student
$students = $course->getUsersWithRole("Student");
foreach ($students as $student) {
    // No email set, continue
    if (!isset($student["email"]) || $student["email"] == null || $student["email"] == "") continue;

    $to = $student["email"];
    $studentId = $student["id"];
    $studentName = explode(" ", isset($student["nickname"]) && $student["nickname"] != "" ? $student["nickname"] : $student["name"])[0];

    $awards = Core::$systemDB->selectMultiple(AwardList::TABLE, ["course" => $courseId, "user" => $studentId]);
    $awardsCurrentPeriod = array_filter($awards, function ($award) use ($startPeriodDate, $endPeriodDate)  { return $award["date"] >= $startPeriodDate && $award["date"] <= $endPeriodDate; });
    $awardsPreviousPeriod = array_filter($awards, function ($award) use ($startPreviousPeriodDate, $endPreviousPeriodDate)  { return $award["date"] >= $startPreviousPeriodDate && $award["date"] <= $endPreviousPeriodDate; });

    // FIXME: not accomodating other periodicities aside from Weekly
    $startDay = intval(date_create($currentDate)->format("w"));
    $weekdays = range(0, 6);
    $end = array_slice($weekdays, 0, $startDay);
    $begin = array_splice($weekdays, $startDay);
    $weekdays = array_merge($begin, $end, [$startDay]);
    $weekdays = array_map(function ($day) { return date('D', strtotime("Sunday " . $day . " days")); }, $weekdays);

    $awardsXPCurrentPeriodByDay = [0, 0, 0, 0, 0, 0, 0, 0];
    $awardsByType = [];
    foreach ($awardsCurrentPeriod as $award) {
        $weekday = intval(date_create($award["date"])->format("w"));
        if ($weekday < $startDay) $index = $weekday + (7 - $startDay);
        elseif ($weekday > $startDay) $index = $weekday - $startDay;
        else if (date_create($award["date"])->format("Y-m-d") == date_create($endPeriodDate)->format("Y-m-d")) $index = 7;
        else $index = 0;
        $awardsXPCurrentPeriodByDay[$index] += intval($award["reward"]);

        $typeDescription = awardTypeToDescription($award["type"]);
        if (!isset($awardsByType[$typeDescription])) $awardsByType[$typeDescription] = 0;
        $awardsByType[$typeDescription] += 1;
    }

    $awardsXPPreviousPeriodByDay = [0, 0, 0, 0, 0, 0, 0, 0];
    foreach ($awardsPreviousPeriod as $award) {
        $weekday = intval(date_create($award["date"])->format("w"));
        if ($weekday < $startDay) $index = $weekday + (7 - $startDay);
        elseif ($weekday > $startDay) $index = $weekday - $startDay;
        else if (date_create($award["date"])->format("Y-m-d") == date_create($endPreviousPeriodDate)->format("Y-m-d")) $index = 7;
        else $index = 0;
        $awardsXPPreviousPeriodByDay[$index] += intval($award["reward"]);
    }

    $areaChart = $areaChartURL . "?data1=" . implode(",", $awardsXPCurrentPeriodByDay) .
                                 "&data2=" . implode(",", $awardsXPPreviousPeriodByDay) .
                                 "&labels=" . implode(",", $weekdays);
    $pieChart = $pieChartURL . "?data1=" . (count($awardsByType) > 0 ? implode(",", array_values($awardsByType)) : 0) .
                               "&labels=" . (count($awardsByType) > 0 ? implode(",", array_map(function ($label) { return ucfirst($label); }, array_keys($awardsByType))) : "No data");

    $totalXP = intval(Core::$systemDB->select(XPLevels::TABLE_XP, ["course" => $courseId, "user" => $studentId], "xp"));
    $currentPeriodXP = array_reduce($awardsCurrentPeriod, function ($carry, $award) {
        $carry += intval($award["reward"]);
        return $carry;
    });
    $previousPeriodXP = array_reduce($awardsPreviousPeriod, function ($carry, $award) {
        $carry += intval($award["reward"]);
        return $carry;
    });

    $diff = $previousPeriodXP == 0 ? 0 : -(($previousPeriodXP - $currentPeriodXP) * 100 / $previousPeriodXP);
    if ($timeLeft > 0) {
        $weekAvg = $totalXP / $seqNr;
        $hasPresentation = !empty(Core::$systemDB->select(AwardList::TABLE, ["course" => $courseId, "user" => $studentId, "type" => "presentation"], "reward"));
        $prediction = round($totalXP + ($hasPresentation ? 0 : 2500) + $weekAvg * $timeLeft); // FIXME: presentation XP is hard-coded
    }

    $reportBlock = '<div class="u-row-container" style="padding: 0px;background-color: transparent; position: relative; z-index: 2; margin-top: 100px;">
                      <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 670px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                        <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                          <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:670px;"><tr style="background-color: transparent;"><![endif]-->

                          <!--[if (mso)|(IE)]><td align="center" width="670" style="background-color: #ffffff;width: 670px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                          <div class="u-col u-col-100" style="max-width: 320px;min-width: 670px;display: table-cell;vertical-align: top;">
                            <div style="background-color: #ffffff;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 10px; box-shadow: 0px 3px 5px 2px rgb(224, 223, 223)">
                              <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 60px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->

                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                      <tr>
                                        <td style="padding-right: 0px;padding-left: 0px;" align="center">
                                          <a href="' . URL . '" target="_blank">
                                            <img align="center" border="0" src="' . URL . '/assets/logo/logo_horz.jpg" alt="GameCourse logo" title="GameCourse logo" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;;max-width: 249.1px;" width="249.1"/>
                                          </a>
                                        </td>
                                      </tr>
                                    </table>

                                  </td>
                                </tr>
                                </tbody>
                              </table>

                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <h1 style="margin: 0px; color: #000000; line-height: 140%; text-align: center; word-wrap: break-word; font-weight: normal; font-family: \'Montserrat\',sans-serif; font-size: 32px;">
                                        <strong>' . $config["periodicityTime"] . ' Progress Report</strong>
                                      </h1>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>

                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px; padding-top: 0; font-family:\'Montserrat\',sans-serif;" align="left">

                                      <div style="line-height: 100%; text-align: center; word-wrap: break-word;">
                                        <p style="font-size: 14px; line-height: 140%; text-align: center;"><span style="font-size: 16px; line-height: 22.4px; color: #9e9d9d;">' .
                                            date_format(date_create($startPeriodDate), "l, F jS") . ' - ' . date_format(date_create($endPeriodDate), "l, F jS") . '</span></p>
                                      </div>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>

                              <table style="font-family:\'Montserrat\',sans-serif; margin-top: 10px;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <div style="line-height: 140%; text-align: left; word-wrap: break-word;">
                                        <p style="font-size: 14px; font-weight: 500; line-height: 140%;"><span style="font-size: 28px; line-height: 39.2px;">Hey ' . $studentName . ',</span></p>
                                      </div>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>

                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <div style="line-height: 170%; text-align: justify; word-wrap: break-word;">
                                        <p style="font-size: 16px; line-height: 25px;">Here is a summary of your progress in <strong>' . $courseName . ' ' . $courseYear . '</strong> ' . ($isWeekly ? 'last week' : 'yesterday') . '.</p>
                                      </div>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>

                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <div>
                                        <div style="display: flex; justify-content: space-between; width: 100%; margin-top: 10px;">
                                            <div style="display: flex; flex-direction: column; justify-content: center; border-color: #dfdfdf; border-width: 2; margin-right: 20px;
                                                border-style: solid; border-radius: 8px; padding-top: 12px; padding-left: 20px; padding-right: 20px; padding-bottom: 12px; width: 100%;">
                                                <div style="display: flex; align-items: center;">
                                                    <img src="' . $imgsPath . '/school-cap.jpg" style="width: 25px; height: 25px; margin-right: 8px;">
                                                    <span style="text-transform: uppercase; font-weight: 600; color: #757575">Total</span>
                                                </div>
                                                <p style="margin-bottom: 0; font-size: 24px; font-weight: 700; margin-top: 10px;">' . number_format($totalXP, 0, ',', ' ') . ' XP</p>
                                            </div>

                                            <div style="display: flex; flex-direction: column; justify-content: center; border-color: #dfdfdf; border-width: 2; margin-left: 20px;
                                                border-style: solid; border-radius: 8px; padding-top: 12px; padding-left: 20px; padding-right: 20px; padding-bottom: 12px; width: 100%;">
                                                <div style="display: flex; align-items: center;">
                                                    <img src="' . $imgsPath . '/calendar.jpg" style="width: 20px; height: 20px; margin-right: 8px;">
                                                    <span style="text-transform: uppercase; font-weight: 600; color: #757575">This week</span>
                                                </div>
                                                <div style="display: flex; align-items: center; margin-top: 10px;">
                                                    <p style="margin-bottom: 0; font-size: 24px; font-weight: 700; margin-top: 0;">' . number_format($currentPeriodXP, 0, ',', ' ') . ' XP</p>';
    if ($diff != 0) {
        $reportBlock .= '                            <div style="display: flex; align-items: center; margin-left: 8px;">
                                                        <img src="' . $imgsPath . ($diff > 0 ? '/arrow-up.jpg' : '/arrow-down.jpg') . '" style="width: 15px; height: 15px;">
                                                        <span style="font-size: 15px; font-weight: 700; color: ' . ($diff > 0 ? '#00C853' : '#E53935') . '">' . $diff . '% *</span>
                                                     </div>';
    }
    $reportBlock .= '

                                                </div>
                                            </div>
                                        </div>
                                      </div>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>';
    if ($seqNr > 1) {
        $reportBlock .= '     <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px; padding-top: 0;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <div style="line-height: 140%; text-align: left; word-wrap: break-word;">
                                        <p style="font-size: 14px; line-height: 140%; text-align: right;"><span style="color: #9e9d9d; font-size: 14px; line-height: 19.6px;">*compared to previous week</span></p>
                                      </div>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>';
    }
    $reportBlock .= '         <table style="font-family:\'Montserrat\',sans-serif; margin-top: 10px" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                          <td style="padding-right: 0px;padding-left: 0px;" align="center">

                                            <img align="center" border="0" src="' . $pieChart . '" alt="Proportions of types of awards earned" title="Proportions of types of awards earned" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 100%;max-width: 530px;" width="530"/>

                                          </td>
                                        </tr>
                                      </table>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>

                              <table style="font-family:\'Montserrat\',sans-serif; margin-top: 40px;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                          <td style="padding-right: 0px;padding-left: 0px;" align="center">

                                            <img align="center" border="0" src="' . $areaChart . '" alt="Comparison between XP earned this vs previous period" title="Comparison between XP earned this vs previous period" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 100%;max-width: 530px;" width="530"/>

                                          </td>
                                        </tr>
                                      </table>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>

                              <table style="font-family:\'Montserrat\',sans-serif; margin-top: 20px;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tbody>
                                  <tr>
                                    <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                      <div align="center">
                                        <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;font-family:\'Montserrat\',sans-serif;"><tr><td style="font-family:\'Montserrat\',sans-serif;" align="center"><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="" style="height:39px; v-text-anchor:middle; width:224px;" arcsize="10.5%" stroke="f" fillcolor="#ffa73b"><w:anchorlock/><center style="color:#FFFFFF;font-family:\'Montserrat\',sans-serif;"><![endif]-->
                                          <a href="' . URL . '/#/courses/' . $courseId . '/pages/' . $profilePageId . '/user/' . $studentId . '" target="_blank" style="box-sizing: border-box;display: inline-block;font-family:\'Montserrat\',sans-serif;text-decoration: none;-webkit-text-size-adjust: none;text-align: center;color: #FFFFFF; background-color: #ffa73b; border-radius: 4px;-webkit-border-radius: 4px; -moz-border-radius: 4px; width:auto; max-width:100%; overflow-wrap: break-word; word-break: break-word; word-wrap:break-word; mso-border-alt: none;">
                                            <span style="display:block;padding:10px 20px;line-height:120%;"><span style="font-size: 16px; line-height: 19.2px;"><strong><span style="line-height: 19.2px; font-size: 16px;">GO TO YOUR PROFILE</span></strong></span></span>
                                          </a>
                                        <!--[if mso]></center></v:roundrect></td></tr></table><![endif]-->
                                      </div>

                                    </td>
                                  </tr>
                                </tbody>
                              </table>

                              <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                            </div>
                          </div>
                          <!--[if (mso)|(IE)]></td><![endif]-->
                          <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                        </div>
                      </div>
                    </div>';

    $foresightBlock = '<div class="u-row-container" style="padding: 0px;background-color: transparent; position: relative; z-index: 2; margin-top: 20px">
                          <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 670px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                              <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:670px;"><tr style="background-color: transparent;"><![endif]-->

                              <!--[if (mso)|(IE)]><td align="center" width="670" style="background-color: #ffffff;width: 670px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                              <div class="u-col u-col-100" style="max-width: 320px;min-width: 670px;display: table-cell;vertical-align: top;">
                                <div style="background-color: #ffffff;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 10px; box-shadow: 0px 3px 5px 2px rgb(224, 223, 223)">
                                  <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 60px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->

                                    <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                      <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <h2 style="margin: 0px; color: #000000; text-align: left; word-wrap: break-word; font-weight: normal; font-family: \'Montserrat\',sans-serif; font-size: 24px;">
                                              <strong>🔎 Foresight Remarks</strong>
                                            </h1>

                                          </td>
                                        </tr>
                                      </tbody>
                                    </table>

                                    <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                      <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <div style="line-height: 170%; text-align: justify; word-wrap: break-word;">
                                              <p style="font-size: 16px; line-height: 25px;">There are <span style="font-weight: 700; font-size: 20px;">' . $timeLeft . '</span> ' . ($isWeekly ? 'weeks' : 'days') . ' left.';
    if ($timeLeft > 0) {
        $foresightBlock .= ' If you continue with the current rhythm you will achieve
                            <span style="font-weight: 800; font-size: 16px; color: ' . getGradeColor($prediction) . '">' . number_format($prediction, 0, ',', ' ') . ' XP</span>* by the end of the course.</p>
                            <p style="font-size: 16px; font-weight: 600; line-height: 25px; margin-top: 10px">';

        if ($prediction >= 16000) $foresightBlock .= 'You\'re doing a nice job. Keep it up! 🥳';
        elseif ($prediction >= 11000) $foresightBlock .= 'You\'re doing fine, but you can do better. Come one! 😁';
        else $foresightBlock .= 'You\'re falling behind. Try to improve this coming ' . ($isWeekly ? 'week' : 'day') . '! 🤓';
        $foresightBlock .= '</p>
                            <p style="color: #9e9d9d; font-size: 14px; line-height: 19.6px; margin-top: 25px">*This value is calculated based on what you have done so far, assuming you will keep the same XP average on every remaining ' . ($isWeekly ? 'week' : 'day') . '.</p>';

    } else $foresightBlock .= '</p>';
    $foresightBlock .= '                    </div>

                                          </td>
                                        </tr>
                                      </tbody>
                                    </table>

                                  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                                </div>
                              </div>
                              <!--[if (mso)|(IE)]></td><![endif]-->
                              <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                            </div>
                          </div>
                        </div>';

    $awardsBlock = '<div class="u-row-container" style="padding: 0px;background-color: transparent; position: relative; z-index: 2; margin-top: 20px">
                      <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 670px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                        <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                          <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:670px;"><tr style="background-color: transparent;"><![endif]-->

                          <!--[if (mso)|(IE)]><td align="center" width="670" style="background-color: #ffffff;width: 670px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                          <div class="u-col u-col-100" style="max-width: 320px;min-width: 670px;display: table-cell;vertical-align: top;">
                            <div style="background-color: #ffffff;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 10px; box-shadow: 0px 3px 5px 2px rgb(224, 223, 223)">
                              <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 60px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; margin-bottom: 30px;"><!--<![endif]-->

                                <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                  <tbody>
                                    <tr>
                                      <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                        <h2 style="margin: 0px; color: #000000; text-align: left; word-wrap: break-word; font-weight: normal; font-family: \'Montserrat\',sans-serif; font-size: 24px;">
                                          <strong>🏆 This Week\'s Awards</strong>
                                        </h1>

                                      </td>
                                    </tr>
                                  </tbody>
                                </table>

                                <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                  <tbody>
                                    <tr>
                                      <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                        <ul style="margin-top: 0; margin-bottom: 0; padding-left: 0; list-style: none;">';
    foreach ($awardsCurrentPeriod as $award) {
        $awardsBlock .= '                <li>
                                            <div style="display: flex; justify-content: space-between;">
                                              <table style="font-family:\'Montserrat\',sans-serif; margin-right: 10px;" role="presentation" cellpadding="0" cellspacing="0" width="25px" border="0">
                                                <tbody>
                                                <tr>
                                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:0; padding-top: 10px; font-family:\'Montserrat\',sans-serif;" align="left">

                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                      <tr>
                                                        <td style="padding: 0" align="center">
                                                            <img src="' . $imgsPath . '/' . getAwardImg($award["type"]) . '" style="width: 25px; height: 25px;">
                                                        </td>
                                                      </tr>
                                                    </table>

                                                  </td>
                                                </tr>
                                                </tbody>
                                              </table>

                                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="300px" border="0">
                                                <tbody>
                                                <tr>
                                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                      <tr>
                                                        <td style="padding-right: 0px;padding-left: 0px;" align="left">
                                                          <p style="font-weight: 600; font-size: 16px;">' . $award["description"] . '</p>
                                                          <p style="color: #9e9d9d; font-size: 14px; margin-top: 5px">' . awardTypeToDescription($award["type"]) . '</p>
                                                        </td>
                                                      </tr>
                                                    </table>

                                                  </td>
                                                </tr>
                                                </tbody>
                                              </table>

                                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="120px" border="0">
                                                <tbody>
                                                <tr>
                                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;padding-right: 35px;font-family:\'Montserrat\',sans-serif;" align="left">

                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                      <tr>
                                                        <td style="padding-right: 0px;padding-left: 0px;" align="right">
                                                          <p style="font-weight: 500; font-size: 13px;">' . $award["reward"] . ' XP</p>
                                                        </td>
                                                      </tr>
                                                    </table>

                                                  </td>
                                                </tr>
                                                </tbody>
                                              </table>

                                              <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="120px" border="0">
                                                <tbody>
                                                <tr>
                                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                      <tr>
                                                        <td style="padding-right: 0px;padding-left: 0px;" align="left">
                                                          <p style="font-size: 13px;">' . date_create($award["date"])->format("d/m/Y") . '</p>
                                                        </td>
                                                      </tr>
                                                    </table>

                                                  </td>
                                                </tr>
                                                </tbody>
                                              </table>
                                            </div>
                                          </li>';
    }

    $awardsBlock .= '                    </ul>

                                      </td>
                                    </tr>
                                  </tbody>
                                </table>

                              <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                            </div>
                          </div>
                          <!--[if (mso)|(IE)]></td><![endif]-->
                          <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                        </div>
                      </div>
                    </div>';

    $message = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
                  <head>
                    <!--[if gte mso 9]>
                    <xml>
                    <o:OfficeDocumentSettings>
                    <o:AllowPNG/>
                    <o:PixelsPerInch>96</o:PixelsPerInch>
                    </o:OfficeDocumentSettings>
                    </xml>
                    <![endif]-->
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <meta name="x-apple-disable-message-reformatting">
                    <!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]-->

                    <style type="text/css">
                    table, td { color: #484848; } a { color: #0000ee; text-decoration: underline; }
                    @media only screen and (min-width: 570px) {
                    .u-row {
                    width: 670px !important;
                    }
                    .u-row .u-col {
                    vertical-align: top;
                    }

                    .u-row .u-col-100 {
                    width: 670px !important;
                    }

                    }

                    @media (max-width: 570px) {
                    .u-row-container {
                    max-width: 100% !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                    }
                    .u-row .u-col {
                    min-width: 320px !important;
                    max-width: 100% !important;
                    display: block !important;
                    }
                    .u-row {
                    width: calc(100% - 40px) !important;
                    }
                    .u-col {
                    width: 100% !important;
                    }
                    .u-col > div {
                    margin: 0 auto;
                    }
                    }
                    body {
                    margin: 0;
                    padding: 0;
                    }

                    table,
                    tr,
                    td {
                    vertical-align: top;
                    border-collapse: collapse;
                    }

                    p {
                    margin: 0;
                    }

                    .ie-container table,
                    .mso-container table {
                    table-layout: fixed;
                    }

                    * {
                    line-height: inherit;
                    }

                    a[x-apple-data-detectors=\'true\'] {
                    color: inherit !important;
                    text-decoration: none !important;
                    }

                    </style>

                    <!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700&display=swap" rel="stylesheet" type="text/css"><!--<![endif]-->
                  </head>

                  <body class="clean-body u_body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #f4f4f4;color: #484848">
                    <!--[if IE]><div class="ie-container"><![endif]-->
                    <!--[if mso]><div class="mso-container"><![endif]-->
                    <table style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #f4f4f4;width:100%" cellpadding="0" cellspacing="0">
                      <tbody>
                        <tr>
                          <td style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
                          Weekly Progress Report
                          </td>
                        </tr>

                        <tr style="vertical-align: top">
                          <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top; position:relative">
                            <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #f4f4f4;"><![endif]-->

                            <div style="background-color: #ffa73b; height: 300px; position: absolute; top: 0; left: 0; width: 100%; z-index: 1;"></div>';

    $message .= $reportBlock;
    if ($timeLeft > 0) $message .= $foresightBlock;
    $message .= $awardsBlock;
    $message .= '<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
              </td>
            </tr>
          </tbody>
        </table>
        <!--[if mso]></div><![endif]-->
        <!--[if IE]></div><![endif]-->
      </body>

    </html>';

    if (!sendEmail($to, $subject, $message)) $error = true;
}


if (!$error) {
    Core::$systemDB->insert(Notifications::TABLE_PROGRESS_REPORT, ["course" => $courseId, "seqNr" => $seqNr]);
    logProgressReport($courseId, "Progress reports sent successfully.", "SUCCESS");

    if ($timeLeft == 0) {
        Notifications::removeCronJob($courseId);
        logProgressReport($courseId, "Last progress report sent. Removed Cron Job.", "SUCCESS");
    }
} else {
    logProgressReport($courseId, "Progress reports not sent.");
}

/**
 * Sends an HTML e-mail to one recipient.
 * It uses PHPMailer to be able to send e-mails to external recipients.
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 *
 * @return bool
 * @throws Exception
 */
function sendEmail(string $to, string $subject, string $message): bool
{
    $senderName = "GameCourse";

    if (Utils::strEndsWith($to, "@tecnico.ulisboa.pt")) { // internal e-mails
        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . $senderName . " <noreply@pcm.rnl.tecnico.ulisboa.pt>" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        return mail($to, $subject, $message, $headers);

    } else { // external e-mails (use PHPMailer)
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = "true";
        $mail->SMTPSecure = "tls";
        $mail->Port = "587";

        $mail->Username = GOOGLE_ACCOUNT_EMAIL;
        $mail->Password = GOOGLE_ACCOUNT_PASSWORD;
        $mail->From = GOOGLE_ACCOUNT_EMAIL;
        $mail->FromName = $senderName;

        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->addAddress($to);

        return $mail->send();
    }
}

function datediff(DateTime $date1, DateTime $date2, string $type): int
{
    if ($date1 > $date2) return datediff($date2, $date1, $type);
    return ceil($date1->diff($date2)->days / ($type === "weeks" ? 7 : 1));
}

function awardTypeToDescription($type): string {
    switch ($type) {
        case "bonus": return "Bonus";
        case "badge": return "Badge";
        case "quiz": return "Quiz";
        case "labs": return "Lab Assignment";
        case "skill": return "Skill Tree";
        case "presentantion": return "Presentation";
        case "streak": return "Streak";
        case "post": return "Post";
        case "assignment": return "Assignment";
        default: return ucfirst($type);
    }
}

function getAwardImg($type): string {
    switch ($type) {
        case "bonus": return 'gift.jpg';
        case "badge": return 'badge.jpg';
        case "quiz": return 'clipboard.jpg';
        case "labs": return 'terminal.jpg';
        case "skill": return 'light-bulb.jpg';
        case "presentation": return 'presentation.jpg';
        case "streak": return 'fire.jpg';
        case "post": return 'annotation.jpg';
        case "assignment": return 'paper-clip.jpg';
        case "tokens": return 'currency.jpg';
        default: return 'check.jpg';
    }
}

function getGradeColor($grade): string {
    if ($grade < 11000) return "#E53935";
    if ($grade < 16000) return "#FF9100";
    return "#00C853";
}

function logProgressReport($courseId, $result, $type="ERROR") {
    date_default_timezone_set("Europe/Lisbon");
    $sep = "\n================================================================================\n";
    $date = "[" . date("Y/m/d H:i:s") ."] : php : " . $type . " \n\n";
    $error = "\n\n================================================================================\n\n";
    file_put_contents(SERVER_PATH . "/logs/log_notifications_course_" . $courseId, $sep . $date . $result . $error, FILE_APPEND);
}
