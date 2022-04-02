#!/usr/bin/env python3

import socket
import signal, os, sys, logging
import json
import mysql.connector
import time
from datetime import datetime, timedelta

from io import StringIO
from course.student import *
from course.logline import *
import config

from course.award import Award
from course.prize import Prize


from course.coursedata import read_achievements, read_tree
achievements = read_achievements()
tree_awards = read_tree()

#   TODO:
#   - check if dictionary only contains terms that are active for a given course id
#


def get_credentials():
    # -----------------------------------------------------------
    # Read db credentials from file
    # -----------------------------------------------------------
    with open(os.path.join(os.path.dirname(os.path.realpath(__file__)),'credentials.txt'), 'r') as f:
        data = f.readlines()
        if len(data) == 3:
            db = data[0].strip('\n')
            un = data[1].strip('\n')
            pw = data[2].strip('\n')
            return (db, un, pw)


def get_dictionary():
    # -----------------------------------------------------------
    # Pulls all GameCourse dictionary terms from the database.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    # keyword must be queried first so it serves as key for a dictionary
    query = "SELECT keyword, moduleId FROM dictionary WHERE course = %s;"
    course = (config.course,)

    cursor.execute(query, course)
    table = cursor.fetchall()

    cnx.close()

    # results to a query are a list of tuples
    return table


def get_student_numbers(course):
    # -----------------------------------------------------------
    # Returns the student numbers of all active targets
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    query = "SELECT id, studentNumber FROM game_course_user;"

    cursor.execute(query)
    table = cursor.fetchall()
    cnx.close()

    student_numbers = {}

    for i in range(len(table)):
        student_numbers[str(table[i][0])] = str(table[i][1])

    return student_numbers




def course_exists(course):
    # -----------------------------------------------------------
    # Checks course exists and is active
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    query = "SELECT isActive FROM course WHERE id = %s;"
    args = (course,)

    cursor.execute(query, args)
    table = cursor.fetchall()
    cnx.close()

    if len(table) == 1:
        return table[0][0]
    else:
        return False


def check_dictionary(library, function):
    # -----------------------------------------------------------
    # Checks if a function exists in the gamecourse dictionary
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    query = "SELECT keyword, moduleId FROM dictionary WHERE course = %s and library=%s and keyword = %s;"
    args = (config.course, library, function)

    cursor.execute(query, args)
    table = cursor.fetchall()
    cnx.close()

    # returns empty list if term not in dictionary
    # TO DO : also will need to check number of arguments once the schema includes that information

    return len(table) == 1



def get_targets(course, timestamp=None, all_targets=False):
    """
    Returns targets for running rules (students)
    """

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    # query that joins roles with participations
    # select user, role.name from participation left join user_role on participation.user = user_role.id left join role on user_role.role = role.id where participation.course = 1 and role.name="Student";

    if all_targets:
        query = "SELECT user_role.id FROM user_role left join role on user_role.role=role.id WHERE user_role.course =%s AND role.name='Student';"
        cursor.execute(query, (course))
        table = cursor.fetchall()
        cnx.close()

    else:
        if timestamp == None:
            query = "SELECT user FROM participation LEFT JOIN user_role ON participation.user = user_role.id LEFT JOIN role ON user_role.role = role.id WHERE participation.course =%s AND role.name='Student';"
            cursor.execute(query, (course,))

            table = cursor.fetchall()
            cnx.close()

        elif timestamp != None:
            query = "SELECT user FROM participation LEFT JOIN user_role ON participation.user = user_role.id LEFT JOIN role ON user_role.role = role.id WHERE participation.course =%s AND role.name='Student' AND date > %s;"
            cursor.execute(query, (course, timestamp))

            table = cursor.fetchall()
            cnx.close()

    targets = {}
    for line in table:
        (user,) = line
        targets[user] = 1

    return targets


def delete_awards(course):
    # -----------------------------------------------------------
    # Deletes all awards of a given course
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    query = "DELETE FROM award where course = %s;"

    cursor.execute(query, (course,))
    cnx.commit()
    cnx.close()

    return


def count_awards(course):
    # -----------------------------------------------------------
    # Deletes all awards of a given course
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    query = "SELECT count(*) FROM award where course = %s;"

    cursor.execute(query, (course,))
    table = cursor.fetchall()

    cnx.commit()
    cnx.close()

    return table[0][0]


def calculate_xp(course, target):
    # -----------------------------------------------------------
    # Insert current XP values into user_xp table
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    # get max values for each type of award
    query = "SELECT maxReward from skill_tree where course = %s;"
    cursor.execute(query, (course,))
    tree_table = cursor.fetchall()

    query = "SELECT maxBonusReward from badges_config where course = %s;"
    cursor.execute(query, (course,))
    badge_table = cursor.fetchall()

    query = "SELECT maxBonusReward from streaks_config where course = %s;"
    cursor.execute(query, (course))
    streak_table = cursor.fetchall()

    if len(tree_table) == 1:
        max_tree_reward = int(tree_table[0][0])

    if len(badge_table) == 1:
        max_badge_bonus_reward = int(badge_table[0][0])

    if len(streak_table) == 1:
        max_streak_bonus_reward = int(streak_table[0][0])

    # get atributed xp so far

    query = "SELECT sum(reward) from award where course=%s and type=%s and user=%s group by user;"
    cursor.execute(query, (course, "skill", target))
    tree_xp = cursor.fetchall()

    # if query returns empty set
    if len(tree_xp) > 0:
        if tree_xp[0][0] != None:
            user_tree_xp = int(tree_xp[0][0])
        else:
            user_tree_xp = 0

    else:
        user_tree_xp = 0

    query = "SELECT sum(reward) from award where course=%s and type=%s and user=%s group by user;"
    cursor.execute(query, (course, "streak", target))
    streak_xp = cursor.fetchall()

    # if query returns empty set
    if len(streak_xp) > 0:
        if streak_xp[0][0] != None:
            user_streak_xp = int(streak_xp[0][0])
        else:
            user_streak_xp = 0

    else:
        user_streak_xp = 0


    query = "SELECT sum(reward) from award where course=%s and (type !=%s and type !=%s and type !=%s and type !=%s) and user=%s group by user;"
    cursor.execute(query, (course, "badge", "skill", "streak", "tokens" ,target))
    other_xp = cursor.fetchall()

    # if query returns empty set
    if len(other_xp) > 0:
        if other_xp[0][0] != None:
            user_other_xp = int(other_xp[0][0])
        else:
            user_other_xp = 0
    else:
        user_other_xp = 0


    # rewards from badges where isExtra = 1
    query = "SELECT sum(reward) from award left join badge on award.moduleInstance=badge.id where award.course=%s and type=%s and isExtra =%s and user=%s;"
    cursor.execute(query, (course, "badge", True, target))
    badge_xp_extra = cursor.fetchall()

    # if query returns empty set
    if len(badge_xp_extra) > 0:
        if badge_xp_extra[0][0] != None:
            user_badge_xp_extra = int(badge_xp_extra[0][0])
        else:
            user_badge_xp_extra = 0

    else:
        user_badge_xp_extra = 0


    # rewards from badges where isExtra = 0
    query = "SELECT sum(reward) from award left join badge on award.moduleInstance=badge.id where award.course=%s and type=%s and isExtra =%s and user=%s;"
    cursor.execute(query, (course, "badge", False, target))
    badge_xp = cursor.fetchall()

    # if query returns empty set
    if len(badge_xp) > 0:
        if badge_xp[0][0] != None:
            user_badge_xp = int(badge_xp[0][0])
        else:
            user_badge_xp = 0

    else:
        user_badge_xp = 0

    extra = user_streak_xp + user_badge_xp_extra  # streaks are extra
    total_skill_xp = min(user_tree_xp, max_tree_reward)
    total_other_xp = user_other_xp
    #total_streak_xp = min(user_streak_xp, max_streak_bonus_reward)
    #total_badge_extra_xp = min(user_badge_xp_extra, max_badge_bonus_reward)
    total_extra_xp = min(extra, max_badge_bonus_reward)
    total_badge_xp = user_badge_xp

    total_xp = total_badge_xp + total_skill_xp + total_other_xp + total_extra_xp


    query = "SELECT id, max(goal) from level where goal <= %s and course = %s group by id order by number desc limit 1;"
    cursor.execute(query, (total_xp, course))
    level_table = cursor.fetchall()

    current_level = int(level_table[0][0])

    query = "UPDATE user_xp set xp= %s, level=%s where course=%s and user=%s;"
    cursor.execute(query, (total_xp, current_level, course, target))


    cnx.commit()
    cnx.close()


def autogame_init(course):
    # -----------------------------------------------------------
    # Pulls gamerules related info for a given course
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    query = "SELECT * FROM autogame where course = %s;"

    cursor.execute(query, (course,))
    table = cursor.fetchall()

    last_activity = None

    if len(table) == 0:
        # if course does not exist, add to table
        query = "INSERT INTO autogame (course,startedRunning,finishedRunning,isRunning) VALUES(%s, %s, %s, %s);"

        timestamp = datetime.now()
        time = timestamp.strftime("%Y/%m/%d %H:%M:%S")
        # create line as running
        cursor.execute(query, (course, time, time, True))
    else:
        if table[0][3] == True:
            cnx.close()
            is_running = True
            return last_activity, is_running

        last_activity = table[0][1]
        query = "UPDATE autogame SET isRunning=%s WHERE course=%s;"

        cursor.execute(query, (True, course))

    cnx.commit()
    cnx.close()
    return last_activity, False


