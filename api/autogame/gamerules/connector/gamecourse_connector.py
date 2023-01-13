#!/usr/bin/env python3
import socket, logging
import mysql.connector
from datetime import timedelta

from io import StringIO
from course.logline import *
import config

from course.coursedata import read_achievements, read_tree
achievements = read_achievements()
tree_awards = read_tree()

from gamerules.connector.db_connector import gc_db as db


### ------------------------------------------------------ ###
###	------------------ PRIVATE functions ----------------- ###
### -------------- (not accessible in rules) ------------- ###
### ------------------------------------------------------ ###

### AutoGame

def autogame_init(course):
    """
    Checks AutoGame status for a given course and initializes it.
    """

    query = "SELECT startedRunning, isRunning FROM autogame WHERE course = %s;"
    last_activity, is_running = db.execute_query(query, course)[0]

    # Check AutoGame status
    if is_running:
        raise Exception("AutoGame is already running for this course.")

    # Initialize AutoGame
    query = "UPDATE autogame SET isRunning = %s WHERE course = %s;"
    db.execute_query(query, (True, course), "commit")

    return last_activity

def autogame_terminate(course, start_date, finish_date):
    """
    Finishes execution of AutoGame, sets isRunning to False
    and notifies server to close the socket.
    """

    # Terminate AutoGame
    if not config.TEST_MODE:
        query = "UPDATE autogame SET startedRunning = %s, finishedRunning = %s, isRunning = %s WHERE course = %s;"
        db.execute_query(query, (start_date, finish_date, False, course), "commit")

    # Check how many courses are running
    query = "SELECT COUNT(*) from autogame WHERE isRunning = %s AND course != %s;"
    courses_running = db.execute_query(query, (True, 0))[0][0]

    # Close socket if no course is running
    if courses_running == 0:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

            try:
                # Connect to server
                s.connect((config.HOST, config.PORT))
                s.settimeout(600.0)

                # Send end message
                end_message = "end gamerules;"
                s.send(end_message.encode())
                s.send("\n".encode())

            except socket.timeout as e:
                print("\nError: Socket timeout, operation took longer than 3 minutes.")

            except KeyboardInterrupt:
                print("\nInterrupt: You pressed CTRL+C!")
                exit()
    return


### Clearing progression

def clear_progression(target):
    """
    Clears all progression for a given target before
    calculating again.

    Needs to be refreshed everytime the Rule System runs.
    """

    # Clear badge progression, if badges enabled
    if module_enabled("Badges"):
        clear_badge_progression(target)

    # Clear streak progression, if streaks enabled
    if module_enabled("Streaks"):
        clear_streak_progression(target)

def clear_badge_progression(target):
    """
    Clears all badge progression for a given target
    before calculating again.

    Needs to be refreshed everytime the Rule System runs.
    """

    query = "DELETE FROM badge_progression WHERE course = %s AND user = %s;"
    db.execute_query(query, (config.COURSE, target), "commit")

def clear_streak_progression(target):
    """
    Clears all streak progression for a given target
    before calculating again.

    Needs to be refreshed everytime the Rule System runs.
    """

    query = "DELETE FROM streak_progression WHERE course = %s and user = %s;"
    db.execute_query(query, (config.COURSE, target), "commit")

    query = "DELETE FROM streak_participations WHERE course = %s and user = %s;"
    db.execute_query(query, (config.COURSE, target), "commit")


### Awards

def get_awards_table():
    """
    Gets awards table for the current AutoGame mode.
    """

    return "award" if not config.TEST_MODE else "award_test"

def give_award(target, type, description, reward, instance=None):
    """
    Gives an award to a specific target.
    """

    table = get_awards_table()
    query = "INSERT INTO " + table + " (user, course, description, type, moduleInstance, reward) VALUES (%s, %s, %s, %s, %s, %s);"
    db.execute_query(query, (target, config.COURSE, description, type, instance, reward), "commit")

def count_awards(course):
    """
    Counts all awards on a given course.
    """

    query = "SELECT COUNT(*) FROM award WHERE course = %s;"
    nr_awards = db.execute_query(query, course)[0][0]

    return nr_awards

def delete_awards(course):
    """
    Deletes all awards on a given course.
    """

    query = "DELETE FROM award WHERE course = %s;"
    db.execute_query(query, course, "commit")

def award_received(target, description, type):
    """
    Checks whether a given award of a certain type has
    already been received by a specific target.
    """

    table = get_awards_table()
    query = "SELECT COUNT(*) FROM " + table + " WHERE course = %s AND user = %s AND type = %s AND description LIKE %s;"
    nr_awards = db.execute_query(query, (config.COURSE, target, type, description))[0][0]

    return nr_awards > 0


### Calculating rewards

def calculate_extra_credit_reward(target, reward, type, instance):
    """
    Calculates reward of a certain type for a given target
    based on max. extra credit values.
    """

    if module_enabled("XPLevels"):
        # Get extra credit info
        query = "SELECT maxExtraCredit FROM xp_config WHERE course = %s;" % config.COURSE
        max_extra_credit = int(db.data_broker.get(db, config.COURSE, query)[0][0])
        max_extra_credit_for_type = get_max_extra_credit_by_type(type)
        target_extra_credit = get_target_extra_credit(target, type, instance)

        # Calculate reward
        return max(min(
            min(max_extra_credit - target_extra_credit["total"], reward),
            min(max_extra_credit_for_type - target_extra_credit[type + "s"], reward)
        ), 0)

    return 0

def get_target_extra_credit(target, type, instance):
    """
    Gets total extra credit values for a given target
    and award type.
    """

    awards_table = get_awards_table()
    extra_credit = {"total": 0}

    # Get badges extra credit
    if type == "badge" and module_enabled("Badges"):
        query = "SELECT IFNULL(SUM(a.reward), 0) " \
                "FROM " + awards_table + " a LEFT JOIN badge b on a.moduleInstance = b.id " \
                "WHERE a.course = %s AND a.user = %s AND a.type = %s AND a.moduleInstance != %s AND b.isExtra = 1;"
        badges_total = int(db.execute_query(query, (config.COURSE, target, type, instance))[0][0])
        extra_credit["badges"] = badges_total
        extra_credit["total"] += badges_total

    # Get skills extra credit
    if type == "skill" and module_enabled("Skills"):
        query = "SELECT IFNULL(SUM(a.reward), 0) " \
                "FROM " + awards_table + " a LEFT JOIN skill s on a.moduleInstance = s.id " \
                "WHERE a.course = %s AND a.user = %s AND a.type = %s AND a.moduleInstance != %s AND s.isExtra = 1;"
        skills_total = int(db.execute_query(query, (config.COURSE, target, type, instance))[0][0])
        extra_credit["skills"] = skills_total
        extra_credit["total"] += skills_total

    # Get streaks extra credit
    if type == "streak" and module_enabled("Streaks"):
        query = "SELECT IFNULL(SUM(a.reward), 0) " \
                "FROM " + awards_table + " a LEFT JOIN streak s on a.moduleInstance = s.id " \
                "WHERE a.course = %s AND a.user = %s AND a.type = %s AND a.moduleInstance != %s AND s.isExtra = 1;"
        streaks_total = int(db.execute_query(query, (config.COURSE, target, type, instance))[0][0])
        extra_credit["streaks"] = streaks_total
        extra_credit["total"] += streaks_total

    return extra_credit

def get_max_extra_credit_by_type(type):
    """
    Gets max. extra credit for a given type.
    """

    query = "SELECT maxExtraCredit FROM %s WHERE course = %s;" % (type + "s_config", config.COURSE)
    return int(db.data_broker.get(db, config.COURSE, query)[0][0])


### Calculating grade

def calculate_grade(target):
    """
    Calculates grade for a given target based on awards received.
    """

    # Calculates XP, if XP enabled
    if module_enabled("XPLevels"):
        calculate_xp(target)

        # Calculates team XP, if teams enabled
        if module_enabled("Teams"):
            calculate_team_xp(target)

def calculate_xp(target):
    """
    Calculates XP for a given target based on awards received.
    """

    # Calculate total
    query = "SELECT IFNULL(SUM(reward), 0) FROM award WHERE course = %s AND user = %s AND type != 'tokens';"
    total_xp = int(db.execute_query(query, (config.COURSE, target))[0][0])

    # Get new level
    query = "SELECT id FROM level WHERE course = %s AND minXP <= %s GROUP BY id ORDER BY minXP DESC limit 1;"
    current_level = db.execute_query(query, (config.COURSE, total_xp))[0][0]

    # Update target total XP and level
    query = "UPDATE user_xp SET xp = %s, level = %s WHERE course = %s AND user = %s;"
    db.execute_query(query, (total_xp, current_level, config.COURSE, target), "commit")

def calculate_team_xp(target):
    """
    Calculates XP for a given target's team based on awards received.
    """

    # Get target team
    query = "SELECT teamId FROM teams_members WHERE memberId = %s;"
    table = db.execute_query(query, target)
    team = table[0][0] if len(table) == 1 else None

    if team is not None:
        # Calculate team total
        query = "SELECT IFNULL(SUM(reward), 0) FROM award_teams WHERE course = %s AND team = %s GROUP BY team;"
        team_xp = int(db.execute_query(query, (config.COURSE, team))[0][0])

        # Get new level
        query = "SELECT id FROM level WHERE course = %s AND minXP <= %s GROUP BY id ORDER BY minXP DESC limit 1;"
        current_level = db.execute_query(query, (config.COURSE, team_xp))[0][0]

        # Update team total XP and level
        query = "UPDATE teams_xp SET xp = %s, level = %s WHERE course = %s AND teamId = %s;"
        db.execute_query(query, (team_xp, current_level, config.COURSE, team), "commit")


