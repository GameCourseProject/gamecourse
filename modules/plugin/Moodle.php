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

    public function __construct($courseId)
    {
        // $this->courseId = $courseId;
        // $this->getDBConfigValues();

        // $logs = $this->getLogs();
        // $this->writeLogsToDB($logs);

        // $votes = $this->getVotes();
        // $this->writeVotesToDb($votes);

        // $quiz_grades = $this->getQuizGrades();
        // $this->writeQuizGradesToDb($quiz_grades);
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
        if (!($this->time) && !($this->user)) {
            $sql = "select " . $this->prefix . "logstore_standard_log.id,  courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname,' ', lastname)  as userfullname, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id=userid inner join " . $this->prefix . "course on " . $this->prefix . "course.id = courseid";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= " order by " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if ($this->time && !($this->user)) {
            $sql = "select " . $this->prefix . "logstore_standard_log.id,  courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname, ' ', lastname ) as userfullname, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid,  " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id=userid inner join " . $this->prefix . "course on " . $this->prefix . "course.id = courseid ";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= " where " . $this->prefix . "logstore_standard_log.timecreated>=" . $this->time . " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if (!($this->time) && $this->user) {
            $sql = "select  " . $this->prefix . "logstore_standard_log.id, courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname, ' ', lastname ) as userfullname, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,   contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid , objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id='" . $this->user . "' inner join " . $this->prefix . "course on " . $this->prefix . "course.id=courseid ";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if ($this->time && $this->user) {
            $sql = "select  " . $this->prefix . "logstore_standard_log.id, courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname, ' ', lastname ) as userfullname, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id='" . $this->user . "' inner join " . $this->prefix . "course on " . $this->prefix . "course.id=courseid ";
            if ($this->course) {
                $sql .= " and p.course=" . $this->course;
            }
            $sql .= " where " . $this->prefix . "logstore_standard_log.timecreated>=" . $this->time . " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        }
        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return array($db, $result, $this->prefix);
    }

    public function parseLogsToDB($row, $db)
    {
        $temp_info = null;
        $temp_url = null;
        $temp_module = $row['target'];
        $temp_action = $row['action'];
        $other_ = json_decode($row['other']);

        if ($row['target'] == 'calendar_event') {
            $temp_module = "calendar";
            $temp_url = "event.php?action=edit&id=" . $row['objectid'];
            $temp_info = $other_->name;
        }

        if ($row['target'] == 'message') {
            $temp_info = $row["userid"];
        }

        if ($row['component'] == 'mod_questionnaire') {
            $temp_module = 'questionnaire';
            $temp_url = 'view.php?id=' . $row['cmid'];
            if ($row["action"] == "submitted") {
                $temp_info = $other_->questionnaireid;
            } else {
                $temp_info = $row['objectid'];
            }
        }
        if ($row['component'] == 'mod_quiz') {
            $temp_module = 'quiz';
            $temp_url = 'view.php?id=' . $row['cmid'];
            if (
                $row["target"] == "edit_page" || $row["target"] == "attempt_summary" || $row["target"] == "report" || $row["target"] == "attempt_preview"
                || ($row["target"] == "attempt" && $row["action"] != "started")
            ) {
                $temp_info = $other_->quizid;
            } else { //course_module
                $temp_info = $row['objectid'];
            }
        }

        if ($row['component'] == 'mod_page' || $row['component'] == 'mod_resource' || $row['component'] == 'mod_chat') {
            $temp_module = $row['objecttable'];
            $temp_info = $row['objectid'];
            $temp_url = "view.php?id=" . $row['cmid'];
        }

        if ($row["component"] == "mod_assign") {
            $temp_module = $row["target"];
            $temp_url = "view.php?id=" . $row['cmid'];
            if ($row["target"] == "course_module") {
                $sqlAssign = "SELECT name FROM " . $this->prefix . "assign inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "assign.id =objectid where component='mod_assign' and objectid =" . $row["objectid"] . ";";
                $resultAssign = mysqli_query($db, $sqlAssign);
                $rowAssign = mysqli_fetch_assoc($resultAssign);
                $temp_info = $rowAssign['name']; //$row4["name"];
            } else if (
                $row["target"] == "submission_form" || $row["target"] == "grading_table" || $row["target"] == "grading_form"
                || $row["target"] == "remove_submission_form" || $row["target"] == "submission_confirmation_form"
            ) {
                $sqlAssign = "SELECT name FROM " . $this->prefix . "assign where id=" . $other_->assignid . ";";
                $resultAssign = mysqli_query($db, $sqlAssign);
                $rowAssign = mysqli_fetch_assoc($resultAssign);
                $temp_info = $rowAssign['name']; //$row4["name"];
            }
        }

        if ($row['component'] == 'mod_resource') {
            $temp_action = "resource view";
            $sql4 = "SELECT name FROM " . $this->prefix . "resource inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "resource.id =objectid where component='mod_resource' and objectid=" . $row['objectid'] . ";";
            $result4 = mysqli_query($db, $sql4);
            $row4 = mysqli_fetch_assoc($result4);
            $temp_info = $row4['name']; //$row4["name"];
        }

        if ($row['component'] == 'mod_forum') {
            $temp_module = "forum";
            $temp_info = $row['objectid'];

            if ($row['objecttable'] == "forum_subscriptions" || $row['objecttable'] == "forum_discussion_subs") {
                if ($row['action'] == "created") {
                    $temp_action = "subscribe";
                    $temp_url = "view.php?id=" . $row['objectid'];
                } else if ($row['action'] == "deleted") {
                    $temp_action = "unsubscribe";
                    $temp_url = "view.php?id=" . $row['objectid'];
                }
            }

            if ($row['objecttable'] == 'forum' && $row['action'] == 'viewed') {
                $temp_action = "view forum";
                $temp_url = "view.php?id=" . $row['cmid'];
            }
            if ($row['objecttable'] == 'forum_discussions') {
                if ($row['action'] == 'created') {
                    $temp_action = "add discussion";
                    $temp_url = "discuss.php?d=" . $row['objectid'];
                } else if ($row['action'] == 'viewed') {
                    $temp_action = "view discussion";
                    $temp_url = "discuss.php?d=" . $row['cmid'];
                } else if ($row['action'] == 'deleted') {
                    $temp_action = "delete discussion";
                    $temp_url = "view.php?id=" . $row['cmid'];
                }
            }

            if ($row['objecttable'] == 'forum_posts') {
                if ($row['action'] == 'created') {
                    $temp_action = "add post";
                    $temp_url = "discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];
                } else if ($row['action'] == 'uploaded') {
                    $temp_action = "upload post";
                    $temp_url = "discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];
                } else if ($row['action'] == 'deleted') {
                    $temp_action = "delete post";
                    $temp_url = "discuss.php?d=" . $other_->discussionid;
                } else if ($row['action'] == 'updated') {
                    $temp_action = "update post";
                    $temp_url = "discuss.php?d=" . $other_->discussionid . "#p" . $row['objectid'] . "&parent=" . $row['objectid'];
                }
            }
        }
        if ($row['target'] == 'course_module' && $row['component'] == 'core') {
            $temp_module =  $other_->modulename;
            if ($row["action"] != "deleted") {
                $temp_info =  $other_->name;
            }
        }

        if ($row['target'] == 'question' || $row['target'] == 'question_category') {
            $temp_info = $row['objectid'];
        }

        if ($row['target'] == 'course') {
            if ($row['objectid'] != NULL) {
                $temp_info = $row['objectid'];
            }
            $temp_url = "view.php?id=" . $row['courseid'];
        }

        if ($row['target'] == 'role') {
            $sql3 = "SELECT shortname FROM " . $this->prefix . "role inner join " . $this->prefix . "logstore_standard_log on  " . $this->prefix . "role.id = objectid and target='role' and " . $this->prefix . "logstore_standard_log.id=" . $row['id'] . ";";
            $result3 = mysqli_query($db, $sql3);
            $row3 = mysqli_fetch_assoc($result3);
            $temp_info = $row3['shortname'];
            $temp_url = "admin/roles/assign.php?contextid=" . $row['cmid'] . "&roleid=" . $row['objectid'];
        }

        if ($row['target'] == 'assessable' && $row["component"] == "mod_forum") {

            $temp_info = $other_->discussionid;
        }
        if ($row['target'] == 'grade_item') {
            $module = $other_->itemmodule;
            $temp_info = $other_->itemname;
        }

        if ($row['target'] == 'user' && $row['action'] == 'graded') {
            $temp_info = $other_->finalgrade;
        }

        if ($row['target'] == 'course_section') {
            $temp_info = $other_->sectionnum;
        }

        if ($row['target'] == 'enrol_instance' || $row['target'] == 'user_enrolment') {
            $temp_info = $row['courseid'];
            $temp_module = "course";
            $temp_url = "../enrol/users.php?id=" . $row['courseid'];
            if ($row['action'] == 'created') {
                $temp_action = "enrol";
            } else if ($row['action'] == 'deleted') {
                $temp_action = "unenrol";
            }
        }

        if ($row['target'] == 'user_list' || $row['target'] == 'user_profile') {
            $temp_info = $row['objectid'];
        }
        if ($row['target'] == 'tour') {
            if ($row["action"] == "ended") {
                $temp_info = $other_->stepid;
            }
            $temp_url = "view.php?id=" . $row['objectid'];
        }
        if ($row['target'] == 'step') {
            $temp_info = $other_->tourid;
            $temp_url = "view.php?id=" . $row['objectid'];
        }

        if ($row['target'] == 'grade_item') {
            $module = $other_->itemmodule;
            $temp_info = $other_->itemname;
        }


        $moodleFields = array(
            "timecreated" => date('Y-m-d, H:i:s', $row['timecreated']),
            "ip"   => $row['ip'],
            "userid" => $row['userid'],
            "userFullName" =>  $row['userfullname'],
            "module" => $temp_module,
            "action" => $temp_action,
            "info" => $temp_info,
            "url" => $temp_url

        );
        return $moodleFields;
    }

    public function writeLogsToDB($dbResult)
    {
        $db = $dbResult[0];
        $result = $dbResult[1];
        $this->prefix = $dbResult[2];
        while ($row = mysqli_fetch_assoc($result)) {
            $moodleField = $this->parseLogsToDB($row, $db);
            Core::$systemDB->insert(
                "moodle_logs",
                [
                    "course" => $this->courseId,
                    "timecreated" => $moodleField["timecreated"],
                    "ip" => $moodleField["ip"],
                    "user" => $moodleField["userid"],
                    "module" => $moodleField["module"],
                    "action" => $moodleField["action"],
                    "info" => $moodleField["info"],
                    "url" => $moodleField["url"]
                ]
            );
        }
    }

    public function getVotes()
    {
        $sql = "select fp.created, c.shortname, fp.userid, CONCAT (firstname,' ',lastname) AS userfullname, f.name, subject, rating, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id
                join " . $this->prefix . "course c ON c.id = f.course
                join " . $this->prefix . "rating r ON r.itemid = fp.id
                join " . $this->prefix . "user u ON fp.userid = u.id";
        if ($this->course) {
            $sql .= " WHERE f.course=" . $this->course;
        }
        $sql .= ";";
        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return $result;
    }

    public function writeVotesToDb($result)
    {
        while ($row = mysqli_fetch_assoc($result)) {
            Core::$systemDB->insert(
                "moodle_votes",
                [
                    "course" => $this->courseId,
                    "timeCreated" =>  date('Y-m-d, H:i:s', $row['created']),
                    "user" => $row['userid'],
                    "forum" =>  $row['name'],
                    "topic" => $row['subject'],
                    "grade" => $row['rating'],
                    "url" => "discuss.php?d=" . $row['id'] . "#p" . $row['itemid']
                ]
            );
        }
    }

    public function getQuizGrades()
    {
        $sql = " select q.id as quizid, q.name as quiz, userid ,c.shortname as shortname, CONCAT (firstname,' ',lastname) as user, g.grade as grade, g.timemodified as timemodified
                from " . $this->prefix . "user as u, " . $this->prefix . "quiz as q, " . $this->prefix . "quiz_grades as g, " . $this->prefix . "course as c
	            where u.id = g.userid and q.course = c.id and g.quiz = q.id";
        if ($this->course) {
            $sql .= " and c.id = " . $this->course;
        }
        $sql .= ";";

        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return $result;
    }

    public function writeQuizGradesToDb($result)
    {
        while ($row = mysqli_fetch_assoc($result)) {
            Core::$systemDB->insert(
                "moodle_quiz_grades",
                [
                    "course" => $this->courseId,
                    "timeModified" =>  date('Y-m-d, H:i:s', $row['timemodified']),
                    "user" =>  $row['userid'],
                    "quizName" => $row['quiz'],
                    "grade" => $row['grade'],
                    "url" => "/mod/quiz/view.php?id=" . $row['quizid']
                ]
            );
        }
    }
}
