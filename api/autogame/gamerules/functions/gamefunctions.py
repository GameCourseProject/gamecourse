#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import config, re

from .utils import rule_effect, rule_function
from gamerules.connector import gamecourse_connector


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

    return gamecourse_connector.get_logs(target, log_type, rating, evaluator, start_date, end_date, description)

@rule_function
def get_forum_logs(target, forum=None, thread=None, rating=None):
    """
    Gets forum logs for a specific target.

    Options to get logs from a specific forum and/or thread,
    as well as with a certain rating.
    """

    return gamecourse_connector.get_forum_logs(target, forum, thread, rating)

@rule_function
def get_peergrading_logs(target, forum=None, thread=None, rating=None):
    """
    Gets peergrading logs for a specific target.

    Options to get logs from a specific forum and/or thread,
    as well as with a certain rating.
    """

    return gamecourse_connector.get_peergrading_logs(target, forum, thread, rating)

@rule_function
def get_resource_view_logs(target, name=None, unique=True):
    """
    Gets all resource view logs for a specific target.

    Option to get a specific resource view by name and
    to get only one resource view log per description.
    """

    return gamecourse_connector.get_resource_view_logs(target, name, unique)

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

    # If not consecutive logs already, put in required format
    if len(logs) > 0 and isinstance(logs[0], tuple):
        logs = [logs]

    for group in logs:
        last_order = None

        # Sort logs by order
        group.sort(key=lambda lg: find_order(lg[config.LOG_DESCRIPTION_COL]))

        for log in group:
            order = find_order(log[config.LOG_DESCRIPTION_COL])
            if is_consecutive(order, last_order):
                consecutive_logs[-1].append(log)
            else:
                consecutive_logs.append([log])
            last_order = order

    return consecutive_logs

@rule_function
def get_consecutive_rating_logs(logs, min_rating=None, max_rating=None, exact_rating=None, custom_rating=None):
    """
    Gets consecutive logs on a set of logs that meet
    certain rating specifications.

    Options:
     > min_rating --> rating must be bigger or equal to a value
     > max_rating --> rating must be smaller or equal to a value
     > exact_rating --> rating must be exactly a value
     > custom_rating --> different ratings based on description
       e.g. {'1': {'min': 100, 'max': None, 'exact': None}, '2': {'min': 100, 'max': None, 'exact': None},
             '3': {'min': 200, 'ma'x': None, 'exact': None}, '4': {'min': 300, 'max': None, 'exact': None}, ...}
    """

    def is_consecutive(r, d, last_r, last_d):
        return last_r is not None and \
            (last_r >= min_rating and r >= min_rating if min_rating is not None else True) and \
            (last_r <= max_rating and r <= max_rating if max_rating is not None else True) and \
            (last_r == exact_rating and r == exact_rating if exact_rating is not None else True) and \
            (last_r >= custom_rating[last_d]['min'] and r >= custom_rating[d]['min'] if custom_rating is not None and 'min' in custom_rating[d] and custom_rating[d]['min'] is not None else True) and \
            (last_r <= custom_rating[last_d]['max'] and r <= custom_rating[d]['max'] if custom_rating is not None and 'max' in custom_rating[d] and custom_rating[d]['max'] is not None else True) and \
            (last_r == custom_rating[last_d]['exact'] and r == custom_rating[d]['exact'] if custom_rating is not None and 'exact' in custom_rating[d] and custom_rating[d]['exact'] is not None else True)

    consecutive_logs = []

    # If not consecutive logs already, put in required format
    if len(logs) > 0 and isinstance(logs[0], tuple):
        logs = [logs]

    # Get consecutive logs
    for group in logs:
        last_description = None
        last_rating = None

        for log in group:
            description = log[config.LOG_DESCRIPTION_COL]
            rating = log[config.LOG_RATING_COL]
            if (min_rating is not None and rating < min_rating) or (max_rating is not None and rating > max_rating) or \
                    (exact_rating is not None and rating != exact_rating) or (custom_rating is not None and (
                    ('min' in custom_rating[description] and custom_rating[description]['min'] is not None and rating < custom_rating[description]['min']) or
                    ('max' in custom_rating[description] and custom_rating[description]['max'] is not None and rating > custom_rating[description]['max']) or
                    ('exact' in custom_rating[description] and custom_rating[description]['exact'] is not None and rating != custom_rating[description]['exact']))):
                last_description = None
                last_rating = None
                continue

            if is_consecutive(rating, description, last_rating, last_description):
                consecutive_logs[-1].append(log)
            else:
                consecutive_logs.append([log])
            last_description = description
            last_rating = rating

    # If last log doesn't fit the specifications, add empty group
    # NOTE: this ensures the progress is 0 instead of the last group's length
    if len(consecutive_logs) > 0 and consecutive_logs[-1][-1][config.LOG_ID_COL] != logs[-1][-1][config.LOG_ID_COL]:
        consecutive_logs.append([])

    return consecutive_logs

@rule_function
def get_consecutive_peergrading_logs(peergrade_assigned, peergrade_done):
    """
    Gets consecutive peergrading logs done by target.
    """

    return gamecourse_connector.get_consecutive_peergrading_logs(peergrade_assigned, peergrade_done)

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

    return gamecourse_connector.get_periodic_logs(logs, number, time, log_type)


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
def get_latest_rating(logs):
    """
    Returns the rating of a set of logs.

    If there are multiple logs, returns the most
    recent rating.

    NOTE: logs are ordered by date ASC.
    """

    nr_logs = len(logs)
    return 0 if nr_logs == 0 else int(logs[nr_logs - 1][config.LOG_RATING_COL])

@rule_function
def get_best_rating(logs):
    """
    Returns the rating of a set of logs.

    If there are multiple logs, returns the
    best rating.
    """

    rating = 0
    for log in logs:
        if int(log[config.LOG_RATING_COL]) > rating:
            rating = int(log[config.LOG_RATING_COL])
    return rating


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