### Utils

def course_exists(course):
    """
    Checks whether a given course exists and is active.
    """

    query = "SELECT isActive FROM course WHERE id = %s;" % course
    table = db.data_broker.get(db, course, query)

    return table[0][0] if len(table) == 1 else False

def module_enabled(module):
    """
    Checks whether a given module exists and is enabled.
    """

    query = "SELECT isEnabled FROM course_module WHERE course = %s AND module = '%s';" % (config.COURSE, module)
    table = db.data_broker.get(db, config.COURSE, query)

    return table[0][0] if len(table) == 1 else False

def get_targets(course, datetime=None, all_targets=False, targets_list=None):
    """
    Returns targets (students) to fire rules for.
    """

    if targets_list is not None:
        if len(targets_list) == 0:
            return {}

        # Running for certain targets
        query = "SELECT id, name, studentNumber FROM user WHERE id IN (%s);" % ", ".join(targets_list)
        table = db.execute_query(query)

    else:
        if all_targets:
            # Running for all targets
            query = "SELECT u.id, u.name, u.studentNumber " \
                    "FROM user_role ur LEFT JOIN role r on ur.role = r.id " \
                    "LEFT JOIN course_user cu on ur.user = cu.id " \
                    "LEFT JOIN user u on ur.user = u.id " \
                    "WHERE ur.course = %s AND cu.isActive = 1 AND r.name = 'Student';"
            table = db.execute_query(query, course)

        else:
            query = "SELECT u.id, u.name, u.studentNumber " \
                    "FROM participation p LEFT JOIN user_role ur ON p.user = ur.user " \
                    "LEFT JOIN role r ON ur.role = r.id " \
                    "LEFT JOIN course_user cu on ur.user = cu.id " \
                    "LEFT JOIN user u on ur.user = u.id " \
                    "WHERE p.course = %s AND cu.isActive = 1 AND r.name = 'Student'"

            if datetime is None:
                # Running for targets w/ data in course
                query += ";"
                table = db.execute_query(query, course)

            else:
                # Running for targets w/ new data in course
                query += " AND date > %s;"
                table = db.execute_query(query, (course, datetime))

    targets = {}
    for row in table:
        (user_id, user_name, user_number) = row
        targets[user_id] = {"name": user_name.decode(), "studentNumber": user_number}

    return targets

def call_gamecourse(course, library, function, args):
    """
    Calls GameCourse on PHP to handle dictionary functions.
    """

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

        try:
            while True:
                # Connect to server
                s.connect((config.HOST, config.PORT))
                s.settimeout(50.0)

                # Send course ID
                s.send(course.encode())
                s.send("\n".encode())

                # Send library name
                s.send(library.encode())
                s.send("\n".encode())

                # Send name of function to execute
                s.send(function.encode())
                s.send("\n".encode())

                # Send args of function
                s.send(json.dumps(args).encode())
                s.send("\n".encode())

                # Receive data type
                data = s.recv(1024)
                datatype = data.decode()

                # Send an ok message for syncing up the socket
                s.send("ok\n".encode())

                # If data received is a collection, it will be
                # sent in chunks as not to break the socket
                if datatype == "collection":
                    data = s.recv(1024)
                    datad = data.decode()
                    collection = datad
                    while data:
                        data = s.recv(1024)
                        datad = data.decode()
                        collection += datad

                    if not data:
                        all_logs = collection.split("\n")[:-1]

                        # create loglines from each el in list
                        participations = []
                        for el in all_logs:
                            io = StringIO(el)
                            log = json.load(io)

                            # TODO - might want to check for errors in this part

                            ll_id = int(log["id"])
                            ll_user = int(log["user"])
                            ll_course = int(log["course"])
                            ll_desc = log["description"]
                            ll_type = log["type"]
                            ll_post = log["post"]
                            ll_date = log["date"]
                            ll_rating = None if log["rating"] is None else int(log["rating"])
                            ll_evaluator = None if log["evaluator"] is None else int(log["evaluator"])

                            logline = LogLine(ll_id,ll_user,ll_course,ll_desc,ll_type,ll_post,ll_date,ll_rating,ll_evaluator)
                            participations.append(logline)

                        result = participations
                        break

                # If data received is a value (int, float, str, etc)
                # then it will be json decoded into the correct type
                elif datatype == "other":
                    data = s.recv(1024)
                    io = StringIO(data.decode())
                    result = json.load(io)
                    break

                else:
                    print("\nError: Return type not valid.")
                    break

        except socket.timeout as e:
            print("\nError: Socket timeout, operation took longer than 3 minutes.")

        except KeyboardInterrupt:
            print("\nInterrupt: You pressed CTRL+C!")
            exit()

    return result


### ------------------------------------------------------ ###
###	------------------ PUBLIC functions ------------------ ###
### -- (accessible in rules through imported functions) -- ###
### ------------------------------------------------------ ###

### Getting logs

def get_logs(target=None, type=None, rating=None, evaluator=None, start_date=None, end_date=None, description=None):
    """
    Gets all logs under certain conditions.

    Option to get logs for a specific target, type, rating,
    evaluator, and/or description, as well as an initial and/or end date.
    """

    query = "SELECT * FROM participation WHERE course = %s" % config.COURSE

    if target is not None:
        query += " AND user = %s" % target

    if type is not None:
        query += " AND type = '%s'" % type

    if rating is not None:
        query += " AND rating = %s" % rating

    if evaluator is not None:
        query += " AND evaluator = %s" % evaluator

    if start_date is not None:
        query += " AND date >= '%s'" % start_date

    if end_date is not None:
        query += " AND date <= '%s'" % end_date

    if description is not None:
        query += " AND description LIKE '%s'" % description

    query += " ORDER BY date ASC;"
    return db.execute_query(query)

def get_assignment_logs(target, name=None):
    """
    Gets all assignment logs for a specific target.

    Option to get a specific assignment by name.
    """

    return get_logs(target, "assignment grade", None, None, None, None, name)

def get_attendance_lab_logs(target, lab_nr=None):
    """
    Gets all lab attendance logs for a specific target.

    Option to get a specific lab by number.
    """

    return get_logs(target, "attended lab", None, None, None, None, lab_nr)

def get_attendance_lecture_logs(target, lecture_nr=None):
    """
    Gets all lecture attendance logs for a specific target.

    Option to get a specific lecture by number.
    """

    return get_logs(target, "attended lecture", None, None, None, None, lecture_nr)

def get_attendance_lecture_late_logs(target, lecture_nr=None):
    """
    Gets all late lecture attendance logs for a specific target.

    Option to get a specific lecture by number.
    """

    return get_logs(target, "attended lecture (late)", None, None, None, None, lecture_nr)

def get_forum_logs(target, forum=None, thread=None, rating=None):
    """
    Gets forum logs for a specific target.

    Options to get logs from a specific forum and/or thread,
    as well as with a certain rating.
    """

    if forum is None:
        return get_logs(target, "forum add post") + get_logs(target, "forum upload post")

    description = forum + ("," if thread is None else ", Re: " + thread) + "%"
    return get_logs(target, "graded post", rating, None, None, None, description)

def get_lab_logs(target, lab_nr=None):
    """
    Gets all labs logs for a specific target.

    Option to get a specific lab by number.
    """

    return get_logs(target, "lab grade", None, None, None, None, lab_nr)

def get_page_view_logs(target, name=None):
    """
    Gets all page view logs for a specific target.

    Option to get a specific page view by name.
    """

    return get_logs(target, "page viewed", None, None, None, None, name)

def get_participation_lecture_logs(target, lecture_nr):
    """
    Gets all lecture participation logs for a specific target.

    Option to get a specific participation by lecture number.
    """

    return get_logs(target, "participated in lecture", None, None, None, None, lecture_nr)

def get_participation_invited_lecture_logs(target, lecture_nr):
    """
    Gets all invited lecture participation logs for a specific target.

    Option to get a specific participation by lecture number.
    """

    return get_logs(target, "participated in invited lecture", None, None, None, None, lecture_nr)

def get_peergrading_logs(target, forum=None, thread=None, rating=None):
    """
    Gets peergrading logs for a specific target.

    Options to get logs from a specific forum and/or thread,
    as well as with a certain rating.
    """

    description = None if forum is None else forum + ("," if thread is None else ", Re: " + thread) + "%"
    return get_logs(None, "peergraded post", rating, target, None, None, description)

def get_presentation_logs(target):
    """
    Gets presentation logs for a specific target.
    """

    return get_logs(target, "presentation grade")

def get_questionnaire_logs(target, name=None):
    """
    Gets all questionnaire logs for a specific target.

    Option to get a specific questionnaire by name.
    """

    return get_logs(target, "questionnaire submitted", None, None, None, None, name)

