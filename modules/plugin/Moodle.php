<?php

namespace Modules\Plugin;

use GameCourse\Core;
use GameCourse\API;
use GameCourse\User;
use GameCourse\CourseUser;
use GameCourse\Course;

class Moodle
{
    private $dbserver; //"db.rnl.tecnico.ulisboa.pt";
    private $dbuser; //"pcm_moodle";
    private $dbpass; //"Dkr1iRwEekJiPSHX9CeNznHlks";
    private $dbname; //"pcm_moodle";
    private $dbport;
    private $prefix;
    private $time;
    private $course; //courseId no moodle
    private $user;
    private $courseId; //courseId no gamecourse
    private $courseGameCourse;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
        $this->courseGameCourse = new Course($this->courseId);
    }

    public function getDBConfigValues()
    {
        $moodleVarsDB = Core::$systemDB->select("config_moodle", ["course" => $this->courseId], "*");
        $this->dbserver = $moodleVarsDB["dbServer"];
        $this->dbuser = $moodleVarsDB["dbUser"];
        $this->dbpass = $moodleVarsDB["dbPass"];
        $this->dbname = $moodleVarsDB["dbName"];
        $this->dbport = $moodleVarsDB["dbPort"];
        $this->prefix = $moodleVarsDB["tablesPrefix"];
        $this->time = $moodleVarsDB["moodleTime"];
        $this->course = $moodleVarsDB["moodleCourse"];
        $this->user = $moodleVarsDB["moodleUser"];
    }

    public function getLogs()
    {
        $this->getDBConfigValues();
        if (!($this->time) && !($this->user)) {
            $sql = "select " . $this->prefix . "logstore_standard_log.id,  courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id=userid inner join " . $this->prefix . "course on " . $this->prefix . "course.id = courseid";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= " order by " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if ($this->time && !($this->user)) {
            $sql = "select " . $this->prefix . "logstore_standard_log.id,  courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid,  " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id=userid inner join " . $this->prefix . "course on " . $this->prefix . "course.id = courseid ";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= " where " . $this->prefix . "logstore_standard_log.timecreated>=" . $this->time . " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if (!($this->time) && $this->user) {
            $sql = "select  " . $this->prefix . "logstore_standard_log.id, courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,   contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid , objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id='" . $this->user . "' inner join " . $this->prefix . "course on " . $this->prefix . "course.id=courseid ";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if ($this->time && $this->user) {
            $sql = "select  " . $this->prefix . "logstore_standard_log.id, courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id='" . $this->user . "' inner join " . $this->prefix . "course on " . $this->prefix . "course.id=courseid ";
            if ($this->course) {
                $sql .= " and p.course=" . $this->course;
            }
            $sql .= " where " . $this->prefix . "logstore_standard_log.timecreated>=" . $this->time . " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        }
        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return array($db, $result);
    }
    public function parseLogsToDB($row, $db)
    {
        $temp_info = null;
        $temp_url = null;
        $temp_module = null;

        $temp_action = array_key_exists("action", $row) ? $row['action'] : null;
        $other_ = array_key_exists("other", $row) ? json_decode($row['other']) : null;
        $temp_username = array_key_exists("username", $row) ?  $row["username"] : null;
        // if ($row['target'] == 'calendar_event') {
        //     $temp_module = "calendar";
        //     $temp_url = "event.php?action=edit&id=" . $row['objectid'];
        //     $temp_info = $other_->name;
        // }
        // if ($row['component'] == 'mod_quiz') {
        //     $temp_url = 'view.php?id=' . $row['cmid'];
        //     $temp_action = "quiz " . $row['action'];
        //     if (
        //         $row["target"] == "edit_page" || $row["target"] == "attempt_summary" || $row["target"] == "report" || $row["target"] == "attempt_preview"
        //         || ($row["target"] == "attempt" && $row["action"] != "started")
        //     ) {
        //         $temp_info = $other_->quizid;
        //     } else { //course_module
        //         $temp_info = $row['objectid'];
        //     }
        //     $sqlQuiz = "SELECT name FROM " . $this->prefix . "quiz where id=" . $temp_info . ";";
        //     $resultQuiz = mysqli_query($db, $sqlQuiz);
        //     $rowQuiz = mysqli_fetch_assoc($resultQuiz);
        //     $temp_module = $rowQuiz['name'];
        // }
        // if ($row['component'] == 'mod_chat') {
        //     $temp_url = "view.php?id=" . $row['cmid'];
        //     $temp_info = $row['objectid'];
        //     $temp_action = "chat" . $row['action'];

        //     if ($row['objecttable'] == "chat_messages") {
        //         $temp_action = "chat message " . $row['action'];
        //         $sqlChatMessages = "SELECT name FROM " . $this->prefix . "chat_messages where id=" . $temp_info . ";";
        //         $resultChatMessage = mysqli_query($db, $sqlChatMessages);
        //         $rowChatMessage = mysqli_fetch_assoc($resultChatMessage);
        //         $temp_info = $rowChatMessage["chatid"];
        //     }
        //     $sqlChat = "SELECT name FROM " . $this->prefix . "chat where id=" . $temp_info . ";";
        //     $resultChat = mysqli_query($db, $sqlChat);
        //     $rowChat = mysqli_fetch_assoc($resultChat);
        //     $temp_module = $rowChat['name'];
        // }
        if (array_key_exists("component", $row)) {

            if ($row['component'] == 'mod_questionnaire') {
                $temp_url = 'view.php?id=' . $row['cmid'];
                $temp_action = "questionnaire " . $row['action'];
                if ($row["action"] == "submitted") {
                    $temp_info = $other_->questionnaireid;
                } else {
                    $temp_info = $row['objectid'];
                }

                $sqlQuestionnaire = "SELECT name FROM " . $this->prefix . "questionnaire where id=" . $temp_info . ";";
                $resultQuestionnaire = mysqli_query($db, $sqlQuestionnaire);
                if ($resultQuestionnaire) {
                    $rowQuestionnaire = mysqli_fetch_assoc($resultQuestionnaire);
                    if ($rowQuestionnaire) {
                        $temp_module = array_key_exists("name", $rowQuestionnaire) ?  $rowQuestionnaire['name'] : null;
                    }
                }
            }

            if ($row['component'] == 'mod_page') {
                $temp_url = "view.php?id=" . $row['cmid'];
                $temp_action = "page " . $row['action'];
                $temp_info = $row['objectid'];
                $sqlPage = "SELECT name FROM " . $this->prefix . "page where id=" . $temp_info . ";";
                $resultPage = mysqli_query($db, $sqlPage);
                $rowPage = mysqli_fetch_assoc($resultPage);
                $temp_module = $rowPage['name'];
            }

            if ($row["component"] == "mod_assign") {
                $temp_url = "view.php?id=" . $row['cmid'];
                $temp_action = "assignment " . $row['action'];
                if ($row["target"] == "course_module") {
                    $sqlAssign = "SELECT name FROM " . $this->prefix . "assign inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "assign.id =objectid where component='mod_assign' and objectid =" . $row["objectid"] . ";";
                    $resultAssign = mysqli_query($db, $sqlAssign);
                    $rowAssign = mysqli_fetch_assoc($resultAssign);
                    $temp_module = $rowAssign['name'];
                } else if (
                    $row["target"] == "submission_form" || $row["target"] == "grading_table" || $row["target"] == "grading_form"
                    || $row["target"] == "remove_submission_form" || $row["target"] == "submission_confirmation_form"
                ) {
                    $sqlAssign = "SELECT name FROM " . $this->prefix . "assign where id=" . $other_->assignid . ";";
                    $resultAssign = mysqli_query($db, $sqlAssign);
                    $rowAssign = mysqli_fetch_assoc($resultAssign);
                    $temp_module = $rowAssign['name'];
                }
            }

            if ($row['component'] == 'mod_resource') {
                $temp_action = "resource view";
                $temp_url = "view.php?id=" . $row['cmid'];
                $sql4 = "SELECT name FROM " . $this->prefix . "resource inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "resource.id =objectid where component='mod_resource' and objectid=" . $row['objectid'] . ";";
                $result4 = mysqli_query($db, $sql4);
                $row4 = mysqli_fetch_assoc($result4);
                $temp_module = $row4['name'];
            }
            if ($row['component'] == 'mod_forum') {

                // if ($row['objecttable'] == 'forum' && $row['action'] == 'viewed') {
                //     $temp_action = "view forum";
                //     $temp_url = "view.php?id=" . $row['cmid'];
                //     $sqlForum = "SELECT name FROM " . $this->prefix . "forum where id=" . $row["objectid"] . ";";
                //     $resultForum = mysqli_query($db, $sqlForum);
                //     $rowForum = mysqli_fetch_assoc($resultForum);
                //     $temp_module = $rowForum['name'];
                // }

                if (array_key_exists("objecttable", $row)) {

                    if ($row['objecttable'] == "forum_subscriptions" || $row['objecttable'] == "forum_discussion_subs") {
                        if ($row['action'] == "created") {
                            $temp_action = "subscribe forum";
                            $temp_url = "view.php?id=" . $other_->forumid;
                            $temp_info = $other_->forumid;
                        } else if ($row['action'] == "deleted") {
                            $temp_action = "unsubscribe forum";
                            $temp_url = "view.php?id=" . $other_->forumid;
                            $temp_info = $other_->forumid;
                        }
                        $sqlForum = "SELECT name FROM " . $this->prefix . "forum where id=" . $temp_info . ";";
                        $resultForum = mysqli_query($db, $sqlForum);
                        $rowForum = mysqli_fetch_assoc($resultForum);
                        $temp_module = $rowForum['name'];
                    }

                    if ($row['objecttable'] == 'forum_discussions') {
                        if ($row['action'] == 'created') {
                            $temp_action = "forum add discussion";
                            $temp_url = "discuss.php?d=" . $row['objectid'];
                            $temp_info = $row['objectid'];
                            // } else if ($row['action'] == 'viewed') {
                            //     $temp_action = "forum view discussion";
                            //     $temp_url = "discuss.php?d=" . $row['cmid'];
                            //     $temp_info = $row['cmid'];
                        } else if ($row['action'] == 'deleted') {
                            $temp_action = "forum delete discussion";
                            $temp_url = "view.php?id=" . $row['cmid'];
                            $temp_info = $row['cmid'];
                        }

                        $sqlForum = "SELECT name FROM " . $this->prefix . "forum_discussions where id=" . $temp_info . ";";
                        $resultForum = mysqli_query($db, $sqlForum);
                        if ($resultForum) {
                            $rowForum = mysqli_fetch_assoc($resultForum);
                            if ($rowForum) {
                                $temp_module = array_key_exists("subject", $rowForum) ? $rowForum['name'] : null;
                            }
                        }
                    }

                    if ($row['objecttable'] == 'forum_posts') {
                        if ($row['action'] == 'created') {
                            $temp_action = "forum add post";
                            $temp_url = "discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];
                        } else if ($row['action'] == 'uploaded') {
                            $temp_action = "forum upload post";
                            $temp_url = "discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];
                        } else if ($row['action'] == 'deleted') {
                            $temp_action = "forum delete post";
                            $temp_url = "discuss.php?d=" . $other_->discussionid;
                        } else if ($row['action'] == 'updated') {
                            $temp_action = "forum update post";
                            $temp_url = "discuss.php?d=" . $other_->discussionid . "#p" . $row['objectid'] . "&parent=" . $row['objectid'];
                        }

                        $sqlForum = "SELECT subject FROM " . $this->prefix . "forum_posts where id=" . $row['objectid'] . ";";
                        $resultForum = mysqli_query($db, $sqlForum);
                        if ($resultForum) {
                            $rowForum = mysqli_fetch_assoc($resultForum);
                            if ($rowForum) {
                                $temp_module = array_key_exists("subject", $rowForum) ? $rowForum['subject'] : null;
                            }
                        }
                    }
                }
            }
        }

        // if ($row['target'] == 'course_module' && $row['component'] == 'core') {
        //     $temp_module =  $other_->modulename;
        //     if ($row["action"] != "deleted") {
        //         $temp_info =  $other_->name;
        //     }
        // }

        // if ($row['target'] == 'question' || $row['target'] == 'question_category') {
        //     $temp_info = $row['objectid'];
        // }

        // if ($row['target'] == 'course') {
        //     $temp_module = "course";
        //     $temp_url = "view.php?id=" . $row['courseid'];
        // }
        /////////////////////////////
        // if ($row['target'] == 'grade_item') {
        //     $module = $other_->itemmodule;
        //     $temp_info = $other_->itemname;
        // }

        // if ($row['target'] == 'user' && $row['action'] == 'graded') {
        //     $temp_module = $other_->finalgrade;
        // }
        ///////////////////////////
        // if ($row['target'] == 'user_list' || $row['target'] == 'user_profile') {
        //     $temp_info = $row['objectid'];
        // }

        // if ($row['target'] == 'tour') {
        //     if ($row["action"] == "ended") {
        //         $temp_info = $other_->stepid;
        //     }
        //     $temp_url = "view.php?id=" . $row['objectid'];
        // }

        // if ($row['target'] == 'step') {
        //     $temp_info = $other_->tourid;
        //     $temp_url = "view.php?id=" . $row['objectid'];
        // }

        // if ($row['target'] == 'grade_item') {
        //     $module = $other_->itemmodule;
        //     $temp_info = $other_->itemname;
        // }

        if (array_key_exists("target", $row)) {

            if ($row['target'] == 'role') {
                $sql3 = "SELECT shortname FROM " . $this->prefix . "role inner join " . $this->prefix . "logstore_standard_log on  " . $this->prefix . "role.id = objectid and target='role' and " . $this->prefix . "logstore_standard_log.id=" . $row['id'] . ";";
                $result3 = mysqli_query($db, $sql3);
                $row3 = mysqli_fetch_assoc($result3);
                $temp_module = $row3['shortname'];
                $temp_url = "admin/roles/assign.php?contextid=" . $row['cmid'] . "&roleid=" . $row['objectid'];
            }

            if ($row['target'] == 'course_section') {
                $temp_module = $other_->sectionnum;
                $temp_action = "course section " . $row["action"];
            }

            if ($row['target'] == 'enrol_instance') {
                $temp_module =  $other_->enrol;
                $temp_action = "enrol instance " . $row["action"];
            }

            if ($row['target'] == 'user_enrolment') {
                $temp_module = $row["target"];
                $temp_url = "../enrol/users.php?id=" . $row['courseid'];
                if ($row['action'] == 'created') {
                    $temp_action = "enrol user";
                } else if ($row['action'] == 'deleted') {
                    $temp_action = "unenrol user";
                }
            }
        }

        $moodleFields = array(
            "timecreated" => date('Y-m-d H:i:s', $row['timecreated']),
            "username" =>  $temp_username,
            "module" => $temp_module,
            "action" => $temp_action,
            "url" => $temp_url
        );
        return $moodleFields;
    }
    public function writeLogsToDB($dbResult)
    {
        $db = $dbResult[0];
        $result = $dbResult[1];
        while ($row = mysqli_fetch_assoc($result)) {
            $moodleField = $this->parseLogsToDB($row, $db);
            if ($moodleField["module"]) {
                $user = User::getUserIdByUsername($moodleField["username"]);
                if ($user) {
                    $courseUser = new CourseUser($user, $this->courseGameCourse);

                    if ($courseUser->getId()) {
                        Core::$systemDB->insert(
                            "participation",
                            [
                                "user" => $courseUser->getId(),
                                "course" => $this->courseId,
                                "description" => $moodleField["module"],
                                "type" => $moodleField["action"],
                                "post" => $moodleField["url"],
                                "date" => $moodleField["timecreated"]
                            ]
                        );
                    }
                }
            }
        }
    }

    public function getVotes()
    {
        $this->getDBConfigValues();
        if (!($this->time) && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id
                join " . $this->prefix . "rating r ON r.itemid = fp.id
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if (!($this->time) && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id
                join " . $this->prefix . "rating r ON r.itemid = fp.id
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id=" . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id and fp.created > " . $this->time . "
                join " . $this->prefix . "rating r ON r.itemid = fp.id
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id and fp.created > " . $this->time . "
                join " . $this->prefix . "rating r ON r.itemid = fp.id  
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id="  . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        }

        $sql .= " order by created;";
        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return array($db, $result);
    }
    public function parseVotesToDB($row, $db)
    {

        $sqlEvaluator = "SELECT username FROM " . $this->prefix . "user where id=" . $row["evaluatorId"] . ";";
        $resultEvaluator = mysqli_query($db, $sqlEvaluator);
        $rowEvaluator = mysqli_fetch_assoc($resultEvaluator);
        $evaluator = $rowEvaluator['username'];
        $votesFields = array(
            "user" => $row["username"],
            "description" => $row['name'] . ", " . $row['subject'],
            "post" => "discuss.php?d=" . $row['id'] . "#p" . $row['itemid'],
            "date" => date('Y-m-d H:i:s', $row['created']),
            "rating" => $row['rating'],
            "evaluator" => $evaluator
        );
        return $votesFields;
    }
    public function writeVotesToDb($dbResult)
    {
        $db = $dbResult[0];
        $result = $dbResult[1];
        $course = new Course($this->courseId);
        while ($row = mysqli_fetch_assoc($result)) {
            $votesField = $this->parseVotesToDB($row, $db);

            $prof = User::getUserIdByUsername($votesField["evaluator"]);
            $user = User::getUserIdByUsername($votesField["user"]);
            if ($user && $prof) {
                $courseUser = new CourseUser($user, $course);
                if ($courseUser->getId()) {
                    Core::$systemDB->insert(
                        "participation",
                        [
                            "user" => $user,
                            "course" => $this->courseId,
                            "description" => $votesField["description"],
                            "type" => "graded post",
                            "post" => $votesField["post"],
                            "date" => $votesField["date"],
                            "rating" => $row['rating'],
                            "evaluator" => $prof
                        ]
                    );
                }
            }
        }
    }

    public function getQuizGrades()
    {
        $this->getDBConfigValues();
        $sql = " select q.id as quizid, q.name as quiz, userid ,c.shortname as shortname, username, g.grade as grade, g.timemodified as timemodified
                from " . $this->prefix . "user as u, " . $this->prefix . "quiz as q, " . $this->prefix . "quiz_grades as g, " . $this->prefix . "course as c
	            where u.id = g.userid and q.course = c.id and g.quiz = q.id";
        if ($this->course) {
            $sql .= " and c.id = " . $this->course;
        }
        if ($this->user) {
            $sql .= " and u.id = " . $this->user;
        }
        if ($this->time) {
            $sql .= " and g.timemodified > " . $this->time;
        }
        $sql .= " order by timemodified;";

        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return $result;
    }
    public function writeQuizGradesToDb($result)
    {
        $course = new Course($this->courseId);
        while ($row = mysqli_fetch_assoc($result)) {
            $user = User::getUserIdByUsername($row["username"]);
            if ($user) {
                $courseUser = new CourseUser($user, $course);

                if ($courseUser->getId()) {
                    Core::$systemDB->insert(
                        "participation",
                        [
                            "user" => $user,
                            "course" => $this->courseId,
                            "description" => $row['quiz'],
                            "type" => "quiz grade",
                            "post" => "/mod/quiz/view.php?id=" . $row['quizid'],
                            "date" => date('Y-m-d, H:i:s', $row['timemodified']),
                            "rating" => $row['grade']
                        ]
                    );
                }
            }
        }
    }
}
