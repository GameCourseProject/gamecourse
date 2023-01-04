#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .utils import rule_effect, rule_function
from gamerules.connector import gamecourse_connector as connector
import sys, logging
import config

from datetime import datetime 

## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## Regular Functions
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_function
def count(collection):
    """ just another name for function 'len' """
    return len(collection)

@rule_function
def is_rule_unlocked(name,target=None):
    """ Returns True if the target has unlocked any rule with the given name """
    from ..namespace import rule_system as rs
    if target is None:
        from ..namespace import target as t
        target = t
    try:
        rules = rs.__data__.target_data.(target)
    except Exception:
        return False
    else:
        for rule in rules:
            if rule == name:
                return True
        return False


@rule_function
def effect_unlocked(val,target=None):
    """ Returns True if the target has unlocked the given effect """
    from ..namespace import rule_system as rs
    if target is None:
        from ..namespace import target as t
        target = t
    try:
        to = rs.__data__.target_data.target_outputs(target)
    except Exception:
        return False
    else:
        for output in to:
            for effect in output.effects():
                if effect.val() == val:
                    return True
        return False

@rule_function
def compute_lvl (val, *lvls):
    """
    The purpose of this function is to determine which level a value belongs to.
    It does so by matching the 'val' against the lvl requirements in 'lvls',
    when the 'val' is GREATER OR EQUAL to a lvl requirement it returns that lvl
    EX:
        > compute_lvl(val=5, 2,4,6) ==> returns 2
        > compute_lvl(val=5, 1,5) ==> returns 2
        > compute_lvl(val=5, 10,10,10) ==> returns 0
        > compute_lvl(val=5, 10,5,1) ==> returns 3
        > compute_lvl(val=5 ) ==> returns 0
        > compute_lvl(val=5, [2,4,6]) ==> returns 2
        > compute_lvl(val=5, (10,5,1)) ==> returns 3
    """

    if len(lvls)==0: # no level specified?
        return 0 # then return the least lvl specified
    if isinstance(lvls[0],(tuple,list)):
        lvls = lvls[0]
    index = len(lvls)-1
    while index >= 0:
        if val >= lvls[index]:
            return index+1
        index-= 1
    return 0


@rule_function
def compute_rating_old (logs):
    """
    Sums the ratings of a series of logs
    """
    rating = 0
    for logline in logs:
        rating += logline.rating
    return rating

@rule_function
def compute_rating (logs):
    """
    Sums the ratings of a series of logs
    """
    rating = 0
    for logline in logs:
        rating += logline[4]
    return rating

@rule_function
def get_rating (logs):
    """
    Returns the rating column of a logline
    """
    if len(logs) == 0:
        return 0
    elif len(logs) == 1:
        for logline in logs:
            rating = logline.rating
            return rating
    else:
        ts = datetime.min
        for logline in logs:
            if logline.date > ts:
                rating = logline.rating
                ts = logline.date
        return rating


@rule_function
def get_campus(target):
    """
    Returns the campus of a given student
    """
    result = connector.get_campus(target)
    return result

@rule_function
def get_username(target):
    """
    Returns the username of a given student
    """
    result = connector.get_username(target)
    return result

@rule_function
def get_team(target):
    """
    Returns the team of a given student
    """
    result = connector.get_team(target)
    return result

@rule_function
def get_logs(target, type):
    """
    Returns the logs of a target for a specific
    participation type
    """
    result  = connector.get_logs(target, type)
    return result


@rule_function
def get_graded_skill_logs(target, minRating):
    """
    Returns the logs of a target for a specific
    participation type
    """
    result  = connector.get_graded_skill_logs(target, minRating)
    return result


@rule_function
def get_graded_logs(target, minRating, include_skills):
    """
    Returns the logs of a target for a specific
    participation type
    """
    result  = connector.get_graded_logs(target, minRating, include_skills)
    return result

@rule_function
def consecutive_peergrading(target):
    """
    Returns the username of a given student
    """
    result = connector.consecutive_peergrading(target)
    return result

@rule_function
def get_valid_attempts(target, skill):
    """
    Returns number of valid attempts for a given skill
    """
    result = connector.get_valid_attempts(target, skill)
    return result

@rule_function
def get_new_total(target, validAttempts, rating):
    """
    Checks if user has enough tokens to spend.
    Returns the user's new wallet total.
    """
    (result1, result2) = connector.get_new_total(target, validAttempts, rating)
    return (result1, result2)