def get_quiz_logs(target, name=None):
    """
    Gets all quiz logs for a specific target.

    Option to get a specific quiz by name.
    """

    return get_logs(target, "quiz grade", None, None, None, None, name)

def get_resource_view_logs(target, name=None):
    """
    Gets all resource view logs for a specific target.

    Option to get a specific resource view by name.
    """

    return get_logs(target, "resource view", None, None, None, None, name)

def get_skill_logs(target, name=None, rating=None):
    """
    Gets skill logs for a specific target.

    Options to get logs for a specific skill by name,
    as well as with a certain rating.
    """

    return get_forum_logs(target, "Skill Tree", name, rating) if module_enabled("Skills") else []

def get_skill_tier_logs(target, tier):
    """
    Gets skill tier logs for a specific target.
    """

    if module_enabled("Skills"):
        type = "skill"
        table = get_awards_table()

        query = "SELECT p.* " \
                "FROM " + table + " a LEFT JOIN skill s on a.moduleInstance = s.id " \
                "LEFT JOIN skill_tier t on s.tier = t.id " \
                "LEFT JOIN award_participation ap on a.id = ap.award " \
                "LEFT JOIN participation p on ap.participation = p.id " \
                "WHERE a.course = %s AND a.user = %s AND a.type = %s AND t.position = %s " \
                "ORDER BY p.date ASC;"
        return db.execute_query(query, (config.COURSE, target, type, tier - 1))

    return []

def get_url_view_logs(target, name=None):
    """
    Gets all URL view logs for a specific target.

    Option to get a specific URL view by name.
    """

    return get_logs(target, "url viewed", None, None, None, None, name)


### Getting total reward

def get_total_reward(target, type):
    """
    Gets total reward for a given target of a specific type.
    """

    table = get_awards_table()
    query = "SELECT IFNULL(SUM(reward), 0) FROM " + table + " WHERE course = %s AND user = %s AND type = %s;"
    return int(db.execute_query(query, (config.COURSE, target, type))[0][0])

def get_total_assignment_reward(target):
    """
    Gets total reward for a given target from assignments.
    """

    return get_total_reward(target, "assignment")

def get_total_badge_reward(target):
    """
    Gets total reward for a given target from badges.
    """

    return get_total_reward(target, "badge")

def get_total_bonus_reward(target):
    """
    Gets total reward for a given target from bonus.
    """

    return get_total_reward(target, "bonus")

def get_total_exam_reward(target):
    """
    Gets total reward for a given target from exams.
    """

    return get_total_reward(target, "exam")

def get_total_lab_reward(target):
    """
    Gets total reward for a given target from labs.
    """

    return get_total_reward(target, "labs")

def get_total_presentation_reward(target):
    """
    Gets total reward for a given target from presentations.
    """

    return get_total_reward(target, "presentation")

def get_total_quiz_reward(target):
    """
    Gets total reward for a given target from quizzes.
    """

    return get_total_reward(target, "quiz")

def get_total_skill_reward(target):
    """
    Gets total reward for a given target from skills.
    """

    return get_total_reward(target, "skill")

def get_total_streak_reward(target):
    """
    Gets total reward for a given target from streaks.
    """

    return get_total_reward(target, "streak")

def get_total_tokens_reward(target):
    """
    Gets total reward for a given target from tokens.
    """

    return get_total_reward(target, "tokens")


### Awarding items

def award(target, type, description, reward, instance=None):
    """
    Awards a single prize to a specific target.

    NOTE: will not retract, but will not award twice.
    Updates award if reward has changed.
    """

    table = get_awards_table()
    query = "SELECT reward FROM " + table + " WHERE course = %s AND user = %s AND type = %s AND description = %s;"
    results = db.execute_query(query, (config.COURSE, target, type, description))

    if len(results) > 1:
        logging.warning("Award '%s' has been awarded more than once for target with ID = %s." % (description, target))
        return

    if len(results) == 0:  # Award
        give_award(target, type, description, reward, instance)

    else:  # Update award, if changed
        old_reward = int(results[0][0])
        if reward != old_reward:
            query = "UPDATE " + table + " SET reward = %s WHERE course = %s AND user = %s AND type = %s AND description = %s;"
            db.execute_query(query, (reward, config.COURSE, target, type, description), "commit")

def award_assignment_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards assignment grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per assignment
     > max_grade ==> max. grade per assignment
    """

    for log in logs:
        name = log[config.DESCRIPTION_COL].decode()
        reward = (int(log[config.RATING_COL]) / max_grade) * max_xp
        award(target, "assignment", name, reward)

def award_badge(target, name, lvl, logs):
    """
    Awards a given level to a specific target.

    NOTE: will retract if level changed.
    Updates award if reward has changed.
    """

    def get_description(name, lvl):
        lvl_info = " (level %s)" % lvl
        return name + lvl_info

    type = "badge"
    awards_table = get_awards_table()

    if module_enabled("Badges"):
        # Get badge info
        query = "SELECT bl.badge, bl.number, bl.reward, b.isExtra " \
                "FROM badge_level bl LEFT JOIN badge b on b.id = bl.badge " \
                "WHERE b.course = %s AND b.name = '%s' ORDER BY number;" % (config.COURSE, name)
        table_badge = db.data_broker.get(db, config.COURSE, query)
        badge_id = table_badge[0][0]

        # Update badge progression
        if not config.TEST_MODE:
            for log in logs:
                query = "INSERT INTO badge_progression (course, user, badge, participation) VALUES (%s, %s, %s, %s);"
                db.execute_query(query, (config.COURSE, target, badge_id, log[0]), "commit")

        # Get awards given for badge
        query = "SELECT COUNT(*) FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description LIKE %s;"
        nr_awards = int(db.execute_query(query, (config.COURSE, target, type, name + "%"))[0][0])

        # Lvl is zero and there are no awards to be removed
        # Simply return right away
        if lvl == 0 and nr_awards == 0:
            return

        # The rule/data sources have been updated, the 'award' table
        # has badge levels attributed which are no longer valid.
        # All levels no longer valid must be deleted
        if nr_awards > lvl:
            for level in range(lvl + 1, nr_awards + 1):
                # Delete award
                description = get_description(name, level)
                query = "DELETE FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
                db.execute_query(query, (config.COURSE, target, type, description, badge_id), "commit")

        # Award and/or update badge levels
        for level in range(1, lvl + 1):
            # Calculate reward
            is_extra = table_badge[level - 1][3]
            badge_reward = int(table_badge[level - 1][2])
            reward = calculate_extra_credit_reward(target, badge_reward, type, badge_id) if is_extra else badge_reward

            # Award badge
            description = get_description(name, level)
            award(target, type, description, reward, badge_id)

def award_bonus(target, name, reward):
    """
    Awards a given bonus to a specific target.
    """

    award(target, "bonus", name, reward)

def award_exam_grade(target, name, reward):
    """
    Awards a given exam grade to a specific target.
    """

    award(target, "exam", name, reward)

def award_lab_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards lab grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per lab
     > max_grade ==> max. grade per lab
    """

    for log in logs:
        lab_nr = int(log[config.DESCRIPTION_COL])
        name = "Lab %s" % lab_nr
        reward = (int(log[config.RATING_COL]) / max_grade) * max_xp
        award(target, "labs", name, reward, lab_nr)

def award_post_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards post grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per post
     > max_grade ==> max. grade per post
    """

    for log in logs:
        name = log[config.DESCRIPTION_COL].decode().split(",")[0]
        reward = (int(log[config.RATING_COL]) / max_grade) * max_xp
        award(target, "post", name, reward)

def award_presentation_grade(target, name, reward):
    """
    Awards a given presentation grade to a specific target.
    """

    award(target, "presentation", name, reward)

def award_quiz_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards quiz grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per quiz
     > max_grade ==> max. grade per quiz
    """

    for log in logs:
        name = log[config.DESCRIPTION_COL].decode()
        reward = (int(log[config.RATING_COL]) / max_grade) * max_xp
        award(target, "quiz", name, reward)

