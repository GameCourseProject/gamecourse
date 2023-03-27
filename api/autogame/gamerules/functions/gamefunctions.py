#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import config, re

from .utils import rule_effect, rule_function
from gamerules.connector import gamecourse_connector as connector


### ------------------------------------------------------ ###
###	------------------ Regular Functions ----------------- ###
### ------------------------------------------------------ ###

### Getting logs

@rule_function
def get_logs(target=None, log_type=None, rating=None, evaluator=None, start_date=None, end_date=None, description=None):
    """
    Gets all logs under certain conditions.

    Option to get logs for a specific target, type, rating,
    evaluator, and/or description, as well as an initial and/or end date.
    """

    return connector.get_logs(target, log_type, rating, evaluator, start_date, end_date, description)

@rule_function
def get_assignment_logs(target, name=None):
    """
    Gets all assignment logs for a specific target.

    Option to get a specific assignment by name.
    """

    return connector.get_assignment_logs(target, name)

@rule_function
def get_attendance_lab_logs(target, lab_nr=None):
    """
    Gets all lab attendance logs for a specific target.

    Option to get a specific lab by number.
    """

    return connector.get_attendance_lab_logs(target, lab_nr)

@rule_function
def get_attendance_lecture_logs(target, lecture_nr=None):
    """
    Gets all lecture attendance logs for a specific target.

    Option to get a specific lecture by number.
    """

    return connector.get_attendance_lecture_logs(target, lecture_nr)

@rule_function
def get_attendance_lecture_late_logs(target, lecture_nr=None):
    """
    Gets all late lecture attendance logs for a specific target.

    Option to get a specific lecture by number.
    """

    return connector.get_attendance_lecture_late_logs(target, lecture_nr)

@rule_function
def get_forum_logs(target, forum=None, thread=None, rating=None):
    """
    Gets forum logs for a specific target.

    Options to get logs from a specific forum and/or thread,
    as well as with a certain rating.
    """

    return connector.get_forum_logs(target, forum, thread, rating)

@rule_function
def get_lab_logs(target, lab_nr=None):
    """
    Gets all labs logs for a specific target.

    Option to get a specific lab by number.
    """

    return connector.get_lab_logs(target, lab_nr)

@rule_function
def get_page_view_logs(target, name=None):
    """
    Gets all page view logs for a specific target.

    Option to get a specific page view by name.
    """

    return connector.get_page_view_logs(target, name)

@rule_function
def get_participation_lecture_logs(target, lecture_nr=None):
    """
    Gets all lecture participation logs for a specific target.

    Option to get a specific participation by lecture number.
    """

    return connector.get_participation_lecture_logs(target, lecture_nr)

@rule_function
def get_participation_invited_lecture_logs(target, lecture_nr=None):
    """
    Gets all invited lecture participation logs for a specific target.

    Option to get a specific participation by lecture number.
    """

    return connector.get_participation_invited_lecture_logs(target, lecture_nr)

@rule_function
def get_peergrading_logs(target, forum=None, thread=None, rating=None):
    """
    Gets peergrading logs for a specific target.

    Options to get logs from a specific forum and/or thread,
    as well as with a certain rating.
    """

    return connector.get_peergrading_logs(target, forum, thread, rating)

@rule_function
def get_presentation_logs(target):
    """
    Gets presentation logs for a specific target.
    """

    return connector.get_presentation_logs(target)

@rule_function
def get_questionnaire_logs(target, name=None):
    """
    Gets all questionnaire logs for a specific target.

    Option to get a specific questionnaire by name.
    """

    return connector.get_questionnaire_logs(target, name)

@rule_function
def get_quiz_logs(target, name=None):
    """
    Gets all quiz logs for a specific target.

    Option to get a specific quiz by name.
    """

    return connector.get_quiz_logs(target, name)

@rule_function
def get_resource_view_logs(target, name=None, unique=True):
    """
    Gets all resource view logs for a specific target.

    Option to get a specific resource view by name and
    to get only one resource view log per description.
    """

    return connector.get_resource_view_logs(target, name, unique)

@rule_function
def get_skill_logs(target, name=None, rating=None):
    """
    Gets skill logs for a specific target.

    Options to get logs for a specific skill by name,
    as well as with a certain rating.
    """

    return connector.get_skill_logs(target, name, rating)

@rule_function
def get_skill_tier_logs(target, tier, only_min_rating=True, only_latest=True):
    """
    Gets skill tier logs for a specific target.
    """

    return connector.get_skill_tier_logs(target, tier, only_min_rating, only_latest)

