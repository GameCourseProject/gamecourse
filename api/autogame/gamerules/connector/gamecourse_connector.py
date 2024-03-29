#!/usr/bin/env python3
import socket, logging
import math
from datetime import datetime, timedelta
import fnmatch

from io import StringIO
from course.logline import *
import config

from gamerules.connector.db_connector import gc_db


### ------------------------------------------------------ ###
###	------------------ PRIVATE functions ----------------- ###
### -------------- (not accessible in rules) ------------- ###
### ------------------------------------------------------ ###

### AutoGame

def autogame_init(course):
    """
    Checks AutoGame status for a given course and initializes it.
    """

    query = "SELECT isRunning FROM autogame WHERE course = %s;"
    is_running = gc_db.execute_query(query, (course,))[0][0]

    # Check AutoGame status
    if is_running:
        raise Exception("AutoGame is already running for this course.")

    # Initialize AutoGame
    query = "UPDATE autogame SET isRunning = 1, runNext = 0 WHERE course = %s;"
    gc_db.execute_query(query, (course,), "commit")

def autogame_terminate(course, start_date=None, finish_date=None):
    """
    Finishes execution of AutoGame and notifies server to
    close the socket.
    """

    # Terminate AutoGame
    if not config.TEST_MODE:
        query = "UPDATE autogame SET isRunning = 0"
        if start_date is not None and finish_date is not None:
            query += ", startedRunning = '%s', finishedRunning = '%s'" % (start_date, finish_date)
        query += " WHERE course = %s;"
        gc_db.execute_query(query, (course,), "commit")

    # Check how many courses are running
    query = "SELECT COUNT(*) from autogame WHERE isRunning = %s AND course != %s;"
    courses_running = gc_db.execute_query(query, (True, 0))[0][0]

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

            except socket.timeout:
                raise Exception('Socket timeout — operation took longer than 3 minutes.')

            except KeyboardInterrupt:
                print("\nInterrupt: You pressed CTRL+C!")
                exit()

def get_targets(course, all_targets=False, targets_list=None):
    """
    Returns targets (students) to fire rules for.
    """

    if targets_list is not None:
        if len(targets_list) == 0:
            return {}

        # Running for certain targets
        query = "SELECT id, name, studentNumber FROM user WHERE id IN (%s);" % ", ".join(targets_list)
        table = gc_db.execute_query(query)

    else:
        if all_targets:
            # Running for all targets
            query = "SELECT u.id, u.name, u.studentNumber " \
                    "FROM user_role ur LEFT JOIN role r on ur.role = r.id " \
                    "LEFT JOIN course_user cu on ur.user = cu.id " \
                    "LEFT JOIN user u on ur.user = u.id " \
                    "WHERE ur.course = %s AND cu.isActive = 1 AND r.name = 'Student';"
            table = gc_db.execute_query(query, (course,))

        else:
            # Running for targets w/ new data in course
            query = "SELECT u.id, u.name, u.studentNumber " \
                    "FROM autogame_target at LEFT JOIN user_role ur ON at.target = ur.user " \
                    "LEFT JOIN role r ON ur.role = r.id " \
                    "LEFT JOIN course_user cu on ur.user = cu.id " \
                    "LEFT JOIN user u on ur.user = u.id " \
                    "WHERE at.course = %s AND cu.isActive = 1 AND r.name = 'Student';"
            table = gc_db.execute_query(query, (course,))

            # Clear targets
            query = "DELETE FROM autogame_target WHERE course = %s;"
            gc_db.execute_query(query, (course,), "commit")

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

                            logline = LogLine(ll_id, ll_user, ll_course, ll_desc, ll_type, ll_post, ll_date, ll_rating,
                                              ll_evaluator)
                            participations.append(logline)

                        result = participations
                        break

                # If data received is a value (int, float, str, etc.)
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


### Preload information

preloaded_logs = {}
preloaded_awards = {}
preloaded_spending = {}

def preload_info(targets_ids):
    """
    Preloads information from the database.
    Minimizes accesses to the database.
    """

    # Preload participations
    preload_logs(targets_ids)

    # Preload awards
    preload_awards(targets_ids)

    if module_enabled("VirtualCurrency"):
        # Preload tokens' spending
        preload_spending(targets_ids)

        # Filter skill logs whose cost couldn't be paid
        if module_enabled("Skills"):
            filter_preloaded_skill_logs(targets_ids)

def preload_logs(targets_ids):
    """
    Preloads logs for given targets.
    Ensures the database is accessed only once to retrieve logs.
    """

    global preloaded_logs

    query = "SELECT * FROM participation WHERE course = %s" % config.COURSE
    query += " AND (user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    query += " OR evaluator IN (%s))" % (', '.join([str(el) for el in targets_ids]))
    query += " ORDER BY date ASC;"
    logs = gc_db.execute_query(query)

    # Initialize preloaded logs
    preloaded_logs = {target_id: [] for target_id in targets_ids}

    # Organize logs by target
    for log in logs:
        log = (log[config.LOG_ID_COL],
               log[config.LOG_USER_COL],
               log[config.LOG_COURSE_COL],
               log[config.LOG_SOURCE_COL].decode(),
               log[config.LOG_DESCRIPTION_COL].decode(),
               log[config.LOG_TYPE_COL].decode(),
               log[config.LOG_POST_COL].decode() if log[config.LOG_POST_COL] is not None else None,
               log[config.LOG_DATE_COL],
               log[config.LOG_RATING_COL],
               log[config.LOG_EVALUATOR_COL])

        target_id = log[config.LOG_USER_COL]
        if target_id in preloaded_logs:
            preloaded_logs[target_id].append(log)
        else:
            preloaded_logs[target_id] = [log]

def preload_awards(targets_ids):
    """
    Preloads awards for given targets.
    Ensures the database is accessed only once to retrieve awards.
    """

    global preloaded_awards

    # Get awards for targets
    query = "SELECT * FROM " + get_awards_table() + " WHERE course = %s" % config.COURSE
    query += " AND user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    query += " ORDER BY date ASC;"
    awards = gc_db.execute_query(query)

    # Initialize preloaded awards
    preloaded_awards = {target_id: [] for target_id in targets_ids}

    # Organize awards by target
    for a in awards:
        a = (a[config.AWARD_ID_COL],
             a[config.AWARD_USER_COL],
             a[config.AWARD_COURSE_COL],
             a[config.AWARD_DESCRIPTION_COL].decode(),
             a[config.AWARD_TYPE_COL].decode(),
             a[config.AWARD_INSTANCE_COL],
             a[config.AWARD_REWARD_COL],
             a[config.AWARD_DATE_COL])

        target_id = a[config.AWARD_USER_COL]
        if target_id in preloaded_awards:
            preloaded_awards[target_id].append(a)
        else:
            preloaded_awards[target_id] = [a]

def preload_spending(targets_ids):
    """
    Preloads tokens' spending for given targets.
    Ensures the database is accessed only once to retrieve tokens' spending.
    """

    global preloaded_spending

    # Get spending for targets
    query = "SELECT * FROM virtual_currency_spending WHERE course = %s" % config.COURSE
    query += " AND user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    query += " ORDER BY date ASC;"
    spending = gc_db.execute_query(query)

    # Initialize preloaded spending
    preloaded_spending = {target_id: [] for target_id in targets_ids}

    # Organize spending by target
    for s in spending:
        s = (s[config.SPENDING_ID_COL],
             s[config.SPENDING_USER_COL],
             s[config.SPENDING_COURSE_COL],
             s[config.SPENDING_DESCRIPTION_COL].decode(),
             s[config.SPENDING_AMOUNT_COL],
             s[config.SPENDING_DATE_COL])

        target_id = s[config.SPENDING_USER_COL]
        if target_id in preloaded_spending:
            preloaded_spending[target_id].append(s)
        else:
            preloaded_spending[target_id] = [s]