def autogame_terminate(course, start_date, finish_date):
    # -----------------------------------------------------------
    # Finishes execution of gamerules, sets isRunning to False
    # and notifies server to close the socket
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    if not config.test_mode:
        query = "UPDATE autogame SET startedRunning=%s, finishedRunning=%s, isRunning=%s WHERE course=%s;"
        cursor.execute(query, (start_date, finish_date, False, course))
        cnx.commit()

    query = "SELECT * from autogame WHERE isRunning=%s AND course != %s;"
    cursor.execute(query, (True, 0))
    table = cursor.fetchall()
    cnx.close()


    if len(table) == 0:
        HOST = '127.0.0.1' # The server's hostname or IP address
        PORT = 8004 # The port used by the server

        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

            try:
                s.connect((HOST, PORT))
                s.settimeout(600.0)
                end_message = "end gamerules;"
                s.send(end_message.encode())
                s.send("\n".encode())

            except socket.timeout as e:
                print("\nError: Socket timeout, operation took longer than 3 minutes.")

            except KeyboardInterrupt:
                print("\nInterrupt: You pressed CTRL+C!")
                exit()
    return


def clear_badge_progression(target):
    # -----------------------------------------------------------
    # Clear all badge progression for a given user before
    # calculating new progression. Needs to be refresh everytime
    # the rule system runs.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course

    query = "DELETE from badge_progression where course=%s and user=%s;"
    cursor.execute(query, (course, target))
    cnx.commit()
    cnx.close()


def award_badge(target, badge, lvl, contributions=None, info=None):
    # -----------------------------------------------------------
    # Writes and updates 'award' table with badge levels won by
    # the user. Will retract if rules/participations have been
    # changed.
    # Is also responsible for creating indicators.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "badge"

    achievement = {}

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    query = "SELECT * FROM " + awards_table + " where user = %s AND course = %s AND description like %s AND type=%s;"

    badge_name = badge + "%"
    cursor.execute(query, (target, course, badge_name, typeof))
    table = cursor.fetchall()


    # get badge info
    query = "SELECT number, badgeId, reward from badge_level left join badge on badge.id = badge_level.badgeId where badge.course = %s and badge.name = %s order by number;"
    cursor.execute(query, (course, badge))
    table_badge = cursor.fetchall()

    if not config.test_mode:
        # update the badge_progression table with the current status
        if contributions != None:
            if len(contributions) > 0:
                badgeid = table_badge[0][1]

                for log in contributions:
                    query = "INSERT into badge_progression (course, user, badgeId, participationId) values (%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, badgeid, log.log_id))
                    cnx.commit()


    # Case 0: lvl is zero and there are no lines to be erased
    # Simply return right away
    if lvl == 0 and len(table) == 0:
        return

    # Case 1: no level of this badge has been attributed yet
    # Simply write the badge level(s) to the 'award' table
    elif len(table) == 0:
        # if table is empty, just write
        for level in range(1, lvl+1):
            # if the user doesnt have the respective badge and level, adds it
            lvl_info = " (level " + str(level) + ")"
            description = badge + lvl_info

            badge_id = table_badge[level-1][1]
            reward = table_badge[level-1][2]


            query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
            cursor.execute(query, (target, course, description, typeof, badge_id, reward))
            cnx.commit()
            cursor = cnx.cursor(prepared=True)

            # insert in award_participation
            if level == 1 and contributions != None:
                query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                cursor.execute(query, (target, course, description, "badge"))
                table_id = cursor.fetchall()
                award_id = table_id[0][0]

                if not config.test_mode:
                    for el in contributions:
                        participation_id = el.log_id
                        query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                        cursor.execute(query, (award_id, participation_id))
                        cnx.commit()

            if not config.test_mode:
                if contributions != None and contributions != None:
                    nr_contributions = str(len(contributions))
                else:
                    nr_contributions = ''

                config.award_list.append([str(target), str(badge), str(level), nr_contributions])

    # Case 2: the rule/data sources have been updated, the 'award' table
    # has badge levels attributed which are no longer valid. All levels
    # no longer valid must be deleted
    elif len(table) > lvl:
        for diff in range(lvl, len(table)):

            lvl_info = " (level " + str(diff + 1) + ")"
            description = badge + lvl_info

            badge_id = table_badge[diff][1]

            query = "DELETE FROM " + awards_table + " WHERE user = %s AND course = %s AND description = %s AND moduleInstance = %s AND type=%s;"
            cursor.execute(query, (target, course, description, badge_id, typeof))
            cnx.commit()

    # Case 3: there have been new participations, the new levels won
    # by the user must be inserted into table 'award'
    elif len(table) < lvl:
        for diff in range(len(table), lvl):

            lvl_info = " (level " + str(diff + 1) + ")"
            description = badge + lvl_info

            badge_id = table_badge[diff][1]
            reward = table_badge[diff][2]

            query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s, %s);"
            cursor.execute(query, (target, course, description, typeof, badge_id, reward))
            cnx.commit()

            level = diff + 1
            # insert in award_participation
            if level == 1 and contributions != None:
                query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                cursor.execute(query, (target, course, description, "badge"))
                table_id = cursor.fetchall()
                award_id = table_id[0][0]

                if not config.test_mode:
                    for el in contributions:
                        participation_id = el.log_id
                        query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                        cursor.execute(query, (award_id, participation_id))
                        cnx.commit()
    cnx.close()