def award_skill(target, name, rating, logs, dependencies=True, use_wildcard=False):
    """
    Awards a given skill to a specific target.
    Option to spend a wildcard to give award.

    NOTE: will retract if rating changed.
    Updates award if reward has changed.
    """

    def award_participation(award_id, participation_id):
        query = "SELECT participation FROM award_participation WHERE award = %s;"
        results = db.execute_query(query, (award_id,))

        # Everything up to date
        if len(results) > 0 and results[0][0] == participation_id:
            return

        needs_update = len(results) > 0 and results[0][0] != participation_id

        query = "UPDATE award_participation SET participation = %s WHERE award = %s;" if needs_update \
            else "INSERT INTO award_participation (participation, award) VALUES (%s, %s);"
        db.execute_query(query, (participation_id, award_id), "commit")

    def spend_wildcard(award_id, use_wildcard):
        query = "SELECT COUNT(*) FROM award_wildcard WHERE award = %s;"
        used_wildcard = int(db.execute_query(query, (award_id,))[0][0]) > 0

        if use_wildcard and not used_wildcard:
            # Use a wildcard skill
            query = "INSERT INTO award_wildcard (award) VALUES (%s);"
            db.execute_query(query, (award_id,), "commit")

        elif not use_wildcard and used_wildcard:
            # Delete wildcard usage
            query = "DELETE FROM award_wildcard WHERE award = %s;"
            db.execute_query(query, (award_id,), "commit")

    def calculate_skill_reward(target, reward, max_total_reward):
        # Get total skill reward
        query = "SELECT IFNULL(SUM(reward), 0) FROM " + awards_table + \
                " WHERE course = %s AND user = %s AND type = %s AND moduleInstance != %s;"
        total_reward = int(db.execute_query(query, (config.COURSE, target, type, skill_id))[0][0])

        return max(min(max_total_reward - total_reward, reward), 0)

    type = "skill"
    awards_table = get_awards_table()

    if not config.TEST_MODE and module_enabled("Skills"):
        # Get min. rating
        query = "SELECT minRating FROM skills_config WHERE course = %s;" % config.COURSE
        min_rating = int(db.data_broker.get(db, config.COURSE, query)[0][0])

        # Get skill info
        query = "SELECT s.id, t.reward, s.isExtra, st.maxReward " \
                "FROM skill s LEFT JOIN skill_tier t on s.tier = t.id " \
                "LEFT JOIN skill_tree st on t.skillTree = st.id " \
                "WHERE s.course = %s AND s.name = '%s';" % (config.COURSE, name)
        table_skill = db.data_broker.get(db, config.COURSE, query)[0]
        skill_id = table_skill[0]

        # Rating is not enough to win the award or dependencies haven't been met
        if rating < min_rating or not dependencies:
            # Get awards given for skill
            query = "SELECT COUNT(*) FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description = %s;"
            nr_awards = int(db.execute_query(query, (config.COURSE, target, type, name))[0][0])

            # Delete invalid award
            if nr_awards > 0:
                query = "DELETE FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
                db.execute_query(query, (config.COURSE, target, type, name, skill_id), "commit")

        else:
            # Calculate reward
            is_extra = table_skill[2]
            max_reward = int(table_skill[3])
            skill_reward = int(table_skill[1])
            reward = calculate_extra_credit_reward(target, skill_reward, type, skill_id) if is_extra else skill_reward
            reward = calculate_skill_reward(target, reward, max_reward)

            # Award skill
            award(target, type, name, reward, skill_id)
            query = "SELECT id FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
            award_id = db.execute_query(query, (config.COURSE, target, type, name, skill_id))[0][0]

            # Spend wildcard
            spend_wildcard(award_id, use_wildcard)

            # Update award participation
            # NOTE: logs are ordered by date ASC
            participation_id = logs[len(logs) - 1][0]
            award_participation(award_id, participation_id)


### Utils

def skill_completed(target, name):
    """
    Checks whether a given skill has already been awarded
    to a specific target.
    """

    return award_received(target, name, "skill")

def has_wildcard_available(target, skill_tree_id, wildcard_tier):
    """
    Checks whether a given target has wildcards available to use.
    """

    type = "skill"
    awards_table = get_awards_table()

    # Get completed skill wildcards
    query = "SELECT COUNT(*) " \
            "FROM " + awards_table + " a LEFT JOIN skill s on a.moduleInstance = s.id " \
            "LEFT JOIN skill_tier t on s.tier = t.id " \
            "WHERE a.course = %s AND a.user = %s AND t.skillTree = %s AND t.name = %s;"
    nr_completed_wildcards = int(db.execute_query(query, (config.COURSE, target, skill_tree_id, wildcard_tier))[0][0])

    # Get used wildcards
    query = "SELECT IFNULL(SUM(aw.nrWildcardsUsed), 0) " \
            "FROM " + awards_table + " a LEFT JOIN award_wildcard aw on a.id = aw.award " \
            "WHERE a.course = %s AND a.user = %s AND a.type = %s;"
    nr_used_wildcards = int(db.execute_query(query, (config.COURSE, target, type))[0][0])

    return nr_completed_wildcards - nr_used_wildcards > 0



# FIXME: refactor below

def award_tokens(target, reward_name, tokens = None, contributions=None):
    # -----------------------------------------------------------
    # Simply awards tokens.
    # Updates 'user_wallet' table with the new total tokens for
    # a user and registers the award in the 'award' table.
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection
    
    #queries = db.queries
    #results = db.results

    course = config.COURSE
    typeof = "tokens"

    if tokens != None:
        reward = int(tokens)
    else:
        query = "SELECT tokens FROM virtual_currency_to_award WHERE course = \"" +  str(course) + "\" AND name = \"" +  str(reward_name) + "\";"
        #cursor.execute(query, (course, reward_name))
        #table_currency = cursor.fetchall()
        result = db.data_broker.get(course, query)
        if not result:
            cursor.execute(query)
            table_currency = cursor.fetchall()
            db.data_broker.add(course, query, table_currency)
            #queries.append(query)
            #results.append(level_table)
        else:
            table_currency = result

        reward = table_currency[0][0]


    if config.TEST_MODE:
        awards_table = "award_test"
    else:
        awards_table = "award"

    query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"
    cursor.execute(query, (target, course, reward_name, typeof))
    table = cursor.fetchall()

    query = "SELECT tokens FROM user_wallet where user = %s AND course = %s;"
    cursor.execute(query, (target, course))
    table_wallet = cursor.fetchall()

    if len(table_wallet) == 0: # user has not been inserted in user_wallet yet and tokens to award are already defined => initialTokens
        # insert in award
        query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
        cursor.execute(query, (target, course, reward_name, typeof, reward))
        connect.commit()
        # insert and give the award
        query = "INSERT INTO user_wallet (user, course, tokens) VALUES(%s, %s , %s);"
        cursor.execute(query, (target, course, reward))
        connect.commit()
    elif len(table) == 0:

        if contributions == None:
            # insert in award
            query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
            cursor.execute(query, (target, course, reward_name, typeof, reward))
            connect.commit()

            newTotal = reward + table_wallet[0][0]

            # simply award the tokens
            query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
            cursor.execute(query, (newTotal, course, target))
            connect.commit()
        else:
            for i in range(len(contributions)):
                query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
                cursor.execute(query, (target, course, reward_name, typeof, reward))
                connect.commit()

            newTotal = table_wallet[0][0] + reward * len(contributions)

            # simply award the tokens
            query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
            cursor.execute(query, (newTotal, course, target))
            connect.commit()

    elif len(table) > 0 and contributions != None:
         query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"

         query = "SELECT id from award where user = %s AND course = %s AND description=%s AND type=%s;"
         cursor.execute(query, (target, course, reward_name, typeof))
         table_id = cursor.fetchall()
         award_id = table_id[0][0]

         for diff in range(len(table_id), len(contributions)):
             query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
             cursor.execute(query, (target, course, reward_name, typeof, reward))
             connect.commit()

         dif = len(contributions) - len(table_id)
         newTotal = table_wallet[0][0] + reward * dif
         # simply award the tokens
         query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
         cursor.execute(query, (newTotal, course, target))
         connect.commit()


    #cnx.close()

    return


def award_tokens_type(target, type, element_name, to_award):
    # -----------------------------------------------------------
    # To use after awarding users with a streak, skill, ...
    # Checks if type given was awarded and, if it was, updates
    # 'user_wallet' table with the new total tokens for a user
    # and registers the award in the 'award' table.
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    #queries = db.queries
    #results = db.results

    course = config.COURSE
    typeof = "tokens"

    if type == "streak":
        query = "SELECT tokens FROM streak where course = \"" + course + "\" AND name = \"" + element_name + "\";"
        result = db.data_broker.get(course, query)
        if not result:
            cursor.execute(query)
            table_reward = cursor.fetchall()
            db.data_broker.add(course, query, table_reward)
        else:
            table_reward = result
        reward = int(table_reward[0][0])
    else:
        # TODO
        # Just for the time being.
        # If skills, badges, ... are supposed to give tokens, add in here.
        reward = 0

    if config.TEST_MODE:
        awards_table = "award_test"
    else:
        awards_table = "award"

    if len(to_award) == 0: # No award was given
        return
    elif len(to_award) > 0: # Award was given

        # nr awards da streak == nr award tokens for completed streak

        name_awarded = element_name + " Completed"
        query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"
        cursor.execute(query, (target, course, name_awarded, typeof))
        table = cursor.fetchall()

        query = "SELECT tokens FROM user_wallet where user = %s AND course = %s;"
        cursor.execute(query, (target, course))
        table_wallet = cursor.fetchall()

        awards = len(to_award)
        for diff in range(len(table), to_award):
            query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
            cursor.execute(query, (target, course, name_awarded, typeof, reward))
            connect.commit()

        name_awarded = element_name + " Completed"
        query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"
        cursor.execute(query, (target, course, name_awarded, typeof))
        table_after = cursor.fetchall()

        newTotal = table_wallet[0][0] + reward * (len(table_after) - len(table))

        # simply award the tokens
        query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
        cursor.execute(query, (newTotal, course, target))
        connect.commit()

    #cnx.close()

    return