def filter_preloaded_skill_logs(targets_ids):
    """
    Filters skill logs whose cost couldn't be paid

    FIXME: very much hard-coded to work for MCP 22/23
           where only skills deduct tokens
    """

    def get_skill_rule_order(s_name):
        q = "SELECT r.position FROM skill s JOIN rule r on s.rule = r.id WHERE s.course = %s AND s.name = '%s';" % (config.COURSE, s_name)
        return int(gc_db.data_broker.get(gc_db, config.COURSE, q)[0][0])

    def get_attempt_cost(attempt_nr):
        # Fixed cost
        if tier_cost_info["costType"] == "fixed":
            return tier_cost_info["cost"]

        # Incremental cost
        elif tier_cost_info["costType"] == "incremental":
            attempt_logs = logs[0: attempt_nr - 1]
            attempts = len([attempt_log for attempt_log in attempt_logs if
                            int(attempt_log[config.LOG_RATING_COL]) >= tier_cost_info["minRating"]])
            return tier_cost_info["cost"] + tier_cost_info["increment"] * attempts
        
        # Exponential cost
        else:
            attempt_logs = logs[0: attempt_nr - 1]
            attempts = len([attempt_log for attempt_log in attempt_logs if
                            int(attempt_log[config.LOG_RATING_COL]) >= tier_cost_info["minRating"]])
            if attempts > 0:
                return tier_cost_info["increment"] * (2 ** (attempts - 1))
            else:
             return tier_cost_info["cost"]

    global preloaded_logs

    for target in targets_ids:
        # Get total tokens received
        tokens_received = get_total_tokens_reward(target)

        # Get skill logs sorted by their running order in the AutoGame
        logs = get_skill_logs(target)
        logs.sort(key=lambda lg: get_skill_rule_order(lg[config.LOG_DESCRIPTION_COL].replace('Skill Tree, Re: ', '')))

        # Organize logs by skill
        logs_by_skill = {}
        for log in logs:
            skill_name = log[config.LOG_DESCRIPTION_COL].replace('Skill Tree, Re: ', '')
            if skill_name in logs_by_skill:
                logs_by_skill[skill_name].append(log)
            else:
                logs_by_skill[skill_name] = [log]

        # Filter logs which couldn't be paid
        for skill_name in logs_by_skill.keys():
            skill_logs = logs_by_skill[skill_name]

            # Get cost info
            query = "SELECT tc.costType, tc.cost, tc.increment, tc.minRating " \
                    "FROM skill s LEFT JOIN skill_tier t on s.tier = t.id " \
                    "LEFT JOIN skill_tier_cost tc on tc.tier = t.id " \
                    "LEFT JOIN skill_tree st on t.skillTree = st.id " \
                    "WHERE s.course = %s AND s.name = '%s';" % (config.COURSE, skill_name)
            table_skill = gc_db.data_broker.get(gc_db, config.COURSE, query)[0]
            tier_cost_info = {"costType": table_skill[0], "cost": int(table_skill[1]),
                              "increment": int(table_skill[2]), "minRating": int(table_skill[3])}
            costs = [get_attempt_cost(attempt) for attempt in range(1, len(skill_logs) + 1)]

            filtered_logs, tokens_received = filter_logs_by_cost(skill_logs, costs, tokens_received)
            logs_by_skill[skill_name] = filtered_logs

            # Send notification for each skill attempt that couldn't be paid
            for i in range(0, len(skill_logs)):
                skill_log = skill_logs[i]
                cant_pay = len([log for log in filtered_logs if log[config.LOG_ID_COL] == skill_log[config.LOG_ID_COL]]) == 0
                if cant_pay:
                    # Get VC name
                    query = "SELECT name FROM virtual_currency_config WHERE course = %s;" % config.COURSE
                    VC_name = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0].decode()

                    # Send notification, if not sent already
                    attempt_nr = i + 1
                    message = 'You don\'t have enough %s to pay for attempt #%s of skill \'%s\'. ' \
                              'This attempt won\'t count for your progress in the course until you have enough %s to pay for it.' \
                              % (VC_name, attempt_nr, skill_name, VC_name)

                    query = "SELECT COUNT(*) FROM notification WHERE course = %s AND user = %s AND message = %s;"
                    already_sent = int(gc_db.execute_query(query, (config.COURSE, target, message))[0][0]) > 0

                    if not already_sent:
                        query = "INSERT INTO notification (course, user, message, isShowed) VALUES (%s,%s,%s,%s);"
                        gc_db.execute_query(query, (config.COURSE, target, message, 0), "commit")

        # Update preloaded logs
        for log in logs:
            skill_name = log[config.LOG_DESCRIPTION_COL].replace('Skill Tree, Re: ', '')

            # Filter out from preloaded logs
            if log not in logs_by_skill[skill_name]:
                index = get_logs(target).index(log)
                preloaded_logs[target] = preloaded_logs[target][:index] + preloaded_logs[target][index + 1:]

        # Sort everything by date again
        preloaded_logs[target].sort(key=lambda lg: lg[config.LOG_DATE_COL])

### Progression

badge_progression = []
skill_progression = []
streak_progression = []

def clear_progression(targets_ids):
    """
    Clears all progression for given targets before
    calculating again.

    Needs to be refreshed everytime AutoGame runs
    rules for each type of item.
    """

    # Clear badge progression, if badges enabled
    if module_enabled("Badges"):
        clear_badge_progression(targets_ids)

    # Clear skill progression, if skills enabled
    if module_enabled("Skills"):
        clear_skill_progression(targets_ids)

    # Clear streak progression, if streaks enabled
    if module_enabled("Streaks"):
        clear_streak_progression(targets_ids)