def award_skill(target, skill, rating, contributions=None, use_wildcard=False, wildcard_tier=None):
    # -----------------------------------------------------------
    # Writes and updates 'award' table with skills completed by
    # the user. Will retract if rules/participations have been
    # changed.
    # -----------------------------------------------------------

    #(username, password) = get_credentials()

    course = config.course
    typeof = "skill"

    if not config.test_mode:
        (database, username, password) = get_credentials()
        cnx = mysql.connector.connect(user=username, password=password,
        host='localhost', database=database)
        cursor = cnx.cursor(prepared=True)

        query = "SELECT * FROM award where user = %s AND course = %s AND description=%s AND type=%s;"
        cursor.execute(query, (target, course, skill, typeof))
        table = cursor.fetchall()


        if use_wildcard != False and wildcard_tier != None:
            # get wildcard tier information
            query = "select t.id from skill_tier t left join skill_tree s on t.treeId=s.id where tier =%s and course = %s;"
            cursor.execute(query, (wildcard_tier, course))
            table_tier = cursor.fetchall()
            if len(table_tier) == 1:
                tier_id = table_tier[0][0]


        query = "SELECT s.id, reward, s.tier FROM skill s join skill_tier on s.tier=skill_tier.tier join skill_tree t on t.id=s.treeId where s.name = %s and course = %s;"
        cursor.execute(query, (skill, course))
        table_skill = cursor.fetchall()

        query = "SELECT COUNT(*) FROM information_schema.TABLES WHERE (TABLE_SCHEMA = 'gamecourse') AND (TABLE_NAME = 'virtual_currency_config');"
        cursor.execute(query)
        table_exists = cursor.fetchall()

        if table_exists[0][0] > 0: # virtual currency is enabled
            tier = table_skill[0][2]

            query = "SELECT skillCost, wildcardCost from virtual_currency_config where course = %s;"
            cursor.execute(query, (course))
            table_currency = cursor.fetchall()
            skillCost = table_currency[0][0]
            wildcardCost = table_currency[0][1]

            query = "SELECT tokens FROM user_wallet where user = %s AND course = %s;"
            cursor.execute(query, (target, course))
            table_tokens = cursor.fetchall()
            currentTokens = table_tokens[0][0]

            # gets all submissions from participation
            query = "SELECT * FROM participation where user = %s AND course = %s AND type='graded post' AND description = %s AND rating >2;"
            cursor.execute(query, (target, course, 'Skill Tree, Re: ' + skill))
            table_counter_participations = cursor.fetchall()

            if len(table_counter_participations) == 0 or len(table_counter_participations) == 1:
                removed = 0
            elif len(table_counter_participations) > 1:
                # 1st = 10, 2nd = 20, 3rd = 40, 4th = 80, ... , n = pow(2, validLogs - 2) * skillCost
                removed = pow(2, len(table_counter_participations) - 2 ) * skillCost

            if len(tier) == 8:
                if len(table) == 0:
                    removed  = wildcardCost
                    newTotal = currentTokens - wildcardCost
                else:
                    newTotal = currentTokens - removed
            else:
                newTotal = currentTokens - removed


        # If rating is not enough to win the award, return
        if rating < 3 and len(table) == 0:
            return

        # If this skill has not been awarded to this user
        # and rating is greater or equal to 3, award skill

        # first skill awarded cost 0 tokens
        elif len(table) == 0:

            skill_id, skill_reward = table_skill[0][0], table_skill[0][1]
            if table_exists[0][0] > 0: # virtual currency is enabled
                if newTotal >= 0:
                    query = "INSERT INTO award (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s, %s);"
                    cursor.execute(query, (target, course, skill, typeof, skill_id, skill_reward))

                    config.award_list.append([str(target), "Skill Tree", str(skill_reward), skill])

                    query = "SELECT id from award where user = %s AND course = %s AND description=%s AND type=%s;"
                    cursor.execute(query, (target, course, skill, typeof))
                    table_id = cursor.fetchall()
                    award_id = table_id[0][0]
                    # contributions is always len == 1, ensured by getSkillParticipations
                    participation_id = contributions[0].log_id

                    query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                    cursor.execute(query, (award_id, participation_id))
                    cnx.commit()

                    participation_ID = table_counter_participations[-1][0]

                    query = "INSERT INTO remove_tokens_participation (course, user, participation, tokensRemoved) VALUES(%s, %s, %s, %s); "
                    cursor.execute(query, (course, target, participation_ID, removed))
                    cnx.commit()

                    # simply remove the tokens
                    query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
                    cursor.execute(query, (newTotal, course, target))
                    cnx.commit()


                    if use_wildcard != False and wildcard_tier != None:
                        # insert into wildcard table
                        query = "INSERT INTO award_wildcard (awardId, tierId) VALUES (%s,%s);"
                        cursor.execute(query, (award_id, tier_id))
                        cnx.commit()
            else:
                query = "INSERT INTO award (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s, %s);"
                cursor.execute(query, (target, course, skill, typeof, skill_id, skill_reward))

                config.award_list.append([str(target), "Skill Tree", str(skill_reward), skill])

                query = "SELECT id from award where user = %s AND course = %s AND description=%s AND type=%s;"
                cursor.execute(query, (target, course, skill, typeof))
                table_id = cursor.fetchall()
                award_id = table_id[0][0]
                # contributions is always len == 1, ensured by getSkillParticipations
                participation_id = contributions[0].log_id

                query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                cursor.execute(query, (award_id, participation_id))
                cnx.commit()

                if use_wildcard != False and wildcard_tier != None:
                    # insert into wildcard table
                    query = "INSERT INTO award_wildcard (awardId, tierId) VALUES (%s,%s);"
                    cursor.execute(query, (award_id, tier_id))
                    cnx.commit()

        # If skill has already been awarded to used
        # compare ratings given before and now
        elif len(table) == 1:
            # If new rating is lesser than 3
            # delete the awarded skill
            if rating < 3:
                query = "DELETE FROM award WHERE user = %s AND course = %s AND description = %s AND type=%s;"
                cursor.execute(query, (target, course, skill, typeof))
            else:
                if table_exists[0][0] > 0 and newTotal >= 0:  # virtual currency is enabled and user has enough tokens

                    participation_ID = table_counter_participations[-1][0]

                    query = "SELECT participation from remove_tokens_participation where user = %s AND course = %s ;"
                    cursor.execute(query, (target, course))
                    table_removed = cursor.fetchall()

                    exists = 0
                    for i in range(len(table_removed)):
                        if table_removed[i][0] == participation_ID:
                            exists = 1
                            break

                    if exists == 0:
                        query = "INSERT INTO remove_tokens_participation (course, user, participation, tokensRemoved) VALUES(%s, %s, %s, %s); "
                        cursor.execute(query, (course, target, participation_ID, removed))
                        cnx.commit()

                        # simply remove the tokens
                        query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
                        cursor.execute(query, (newTotal, course, target))
                        cnx.commit()

            # If new rating is greater or equal to 3
            # no changes to table award, so continue!
            # There might be a change to the award_participation:
            if table_exists[0][0] > 0:
                if contributions[0].rating > table[0][5] and newTotal >= 0:
                    award_id = table[0][0]
                    participation_id = contributions[0].log_id

                    query = "UPDATE award_participation set participation=%s where award=%s;"
                    cursor.execute(query, (participation_id, award_id))

            else:
                if contributions[0].rating > table[0][5]:
                    award_id = table[0][0]
                    participation_id = contributions[0].log_id

                    query = "UPDATE award_participation set participation=%s where award=%s;"
                    cursor.execute(query, (participation_id, award_id))

        else:
            print("ERROR: More than one line for a skill found on the database.")

        cnx.commit()
        cnx.close()



def award_prize(target, reward_name, xp, contributions=None):
    # -----------------------------------------------------------
    # Writes 'award' table with reward that is not a badge or a
    # skill. Will not retract effects, but will not award twice
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "bonus"
    reward = int(xp)

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"

    cursor.execute(query, (target, course, reward_name, typeof))
    table = cursor.fetchall()

    if len(table) == 0:
        # simply award the prize
        query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
        cursor.execute(query, (target, course, reward_name, typeof, reward))
        cnx.commit()

        config.award_list.append([str(target), reward_name, str(reward), ""])


    cnx.close()

    return


def award_tokens(target, reward_name, tokens = None, contributions=None):
    # -----------------------------------------------------------
    # Simply awards tokens.
    # Updates 'user_wallet' table with the new total tokens for
    # a user and registers the award in the 'award' table.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "tokens"
    if tokens != None:
        reward = int(tokens)
    else:
        query = "SELECT initialTokens from virtual_currency_config where course = %s;"
        cursor.execute(query, (course))
        table_currency = cursor.fetchall()
        reward = table_currency[0][0]


    if config.test_mode:
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
        cnx.commit()
        # insert and give the award
        query = "INSERT INTO user_wallet (user, course, tokens) VALUES(%s, %s , %s);"
        cursor.execute(query, (target, course, reward))
        cnx.commit()
    elif len(table) == 0:

        if contributions == None:
            # insert in award
            query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
            cursor.execute(query, (target, course, reward_name, typeof, reward))
            cnx.commit()

            newTotal = reward + table_wallet[0][0]

            # simply award the tokens
            query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
            cursor.execute(query, (newTotal, course, target))
            cnx.commit()
        else:
            for i in range(len(contributions)):
                query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
                cursor.execute(query, (target, course, reward_name, typeof, reward))
                cnx.commit()

            newTotal = table_wallet[0][0] + reward * len(contributions)

            # simply award the tokens
            query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
            cursor.execute(query, (newTotal, course, target))
            cnx.commit()

    elif len(table) > 0 and contributions != None:
         query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"

         query = "SELECT id from award where user = %s AND course = %s AND description=%s AND type=%s;"
         cursor.execute(query, (target, course, reward_name, typeof))
         table_id = cursor.fetchall()
         award_id = table_id[0][0]

         for diff in range(len(table_id), len(contributions)):
             query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
             cursor.execute(query, (target, course, reward_name, typeof, reward))
             cnx.commit()

         dif = len(contributions) - len(table_id)
         newTotal = table_wallet[0][0] + reward * dif
         # simply award the tokens
         query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
         cursor.execute(query, (newTotal, course, target))
         cnx.commit()


    cnx.close()

    return


def award_tokens_type(target, type, tokens, element_name = None, contributions=None):
    # -----------------------------------------------------------
    # To use after awarding users with a streak, skill, ...
    # Checks if type given was awarded and, if it was, updates
    # 'user_wallet' table with the new total tokens for a user
    # and registers the award in the 'award' table.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "tokens"
    reward = int(tokens)

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    if element_name != None:
        query = "SELECT * FROM " + awards_table + " where user = %s AND course = %s AND description LIKE %s AND type=%s;"
        cursor.execute(query, (target, course, element_name + '%', type))
        table_award = cursor.fetchall()
    else:
        query = "SELECT * FROM " + awards_table + " where user = %s AND course = %s AND type=%s;"
        cursor.execute(query, (target, course, type))
        table_award = cursor.fetchall()


    if len(table_award) == 0: # No award was given
        return
    elif len(table_award) > 0: # Award was given

        # nr awards da streak == nr award tokens for completed streak

        name_awarded = element_name + " Completed"
        query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"
        cursor.execute(query, (target, course, name_awarded, typeof))
        table = cursor.fetchall()

        query = "SELECT tokens FROM user_wallet where user = %s AND course = %s;"
        cursor.execute(query, (target, course))
        table_wallet = cursor.fetchall()

        awards = len(table_award)
        for diff in range(len(table), awards):
            query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
            cursor.execute(query, (target, course, name_awarded, typeof, reward))
            cnx.commit()

        name_awarded = element_name + " Completed"
        query = "SELECT moduleInstance FROM " + awards_table + " where user = %s AND course = %s AND description = %s AND type=%s;"
        cursor.execute(query, (target, course, name_awarded, typeof))
        table_after = cursor.fetchall()

        newTotal = table_wallet[0][0] + reward * (len(table_after) - len(table))

        # simply award the tokens
        query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
        cursor.execute(query, (newTotal, course, target))
        cnx.commit()


    cnx.close()

    return