@rule_function
def get_url_view_logs(target, name=None):
    """
    Gets all URL view logs for a specific target.

    Option to get a specific URL view by name.
    """

    return connector.get_url_view_logs(target, name)


### Getting consecutive & periodic logs

@rule_function
def get_consecutive_logs(logs):
    """
    Gets consecutive logs on a set of logs.

    The order is defined by the log's 1st number in description:
        > "1" -> order 1
        > "Quiz 1" -> order 1
        > "1 - Quiz" -> order 1
        > "Quiz 1 (22/01/2023)" -> will raise error
        > "1 - Quiz (22/01/2023)" -> will raise error
        > "Quiz (22/01/2023) - 1" -> will raise error
        > "Quiz" -> will raise error
    """

    def find_order(description):
        if description.isnumeric():
            return int(description)

        else:
            log_order = re.findall(r'\d+', description)
            nr_nums = len(log_order)

            if nr_nums == 0:
                raise Exception("Found no possible order for description '%s'." % description)

            if nr_nums > 1:
                raise Exception("Found more than one possible order for description '%s'." % description)

            return int(log_order[0])

    def is_consecutive(o, last_o):
        return last_o is not None and o > last_o and o - last_o == 1

    consecutive_logs = []
    last_order = None

    for log in logs:
        order = find_order(log[config.LOG_DESCRIPTION_COL])
        if is_consecutive(order, last_order):
            consecutive_logs[-1].append(log)
        else:
            consecutive_logs.append([log])
        last_order = order

    return consecutive_logs

@rule_function
def get_consecutive_rating_logs(logs, min_rating=None, max_rating=None, exact_rating=None):
    """
    Gets consecutive logs on a set of logs that meet
    certain rating specifications.

    Options:
     > min_rating --> rating must be bigger or equal to a value
     > max_rating --> rating must be smaller or equal to a value
     > exact_rating --> rating must be exactly a value
    """

    def is_consecutive(r, last_r):
        return last_r is not None and \
            (last_r >= min_rating and r >= min_rating if min_rating is not None else True) and \
            (last_r <= max_rating and r <= max_rating if max_rating is not None else True) and \
            (last_r == exact_rating and r == exact_rating if exact_rating is not None else True)

    consecutive_logs = []
    last_rating = None

    for log in logs:
        rating = log[config.LOG_RATING_COL]
        if (min_rating is not None and rating < min_rating) or (max_rating is not None and rating > max_rating) or \
                (exact_rating is not None and rating != exact_rating):
            last_rating = None
            continue

        if is_consecutive(rating, last_rating):
            consecutive_logs[-1].append(log)
        else:
            consecutive_logs.append([log])
        last_rating = rating

    return consecutive_logs

@rule_function
def get_consecutive_peergrading_logs(target):
    """
    Gets consecutive peergrading logs done by target.
    """

    return connector.get_consecutive_peergrading_logs(target)

@rule_function
def get_periodic_logs(logs, number, time, log_type):
    """
    Gets periodic logs on a set of logs.

    There are two options for periodicity:
        > absolute -> check periodicity in equal periods,
                    beginning at course start date until end date

        > relative -> check periodicity starting on the
                    first entry for streak
    """

    return connector.get_periodic_logs(logs, number, time, log_type)


### Getting total reward

@rule_function
def get_total_reward(target, award_type=None):
    """
    Gets total reward for a given target.
    Option to filter by a specific award type.
    """

    return connector.get_total_reward(target, award_type)

@rule_function
def get_total_assignment_reward(target):
    """
    Gets total reward for a given target from assignments.
    """

    return connector.get_total_assignment_reward(target)

@rule_function
def get_total_badge_reward(target):
    """
    Gets total reward for a given target from badges.
    """

    return connector.get_total_badge_reward(target)

@rule_function
def get_total_bonus_reward(target):
    """
    Gets total reward for a given target from bonus.
    """

    return connector.get_total_bonus_reward(target)

@rule_function
def get_total_exam_reward(target):
    """
    Gets total reward for a given target from exams.
    """

    return connector.get_total_exam_reward(target)

@rule_function
def get_total_lab_reward(target):
    """
    Gets total reward for a given target from labs.
    """

    return connector.get_total_lab_reward(target)

@rule_function
def get_total_presentation_reward(target):
    """
    Gets total reward for a given target from presentations.
    """

    return connector.get_total_presentation_reward(target)