def clear_badge_progression(targets_ids):
    """
    Clears all badge progression for given targets
    before calculating again.

    Needs to be refreshed everytime AutoGame runs
    rules for badges.
    """

    # Get badges with active rules
    query = "SELECT b.id FROM badge b JOIN rule r on b.rule = r.id " \
            "WHERE b.course = %s AND r.isActive = True;" % config.COURSE
    badges_ids = [item for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

    # Clear badge progression
    if len(badges_ids) > 0:
        query = "DELETE FROM badge_progression WHERE course = %s AND user IN (%s) AND badge IN (%s);" \
                % (config.COURSE, ', '.join([str(el) for el in targets_ids]), ', '.join([str(e) for e in badges_ids]))
        gc_db.execute_query(query, (), "commit")

def clear_skill_progression(targets_ids):
    """
    Clears all skill progression for given targets
    before calculating again.

    Needs to be refreshed everytime AutoGame runs
    rules for skills.
    """

    # Get skills with active rules
    query = "SELECT s.id FROM skill s JOIN rule r on s.rule = r.id " \
            "WHERE s.course = %s AND r.isActive = True;" % config.COURSE
    skills_ids = [item for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

    # Clear skill progression
    if len(skills_ids) > 0:
        query = "DELETE FROM skill_progression WHERE course = %s AND user IN (%s) AND skill IN (%s);" \
                % (config.COURSE, ', '.join([str(el) for el in targets_ids]), ', '.join([str(el) for el in skills_ids]))
        gc_db.execute_query(query, (), "commit")

def clear_streak_progression(targets_ids):
    """
    Clears all streak progression for given targets
    before calculating again.

    Needs to be refreshed everytime AutoGame runs
    rules for streaks.
    """

    # Get streaks with active rules
    query = "SELECT s.id FROM streak s JOIN rule r on s.rule = r.id " \
            "WHERE s.course = %s AND r.isActive = True;" % config.COURSE
    streaks_ids = [item for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

    # Clear streak progression
    if len(streaks_ids) > 0:
        query = "DELETE FROM streak_progression WHERE course = %s AND user IN (%s) AND streak IN (%s);" \
                % (config.COURSE, ', '.join([str(el) for el in targets_ids]), ', '.join([str(el) for el in streaks_ids]))
        gc_db.execute_query(query, (), "commit")

def update_progression():
    """
    Updates all progression in bulk.
    """

    if not config.TEST_MODE:
        # Update badge progression, if badges enabled
        if module_enabled("Badges"):
            update_badge_progression()

        # Update skill progression, if skills enabled
        if module_enabled("Skills"):
            update_skill_progression()

        # Update streak progression, if streaks enabled
        if module_enabled("Streaks"):
            update_streak_progression()

def update_badge_progression():
    """
    Updates all badge progression in bulk.
    """

    global badge_progression

    if len(badge_progression) > 0:
        query = "INSERT INTO badge_progression (course, user, badge, participation) VALUES %s;" % ", ".join(badge_progression)
        gc_db.execute_query(query, (), "commit")

def update_skill_progression():
    """
    Updates all skill progression in bulk.
    """

    global skill_progression

    if len(skill_progression) > 0:
        query = "INSERT INTO skill_progression (course, user, skill, participation) VALUES %s;" % ", ".join(skill_progression)
        gc_db.execute_query(query, (), "commit")

def update_streak_progression():
    """
    Updates all streak progression in bulk.
    """

    global streak_progression

    if len(streak_progression) > 0:
        query = "INSERT INTO streak_progression (course, user, streak, repetition, participation) VALUES %s;" % ", ".join(streak_progression)
        gc_db.execute_query(query, (), "commit")


### Awards

def get_awards_table():
    """
    Gets awards table for the current AutoGame mode.
    """

    return "award" if not config.TEST_MODE else "award_test"

def get_award(target, description=None, award_type=None, instance=None, reward=None, award_id=None):
    """
    Gets an award for a specific target.
    """

    awards = get_awards(target, description, award_type, instance, reward, award_id)
    nr_awards = len(awards)

    if nr_awards > 1:
        raise Exception("Couldn't get award for target with ID = %s: more than one award found." % target)

    elif nr_awards == 0:
        return None

    else:
        return awards[0]

def get_awards(target, description=None, award_type=None, instance=None, reward=None, award_id=None):
    global preloaded_awards

    instance = int(instance) if instance is not None else None
    reward = int(reward) if reward is not None else None
    award_id = int(award_id) if award_id is not None else None

    return [a for a in preloaded_awards[target] if
            (compare_with_wildcards(a[config.AWARD_DESCRIPTION_COL],
                                    description) if description is not None else True) and
            (a[config.AWARD_TYPE_COL] == award_type if award_type is not None else True) and
            (a[config.AWARD_INSTANCE_COL] == instance if instance is not None else True) and
            (a[config.AWARD_REWARD_COL] == reward if reward is not None else True) and
            (a[config.AWARD_ID_COL] == award_id if award_id is not None else True)]

def give_award(target, award_type, description, reward, instance=None):
    """
    Gives an award to a specific target.
    """

    global preloaded_awards

    # Parse params
    reward = int(reward)
    instance = int(instance) if instance is not None else None

    # Add award to database
    query = "INSERT INTO " + get_awards_table() + " (user, course, description, type, moduleInstance, reward) " \
            "VALUES (%s, %s, %s, %s, %s, %s);"
    gc_db.execute_query(query, (target, config.COURSE, description, award_type, instance, reward), "commit")

    # Get award info
    query = "SELECT LAST_INSERT_ID();"
    award_id = gc_db.execute_query(query)[0][0]

    query = "SELECT * FROM " + get_awards_table() + " WHERE id = %s;"
    award_given = gc_db.execute_query(query, (award_id,))[0]

    # Add award to preloaded awards
    award_to_preload = (award_given[config.AWARD_ID_COL], target, config.COURSE, description, award_type, instance,
                        reward, award_given[config.AWARD_DATE_COL])
    preloaded_awards[target].append(award_to_preload)

def remove_award(target, award_type, description, instance=None, award_id=None):
    """
    Removes an award from a specific target.
    """

    global preloaded_awards

    # Remove award from database
    query = "DELETE FROM " + get_awards_table() + " WHERE course = %s AND user = %s AND type = %s AND description LIKE %s"
    if instance is not None:
        query += " AND moduleInstance = %s" % instance
    if award_id is not None:
        query += " AND id = %s" % award_id
    query += ";"
    gc_db.execute_query(query, (config.COURSE, target, award_type, description), "commit")

    # Remove award from preloaded awards
    index = get_awards(target).index(get_award(target, description, award_type, instance, None, award_id))
    preloaded_awards[target] = preloaded_awards[target][:index] + preloaded_awards[target][index + 1:]

def update_award(target, award_type, description, new_reward, instance=None, award_id=None):
    """
    Updates an award's reward from a specific target.
    """

    global preloaded_awards

    # Update award in database
    query = "UPDATE " + get_awards_table() + " SET reward = %s " \
            "WHERE course = %s AND user = %s AND type = %s AND description LIKE %s"
    if instance is not None:
        query += " AND moduleInstance = %s" % instance
    if award_id is not None:
        query += " AND id = %s" % award_id
    query += ";"
    gc_db.execute_query(query, (new_reward, config.COURSE, target, award_type, description), "commit")

    # Update award in preloaded awards
    a = get_award(target, description, award_type, instance, None, award_id)
    index = get_awards(target).index(a)
    new_award = (a[config.AWARD_ID_COL] if award_id is None else award_id, target, config.COURSE, description,
                 award_type, instance, new_reward, a[config.AWARD_DATE_COL])
    preloaded_awards[target].insert(index, new_award)
    preloaded_awards[target] = preloaded_awards[target][:index + 1] + preloaded_awards[target][index + 2:]

def award_received(target, award_type, description, instance=None, award_id=None):
    """
    Checks whether a given award has already been received
    by a specific target.
    """

    return get_award(target, description, award_type, instance, None, award_id) is not None


### Tokens' spending

def get_spending(target, description=None, amount=None, spending_id=None):
    global preloaded_spending

    amount = int(amount) if amount is not None else None
    spending_id = int(spending_id) if spending_id is not None else None

    return [s for s in preloaded_spending[target] if
            (compare_with_wildcards(s[config.SPENDING_DESCRIPTION_COL], description) if description is not None else True) and
            (s[config.SPENDING_AMOUNT_COL] == amount if amount is not None else True) and
            (s[config.SPENDING_ID_COL] == spending_id if spending_id is not None else True)]

def do_spending(target, description, amount):
    """
    Spends a certain amount of tokens from a specific target.
    """

    global preloaded_spending

    # Parse params
    amount = int(amount)

    # Add spending to database
    query = "INSERT INTO virtual_currency_spending (user, course, description, amount) VALUES (%s, %s, %s, %s);"
    gc_db.execute_query(query, (target, config.COURSE, description, amount), "commit")

    # Get spending info
    query = "SELECT LAST_INSERT_ID();"
    spending_id = gc_db.execute_query(query)[0][0]

    query = "SELECT * FROM virtual_currency_spending WHERE id = %s;"
    spending_given = gc_db.execute_query(query, (spending_id,))[0]

    # Add spending to preloaded spending
    spending_to_preload = (spending_given[config.SPENDING_ID_COL], target, config.COURSE, description, amount, spending_given[config.SPENDING_DATE_COL])
    preloaded_spending[target].append(spending_to_preload)

def remove_spending(target, description, spending_id=None):
    """
    Removes a tokens' spending from a specific target.
    """

    global preloaded_spending

    # Remove spending from database
    query = "DELETE FROM virtual_currency_spending WHERE course = %s AND user = %s AND description LIKE %s"
    if spending_id is not None:
        query += " AND id = %s" % spending_id
    query += ";"
    gc_db.execute_query(query, (config.COURSE, target, description), "commit")

    # Remove spending from preloaded spending
    index = get_spending(target).index(get_spending(target, description, None, spending_id)[0])
    preloaded_spending[target] = preloaded_spending[target][:index] + preloaded_spending[target][index + 1:]

def update_spending(target, description, new_amount, spending_id=None):
    """
    Updates a spending amount from a specific target.
    """

    global preloaded_spending

    # Update spending in database
    query = "UPDATE virtual_currency_spending SET amount = %s WHERE course = %s AND user = %s AND description LIKE %s"
    if spending_id is not None:
        query += " AND id = %s" % spending_id
    query += ";"
    gc_db.execute_query(query, (new_amount, config.COURSE, target, description), "commit")

    # Update spending in preloaded spending
    s = get_spending(target, description, None, spending_id)[0]
    index = get_spending(target).index(s)
    new_spending = (s[config.SPENDING_ID_COL] if spending_id is None else spending_id, target, config.COURSE,
                    description, new_amount, s[config.SPENDING_DATE_COL])
    preloaded_spending[target].insert(index, new_spending)
    preloaded_spending[target] = preloaded_spending[target][:index + 1] + preloaded_spending[target][index + 2:]

def filter_logs_by_cost(logs, costs, available_tokens):
    """
    Filters logs whose cost couldn't be paid.
    """

    nr_logs = len(logs)
    if nr_logs > len(costs):
        raise Exception('Couldn\'t filter logs by cost: missing cost info on some logs.')

    filtered_logs = []
    for i in range(0, nr_logs):
        if available_tokens - costs[i] < 0:  # Not enough tokens
            break
        else:  # Enough tokens
            available_tokens -= costs[i]
            filtered_logs.append(logs[i])

    return filtered_logs, available_tokens


### Calculating rewards

def calculate_reward(target, award_type, reward_to_give, reward_given=0):
    """
    Calculates reward for a given target
    based on max. values.
    """

    reward_to_give = int(reward_to_give)
    reward_given = int(reward_given)

    def get_max_xp():
        query = "SELECT maxXP FROM xp_config WHERE course = %s;" % config.COURSE
        result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0]
        return int(result) if result else None

    def get_max_xp_by_type():
        query = "SELECT maxXP FROM %s WHERE course = %s;" % (award_type + "s_config", config.COURSE)
        result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0]
        return int(result) if result else None

    def get_target_xp():
        xp = {"total": 0}

        # Get badges XP
        if module_enabled("Badges"):
            badges_total = get_total_badge_reward(target)
            xp["badges"] = badges_total
            xp["total"] += badges_total

        # Get skills XP
        if module_enabled("Skills"):
            skills_total = get_total_skill_reward(target)
            xp["skills"] = skills_total
            xp["total"] += skills_total

        # Get streaks extra credit
        if module_enabled("Streaks"):
            streaks_total = get_total_streak_reward(target)
            xp["streaks"] = streaks_total
            xp["total"] += streaks_total

        return xp

    def calculate_by_type():
        return award_type == 'badge' or award_type == 'skill' or award_type == 'streak'

    if module_enabled("XPLevels"):
        # Get max. XP
        max_xp = get_max_xp()
        max_xp_for_type = get_max_xp_by_type() if calculate_by_type() else None

        # No max. threshold set, nothing to calculate
        if max_xp is None and max_xp_for_type is None:
            return reward_to_give

        # Calculate reward
        target_xp = get_target_xp()
        if max_xp is not None:
            reward_to_give = max(min(max_xp - (target_xp['total'] - reward_given), reward_to_give), 0)
            reward_given = reward_to_give

        if max_xp_for_type is not None:
            reward_to_give = max(min(max_xp_for_type - (target_xp[award_type + "s"] - reward_given), reward_to_give), 0)

        return reward_to_give

    return 0

def calculate_extra_credit_reward(target, award_type, reward_to_give, reward_given=0):
    """
    Calculates reward of a certain type for a given target
    based on max. extra credit values.
    """

    reward_to_give = int(reward_to_give)
    reward_given = int(reward_given)

    def get_max_extra_credit():
        query = "SELECT maxExtraCredit FROM xp_config WHERE course = %s;" % config.COURSE
        result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0]
        return int(result) if result else None

    def get_max_extra_credit_by_type():
        query = "SELECT maxExtraCredit FROM %s WHERE course = %s;" % (award_type + "s_config", config.COURSE)
        result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0]
        return int(result) if result else None

    def get_target_extra_credit():
        extra_credit = {"total": 0}

        # Get badges extra credit
        if module_enabled("Badges"):
            # Get badges IDs which are extra credit
            query = "SELECT id FROM badge WHERE course = %s AND isExtra = True;" % config.COURSE
            badges_ids = [item for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

            # Calculate badges extra credit already awarded
            badges_total = 0
            for a in get_awards(target, None, "badge"):
                # Ignore badges which are not extra credit
                if a[config.AWARD_INSTANCE_COL] not in badges_ids:
                    continue

                badges_total += a[config.AWARD_REWARD_COL]

            extra_credit["badges"] = badges_total
            extra_credit["total"] += badges_total

        # Get skills extra credit
        if module_enabled("Skills"):
            # Get skill IDs which are extra credit
            query = "SELECT id FROM skill WHERE course = %s AND isExtra = True;" % config.COURSE
            skills_ids = [item for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

            # Calculate skills extra credit already awarded
            skills_total = 0
            for a in get_awards(target, None, "skill"):
                # Ignore skills which are not extra credit
                if a[config.AWARD_INSTANCE_COL] not in skills_ids:
                    continue

                skills_total += a[config.AWARD_REWARD_COL]

            extra_credit["skills"] = skills_total
            extra_credit["total"] += skills_total

        # Get streaks extra credit
        if module_enabled("Streaks"):
            # Get streaks IDs which are extra credit
            query = "SELECT id FROM streak WHERE course = %s AND isExtra = True;" % config.COURSE
            streaks_ids = [item for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

            # Calculate streaks extra credit already awarded
            streaks_total = 0
            for a in get_awards(target, None, "streak"):
                # Ignore streaks which are not extra credit
                if a[config.AWARD_INSTANCE_COL] not in streaks_ids:
                    continue

                streaks_total += a[config.AWARD_REWARD_COL]

            extra_credit["streaks"] = streaks_total
            extra_credit["total"] += streaks_total

        return extra_credit

    def calculate_by_type():
        return award_type == 'badge' or award_type == 'skill' or award_type == 'streak'

    if module_enabled("XPLevels"):
        # Get max. extra credit
        max_extra_credit = get_max_extra_credit()
        max_extra_credit_for_type = get_max_extra_credit_by_type() if calculate_by_type() else None

        # No max. thresholds set, nothing to calculate
        if max_extra_credit is None and max_extra_credit_for_type is None:
            return reward_to_give

        # Calculate reward
        target_extra_credit = get_target_extra_credit()
        if max_extra_credit is not None:
            reward_to_give = max(min(max_extra_credit - (target_extra_credit['total'] - reward_given), reward_to_give), 0)
            reward_given = reward_to_give

        if max_extra_credit_for_type is not None:
            reward_to_give = max(min(max_extra_credit_for_type - (target_extra_credit[award_type + "s"] - reward_given), reward_to_give), 0)

        return reward_to_give

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

    def get_level(xp):
        # Get levels in course
        qry = "SELECT * FROM level WHERE course = %s ORDER BY minXP;" % config.COURSE
        levels = gc_db.data_broker.get(gc_db, config.COURSE, qry)

        # Find current level
        current_lvl = int(levels[0][0])
        for lvl in levels:
            if xp >= int(lvl[2]):
                current_lvl = int(lvl[0])
            else:
                return current_lvl

    # Calculate total
    total_xp = 0
    for a in get_awards(target):
        if a[config.AWARD_TYPE_COL] == 'tokens':
            continue
        total_xp += a[config.AWARD_REWARD_COL]

    # Get new level
    current_level = get_level(total_xp)

    # Update target total XP and level
    query = "UPDATE user_xp SET xp = %s, level = %s WHERE course = %s AND user = %s;"
    gc_db.execute_query(query, (total_xp, current_level, config.COURSE, target), "commit")

def calculate_team_xp(target):
    """
    Calculates total XP for a given target's team based on awards received.
    """

    # Get target team
    query = "SELECT teamId FROM teams_members WHERE memberId = %s;"
    table = gc_db.execute_query(query, target)
    team = table[0][0] if len(table) == 1 else None

    if team is not None:
        # Calculate team total
        query = "SELECT IFNULL(SUM(reward), 0) FROM award_teams WHERE course = %s AND team = %s GROUP BY team;"
        team_xp = int(gc_db.execute_query(query, (config.COURSE, team))[0][0])

        # Get new level
        query = "SELECT id FROM level WHERE course = %s AND minXP <= %s GROUP BY id ORDER BY minXP DESC limit 1;"
        current_level = gc_db.execute_query(query, (config.COURSE, team_xp))[0][0]

        # Update team total XP and level
        query = "UPDATE teams_xp SET xp = %s, level = %s WHERE course = %s AND teamId = %s;"
        gc_db.execute_query(query, (team_xp, current_level, config.COURSE, team), "commit")

def calculate_tokens(target):
    """
    Calculates total tokens for a given target based on awards received
    and spending performed.
    """

    # Calculate total tokens received
    total_received = get_total_tokens_reward(target)

    # Calculate total tokens spent
    total_spent = 0
    for s in get_spending(target):
        total_spent += s[config.SPENDING_AMOUNT_COL]

    # Update target wallet
    query = "UPDATE user_wallet SET tokens = %s WHERE course = %s AND user = %s;"
    gc_db.execute_query(query, (total_received - total_spent, config.COURSE, target), "commit")


### Utils

def course_exists(course):
    """
    Checks whether a given course exists and is active.
    """

    query = "SELECT isActive FROM course WHERE id = %s;" % course
    table = gc_db.data_broker.get(gc_db, course, query)

    return table[0][0] if len(table) == 1 else False

def get_course_dates(course):
    """
    Gets a given course's start and end dates.
    """

    query = "SELECT startDate, endDate FROM course WHERE id = %s;" % course
    return [date for date in gc_db.data_broker.get(gc_db, course, query)[0]]

def module_enabled(module):
    """
    Checks whether a given module exists and is enabled.
    """

    query = "SELECT isEnabled FROM course_module WHERE course = %s AND module = '%s';" % (config.COURSE, module)
    table = gc_db.data_broker.get(gc_db, config.COURSE, query)

    return table[0][0] if len(table) == 1 else False

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
        start = date if precise else \
            date - timedelta(seconds=date.second, minutes=date.minute, hours=date.hour, days=date.weekday())
        end = start + timedelta(weeks=number)

    elif time == "month":
        start = date if precise else \
            date - timedelta(seconds=date.second, minutes=date.minute, hours=date.hour, days=date.day - 1)
        end = (start + timedelta(days=number*32)).replace(day=start.day) if precise else \
            (start.replace(day=1) + timedelta(days=number*32)).replace(day=1)

    elif time == "year":
        start = date if precise else datetime(date.year, 1, 1)
        end = (start + timedelta(days=366)).replace(day=start.day, month=start.month) if precise else \
            datetime(date.year + number, 1, 1)

    return start, end

def compare_with_wildcards(text_1, text_2):
    """
    Compares two strings using wildcards (only 2nd string
    can have wildcards).
    Wildcard character can be either '%' or '*'.

    Examples:
        > t1 = 'Skill Tree, Re: Movie Poster', t2 = 'Skill Tree%' --> True
        > t1 = 'Skill Tree, Re: Movie Poster', t2 = 'Skill Tree*' --> True
        > t1 = 'Skill Tree, Re: Movie Poster', t2 = 'Skill Trees%' --> False
        > t1 = 'Skill Tree, Re: Movie Poster', t2 = 'Skill Tree, Re: Movie Poster' --> True
    """

    return fnmatch.fnmatch(text_1, text_2.replace("%", "*"))


### ------------------------------------------------------ ###
###	------------------ PUBLIC functions ------------------ ###
### -- (accessible in rules through imported functions) -- ###
### ------------------------------------------------------ ###

### Getting logs

def get_logs(target=None, log_type=None, rating=None, evaluator=None, start_date=None, end_date=None, description=None):
    """
    Gets all logs under certain conditions.

    Option to get logs for a specific target, type, rating,
    evaluator, and/or description, as well as an initial and/or end date.
    """
    global preloaded_logs

    if target is not None and target in preloaded_logs:
        logs = preloaded_logs[target]
    else:
        logs = [item for sublist in preloaded_logs.values() for item in sublist]
        if target is not None:
            logs = [log for log in logs if int(log[config.LOG_USER_COL]) == target]

    if log_type is not None:
        logs = [log for log in logs if log[config.LOG_TYPE_COL] == log_type]

    if rating is not None:
        logs = [log for log in logs if int(log[config.LOG_RATING_COL]) == rating]

    if evaluator is not None:
        logs = [log for log in logs if int(log[config.LOG_EVALUATOR_COL]) == evaluator]

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

def get_resource_view_logs(target, name=None, unique=True):
    """
    Gets all resource view logs for a specific target.

    Option to get a specific resource view by name and
    to get only one resource view log per description.
    """

    logs = get_logs(target, "resource view", None, None, None, None, name)

    if unique:
        unique_logs = []
        descriptions = []
        for log in logs:
            if log[config.LOG_DESCRIPTION_COL] not in descriptions:
                unique_logs.append(log)
                descriptions.append(log[config.LOG_DESCRIPTION_COL])
        logs = unique_logs

    return logs

def get_skill_logs(target, name=None, rating=None, only_min_rating=False, only_latest=False):
    """
    Gets skill logs for a specific target.

    Options to get logs for a specific skill by name,
    as well as with a certain rating.

    Additional options to get only logs that meet the minimum
    rating, as well as only the latest log for each skill.
    """

    logs = get_forum_logs(target, "Skill Tree", name, rating) if module_enabled("Skills") else []

    # Get only logs that meet the minimum rating
    if only_min_rating:
        # Get min. rating
        query = "SELECT minRating FROM skills_config WHERE course = %s;" % config.COURSE
        min_rating = int(gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0])

        # Filter by minimum rating
        logs = [log for log in logs if int(log[config.LOG_RATING_COL]) >= min_rating]

    # Get only the latest log for each skill
    if only_latest:
        # Group logs by skill
        logs_by_skill = {}
        for log in logs:
            skill_name = log[config.LOG_DESCRIPTION_COL].replace('Skill Tree, Re: ', '')
            if skill_name in logs_by_skill:
                logs_by_skill[skill_name].append(log)
            else:
                logs_by_skill[skill_name] = [log]

        # Get the latest log for each skill
        logs = []
        for skill_name in logs_by_skill.keys():
            nr_skill_logs = len(logs_by_skill[skill_name])
            logs += [logs_by_skill[skill_name][nr_skill_logs - 1]]

    logs.sort(key=lambda lg: lg[config.LOG_DATE_COL])
    return logs

def get_skill_tier_logs(target, tier, only_min_rating=True, only_latest=True):
    """
    Gets skill tier logs for a specific target.

    Options to get only logs that meet the minimum rating,
    as well as only the latest log for each skill.
    """

    if module_enabled("Skills"):
        # Get skill names of tier
        query = "SELECT s.name " \
                "FROM skill s JOIN skill_tier t on s.tier = t.id " \
                "WHERE s.course = %s AND t.position = %s" % (config.COURSE, tier - 1)
        skill_names = [item.decode() for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

        # Get logs
        logs = []
        for name in skill_names:
            logs += get_skill_logs(target, name, None, only_min_rating, only_latest)
        return logs

    return []

def get_url_view_logs(target, name=None):
    """
    Gets all URL view logs for a specific target.

    Option to get a specific URL view by name.
    """

    return get_logs(target, "url viewed", None, None, None, None, name)

def get_periodic_logs(logs, number, time, log_type):
    """
    Gets periodic logs on a set of logs.

    There are two options for periodicity:
        > absolute -> check periodicity in equal periods,
                    beginning at course start date until end date

        > relative -> check periodicity starting on the
                    first entry for streak
    """

    if len(logs) == 0:
        return []

    if log_type == "absolute":
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
            if log_type == "absolute":
                period = end_date - start_date
                start_date = end_date
                end_date += period

                if not (start_date <= date <= end_date):
                    periodic_logs.append([])
                    start_date, end_date = get_dates_of_period(date, number, time, False)

            periodic_logs.append([log])

        if log_type == "relative":
            start_date, end_date = get_dates_of_period(log[config.LOG_DATE_COL], number, time)

    return periodic_logs


### Getting total reward

def get_total_reward(target, award_type=None):
    """
    Gets total reward for a given target.
    Option to filter by a specific award type.
    """

    total_reward = 0
    for a in get_awards(target, None, award_type):
        total_reward += int(a[config.AWARD_REWARD_COL])
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

def award(target, award_type, description, reward, instance=None, unique=True, award_id=None):
    """
    Awards a single prize to a specific target.

    NOTE: will not retract, but will not award twice if unique.
    Updates award if reward has changed.
    """

    awards_given = get_awards(target, description, award_type, instance, None, award_id)
    nr_awards_given = len(awards_given)

    if unique and nr_awards_given > 1:
        logging.warning("Award '%s' has been awarded more than once for target with ID = %s." % (description, target))
        return

    if nr_awards_given == 0 or (not unique and award_id is None):  # Award
        reward = calculate_reward(target, award_type, reward)
        give_award(target, award_type, description, reward, instance)

    elif unique or award_id is not None:  # Update award, if changed
        old_reward = awards_given[0][config.AWARD_REWARD_COL]
        reward = calculate_reward(target, award_type, reward, old_reward)
        if reward != old_reward:
            update_award(target, award_type, description, reward, instance, awards_given[0][config.AWARD_ID_COL])

def award_assignment_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards assignment grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per assignment
     > max_grade --> maximum grade per assignment

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL]
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award(target, "assignment", name, reward)

def award_badge(target, name, lvl, logs, progress=None):
    """
    Awards a given level to a specific target.

    NOTE: will retract if level changed.
    Updates award if reward has changed.
    """

    def get_description(badge_name, badge_lvl):
        lvl_info = " (level %s)" % badge_lvl
        return badge_name + lvl_info

    global badge_progression
    award_type = "badge"

    if module_enabled("Badges"):
        # Get badge info
        query = "SELECT bl.badge, bl.number, bl.reward, bl.tokens, b.isExtra, bl.goal, b.description, bl.description " \
                "FROM badge_level bl LEFT JOIN badge b on b.id = bl.badge " \
                "WHERE b.course = %s AND b.name = '%s' ORDER BY number;" % (config.COURSE, name)
        table_badge = gc_db.data_broker.get(gc_db, config.COURSE, query)
        badge_id = table_badge[0][0]

        # Update badge progression
        for log in logs:
            badge_progression.append('(%s, %s, %s, %s)' % (config.COURSE, target, badge_id, log[config.LOG_ID_COL]))

        # Get awards already given
        awards_given = get_awards(target, name + "%", award_type, badge_id)
        nr_awards_given = len(awards_given)

        # Lvl is zero and there are no awards to be removed
        # Simply return right away
        if lvl == 0 and nr_awards_given == 0:
            return

        # The rule/data sources have been updated, the 'award' table
        # has badge levels attributed which are no longer valid.
        # All levels no longer valid must be deleted
        if nr_awards_given > lvl:
            for level in range(lvl + 1, nr_awards_given + 1):
                description = get_description(name, level)

                # Delete award
                remove_award(target, award_type, description, badge_id)

                # Remove tokens
                if len(get_awards(target, description, 'tokens', badge_id)) > 0:
                    remove_award(target, 'tokens', description, badge_id)

        # Award and/or update badge levels
        for level in range(1, lvl + 1):
            description = get_description(name, level)

            # Calculate reward
            is_extra = table_badge[level - 1][4]
            badge_reward = int(table_badge[level - 1][2])
            award_given = get_award(target, description, award_type, badge_id)
            reward = calculate_extra_credit_reward(target, award_type, badge_reward, award_given[config.AWARD_REWARD_COL] if award_given is not None else 0) if is_extra else badge_reward

            # Award badge
            award(target, award_type, description, reward, badge_id)

            # Award tokens
            badge_tokens = int(table_badge[level - 1][3])
            if module_enabled("VirtualCurrency") and badge_tokens > 0:
                award_tokens(target, description, [level], badge_tokens, badge_id)

def award_bonus(target, name, logs, reward=None, instance=None, unique=True):
    """
    Awards given bonus to a specific target.

    NOTE: will retract if bonus removed.
    Updates award if reward has changed.
    """

    award_type = 'bonus'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = get_awards(target, name, award_type, instance)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has bonus attributed which are longer valid.
    # The bonus no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete latest invalid awards
        for diff in range(0, nr_awards_given - nr_logs):
            remove_award(target, award_type, name, instance, awards_given[nr_awards_given - diff - 1][config.AWARD_ID_COL])
        nr_awards_given = nr_logs

    # Award and/or update bonus
    for i in range(0, nr_logs):
        log = logs[i]
        award_id = None
        if not unique and nr_awards_given > i:
            award_id = awards_given[i][config.AWARD_ID_COL]
        bonus = reward if reward is not None else logs[nr_logs - 1][config.LOG_RATING_COL] if unique else log[config.LOG_RATING_COL]
        award(target, award_type, name, bonus, instance, unique, award_id)

def award_exam_grade(target, name, logs, reward, max_xp=1, max_grade=1):
    """
    Awards exam grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP for exam
     > max_grade --> maximum grade for exam

    NOTE: will retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'exam'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = get_awards(target, name, award_type)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has an exam grade attributed which is longer valid.
    # The grade no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete invalid awards
        remove_award(target, award_type, name)

    # Award and/or update exam grade
    if nr_logs > 0:
        reward = round((reward / max_grade) * max_xp)
        award(target, award_type, name, reward)

def award_lab_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards lab grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per lab
     > max_grade --> maximum grade per lab

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'labs'

    for log in logs:
        lab_nr = int(log[config.LOG_DESCRIPTION_COL])
        name = "Lab %s" % lab_nr
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award(target, award_type, name, reward, lab_nr)

def award_post_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards post grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per post
     > max_grade --> maximum grade per post

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL].split(",")[0]
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award(target, "post", name, reward)

def award_presentation_grade(target, name, logs, max_xp=1, max_grade=1):
    """
    Awards presentation grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP for presentation
     > max_grade --> maximum grade for presentation

    NOTE: will retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'presentation'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = get_awards(target, name, award_type)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has a presentation grade attributed which is longer valid.
    # The grade no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete invalid awards
        remove_award(target, award_type, name)

    # Award and/or update exam grade
    if nr_logs > 0:
        reward = round((logs[nr_logs - 1][config.LOG_RATING_COL] / max_grade) * max_xp)
        award(target, award_type, name, reward)

def award_quiz_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards quiz grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per quiz
     > max_grade --> maximum grade per quiz

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'quiz'

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL]
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award(target, award_type, name, reward)

def award_skill(target, name, rating, logs, dependencies=True, use_wildcard=False):
    """
    Awards a given skill to a specific target.
    Option to spend a wildcard to give award.

    NOTE: will retract if rating changed.
    Updates award if reward has changed.
    """

    def calculate_skill_tree_reward(reward_to_give, reward_given=0):
        reward_to_give = int(reward_to_give)
        reward_given = int(reward_given)

        # Get max. skill tree reward
        max_skill_tree_reward = int(table_skill[3]) if table_skill[3] else None

        # No max. threshold set, nothing to calculate
        if max_skill_tree_reward is None:
            return reward_to_give

        # Calculate reward
        target_skill_tree_reward = get_total_skill_reward(target)
        reward_to_give = max(min(max_skill_tree_reward - (target_skill_tree_reward - reward_given), reward_to_give), 0)
        return reward_to_give

    def spend_wildcard(a_id, nr_wildcards_to_spend=1):
        q = "SELECT nrWildcardsUsed FROM award_wildcard WHERE award = %s;"
        res = gc_db.execute_query(q, (a_id,))
        nr_wildcards_used = int(res[0][0]) if len(res) > 0 else 0

        if nr_wildcards_used != nr_wildcards_to_spend:
            # Use wildcards to pay for skill
            if len(res) > 0:
                q = "UPDATE award_wildcard SET nrWildcardsUsed = %s WHERE award = %s;"
                gc_db.execute_query(q, (nr_wildcards_to_spend, a_id), "commit")
            else:
                q = "INSERT INTO award_wildcard (award, nrWildcardsUsed) VALUES (%s, %s);"
                gc_db.execute_query(q, (a_id, nr_wildcards_to_spend), "commit")

    def get_attempt_cost(attempt_nr):
        if attempt_nr < 1:
            raise Exception("Attempt number for target with ID = %s at skill '%s' needs to be bigger than zero." % (target, name))

        # Fixed cost
        if tier_cost_info["costType"] == "fixed":
            return tier_cost_info["cost"]

        # Incremental cost
        elif tier_cost_info["costType"] == "incremental":
            attempt_logs = logs[0: attempt_nr - 1]
            attempts = len([attempt_log for attempt_log in attempt_logs if int(attempt_log[config.LOG_RATING_COL]) >= tier_cost_info["minRating"]])
            return tier_cost_info["cost"] + tier_cost_info["increment"] * attempts
        
        # Exponential cost
        else:
            attempt_logs = logs[0: attempt_nr - 1]
            attempts = len([attempt_log for attempt_log in attempt_logs if int(attempt_log[config.LOG_RATING_COL]) >= tier_cost_info["minRating"]])
            if attempts > 0:
                return tier_cost_info["increment"] * (2 ** (attempts - 1))
            else:
             return tier_cost_info["cost"]

    def get_attempt_description(att):
        attempt_info = " (%s%s attempt)" % (att, "st" if att == 1 else "nd" if att == 2 else "rd" if att == 3 else "th")
        return name + attempt_info

    global skill_progression
    award_type = "skill"

    if module_enabled("Skills"):
        # Get min. rating
        query = "SELECT minRating FROM skills_config WHERE course = %s;" % config.COURSE
        min_rating = int(gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0])

        # Get skill info
        query = "SELECT s.id, t.reward, s.isExtra, st.maxReward, tc.costType, tc.cost, tc.increment, tc.minRating " \
                "FROM skill s LEFT JOIN skill_tier t on s.tier = t.id " \
                "LEFT JOIN skill_tier_cost tc on tc.tier = t.id " \
                "LEFT JOIN skill_tree st on t.skillTree = st.id " \
                "WHERE s.course = %s AND s.name = '%s';" % (config.COURSE, name)
        table_skill = gc_db.data_broker.get(gc_db, config.COURSE, query)[0]
        skill_id = table_skill[0]

        # Update skill progression
        for log in logs:
            skill_progression.append('(%s, %s, %s, %s)' % (config.COURSE, target, skill_id, log[config.LOG_ID_COL]))

        # Rating is not enough to win the award or dependencies haven't been met
        if rating < min_rating or not dependencies:
            # Get awards already given
            awards_given = get_awards(target, name, award_type, skill_id)
            nr_awards_given = len(awards_given)

            # The rule/data sources have been updated, the 'award' table
            # has a skill award attributed which is longer valid.
            # The award no longer valid must be deleted
            if nr_awards_given > 0:
                # Delete invalid award
                # NOTE: wildcards used to pay for skill will be automatically
                #       deleted because of foreign key binding to the award
                remove_award(target, award_type, name, skill_id)

        # Award and/or update skill award
        else:
            # Calculate reward
            is_extra = table_skill[2]
            skill_reward = int(table_skill[1])
            award_given = get_award(target, name, award_type, skill_id)
            reward = calculate_extra_credit_reward(target, award_type, skill_reward, award_given[config.AWARD_REWARD_COL] if award_given is not None else 0) if is_extra else skill_reward
            reward = calculate_skill_tree_reward(reward, award_given[config.AWARD_REWARD_COL] if award_given is not None else 0)

            # Award skill
            award(target, award_type, name, reward, skill_id)

            # Spend wildcard
            if use_wildcard:
                skill_award = get_award(target, name, award_type, skill_id)
                if skill_award is None:
                    query = "SELECT id FROM " + get_awards_table() + " " \
                            "WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
                    award_id = gc_db.execute_query(query, (config.COURSE, target, award_type, name, skill_id))[0][0]
                else:
                    award_id = skill_award[config.AWARD_ID_COL]
                spend_wildcard(award_id)

        # Spend tokens, if virtual currency enabled
        if module_enabled("VirtualCurrency"):
            tier_cost_info = {"costType": table_skill[4], "cost": int(table_skill[5]), "increment": int(table_skill[6]),
                              "minRating": int(table_skill[7])}

            nr_attempts = len(logs)
            spending_done = len(get_spending(target, name + '%'))

            if spending_done > nr_attempts:
                # Remove excess spending
                for attempt in range(nr_attempts + 1, spending_done + 1):
                    description = get_attempt_description(attempt)
                    remove_spending(target, description)

            elif dependencies:
                # Perform and/or update spending
                for attempt in range(1, nr_attempts + 1):
                    attempt_cost = get_attempt_cost(attempt)
                    description = get_attempt_description(attempt)
                    spend_tokens(target, description, attempt_cost)

        # Check if rating is enough to win the award, but dependencies are missing (create notification)
        if rating >= min_rating and not dependencies:
            query = "SELECT s.name FROM skill s WHERE s.id IN (" \
                        "SELECT sdc.skill " \
                        "FROM skill s JOIN skill_dependency sd on sd.skill = s.id " \
                        "JOIN skill_dependency_combo sdc on sdc.dependency = sd.id " \
                        "WHERE s.id = %s" \
                    ");" % skill_id
            dependencies_names = gc_db.data_broker.get(gc_db, config.COURSE, query)

            # Removes duplicates
            dependencies_names_unique = list(set([el[0].decode() for el in dependencies_names]))

            # Filter dependencies already awarded
            dependencies_missing = [dep_name for dep_name in dependencies_names_unique
                                    if not award_received(target, award_type, dep_name)]

            # Transform array into string with commas
            dependencies_missing.sort()
            dependencies_missing_string = ', '.join(dependencies_missing)

            message = "You can't be awarded skill '%s' yet... Almost there! There are some dependencies missing: %s" \
                      % (name, dependencies_missing_string)

            query = "SELECT COUNT(*) FROM notification WHERE course = %s AND user = %s AND message = %s;"
            already_sent = int(gc_db.execute_query(query, (config.COURSE, target, message))[0][0]) > 0

            # Add notification to table
            if not already_sent:
                query = "INSERT INTO notification (course, user, message, isShowed) VALUES (%s,%s,%s,%s);"
                gc_db.execute_query(query, (config.COURSE, target, message, 0), "commit")