def remove_tokens(target, tokens = None, skillName = None, contributions=None):
    # -----------------------------------------------------------
    # Updates 'user_wallet' table with the new total tokens for
    # a user.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "tokens"

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    query = "SELECT tokens FROM user_wallet where user = %s AND course = %s;"
    cursor.execute(query, (target, course))
    table_tokens = cursor.fetchall()
    currentTokens = table_tokens[0][0]

    query = "SELECT skillCost, wildcardCost, attemptRating, costFormula, incrementCost FROM virtual_currency_config where user = %s AND course = %s;"
    cursor.execute(query, (target, course))
    table_tokens = cursor.fetchall()
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
            newTotal = currentTokens - toRemove

            if newTotal < 0:
                cnx.close()
                return newTotal
            else:
                query = "INSERT INTO remove_tokens_participation (course, user, participation, tokensRemoved) VALUES(%s, %s, %s, %s); "
                cursor.execute(query, (course, target, contributions[0].log_id, toRemove))
                cnx.commit()

                query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
                cursor.execute(query, (toRemove, course, target))
                cnx.commit()

                cnx.close()
                return newTotal

        # else: Tokens have already been removed for that participation
        
    # Remove tokens for skill retry
    elif contributions != None and skillName != None:

        query = "SELECT s.id, s.tier FROM skill s join skill_tier on s.tier=skill_tier.tier join skill_tree t on t.id=s.treeId where s.name = %s and course = %s;"
        cursor.execute(query, (skillName, course))
        table_skill = cursor.fetchall()
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
            cnx.close()
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

            if formula.startswith("Case 0"):
                removed = incrementCost
            elif formula.startswith("Case 1"):
                removed =  incrementCost * validAttempts
            elif formula.startswith("Case 2"):
                if skillCost == 0:
                    n = 2
                else:
                    n = 1
                removed = pow(2, validAttempts - n) * incrementCost

            if validAttempts == 0:
                if tier.decode() =  'Wildcard':
                   removed  = wildcardCost
                else:
                    removed  = skillCost
                newTotal = currentTokens - removed
            else:
                newTotal = currentTokens - removed

            if newTotal >= 0:
                query = "INSERT INTO remove_tokens_participation (course, user, participation, tokensRemoved) VALUES(%s, %s, %s, %s); "
                cursor.execute(query, (course, target, contributions[0].log_id, removed))
                cnx.commit()

                # simply remove the tokens
                query = "UPDATE user_wallet SET tokens=%s WHERE course=%s AND user = %s;"
                cursor.execute(query, (newTotal, course, target))
                cnx.commit()

            cnx.close()
            return newTotal

    cnx.close()
    return


def award_grade(target, item, contributions=None, extra=None):
    # -----------------------------------------------------------
    # Writes 'award' table with reward that is not a badge or a
    # skill. Will not retract effects, but will not award twice
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    if item == "Lab":
        description = "Lab Grade"
        typeof = "labs"
    elif item == "Quiz":
        description = "Quiz Grade"
        typeof = "quiz"
    elif item == "Presentation":
        description = "Presentation Grade"
        typeof = "presentation"

    query = "SELECT moduleInstance, reward FROM " + awards_table + " where user = %s AND course = %s AND type=%s AND description = %s;"
    cursor.execute(query, (target, course, typeof, description))
    table = cursor.fetchall()


    if item == "Presentation":
        for line in contributions:
            grade = int(line.rating)
            if len(table) == 0:
                query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
                cursor.execute(query, (target, course, description, typeof, grade))
                cnx.commit()
                config.award_list.append([str(target), "Grade from " + item, str(grade), ""])

            elif len(table) == 1:
                query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s"
                cursor.execute(query, (grade, course, target, typeof, description))
                cnx.commit()

    elif item == "Quiz" and extra:
        # add last quiz
        if len(contributions) == 1:
            number = int(contributions[0].description.split()[1]) # get the number
            grade = int(contributions[0].rating)
            found = False
            for row in table:
                if row[0] == number:
                    found = True
                    line = row
                    break

            if found:
                # if the line already existed and the new rating is different, update the line
                old_grade = int(line[1])
                if old_grade != grade:
                    query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s AND moduleInstance = %s;"
                    cursor.execute(query, (grade, course, target, typeof, description, number))
                    cnx.commit()

            else:
                # if the line did not exist, add it
                query = "INSERT INTO " + awards_table + " (user, course, description, type, reward, moduleInstance) VALUES(%s, %s , %s, %s, %s, %s);"
                cursor.execute(query, (target, course, description, typeof, grade, number))
                cnx.commit()
                #config.award_list.append([str(target), "Grade from " + item, str(grade), str(number)])


    elif (item == "Quiz" and not extra) or item == "Lab":
        for line in contributions:
            if line.description == "Dry Run":
                continue

            grade = int(line.rating)
            nums = [int(s) for s in line.description.split() if s.isdigit()]
            number = nums[0]

            found = False
            for row in table:
                if row[0] == number:
                    found = True
                    line = row
                    break

            if found:
                # if the line already existed and the new rating is different, update the line
                old_grade = int(line[1])
                if old_grade != grade:
                    query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s AND moduleInstance = %s;"
                    cursor.execute(query, (grade, course, target, typeof, description, number))
                    cnx.commit()

            else:
                # if the line did not exist, add it
                query = "INSERT INTO " + awards_table + " (user, course, description, type, reward, moduleInstance) VALUES(%s, %s , %s, %s, %s, %s);"
                cursor.execute(query, (target, course, description, typeof, grade, number))
                cnx.commit()
                #config.award_list.append([str(target), "Grade from " + item, str(grade), str(number)])

    cnx.close()
    return

def award_quiz_grade(target, contributions=None, xp_per_quiz=1, max_grade=1, ignore_case=None, extra=None):
    # -----------------------------------------------------------
    # Writes 'award' table with reward from a quiz.
    # Will not retract effects, but will not award twice
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    description = "Quiz Grade"
    typeof = "quiz"


    query = "SELECT moduleInstance, reward FROM " + awards_table + " where user = %s AND course = %s AND type=%s AND description = %s;"
    cursor.execute(query, (target, course, typeof, description))
    table = cursor.fetchall()

    if extra:
        # add last quiz
        if len(contributions) == 1:
            number = int(contributions[0].description.split()[1]) # get the number
            grade = (int(contributions[0].rating)/ max_grade) * xp_per_quiz
            found = False
            for row in table:
                if row[0] == number:
                    found = True
                    line = row
                    break

            if found:
                # if the line already existed and the new rating is different, update the line
                old_grade = int(line[1])
                if old_grade != grade:
                    query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s AND moduleInstance = %s;"
                    cursor.execute(query, (grade, course, target, typeof, description, number))
                    cnx.commit()

            else:
                # if the line did not exist, add it
                query = "INSERT INTO " + awards_table + " (user, course, description, type, reward, moduleInstance) VALUES(%s, %s , %s, %s, %s, %s);"
                cursor.execute(query, (target, course, description, typeof, grade, number))
                cnx.commit()
                #config.award_list.append([str(target), "Grade from " + item, str(grade), str(number)])


    else:
        for line in contributions:
            if ignore_case != None and ignore_case in line.description:
                continue

            grade = (int(line.rating) / max_grade) * xp_per_quiz
            nums = [int(s) for s in line.description.split() if s.isdigit()]
            number = nums[0]

            found = False
            for row in table:
                if row[0] == number:
                    found = True
                    line = row
                    break

            if found:
                # if the line already existed and the new rating is different, update the line
                old_grade = int(line[1])
                if old_grade != grade:
                    query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s AND moduleInstance = %s;"
                    cursor.execute(query, (grade, course, target, typeof, description, number))
                    cnx.commit()

            else:
                # if the line did not exist, add it
                query = "INSERT INTO " + awards_table + " (user, course, description, type, reward, moduleInstance) VALUES(%s, %s , %s, %s, %s, %s);"
                cursor.execute(query, (target, course, description, typeof, grade, number))
                cnx.commit()
                #config.award_list.append([str(target), "Grade from " + item, str(grade), str(number)])

    cnx.close()
    return

def award_post_grade(target, contributions=None, xp_per_post=1, max_grade=1, forum=None):
    # -----------------------------------------------------------
    # Writes 'award' table with reward that is a post
    # grade. Will not retract effects, but will not award twice
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "post"
    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    if forum != None:
        description = forum
        query = "SELECT description, reward FROM " + awards_table + " where user = %s AND course = %s AND type = %s AND description = %s;"
        cursor.execute(query, (target, course, typeof, description))
        table = cursor.fetchall()
    else:
        query = "SELECT description, reward FROM " + awards_table + " where user = %s AND course = %s AND type = %s;"
        cursor.execute(query, (target, course, typeof))
        table = cursor.fetchall()

    for line in contributions:
        grade = (int(line.rating) / max_grade) * xp_per_post
        if forum == None:
            description = line.description

        found = False
        for row in table:
            if row[0].decode() == description:
                found = True
                line = row
                break

        if found:
            # if the line already existed and the new rating is different, update the line
            old_grade = int(line[1])
            if old_grade != grade:
                query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s;"
                cursor.execute(query, (grade, course, target, typeof, description))
                cnx.commit()

        else:
            # if the line did not exist, add it
            query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
            cursor.execute(query, (target, course, description, typeof, grade))
            cnx.commit()

    cnx.close()
    return