@rule_function
def filter_excellence_old(logs, tiers, classes):
    """
    Filters the list of logs in a way that only
    participations within excellence thresholds are
    returned.
    """

    if len(tiers) != len(classes):
        print("ERROR: number of tiers does not match number of classes")

    filtered = []
    for i in range(0, len(classes)):
        tier = tiers[i]
        tier_labs = classes[i]

        for line in logs:
            if int(line.description) in tier_labs and int(line.rating) >= tier:
                filtered.append(line)

    return filtered

@rule_function
def filter_excellence(logs, tiers, classes):
    """
    Filters the list of logs in a way that only
    participations within excellence thresholds are
    returned.
    """

    if len(tiers) != len(classes):
        print("ERROR: number of tiers does not match number of classes")

    filtered = []
    for i in range(0, len(classes)):
        tier = tiers[i]
        tier_labs = classes[i]

        for line in logs:
            if int(line[3]) in tier_labs and int(line[4]) >= tier:
                filtered.append(line)

    return filtered

@rule_function
def filter_quiz_old(logs, desc):
    """
    Filters the list of logs in a way that quiz 9
    is removed
    """

    filtered_logs = []

    for line in logs:
        if line.description != desc or line.description != "Dry Run":
            filtered_logs.append(line)
    return filtered_logs

@rule_function
def filter_quiz(logs, desc):
    """
    Filters the list of logs in a way that quiz 9
    is removed
    """

    filtered_logs = []

    for line in logs:
        if line[3] != desc or line[3] != "Dry Run":
            filtered_logs.append(line)
    return filtered_logs

@rule_function
def filter_skills(logs):
    """
    Filters the list of logs to only keep
    unique skill graded post.
    Avoids multiple posts for the same skill.
    """
    desc = "Skill Tree"
    filtered_logs = []
    already_inserted_skill = []

    for line in logs:
        post = line.description
        if post.startswith(desc):
            if post not in already_inserted_skill:
                already_inserted_skill.append(post)
                filtered_logs.append(line)
    return filtered_logs

@rule_function
def exclude_worst_old(logs, last):
    """
    Will calculate the adjustment for getting rid of the
    worst quiz in the bunch
    """

    worst = int(config.metadata["quiz_max_grade"])
    fix = last

    if len(logs) == 9:
        for line in logs:
            worst = min(int(line.rating), worst)

        if len(last) == 1:
            last_quiz = max(int(last[0].rating) - worst, 0)
            fix[0].rating = last_quiz

    return fix

@rule_function
def exclude_worst(logs, last):
    """
    Will calculate the adjustment for getting rid of the
    worst quiz in the bunch
    """

    worst = int(config.metadata["quiz_max_grade"])
    fix = last

    if len(logs) == 9:
        for line in logs:
            worst = min(int(line[4]), worst)

        if len(last) == 1:
            last_quiz = max(int(last[0][4]) - worst, 0)
            fix[0][4] = last_quiz

    return fix
 

@rule_effect
def print_info(text):
        """
        returns the output of a skill and writes the award to database
        """
        sys.stderr.write(str(text))



## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## Decorated Functions
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_effect
def transform (val):
    """ wraps any value into a rule effect, this way it will be part of the rule
    output
    """
    return val


@rule_effect
def award_badge(target, badge, lvl, contributions=None, info=None):
    """
    returns the output of a badge and writes the award to database
    """
    result = connector.award_badge(target, badge, lvl, contributions, info)
    return result


@rule_effect
def award_skill(target, skill, rating, contributions=None, use_wildcard=False, wildcard_tier=None):
    """
    returns the output of a skill and writes the award to database
    """
    result = connector.award_skill(target, skill, rating, contributions, use_wildcard, wildcard_tier)
    return result

@rule_effect
def award_prize(target, reward_name, xp, contributions=None):
    """
    returns the output of a skill and writes the award to database
    """
    connector.award_prize(target, reward_name, xp, contributions)
    # TODO possible upgrade: returning indicators to include these types of prizes as well
    return

@rule_effect
def award_tokens(target, reward_name, tokens = None, contributions=None):
    """
    Awards tokens to students.
    """
    connector.award_tokens(target, reward_name, tokens, contributions)
    return

@rule_effect
def award_tokens_type(target, type, element_name, to_award):
    """
    Awards tokens to students based on an award given.
    """
    connector.award_tokens_type(target, type, element_name, to_award)
    return