def award_streak(target, name, logs):
    """
    Awards a given streak to a specific target.

    NOTE: will retract if streak changed.
    Updates award if reward has changed.
    """

    def get_description(streak_name, streak_repetition):
        repetition_info = " (%s%s time)" % (streak_repetition, "st" if streak_repetition == 1 else "nd" if streak_repetition == 2 else "rd" if streak_repetition == 3 else "th")
        return streak_name + repetition_info

    def get_deadline(last_dt, period_tp, period_num, period_tm):
        if not is_periodic or not last_dt:
            return None

        course_end_date = get_course_dates(config.COURSE)[1]
        dl = get_dates_of_period(last_dt, period_num, period_tm, not period_tp == "absolute")[1]
        return course_end_date if dl >= course_end_date else dl

    global streak_progression
    award_type = "streak"

    if module_enabled("Streaks"):
        # Get streak info
        query = "SELECT id, goal, periodicityGoal, periodicityNumber, periodicityTime, periodicityType, reward, tokens, isExtra, isRepeatable " \
                "FROM streak WHERE course = %s AND name = '%s';" % (config.COURSE, name)
        table_streak = gc_db.data_broker.get(gc_db, config.COURSE, query)[0]
        streak_id = table_streak[0]
        goal = int(table_streak[1])
        period_number = int(table_streak[3]) if table_streak[3] is not None else None
        period_time = table_streak[4].decode() if table_streak[4] is not None else None
        is_periodic = period_number is not None and period_time is not None
        period_type = table_streak[5].decode() if table_streak[5] is not None else None
        period_goal = int(table_streak[2]) if table_streak[2] is not None else None

        # Get target progression in streak
        progression = []
        nr_groups = len(logs)
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
        is_repeatable = table_streak[9]
        if not is_repeatable:
            progression = progression[:goal]

        # Update streak progression
        steps = len(progression)
        for index in range(0, steps):
            log = progression[index]
            repetition = math.floor(index / goal + 1)
            streak_progression.append('(%s, %s, %s, %s, %s)' % (config.COURSE, target, streak_id, repetition, log[config.LOG_ID_COL]))

        # Update streak deadline for target
        if is_periodic:
            if steps == 0:
                last_date = datetime.now() if period_type == "absolute" else None
            else:
                last_date = progression[-1][config.LOG_DATE_COL]

            query = "SELECT deadline FROM streak_deadline WHERE course = %s AND user = %s AND streak = %s;"
            old_deadline = gc_db.execute_query(query, (config.COURSE, target, streak_id))
            new_deadline = get_deadline(last_date, period_type, period_number, period_time)

            if not old_deadline and new_deadline:
                query = "INSERT INTO streak_deadline (course, user, streak, deadline) VALUES (%s, %s, %s, %s);"
                gc_db.execute_query(query, (config.COURSE, target, streak_id, new_deadline), "commit")

            elif old_deadline and new_deadline and new_deadline != old_deadline:
                query = "UPDATE streak_deadline SET deadline = %s WHERE course = %s AND user = %s AND streak = %s;"
                gc_db.execute_query(query, (new_deadline, config.COURSE, target, streak_id), "commit")

            elif old_deadline and not new_deadline:
                query = "DELETE FROM streak_deadline WHERE course = %s AND user = %s and streak = %s;"
                gc_db.execute_query(query, (config.COURSE, target, streak_id), "commit")

        # Get awards already given
        awards_given = get_awards(target, name + "%", award_type, streak_id)
        nr_awards = len(awards_given)

        # No streaks reached and there are no awards to be removed
        # Simply return right away
        if nr_groups == 0 and nr_awards == 0:
            return

        # The rule/data sources have been updated, the 'award' table
        # has streaks attributed which are no longer valid.
        # All streaks no longer valid must be deleted
        nr_repetitions = math.floor(steps / goal)
        if nr_awards > nr_repetitions:
            for repetition in range(nr_repetitions + 1, nr_awards + 1):
                description = get_description(name, repetition)

                # Delete award
                remove_award(target, award_type, description, streak_id)

                # Remove tokens
                remove_award(target, 'tokens', description, streak_id)

        # Award and/or update streaks
        for repetition in range(1, nr_repetitions + 1):
            description = get_description(name, repetition)

            # Calculate reward
            is_extra = table_streak[8]
            streak_reward = int(table_streak[6])
            award_given = get_award(target, description, award_type, streak_id)
            reward = calculate_extra_credit_reward(target, award_type, streak_reward, award_given[config.AWARD_REWARD_COL] if award_given is not None else 0) if is_extra else streak_reward

            # Award streak
            award(target, award_type, description, reward, streak_id)

            # Award tokens
            streak_tokens = int(table_streak[7])
            if module_enabled("VirtualCurrency") and streak_tokens > 0:
                award_tokens(target, description, [repetition], streak_tokens, streak_id)