def award_assignment_grade(target, contributions=None, xp_per_assignemnt=1, max_grade=1):
    # -----------------------------------------------------------
    # Writes 'award' table with reward from assigment grades.
    # Will not retract effects, but will not award twice
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "assignment"
    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    query = "SELECT description, reward FROM " + awards_table + " where user = %s AND course = %s AND type = %s;"
    cursor.execute(query, (target, course, typeof))
    table = cursor.fetchall()


    for line in contributions:
        grade = (int(line.rating) / max_grade) * xp_per_assignemnt
        description = line.description

        found = False
        for row in table:
            if row[0].decode() == description:
                found = True
                line = row
                break

        if found:
            # if the line already existed and the new rating is different, update the line
            old_grade = int(line[1])
            if old_grade != grade:
                query = "UPDATE " + awards_table + " SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s;"
                cursor.execute(query, (grade, course, target, typeof, description))
                cnx.commit()

        else:
            # if the line did not exist, add it
            query = "INSERT INTO " + awards_table + " (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
            cursor.execute(query, (target, course, description, typeof, grade))
            cnx.commit()

    cnx.close()
    return

def clear_streak_progression(target):
      # -----------------------------------------------------------
      # Clear all streak progression for a given user before
      # calculating new progression. Needs to be refresh everytime
      # the rule system runs.
      # -----------------------------------------------------------

      (database, username, password) = get_credentials()
      cnx = mysql.connector.connect(user=username, password=password,
      host='localhost', database=database)
      cursor = cnx.cursor(prepared=True)

      course = config.course

      query = "DELETE from streak_progression where course=%s and user=%s;"
      cursor.execute(query, (course, target))
      cnx.commit()
      cnx.close()

def clear_streak_participations(target):
      # -----------------------------------------------------------
      # Clear all streak progression for a given user before
      # calculating new progression. Needs to be refresh everytime
      # the rule system runs.
      # -----------------------------------------------------------

      (database, username, password) = get_credentials()
      cnx = mysql.connector.connect(user=username, password=password,
      host='localhost', database=database)
      cursor = cnx.cursor(prepared=True)

      course = config.course

      query = "DELETE from streak_participations where course=%s and user=%s;"
      cursor.execute(query, (course, target))
      cnx.commit()
      cnx.close()

def check_periodicity(target, course, participationType, periodicity, periodicityTime, streakid ):
    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "streak"

    # gets date of participations that matter, disgarding submission withtin the same time period
    if len(periodicityTime) == 7:  # minutes - gets all participations
        query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type = %s;"
        cursor.execute(query, (target, course, participationType))
        table_participations = cursor.fetchall()
    elif len(periodicityTime) == 5:  # hours - gets participations with different hours only
        query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type= %s GROUP BY hour(date), day(date) ORDER BY id;"
        cursor.execute(query, (target, course, participationType))
        table_participations = cursor.fetchall()
    elif len(periodicityTime) == 4 or len(periodicityTime) == 6:   # days or weeks - gets only distinct days
        query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type= %s GROUP BY day(date);"
        cursor.execute(query, (target, course, participationType))
        table_participations = cursor.fetchall()
    else:
        return

    size = len(table_participations)

    for i in range(size):
         j = i+1
         if j < size:
            firstParticipationId = table_participations[i][0]  # YYYY-MM-DD HH:MM:SS
            secondParticipationId = table_participations[j][0]

            firstParticipationObj = table_participations[i][1]  # YYYY-MM-DD HH:MM:SS
            secondParticipationObj = table_participations[j][1]

            # if it disrespects streak periodicity, then return
            if len(periodicityTime) == 7:  # minutes
                dif = secondParticipationObj - firstParticipationObj
                margin = 5 # time for any possible delay
                if dif < timedelta(minutes=periodicity-margin) or dif > timedelta(minutes=periodicity+margin):
                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                    cnx.commit()
                else:
                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                    cnx.commit()
                    if j == size-1:
                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                       cnx.commit()

            elif len(periodicityTime) == 5:   # hours
                dif = secondParticipationObj.time().hour - firstParticipationObj.time().hour
                difDay = secondParticipationObj.date() - firstParticipationObj.date()

                if difDay  == timedelta(days=1):
                    sumHours =  secondParticipationObj.time().hour + firstParticipationObj.time().hour
                    limit = 23
                    difLimit = 0

                    if time3.time().hour < limit: # before 23
                        difLimit = limit - time3.time().hour
                        sumHours += difLimit

                    calculatedPeriodicity = (sumHours-limit) + 1 + difLimit
                    if calculatedPeriodicity != periodicity:
                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                        cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                        cnx.commit()
                    else:
                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                        cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                        cnx.commit()

                        if j == size-1:
                           query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                           cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                           cnx.commit()

                elif dif != periodicity:
                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                    cnx.commit()
                else:
                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                    cnx.commit()
                    if j == size-1:
                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                       cnx.commit()

            elif len(periodicityTime) == 4 or len(periodicityTime) == 6:  # days or weeks
                if len(periodicityTime) == 6:
                    periodicityDays = periodicity * 7
                else:
                    periodicityDays = periodicity

                dif = secondParticipationObj.date() - firstParticipationObj.date()
                if dif != timedelta(days=periodicityDays): # dif needs to be equal to periodicity
                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                    cnx.commit()
                else:
                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                    cnx.commit()
                    if j == size-1:
                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                       cnx.commit()
            else:
                return

    query = "SELECT participationId, isValid FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s;"
    cursor.execute(query, (target, course, streakid))
    table_all_participations = cursor.fetchall()

    total = len(table_all_participations)

    for p in range(total):
        participationValid = table_all_participations[p][1]

        if not participationValid:
            for i in range(p-1, -1, -1):
                participationId = table_all_participations[i][0]

                query = "UPDATE streak_participations SET isValid = '0' WHERE user = %s AND course = %s AND streakId = %s AND participationId= %s;"
                cursor.execute(query, (target, course, streakid, participationId))
                cnx.commit()

    query = "SELECT participationId FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s AND isValid = '1';"
    cursor.execute(query, (target, course, streakid))
    table_valid = cursor.fetchall()

    for participation in table_valid:
        participation_id = participation[0]
        query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
        cursor.execute(query, (course, target, streakid, participation_id))
        cnx.commit()



def check_is_consecutive(target, course, participationType, streakid):
    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "streak"

    query = "SELECT id, description FROM participation WHERE user = %s AND course = %s AND type = %s;"
    cursor.execute(query, (target, course, participationType))
    table_participations = cursor.fetchall()

    size = len(table_participations)

    for i in range(size):
        j = i+1
        if j < size:
            firstParticipationId = table_participations[i][0]
            secondParticipationId = table_participations[j][0]

            description1 = int(table_participations[i][1])
            description2 = int(table_participations[j][1])

            if description2 - description1 != 1:
                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                cnx.commit()
            else:
                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                cnx.commit()
                if j == size-1:
                   query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                   cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                   cnx.commit()

    query = "SELECT participationId, isValid FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s;"
    cursor.execute(query, (target, course, streakid))
    table_all_participations = cursor.fetchall()

    total = len(table_all_participations)

    for p in range(total):
        participationValid = table_all_participations[p][1]

        if not participationValid:
            for i in range(p-1, -1, -1):
                participationId = table_all_participations[i][0]

                query = "UPDATE streak_participations SET isValid = '0' WHERE user = %s AND course = %s AND streakId = %s AND participationId= %s;"
                cursor.execute(query, (target, course, streakid, participationId))
                cnx.commit()

    query = "SELECT participationId FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s AND isValid = '1';"
    cursor.execute(query, (target, course, streakid))
    table_valid = cursor.fetchall()

    for participation in table_valid:
        participation_id = participation[0]
        query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
        cursor.execute(query, (course, target, streakid, participation_id))
        cnx.commit()