@rule_effect
def award_grade(target, item, contributions=None, extra=None):
    """
    returns the output of a skill and writes the award to database
    """
    connector.award_grade(target, item, contributions, extra)
    # TODO possible upgrade: returning indicators to include these types of prizes as well
    return

@rule_effect
def award_team_grade(target, item, contributions=None, extra=None):
    """
    returns the output of a grade and writes the award to database
    """
    connector.award_team_grade(target, item, contributions, extra)
    # TODO possible upgrade: returning indicators to include these types of prizes as well
    return

@rule_function
def award_quiz_grade(target, contributions=None, xp_per_quiz=1, max_grade=1, ignore_case=None, extra=None):
    """
    Awards a quiz grade (XP) to "target". Grades awarded will depend on the logs passed
    , which contain the XP reward to be awarded.
    """
    connector.award_quiz_grade(target, contributions, xp_per_quiz, max_grade, ignore_case, extra)
    return

@rule_function
def award_post_grade(target, contributions=None, xp_per_post=1, max_grade=1, forum=None):
    """
    Awards a post grade (XP) to "target". Grades awarded will depend on the logs passed
    , which contain the XP reward to be awarded.
    """
    connector.award_post_grade(target, contributions, xp_per_post, max_grade, forum)
    return

@rule_function
def award_assignment_grade(target, contributions=None, xp_per_assignemnt=1, max_grade=1):
    """
    Awards an assignment grade (XP) to "target". Grades awarded will depend on the logs passed
    , which contain the XP reward to be awarded.
    """
    connector.award_assignment_grade(target, contributions, xp_per_assignemnt, max_grade)
    return

@rule_effect
def award_rating_streak(target, streak, rating, contributions=None, info=None):
    """
    returns the output of a streak and writes the award to database
    """
    result = connector.award_rating_streak(target, streak, rating, contributions, info)
    return result

@rule_effect
def award_streak(target, streak, to_award, participations, type=None):
    """
    returns the output of a streak and writes the award to database
    """
    result = connector.award_streak(target, streak, to_award, participations, type)
    return result

@rule_function
def remove_tokens(target, tokens = None, skillName = None, contributions=None):
    """
    Removes tokens for a specific user.
    If tokens are given, simply removes.
    If skillName & contributions are given, removes tokens for skill retries.
    """
    result = connector.remove_tokens(target, tokens, skillName, contributions)
    return result

@rule_function
def update_wallet(target, newTotal, removed, contributions=None):
    """
    Updates 'user_wallet' table with the new total tokens for
    a user.
    """
    result = connector.update_wallet(target, newTotal, removed, contributions)
    return result


@rule_function
def rule_unlocked(name, target):
    """
    Checks if rule was already unlocked by user.
    """
    result = connector.rule_unlocked(name, target)
    return result

@rule_function
def awards_to_give(target, streak_name):
    """
    Checks if rule was already unlocked by user.
    """
    result = connector.awards_to_give(target, streak_name)
    return result

@rule_effect
def get_consecutive_peergrading_logs(target, streak, contributions):
    """
    Checks consecutive peergrader posts.
    """
    connector.get_consecutive_peergrading_logs(target, streak, contributions)
    return

@rule_effect
def get_consecutive_rating_logs(target, streak, type, rating, only_skill_posts):
    """
    Checks consecutive logs - mainly based on rating or description.
    """
    connector.get_consecutive_rating_logs(target, streak, type, rating, only_skill_posts)
    return

@rule_effect
def get_consecutive_logs(target, streak, type):
    """
    Checks consecutive logs - based on description.
    """
    connector.get_consecutive_logs(target, streak, type)
    return

@rule_effect
def get_periodic_logs(target, streak_name, contributions, participationType=None):
    """
    Checks periodic logs - checks periodicity 
    """
    connector.get_periodic_logs(target, streak_name, contributions, participationType)
    return


## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## GameCourse Wrapper Functions
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_function
def gc(library, name, *args):
        # this is a wrapper that handles gamecourse specific functions.
    # every rule with a function that has syntax
    #
    # 	GC.library.function(arg1, arg2)
    #
    # will be mapped on the parser to function
    #
    #	gc("library", "function", arg1, arg2)
    #
    # This wrapper will handle the function request and pass it to php

    from ..connector.gamecourse_connector import get_dictionary, check_dictionary, call_gamecourse

    # TODO fix condition : must check the dictionary first
    #in_dictionary = check_dictionary(library, name)
    #dictionary = get_dictionary()

    if True:
        data = call_gamecourse(config.course, library, name, list(args))

    return data