def award_tokens(target, name, logs, reward=None, instance=None, unique=True):
    """
    Awards given tokens to a specific target.

    NOTE: will retract if tokens removed.
    Updates award if reward has changed.
    """

    award_type = 'tokens'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = get_awards(target, name, award_type, instance)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has tokens attributed which are longer valid.
    # The tokens no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete latest invalid awards
        for diff in range(0, nr_awards_given - nr_logs):
            remove_award(target, award_type, name, instance, awards_given[nr_awards_given - diff - 1][config.AWARD_ID_COL])
        nr_awards_given = nr_logs

    # Award and/or update tokens
    for i in range(0, nr_logs):
        log = logs[i]
        award_id = None
        if not unique and nr_awards_given > i:
            award_id = awards_given[i][config.AWARD_ID_COL]
        tokens = reward if reward is not None else logs[nr_logs - 1][config.LOG_RATING_COL] if unique else log[config.LOG_RATING_COL]
        award(target, award_type, name, tokens, instance, unique, award_id)


### Spend items

def spend_tokens(target, description, amount, unique=True, spending_id=None):
    """
    Spends a certain amount of tokens of a specific target.

    NOTE: will not retract, but will not spend twice if unique.
    Updates spending if amount has changed.
    """

    spending = get_spending(target, description, None, spending_id)
    spending_done = len(spending)

    if unique and spending_done > 1:
        logging.warning("Spending '%s' has been performed more than once for target with ID = %s." % (description, target))
        return

    if spending_done == 0 or (not unique and spending_id is None) and amount > 0:  # Spend
        do_spending(target, description, amount)

    elif unique or spending_id is not None:  # Update spending, if changed
        old_amount = int(spending[0][4])
        if amount != old_amount:
            if amount > 0:
                update_spending(target, description, amount, spending[0][config.SPENDING_ID_COL])
            else:
                remove_spending(target, description, spending[0][config.SPENDING_ID_COL])