#  logs-> participations.getParticipations(user,type,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
def award_rating_streak(target, streak, rating, contributions=None, info=None):
    # -----------------------------------------------------------
    # Writes and updates 'award' table with streaks won by the
    # user. Will retract if rules/participations have been
    # changed.
    # Is also responsible for creating indicators.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "streak"

    nlogs = len(contributions)
    if contributions != None:
        participationType = contributions[0].log_type

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    # gets all awards for this user order by descending date (most recent on top)
    query = "SELECT * FROM " + awards_table + " where user = %s AND course = %s AND description like %s AND type=%s;"
    streak_name = streak + "%"
    cursor.execute(query, (target, course, streak_name, typeof))
    table = cursor.fetchall()


    # get streak info
    query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic from streak where course = %s and name = %s;"
    cursor.execute(query, (course, streak))
    table_streak = cursor.fetchall()

    if not config.test_mode:
        if contributions != None:
            # contributions = logs = nr of participations as per say like peergrading posts, skill tree posts, ...
            if len(contributions) > 0:
                streakid, isCount, isPeriodic  = table_streak[0][0], table_streak[0][6], table_streak[0][7]
                periodicity, periodicityTime = table_streak[0][1], table_streak[0][2]

                # if isCount inserts all streak participations in the streak_progression.
                if isCount and not isPeriodic:

                    query = "SELECT id, rating FROM participation WHERE user = %s AND course = %s AND type = %s;"
                    cursor.execute(query, (target, course, participationType))
                    table_participations = cursor.fetchall()

                    size = len(table_participations)

                    for i in range(size):

                        participation = table_participations[i][0]
                        participationRating = table_participations[i][1]

                        if participationRating < rating:
                            query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, participation, '0'))
                            cnx.commit()
                        else:
                            query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, participation, '1'))
                            cnx.commit()


                    query = "SELECT participationId, isValid FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s;"
                    cursor.execute(query, (target, course, streakid))
                    table_all_participations = cursor.fetchall()

                    total = len(table_all_participations)

                    for p in range(total):
                        participationValid = table_all_participations[p][1]

                        if not participationValid:
                            for i in range(p-1, -1, -1):
                                participationId = table_all_participations[i][0]

                                query = "UPDATE streak_participations SET isValid = '0' WHERE user = %s AND course = %s AND streakId = %s AND participationId= %s;"
                                cursor.execute(query, (target, course, streakid, participationId))
                                cnx.commit()

                    query = "SELECT participationId FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s AND isValid = '1';"
                    cursor.execute(query, (target, course, streakid))
                    table_valid = cursor.fetchall()

                    for participation in table_valid:
                        participation_id = participation[0]
                        query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                        cursor.execute(query, (course, target, streakid, participation_id))
                        cnx.commit()

    ###
    # gets all streak progressions
    query = "SELECT * FROM streak_progression where user = %s AND course = %s AND streakId = %s ;"
    cursor.execute(query, (target, course, streakid))
    table_progressions = cursor.fetchall()

    # no valid progressions
    if len(table_progressions) == 0:
        return

    # table contains  user, course, description,  type, reward, date
    # table = filtered awards_table
    elif len(table) == 0:  # no streak has been awarded with this name for this user
        isRepeatable = table_streak[0][5]
        streak_count, streak_reward = table_streak[0][3], table_streak[0][4]

        # if streak is finished, award it
        if len(table_progressions) >= streak_count:

            if not isRepeatable:
                description = streak

                query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                cnx.commit()
                cursor = cnx.cursor(prepared=True)

                # gets award_id
                query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                cursor.execute(query, (target, course, description, typeof))
                table_id = cursor.fetchall()
                award_id = table_id[0][0]

                if not config.test_mode:
                    for el in table_progressions:
                        participation_id = el[3]
                        query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                        cursor.execute(query, (award_id, participation_id))
                        cnx.commit()

            else:
                totalAwards = len(table_progressions) // streak_count

                # inserts in award table the new streaks that have not been awarded
                for diff in range(len(table), totalAwards):
                    repeated_info = " (Repeated for the " + str(diff + 1) + ")"
                    description = streak + repeated_info

                    query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                    cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                    cnx.commit()
                    cursor = cnx.cursor(prepared=True)

                    if diff == 0:
                        if contributions != None:
                            query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                            cursor.execute(query, (target, course, description, typeof))
                            table_id = cursor.fetchall()
                            award_id = table_id[0][0]

                            if not config.test_mode:
                                for el in range(streak_count):
                                    participation_id = table_progressions[el][3]
                                    query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                                    cursor.execute(query, (award_id, participation_id))
                                    cnx.commit()


            if not config.test_mode:
                if contributions != None and contributions != None:
                    nr_contributions = str(len(contributions))
                else:
                    nr_contributions = ''

                config.award_list.append([str(target), str(streak), str(streak_reward), nr_contributions])

    # if this streak has already been awarded, check if it is repeatable to award it again.
    elif len(table) > 0:
        isRepeatable = table_streak[0][5]
        streak_count, streak_reward = table_streak[0][3], table_streak[0][4]

        if isRepeatable and len(table_progressions) > streak_count:

            totalAwards = len(table_progressions) // streak_count

            # inserts in award table the new streaks that have not been awarded
            for diff in range(len(table), totalAwards):
                repeated_info = " (Repeated for the " + str(diff + 1) + ")"
                description = streak + repeated_info

                query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                cnx.commit()
                cursor = cnx.cursor(prepared=True)

                # inserir na award_participation ?
                if contributions != None:
                    query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                    cursor.execute(query, (target, course, description, typeof))
                    table_id = cursor.fetchall()
                    award_id = table_id[0][0]

                    if not config.test_mode:
                        for el in contributions:
                            participation_id = el.log_id
                            query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                            cursor.execute(query, (award_id, participation_id))
                            cnx.commit()

    cnx.commit()
    cnx.close()


