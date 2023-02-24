#!/usr/bin/env python3
import socket, logging
import mysql.connector
import math
from datetime import datetime, timedelta
import fnmatch

from io import StringIO
from course.logline import *
import config

from course.coursedata import read_achievements, read_tree
achievements = read_achievements()
tree_awards = read_tree()

from gamerules.connector.db_connector import gc_db as db
preloaded_logs = {}
preloaded_awards = {}


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
        query = "UPDATE autogame SET startedRunning = %s, finishedRunning = %s, isRunning = %s, runNext = 0 WHERE course = %s;"
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


### Preload information

def preload_logs(targets_ids):
    """
    Preloads logs for all targets.
    Ensures the database is accessed only once to retrieve logs.
    """

    global preloaded_logs

    query = "SELECT * FROM participation WHERE course = %s" % config.COURSE
    query += " AND user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    query += " ORDER BY date ASC;"
    logs = db.execute_query(query)

    preloaded_logs = {target_id: [] for target_id in targets_ids}
    for log in logs:
        log = (log[0], log[1], log[2], log[config.LOG_SOURCE_COL].decode() if log[config.LOG_SOURCE_COL] is not None else None,
               log[config.LOG_DESCRIPTION_COL].decode() if log[config.LOG_DESCRIPTION_COL] is not None else None,
               log[config.LOG_TYPE_COL].decode() if log[config.LOG_TYPE_COL] is not None else None,
               log[config.LOG_POST_COL].decode() if log[config.LOG_POST_COL] is not None else None, log[config.LOG_DATE_COL],
               log[config.LOG_RATING_COL], log[config.LOG_EVALUATOR_COL])

        target_id = log[1]
        if target_id in preloaded_logs:
            preloaded_logs[target_id].append(log)
        else:
            preloaded_logs[target_id] = [log]

def preload_awards(targets_ids):
    """
    Preloads awards for all targets.
    Ensures the database is accessed only once to retrieve awards.
    """

    global preloaded_awards

    query = "SELECT * FROM " + get_awards_table() + " WHERE course = %s" % config.COURSE
    query += " AND user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    awards = db.execute_query(query)

    preloaded_awards = {target_id: [] for target_id in targets_ids}
    for award in awards:
        award = (award[0], award[1], award[2], award[config.AWARD_DESCRIPTION_COL].decode() if award[config.AWARD_DESCRIPTION_COL] is not None else None,
                 award[config.AWARD_TYPE_COL].decode() if award[config.AWARD_TYPE_COL] is not None else None,
                 award[config.AWARD_INSTANCE_COL], award[config.AWARD_REWARD_COL], award[config.LOG_DATE_COL])

        target_id = award[1]
        if target_id in preloaded_awards:
            preloaded_awards[target_id].append(award)
        else:
            preloaded_awards[target_id] = [award]

def get_preloaded_award(target, description=None, type=None, instance=None, reward=None):
    global preloaded_awards

    awards = [award for award in preloaded_awards[target] if
            (compare_with_wildcards(award[config.AWARD_DESCRIPTION_COL],
                                    description) if description is not None else True) and
            (award[config.AWARD_TYPE_COL] == type if type is not None else True) and
            (award[config.AWARD_INSTANCE_COL] == instance if instance is not None else True) and
            (award[config.AWARD_REWARD_COL] == reward if reward is not None else True)]
    nr_awards = len(awards)

    if nr_awards > 1:
        raise Exception("Couldn't get preloaded award: more than one award found.")

    elif nr_awards == 0:
        return None

    else:
        return awards[0]

def get_preloaded_awards(target, description=None, type=None, instance=None, reward=None):
    global preloaded_awards

    return [award for award in preloaded_awards[target] if
              (compare_with_wildcards(award[config.AWARD_DESCRIPTION_COL], description) if description is not None else True) and
              (award[config.AWARD_TYPE_COL] == type if type is not None else True) and
              (award[config.AWARD_INSTANCE_COL] == instance if instance is not None else True) and
              (award[config.AWARD_REWARD_COL] == reward if reward is not None else True)]

def add_preloaded_award(target, award, index=None):
    global preloaded_awards

    if index is None:
        preloaded_awards[target].append(award)
    else:
        preloaded_awards[target].insert(index, award)

def remove_preloaded_award(target, index):
    global preloaded_awards

    preloaded_awards[target] = preloaded_awards[target][:index] + preloaded_awards[target][index + 1:]

def update_preloaded_award(target, index, award):
    add_preloaded_award(target, index, award)
    remove_preloaded_award(target, index + 1)

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

    # Clear skill progression, if skills enabled
    if module_enabled("Skills"):
        clear_skill_progression(target)

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

def clear_skill_progression(target):
    """
    Clears all skill progression for a given target
    before calculating again.

    Needs to be refreshed everytime the Rule System runs.
    """

    query = "DELETE FROM skill_progression WHERE course = %s AND user = %s;"
    db.execute_query(query, (config.COURSE, target), "commit")