@rule_function
def get_total_quiz_reward(target):
    """
    Gets total reward for a given target from quizzes.
    """

    return connector.get_total_quiz_reward(target)

@rule_function
def get_total_skill_reward(target):
    """
    Gets total reward for a given target from skills.
    """

    return connector.get_total_skill_reward(target)

@rule_function
def get_total_streak_reward(target):
    """
    Gets total reward for a given target from streaks.
    """

    return connector.get_total_streak_reward(target)

@rule_function
def get_total_tokens_reward(target):
    """
    Gets total reward for a given target from tokens.
    """

    return connector.get_total_tokens_reward(target)


### Filtering logs

@rule_function
def filter_logs(logs, with_descriptions=None, without_descriptions=None, min_rating=None, max_rating=None, exact_rating=None):
    """
    Filters logs by a set of descriptions and/or ratings.
    """

    # Filter by description
    logs = filter_logs_by_description(logs, with_descriptions, without_descriptions)

    # Filter by rating
    logs = filter_logs_by_rating(logs, min_rating, max_rating, exact_rating)

    return logs

@rule_function
def filter_logs_by_description(logs, with_descriptions=None, without_descriptions=None):
    """
    Filters logs by a set of descriptions.
    Ex:
        > filter_logs_by_description(logs, "A") ==> w/ description equal to 'A'
        > filter_logs_by_description(logs, ["A", "B"]) ==> w/ description equal to 'A' or 'B'
        > filter_logs_by_description(logs, None, "A") ==> w/ description not equal to 'A'
        > filter_logs_by_description(logs, None, ["A", "B"]) ==> w/ description not equal to 'A' nor 'B'
    """

    if isinstance(with_descriptions, str):
        with_descriptions = [with_descriptions]

    if isinstance(without_descriptions, str):
        without_descriptions = [without_descriptions]

    return [log for log in logs if
            with_descriptions is not None and log[config.LOG_DESCRIPTION_COL] in with_descriptions or
            without_descriptions is not None and log[config.LOG_DESCRIPTION_COL] not in without_descriptions]

@rule_function
def filter_logs_by_rating(logs, min_rating=None, max_rating=None, exact_rating=None):
    """
    Filters logs by rating.
    """

    return [log for log in logs if
            (int(log[config.LOG_RATING_COL]) >= min_rating if min_rating is not None else True) and
            (int(log[config.LOG_RATING_COL]) <= max_rating if max_rating is not None else True) and
            (int(log[config.LOG_RATING_COL] == exact_rating if exact_rating is not None else True))]


