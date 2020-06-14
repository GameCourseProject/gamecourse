<?php

namespace Modules\Plugin;

use GameCourse\Core;
use GameCourse\API;
use GameCourse\User;
use GameCourse\CourseUser;
use GameCourse\Course;

class Moodle
{

    public function __construct($moodle)
    {
        $this->moodle = $moodle;
    }

    public function getLogs($time, $user, $course, $prefix, $dbserver, $dbuser, $dbpass, $db, $dbport)
    {
        if (!isset($time) && !isset($user)) {
            $sql = "select " . $prefix . "logstore_standard_log.id,  courseid, userid, " . $prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname,' ', lastname)  as userfullname, " . $prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $prefix . "logstore_standard_log.objectid, objecttable from " . $prefix . "user inner join " . $prefix . "logstore_standard_log on " . $prefix . "user.id=userid inner join " . $prefix . "course on " . $prefix . "course.id = courseid";
            if (isset($course)) {
                $sql .= " and courseid=" . $course;
            }
            $sql .= " order by . $prefix .logstore_standard_log.timecreated;";
        } else if (isset($time) && !isset($user)) {
            $sql = "select " . $prefix . "logstore_standard_log.id,  courseid, userid, " . $prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname, ' ', lastname ) as userfullname, " . $prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid,  " . $prefix . "logstore_standard_log.objectid, objecttable from " . $prefix . "user inner join " . $prefix . "logstore_standard_log on " . $prefix . "user.id=userid inner join " . $prefix . "course on " . $prefix . "course.id = courseid ";
            if (isset($course)) {
                $sql .= " and courseid=" . $course;
            }
            $sql .= " where " . $prefix . "logstore_standard_log.timecreated>=" . $time . " order by  " . $prefix . "logstore_standard_log.timecreated;";
        } else if (!isset($time) && isset($user)) {
            $sql = "select  " . $prefix . "logstore_standard_log.id, courseid, userid, " . $prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname, ' ', lastname ) as userfullname, " . $prefix . "logstore_standard_log.timecreated, target, action, other, component,   contextinstanceid as cmid , " . $prefix . "logstore_standard_log.objectid , objecttable from " . $prefix . "user inner join " . $prefix . "logstore_standard_log on " . $prefix . "user.id='" . $user . "' inner join " . $prefix . "course on " . $prefix . "course.id=courseid ";
            if (isset($course)) {
                $sql .= " and courseid=" . $course;
            }
            $sql .= " order by  " . $prefix . "logstore_standard_log.timecreated;";
        } else if (isset($time) && isset($user)) {
            $sql = "select  " . $prefix . "logstore_standard_log.id, courseid, userid, " . $prefix . "logstore_standard_log.timecreated, ip, CONCAT (firstname, ' ', lastname ) as userfullname, " . $prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $prefix . "logstore_standard_log.objectid, objecttable from " . $prefix . "user inner join " . $prefix . "logstore_standard_log on " . $prefix . "user.id='" . $user . "' inner join " . $prefix . "course on " . $prefix . "course.id=courseid ";
            if (isset($course)) {
                $sql .= " and p.course=" . $course;
            }
            $sql .= " where " . $prefix . "logstore_standard_log.timecreated>=" . $time . " order by  " . $prefix . "logstore_standard_log.timecreated;";
        }
        $db = mysqli_connect($dbserver, $dbuser, $dbpass, $db, $dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return array($db, $result, $prefix);
    }

    public function parseLogsToDB($row, $db, $prefix)
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
                $sqlAssign = "SELECT name FROM " . $prefix . "assign inner join " . $prefix . "logstore_standard_log on " . $prefix . "assign.id =objectid where component='mod_assign' and objectid =" . $row["objectid"] . ";";
                $resultAssign = mysqli_query($db, $sqlAssign);
                $rowAssign = mysqli_fetch_assoc($resultAssign);
                $temp_info = $rowAssign['name']; //$row4["name"];
            } else if (
                $row["target"] == "submission_form" || $row["target"] == "grading_table" || $row["target"] == "grading_form"
                || $row["target"] == "remove_submission_form" || $row["target"] == "submission_confirmation_form"
            ) {
                $sqlAssign = "SELECT name FROM " . $prefix . "assign where id=" . $other_->assignid . ";";
                $resultAssign = mysqli_query($db, $sqlAssign);
                $rowAssign = mysqli_fetch_assoc($resultAssign);
                $temp_info = $rowAssign['name']; //$row4["name"];
            }
        }

        if ($row['component'] == 'mod_resource') {
            $temp_action = "resource view";
            $sql4 = "SELECT name FROM " . $prefix . "resource inner join " . $prefix . "logstore_standard_log on " . $prefix . "resource.id =objectid where component='mod_resource' and objectid=" . $row['objectid'] . ";";
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
            $sql3 = "SELECT shortname FROM " . $prefix . "role inner join " . $prefix . "logstore_standard_log on  " . $prefix . "role.id = objectid and target='role' and " . $prefix . "logstore_standard_log.id=" . $row['id'] . ";";
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
        $prefix = $dbResult[2];
        $courseId = API::getValue('course');

        while ($row = mysqli_fetch_assoc($result)) {
            $moodleField = $this->parseLogsToDB($row, $db, $prefix);
            Core::$systemDB->insert(
                "moodle_logs",
                [
                    "course" => $courseId,
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

    public function getVotes($course, $prefix, $dbserver, $dbuser, $dbpass, $db, $dbport)
    {
        $sql = "select fp.created, c.shortname, fp.userid, CONCAT (firstname,' ',lastname) AS userfullname, f.name, subject, rating, fd.id, itemid
                from " . $prefix . "forum f
                join " . $prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $prefix . "forum_posts fp ON fp.discussion = fd.id
                join " . $prefix . "course c ON c.id = f.course
                join " . $prefix . "rating r ON r.itemid = fp.id
                join " . $prefix . "user u ON fp.userid = u.id";
        if (isset($course)) {
            $sql .= " WHERE f.course=" . $course;
        }
        $sql .= ";";
        $db = mysqli_connect($dbserver, $dbuser, $dbpass, $db, $dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return $result;
    }

    public function writeVotesToDb($result)
    {
        $courseId = API::getValue('course');
        while ($row = mysqli_fetch_assoc($result)) {
            Core::$systemDB->insert(
                "moodle_votes",
                [
                    "course" => $courseId,
                    "timeCreated" =>  date('Y-m-d, H:i:s',$row['created']),
                    "user" => $row['userid'],
                    "forum" =>  $row['name'],
                    "topic" => $row['subject'],
                    "grade" => $row['rating'],
                    "url" => "discuss.php?d=" . $row['id'] . "#p" . $row['itemid']
                ]
            );
        }
    }

    public function getQuizGrades($course, $prefix, $dbserver, $dbuser, $dbpass, $db, $dbport)
    {
        $sql = " select q.id as quizid, q.name as quiz, userid ,c.shortname as shortname, CONCAT (firstname,' ',lastname) as user, g.grade as grade, g.timemodified as timemodified
                from " . $prefix . "user as u, " . $prefix . "quiz as q, " . $prefix . "quiz_grades as g, " . $prefix . "course as c
	            where u.id = g.userid and q.course = c.id and g.quiz = q.id";
        if (isset($course)) {
            $sql .= " and c.id = " . $course;
        }
        $sql .= ";";

        $db = mysqli_connect($dbserver, $dbuser, $dbpass, $db, $dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return $result;
    }

    public function writeQuizGradesToDb($result)
    {
        $courseId = API::getValue('course');
        while ($row = mysqli_fetch_assoc($result)) {
            Core::$systemDB->insert(
                "moodle_quiz_grades",
                [
                    "course" => $courseId,
                    "timeModified" =>  date('Y-m-d, H:i:s',$row['timemodified']),
                    "user" =>  $row['userid'],
                    "quizName" => $row['quiz'],
                    "grade" => $row['grade'],
                    "url" => "/mod/quiz/view.php?id=" . $row['quizid']
                ]
            );
        }
    }
}