def clear_streak_progression(target):
    """
    Clears all streak progression for a given target
    before calculating again.

    Needs to be refreshed everytime the Rule System runs.
    """

    query = "DELETE FROM streak_progression WHERE course = %s and user = %s;"
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
    add_preloaded_award(target, (None, target, config.COURSE, description, type, instance, reward, None))

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

    return get_preloaded_award(target, description, type) is not None

### Calculating rewards

def calculate_reward(target, type, reward, old_reward=0):
    """
    Calculates reward for a given target based on max. values.
    """

    def calculate_by_type(type):
        return type == "badge" or type == "skill" or type == "streak"

    def get_max_xp():
        query = "SELECT maxXP FROM xp_config WHERE course = %s;" % config.COURSE
        result = db.data_broker.get(db, config.COURSE, query)[0][0]
        return int(result) if result else None

    def get_max_xp_by_type(type):
        query = "SELECT maxXP FROM %s WHERE course = %s;" % (type + "s_config", config.COURSE)
        result = db.data_broker.get(db, config.COURSE, query)[0][0]
        return int(result) if result else None

    if module_enabled("XPLevels"):
        # Get max XP info
        max_xp = get_max_xp()
        max_xp_for_type = get_max_xp_by_type(type) if calculate_by_type(type) else None

        # Calculate reward
        if max_xp is not None:
            reward = max(min(max_xp - (get_total_reward(target) - old_reward), reward), 0)

        if max_xp_for_type is not None:
            reward = max(min(max_xp_for_type - (get_total_reward(target, type) - old_reward), reward), 0)

        return reward

    return 0

def calculate_extra_credit_reward(target, reward, type, instance):
    """
    Calculates reward of a certain type for a given target
    based on max. extra credit values.
    """

    def get_max_extra_credit():
        query = "SELECT maxExtraCredit FROM xp_config WHERE course = %s;" % config.COURSE
        result = db.data_broker.get(db, config.COURSE, query)[0][0]
        return int(result) if result else None

    def get_max_extra_credit_by_type(type):
        query = "SELECT maxExtraCredit FROM %s WHERE course = %s;" % (type + "s_config", config.COURSE)
        result = db.data_broker.get(db, config.COURSE, query)[0][0]
        return int(result) if result else None

    def get_target_extra_credit(target, type, instance):
        awards_table = get_awards_table()
        extra_credit = {"total": 0}

        # Get badges extra credit
        if module_enabled("Badges"):
            query = "SELECT IFNULL(SUM(a.reward), 0) " \
                    "FROM " + awards_table + " a LEFT JOIN badge b on a.moduleInstance = b.id " \
                                             "WHERE a.course = %s AND a.user = %s AND a.type = %s AND a.moduleInstance != %s AND b.isExtra = 1;"
            badges_total = int(db.execute_query(query, (config.COURSE, target, type, instance))[0][0])
            extra_credit["badges"] = badges_total
            extra_credit["total"] += badges_total

        # Get skills extra credit
        if module_enabled("Skills"):
            query = "SELECT IFNULL(SUM(a.reward), 0) " \
                    "FROM " + awards_table + " a LEFT JOIN skill s on a.moduleInstance = s.id " \
                                             "WHERE a.course = %s AND a.user = %s AND a.type = %s AND a.moduleInstance != %s AND s.isExtra = 1;"
            skills_total = int(db.execute_query(query, (config.COURSE, target, type, instance))[0][0])
            extra_credit["skills"] = skills_total
            extra_credit["total"] += skills_total

        # Get streaks extra credit
        if module_enabled("Streaks"):
            query = "SELECT IFNULL(SUM(a.reward), 0) " \
                    "FROM " + awards_table + " a LEFT JOIN streak s on a.moduleInstance = s.id " \
                                             "WHERE a.course = %s AND a.user = %s AND a.type = %s AND a.moduleInstance != %s AND s.isExtra = 1;"
            streaks_total = int(db.execute_query(query, (config.COURSE, target, type, instance))[0][0])
            extra_credit["streaks"] = streaks_total
            extra_credit["total"] += streaks_total

        return extra_credit

    if module_enabled("XPLevels"):
        # Get max extra credit
        max_extra_credit = get_max_extra_credit()
        max_extra_credit_for_type = get_max_extra_credit_by_type(type)
        target_extra_credit = get_target_extra_credit(target, type, instance) if \
            (max_extra_credit is not None or max_extra_credit_for_type is not None) else None

        # Calculate reward
        if max_extra_credit is not None:
            reward = max(min(max_extra_credit - target_extra_credit["total"], reward), 0)

        if max_extra_credit_for_type is not None:
            reward = max(min(max_extra_credit_for_type - target_extra_credit[type + "s"], reward), 0)

        return reward

    return 0


### Calculating grade

