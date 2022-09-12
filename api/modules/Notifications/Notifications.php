<?php
namespace GameCourse\Module\Notifications;

use DateTime;
use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Profile\Profile;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use GameCourse\Views\Page\Page;
use Utils\CronJob;
use Utils\Utils;

/**
 * This is the Notifications module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Notifications extends Module
{
    const TABLE_NOTIFICATIONS_PROGRESS_REPORT = "notifications_progress_report";
    const TABLE_NOTIFICATIONS_PROGRESS_REPORT_HISTORY = "notifications_progress_report_history";
    const TABLE_NOTIFICATIONS_PROGRESS_REPORT_CONFIG = "notifications_progress_report_config";

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Notifications";  // NOTE: must match the name of the class
    const NAME = "Notifications";
    const DESCRIPTION = "Allows email notifications for progress reports.";
    const TYPE = ModuleType::UTILITY;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Awards::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => Profile::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => VirtualCurrency::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/'];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();

        // Init config
        Core::database()->insert(self::TABLE_NOTIFICATIONS_PROGRESS_REPORT_CONFIG, ["course" => $this->course->getId()]);

        // Init logs file
        file_put_contents($this->getLogsPath(), "");

        $this->initEvents();
    }

    public function initEvents()
    {
        Event::listen(EventType::COURSE_DISABLED, function (int $courseId) {
            if ($courseId == $this->course->getId()) {
                $config = $this->getProgressReportConfig();
                $this->saveProgressReportConfig($config["endDate"], $config["periodicityTime"],  $config["periodicityHours"],
                    $config["periodicityDay"], false);
            }
        });
    }

    public function copyTo(Course $copyTo)
    {
        $copiedModule = new Notifications($copyTo);

        // Copy config
        $config = $this->getProgressReportConfig();
        $copiedModule->saveProgressReportConfig(null, $config["periodicityTime"], $config["periodicityHours"],
            $config["periodicityDay"], false);
    }

    public function disable()
    {
        $this->cleanDatabase();
        $this->removeEvents();

        CronJob::removeCronJob("ProgressReport", $this->course->getId());
        unlink($this->getLogsPath());
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getPersonalizedConfig(): ?string
    {
        return $this->id;
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ----- Progress Report ------ ***/

    public function getProgressReportConfig(): array
    {
        $config = Core::database()->select(self::TABLE_NOTIFICATIONS_PROGRESS_REPORT_CONFIG, ["course" => $this->course->getId()]);
        return [
            "endDate" => $config["endDate"],
            "periodicityTime" => $config["periodicityTime"],
            "periodicityHours" => isset($config["periodicityHours"]) ? intval($config["periodicityHours"]) : null,
            "periodicityDay" => isset($config["periodicityDay"]) ? intval($config["periodicityDay"]) : null,
            "isEnabled" => boolval($config["isEnabled"])
        ];
    }

    public function saveProgressReportConfig(?string $endDate, ?string $periodicityTime, ?int $periodicityHours,
                                             ?int $periodicityDay, ?bool $isEnabled)
    {
        Core::database()->update(self::TABLE_NOTIFICATIONS_PROGRESS_REPORT_CONFIG, [
            "endDate" => $endDate,
            "periodicityTime" => $periodicityTime,
            "periodicityHours" => $periodicityHours,
            "periodicityDay" => $periodicityDay,
            "isEnabled" => +$isEnabled
        ], ["course" => $this->course->getId()]);

        if (!$isEnabled) { // disable progress report
            CronJob::removeCronJob("ProgressReport", $this->course->getId());

        } else { // enable progress report
            new CronJob("ProgressReport", $this->course->getId(), $periodicityHours, $periodicityTime, $periodicityDay);
        }
    }

    public function getLogsPath(): string
    {
        return LOGS_FOLDER . "/progress_report_" . $this->course->getId() . ".txt";
    }


    /**
     * Gets the progress report HTML template for a specific user,
     * along with values in it.
     *
     * @param int $userId
     * @param int $seqNr
     * @param array|null $info
     * @return array
     * @throws Exception
     */
    public function getUserProgressReport(int $userId, int $seqNr, array $info = null): array
    {
        // Get all the information needed if not already given
        if (is_null($info)) $info = $this->getStaticInfo($seqNr);
        $tokensName = $info['tokensName'] ?? null;

        $user = $this->course->getCourseUserById($userId);
        $userName = $user->getNickname() ?? explode(" ", $user->getName())[0];

        $awards = (new Awards($this->course))->getUserAwards($userId);
        $awardsCurrentPeriod = array_filter($awards, function ($award) use ($info)  { return $award["date"] >= $info['startPeriodDate'] && $award["date"] <= $info['endPeriodDate']; });
        $awardsPreviousPeriod = array_filter($awards, function ($award) use ($info)  { return $award["date"] >= $info['startPreviousPeriodDate'] && $award["date"] <= $info['endPreviousPeriodDate']; });

        // FIXME: not accomodating other periodicities aside from Weekly
        $startDay = intval(date_create($info['currentDate'])->format("w"));
        $weekdays = range(0, 6);
        $end = array_slice($weekdays, 0, $startDay);
        $begin = array_splice($weekdays, $startDay);
        $weekdays = array_merge($begin, $end, [$startDay]);
        $weekdays = array_map(function ($day) { return date('D', strtotime("Sunday " . $day . " days")); }, $weekdays);

        $awardsXPCurrentPeriodByDay = [0, 0, 0, 0, 0, 0, 0, 0];
        $awardsByType = [];
        foreach ($awardsCurrentPeriod as $award) {
            if ($award["type"] == "tokens") continue;

            $weekday = intval(date_create($award["date"])->format("w"));
            if ($weekday < $startDay) $index = $weekday + (7 - $startDay);
            elseif ($weekday > $startDay) $index = $weekday - $startDay;
            else if (date_create($award["date"])->format("Y-m-d") == date_create($info['endPeriodDate'])->format("Y-m-d")) $index = 7;
            else $index = 0;
            $awardsXPCurrentPeriodByDay[$index] += intval($award["reward"]);

            $typeDescription = self::awardTypeToDescription($award["type"], $tokensName);
            if (!isset($awardsByType[$typeDescription])) $awardsByType[$typeDescription] = 0;
            $awardsByType[$typeDescription] += 1;
        }

        $awardsXPPreviousPeriodByDay = [0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($awardsPreviousPeriod as $award) {
            if ($award["type"] == "tokens") continue;

            $weekday = intval(date_create($award["date"])->format("w"));
            if ($weekday < $startDay) $index = $weekday + (7 - $startDay);
            elseif ($weekday > $startDay) $index = $weekday - $startDay;
            else if (date_create($award["date"])->format("Y-m-d") == date_create($info['endPreviousPeriodDate'])->format("Y-m-d")) $index = 7;
            else $index = 0;
            $awardsXPPreviousPeriodByDay[$index] += intval($award["reward"]);
        }

        $areaChart = $info['areaChartURL'] . "?data1=" . implode(",", $awardsXPCurrentPeriodByDay) .
            "&data2=" . implode(",", $awardsXPPreviousPeriodByDay) .
            "&labels=" . implode(",", $weekdays);
        $pieChart = $info['pieChartURL'] . "?data1=" . (count($awardsByType) > 0 ? implode(",", array_values($awardsByType)) : 0) .
            "&labels=" . (count($awardsByType) > 0 ? implode(",", array_map(function ($label) { return ucfirst($label); }, array_keys($awardsByType))) : "No data");

        $isGeneratingReport = Core::database()->select(self::TABLE_NOTIFICATIONS_PROGRESS_REPORT, ["course" => $this->course->getId(), "seqNr" => $seqNr], "count(*)") == 0;
        $totalXP = $isGeneratingReport ? (new XPLevels($this->course))->getUserXP($userId) :
            intval(Core::database()->select(self::TABLE_NOTIFICATIONS_PROGRESS_REPORT_HISTORY, ["course" => $this->course->getId(), "user" => $userId, "seqNr" => $seqNr], "totalXP"));

        $currentPeriodXP = array_reduce($awardsCurrentPeriod, function ($carry, $award) {
            $carry += $award["type"] == "tokens" ? 0 : intval($award["reward"]);
            return $carry;
        }, 0);
        $previousPeriodXP = array_reduce($awardsPreviousPeriod, function ($carry, $award) {
            $carry += $award["type"] == "tokens" ? 0 : intval($award["reward"]);
            return $carry;
        }, 0);

        $diff = $previousPeriodXP == 0 ? 0 : round(-(($previousPeriodXP - $currentPeriodXP) * 100 / $previousPeriodXP));
        if ($info['timeLeft'] > 0) {
            $weekAvg = $totalXP / $seqNr;
            $hasPresentation = !empty(Core::database()->select(Awards::TABLE_AWARD, ["course" => $this->course->getId(), "user" => $userId, "type" => "presentation"], "reward"));
            $prediction = round($totalXP + ($hasPresentation ? 0 : 2500) + $weekAvg * $info['timeLeft']); // FIXME: presentation XP is hard-coded
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
                                        <strong>' . $info['periodicity'] . ' Progress Report</strong>
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
            date_format(date_create($info['startPeriodDate']), "l, F jS") . ' - ' . date_format(date_create($info['endPeriodDate']), "l, F jS") . '</span></p>
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
                                        <p style="font-size: 14px; font-weight: 500; line-height: 140%;"><span style="font-size: 28px; line-height: 39.2px;">Hey ' . $userName . ',</span></p>
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
                                        <p style="font-size: 16px; line-height: 25px;">Here is a summary of your progress in <strong>' . $info['courseName'] . ' ' . $info['courseYear'] . '</strong> ' . ($info['isWeekly'] ? 'last week' : 'yesterday') . '.</p>
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
                                                    <img src="' . $info['imgsPath'] . '/school-cap.jpg" style="width: 25px; height: 25px; margin-right: 8px;">
                                                    <span style="text-transform: uppercase; font-weight: 600; color: #757575">Total</span>
                                                </div>
                                                <p style="margin-bottom: 0; font-size: 24px; font-weight: 700; margin-top: 10px;">' . number_format($totalXP, 0, ',', ' ') . ' XP</p>
                                            </div>

                                            <div style="display: flex; flex-direction: column; justify-content: center; border-color: #dfdfdf; border-width: 2; margin-left: 20px;
                                                border-style: solid; border-radius: 8px; padding-top: 12px; padding-left: 20px; padding-right: 20px; padding-bottom: 12px; width: 100%;">
                                                <div style="display: flex; align-items: center;">
                                                    <img src="' . $info['imgsPath'] . '/calendar.jpg" style="width: 20px; height: 20px; margin-right: 8px;">
                                                    <span style="text-transform: uppercase; font-weight: 600; color: #757575">This week</span>
                                                </div>
                                                <div style="display: flex; align-items: center; margin-top: 10px;">
                                                    <p style="margin-bottom: 0; font-size: 24px; font-weight: 700; margin-top: 0;">' . number_format($currentPeriodXP, 0, ',', ' ') . ' XP</p>';
        if ($diff != 0) {
            $reportBlock .= '                            <div style="display: flex; align-items: center; margin-left: 8px;">
                                                        <img src="' . $info['imgsPath'] . ($diff > 0 ? '/arrow-up.jpg' : '/arrow-down.jpg') . '" style="width: 15px; height: 15px;">
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
                                          <a href="' . URL . '/#/courses/' . $this->course->getId() . '/pages/' . $info['profilePageId'] . '/user/' . $userId . '" target="_blank" style="box-sizing: border-box;display: inline-block;font-family:\'Montserrat\',sans-serif;text-decoration: none;-webkit-text-size-adjust: none;text-align: center;color: #FFFFFF; background-color: #ffa73b; border-radius: 4px;-webkit-border-radius: 4px; -moz-border-radius: 4px; width:auto; max-width:100%; overflow-wrap: break-word; word-break: break-word; word-wrap:break-word; mso-border-alt: none;">
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
                                              <p style="font-size: 16px; line-height: 25px;">There are <span style="font-weight: 700; font-size: 20px;">' . $info['timeLeft'] . '</span> ' . ($info['isWeekly'] ? 'weeks' : 'days') . ' left.';
        if ($info['timeLeft'] > 0) {
            $foresightBlock .= ' If you continue with the current rhythm you will achieve
                            <span style="font-weight: 800; font-size: 16px; color: ' . Notifications::getGradeColor($prediction) . '">' . number_format($prediction, 0, ',', ' ') . ' XP</span>* by the end of the course.</p>
                            <p style="font-size: 16px; font-weight: 600; line-height: 25px; margin-top: 10px">';

            if ($prediction >= 16000) $foresightBlock .= 'You\'re doing a nice job. Keep it up! 🥳';
            elseif ($prediction >= 11000) $foresightBlock .= 'You\'re doing fine, but you can do better. Come one! 😁';
            else $foresightBlock .= 'You\'re falling behind. Try to improve this coming ' . ($info['isWeekly'] ? 'week' : 'day') . '! 🤓';
            $foresightBlock .= '</p>
                            <p style="color: #9e9d9d; font-size: 14px; line-height: 19.6px; margin-top: 25px">*This value is calculated based on what you have done so far, assuming you will keep the same XP average on every remaining ' . ($info['isWeekly'] ? 'week' : 'day') . '.</p>';

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
                                                            <img src="' . $info['imgsPath'] . '/' . Notifications::getAwardImg($award["type"]) . '" style="width: 25px; height: 25px;">
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
                                                          <p style="color: #9e9d9d; font-size: 14px; margin-top: 5px">' . Notifications::awardTypeToDescription($award["type"], $tokensName) . '</p>
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
                                                          <p style="font-weight: 500; font-size: 13px;">' . $award["reward"] . ($award["type"] == "tokens" ? '' : ' XP') .  '</p>
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

        $report = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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

        $report .= $reportBlock;
        if ($info['timeLeft'] > 0) $report .= $foresightBlock;
        $report .= $awardsBlock;
        $report .= '<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
              </td>
            </tr>
          </tbody>
        </table>
        <!--[if mso]></div><![endif]-->
        <!--[if IE]></div><![endif]-->
      </body>

    </html>';

        return array($report, $totalXP, $currentPeriodXP, $diff, $info['timeLeft'], $prediction ?? null, $pieChart, $areaChart);
    }

    /**
     * Gets information that doesn't change for any user.
     *
     * @param int|null $seqNr
     * @return array
     * @throws Exception
     */
    public function getStaticInfo(int $seqNr = null): array
    {
        if ($this->course->getModuleById(Profile::ID)->isEnabled()) {
            $profilePageId = Core::database()->select(Page::TABLE_PAGE, ["course" => $this->course->getId(), "name" => "Profile"], "id");
        } else $profilePageId = null;

        $imgsPath = API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . Notifications::ID . "/assets";

        $config = Core::database()->select(self::TABLE_NOTIFICATIONS_PROGRESS_REPORT_CONFIG, ["course" => $this->course->getId()]);
        $periodicity = $config["periodicityTime"];
        $isWeekly = $periodicity === "Weekly";

        if ($seqNr != null) { // get previous report
            $reportInfo = Core::database()->select(self::TABLE_NOTIFICATIONS_PROGRESS_REPORT, ["course" => $this->course->getId(), "seqNr" => $seqNr]);
            $currentDate = $reportInfo['periodEnd'];

        } else { // generate new report
            $currentDate = date_create()->format("Y-m-d H:i:s");
        }

        $startPeriodDate = date_sub(date_create($currentDate), date_interval_create_from_date_string($isWeekly ? "1 weeks" : "1 days"))->format("Y-m-d H:i:s");
        $endPeriodDate = $currentDate;
        $startPreviousPeriodDate = date_sub(date_create($startPeriodDate), date_interval_create_from_date_string($isWeekly ? "1 weeks" : "1 days"))->format("Y-m-d H:i:s");
        $endPreviousPeriodDate = $startPeriodDate;

        $timeLeft = self::datediff(date_create($currentDate), date_create($config["endDate"]), $isWeekly ? "weeks" : "days");

        // FIXME: charts don't exist anymore; always download image and save to user_data
        $pieChartURL = "https://quickchart.io/chart/render/zm-f7746c74-abbb-4250-a0c2-11451cb8f7d6"; // editor: https://quickchart.io/chart-maker/edit/zm-7f2783e0-a82c-41b0-920b-9ce72210fd0a
        $areaChartURL = "https://quickchart.io/chart/render/zm-a1ad2eb1-9680-4fc1-9e83-008080e79c6b"; // editor: https://quickchart.io/chart-maker/edit/zm-72a28b6b-b495-4c9d-a4e8-ce3e5ce5b013

        if ($this->course->getModuleById(VirtualCurrency::ID)->isEnabled())
            $tokensName = Core::database()->select(VirtualCurrency::TABLE_VC_CONFIG, ["course" => $this->course->getId()], "name");
        else $tokensName = null;

        return ['courseName' => $this->course->getName(), 'courseYear' => $this->course->getYear(), 'profilePageId' => $profilePageId ?? null, 'imgsPath' => $imgsPath,
            'periodicity' => $periodicity, 'isWeekly' => $isWeekly, 'currentDate' => $currentDate, 'startPeriodDate' => $startPeriodDate,
            'endPeriodDate' => $endPeriodDate, 'startPreviousPeriodDate' => $startPreviousPeriodDate, 'endPreviousPeriodDate' => $endPreviousPeriodDate,
            'timeLeft' => $timeLeft, 'pieChartURL' => $pieChartURL, 'areaChartURL' => $areaChartURL, 'tokensName' => $tokensName ?? null
        ];
    }

    private static function datediff(DateTime $date1, DateTime $date2, string $type): int
    {
        if ($date1 > $date2) return Notifications::datediff($date2, $date1, $type);
        return ceil($date1->diff($date2)->days / ($type === "weeks" ? 7 : 1));
    }

    private static function awardTypeToDescription($type, ?string $tokensName) : string {
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
            case "tokens": return $tokensName ?? "Token(s)";
            default: return ucfirst($type);
        }
    }

    private static function getAwardImg($type): string {
        switch ($type) {
            case "bonus": return 'gift.jpg';
            case "badge": return 'badge.jpg';
            case "exam": return 'book-open.jpg';
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

    private static function getGradeColor($grade): string {
        if ($grade < 11000) return "#E53935";
        if ($grade < 16000) return "#FF9100";
        return "#00C853";
    }


    /**
     * Gets all reports sent.
     *
     * @return array
     */
    public function getReports(): array
    {
        $table = self::TABLE_NOTIFICATIONS_PROGRESS_REPORT;
        $reports = Core::database()->selectMultiple($table, ["course" => $this->course->getId()]);
        foreach ($reports as &$report) {
            unset($report["course"]);
            $report["seqNr"] = intval($report["seqNr"]);
            $report["reportsSent"] = intval($report["reportsSent"]);
        }
        return $reports;
    }

    /**
     * Gets all students who received a given report.
     *
     * @param int $seqNr
     * @return array
     */
    public function getStudentsWithReport(int $seqNr): array
    {
        $table = self::TABLE_NOTIFICATIONS_PROGRESS_REPORT_HISTORY . " r JOIN " . User::TABLE_USER . " u on r.user=u.id";
        $reports = Core::database()->selectMultiple($table, ["course" => $this->course->getId(), "seqNr" => $seqNr]);
        foreach ($reports as &$report) {
            unset($report["course"]);
            unset($report["seqNr"]);
            $report["user"] = (new User($report["user"]))->getData();
            $report["totalXP"] = intval($report["totalXP"]);
            $report["periodXP"] = intval($report["periodXP"]);
            $report["diffXP"] = intval($report["diffXP"]);
            $report["timeLeft"] = intval($report["timeLeft"]);
            $report["prediction"] = intval($report["prediction"]);
        }
        return $reports;
    }
}