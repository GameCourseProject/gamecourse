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
modules_enabled = []

### Course Modules
_awards_module = None
_badges_module = None
_skills_module = None
_streaks_module = None
_virtual_currency_module = None


def get_modules_enabled():
    """
    Preloads all enabled modules.
    """
    global modules_enabled

    query = "SELECT module FROM course_module WHERE course = %s AND isEnabled = 1;" % config.COURSE
    table = gc_db.data_broker.get(gc_db, config.COURSE, query)
    modules_enabled = [item.decode() for sublist in table for item in sublist]

def load_course_modules():
    global _awards_module, _badges_module, _skills_module, _streaks_module, _virtual_currency_module

    if "Awards" in modules_enabled:
        _awards_module = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.awards",
                                                 package=__package__)
    if "Badges" in modules_enabled:
        _badges_module = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.badges",
                                                 package=__package__)
    if "Skills" in modules_enabled:
        _skills_module = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.skills",
                                                 package=__package__)
    if "Streaks" in modules_enabled:
        _streaks_module = importlib.import_module(
            f"gamerules.functions.gamefunctions.course_{config.COURSE}.streaks", package=__package__)
    if "VirtualCurrency" in modules_enabled:
        _virtual_currency_module = importlib.import_module(
            f"gamerules.functions.gamefunctions.course_{config.COURSE}.virtual_currency", package=__package__)

def preload_info(targets_ids):
    """
    Preloads information from the database.
    Minimizes accesses to the database.
    """

    # Preload participations
    preload_logs(targets_ids)

    # Preload awards
    if "Awards" in modules_enabled:
        _awards_module.preload_awards(targets_ids)
        _awards_module.preload_awards_rewards_config()

    if "Badges" in modules_enabled:
        _badges_module.setup_module(targets_ids)

    if "VirtualCurrency" in modules_enabled:
        # Preload tokens' spending
        _virtual_currency_module.setup_module(targets_ids)

        # Filter skill logs whose cost couldn't be paid
        if "Skills" in modules_enabled:
            filter_preloaded_skill_logs(targets_ids)
            _skills_module.setup_module(targets_ids)

def preload_logs(targets_ids):
    """
    Preloads logs for given targets.
    Ensures the database is accessed only once to retrieve logs.
    """

    global preloaded_logs

    query = "SELECT id, user, description, type, post, date, rating, evaluator FROM participation WHERE course = %s" % config.COURSE
    query += " AND (user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    query += " OR evaluator IN (%s))" % (', '.join([str(el) for el in targets_ids]))
    query += " ORDER BY date ASC;"
    logs = gc_db.execute_query(query)

    # Initialize preloaded logs
    preloaded_logs = {}

    # Organize logs by target
    for log in logs:
        log = (log[config.LOG_ID_COL],
               log[config.LOG_USER_COL],
               log[config.LOG_DESCRIPTION_COL].decode(),
               log[config.LOG_TYPE_COL].decode(),
               log[config.LOG_POST_COL].decode() if log[config.LOG_POST_COL] is not None else None, # can remove
               log[config.LOG_DATE_COL],
               log[config.LOG_RATING_COL],
               log[config.LOG_EVALUATOR_COL])

        target_id = log[config.LOG_USER_COL]
        log_type = log[config.LOG_TYPE_COL]
        if target_id in preloaded_logs:
            if log_type in preloaded_logs[target_id]:
                preloaded_logs[target_id][log_type].append(log)
            else:
                preloaded_logs[target_id][log_type] = [log]
        else:
            preloaded_logs[target_id] = {log_type: [log]}

        evaluator = log[config.LOG_EVALUATOR_COL]
        if evaluator in targets_ids:
            if evaluator in preloaded_logs:
                if "evaluator" in preloaded_logs[evaluator]:
                    preloaded_logs[evaluator]["evaluator"].append(log)
                else:
                    preloaded_logs[evaluator]["evaluator"] = [log]
            else:
                preloaded_logs[evaluator] = {"evaluator": [log]}

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
        from gamerules.functions.gamefunctions.course_24 import awards
        tokens_received = awards.get_total_tokens_reward(target)

        # Get skill logs sorted by their running order in the AutoGame
        from gamerules.functions.gamefunctions.course_24 import skills
        #logs = get_forum_logs(target, "Skill Tree")
        logs = skills.get_skill_logs(target)
        logs.sort(key=lambda lg: get_skill_rule_order(lg[config.LOG_DESCRIPTION_COL].replace('Skill Tree, Re: ', '')))
        #print(logs)

        # Organize logs by skill
        logs_by_skill = {}
        for log in logs:
            skill_name = log[config.LOG_DESCRIPTION_COL].replace('Skill Tree, Re: ', '')
            if skill_name in logs_by_skill:
                logs_by_skill[skill_name].append(log)
            else:
                logs_by_skill[skill_name] = [log]
        #print("\nLogs By Skill\n")
        #print(logs_by_skill)
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
                log_type = log[config.LOG_TYPE_COL]
                index = preloaded_logs[target][log_type].index(log)
                preloaded_logs[target][log_type] = preloaded_logs[target][log_type][:index] + preloaded_logs[target][log_type][index + 1:]

                #index = get_logs(target).index(log)
                #preloaded_logs[target] = preloaded_logs[target][:index] + preloaded_logs[target][index + 1:]

        # Sort everything by date again
        for key in preloaded_logs[target]:
            preloaded_logs[target][key].sort(key=lambda lg: lg[config.LOG_DATE_COL])
        #preloaded_logs[target].sort(key=lambda lg: lg[config.LOG_DATE_COL])