### Utils

def skill_completed(target, name):
    """
    Checks whether a given skill has already been awarded
    to a specific target.
    """

    return award_received(target, "skill", name)

def has_wildcard_available(target, skill_tree_id, wildcard_tier):
    """
    Checks whether a given target has wildcards available to use.
    """

    award_type = 'skill'

    # Get all wildcard skill IDs
    query = "SELECT s.id FROM skill s LEFT JOIN skill_tier t on s.tier = t.id " \
            "WHERE s.course = %s AND t.skillTree = %s AND t.name = '%s' AND t.isActive = True AND s.isActive = True;" \
            % (config.COURSE, skill_tree_id, wildcard_tier)
    wildcards_ids = [item for sublist in gc_db.data_broker.get(gc_db, config.COURSE, query) for item in sublist]

    # Get completed skill wildcards
    nr_completed_wildcards = 0
    for a in get_awards(target, None, award_type):
        if a[config.AWARD_INSTANCE_COL] in wildcards_ids:
            nr_completed_wildcards += 1

    # Get used wildcards
    query = "SELECT IFNULL(SUM(aw.nrWildcardsUsed), 0) " \
            "FROM " + get_awards_table() + " a LEFT JOIN award_wildcard aw on a.id = aw.award " \
            "WHERE a.course = %s AND a.user = %s AND a.type = %s;"
    nr_used_wildcards = int(gc_db.execute_query(query, (config.COURSE, target, award_type))[0][0])

    return nr_completed_wildcards > 0 and nr_used_wildcards <= nr_completed_wildcards



# FIXME: refactor below

def get_team(target):
    # -----------------------------------------------------------
    # Gets team id from target
    # -----------------------------------------------------------

    cursor = gc_db.cursor
    connect = gc_db.connection

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

    cursor = gc_db.cursor
    connect = gc_db.connection

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