#  logs-> participations.getParticipations(user,type,rating,evaluator,initialDate,finalDate,activeUser,activeItem)
def award_streak(target, streak, contributions=None, info=None):
    # -----------------------------------------------------------
    # Writes and updates 'award' table with streaks won by the
    # user. Will retract if rules/participations have been
    # changed.
    # Is also responsible for creating indicators.
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    typeof = "streak"
    #logging.exception(streak)


    nlogs = len(contributions)
    participationType = ''
    if contributions != None and streak != "Grader Extraordinaire":
        participationType = contributions[0].log_type

        
    #logging.exception(participationType)

    if config.test_mode:
        awards_table = "award_test"
    else:
        awards_table = "award"

    # gets all awards for this user order by descending date (most recent on top)
    query = "SELECT * FROM " + awards_table + " where user = %s AND course = %s AND description like %s AND type=%s;"
    streak_name = streak + "%"
    cursor.execute(query, (target, course, streak_name, typeof))
    table = cursor.fetchall()

    # get streak info
    query = "SELECT id, periodicity, periodicityTime, count, reward, isRepeatable, isCount, isPeriodic, isAtMost from streak where course = %s and name = %s;"
    cursor.execute(query, (course, streak))
    table_streak = cursor.fetchall()

    if not config.test_mode:
        if contributions != None:
            # contributions = logs = nr of participations as per say like peergrading posts, skill tree posts, ...
            if len(contributions) > 0:
                streakid, isCount, isPeriodic, isAtMost  = table_streak[0][0], table_streak[0][6], table_streak[0][7], table_streak[0][8]
                periodicity, periodicityTime = table_streak[0][1], table_streak[0][2]

                # if isCount inserts all streak participations in the streak_progression.
                if isCount and not isPeriodic:

                   if participationType.startswith("attended") or participationType.endswith("grade"):
                        #logging.exception("aqui")

                        maxlabs = 125
                        maxlab_impar = 150
                        maxlab_par = 400
                        max_quiz = 1000


                        if participationType.startswith("quiz"):
                            query = "SELECT id, description, rating FROM participation WHERE user = %s AND course = %s AND type = %s AND description like 'Quiz%';"
                            cursor.execute(query, (target, course, participationType))
                            table_participations = cursor.fetchall()
                        else:
                            query = "SELECT id, description, rating FROM participation WHERE user = %s AND course = %s AND type = %s;"
                            cursor.execute(query, (target, course, participationType))
                            table_participations = cursor.fetchall()

                        size = len(table_participations)

                        for i in range(size):
                            j = i+1
                            if j < size:
                                firstParticipationId = table_participations[i][0]
                                secondParticipationId = table_participations[j][0]
                                if participationType == "quiz grade":
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
                                    if participationType == "lab grade":
                                        rating1 = table_participations[i][2]
                                        rating2 = table_participations[j][2]


                                # if participation is not consecutive, inserts in table as invalid
                                if description2 - description1 != 1:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    cnx.commit()
                                else:
                                    # if consecutive, check if rating is max for grades
                                    if participationType == "quiz grade":
                                        if rating1 != max_quiz:
                                            query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                            cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                            cnx.commit()
                                        else:
                                            query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                            cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                            cnx.commit()
                                            if j == size-1:
                                                if rating2 != max_quiz:
                                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                    cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                                    cnx.commit()
                                                else:
                                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                    cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                                    cnx.commit()
                                    elif participationType == "lab grade":
                                        if  description1 == 1 or description1 == 2:
                                            if rating1 != maxlabs:
                                                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                                cnx.commit()
                                            else:
                                                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                                cnx.commit()
                                                if j == size-1:
                                                    if rating2 == maxlabs and description2 == 2:
                                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                                        cnx.commit()
                                                    elif rating2 == maxlab_impar and description2 == 3:
                                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                                        cnx.commit()
                                                    else:
                                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                                        cnx.commit()
                                        elif (description1 % 2 != 0):
                                            if rating1 != maxlab_impar:
                                                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                                cnx.commit()
                                            else:
                                                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                                cnx.commit()
                                                if j == size-1:
                                                    if rating2 != maxlab_par:
                                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                                        cnx.commit()
                                                    else:
                                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                        cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                                        cnx.commit()
                                        elif  (description1 % 2 == 0):
                                            if rating1 != maxlab_par:
                                                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                                cnx.commit()
                                            else:
                                                query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                                cnx.commit()
                                                if j == size-1:
                                                   if rating2 != maxlab_impar:
                                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '0'))
                                                       cnx.commit()
                                                   else:
                                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                                       cnx.commit()
                                    else:
                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                        cnx.commit()
                                        if j == size-1:
                                           query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                           cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                           cnx.commit()

                        query = "SELECT participationId, isValid FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s;"
                        cursor.execute(query, (target, course, streakid))
                        table_all_participations = cursor.fetchall()

                        total = len(table_all_participations)

                        for p in range(total):
                            participationValid = table_all_participations[p][1]

                            if not participationValid:
                                for i in range(p-1, -1, -1):
                                    participationId = table_all_participations[i][0]

                                    query = "UPDATE streak_participations SET isValid = '0' WHERE user = %s AND course = %s AND streakId = %s AND participationId= %s;"
                                    cursor.execute(query, (target, course, streakid, participationId))
                                    cnx.commit()

                        query = "SELECT participationId FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s AND isValid = '1';"
                        cursor.execute(query, (target, course, streakid))
                        table_valid = cursor.fetchall()

                        for participation in table_valid:
                            participation_id = participation[0]
                            query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, participation_id))
                            cnx.commit()

                   elif streak.startswith("Grader"):
                        # contributions - id, timeassigned, expired, ended
                        size = len(contributions)

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

                            query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, id, valid))
                            cnx.commit()


                        query = "SELECT participationId, isValid FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s;"
                        cursor.execute(query, (target, course, streakid))
                        table_all_participations = cursor.fetchall()

                        total = len(table_all_participations)

                        for p in range(total):
                            participationValid = table_all_participations[p][1]

                            if not participationValid:
                                for i in range(p-1, -1, -1):
                                    participationId = table_all_participations[i][0]
                                    query = "UPDATE streak_participations SET isValid = '0' WHERE user = %s AND course = %s AND streakId = %s AND participationId= %s;"
                                    cursor.execute(query, (target, course, streakid, participationId))
                                    cnx.commit()

                        query = "SELECT participationId FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s AND isValid = '1';"
                        cursor.execute(query, (target, course, streakid))
                        table_valid = cursor.fetchall()

                        for participation in table_valid:
                            participation_id = participation[0]
                            query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, participation_id))
                            cnx.commit()


                   else:
                        for log in contributions:
                            query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, log.log_id))
                            cnx.commit()

                # is Count & is Periodic =>  the streak periodicity is between the first participation and the last.
                # example of streak: do 7 tasks in 1 week. We just need to check if the time interval was respected.
                elif isCount and isPeriodic:
                    # gets first streak participation
                    query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND id = %s;  "
                    cursor.execute(query, (target, course, contributions[0].log_id ))
                    table_first_streak = cursor.fetchall()

                    firstParticipationObj = table_first_streak[0][1]  # YYYY-MM-DD HH:MM:SS

                    # gets most recent streak participation
                    query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND id = %s;  "
                    cursor.execute(query, (target, course, contributions[-1].log_id))
                    table_last_streak = cursor.fetchall()

                    secondParticipationObj = table_last_streak[0][1]

                    if len(periodicityTime) == 7:  # minutes
                        dif = secondParticipationObj - firstParticipationObj
                        if dif > timedelta(minutes=periodicity):
                            return
                    elif len(periodicityTime) == 5:   # hours
                        dif = secondParticipationObj - firstParticipationObj
                        if dif > timedelta(hours=periodicity):
                            return
                    elif len(periodicityTime) == 4:   # days
                        dif = secondParticipationObj.date() - firstParticipationObj.date()
                        if dif > timedelta(days=periodicity):
                            return
                    elif len(periodicityTime) == 6:  # weeks_
                        weeksInDays = periodicity*7
                        dif = secondParticipationObj.date() - firstParticipationObj.date()
                        if dif > timedelta(days=weeksInDays):
                           return
                    else:
                        return

                    for log in contributions:
                        query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                        cursor.execute(query, (course, target, streakid, log.log_id))
                        cnx.commit()

                # check periodicity between participations
                elif isPeriodic and not isCount and not isAtMost:
                    #check_periodicity(target, course, participationType, periodicity, periodicityTime, streakid)

                    # gets date of participations that matter, disgarding submission withtin the same time period
                    if len(periodicityTime) == 7:  # minutes - gets all participations
                        query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type = %s;"
                        cursor.execute(query, (target, course, participationType))
                        table_participations = cursor.fetchall()
                    elif len(periodicityTime) == 5:  # hours - gets participations with different hours only
                        query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type= %s GROUP BY hour(date), day(date) ORDER BY id;"
                        cursor.execute(query, (target, course, participationType))
                        table_participations = cursor.fetchall()
                    elif len(periodicityTime) == 4 or len(periodicityTime) == 6:   # days or weeks - gets only distinct days
                        query = "SELECT id, date FROM participation WHERE user = %s AND course = %s AND type= %s GROUP BY day(date);"
                        cursor.execute(query, (target, course, participationType))
                        table_participations = cursor.fetchall()
                    else:
                        return

                    size = len(table_participations)

                    for i in range(size):
                         j = i+1
                         if j < size:
                            firstParticipationId = table_participations[i][0]  # YYYY-MM-DD HH:MM:SS
                            secondParticipationId = table_participations[j][0]

                            firstParticipationObj = table_participations[i][1]  # YYYY-MM-DD HH:MM:SS
                            secondParticipationObj = table_participations[j][1]

                            # if it disrespects streak periodicity, then return
                            if len(periodicityTime) == 7:  # minutes
                                dif = secondParticipationObj - firstParticipationObj
                                margin = 5 # time for any possible delay
                                if dif < timedelta(minutes=periodicity-margin) or dif > timedelta(minutes=periodicity+margin):
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    cnx.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                    cnx.commit()
                                    if j == size-1:
                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                       cnx.commit()

                            elif len(periodicityTime) == 5:   # hours
                                dif = secondParticipationObj.time().hour - firstParticipationObj.time().hour
                                difDay = secondParticipationObj.date() - firstParticipationObj.date()

                                if difDay  == timedelta(days=1):
                                    sumHours =  secondParticipationObj.time().hour + firstParticipationObj.time().hour
                                    limit = 23
                                    difLimit = 0

                                    if time3.time().hour < limit: # before 23
                                        difLimit = limit - time3.time().hour
                                        sumHours += difLimit

                                    calculatedPeriodicity = (sumHours-limit) + 1 + difLimit
                                    if calculatedPeriodicity != periodicity:
                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                        cnx.commit()
                                    else:
                                        query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                        cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                        cnx.commit()

                                        if j == size-1:
                                           query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                           cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                           cnx.commit()

                                elif dif != periodicity:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    cnx.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                    cnx.commit()
                                    if j == size-1:
                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                       cnx.commit()

                            elif len(periodicityTime) == 4 or len(periodicityTime) == 6:  # days or weeks
                                if len(periodicityTime) == 6:
                                    periodicityDays = periodicity * 7
                                else:
                                    periodicityDays = periodicity

                                dif = secondParticipationObj.date() - firstParticipationObj.date()
                                if dif != timedelta(days=periodicityDays): # dif needs to be equal to periodicity
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    cnx.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                    cnx.commit()
                                    if j == size-1:
                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                       cnx.commit()
                            else:
                                return

                    query = "SELECT participationId, isValid FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s;"
                    cursor.execute(query, (target, course, streakid))
                    table_all_participations = cursor.fetchall()

                    total = len(table_all_participations)

                    for p in range(total):
                        participationValid = table_all_participations[p][1]

                        if not participationValid:
                            for i in range(p-1, -1, -1):
                                participationId = table_all_participations[i][0]

                                query = "UPDATE streak_participations SET isValid = '0' WHERE user = %s AND course = %s AND streakId = %s AND participationId= %s;"
                                cursor.execute(query, (target, course, streakid, participationId))
                                cnx.commit()

                    query = "SELECT participationId FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s AND isValid = '1';"
                    cursor.execute(query, (target, course, streakid))
                    table_valid = cursor.fetchall()

                    for participation in table_valid:
                        participation_id = participation[0]
                        query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                        cursor.execute(query, (course, target, streakid, participation_id))
                        cnx.commit()

                elif isPeriodic and isAtMost and not isCount:
                    query = "SELECT id, date, post, description FROM participation WHERE user = %s AND course = %s AND type = %s AND description LIKE 'Skill Tree%' AND rating > 2 ;"
                    cursor.execute(query, (target, course, participationType))
                    table_participations = cursor.fetchall()

                    all = len(table_participations)

                    skills = []

                    filtered = []
                    for i in range(all):
                        name = table_participations[i][3]
                        if name not in skills:
                            skills.append(name)
                            filtered.append(table_participations[i])


                    size = len(filtered)

                    for i in range(size):
                        j = i+1
                        if size == 1:
                            participation_id = filtered[i][0]
                            query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                            cursor.execute(query, (course, target, streakid, participation_id))
                            cnx.commit()
                        if j < size:
                            query = "SELECT streakId FROM streak_progression WHERE user = %s and course = %s and streakId = %s;"
                            cursor.execute(query, (target, course, streakid))
                            table_progressions = cursor.fetchall()

                            if len(table_progressions) == 1:
                                query = "DELETE from streak_progression where course=%s and user=%s and streakId =%s;"
                                cursor.execute(query, (course, target, streakid))
                                cnx.commit()
                                cnx.close()

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


                            query = "SELECT id, date FROM participation WHERE user = %s and course = %s and type = 'peerforum add post' AND post = %s;"
                            cursor.execute(query, (target, course, firstGraded))
                            table_first_date = cursor.fetchall()

                            #logging.exception(table_first_date)
                            #sys.exit(table_first_date[0][0])

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

                            # ************ LETS GO ************** #
                            firstParticipationId = filtered[i][0]  # YYYY-MM-DD HH:MM:SS
                            secondParticipationId = filtered[j][0]

                            firstParticipationObj = table_first_date[0][1]  # YYYY-MM-DD HH:MM:SS
                            secondParticipationObj = table_second_date[0][0]

                            if len(periodicityTime) == 7:  # minutes
                                dif = secondParticipationObj - firstParticipationObj
                                if dif > timedelta(minutes=periodicity):
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    cnx.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                    cnx.commit()
                                    if j == size-1:
                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                       cnx.commit()
                            elif len(periodicityTime) == 5:   # hours
                                dif = secondParticipationObj - firstParticipationObj
                                if dif > timedelta(hours=periodicity):
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    cnx.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                    cnx.commit()
                                    if j == size-1:
                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                       cnx.commit()
                            elif len(periodicityTime) == 4 or len(periodicityTime) == 6:   # days  or weeks
                                if len(periodicityTime) == 6:
                                    periodicityDays = periodicity * 7
                                else:
                                    periodicityDays = periodicity

                                dif = secondParticipationObj.date() - firstParticipationObj.date()
                                if dif > timedelta(days=periodicityDays):
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '0'))
                                    cnx.commit()
                                else:
                                    query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                    cursor.execute(query, (course, target, streakid, firstParticipationId, '1'))
                                    cnx.commit()
                                    if j == size-1:
                                       query = "INSERT into streak_participations (course, user, streakId, participationId, isValid) values (%s,%s,%s,%s,%s);"
                                       cursor.execute(query, (course, target, streakid, secondParticipationId, '1'))
                                       cnx.commit()
                            else:
                                return

                    query = "SELECT participationId, isValid FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s;"
                    cursor.execute(query, (target, course, streakid))
                    table_all_participations = cursor.fetchall()

                    total = len(table_all_participations)

                    for p in range(total):
                        participationValid = table_all_participations[p][1]

                        if not participationValid:
                            for i in range(p-1, -1, -1):
                                query = "UPDATE streak_participations SET isValid = '0' WHERE user = %s AND course = %s AND participationId= %s;"
                                cursor.execute(query, (target, course, table_participations[i][0]))
                                cnx.commit()

                    query = "SELECT participationId FROM streak_participations WHERE user = %s AND course = %s AND streakId= %s AND isValid = '1';"
                    cursor.execute(query, (target, course, streakid))
                    table_valid = cursor.fetchall()

                    for participation in table_valid:
                        participation_id = participation[0]
                        query = "INSERT into streak_progression (course, user, streakId, participationId) values (%s,%s,%s,%s);"
                        cursor.execute(query, (course, target, streakid, participation_id))
                        cnx.commit()

    # gets all streak progressions
    query = "SELECT * FROM streak_progression where user = %s AND course = %s AND streakId = %s ;"
    cursor.execute(query, (target, course, streakid))
    table_progressions = cursor.fetchall()

    # no valid progressions
    if len(table_progressions) == 0:
        return

    # table contains  user, course, description,  type, reward, date
    # table = filtered awards_table
    elif len(table) == 0:  # no streak has been awarded with this name for this user
        isRepeatable = table_streak[0][5]
        streak_count, streak_reward = table_streak[0][3], table_streak[0][4]

        # if streak is finished, award it
        if len(table_progressions) >= streak_count:

            if not isRepeatable:
                description = streak

                query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                cnx.commit()
                cursor = cnx.cursor(prepared=True)

                if not streak.startswith("Grader"):
                    # gets award_id
                    query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                    cursor.execute(query, (target, course, description, typeof))
                    table_id = cursor.fetchall()
                    award_id = table_id[0][0]

                    if not config.test_mode:
                        for el in table_progressions:
                            participation_id = el[3]
                            query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                            cursor.execute(query, (award_id, participation_id))
                            cnx.commit()

            else:
                totalAwards = len(table_progressions) // streak_count

                # inserts in award table the new streaks that have not been awarded
                for diff in range(len(table), totalAwards):
                    repeated_info = " (Repeated for the " + str(diff + 1) + ")"
                    description = streak + repeated_info

                    query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                    cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                    cnx.commit()
                    cursor = cnx.cursor(prepared=True)

                    if diff == 0:
                        if contributions != None:
                            query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                            cursor.execute(query, (target, course, description, typeof))
                            table_id = cursor.fetchall()
                            award_id = table_id[0][0]

                            if not config.test_mode and not streak.startswith("Grader"):
                                for el in range(streak_count):
                                    participation_id = table_progressions[el][3]
                                    query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                                    cursor.execute(query, (award_id, participation_id))
                                    cnx.commit()


            if not config.test_mode:
                if contributions != None and contributions != None:
                    nr_contributions = str(len(contributions))
                else:
                    nr_contributions = ''

                config.award_list.append([str(target), str(streak), str(streak_reward), nr_contributions])



    # if this streak has already been awarded, check if it is repeatable to award it again.
    elif len(table) > 0:
        isRepeatable = table_streak[0][5]
        streak_count, streak_reward = table_streak[0][3], table_streak[0][4]

        if isRepeatable and len(table_progressions) > streak_count:

            totalAwards = len(table_progressions) // streak_count

            # inserts in award table the new streaks that have not been awarded
            for diff in range(len(table), totalAwards):
                repeated_info = " (Repeated for the " + str(diff + 1) + ")"
                description = streak + repeated_info

                query = "INSERT INTO " + awards_table + " (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
                cursor.execute(query, (target, course, description, typeof, streakid, streak_reward))
                cnx.commit()
                cursor = cnx.cursor(prepared=True)

                # inserir na award_participation ?
                if contributions != None:
                    query = "SELECT id from " + awards_table + " where user = %s AND course = %s AND description=%s AND type=%s;"
                    cursor.execute(query, (target, course, description, typeof))
                    table_id = cursor.fetchall()
                    award_id = table_id[0][0]

                    if not config.test_mode and not streak.startswith("Grader"):
                        for el in contributions:
                            participation_id = el.log_id
                            query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
                            cursor.execute(query, (award_id, participation_id))
                            cnx.commit()

    cnx.commit()
    cnx.close()