### Progression

def clear_progression(targets_ids):
    """
    Clears all progression for given targets before
    calculating again.

    Needs to be refreshed everytime AutoGame runs
    rules for each type of item.
    """

    # Clear badge progression, if badges enabled
    if "Badges" in modules_enabled:
        from gamerules.functions.gamefunctions.course_24 import badges
        badges.clear_badge_progression(targets_ids)

    # Clear skill progression, if skills enabled
    if "Skills" in modules_enabled:
        from gamerules.functions.gamefunctions.course_24 import skills
        skills.clear_skill_progression(targets_ids)

    # Clear streak progression, if streaks enabled
    if "Streaks" in modules_enabled:
        from gamerules.functions.gamefunctions.course_24 import streaks
        streaks.clear_streak_progression(targets_ids)

def update_progression():
    """
    Updates all progression in bulk.
    """
    global modules_enabled

    if not config.TEST_MODE:
        # Update badge progression, if badges enabled
        if "Badges" in modules_enabled:
            from gamerules.functions.gamefunctions.course_24 import badges
            badges.update_badge_progression()

        # Update skill progression, if skills enabled
        if "Skills" in modules_enabled:
            from gamerules.functions.gamefunctions.course_24 import skills
            skills.update_skill_progression()

        # Update streak progression, if streaks enabled
        if "Streaks" in modules_enabled:
            from gamerules.functions.gamefunctions.course_24 import streaks
            streaks.update_streak_progression()

def clear_target_preloaded_data(target):
    global preloaded_logs
    global preloaded_spending

    #print(f"Erasing data from {target}")
    preloaded_logs[target] = []
    #preloaded_spending[target] = []

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

def calculate_grade(target):
    """
    Calculates grade for a given target based on awards received.
    """

    # Calculates total XP, if XP enabled
    if "XPLevels" in modules_enabled:
        calculate_xp(target)

        # Calculates total team XP, if teams enabled
        if "Teams" in modules_enabled:
            calculate_team_xp(target)

    # Calculates total tokens, if Virtual Currency enabled
    if "VirtualCurrency" in modules_enabled:
        from gamerules.functions.gamefunctions.course_24 import virtual_currency
        virtual_currency.calculate_tokens(target)

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
    from gamerules.functions.gamefunctions.course_24 import awards
    for a in awards.get_awards(target):
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

    global modules_enabled
    if modules_enabled == []:
        get_modules_enabled()
    return module in modules_enabled

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

    if target is not None:
        if log_type is not None: # target and type
            if log_type in preloaded_logs[target]:
                logs = preloaded_logs[target][log_type]
            else:
                return []
        else: # all logs from target
            logs = [item for key, item in preloaded_logs[target].items()]
    else: # no target
        if evaluator is not None: # looking for peergrade
            if "evaluator" in preloaded_logs[evaluator]:
                logs = preloaded_logs[evaluator]["evaluator"]
            else:
                return []
        else:
            logs = [item for key, item in preloaded_logs[evaluator].items()]

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

def get_peergrading_logs(target, forum=None, thread=None, rating=None):
    """
    Gets peergrading logs for a specific target.

    Options to get logs from a specific forum and/or thread,
    as well as with a certain rating.
    """

    description = None if forum is None else forum + ("," if thread is None else ", Re: " + thread) + "%"
    return get_logs(None, "peergraded post", rating, target, None, None, description)

def get_consecutive_peergrading_logs(logs_from_moodle, logs_from_gc):
    """
    Gets consecutive peergrading logs done by target.
    """
    # Get consecutive peergrading logs
    consecutive_logs = []
    last_peergrading = None

    for peergrade in logs_from_moodle:

        user_id = peergrade[config.LOG_USER_COL]
        evaluator = peergrade[config.LOG_EVALUATOR_COL]
        description = peergrade[config.LOG_DESCRIPTION_COL]
        post_id = peergrade[config.LOG_POST_COL]
        log = get_log_from_gc(logs_from_gc, user_id, evaluator, description, post_id)
        peergraded = True if log is not None else False

        if not peergraded:
            last_peergrading = None
            continue
        if last_peergrading is not None:
            consecutive_logs[-1].append(log)
        else:
            consecutive_logs.append([log])
        last_peergrading = peergraded

    return consecutive_logs

def get_log_from_gc(gc_logs, user_id, evaluator, description, post_id):
    for gc_log in gc_logs:
        if gc_log[config.LOG_USER_COL] == user_id and  gc_log[config.LOG_EVALUATOR_COL] == evaluator and gc_log[config.LOG_DESCRIPTION_COL] == description and gc_log[config.LOG_POST_COL] == post_id:
            return gc_log
    return None

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


### Utils

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
