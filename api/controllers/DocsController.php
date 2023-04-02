<?php
namespace API;

use Event\Event;
use Event\EventType;
use GameCourse\Adaptation\GameElement;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\Fenix\Fenix;
use GameCourse\Module\Leaderboard\Leaderboard;
use GameCourse\Module\Module;
use GameCourse\Module\Moodle\Moodle;
use GameCourse\Module\Profile\Profile;
use GameCourse\Role\Role;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\CreationMode;
use GameCourse\Views\Dictionary\CollectionLibrary;
use GameCourse\Views\Page\Page;
use GameCourse\Views\ViewHandler;
use OpenApi\Generator;

/**
 * This is the Docs controller, which holds API endpoints for
 * documentation related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 */
class DocsController
{
    /**
     * @OA\Get(
     *     path="/?module=docs&request=getAPIDocs",
     *     tags={"Documentation"},
     *     @OA\Response(response="200", description="GameCourse API documentation")
     * )
     */
    public function getAPIDocs()
    {
        API::requireAdminPermission();
        $openAPI = Generator::scan([ROOT_PATH . "controllers"]);
        API::response(json_decode($openAPI->toJSON()));
    }

    public function test()
    {
        $courseId = 1;
        $viewTree = [
            ["type" => "block", "children" => [
                [
                    ["aspect" => ["viewerRole" => "Student"], "type" => "text", "text" => "I'm a student"],
                    ["aspect" => ["viewerRole" => "Teacher"], "type" => "text", "text" => "I'm a teacher"],
                    ["type" => "text", "text" => "I'm neither a student nor a teacher"]
                ]
            ]],
        ];
//        $viewTree = [
//            ["type" => "block", "children" => [
//                [
//                    ["type" => "chart", "chartType" => "progress", "data" => ["value" => 21, "max" => 50]]
//                ]
//            ]]
//        ];
//        $viewTree = [
//            ["type" => "block", "children" => [
//                [
//                    ["type" => "collapse", "icon" => "arrow", "children" => [
//                        [
//                            ["type" => "text", "text" => "Click me to show/hide content"]
//                        ],
//                        [
//                            ["type" => "block", "children" => [
//                                [
//                                    ["type" => "text", "text" => "hello"]
//                                ],
//                                [
//                                    ["type" => "text", "text" => "world"]
//                                ]
//                            ]]
//                        ]
//                    ]]
//                ]
//            ]],
//        ];
//        $viewTree = [
//            ["type" => "block", "children" => [
//                [
//                    ["type" => "table", "children" => [
//                        [
//                            ["type" => "row", "rowType" => "header", "children" => [
//                                [["type" => "text", "text" => "Header1"]],
//                                [["type" => "text", "text" => "Header2"]],
//                                [["type" => "text", "text" => "Header3"]]
//                            ]]
//                        ],
//                        [
//                            ["type" => "row", "rowType" => "body", "children" => [
//                                [["type" => "block", "children" => [
//                                    [["type" => "text", "text" => "oi"]],
//                                    [["type" => "image", "src" => "https://images.unsplash.com/photo-1675667328761-7a373b11ce98"]]
//                                ]]],
//                                [["type" => "text", "text" => "Body2"]],
//                                [["type" => "text", "text" => "Body3"]],
//                            ]]
//                        ]
//                    ]]
//                ]
//            ]],
//        ];

        $leaderboard = [
            ["type" => "block", "children" => [
                [
                    ["type" => "block", "children" => [
                        [["type" => "icon", "icon" => "tabler-books"]],
                        [["type" => "text", "text" => "Leaderboard"]]
                    ]]
                ],
                [
                    ["type" => "table", "children" => [
                        [
                            ["type" => "row", "rowType" => "header", "children" => [
                                [["type" => "text", "text" => "#"]],
                                [["type" => "text", "text" => "Student"]],
                                [["type" => "text", "text" => "Experience"]],
                                [["type" => "text", "text" => "Level"]],
                                [["type" => "text", "text" => "Badges"]]
                            ]]
                        ],
                        [
                            ["type" => "row", "rowType" => "body", "loopData" => "{users.getStudents(true).sort()}", "children" => [
                                    [["type" => "text", "text" => "{%index + 1}"]],
                                    [["type" => "block", "children" => [
                                        [["type" => "image", "src" => "https://images.unsplash.com/photo-1675667328761-7a373b11ce98"]],
                                        [["type" => "block", "children" => [
                                            [["type" => "text", "text" => "Name"]],
                                            [["type" => "text", "text" => "Major"]]
                                        ]]]
                                    ]]],
                                    [["type" => "block", "children" => [
                                        [["type" => "text", "text" => "15000 XP"]],
                                        [["type" => "chart", "chartType" => "line", "data" => "{providers.XPEvolution(%item.id, \"day\")}"]]
                                    ]]],
                                    [["type" => "block", "children" => [
                                        [["type" => "text", "text" => "0-Absent Without Leave (AWOL)"]],
                                        [["type" => "text", "text" => "1000 for L1 at 1000 XP"]]
                                    ]]],
                                    [["type" => "chart", "chartType" => "progress", "data" => ["value" => 31, "max" => 65]]]
                            ]]
                        ]
                    ]]
                ]
            ]]
        ];

        $studentId = 2;
        $teacherId = 1;
        $watcherId = 3;
//        $vr = ViewHandler::insertViewTree($viewTree, 1);
//        var_dump($vr);
        $paramsToPopulate = ["course" => 1, "viewer" => $studentId, "user" => $studentId];
        API::response(ViewHandler::renderView(1758523867455415, Aspect::getAspects($courseId, $studentId, true), $paramsToPopulate));
    }