def get_valid_attempts(target, skill):
    # -----------------------------------------------------------
    # Returns number of valid attempts for a given skill
    # -----------------------------------------------------------
    cursor = db.cursor
    connect = db.connection

    ##queries = db.queries
    #results = db.results

    course = config.COURSE

    query = "SELECT attemptRating FROM virtual_currency_config where course = \"" + course + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_currency = cursor.fetchall()
        db.data_broker.add(course, query, table_currency)
    else:
        table_currency = result

    minRating = table_currency[0][0]

    query = "SELECT * FROM participation where user = %s AND course = %s AND type='graded post' AND description = %s AND rating >= %s ;"
    cursor.execute(query, (target, course, 'Skill Tree, Re: ' + skill, minRating))
    table_count = cursor.fetchall()
    validAttempts = len(table_count)

    return validAttempts


def get_new_total(target, validAttempts, rating):
    # -----------------------------------------------------------
    # Checks if user has enough tokens to spend.
    # Returns the user's new wallet total.
    # -----------------------------------------------------------
    cursor = db.cursor
    connect = db.connection

    #queries = #
    #results = db.results

    course = config.COURSE

    query = "SELECT tokens FROM user_wallet where user = %s AND course = %s;"
    cursor.execute(query, (target, course))
    table_tokens = cursor.fetchall()
    currentTokens = table_tokens[0][0]

    query = "SELECT skillCost, wildcardCost, attemptRating, costFormula, incrementCost FROM virtual_currency_config where course = \"" + course + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_tokens = cursor.fetchall()
        db.data_broker.add(course, query, table_tokens)

    else:
        table_tokens = result

    skillcost = table_tokens[0][0]
    wildcardcost = table_tokens[0][1]
    minRating = table_tokens[0][2]
    formula = table_tokens[0][3]
    incrementCost = table_tokens[0][4]

    # no tokens need to be removed
    if rating < minRating:
        return

    # * * * * * * * * * * * * * FORMULA OPTIONS * * * * * * * * * * * * * #
    #                                                                     #
    #  Case 0 - SUB: removed = incrementCost                              #
    #  Case 1 - MUL: removed = incrementCost * validAttempts              #
    #           e.g.: 1st = 10, 2nd = 20, 3rd = 30, 4th = 40, ... ,       #
    #  Case 2 - POW: removed = incrementCost * pow(2, validAttempts - n)  #
    #           e.g.: 1st = 10, 2nd = 20, 3rd = 40, 4th = 80,             #
    # * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * #

    if formula == '0':
        removed = incrementCost
    elif formula == '1':
        removed =  incrementCost * validAttempts
    elif formula == '2':
        if skillCost == 0:
            n = 2
        else:
            n = 1
        removed = pow(2, validAttempts - n) * incrementCost
    else:
        removed = incrementCost

    if validAttempts == 0:
        if tier.decode() == 'Wildcard':
           removed  = wildcardCost
        else:
            removed  = skillCost
        newTotal = currentTokens - removed
    else:
        newTotal = currentTokens - removed

    return (newTotal, removed)


def update_wallet(target, newTotal, removed, contributions=None):
    # -----------------------------------------------------------
    # Updates 'user_wallet' table with the new total tokens for
    # a user.
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    ##queries = db.queries
    #results = db.results

    course = config.COURSE

    query = "SELECT award FROM award_participation LEFT JOIN award ON award_participation.award = award.id where user = \""+ str(target) +"\" AND course = \""+ course +"\" AND participation = \""+ str(contributions[0].log_id) +"\";"
    cursor.execute(query)
    table_awarded = cursor.fetchall()

    awarded = len(table_awarded)

    # if no award was given, there are no tokens to remove
    if awarded == 0:
        return

    query = "SELECT * FROM remove_tokens_participation where user = \""+ str(target) +"\" AND course = \""+ course +"\" AND participation = \""+ str(contributions[0].log_id) +"\" ;"
    cursor.execute(query)
    table_removed = cursor.fetchall()
    
    alreadyRemoved = len(table_removed)

    if alreadyRemoved == 1:
        return

    query = "INSERT INTO remove_tokens_participation (course, user, participation, tokensRemoved) VALUES(%s, %s, %s, %s); "
    cursor.execute(query, (course, target, contributions[0].log_id, removed))
    connect.commit()

    # simply remove the tokens
    query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
    cursor.execute(query, (newTotal, course, target))
    connect.commit()

    return


def remove_tokens(target, tokens = None, skillName = None, contributions=None):
    # -----------------------------------------------------------
    # Updates 'user_wallet' table with the new total tokens for
    # a user.
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    #queries = #
    #results = db.results

    course = config.COURSE
    typeof = "tokens"

    if config.TEST_MODE:
        awards_table = "award_test"
    else:
        awards_table = "award"

    query = "SELECT tokens FROM user_wallet where user = %s AND course = %s;"
    cursor.execute(query, (target, course))
    table_tokens = cursor.fetchall()
    currentTokens = table_tokens[0][0]

    query = "SELECT skillCost, wildcardCost, attemptRating, costFormula, incrementCost FROM virtual_currency_config where course = \"" + course + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_tokens = cursor.fetchall()
        db.data_broker.add(course, query, table_tokens)
    else:
        table_tokens = result

    skillcost = table_tokens[0][0]
    wildcardcost = table_tokens[0][1]
    minRating = table_tokens[0][2]
    formula = table_tokens[0][3]
    incrementCost = table_tokens[0][4]

    # If tokens are give, simply remove them for the user.
    if tokens != None:
        query = "SELECT participation FROM remove_tokens_participation WHERE user = %s AND course = %s AND participation = %s ;"
        cursor.execute(query, (target, course, contributions[0].log_id))
        table_removed = cursor.fetchall()

        if len(table_removed) == 0:
            toRemove = int(tokens)
            #newTotal = currentTokens - toRemove

            query = "INSERT INTO remove_tokens_participation (course, user, participation, tokensRemoved) VALUES(%s, %s, %s, %s); "
            cursor.execute(query, (course, target, contributions[0].log_id, toRemove))
            connect.commit()

            query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
            cursor.execute(query, (toRemove, course, target))
            connect.commit()

            #cnx.close()
            #return newTotal

        # else: Tokens have already been removed for that participation

    # Remove tokens for skill retry
    elif contributions != None and skillName != None:

        query = "SELECT s.id, s.tier FROM skill s join skill_tier on s.tier=skill_tier.id join skill_tree t on t.id=skill_tier.skillTree where s.name = \""+ skillName +"\" and course = \""+ course +"\";"
        result = db.data_broker.get(course, query)
        if not result:
            cursor.execute(query)
            table_skill = cursor.fetchall()
            db.data_broker.add(course, query, table_skill)
        else:
            table_skill = result

        tier = table_skill[0][1]

        # gets all submissions from participation
        query = "SELECT * FROM participation where user = %s AND course = %s AND type='graded post' AND description = %s AND rating >= %s ;"
        cursor.execute(query, (target, course, 'Skill Tree, Re: ' + skillName, minRating))
        table_counter_participations = cursor.fetchall()
        validAttempts = len(table_counter_participations)

        query = "SELECT * FROM remove_tokens_participation where user = %s AND course = %s AND participation = %s ;"
        cursor.execute(query, (target, course, contributions[0].log_id))
        table_participation = cursor.fetchall()
        # We need to check if the participation at cause has already been inserted in the table so that we do not
        # remove tokens for the same participation
        alreadySubmitted = len(table_participation)

        if alreadySubmitted != 0:
            #cnx.close()
            return currentTokens
        else:

            # * * * * * * * * * * * * * FORMULA OPTIONS * * * * * * * * * * * * * #
            #                                                                     #
            #  Case 0 - SUB: removed = incrementCost                             #
            #  Case 1 - MUL: removed = incrementCost * validAttempts              #
            #           e.g.: 1st = 10, 2nd = 20, 3rd = 30, 4th = 40, ... ,       #
            #  Case 2 - POW: removed = incrementCost * pow(2, validAttempts - n)  #
            #           e.g.: 1st = 10, 2nd = 20, 3rd = 40, 4th = 80,             #
            # * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * #

            if formula == '0':
                removed = incrementCost
            elif formula == '1':
                removed =  incrementCost * validAttempts
            elif formula == '2':
                if skillCost == 0:
                    n = 2
                else:
                    n = 1
                removed = pow(2, validAttempts - n) * incrementCost

            if validAttempts == 0:
                if tier.decode() == 'Wildcard':
                   removed  = wildcardCost
                else:
                    removed  = skillCost
                newTotal = currentTokens - removed
            else:
                newTotal = currentTokens - removed

            if newTotal >= 0:
                query = "INSERT INTO remove_tokens_participation (course, user, participation, tokensRemoved) VALUES(%s, %s, %s, %s); "
                cursor.execute(query, (course, target, contributions[0].log_id, removed))
                connect.commit()

                # simply remove the tokens
                query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
                cursor.execute(query, (newTotal, course, target))
                connect.commit()

            #cnx.close()
            return newTotal

    #cnx.close()
    return

def get_team(target):
    # -----------------------------------------------------------
    # Gets team id from target
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    course = config.COURSE

    query = "SELECT teamId FROM teams_members where memberId = \"" + str(target) + "\";"
    cursor.execute(query)
    table = cursor.fetchall()


    if len(table) == 1:
        team = table[0][0]
    elif len(table) == 0:
        print("ERROR: No student with given id found in teams_members database.")
        team = None
    else:
        print("ERROR: More than one student with the same id in teams_member database.")
        team = None

    return team