def get_campus(target):
    # -----------------------------------------------------------
    # Returns the campus of a target user
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    query = "select major from course_user left join game_course_user on course_user.id=game_course_user.id where course = %s and course_user.id = %s;"

    cursor.execute(query, (course, target))
    table = cursor.fetchall()
    cnx.close()

    if len(table) == 1:
        major = table[0][0]
        if major in config.majors_alameda:
            campus = 'A'
        elif major in config.majors_tagus:
            campus = 'T'
        else:
            campus = 'A'

    elif len(table) == 0:
        print("ERROR: No student with given id found in course_user database.")
        campus = None
    else:
        print("ERROR: More than one student with the same id in course_user database.")
        campus = None

    return campus


def get_username(target):
    # -----------------------------------------------------------
    # Returns the username of a target user
    # -----------------------------------------------------------

    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    course = config.course
    query = "select username from auth right join course_user on auth.id=course_user.id where course = %s and auth.id = %s;"

    cursor.execute(query, (course, target))
    table = cursor.fetchall()
    cnx.close()

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

    course = config.course
    query = "select id, timeassigned, expired, ended from mdl_peerforum_time_assigned where component = %s and userid = (select id from mdl_user where username = %s);"
    comp  = 'mod_peerforum'
    cursor.execute(query, (comp, target.decode()))
    table = cursor.fetchall()
    cnx.close()

    return table



def call_gamecourse(course, library, function, args):
    # converts args to json
    args_json = json.dumps(args)


    HOST = '127.0.0.1' # The server's hostname or IP address
    PORT = 8004 # The port used by the server

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

        try:
            while True:
                s.connect((HOST, PORT))
                s.settimeout(50.0)

                # send course number
                s.send(course.encode())
                s.send("\n".encode())
                # send name of parent module
                s.send(library.encode())
                s.send("\n".encode())
                # send name of function to execute

                #sys.stderr.write("\n>>> " + function +"\n")

                s.send(function.encode())
                s.send("\n".encode())
                # send args of function

                s.send(args_json.encode())
                s.send("\n".encode())

                data = s.recv(1024)
                datatype = data.decode()
                # send an ok message for syncing up the socket
                s.send("ok\n".encode())

                # if data received is a collection, it will be sent in chunks
                # as not to break the socket
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

                # if data received is a value (int, float, str, etc)
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
