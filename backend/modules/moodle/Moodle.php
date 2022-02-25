<?php
namespace Modules\Moodle;

use GameCourse\Core;
use GameCourse\User;
use GameCourse\Course;
use mysqli;

class Moodle
{
    private $dbserver;
    private $dbuser;
    private $dbpass;
    private $dbname;
    private $dbport;
    private $prefix;
    private $time;
    private $course; //courseId no moodle
    private $user;
    private $courseId; //courseId no gamecourse
    private $courseGameCourse;
    private $timeToUpdate = null;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
        $this->courseGameCourse = new Course($this->courseId);
    }

    public static function checkConnection($dbServer, $dbUser, $dbPass, $dbName, $dbPort){
        $db = mysqli_connect($dbServer, $dbUser, $dbPass, $dbName, $dbPort);
        return $db;
    }
    public function getDBConfigValues()
    {
        $moodleVarsDB = Core::$systemDB->select(MoodleModule::TABLE_CONFIG, ["course" => $this->courseId], "*");
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
        if ($this->timeToUpdate) {
            $sql .= " and g.timemodified <= " . $this->timeToUpdate;
        }
        $sql .= " order by timemodified;";


        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return $result;
    }
    public function writeQuizGradesToDb($result)
    {
        $course = new Course($this->courseId);
        $row_ = array();
        while ($line = mysqli_fetch_assoc($result)) {
            array_push($row_, $line);
        }

        $inserted = false;
        $updated = false;
        $insertedOrUpdated = false;
        $sql = "insert into participation (user, course, description, type, post, rating) values";
        $values = "";
        foreach ($row_ as $row) {
            $user = User::getUserIdByUsername($row["username"]);
            if ($user) {
                $courseUser = Core::$systemDB->select("course_user", ["id" => $user, "course" => $this->courseId]);
                if ($courseUser) {
                    $result = Core::$systemDB->select("participation", ["user" => $user, "course" => $this->courseId, "type" => "quiz grade", "post" => "mod/quiz/view.php?id=" . $row['quizid']]);
                    if (!$result) {
                        $inserted = true;
                        $values .= "(" . $user . "," . $this->courseId . ",'" . $row['quiz'] . "','quiz grade', 'mod/quiz/view.php?id=" . $row['quizid'] . "','" . $row['grade'] . "'),";
                    } else {
                        $updated = true;
                        Core::$systemDB->update(
                            "participation",
                            array(
                                "user" => $user,
                                "course" => $this->courseId,
                                "description" => $row['quiz'],
                                "type" => "quiz grade",
                                "post" => "mod/quiz/view.php?id=" . $row['quizid'],
                                "date" => date('Y-m-d H:i:s', $row['timemodified']),
                                "rating" => $row['grade']
                            ),
                            array(
                                "user" => $user,
                                "course" => $this->courseId,
                                "type" => "quiz grade",
                                "description" => $row['quiz']

                            )
                        );
                    }
                }
            }
        }
        $values = rtrim($values, ",");
        if ($inserted) {
            $sql .= $values;
            Core::$systemDB->executeQuery($sql);
            if (!empty($row_) && $this->timeToUpdate == null) {
                $lastRecord = end($row_);
                $this->timeToUpdate = $lastRecord["timemodified"];
            }
        }

        $insertedOrUpdated = $inserted || $updated;
        return $insertedOrUpdated;
    }

    public function getAssignmentGrades()
    {
        $this->getDBConfigValues();
        $sql = "select a.id as assigmentId, a.name as assignment, userid ,c.shortname as shortname, username, g.grade as grade, g.timemodified as timemodified, g.attemptnumber as attemptnumber ";
        $sql .= "from " . $this->prefix . "assign a ";
        $sql .= "join " . $this->prefix . "assign_grades g on a.id = g.assignment ";
        $sql .= "join " . $this->prefix . "course c on a.course = c.id ";
        $sql .= "join " . $this->prefix . "user u on g.userid = u.id";

        $sql .= " and g.grade > -1";

        if ($this->course) {
            $sql .= " and c.id = " . $this->course;
        }
        if ($this->user) {
            $sql .= " and u.id = " . $this->user;
        }
        if ($this->time) {
            $sql .= " and g.timemodified > " . $this->time;
        }
        if ($this->timeToUpdate) {
            $sql .= " and g.timemodified <= " . $this->timeToUpdate;
        }
        $sql .= " order by timemodified;";


        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return $result;
    }

    public function writeAssignmentGradesToDb($result)
    {
        $row_ = array();
        while ($line = mysqli_fetch_assoc($result)) {
            array_push($row_, $line);
        }

        $inserted = false;
        $updated = false;
        $insertedOrUpdated = false;
        $sql = "insert into participation (user, course, description, type, post, rating) values";
        $values = "";
        foreach ($row_ as $row) {
            $user = User::getUserIdByUsername($row["username"]);
            if ($user) {
                $courseUser = Core::$systemDB->select("course_user", ["id" => $user, "course" => $this->courseId]);
                if ($courseUser) {
                    $result = Core::$systemDB->select("participation", ["user" => $user, "course" => $this->courseId, "type" => "assignment grade", "post" => "mod/assign/view.php?id=" . $row['assigmentId']]);
                    if (!$result) {
                        $inserted = true;
                        $values .= "(" . $user . "," . $this->courseId . ",'" . $row['assignment'] . "','assignment grade', 'mod/assign/view.php?id=" . $row['assigmentId'] . "','" . $row['grade'] . "'),";
                    } else {
                        $updated = true;
                        Core::$systemDB->update(
                            "participation",
                            array(
                                "user" => $user,
                                "course" => $this->courseId,
                                "description" => $row['assignment'],
                                "type" => "assignment grade",
                                "post" => "mod/assign/view.php?id=" . $row['assigmentId'],
                                "date" => date('Y-m-d H:i:s', $row['timemodified']),
                                "rating" => $row['grade']
                            ),
                            array(
                                "user" => $user,
                                "course" => $this->courseId,
                                "type" => "assignment grade",
                                "description" => $row['assignment']

                            )
                        );
                    }
                }
            }
        }
        $values = rtrim($values, ",");
        if ($inserted) {
            $sql .= $values;
            Core::$systemDB->executeQuery($sql);
            if (!empty($row_) && $this->timeToUpdate == null) {
                $lastRecord = end($row_);
                $this->timeToUpdate = $lastRecord["timemodified"];
            }
        }

        $insertedOrUpdated = $inserted || $updated;
        return $insertedOrUpdated;
    }

    public function getVotes()
    {
        $timeUpLimit = "";
        if ($this->timeToUpdate) {
            $timeUpLimit = " and r.timemodified <= " . $this->timeToUpdate;
        }
        $this->getDBConfigValues();
        if (!($this->time) && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating,  r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "rating r ON r.itemid = fp.id " . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if (!($this->time) && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating,  r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id  
                join " . $this->prefix . "rating r ON r.itemid = fp.id " . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id=" . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id
                join " . $this->prefix . "rating r ON r.itemid = fp.id and r.timemodified > " . $this->time . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "forum f
                join " . $this->prefix . "forum_discussions fd ON fd.forum = f.id
                join " . $this->prefix . "forum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "rating r ON r.itemid = fp.id and r.timemodified > " . $this->time . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id="  . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        }

        $sql .= " order by r.timemodified;";


        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return array($db, $result);
    }


    public function getProfessorRatings()
    {
        // this function gets professor ratings from peer-rated forums
        $timeUpLimit = "";
        if ($this->timeToUpdate) {
            $timeUpLimit = " and r.timemodified <= " . $this->timeToUpdate;
        }
        $this->getDBConfigValues();
        if (!($this->time) && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "rating r ON r.itemid = fp.id " . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if (!($this->time) && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "rating r ON r.itemid = fp.id " . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id=" . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "rating r ON r.itemid = fp.id and r.timemodified > " . $this->time . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, rating, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id
                join " . $this->prefix . "rating r ON r.itemid = fp.id and r.timemodified > " . $this->time . $timeUpLimit . "
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id="  . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        }

        $sql .= " order by r.timemodified;";


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
            "description" => addslashes($row['name'] . ", " . $row['subject']),
            "post" => "discuss.php?d=" . $row['id'] . "#p" . $row['itemid'],
            "date" => date('Y-m-d H:i:s', $row['timemodified']),
            "rating" => $row['rating'],
            "evaluator" => $evaluator
        );
        return $votesFields;
    }
    public function writeVotesToDb($dbResult, $peerForum=false)
    {
        $db = $dbResult[0];
        $result = $dbResult[1];
        $course = new Course($this->courseId);
        $row_ = array();
        while ($line = mysqli_fetch_assoc($result)) {
            array_push($row_, $line);
        }
        $inserted = false;
        $updated = false;
        $insertedOrUpdated = false;
        $sql = "insert into participation (user, course, description, type, post, rating, evaluator) values";
        $values = "";

        if ($peerForum)
            $forumPrefix = "mod/peerforum/";
        else
            $forumPrefix = "mod/forum/";

        foreach ($row_ as $row) {
            $votesField = $this->parseVotesToDB($row, $db);
            $prof = User::getUserIdByUsername($votesField["evaluator"]);
            $user = User::getUserIdByUsername($votesField["user"]);
            if ($user && $prof) {
                $courseUser = Core::$systemDB->select("course_user", ["id" => $user, "course" => $this->courseId]);
                $courseUserProf = Core::$systemDB->select("course_user", ["id" => $prof, "course" => $this->courseId]);
                if ($courseUser && $courseUserProf) {
                    $result = Core::$systemDB->select("participation", ["user" => $user, "course" => $this->courseId, "type" => "graded post", "post" => $forumPrefix . $votesField["post"]]);
                    if (!$result) {
                        $inserted = true;
                        $values .= '(' . $user . ',' . $this->courseId . ',"' . $votesField["description"] . '","graded post", "' . $forumPrefix . $votesField["post"] . '","' . $votesField["rating"] . '",' . $prof . '),';
                        // Core::$systemDB->insert(
                        //     "participation",
                        //     [
                        //         "user" => $user,
                        //         "course" => $this->courseId,
                        //         "description" => $votesField["description"],
                        //         "type" => "graded post",
                        //         "post" => $votesField["post"],
                        //         "date" => $votesField["date"],
                        //         "rating" => $row['rating'],
                        //         "evaluator" => $prof
                        //     ]
                        // );
                    } else {
                        $updated = true;
                        Core::$systemDB->update(
                            "participation",
                            [
                                "user" => $user,
                                "course" => $this->courseId,
                                "description" => $votesField["description"],
                                "type" => "graded post",
                                "post" => $forumPrefix . $votesField["post"],
                                "date" => $votesField["date"],
                                "rating" => $votesField["rating"],
                                "evaluator" => $prof
                            ],
                            [
                                "user" => $user,
                                "course" => $this->courseId,
                                "type" => "graded post",
                                "post" => $forumPrefix . $votesField["post"]

                            ]
                        );
                    }
                }
            }
        }

        $values = rtrim($values, ",");
        if ($inserted) {
            $sql .= $values;
            Core::$systemDB->executeQuery($sql);
            if (!empty($row_) && $this->timeToUpdate == null) {
                $lastRecord = end($row_);
                $this->timeToUpdate = $lastRecord["timemodified"];
            }
        }
        $insertedOrUpdated = $inserted || $updated;
        return $insertedOrUpdated;
    }


    public function getLogsNew() {

        $this->getDBConfigValues();

        // query fields

        $query = "select "; // base
        $query .= $this->prefix . "logstore_standard_log.id, " . $this->prefix . "logstore_standard_log.timecreated, username, "; // fields
        $query .= $this->prefix . "logstore_standard_log.timecreated, action, other, component, contextinstanceid as cmid , "; // fields
        $query .= $this->prefix . "logstore_standard_log.objectid, objecttable"; // fields
        $query .= " from ";


        // tables to consult + joins

        $tables_start = $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on ";

        $tables_user = $this->prefix . "user.id=userid";
        //$tables_no_user = $this->prefix . "user.id='" . $this->user;

        $tables_end = " inner join " . $this->prefix . "course on " . $this->prefix . "course.id = courseid ";


        // where clause fields
        $where = "where (";
        $where .= "(component = 'mod_questionnaire' and (action = 'submitted' or action = 'viewed' or action = 'resumed'))"; // submitted, viewed or resumed questionaire
        $where .= " or (component = 'mod_assign' and (action = 'submitted' or action = 'updated' or action = 'viewed'))"; // assign
        $where .= " or ((component = 'mod_forum' or component = 'mod_peerforum') and (action = 'searched' or target = 'subscribers' or target = 'user_report' or target = 'course_module' or target = 'course_module_instance_list') )"; // resource view
        $where .= " or component = 'mod_resource'"; // resource view
        $where .= " or component = 'mod_quiz'"; // quiz
        $where .= " or component = 'mod_chat'"; // chat
        $where .= " or component = 'mod_url'"; // url
        $where .= " or component = 'mod_page'"; // page
        $where .= " or target = 'role'"; // role
        $where .= " or (target = 'recent_activity' and action = 'viewed')"; // view recent activity
        $where .= " or (target = 'course' and action = 'viewed')"; // course view
        $where .= " or target = 'user_enrolment'"; // enrolment
        $where .= " or (action = 'viewed' and (target = 'user_list' or target = 'user_profile'))"; // user view and user view all
        $where .= " or (objecttable = 'forum_discussions' and (action = 'created' or action = 'viewed' or action = 'deleted'))"; // forum discussion created, viewed, or deleted
        $where .= " or (objecttable = 'tag_instance' and (action = 'added' or action = 'removed'))"; // tag added or removed
        $where .= " or (objecttable = 'forum_posts' and (action = 'uploaded' or action = 'updated' or action = 'deleted' or action = 'created')) "; // forum created, deleted, uploaded or updated
        $where .= " or (objecttable = 'forum_subscriptions' and (action = 'created' or action = 'deleted')) "; // forum subscribed or unsubscribed
        $where .= " or (objecttable = 'peerforum_discussions' and (action = 'created' or action = 'viewed' or action = 'deleted'))"; // peerforum created, viewed, or deleted
        $where .= " or (objecttable = 'peerforum_subscriptions' and (action = 'created' or action = 'deleted')) "; // peerforum subscribed or unsubscribed
        $where .= " or (objecttable = 'peerforum_posts' and (action = 'uploaded' or action = 'deleted' or action = 'created'))) "; // peerforum

        // others

        $timeUpLimit = "";
        $orderby = "order by " . $this->prefix . "logstore_standard_log.timecreated;";


        ///////////////////////////////////////



        // if a time is set in config
        if ($this->timeToUpdate) {
            $timeUpLimit = "and " . $this->prefix . "logstore_standard_log.timecreated <= " . $this->timeToUpdate . " ";
            $where .= $timeUpLimit;
        }

        if ($this->course and 1) {
            $where .= "and courseid=" . $this->course . " ";
        }


        // if a user IS NOT specified in the config
        if (!($this->user)) {
            //$sql = $query . $tables_start . $tables_no_user . $tables_end;
            $sql = $query . $tables_start . $tables_user . $tables_end;
        }

        // if a user IS specified in the config
        if (($this->user)) {
            //$sql = $query . $tables_start . $tables_user . $tables_end;
            $sql = $query . $tables_start . $tables_user . $tables_end;
            $where .= "and " . $this->prefix . "user.id=" . $this->user . " ";
        }

        $sql .= $where;

        // if no time is set in config
        if ($this->time) {
            $sql .=  "and " . $this->prefix . "logstore_standard_log.timecreated>=" . $this->time . " ";
        }

        $sql .= $orderby;


        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");

        $result = mysqli_query($db, $sql);

        return array($db, $result);

    }

    public function getPeergrades()
    {
        $timeUpLimit = "";
        if ($this->timeToUpdate) {
            $timeUpLimit = " and r.timemodified <= " . $this->timeToUpdate;
        }
        $this->getDBConfigValues();
        if (!($this->time) && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, peergrade, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "peerforum_peergrade r ON r.itemid = fp.id " . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if (!($this->time) && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, peergrade,  r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id  
                join " . $this->prefix . "peerforum_peergrade r ON r.itemid = fp.id " . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id=" . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && !($this->user)) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, peergrade, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "peerforum_peergrade r ON r.itemid = fp.id and r.timemodified >" . $this->time . $timeUpLimit . " 
                join " . $this->prefix . "user u ON fp.userid = u.id
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        } else if ($this->time && $this->user) {
            $sql = "select fp.created, c.shortname, fp.userid, username, f.name, subject, peergrade, r.timemodified as timemodified, r.userid as evaluatorId, fd.id, itemid
                from " . $this->prefix . "peerforum f
                join " . $this->prefix . "peerforum_discussions fd ON fd.peerforum = f.id
                join " . $this->prefix . "peerforum_posts fp ON fp.discussion = fd.id 
                join " . $this->prefix . "peerforum_peergrade r ON r.itemid = fp.id and r.timemodified >" . $this->time . $timeUpLimit . "
                join " . $this->prefix . "user u ON fp.userid = u.id and u.id="  . $this->user . " 
                join " . $this->prefix . "course c ON c.id = f.course";
            if ($this->course) {
                $sql .= " and f.course=" . $this->course;
            }
        }

        $sql .= " order by r.timemodified;";


        $db = mysqli_connect($this->dbserver, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport) or die("not connecting");
        $result = mysqli_query($db, $sql);
        return array($db, $result);
    }
    public function parsePeergradesToDB($row, $db)
    {

        $sqlEvaluator = "SELECT username FROM " . $this->prefix . "user where id=" . $row["evaluatorId"] . ";";
        $resultEvaluator = mysqli_query($db, $sqlEvaluator);
        $rowEvaluator = mysqli_fetch_assoc($resultEvaluator);
        $evaluator = $rowEvaluator['username'];
        $votesFields = array(
            "user" => $row["username"],
            "description" => $row['name'] . ", " . $row['subject'],
            "post" => "discuss.php?d=" . $row['id'] . "#p" . $row['itemid'],
            "date" => date('Y-m-d H:i:s', $row['timemodified']),
            "rating" => $row['peergrade'],
            "evaluator" => $evaluator
        );
        return $votesFields;
    }

    public function writePeergradesToDB($dbResult){
        $db = $dbResult[0];
        $result = $dbResult[1];
        $course = new Course($this->courseId);
        $row_ = array();
        while ($line = mysqli_fetch_assoc($result)) {
            array_push($row_, $line);
        }
        $inserted = false;
        $updated = false;
        $insertedOrUpdated = false;
        $sql = "insert into participation (user, course, description, type, post, rating, evaluator) values";
        $values = "";
        foreach ($row_ as $row) {
            $votesField = $this->parsePeergradesToDB($row, $db);

            $evaluator = User::getUserIdByUsername($votesField["evaluator"]);
            $user = User::getUserIdByUsername($votesField["user"]);
            if ($user && $evaluator) {
                $courseUser = Core::$systemDB->select("course_user", ["id" => $user, "course" => $this->courseId]);
                $courseUserEvaluator = Core::$systemDB->select("course_user", ["id" => $evaluator, "course" => $this->courseId]);
                if ($courseUser && $courseUserEvaluator) {
                    $result = Core::$systemDB->select("participation", ["user" => $user, "course" => $this->courseId, "type" => "peergraded post", "post" => "mod/peerforum/" . $votesField["post"], "evaluator" => $evaluator]);
                    if (!$result) {
                        $inserted = true;
                        $values .= '(' . $user . ',' . $this->courseId . ',"' . $votesField["description"] . '","peergraded post", "' . "mod/peerforum/" . $votesField["post"] . '","' . $votesField["rating"] . '",' . $evaluator . '),';

                    } else {
                        $updated = true;
                        Core::$systemDB->update(
                            "participation",
                            [
                                "user" => $user,
                                "course" => $this->courseId,
                                "description" => $votesField["description"],
                                "type" => "peergraded post",
                                "post" => "mod/peerforum/" . $votesField["post"],
                                "date" => $votesField["date"],
                                "rating" => $votesField["rating"],
                                "evaluator" => $evaluator
                            ],
                            [
                                "user" => $user,
                                "course" => $this->courseId,
                                "evaluator" => $evaluator,
                                "type" => "peergraded post",
                                "post" => "mod/peerforum/" . $votesField["post"]

                            ]
                        );
                    }
                }
            }
        }

        $values = rtrim($values, ",");
        if ($inserted) {
            $sql .= $values;
            Core::$systemDB->executeQuery($sql);
            if (!empty($row_) && $this->timeToUpdate == null) {
                $lastRecord = end($row_);
                $this->timeToUpdate = $lastRecord["timemodified"];
            }
        }
        $insertedOrUpdated = $inserted || $updated;
        return $insertedOrUpdated;
    }

    public function getLogs()
    {
        $whereClause = "((component = 'mod_questionnaire' and action='submitted') or component = 'mod_resource' or (objecttable = 'forum_discussions' and (action = 'created' or action = 'viewed' or action = 'deleted')) or (objecttable = 'peerforum_discussions' and (action = 'created' or action = 'viewed' or action = 'deleted')) or (objecttable = 'forum_posts' and (action = 'uploaded' or action = 'deleted')))";
        $timeUpLimit = "";
        if ($this->time) {
            if ($this->timeToUpdate) {
                $timeUpLimit =  $this->prefix . "logstore_standard_log.timecreated <= " . $this->timeToUpdate . " and " . $whereClause;
            } else {
                $timeUpLimit =  " and " . $whereClause;
            }
        } else {
            if ($this->timeToUpdate) {
                $timeUpLimit = " " . $this->prefix . "logstore_standard_log.timecreated <= " . $this->timeToUpdate . " and " . $whereClause;
            } else {
                $timeUpLimit = " " . $whereClause;
            }
        }
        $this->getDBConfigValues();
        if (!($this->time) && !($this->user)) {
            $sql = "select " . $this->prefix . "logstore_standard_log.id,  courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id=userid inner join " . $this->prefix . "course on " . $this->prefix . "course.id = courseid";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= $timeUpLimit . " order by " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if ($this->time && !($this->user)) {
            $sql = "select " . $this->prefix . "logstore_standard_log.id,  courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid,  " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id=userid inner join " . $this->prefix . "course on " . $this->prefix . "course.id = courseid ";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= " where "  . $timeUpLimit . " and " .  $this->prefix . "logstore_standard_log.timecreated>=" . $this->time . " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if (!($this->time) && $this->user) {
            $sql = "select  " . $this->prefix . "logstore_standard_log.id, courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,   contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid , objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id='" . $this->user . "' inner join " . $this->prefix . "course on " . $this->prefix . "course.id=courseid ";
            if ($this->course) {
                $sql .= " and courseid=" . $this->course;
            }
            $sql .= $timeUpLimit . " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
        } else if ($this->time && $this->user) {
            $sql = "select  " . $this->prefix . "logstore_standard_log.id, courseid, userid, " . $this->prefix . "logstore_standard_log.timecreated, ip, username, " . $this->prefix . "logstore_standard_log.timecreated, target, action, other, component,  contextinstanceid as cmid , " . $this->prefix . "logstore_standard_log.objectid, objecttable from " . $this->prefix . "user inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "user.id='" . $this->user . "' inner join " . $this->prefix . "course on " . $this->prefix . "course.id=courseid ";
            if ($this->course) {
                $sql .= " and p.course=" . $this->course;
            }
            $sql .= " where " . $timeUpLimit . $this->timeToUpdate . " and " . $this->prefix . "logstore_standard_log.timecreated>=" . $this->time . " order by  " . $this->prefix . "logstore_standard_log.timecreated;";
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
        /*if ($row['target'] == 'calendar_event') {
             $temp_module = "calendar";
             $temp_url = "event.php?action=edit&id=" . $row['objectid'];
             $temp_info = $other_->name;
        }*/



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

            if ($row['component'] == 'mod_quiz') {
                $temp_url = 'view.php?id=' . $row['cmid'];
                $temp_action = "quiz " . $row['action'];

                if($row["target"] == "report"){
                    $temp_action = "quiz report";
                    $temp_info = $other_->quizid;
                }
                if($row["target"] == "attempt_preview"){
                    $temp_action = "quiz preview";
                    $temp_info = $other_->quizid;
                }
                if($row["target"] == "attempt_summary"){
                    $temp_action = "quiz view summary";
                    $temp_info = $other_->quizid;
                }
                if ($row["target"] == "edit_page" || ($row["target"] == "attempt" && $row["action"] != "started")) {
                    $temp_info = $other_->quizid;
                } else { //course_module
                    $temp_info = $row['objectid'];
                }

                $sqlQuiz = "SELECT name FROM " . $this->prefix . "quiz where id=" . $temp_info . ";";
                $resultQuiz = mysqli_query($db, $sqlQuiz);
                $rowQuiz = mysqli_fetch_assoc($resultQuiz);
                $temp_module = $rowQuiz['name'];
            }

            if ($row['component'] == 'mod_chat') {
                $temp_url = "view.php?id=" . $row['cmid'];
                $temp_info = $row['objectid'];
                $temp_action = "chat" . $row['action'];
            }

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
            if ($row['component'] == 'mod_url') {
                $temp_action = "url " . $row['action'];
                $temp_url = "view.php?id=" . $row['cmid'];
                $sql4 = "SELECT name FROM " . $this->prefix . "url inner join " . $this->prefix . "logstore_standard_log on " . $this->prefix . "url.id =objectid where component='mod_url' and objectid=" . $row['objectid'] . ";";
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

                    if ($row['objecttable'] == "forum_subscriptions" /*|| $row['objecttable'] == "forum_discussion_subs"*/) {
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
                            $temp_url = "mod/forum/discuss.php?d=" . $row['objectid'];
                            $temp_info = $row['objectid'];
                            strpos($temp_info, '"');
                        } else if ($row['action'] == 'viewed') {
                            $temp_action = "forum view discussion";
                            $temp_url = "discuss.php?d=" . $row['cmid'];
                            $temp_info = $row['cmid'];
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
                                $temp_module = array_key_exists("name", $rowForum) ? $rowForum['name'] : null;
                            }
                        }
                    }
                    if ($row['objecttable'] == 'forum') {
                        if($row['action'] == 'viewed'){
                            $temp_action = "forum view forum";
                        }
                    }

                    if ($row['objecttable'] == 'forum_posts') {
                        if ($row['action'] == 'created') {
                            $temp_action = "forum add post";
                            $temp_url = "mod/forum/discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];
                        }
                        if ($row['action'] == 'uploaded') {
                            $temp_action = "forum upload post";
                            $temp_url = "mod/forum/discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];

                        } else if ($row['action'] == 'deleted') {
                            $temp_action = "forum delete post";
                            $temp_url = "mod/forum/discuss.php?d=" . $other_->discussionid;
                        }
                        else if ($row['action'] == 'updated') {
                            $temp_action = "forum update post";
                            $temp_url = "mod/forum/discuss.php?d=" . $other_->discussionid . "#p" . $row['objectid'] . "&parent=" . $row['objectid'];
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
                if (array_key_exists("target", $row)) {
                    if ($row['target'] == "course"){
                        if ($row['action'] = "searched"){
                            $temp_action = "forum search";
                        }
                    }
                    if ($row['target'] == "subscribers"){
                        if ($row['action'] = "viewed"){
                            $temp_action = "forum view subscribers";
                        }
                    }
                    if ($row['target'] == "course_module_instance_list"){
                        if ($row['action'] = "viewed"){
                            $temp_action = "forum view forums";
                        }
                    }
                    if ($row['target'] == "user_report"){
                        if ($row['action'] = "viewed"){
                            $temp_action = "forum user report";
                        }
                    }
                }
            }

            if ($row['component'] == "mod_peerforum") {
                if (array_key_exists("objecttable", $row)) {
                    if ($row['objecttable'] == 'peerforum_posts') {
                        if ($row['action'] == 'created') {
                            $temp_action = "peerforum add post";
                            $temp_url = "mod/peerforum/discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];
                        }
                        if ($row['action'] == 'uploaded') {
                            $temp_action = "peerforum upload post";
                            $temp_url = "mod/peerforum/discuss.php?d=" . $other_->discussionid . "&parent=" . $row['objectid'];
                        } else if ($row['action'] == 'deleted') {
                            $temp_action = "peerforum delete post";
                            $temp_url = "mod/peerforum/discuss.php?d=" . $other_->discussionid;
                        } else if ($row['action'] == 'updated') {
                            $temp_action = "peerforum update post";
                            $temp_url = "mod/peerforum/discuss.php?d=" . $other_->discussionid . "#p" . $row['objectid'] . "&parent=" . $row['objectid'];
                        }

                        $sqlForum = "SELECT subject FROM " . $this->prefix . "peerforum_posts where id=" . $row['objectid'] . ";";
                        $resultForum = mysqli_query($db, $sqlForum);
                        if ($resultForum) {
                            $rowForum = mysqli_fetch_assoc($resultForum);
                            if ($rowForum) {
                                $temp_module = array_key_exists("subject", $rowForum) ? $rowForum['subject'] : null;
                            }
                        }
                    }
                    if ($row['objecttable'] == 'peerforum_discussions') {
                        if ($row['action'] == 'created') {
                            $temp_action = "peerforum add discussion";
                            $temp_url = "mod/peerforum/discuss.php?d=" . $row['objectid'];
                            $temp_info = $row['objectid'];
                            strpos($temp_info, '"');
                        } else if ($row['action'] == 'viewed') {
                            $temp_action = "peerforum view discussion";
                            $temp_url = "discuss.php?d=" . $row['cmid'];
                            $temp_info = $row['cmid'];
                        } else if ($row['action'] == 'deleted') {
                            $temp_action = "peerforum delete discussion";
                            $temp_url = "view.php?id=" . $row['cmid'];
                            $temp_info = $row['cmid'];
                        }

                        $sqlForum = "SELECT name FROM " . $this->prefix . "peerforum_discussions where id=" . $temp_info . ";";
                        $resultForum = mysqli_query($db, $sqlForum);
                        if ($resultForum) {
                            $rowForum = mysqli_fetch_assoc($resultForum);
                            if ($rowForum) {
                                $temp_module = array_key_exists("name", $rowForum) ? $rowForum['name'] : null;
                            }
                        }
                    }
                    if ($row['objecttable'] == "peerforum_subscriptions" /*|| $row['objecttable'] == "peerforum_discussion_subs"*/) {
                        if ($row['action'] == "created") {
                            $temp_action = "subscribe peerforum";
                            $temp_url = "view.php?id=" . $other_->peerforumid;
                            $temp_info = $other_->peerforumid;

                        } else if ($row['action'] == "deleted") {
                            $temp_action = "unsubscribe peerforum";
                            $temp_url = "view.php?id=" . $other_->peerforumid;
                            $temp_info = $other_->peerforumid;
                        }
                        $sqlForum = "SELECT name FROM " . $this->prefix . "peerforum where id=" . $temp_info . ";";
                        $resultForum = mysqli_query($db, $sqlForum);
                        $rowForum = mysqli_fetch_assoc($resultForum);
                        $temp_module = $rowForum['name'];
                    }

                    if ($row['objecttable'] == 'peerforum') {
                        if($row['action'] == 'viewed'){
                            $temp_action = "peerforum view peerforum";
                        }
                    }

                }
                if (array_key_exists("target", $row)) {
                    if ($row['target'] == "course"){
                        if ($row['action'] = "searched"){
                            $temp_action = "peerforum search";
                        }
                    }
                    if ($row['target'] == "subscribers"){
                        if ($row['action'] = "viewed"){
                            $temp_action = "peerforum view subscribers";
                        }
                    }
                    if ($row['target'] == "course_module_instance_list"){
                        if ($row['action'] = "viewed"){
                            $temp_action = "peerforum view peerforums";
                        }
                    }
                    if ($row['target'] == "user_report"){
                        if ($row['action'] = "viewed"){
                            $temp_action = "peerforum user report";
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
                $temp_action = "role " . $row['action'];
                $temp_url = "admin/roles/assign.php?contextid=" . $row['cmid'] . "&roleid=" . $row['objectid'];
            }

            if ($row['target'] == 'user_list'){
                $temp_action = "user view all";
                $temp_url = "user/view.php?id=" . $row['objectid'] . "&course=" . $row['courseid'];
            }

            if ($row['target'] == 'user_profile') {
                $temp_action = "user view";
                $temp_url = "user/index.php?id=" . $this->courseId;
            }

            if ($row['target'] == 'recent_activity') {
                $temp_action = "course view recent";
            }

            if ($row['target'] == 'course') {
                $temp_action = "course view";
            }

            if ($row['target'] == 'tag') {
                if($row['action'] == "added"){
                    $temp_action = "tag add";
                }
                if($row['action'] == "removed"){
                    $temp_action = "tag remove";
                }

                $temp_info = $other_->tagid;

                $sql4 = "SELECT name FROM " . $this->prefix . "tag inner join " . $this->prefix . "logstore_standard_log on  " . $this->prefix . "tag.id = " . $temp_info . "and target='tag' and " . $this->prefix . "logstore_standard_log.id=" . $row['id'] . ";";
                $result3 = mysqli_query($db, $sql4);
                $row4 = mysqli_fetch_assoc($result4);
                $temp_module = "Tag:" . $row4['name'];
            }

            // if ($row['target'] == 'course_section') {
            //     $temp_module = $other_->sectionnum;
            //     $temp_action = "course section " . $row["action"];
            // }

            // if ($row['target'] == 'enrol_instance') {
            //     $temp_module =  $other_->enrol;
            //     $temp_action = "enrol instance " . $row["action"];
            // }

            if ($row['target'] == 'user_enrolment') {
                $temp_module = $row["target"];
                $temp_url = "../enrol/users.php?id=" . $row['courseid'];
                if ($row['action'] == 'created') {
                    $temp_action = "course enrol user";
                } else if ($row['action'] == 'deleted') {
                    $temp_action = "course unenrol user";
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
        $row_ = array();
        while ($line = mysqli_fetch_assoc($result)) {
            array_push($row_, $line);
        }
        $inserted = false;
        $sql = "insert into participation (user, course, description, type, post) values";
        $values = "";
        foreach ($row_ as $row) {
            $moodleField = $this->parseLogsToDB($row, $db);
            if ($moodleField["module"] && ($moodleField["action"] != "updated")) {
                $user = User::getUserIdByUsername($moodleField["username"]);
                if ($user) {
                    $courseUser = Core::$systemDB->select("course_user", ["id" => $user, "course" => $this->courseId]);
                    if ($courseUser) {
                        $result = Core::$systemDB->select("participation", ["user" => $user, "course" => $this->courseId, "description" => $moodleField["module"], "type" => $moodleField["action"], "post" => $moodleField["url"]]);
                        if (!$result /*|| ($result && Core::$systemDB->select("participation", ["type" => "questionnaire submitted"]))*/) {
                            if (Core::$systemDB->select("course_user", ["course" => $this->courseId, "id" => $user])) {
                                $module = str_replace('"', ' ', $moodleField["module"]);
                                $values .= '(' . $courseUser["id"] . ',' . $this->courseId . ',"' . $module . '","' .  $moodleField["action"] . '","' . $moodleField["url"] . '"),';
                                $inserted = true;
                                // Core::$systemDB->insert(
                                //     "participation",
                                //     [
                                //         "user" => $courseUser->getId(),
                                //         "course" => $this->courseId,
                                //         "description" => $moodleField["module"],
                                //         "type" => $moodleField["action"],
                                //         "post" => $moodleField["url"],
                                //         "date" => $moodleField["timecreated"]
                                //     ]
                                // );
                            }
                        }
                    }
                }
            }
        }
        $values = rtrim($values, ",");
        $sql .= $values;
        if($values){
            Core::$systemDB->executeQuery($sql);
        }
        if (!empty($row_) && $this->timeToUpdate == null) {
            $lastRecord = end($row_);
            $this->timeToUpdate = $lastRecord["timecreated"];
        }
        return $inserted;
    }

    public function updateMoodleConfigTime()
    {
        if ($this->timeToUpdate) {
            Core::$systemDB->update(MoodleModule::TABLE_CONFIG, ["moodleTime" => $this->timeToUpdate], ["course" => $this->courseId]);
        }
    }
}