def award_team_grade(target, item, contributions=None, extra=None):
    # -----------------------------------------------------------
    # Writes 'award' table with reward that is not a badge or a
    # skill. Will not retract effects, but will not award twice
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    course = config.COURSE

    if config.TEST_MODE:
        awards_table = "award_teams_test"
    else:
        awards_table = "award_teams"

    if item == "Lab":
        description = "Lab Grade"
        typeof = "labs"
    elif item == "Presentation":
        description = "Presentation Grade"
        typeof = "presentation"

    query = "SELECT moduleInstance, reward FROM " + awards_table + " where team = %s AND course = %s AND type=%s AND description = %s;"
    cursor.execute(query, (target, course, typeof, description))
    table = cursor.fetchall()

    if item == "Presentation":
        for line in contributions:
            grade = int(line.rating)
            if len(table) == 0:
                query = "INSERT INTO " + awards_table + " (team, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
                cursor.execute(query, (target, course, description, typeof, grade))
                connect.commit()
                config.award_list.append([str(target), "Grade from " + item, str(grade), ""])

            elif len(table) == 1:
                query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND team = %s AND type=%s AND description=%s"
                cursor.execute(query, (grade, course, target, typeof, description))
                connect.commit()


    #cnx.close()
    return


def get_consecutive_peergrading_logs(target, streak, contributions):
    # -----------------------------------------------------------
    # Verifies moodle peergrading logs, adds them to the progression table.
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    #queries = db.queries
    #results = db.results

    course = config.COURSE
    typeof = "streak"

    size = len(contributions)


    if not config.TEST_MODE:

        # get streak info
        query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic, isAtMost, isActive from streak where course = \"" + course + "\" and name = \"" + streak + "\";"
        result = db.data_broker.get(course, query)
        if not result:
            cursor.execute(query)
            table_streak = cursor.fetchall()
            db.data_broker.add(course, query, table_streak)

        else:
            table_streak = result

        streakid = table_streak[0][0]

        for i in range(size):
            id = contributions[i][0]
            expired = contributions[i][2]
            ended = contributions[i][3]
            if expired == 0:
                if ended == 1:
                    valid = 1
                else:
                    valid = 0
            else:
                valid = 0

            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
            cursor.execute(query, (course, target, streakid, id, valid))
            connect.commit()


def get_consecutive_rating_logs(target, streak, type, rating, only_skill_posts):
    # -----------------------------------------------------------
    # Check if participations
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    #queries = db.queries
    #results = db.results

    course = config.COURSE
    typeof = "streak"

    participationType = type + "%"

    if rating == None:
        logging.exception("Minimum rating was not given as an argument in rule.")
        return

    if only_skill_posts:
        query = "SELECT id, rating FROM participation WHERE user = %s AND course = %s AND type = %s and description like 'Skill Tree%';"
        cursor.execute(query, (target, course, type))
        table_participations = cursor.fetchall()
    else:
        query = "SELECT id, rating FROM participation WHERE user = %s AND course = %s AND type = %s;"
        cursor.execute(query, (target, course, type))
        table_participations = cursor.fetchall()


    if len(table_participations) <= 0:
        return

    # get streak info
    query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic, isAtMost, isActive from streak where course = \"" + course + "\" and name = \"" + streak + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_streak = cursor.fetchall()
        db.data_broker.add(course, query, table_streak)
    else:
        table_streak = result

    streakid = table_streak[0][0]


    size = len(table_participations)
    for i in range(size):

        participation = table_participations[i][0]
        participationRating = table_participations[i][1]

        if participationRating < rating:
            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
            cursor.execute(query, (course, target, streakid, participation, '0'))
            connect.commit()
        else:
            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
            cursor.execute(query, (course, target, streakid, participation, '1'))
            connect.commit()


def get_consecutive_logs(target, streak, type):
    # -----------------------------------------------------------
    # Check if participations
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    #queries = db.queries
    #results = db.results

    course = config.COURSE
    typeof = "streak"

    participationType = type + "%"

    if type.startswith("quiz"):
        query = "SELECT id, description, rating FROM participation WHERE user = %s AND course = %s AND type = %s AND description like 'Quiz%' ORDER BY description ASC;"
        cursor.execute(query, (target, course, type))
        table_participations = cursor.fetchall()
    else:
        query = "SELECT id, description, rating FROM participation WHERE user = %s AND course = %s AND type like %s ORDER BY description ASC;"
        cursor.execute(query, (target, course, participationType))
        table_participations = cursor.fetchall()

    if len(table_participations) <= 0:
        return

    # get streak info
    query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic, isAtMost, isActive from streak where course = \"" + course + "\" and name = \"" + streak + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_streak = cursor.fetchall()
        db.data_broker.add(course, query, table_streak)
    else:
        table_streak = result

    streakid = table_streak[0][0]

    if type.startswith("attended") or type.endswith("grade"):

        # NOTE:
        # This part is extremely tailored towads MCP 2021/2022

        # TODO :
        # Retrieve these values from db

        maxlabs = 125
        maxlab_impar = 150
        maxlab_par = 400
        max_quiz = 1000

        size = len(table_participations)

        for i in range(size):
            j = i+1

            if j < size:

                firstParticipationId = table_participations[i][0]
                secondParticipationId = table_participations[j][0]

                if type == "quiz grade":

                    quiz1 = table_participations[i][1]
                    quiz2 = table_participations[j][1]
                    # gets quiz number since description = 'Quiz X'
                    description1 = int(quiz1[-1])
                    description2 = int(quiz2[-1])

                    rating1 = table_participations[i][2]
                    rating2 = table_participations[j][2]
                else:
                    description1 = int(table_participations[i][1])
                    description2 = int(table_participations[j][1])
                    if type == "lab grade":
                        rating1 = table_participations[i][2]
                        rating2 = table_participations[j][2]


                # if participation is not consecutive, inserts in table as invalid
                if description2 - description1 != 1:
                    query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                    connect.commit()
                else:
                    # if consecutive, check if rating is max for grades
                    if participationType == "quiz grade":
                        if rating1 != max_quiz:
                            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                            connect.commit()
                        else:
                            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                            connect.commit()
                            if j == size-1:
                                if rating2 != max_quiz:
                                    query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                    connect.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                    connect.commit()
                    elif type == "lab grade":
                        if  description1 == 1 or description1 == 2:
                            if rating1 != maxlabs:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                connect.commit()
                            else:

                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                connect.commit()
                                if j == size-1:
                                    if rating2 == maxlabs and description2 == 2:
                                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                        connect.commit()
                                    elif rating2 == maxlab_impar and description2 == 3:
                                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                        connect.commit()
                                    else:
                                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                        connect.commit()
                        elif (description1 % 2 != 0):
                            if rating1 != maxlab_impar:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                connect.commit()
                            else:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                connect.commit()
                                if j == size-1:
                                    if rating2 != maxlab_par:
                                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                        connect.commit()
                                    else:
                                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                        connect.commit()
                        elif  (description1 % 2 == 0):
                            if rating1 != maxlab_par:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                connect.commit()
                            else:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                connect.commit()
                                if j == size-1:
                                    if rating2 != maxlab_impar:
                                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                        connect.commit()
                                    else:
                                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                        connect.commit()
                    else:
                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                        cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                        connect.commit()
                        if j == size-1:
                            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                            connect.commit()

    # elif: other checks to implement
    # TODO : this is only for isCount streaks (do not exist)
    #else:
    #    for log in table_participations:
    #        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s); "
    #        cursor.execute(query, (course, target, streakid, log.log_id, '1'))
    #        cnx.commit()