### Computing values

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
        > compute_lvl(val=5) ==> returns 0
        > compute_lvl(val=5, [2,4,6]) ==> returns 2
        > compute_lvl(val=5, (10,5,1)) ==> returns 3
    """

    # No levels specified
    if len(lvls) == 0:
        return 0

    # Levels passed as tuple or list
    if isinstance(lvls[0], (tuple, list)):
        lvls = lvls[0]

    # Find level associated to value
    for i in range(len(lvls) - 1, -1, -1):
        if val >= lvls[i]:
            return i + 1

    return 0

@rule_function
def compute_rating(logs):
    """
    Sums the ratings of a set of logs.
    """

    return sum([log[config.LOG_RATING_COL] for log in logs])


### Utils

@rule_function
def count(collection):
    """
    Just another name for function 'len'.
    """

    return len(collection)

@rule_function
def get_description(log):
    """
    Returns the description of a logline.
    """

    return log[config.LOG_DESCRIPTION_COL]

@rule_function
def get_rating(logs):
    """
    Returns the rating of a set of logs.

    If there are multiple logs, returns the most
    recent rating.

    NOTE: logs are ordered by date ASC.
    """

    nr_logs = len(logs)
    return 0 if nr_logs == 0 else int(logs[nr_logs - 1][config.LOG_RATING_COL])

@rule_function
def skill_completed(target, name):
    """
    Checks whether a given skill has already been awarded
    to a specific target.
    """

    return connector.skill_completed(target, name)

@rule_function
def has_wildcard_available(target, skill_tree_id, wildcard_tier):
    """
    Checks whether a given target has wildcards available to use.
    """

    return connector.has_wildcard_available(target, skill_tree_id, wildcard_tier)


### ------------------------------------------------------ ###
###	---------------- Decorated Functions ----------------- ###
### ------------------------------------------------------ ###

### Awarding items

@rule_effect
def award(target, award_type, description, reward, instance=None, unique=True, award_id=None):
    """
    Awards a single prize to a specific target.

    NOTE: will not retract, but will not award twice if unique.
    Updates award if reward has changed.
    """

    connector.award(target, award_type, description, reward, instance, unique, award_id)

@rule_effect
def award_assignment_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards assignment grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per assignment
     > max_grade --> maximum grade per assignment

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    connector.award_assignment_grade(target, logs, max_xp, max_grade)

@rule_effect
def award_badge(target, name, lvl, logs, progress=None):
    """
    Awards a given level to a specific target.

    NOTE: will retract if level changed.
    Updates award if reward has changed.
    """

    connector.award_badge(target, name, lvl, logs, progress)

@rule_effect
def award_bonus(target, name, logs, reward=None, instance=None, unique=True):
    """
    Awards given bonus to a specific target.

    NOTE: will retract if bonus removed.
    Updates award if reward has changed.
    """

    connector.award_bonus(target, name, logs, reward, instance, unique)

@rule_effect
def award_exam_grade(target, name, logs, reward, max_xp=1, max_grade=1):
    """
    Awards exam grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP for exam
     > max_grade --> maximum grade for exam

    NOTE: will retract if grade removed.
    Updates award if reward has changed.
    """

    connector.award_exam_grade(target, name, logs, reward, max_xp, max_grade)

@rule_effect
def award_lab_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards lab grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per lab
     > max_grade --> maximum grade per lab

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    connector.award_lab_grade(target, logs, max_xp, max_grade)

@rule_effect
def award_post_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards post grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per post
     > max_grade --> maximum grade per post

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    connector.award_post_grade(target, logs, max_xp, max_grade)

@rule_effect
def award_presentation_grade(target, name, logs, max_xp=1, max_grade=1):
    """
    Awards presentation grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP for presentation
     > max_grade --> maximum grade for presentation

    NOTE: will retract if grade removed.
    Updates award if reward has changed.
    """

    connector.award_presentation_grade(target, name, logs, max_xp, max_grade)

@rule_effect
def award_quiz_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards quiz grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per quiz
     > max_grade --> maximum grade per quiz

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    connector.award_quiz_grade(target, logs, max_xp, max_grade)

@rule_effect
def award_skill(target, name, rating, logs, dependencies=True, use_wildcard=False):
    """
    Awards a given skill to a specific target.
    Option to spend a wildcard to give award.

    NOTE: will retract if rating changed.
    Updates award if reward has changed.
    """

    connector.award_skill(target, name, rating, logs, dependencies, use_wildcard)

@rule_effect
def award_streak(target, name, logs):
    """
    Awards a given streak to a specific target.

    NOTE: will retract if streak changed.
    Updates award if reward has changed.
    """

    connector.award_streak(target, name, logs)

@rule_effect
def award_tokens(target, name, logs, reward=None, instance=None, unique=True):
    """
    Awards given tokens to a specific target.

    NOTE: will retract if tokens removed.
    Updates award if reward has changed.
    """

    connector.award_tokens(target, name, logs, reward, instance, unique)


### Spend items

@rule_effect
def spend_tokens(target, name, amount, repetitions=1):
    """
    Spends a single item of a specific target.

    NOTE: will not retract, but will not spend twice if is unique.
    Updates if amount has changed.
    """

    connector.spend_tokens(target, name, amount, repetitions)


### ------------------------------------------------------ ###
###	----------------- GameCourse Wrapper ----------------- ###
### ------------------------------------------------------ ###

@rule_function
def gc(library, name, *args):
    """
    This is a wrapper that handles GameCourse specific functions.

    Every rule with a function that has syntax:
        GC.library.function(arg1, arg2)

    will be mapped on the parser to function:
        gc("library", "function", arg1, arg2)

    This wrapper will handle the function request and pass it to PHP.
    """

    from ..connector.gamecourse_connector import call_gamecourse

    if True:
        data = call_gamecourse(config.COURSE, library, name, list(args))

    return data


### ------------------------------------------------------ ###
###	-------------------- Used in tests ------------------- ###
### ------------------------------------------------------ ###

@rule_function
def effect_unlocked(val, target=None):
    """
    Returns True if the target has unlocked the given effect.
    """

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

@rule_effect
def transform(val):
    """
    Wraps any value into a rule effect, this way it will be
    part of the rule output.
    """

    return val
