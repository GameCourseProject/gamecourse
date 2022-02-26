<?php
namespace Modules\Notifications;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\CronJob;
use GameCourse\Module;
use GameCourse\ModuleLoader;

class Notifications extends Module
{
    const ID = 'notifications';

    const TABLE_PROGRESS_REPORT = self::ID . '_progress_report';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->setupData();
    }

    public function initAPIEndpoints()
    {
        /**
         * Gets progress report variables.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getProgressReportVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(array('getProgressReportVars' => $this->getProgressReportVars($courseId)));
        });

        /**
         * Sets progress report variables.
         *
         * @param int $courseId
         * @param $progressReport
         */
        API::registerFunction(self::ID, 'setProgressReportVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'progressReport');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $progressReport = API::getValue('progressReport');
            $this->setProgressReportVars($courseId, $progressReport);
        });
    }

    public function setupResources()
    {
        parent::addResources('imgs/');
    }

    public function setupData()
    {
        $this->addTables(self::ID, self::TABLE_PROGRESS_REPORT);
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    public function disable(int $courseId)
    {
        new CronJob("ProgressReport", $courseId, null, null, null, true);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_personalized_config(): bool
    {
        return true;
    }

    public function get_personalized_function(): string
    {
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows(int $courseId)
    {
        Core::$systemDB->delete(self::TABLE_PROGRESS_REPORT, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getProgressReportVars($courseId): array
    {
        $progressReportVarsDB = Core::$systemDB->select(self::TABLE_PROGRESS_REPORT, ["course" => $courseId], "*");
        $isEmpty = empty($progressReportVarsDB);

        return [
            "endDate" => $isEmpty ? "" : explode(" ", $progressReportVarsDB["endDate"])[0],
            "periodicityTime" => $isEmpty ? "Weekly" : $progressReportVarsDB["periodicityTime"],
            "periodicityHours" => $isEmpty ? 18 : intval($progressReportVarsDB["periodicityHours"]),
            "periodicityDay" => $isEmpty ? 5 : intval($progressReportVarsDB["periodicityDay"]),
            "isEnabled" => $isEmpty ? false : $progressReportVarsDB["isEnabled"]
        ];
    }

    private function setProgressReportVars($courseId, $progressReport)
    {
        $arrayToDb = [
            "course" => $courseId,
            "endDate" => $progressReport["endDate"] . " 23:59:59",
            "periodicityTime" => $progressReport['periodicityTime'],
            "periodicityHours" => $progressReport['periodicityHours'],
            "periodicityDay" => $progressReport['periodicityDay'],
            "isEnabled" => filter_var($progressReport["isEnabled"], FILTER_VALIDATE_BOOLEAN)
        ];

        if (empty(Core::$systemDB->select(self::TABLE_PROGRESS_REPORT, ["course" => $courseId], "*"))) {
            Core::$systemDB->insert(self::TABLE_PROGRESS_REPORT, $arrayToDb);
        } else {
            Core::$systemDB->update(self::TABLE_PROGRESS_REPORT, $arrayToDb, ["course" => $courseId]);
        }

        if (!$progressReport['isEnabled']) { // disable progress report
            $this->removeCronJob($courseId);

        } else { // enable progress report
            $this->setCronJob($courseId, $progressReport['periodicityHours'], $progressReport['periodicityTime'], $progressReport['periodicityDay']);
        }
    }

    private function setCronJob(int $courseId, int $periodicityHours, string $periodicityTime, int $periodicityDay)
    {
        API::verifyCourseIsActive($courseId);

        $progressReportVars = Core::$systemDB->select(self::TABLE_PROGRESS_REPORT, ["course" => $courseId], "*");
        if ($progressReportVars){
            new CronJob("ProgressReport", $courseId, $periodicityHours, $periodicityTime, $periodicityDay);

        } else {
            API::error("Please set the progress report variables");
        }
    }

    private function removeCronJob($courseId)
    {
        Core::$systemDB->delete(self::TABLE_PROGRESS_REPORT, ["course" => $courseId]);
        new CronJob("ProgressReport", $courseId, null, null, null, true);
    }
}

ModuleLoader::registerModule(array(
    'id' => Notifications::ID,
    'name' => 'Notifications',
    'description' => 'Allows email notifications for progress reports.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new Notifications();
    }
));