def calculate_grade(target):
    """
    Calculates grade for a given target based on awards received.
    """

    # Calculates total XP, if XP enabled
    if module_enabled("XPLevels"):
        calculate_xp(target)

        # Calculates total team XP, if teams enabled
        if module_enabled("Teams"):
            calculate_team_xp(target)

    # Calculates total tokens, if Virtual Currency enabled
    if module_enabled("VirtualCurrency"):
        calculate_tokens(target)

def calculate_xp(target):
    """
    Calculates total XP for a given target based on awards received.
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
    Calculates total XP for a given target's team based on awards received.
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

def calculate_tokens(target):
    """
    Calculates total tokens for a given target based on awards received
    and spending performed.
    """

    # Calculate total tokens received
    total_received = get_total_tokens_reward(target)

    # Calculate total tokens spent
    query = "SELECT IFNULL(SUM(amount), 0) FROM virtual_currency_spending WHERE course = %s AND user = %s;"
    total_spent = int(db.execute_query(query, (config.COURSE, target))[0][0])

    # Update target wallet
    query = "UPDATE user_wallet SET tokens = %s WHERE course = %s AND user = %s;"
    db.execute_query(query, (total_received - total_spent, config.COURSE, target), "commit")


### Utils

def course_exists(course):
    """
    Checks whether a given course exists and is active.
    """

    query = "SELECT isActive FROM course WHERE id = %s;" % course
    table = db.data_broker.get(db, course, query)

    return table[0][0] if len(table) == 1 else False

def get_course_dates(course):
    """
    Gets a given course's start and end dates.
    """

    query = "SELECT startDate, endDate FROM course WHERE id = %s;" % course
    return [date for date in db.data_broker.get(db, course, query)[0]]

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

def get_dates_of_period(date, number, time, precise=True):
    """
    Gets start and end dates for a given date and time period.
    When precise, it will calculate start and end dates from precisely the given date.
    Otherwise, will go back to the closest time.

    Examples for not precise:
        > date='2023-01-19 12:34:45'; number=1; time='second' -> start='2023-01-19 12:34:45'; end='2023-01-19 12:34:46'
        > date='2023-01-19 12:34:45'; number=1; time='minute' -> start='2023-01-19 12:34:00'; end='2023-01-19 12:35:00'
        > date='2023-01-19 12:34:45'; number=1; time='hour' -> start='2023-01-19 12:00:00'; end='2023-01-19 13:00:00'
        > date='2023-01-19 12:34:45'; number=1; time='day' -> start='2023-01-19 00:00:00'; end='2023-01-20 00:00:00'
        > date='2023-01-19 12:34:45'; number=1; time='week' -> start='2023-01-16 00:00:00'; end='2023-01-23 00:00:00'
        > date='2023-01-19 12:34:45'; number=1; time='month' -> start='2023-01-01 00:00:00'; end='2023-02-01 00:00:00'
        > date='2023-01-19 12:34:45'; number=1; time='year' -> start='2023-01-01 00:00:00'; end='2024-01-01 00:00:00'
        > date='2023-01-19 12:34:45'; number=2; time='week' -> start='2023-01-16 00:00:00'; end='2023-01-30 00:00:00'
    """

    start = None
    end = None

    if time == "second":
        start = date
        end = date + timedelta(seconds=number)

    elif time == "minute":
        start = date if precise else date - timedelta(seconds=date.second)
        end = start + timedelta(minutes=number)

    elif time == "hour":
        start = date if precise else date - timedelta(seconds=date.second, minutes=date.minute)
        end = start + timedelta(hours=number)

    elif time == "day":
        start = date if precise else date - timedelta(seconds=date.second, minutes=date.minute, hours=date.hour)
        end = start + timedelta(days=number)

    elif time == "week":
        start = date if precise else date - timedelta(seconds=date.second, minutes=date.minute, hours=date.hour, days=date.weekday())
        end = start + timedelta(weeks=number)

    elif time == "month":
        start = date if precise else date - timedelta(seconds=date.second, minutes=date.minute, hours=date.hour, days=date.day - 1)
        end = (start + timedelta(days=number*32)).replace(day=start.day) if precise else (start.replace(day=1) + timedelta(days=number*32)).replace(day=1)

    elif time == "year":
        start = date if precise else datetime(date.year, 1, 1)
        end = (start + timedelta(days=366)).replace(day=start.day, month=start.month) if precise else datetime(date.year + number, 1, 1)

    return start, end

def compare_with_wildcards(text_1, text_2):
    return fnmatch.fnmatch(text_1, text_2.replace("%", "*"))

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
    global preloaded_logs

    if target is not None:
        logs = preloaded_logs[target]
    else:
        logs = [item for sublist in preloaded_logs.values() for item in sublist]

    if type is not None:
        logs = [log for log in logs if log[config.LOG_TYPE_COL] == type]

    if rating is not None:
        logs = [log for log in logs if int(log[config.LOG_RATING_COL]) == type]

    if evaluator is not None:
        logs = [log for log in logs if int(log[config.LOG_EVALUATOR_COL]) == type]

    if start_date is not None:
        logs = [log for log in logs if log[config.LOG_DATE_COL] >= start_date]

    if end_date is not None:
        logs = [log for log in logs if log[config.LOG_DATE_COL] <= end_date]

    if description is not None:
        logs = [log for log in logs if compare_with_wildcards(log[config.LOG_DESCRIPTION_COL], description)]

    return logs

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

def get_participation_lecture_logs(target, lecture_nr=None):
    """
    Gets all lecture participation logs for a specific target.

    Option to get a specific participation by lecture number.
    """

    return get_logs(target, "participated in lecture", None, None, None, None, lecture_nr)

def get_participation_invited_lecture_logs(target, lecture_nr=None):
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

def get_skill_tier_logs(target, tier, only_min_rating=True):
    """
    Gets skill tier logs for a specific target.
    """

    if module_enabled("Skills"):
        type = "skill"
        table = get_awards_table()

        # Get min. rating
        query = "SELECT minRating FROM skills_config WHERE course = %s;" % config.COURSE
        min_rating = int(db.data_broker.get(db, config.COURSE, query)[0][0])

        # Get logs
        query = "SELECT p.* " \
                "FROM " + table + " a LEFT JOIN skill s on a.moduleInstance = s.id " \
                "LEFT JOIN skill_tier t on s.tier = t.id " \
                "LEFT JOIN skill_progression sp on sp.skill = s.id " \
                "LEFT JOIN participation p on sp.participation = p.id " \
                "WHERE a.course = %s AND a.user = %s AND a.type = %s AND t.position = %s"
        if only_min_rating:
            query += " AND p.rating >= %s" % min_rating
        query += " ORDER BY p.date ASC;"
        return db.execute_query(query, (config.COURSE, target, type, tier - 1))

    return []

def get_url_view_logs(target, name=None):
    """
    Gets all URL view logs for a specific target.

    Option to get a specific URL view by name.
    """

    return get_logs(target, "url viewed", None, None, None, None, name)


### Getting consecutive & periodic logs

def get_consecutive_peergrading_logs(target):
    """
    Gets consecutive peergrading logs done by target.
    """

    if module_enabled("Moodle"):
        from gamerules.connector.db_connector import moodle_db
    else:
        raise Exception("Can't get consecutive peergrading logs: Moodle is not enabled.")

    # Get target username
    # NOTE: target GC username = target Moodle username
    query = "SELECT username FROM auth WHERE user = %s;" % target
    username = (db.data_broker.get(db, target, query, "user")[0][0]).decode()

    # Get Moodle info
    query = "SELECT tablesPrefix, moodleCourse FROM moodle_config WHERE course = %s;" % config.COURSE
    mdl_prefix, mdl_course = db.data_broker.get(db, config.COURSE, query)[0]
    mdl_prefix = mdl_prefix.decode()

    # Get peergrading assigned to target
    query = "SELECT pa.expired, pa.timemodified " \
            "FROM " + mdl_prefix + "peerforum_time_assigned pa JOIN " + mdl_prefix + "peerforum_posts fp on pa.itemid=fp.id " \
            "JOIN " + mdl_prefix + "peerforum_discussions fd on fd.id=fp.discussion " \
            "JOIN " + mdl_prefix + "peerforum f on f.id=fd.peerforum " \
            "JOIN " + mdl_prefix + "user u on pa.userid=u.id " \
            "WHERE f.course = %s AND u.username = %s " \
            "ORDER BY pa.timeassigned;"
    logs = moodle_db.execute_query(query, (mdl_course, username))

    # Get consecutive peergrading logs
    consecutive_logs = []
    last_peergrading = None

    for log in logs:
        expired = int(log[0])
        date = log[1]
        peergraded = not expired

        if not peergraded:
            last_peergrading = None
            continue

        log = (None, target, config.COURSE, None, None, None, None, date, None, None)
        if last_peergrading is not None:
            consecutive_logs[-1].append(log)
        else:
            consecutive_logs.append([log])
        last_peergrading = peergraded

    return consecutive_logs

def get_periodic_logs(logs, number, time, type):
    """
    Gets periodic logs on a set of logs.

    There are two options for periodicity:
        > absolute -> check periodicity in equal periods,
                    beginning at course start date until end date.

        > relative -> check periodicity starting on the
                    first entry for streak
    """

    if len(logs) == 0:
        return []

    if type == "absolute":
        course_start_date, course_end_date = get_course_dates(config.COURSE)
        start_date, end_date = get_dates_of_period(course_start_date, number, time, False)

    else:
        start_date, end_date = get_dates_of_period(logs[0][config.LOG_DATE_COL], number, time)

    periodic_logs = []

    for log in logs:
        date = log[config.LOG_DATE_COL]
        if start_date <= date <= end_date:
            nr_periods = len(periodic_logs)
            if nr_periods == 0:
                periodic_logs.append([log])
            else:
                periodic_logs[nr_periods - 1].append(log)

        else:
            if type == "absolute":
                period = end_date - start_date
                start_date = end_date
                end_date += period

                if not (start_date <= date <= end_date):
                    periodic_logs.append([])
                    start_date, end_date = get_dates_of_period(date, number, time, False)

            periodic_logs.append([log])

        if type == "relative":
            start_date, end_date = get_dates_of_period(log[config.LOG_DATE_COL], number, time)

    return periodic_logs


### Getting total reward

def get_total_reward(target, type=None):
    """
    Gets total reward for a given target of a specific type.
    """

    global preloaded_awards
    total_reward = 0
    for award in get_preloaded_awards(target, None, type):
        total_reward += int(award[config.AWARD_REWARD_COL])
    return total_reward

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

def award(target, type, description, reward, instance=None, unique=True):
    """
    Awards a single prize to a specific target.

    NOTE: will not retract, but will not award twice if unique.
    Updates award if reward has changed.
    """

    global preloaded_awards
    awards_given = get_preloaded_awards(target, description, type, instance)
    nr_awards_given = len(awards_given)

    if unique and nr_awards_given > 1:
        logging.warning("Award '%s' has been awarded more than once for target with ID = %s." % (description, target))
        return

    if nr_awards_given == 0 or not unique:  # Award
        reward = calculate_reward(target, type, reward)
        give_award(target, type, description, reward, instance)

    elif unique:  # Update award, if changed
        old_reward = int(awards_given[0][config.AWARD_REWARD_COL])
        reward = calculate_reward(target, type, reward, old_reward)
        if reward != old_reward:
            query = "UPDATE " + get_awards_table() + " SET reward = %s WHERE course = %s AND user = %s AND type = %s AND description = %s;"
            db.execute_query(query, (reward, config.COURSE, target, type, description), "commit")

            award = get_preloaded_award(target, description, type)
            update_preloaded_award(target, awards_given.index(award), (None, target, config.COURSE, description, type, instance, reward, award[config.AWARD_DATE_COL]))

def award_assignment_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards assignment grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per assignment
     > max_grade ==> max. grade per assignment
    """

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL]
        reward = (int(log[config.LOG_RATING_COL]) / max_grade) * max_xp
        award(target, "assignment", name, reward)

