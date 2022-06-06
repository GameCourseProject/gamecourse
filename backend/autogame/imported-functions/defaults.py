#!/usr/bin/env python
# -*- coding: utf-8 -*-
import sys
import datetime
from decorators import rule_function
from gamerules.connector import gamecourse_connector as connector


@rule_function
def compute_lvl(val, *lvls):
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

    if len(lvls) == 0:  # no level specified?
        return 0  # then return the least lvl specified
    if isinstance(lvls[0], (tuple, list)):
        lvls = lvls[0]
    index = len(lvls) - 1
    while index >= 0:
        if val >= lvls[index]:
            return index + 1
        index -= 1
    return 0


@rule_function
def compute_rating(logs):
    """
    Sums the ratings of a series of logs
    """
    rating = 0
    for logline in logs:
        rating += logline.rating
    return rating


@rule_function
def get_rating(logs):
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
            if int(line.description) in tier_labs and int(line.rating) >= tier:
                filtered.append(line)

    return filtered


@rule_function
def filter_skills(logs):
    """
    Filters the list of logs to only keep
    skill graded post
    """
    desc = "Skill Tree"
    filtered_logs = []

    for line in logs:
        post = line.description
        if post.startswith(desc):
            filtered_logs.append(line)
    return filtered_logs


@rule_function
def award_prize(target, reward_name, xp, contributions=None):
    """
    Awards a prize called "reward_name" of "xp" points to students.
    """
    connector.award_prize(target, reward_name, xp, contributions)
    return


@rule_function
def award_tokens(target, reward_name, tokens=None, contributions=None):
    """
    Awards tokens to students.
    """
    connector.award_tokens(target, reward_name, tokens, contributions)
    return


@rule_function
def award_tokens_type(target, type, element_name):
    """
    Awards tokens to students based on an award given.
    """
    connector.award_tokens_type(target, type, element_name)
    return


@rule_function
def award_badge(target, badge, lvl, contributions=None, info=None):
    """
    Awards a Badge type award called "badge" to "target". The "lvl" argument represents the level that can be attributed
    to a given student (used in conjunction with compute_lvl). The "contributions" parameter should receive the participations
    that justify the attribution of the badge for a given target.
    """
    result = connector.award_badge(target, badge, lvl, contributions, info)
    return result


@rule_function
def award_skill(target, skill, rating, contributions=None, use_wildcard=False, wildcard_tier=None):
    """
    Awards a Skill type award called "skill" to "target".
    """
    result = connector.award_skill(target, skill, rating, contributions, use_wildcard, wildcard_tier)
    return result


@rule_function
def award_grade(target, item, contributions=None):
    """
    Awards a grade (XP) to "target". Grades awarded will depend on the logs passed
    in argument "item", which contain the XP reward to be awarded.
    """
    connector.award_grade(target, item, contributions)
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


@rule_function
def award_rating_streak(target, streak, rating, contributions=None, info=None):
    """
    Awards a Streak type rating-related award called "streak" to "target". The "contributions"
    parameter should receive the participations that justify the attribution of the streak for
    a given target.
    """
    result = connector.award_rating_streak(target, streak, rating, contributions, info)
    return result


@rule_function
def award_streak(target, streak, to_award, contributions=None)
    """
    Awards a Streak type award called "streak" to "target". The "contributions" parameter should
    receive the participations that justify the attribution of the streak for a given target.
    """
    result = connector.award_streak(target, streak, to_award, contributions)
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
def get_consecutive_logs(target, streak, contributions, check):
    """
    Checks consecutive logs - mainly based on rating or description.
    """
    connector.get_consecutive_logs(target, streak, contributions, check)
    return

@rule_effect
def get_periodic_logs(target, streak_name, contributions):
    """
    Checks periodic logs - checks periodicity
    """
    connector.get_periodic_logs(target, streak_name, contributions)
    return