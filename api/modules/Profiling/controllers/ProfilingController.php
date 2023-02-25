<?php
namespace API;

use Exception;
use GameCourse\Module\Profiling\Profiling;
use GameCourse\Role\Role;

/**
 * This is the Profiling controller, which holds API endpoints for
 * assigning students to clusters.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Profiling",
 *     description="API endpoints for assigning students to clusters"
 * )
 */
class ProfilingController
{
    /*** --------------------------------------------- ***/
    /*** ----------------- Overview ------------------ ***/
    /*** --------------------------------------------- ***/

    public function getHistory()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $profiling = new Profiling($course);
        $history = $profiling->getClusterHistory();
        $evolution = $profiling->getClusterEvolution($history["clusters"], $history["days"]);

        API::response([
            "days" => $history["days"],
            "history" => $history["clusters"],
            "nodes" => $evolution["nodes"],
            "data" => $evolution["data"]
        ]);
    }


    /*** --------------------------------------------- ***/
    /*** ----------------- Predictor ----------------- ***/
    /*** --------------------------------------------- ***/

    public function runPredictor()
    {
        API::requireValues("courseId", "method", "endDate");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $method = API::getValue("method");
        $endDate = API::getValue("endDate");

        $profiling = new Profiling($course);
        $profiling->runPredictor($method, $endDate);
    }

    public function checkPredictorStatus()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $profiling = new Profiling($course);
        $status = $profiling->checkPredictorStatus();
        if (array_key_exists("error", $status))
            API::error($status["error"]);
        API::response($status);
    }


    /*** --------------------------------------------- ***/
    /*** ----------------- Profiler ------------------ ***/
    /*** --------------------------------------------- ***/

    public function getLastRun()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $profiling = new Profiling($course);
        API::response($profiling->getLastRun());
    }

    /**
     *
     * @throws Exception
     */
    public function runProfiler()
    {
        API::requireValues("courseId", "nrClusters", "minSize", "endDate");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $nrClusters = API::getValue("nrClusters", "int");
        $minSize = API::getValue("minSize", "int");
        $endDate = API::getValue("endDate");

        $profiling = new Profiling($course);
        $profiling->runProfiler($nrClusters, $minSize, $endDate);
    }

    public function checkProfilerStatus()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $profiling = new Profiling($course);
        $status = $profiling->checkProfilerStatus();
        if (array_key_exists("error", $status))
            API::error($status["error"]);
        API::response($status);
    }


    /*** --------------------------------------------- ***/
    /*** ----------------- Clusters ------------------ ***/
    /*** --------------------------------------------- ***/

    public function getSavedClusters()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $profiling = new Profiling($course);
        $savedClusters = $profiling->getSavedClusters();
        $names = Role::getChildrenNamesOfRole($course->getRolesHierarchy(), Profiling::PROFILING_ROLE);
        API::response(["saved" => $savedClusters, "names" => $names]);
    }

    public function saveClusters()
    {
        API::requireValues("courseId", "clusters");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $clusters = API::getValue("clusters");
        $profiling = new Profiling($course);
        $profiling->saveClusters($clusters);
    }

    public function deleteSavedClusters()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $profiling = new Profiling($course);
        $profiling->deleteSavedClusters();
    }

    public function commitClusters()
    {
        API::requireValues("courseId", "clusters");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $clusters = API::getValue("clusters");
        $profiling = new Profiling($course);
        $profiling->commitClusters($clusters);
    }
}