def award_badge(target, name, lvl, logs, progress=None):
    """
    Awards a given level to a specific target.

    NOTE: will retract if level changed.
    Updates award if reward has changed.
    """

    def get_description(name, lvl):
        lvl_info = " (level %s)" % lvl
        return name + lvl_info

    global preloaded_awards
    type = "badge"
    awards_table = get_awards_table()

    if module_enabled("Badges"):
        # Get badge info
        query = "SELECT bl.badge, bl.number, bl.reward, bl.tokens, b.isExtra, bl.goal, b.description, bl.description " \
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
        badge_awards = get_preloaded_awards(target, name + "%", type)
        nr_awards = len(badge_awards)

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
                remove_preloaded_award(target, badge_awards.index(get_preloaded_award(target, description, type, badge_id)))

                # Remove tokens
                query = "DELETE FROM " + awards_table + " WHERE course = %s AND user = %s AND type = 'tokens' AND description = %s AND moduleInstance = %s;"
                db.execute_query(query, (config.COURSE, target, description, badge_id), "commit")

        # Award and/or update badge levels
        for level in range(1, lvl + 1):
            # Calculate reward
            is_extra = table_badge[level - 1][4]
            badge_reward = int(table_badge[level - 1][2])
            reward = calculate_extra_credit_reward(target, badge_reward, type, badge_id) if is_extra else badge_reward

            # Award badge
            description = get_description(name, level)
            award(target, type, description, reward, badge_id)

            # Award tokens
            badge_tokens = int(table_badge[level - 1][3])
            if module_enabled("VirtualCurrency") and badge_tokens > 0:
                award_tokens(target, description, badge_tokens, 1, badge_id)

            # Notification
            if progress:
                goal = 0
                badge_description = ""
                level_description = ""
                # Get goal and description of specific level award
                for i in range(0, len(table_badge)):
                    if table_badge[i][1] == lvl:
                        goal = int(table_badge[i][5])
                        badge_description = table_badge[i][6]        # e.g. Show up for theoretical lectures!
                        level_description = table_badge[i][7]        # e.g. be there for 50% of lectures
                        break

                # Check if give notification
                instances = goal - progress

                # threshold to limit notifications and avoid spamming
                if 1 < instances <= 2:
                    message = "You are " + instances + " events away from achieving " + name + " badge! : " \
                            + badge_description + " - " + level_description

                    query = "INSERT INTO notification (course, user, message, isShowed) VALUES (%s, %s, %s,%s);"
                    db.execute_query(query, (config.COURSE, target, message, 0), "commit")

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
        lab_nr = int(log[config.LOG_DESCRIPTION_COL])
        name = "Lab %s" % lab_nr
        reward = (int(log[config.LOG_RATING_COL]) / max_grade) * max_xp
        award(target, "labs", name, reward, lab_nr)