def get_periodic_logs(target, streak_name, contributions, participationType = None):
    # -----------------------------------------------------------
    # Verifies periodic streak participations and adds them to the progression table.
    #   Periodic : a skill every x [selected time period (minutes, hours, days, weeks)]
    #       & Count : Do X skills in [selected time period]
    #       & atMost: Do X skills with NO MORE THAN Y [selected time period] between them
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    #queries = db.queries
    #results = db.results

    course = config.COURSE
    typeof = "streak"

    if len(contributions) <= 0:
        return

    # get streak info
    query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic, isAtMost, isActive from streak where course = \"" + course + "\" and name = \"" + streak_name + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_streak = cursor.fetchall()
        db.data_broker.add(course, query, table_streak)
    else:
        table_streak = result
        

    if not config.TEST_MODE:
        streakid, isCount, isPeriodic, isAtMost  = table_streak[0][0], table_streak[0][6], table_streak[0][7], table_streak[0][8]
        periodicity, periodicityTime = table_streak[0][1], table_streak[0][2]

        if not isPeriodic:
            return
        else:
            if isCount:
                # ******************************************************* #
                #               Do X [action] in [periodicity]            #
                # ******************************************************* #
                logging.exception(contributions[0][1])
                firstParticipationDate = contributions[0][1]
                secondParticipationDate = contributions[-1][1]

                if len(periodicityTime) == 7:  # minutes
                    dif = secondParticipationDate - firstParticipationDate
                    if dif > timedelta(minutes=periodicity):
                        return
                elif len(periodicityTime) == 5:   # hours
                    dif = secondParticipationDate - firstParticipationDate
                    if dif > timedelta(hours=periodicity):
                        return
                elif len(periodicityTime) == 4:   # days
                    dif = secondParticipationDate.date() - firstParticipationDate.date()
                    if dif > timedelta(days=periodicity):
                        return
                elif len(periodicityTime) == 6:  # weeks_
                    weeksInDays = periodicity*7
                    dif = secondParticipationDate.date() - firstParticipationDate.date()
                    if dif > timedelta(days=weeksInDays):
                       return
                else:
                    return

                for log in contributions:
                    query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s); "
                    cursor.execute(query, (course, target, streakid, log[0], '1'))
                    connect.commit()

            elif isAtMost:
                # ******************************************************* #
                #      Do X [action] with no more than [periodicity]      #
                #    between them                                         #
                # ******************************************************* #

                all = len(contributions)
                skills = []

                # TODO: This part only allows skills. Needs refactoring.

                # To only count for a skill or badge one time. (a retrial in a skill should not count)
                filtered = []
                for i in range(all):
                    name = contributions[i][3]
                    if name not in skills:
                        rating = contributions[i][4]
                        if rating > 2:
                            skills.append(name)
                            filtered.append(contributions[i])

                size = len(filtered)
                for i in range(size):
                    j = i+1
                    if size == 1:
                        participation_id = filtered[i][0]
                        query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s); "
                        cursor.execute(query, (course, target, streakid, participation_id, '1'))
                        connect.commit()

                    if j < size:

                        if participationType == "graded post":
                            # ************ FIRST SUBMISSION DATE ************** #
                            firstgradedPost = (filtered[i][2]).decode("utf-8")   # e.g: mod/peerforum/discuss.php?d=38#p65
                            indexpost = 0
                            for m in range(len(firstgradedPost)):
                                if firstgradedPost[m] == '#':
                                    indexpost = m
                                    break

                            p_gradedPost = firstgradedPost[indexpost+2:]
                            d_gradedPost = firstgradedPost[28:indexpost]
                            firstGraded  = "mod/peerforum/discuss.php?d=" + str(d_gradedPost) + "&parent="  +  str(p_gradedPost)

                            # gets date from student post
                            query = "SELECT date FROM participation WHERE user = %s and course = %s and type = 'peerforum add post' AND post = %s;"
                            cursor.execute(query, (target, course, firstGraded))
                            table_first_date = cursor.fetchall()

                            # ************ SECOND SUBMISSION DATE ************** #

                            secondgradedPost = (filtered[j][2].decode("utf-8") )
                            indexpost2 = 0
                            for n in range(len(secondgradedPost)):
                                if secondgradedPost[n] == '#':
                                    indexpost2 = n
                                    break

                            p_gradedPost2 = secondgradedPost[indexpost2+2:]
                            d_gradedPost2 = secondgradedPost[28:indexpost2]
                            secondGraded  = "mod/peerforum/discuss.php?d=" + str(d_gradedPost2) + "&parent="  +  str(p_gradedPost2)

                            query = "SELECT date FROM participation WHERE user = %s AND course = %s AND type = 'peerforum add post' AND post = %s; "
                            cursor.execute(query, (target, course, secondGraded))
                            table_second_date = cursor.fetchall()

                            firstId = filtered[i][0]
                            secondId = filtered[j][0]

                            firstDate = table_first_date[0][0]  # YYYY-MM-DD HH:MM:SS
                            secondDate = table_second_date[0][0]
                            
                        else:
                           firstId = contributions[i][0]
                           secondId = contributions[j][0]
                           
                           firstDate = contributions[i][1]
                           secondDate = contributions[j][1]


                        # *********************************************** #
                        # ************ VERIFICATION BEGINS ************** #
                        # *********************************************** #

                        firstParticipationId = firstId
                        secondParticipationId = secondId

                        firstParticipationDate = firstDate  # YYYY-MM-DD HH:MM:SS
                        secondParticipationDate = secondDate

                        if len(periodicityTime) == 7:  # minutes
                            dif = secondParticipationDate - firstParticipationDate
                            timePeriod = timedelta(minutes=periodicity)
                        elif len(periodicityTime) == 5:   # hours
                            dif = secondParticipationDate - firstParticipationDate
                            timePeriod = timedelta(hours=periodicity)
                        elif len(periodicityTime) == 4 or len(periodicityTime) == 6:   # days or weeks
                            if len(periodicityTime) == 6:
                                periodicityDays = periodicity * 7
                            else:
                                periodicityDays = periodicity

                            dif = secondParticipationDate.date() - firstParticipationDate.date()
                            timePeriod = timedelta(days=periodicityDays)
                        else:
                            return

                        if dif > timePeriod:
                            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                            connect.commit()
                        else:
                            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                            connect.commit()

                        if j == size-1:
                            query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                            connect.commit()

            else:
                # Simply periodic -> do [action] every [periodicity]
                #                           - minutes, hours, days, weeks
                participationType = (contributions[0][-1]).decode("utf-8")
                description = (contributions[0][3]).decode("utf-8")

                if participationType == "graded post" and description.find("Skill") != -1:
                    all = len(contributions)
                    skills = []
                    # To only count for a skill or badge one time. (a retrial in a skill should not count)
                    filtered = []
                    for i in range(all):
                        name = contributions[i][3]
                        if name not in skills:
                            rating = contributions[i][4]
                            if rating > 2:
                                skills.append(name)
                                filtered.append(contributions[i])
                    size = len(contributions)
                else:
                    ''' gets date of participations that matter, disgarding submission withtin the same time period
                    if len(periodicityTime) == 7:  # minutes - gets all participations
                        query = "SELECT id, date FROM participation WHERE %s AND course = %s AND type = %s;"
                    elif len(periodicityTime) == 5:  # hours - gets participations with different hours only
                        query = "SELECT id, date FROM participation WHERE %s AND course = %s AND type= %s GROUP BY hour(date), day(date) ORDER BY id;"
                    elif len(periodicityTime) == 4 or len(periodicityTime) == 6:   # days or weeks - gets only distinct days
                        query = "SELECT id, date FROM participation WHERE %s AND course = %s AND type= %s GROUP BY day(date);"
                    else:
                        return
                                  '''
                    if participationType == 'peergraded post':
                        whereUser = "evaluator = '" + str(target)  + "'"
                        if len(periodicityTime) == 7:  # minutes - gets all participations
                            query = "SELECT id, date FROM participation WHERE evaluator = %s AND course = %s AND type = %s;"
                        elif len(periodicityTime) == 5:  # hours - gets participations with different hours only
                            query = "SELECT id, date FROM participation WHERE evaluator = %s AND course = %s AND type= %s GROUP BY hour(date), day(date) ORDER BY id;"
                        elif len(periodicityTime) == 4 or len(periodicityTime) == 6:   # days or weeks - gets only distinct days
                            query = "SELECT id, date FROM participation WHERE evaluator = %s AND course = %s AND type= %s GROUP BY day(date);"
                        else:
                            return

                    else:
                        whereUser = "user = '" + str(target) + "'"

                        if len(periodicityTime) == 7:  # minutes - gets all participations
                            query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type = %s;"
                        elif len(periodicityTime) == 5:  # hours - gets participations with different hours only
                            query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type= %s GROUP BY hour(date), day(date) ORDER BY id;"
                        elif len(periodicityTime) == 4 or len(periodicityTime) == 6:   # days or weeks - gets only distinct days
                            query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type= %s GROUP BY day(date);"
                        else:
                            return
                        
                    cursor.execute(query, (target, course, participationType))
                    table_participations = cursor.fetchall()
                    size = len(table_participations)

                for i in range(size):
                     j = i+1
                     if j < size:

                        if participationType == "graded post" and description.find("Skill") != -1:
                            # ************ FIRST SUBMISSION DATE ************** #
                            firstgradedPost = (filtered[i][2]).decode("utf-8")   # e.g: mod/peerforum/discuss.php?d=38#p65
                            indexpost = 0
                            for m in range(len(firstgradedPost)):
                                if firstgradedPost[m] == '#':
                                    indexpost = m
                                    break

                            p_gradedPost = firstgradedPost[indexpost+2:]
                            d_gradedPost = firstgradedPost[28:indexpost]
                            firstGraded  = "mod/peerforum/discuss.php?d=" + str(d_gradedPost) + "&parent="  +  str(p_gradedPost)

                            # gets date from student post
                            query = "SELECT date FROM participation WHERE user = %s and course = %s and type = 'peerforum add post' AND post = %s;"
                            cursor.execute(query, (target, course, firstGraded))
                            table_first_date = cursor.fetchall()

                            # ************ SECOND SUBMISSION DATE ************** #
                            secondgradedPost = (filtered[j][2].decode("utf-8") )
                            indexpost2 = 0
                            for n in range(len(secondgradedPost)):
                                if secondgradedPost[n] == '#':
                                    indexpost2 = n
                                    break

                            p_gradedPost2 = secondgradedPost[indexpost2+2:]
                            d_gradedPost2 = secondgradedPost[28:indexpost2]
                            secondGraded  = "mod/peerforum/discuss.php?d=" + str(d_gradedPost2) + "&parent="  +  str(p_gradedPost2)

                            query = "SELECT date FROM participation WHERE user = %s AND course = %s AND type = 'peerforum add post' AND post = %s; "
                            cursor.execute(query, (target, course, secondGraded))
                            table_second_date = cursor.fetchall()

                            firstId = filtered[i][0]
                            secondId = filtered[j][0]

                            firstDate = table_first_date[0][0]  # YYYY-MM-DD HH:MM:SS
                            secondDate = table_second_date[0][0]

                        else:
                           firstId = table_participations[i][0]
                           secondId = table_participations[j][0]

                           firstDate = table_participations[i][1]
                           secondDate = table_participations[j][1]

                        # *********************************************** #
                        # ************ VERIFICATION BEGINS ************** #
                        # *********************************************** #

                        firstParticipationId = firstId
                        secondParticipationId = secondId

                        firstParticipationDate = firstDate 
                        secondParticipationDate = secondDate

                        # if it disrespects streak periodicity, then return
                        if len(periodicityTime) == 7:  # minutes
                            dif = secondParticipationDate - firstParticipationDate
                            margin = 5 # time for any possible delay
                            if dif < timedelta(minutes=periodicity-margin) or dif > timedelta(minutes=periodicity+margin):
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                connect.commit()
                            else:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                connect.commit()
                                if j == size-1:
                                   query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                   cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                   connect.commit()

                        elif len(periodicityTime) == 5:   # hours
                            dif = secondParticipationDate.time().hour - firstParticipationDate.time().hour
                            difDay = secondParticipationDate.date() - firstParticipationDate.date()

                            if difDay  == timedelta(days=1):
                                sumHours =  secondParticipationDate.time().hour + firstParticipationDate.time().hour
                                limit = 23
                                difLimit = 0

                                if time3.time().hour < limit: # before 23
                                    difLimit = limit - time3.time().hour
                                    sumHours += difLimit

                                calculatedPeriodicity = (sumHours-limit) + 1 + difLimit
                                if calculatedPeriodicity != periodicity:
                                    query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    connect.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streakId, participation, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                    connect.commit()

                                    if j == size-1:
                                       query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                       connect.commit()

                            elif dif != periodicity:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                connect.commit()
                            else:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                connect.commit()
                                if j == size-1:
                                   query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                   cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                   connect.commit()

                        elif len(periodicityTime) == 4 or len(periodicityTime) == 6:  # days or weeks
                            if len(periodicityTime) == 6:
                                periodicityDays = periodicity * 7
                            else:
                                periodicityDays = periodicity

                            dif = secondParticipationDate.date() - firstParticipationDate.date()
                            if dif != timedelta(days=periodicityDays): # dif needs to be equal to periodicity
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                connect.commit()
                            else:
                                query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                connect.commit()
                                if j == size-1:
                                   query = "INSERT into streak_participations (course, user, streak, participation, isValid) values (%s,%s,%s,%s,%s);"
                                   cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                   connect.commit()
                        else:
                            return


        # progression done in awards_to_give


