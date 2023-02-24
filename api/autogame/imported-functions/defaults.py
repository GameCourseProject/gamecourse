#!/usr/bin/env python
# -*- coding: utf-8 -*-
import config

from decorators import rule_function, rule_effect
from gamerules.connector import gamecourse_connector as connector


### ------------------------------------------------------ ###
###	------------------ Regular Functions ----------------- ###
### ------------------------------------------------------ ###

### Getting logs

@rule_function
def get_logs(target=None, type=None, rating=None, evaluator=None, start_date=None, end_date=None, description=None):
    """
    Gets all logs under certain conditions.

    Option to get logs for a specific target, type, rating,
    evaluator, and/or description, as well as an initial and/or end date.
    """

    return connector.get_logs(target, type, rating, evaluator, start_date, end_date, description)

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
def get_participation_lecture_logs(target, lecture_nr):
    """
    Gets all lecture participation logs for a specific target.

    Option to get a specific participation by lecture number.
    """

    return connector.get_participation_lecture_logs(target, lecture_nr)

@rule_function
def get_participation_invited_lecture_logs(target, lecture_nr):
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
def get_resource_view_logs(target, name=None):
    """
    Gets all resource view logs for a specific target.

    Option to get a specific resource view by name.
    """

    return connector.get_resource_view_logs(target, name)

@rule_function
def get_skill_logs(target, name=None, rating=None):
    """
    Gets skill logs for a specific target.

    Options to get logs for a specific skill by name,
    as well as with a certain rating.
    """

    return connector.get_skill_logs(target, name, rating)

@rule_function
def get_skill_tier_logs(target, tier):
    """
    Gets skill tier logs for a specific target.
    """

    return connector.get_skill_tier_logs(target, tier)

@rule_function
def get_url_view_logs(target, name=None):
    """
    Gets all URL view logs for a specific target.

    Option to get a specific URL view by name.
    """

    return connector.get_url_view_logs(target, name)


### Getting total reward

@rule_function
def get_total_reward(target, type):
    """
    Gets total reward for a given target of a specific type.
    """

    return connector.get_total_reward(target, type)

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
def filter_logs(logs, with_descriptions=None, without_descriptions=None, min_rating=None, max_rating=None):
    """
    Filters logs by a set of descriptions and/or ratings.
    """

    # Filter by description
    logs = filter_logs_by_description(logs, with_descriptions, without_descriptions)

    # Filter by rating
    logs = filter_logs_by_rating(logs, min_rating, max_rating)

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
            with_descriptions is not None and log[config.LOG_DESCRIPTION_COL].decode() in with_descriptions or
            without_descriptions is not None and log[config.LOG_DESCRIPTION_COL].decode() not in without_descriptions]

@rule_function
def filter_logs_by_rating(logs, min_rating=None, max_rating=None):
    """
    Filters logs by rating.
    """

    return [log for log in logs if
            (int(log[config.LOG_RATING_COL]) >= min_rating if min_rating is not None else True) and
            (int(log[config.LOG_RATING_COL]) <= max_rating if max_rating is not None else True)]


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
def award(target, type, description, reward, instance=None):
    """
    Awards a single prize to a specific target.

    NOTE: will not retract, but will not award twice.
    Updates award if reward has changed.
    """

    connector.award(target, type, description, reward, instance)

@rule_effect
def award_assignment_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards assignment grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per assignment
     > max_grade ==> max. grade per assignment
    """

    connector.award_assignment_grade(target, logs, max_xp, max_grade)

@rule_effect
def award_badge(target, name, lvl, logs, progress = None):
    """
    Awards a given level to a specific target.

    NOTE: will retract if level changed.
    Updates award if reward has changed.
    """

    connector.award_badge(target, name, lvl, logs, progress)

@rule_effect
def award_bonus(target, name, reward):
    """
    Awards a given bonus to a specific target.
    """

    connector.award_bonus(target, name, reward)

@rule_effect
def award_exam_grade(target, name, reward):
    """
    Awards a given exam grade to a specific target.
    """

    connector.award_exam_grade(target, name, reward)

@rule_effect
def award_lab_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards lab grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per lab
     > max_grade ==> max. grade per lab
    """

    connector.award_lab_grade(target, logs, max_xp, max_grade)

@rule_effect
def award_post_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards post grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per post
     > max_grade ==> max. grade per post
    """

    connector.award_post_grade(target, logs, max_xp, max_grade)

@rule_effect
def award_presentation_grade(target, name, reward):
    """
    Awards a given presentation grade to a specific target.
    """

    connector.award_presentation_grade(target, name, reward)

@rule_effect
def award_quiz_grade(target, logs, max_xp=1, max_grade=1):
    """
    Awards quiz grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp ==> max. XP per quiz
     > max_grade ==> max. grade per quiz
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