def award_post_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards post grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per post
     > max_grade ==> max. grade per post
    """

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL].split(",")[0]
        reward = (int(log[config.LOG_RATING_COL]) / max_grade) * max_xp
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
        name = log[config.LOG_DESCRIPTION_COL]
        reward = (int(log[config.LOG_RATING_COL]) / max_grade) * max_xp
        award(target, "quiz", name, reward)

def award_skill(target, name, rating, logs, dependencies=True, use_wildcard=False):
    """
    Awards a given skill to a specific target.
    Option to spend a wildcard to give award.

    NOTE: will retract if rating changed.
    Updates award if reward has changed.
    """

    def get_attempt_cost(tier_cost_info, attempt_nr):
        if attempt_nr < 1:
            raise Exception("User with ID = %s attempt number at skill '%s' needs to be bigger than zero." % (target, name))

        if tier_cost_info["costType"] == "fixed":
            return tier_cost_info["cost"]

        else:
            attempt_logs = logs[0: attempt_nr - 1]
            nr_attempts = len([log for log in attempt_logs if int(log[config.LOG_RATING_COL]) >= tier_cost_info["minRating"]])
            return tier_cost_info["cost"] + tier_cost_info["increment"] * nr_attempts

    def get_attempt_description(attempt):
        attempt_info = " (%s%s attempt)" % (attempt, "st" if attempt == 1 else "nd" if attempt == 2 else "rd" if attempt == 3 else "th")
        return name + attempt_info

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

    def calculate_skill_tree_reward(target, reward, skill_tree_max_reward):
        global preloaded_awards

        target_skill_tree_reward = 0
        for award in get_preloaded_awards(target, None, type):
            if award[config.AWARD_INSTANCE_COL != skill_id]:
                target_skill_tree_reward += award[config.AWARD_REWARD_COL]
        return max(min(skill_tree_max_reward - target_skill_tree_reward, reward), 0)

    global preloaded_awards
    type = "skill"
    awards_table = get_awards_table()

    if module_enabled("Skills"):
        # Get min. rating
        query = "SELECT minRating FROM skills_config WHERE course = %s;" % config.COURSE
        min_rating = int(db.data_broker.get(db, config.COURSE, query)[0][0])

        # Get skill info
        query = "SELECT s.id, t.reward, s.isExtra, st.maxReward, tc.costType, tc.cost, tc.increment, tc.minRating " \
                "FROM skill s LEFT JOIN skill_tier t on s.tier = t.id " \
                "LEFT JOIN skill_tier_cost tc on tc.tier = t.id " \
                "LEFT JOIN skill_tree st on t.skillTree = st.id " \
                "WHERE s.course = %s AND s.name = '%s';" % (config.COURSE, name)
        table_skill = db.data_broker.get(db, config.COURSE, query)[0]
        skill_id = table_skill[0]

        # Update skill progression
        nr_attempts = len(logs)
        if not config.TEST_MODE:
            for log in logs:
                query = "INSERT INTO skill_progression (course, user, skill, participation) VALUES (%s, %s, %s, %s);"
                db.execute_query(query, (config.COURSE, target, skill_id, log[0]), "commit")

        # Rating is not enough to win the award or dependencies haven't been met
        if rating < min_rating or not dependencies:
            # Get awards given for skill
            skill_awards = get_preloaded_awards(target, name, type)
            nr_awards = len(skill_awards)

            # Delete invalid skill
            if nr_awards > 0:
                # Delete award
                query = "DELETE FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
                db.execute_query(query, (config.COURSE, target, type, name, skill_id), "commit")
                remove_preloaded_award(target, skill_awards.index(get_preloaded_award(target, name, type, skill_id)))

                # Give tokens back
                if module_enabled("VirtualCurrency"):
                    table = "virtual_currency_spending"

                    # Get date from which to give back
                    query = "SELECT date FROM " + table + " WHERE course = %s AND user = %s AND description LIKE %s " \
                            "ORDER BY date ASC;"
                    spending_table = db.execute_query(query, (config.COURSE, target, name + " (% attempt)"))
                    delete_from = spending_table[nr_attempts][0]

                    # Return tokens
                    query = "DELETE FROM " + table + " WHERE course = %s AND user = %s AND date >= %s;"
                    db.execute_query(query, (config.COURSE, target, delete_from), "commit")

        else:
            # Calculate reward
            is_extra = table_skill[2]
            skill_tree_max_reward = int(table_skill[3])
            skill_reward = int(table_skill[1])
            reward = calculate_extra_credit_reward(target, skill_reward, type, skill_id) if is_extra else skill_reward
            reward = calculate_skill_tree_reward(target, reward, skill_tree_max_reward)

            # Award skill
            award(target, type, name, reward, skill_id)
            query = "SELECT id FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
            award_id = db.execute_query(query, (config.COURSE, target, type, name, skill_id))[0][0]

            # Spend wildcard
            spend_wildcard(award_id, use_wildcard)

        # Spend tokens, if virtual currency enabled
        if module_enabled("VirtualCurrency") and nr_attempts > 0 and dependencies:
            tier_cost_info = {"costType": table_skill[4], "cost": int(table_skill[5]), "increment": int(table_skill[6]),
                              "minRating": int(table_skill[7])}
            for attempt in range(1, nr_attempts + 1):
                attempt_cost = get_attempt_cost(tier_cost_info, attempt)
                if attempt_cost > 0:
                    description = get_attempt_description(attempt)
                    spend_tokens(target, description, attempt_cost, 1)

        # Check if rating is enough to win the award, but dependencies are missing (create notification)
        if rating >= min_rating and not dependencies:
            query = "SELECT s.name " \
                    "FROM skill s JOIN skill_dependency sd JOIN skill_dependency_combo sdc " \
                    "ON s.id = sd.skill AND sd.id = sdc.dependency " \
                    "WHERE sdc.skill = %s;" % skill_id
            dependencies_names = db.data_broker.get(db, config.COURSE, query)

            # Removes duplicates
            dependencies_names_unique = list(set([el[0].decode() for el in dependencies_names]))

            # Transform array into string with commas
            dependencies_names_string = ','.join(dependencies_names_unique)

            message = "You can't be rewarded yet... Almost there! There are some dependencies missing: " \
                      + dependencies_names_string

            # Add notification to table
            query = "INSERT INTO notification (course, user, message, isShowed) VALUES (%s,%s,%s,%s);"
            db.execute_query(query, (config.COURSE, target, message, 0), "commit")

def award_streak(target, name, logs):
    """
    Awards a given streak to a specific target.

    NOTE: will retract if streak changed.
    Updates award if reward has changed.
    """

    def get_description(name, repetition):
        repetition_info = " (%s%s time)" % (repetition, "st" if repetition == 1 else "nd" if repetition == 2 else "rd" if repetition == 3 else "th")
        return name + repetition_info

    def get_deadline(last_date, period_type, period_number, period_time):
        if not is_periodic or not last_date:
            return None

        course_end_date = get_course_dates(config.COURSE)[1]
        deadline = get_dates_of_period(last_date, period_number, period_time, not period_type == "absolute")[1]
        return course_end_date if deadline >= course_end_date else deadline

    global preloaded_awards
    type = "streak"
    awards_table = get_awards_table()

    if module_enabled("Streaks"):
        # Get streak info
        query = "SELECT id, goal, periodicityGoal, periodicityNumber, periodicityTime, periodicityType, reward, tokens, isExtra, isRepeatable " \
                "FROM streak WHERE course = %s AND name = '%s';" % (config.COURSE, name)
        table_streak = db.data_broker.get(db, config.COURSE, query)
        streak_id = table_streak[0][0]
        goal = int(table_streak[0][1])
        period_number = int(table_streak[0][3]) if table_streak[0][3] is not None else None
        period_time = table_streak[0][4].decode() if table_streak[0][4] is not None else None
        is_periodic = period_number is not None and period_time is not None
        period_type = table_streak[0][5].decode() if table_streak[0][5] is not None else None
        period_goal = int(table_streak[0][2]) if table_streak[0][2] is not None else None

        # Get awards given for streak
        streak_awards = get_preloaded_awards(target, name + "%", type)
        nr_awards = len(streak_awards)

        # No streaks reached and there are no awards to be removed
        # Simply return right away
        nr_groups = len(logs)
        if nr_groups == 0 and nr_awards == 0:
            return

        # Get target progression in streak
        progression = []
        for group_index in range(0, nr_groups):
            group = logs[group_index]
            last = group_index == nr_groups - 1
            total = len(group)

            nr_valid = 0
            if is_periodic and period_type == "absolute":   # periodic (absolute)
                if total >= period_goal or (last and total > 0 and get_deadline(group[-1][config.LOG_DATE_COL], period_type, period_number, period_time) > datetime.now()):
                    nr_valid = total
                else:
                    progression = []

            else:   # consecutive & periodic (relative)
                nr_valid = math.floor(total / goal) * goal
                if last and total > 0 and nr_valid < total:
                    last_date = group[-1][config.LOG_DATE_COL]
                    deadline = get_deadline(last_date, period_type, period_number, period_time)
                    if deadline is None or deadline > datetime.now():
                        nr_valid = total

            for index in range(0, nr_valid):
                progression.append(group[index])

        # If not repeatable, only allow one repetition of streak
        is_repeatable = table_streak[0][9]
        if not is_repeatable:
            progression = progression[:goal]

        # Update streak progression
        steps = len(progression)
        if not config.TEST_MODE:
            for index in range(0, steps):
                log = progression[index]
                repetition = math.floor(index / goal + 1)

                query = "INSERT INTO streak_progression (course, user, streak, repetition, participation) VALUES (%s, %s, %s, %s, %s);"
                db.execute_query(query, (config.COURSE, target, streak_id, repetition, log[0]), "commit")

        # Update streak deadline for target
        if is_periodic:
            if steps == 0:
                last_date = datetime.now() if period_type == "absolute" else None
            else:
                last_date = progression[-1][config.LOG_DATE_COL]

            query = "SELECT deadline FROM streak_deadline WHERE course = %s AND user = %s AND streak = %s;"
            old_deadline = db.execute_query(query, (config.COURSE, target, streak_id))
            new_deadline = get_deadline(last_date, period_type, period_number, period_time)

            if not old_deadline and new_deadline:
                query = "INSERT INTO streak_deadline (course, user, streak, deadline) VALUES (%s, %s, %s, %s);"
                db.execute_query(query, (config.COURSE, target, streak_id, new_deadline), "commit")

            elif old_deadline and new_deadline and new_deadline != old_deadline:
                query = "UPDATE streak_deadline SET deadline = %s WHERE course = %s AND user = %s AND streak = %s;"
                db.execute_query(query, (new_deadline, config.COURSE, target, streak_id), "commit")

            elif old_deadline and not new_deadline:
                query = "DELETE FROM streak_deadline WHERE course = %s AND user = %s and streak = %s;"
                db.execute_query(query, (config.COURSE, target, streak_id), "commit")

        # Award and/or update streaks
        nr_repetitions = math.floor(steps / goal)
        for repetition in range(1, nr_repetitions + 1):
            # Calculate reward
            is_extra = table_streak[0][8]
            streak_reward = int(table_streak[0][6])
            reward = calculate_extra_credit_reward(target, streak_reward, type, streak_id) if is_extra else streak_reward

            # Award streak
            description = get_description(name, repetition)
            award(target, type, description, reward, streak_id)

            # Award tokens
            streak_tokens = int(table_streak[0][7])
            if module_enabled("VirtualCurrency") and streak_tokens > 0:
                award_tokens(target, description, streak_tokens, 1, streak_id)

        # The rule/data sources have been updated, the 'award' table
        # has streaks attributed which are no longer valid.
        # All streaks no longer valid must be deleted
        if nr_awards > nr_repetitions:
            for repetition in range(nr_repetitions + 1, nr_awards + 1):
                # Delete award
                description = get_description(name, repetition)
                query = "DELETE FROM " + awards_table + " WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
                db.execute_query(query, (config.COURSE, target, type, description, streak_id), "commit")
                remove_preloaded_award(target, streak_awards.index(get_preloaded_award(target, description, type, streak_id)))

                # Remove tokens
                query = "DELETE FROM " + awards_table + " WHERE course = %s AND user = %s AND type = 'tokens' AND description = %s AND moduleInstance = %s;"
                db.execute_query(query, (config.COURSE, target, description, streak_id), "commit")

def award_tokens(target, name, reward, repetitions=1, instance=None):
    """
    Awards given tokens to a specific target.
    """

    # Get awards already given
    awards_table = get_awards_table()
    query = "SELECT COUNT(*) FROM " + awards_table + " WHERE course = %s AND user = %s AND description = %s AND type = 'tokens'"
    if instance is not None:
        query += " AND moduleInstance = %s" % instance
    query += ";"
    awards_given = int(db.execute_query(query, (config.COURSE, target, name))[0][0])

    # Give awards missing
    for diff in range(awards_given, repetitions):
        award(target, "tokens", name, reward, instance, repetitions == 1)


### Spend items

def spend_tokens(target, name, amount, repetitions=1):
    """
    Spends a single item of a specific target.

    NOTE: will not retract, but will not spend twice if is unique.
    Updates if amount has changed.
    """

    def spend(target, description, amount, unique=True):
        if unique and spending_done > 1:
            logging.warning("Spending '%s' has been performed more than once for target with ID = %s." % (description, target))
            return

        if spending_done == 0 or not unique:  # Spend
            query = "INSERT INTO " + table + " (user, course, description, amount) VALUES (%s, %s, %s, %s);"
            db.execute_query(query, (target, config.COURSE, name, amount), "commit")

        elif unique:  # Update spending, if changed
            old_amount = int(spending_table[0][1])
            if amount != old_amount:
                query = "UPDATE " + table + " SET amount = %s WHERE course = %s AND user = %s AND description = %s;"
                db.execute_query(query, (amount, config.COURSE, target, name), "commit")

    table = "virtual_currency_spending"

    # Get spending already given
    query = "SELECT COUNT(*), amount FROM " + table + " WHERE course = %s AND user = %s AND description = %s;"
    spending_table = db.execute_query(query, (config.COURSE, target, name))
    spending_done = int(spending_table[0][0])

    # Perform spending missing
    for diff in range(spending_done, repetitions):
        spend(target, name, amount, repetitions == 1)


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