def awards_to_give(target, streak_name):
    # -----------------------------------------------------------
    # Calculates how many awards yet to be given for a certain
    # streak.
    # -----------------------------------------------------------
    cursor = db.cursor
    connect = db.connection

    #queries = db.queries
    #results = db.results

    course = config.COURSE

    #       isPeriodic and not isCount || isPeriodic and isAtMost

    # get streak info
    query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic, isAtMost, isActive from streak where course = \"" + course + "\" and name = \"" + streak_name + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_streak = cursor.fetchall()
        db.data_broker.add(course, query, table_streak)

    else:
        table_streak = result


    streak_id, streak_count, isRepeatable = table_streak[0][0], table_streak[0][3], table_streak[0][5]

    query = "SELECT participation, isValid FROM streak_participations WHERE user = \"" + str(target) + "\" AND course = \"" + str(course) + "\" AND streak= \"" + str(streak_id) + "\" ;"
    cursor.execute(query)
    table_all_participations = cursor.fetchall()

    total = len(table_all_participations)
    awards = 0
    count = 0
    participations = []
    for p in range(total):
        id, participationValid = table_all_participations[p][0], table_all_participations[p][1]
        if participationValid:
            count = count + 1
            participations.append(id)
        else:
            count = 0

        if count == streak_count:
            awards = awards + 1
            count = 0


    if not isRepeatable and awards > 0 :
        awards = 1
        participations = participations[:streak_count]

    return awards, participations


def award_streak(target, streak, to_award, participations, type=None):
    # -----------------------------------------------------------
    # Writes and updates 'award' table with streaks won by the
    # user. Will retract if rules/participations have been
    # changed.
    # Is also responsible for creating indicators.
    # -----------------------------------------------------------

    cursor = db.cursor
    connect = db.connection

    ##queries = db.queries
    #results = db.results

    course = config.COURSE
    typeof = "streak"

    nlogs = len(participations)
    participationType = ''
    if type != None and streak != "Grader":
        participationType = type

    if config.TEST_MODE:
        awards_table = "award_test"
    else:
        awards_table = "award"



    # gets all awards for this user order by descending date (most recent on top)
    query = "SELECT * FROM " + awards_table + " where user = %s AND course = %s AND description like %s AND type=%s;"
    streak_name = streak + "%"
    cursor.execute(query, (target, course, streak_name, typeof))
    table = cursor.fetchall()

    # get streak info
    query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic, isAtMost, isActive from streak where course = \"" + course + "\" and name = \"" + streak + "\";"
    result = db.data_broker.get(course, query)
    if not result:
        cursor.execute(query)
        table_streak = cursor.fetchall()
        db.data_broker.add(course, query, table_streak)

    else:
        table_streak = result

    streakid = table_streak[0][0]
    isStreakActive = table_streak[0][9]

    if not isStreakActive:
        return

    # table contains  user, course, description,  type, reward, date
    # table = filtered awards_table
    if len(table) == 0:  # no streak has been awarded with this name for this user

        isRepeatable = table_streak[0][5]
        streak_count, streak_reward = table_streak[0][3], table_streak[0][4]

        if not isRepeatable:
            description = streak

            query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
            cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
            connect.commit()

            if not streak.startswith("Grader"):
                # gets award_id
                query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                cursor.execute(query, (target, course, description, typeof))
                table_id = cursor.fetchall()
                award_id = table_id[0][0]

                if not config.TEST_MODE:
                    for id in participations:
                        query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                        cursor.execute(query, (award_id, id))
                        connect.commit()

        else:
            # inserts in award table the new streaks that have not been awarded
            for diff in range(len(table), to_award):
                repeated_info = " (" + str(diff + 1) + ")"
                description = streak + repeated_info

                query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                connect.commit()

                if diff == 0:
                    query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                    cursor.execute(query, (target, course, description, typeof))
                    table_id = cursor.fetchall()
                    award_id = table_id[0][0]

                    if not config.TEST_MODE and not streak.startswith("Grader"):
                        for id in participations:
                            query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                            cursor.execute(query, (award_id, id))
                            connect.commit()


        #if not config.test_mode:
        #    if contributions != None and contributions != None:
        #        nr_contributions = str(len(contributions))
        #    else:
        #        nr_contributions = ''
        #
        #    config.award_list.append([str(target), str(streak), str(streak_reward), nr_contributions])

    # if this streak has already been awarded, check if it is repeatable to award it again.
    elif len(table) > 0:
        isRepeatable = table_streak[0][5]
        streak_count, streak_reward = table_streak[0][3], table_streak[0][4]

        if isRepeatable:

            # inserts in award table the new streaks that have not been awarded
            for diff in range(len(table), to_award):
                repeated_info = " (" + str(diff + 1) + ")"
                description = streak + repeated_info

                query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                connect.commit()

                query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                cursor.execute(query, (target, course, description, typeof))
                table_id = cursor.fetchall()
                award_id = table_id[0][0]

                if not config.TEST_MODE and not streak.startswith("Grader"):
                    for id in participations:
                        query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                        cursor.execute(query, (award_id, id))
                        connect.commit()

    connect.commit()


def get_username(target):
    # -----------------------------------------------------------
    # Returns the username of a target user
    # -----------------------------------------------------------

    cursor = db.cursor
    #connect = db.connection

    course = config.COURSE
    query = "select username from auth right join course_user on auth.user=course_user.id where course = %s and auth.user = %s;"

    cursor.execute(query, (course, target))
    table = cursor.fetchall()
    #cnx.close()

    if len(table) == 1:
        username = table[0][0]

    elif len(table) == 0:
        print("ERROR: No student with given id found in auth database.")
        username = None
    else:
        print("ERROR: More than one student with the same id in auth database.")
        username = None

    return username


def consecutive_peergrading(target):
    # -----------------------------------------------------------
    # Returns the peergrading of a target user
    # target = username (e.g, ist112345)
    # -----------------------------------------------------------

    cnx = mysql.connector.connect(user="pcm_moodle", password="Dkr1iRwEekJiPSHX9CeNznHlks",
    host='db.rnl.tecnico.ulisboa.pt', database='pcm_moodle')
    cursor = cnx.cursor(prepared=True)

    course = config.COURSE
    query = "select id, timeassigned, expired, ended from mdl_peerforum_time_assigned where component = %s and userid = (select id from mdl_user where username = %s);"
    comp  = 'mod_peerforum'
    if target is None:
        return []
    cursor.execute(query, (comp, target.decode()))
    table = cursor.fetchall()
    cnx.close()

    return table