    public function test2()
    {
        $leaderboard = json_decode(file_get_contents(ROOT_PATH . "/modules/Leaderboard/templates/leaderboard.txt"), true);
        $relativeLeaderboard = json_decode(file_get_contents(ROOT_PATH . "/modules/Leaderboard/templates/relativeLeaderboard.txt"), true);

        $profile = json_decode(file_get_contents(ROOT_PATH . "/modules/Profile/templates/profile.txt"), true);

        $badges = json_decode(file_get_contents(ROOT_PATH . "/modules/Badges/templates/badges.txt"), true);

//        Page::addPage(1, CreationMode::BY_VALUE, "Leaderboard", $leaderboard);
//        Page::addPage(1, CreationMode::BY_VALUE, "Profile", $profile);
//        Page::addPage(1, CreationMode::BY_VALUE, "Badges", $badges);
        Page::addPage(1, CreationMode::BY_VALUE, "Skill Tree", ViewHandler::ROOT_VIEW);
        Page::addPage(1, CreationMode::BY_VALUE, "Streaks", ViewHandler::ROOT_VIEW);
    }

    public function test3()
    {
        $collectionLibrary = Core::dictionary()->getLibraryById(CollectionLibrary::ID);

        $collection = ["a" => ["id" => 1], "b" => ["id" => 2]];
        var_dump($collectionLibrary->index($collection, 2, "id")->getValue());
    }

    public function setupPages()
    {
        $leaderboard = json_decode(file_get_contents(ROOT_PATH . "/temp/leaderboard.txt"), true);
        $awards = json_decode(file_get_contents(MODULES_FOLDER . "/Awards/templates/userAwardsList.txt"), true);
        $spendings = json_decode(file_get_contents(MODULES_FOLDER . "/VirtualCurrency/templates/userSpendingsList.txt"), true);
        $profile = json_decode(file_get_contents(ROOT_PATH . "/temp/profile.txt"), true);
        $badges = json_decode(file_get_contents(ROOT_PATH . "/temp/badges.txt"), true);

        Page::addPage(9, CreationMode::BY_VALUE, "Leaderboard", $leaderboard);
        Page::addPage(9, CreationMode::BY_VALUE, "Awards", $awards);
        Page::addPage(9, CreationMode::BY_VALUE, "Spendings", $spendings);
        Page::addPage(9, CreationMode::BY_VALUE, "Profile", $profile);
        Page::addPage(9, CreationMode::BY_VALUE, "Badges", $badges);
        Page::addPage(9, CreationMode::BY_VALUE, "Skill Tree", [["aspect" => ["viewerRole" => null, "userRole" => "Student"], "type" => "block"]]);
        Page::addPage(9, CreationMode::BY_VALUE, "Streaks", [["aspect" => ["viewerRole" => null, "userRole" => "Student"], "type" => "block"]]);
    }


    public function addAdaptation(array $roles, int $course, string $moduleId){
        $parent = array_keys($roles)[0];
        $versions = array_keys($roles[$parent]);

        Role::addAdaptationRolesToCourse($course, $moduleId, $parent, $versions);
        foreach ($roles[$parent] as $key => $value){
            $roleId = Role::getRoleId($key, $course);
            GameElement::addGameElementDescription($roleId, $value[0]);
        }
    }

    public function createAdaptationRoles()
    {
        $course = new Course(9);

        $this->addAdaptation(Badges::ADAPTATION_BADGES, $course->getId(), Badges::ID);
        GameElement::addGameElement($course->getId(), Badges::ID);

        $this->addAdaptation(Leaderboard::ADAPTATION_LEADERBOARD, $course->getId(), Leaderboard::ID);
        GameElement::addGameElement($course->getId(), Leaderboard::ID);

        $this->addAdaptation(Profile::ADAPTATION_PROFILE, $course->getId(), Profile::ID);
        GameElement::addGameElement($course->getId(), Profile::ID);


    }
}
