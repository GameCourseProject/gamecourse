from gamerules.connector import gamecourse_connector
from gamerules.connector.db_connector import gc_db
from decorators import rule_effect, rule_function
import config
import math
from datetime import datetime, timedelta
import importlib
awards = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.awards", package=__package__)

"""
#####################################################
Streak game functions available
#####################################################
"""

@rule_effect
def award_streak(target, name, logs):
    """
    :description: Awards a given streak to a specific target. NOTE: will retract if streak changed. Updates award if reward has changed.

    :arg target: Student id for whom the streaks will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Description of award
    :type name: str

    :arg logs: Lists of Logs
    :type logs: list

    :returns: void
    """

    award_streak_to_target(target, name, logs)

"""
##############################################################################
"""

streak_progression = []
preloaded_streaks = {}


def setup_module():
    global preloaded_streaks
    query = "SELECT name, id, goal, periodicityGoal, periodicityNumber, periodicityTime, periodicityType, reward, tokens, isExtra, isRepeatable " \
            "FROM streak WHERE course = %s;" % (config.COURSE)

    table_streak = gc_db.data_broker.get(gc_db, config.COURSE, query)
    for streak in table_streak:
        streak_name = streak[0].decode()
        streak_id = streak[1]
        goal = int(streak[2])
        period_goal = int(streak[3]) if streak[3] is not None else None
        period_number = int(streak[4]) if streak[4] is not None else None
        period_time = streak[5].decode() if streak[5] is not None else None
        is_periodic = period_number is not None and period_time is not None
        period_type = streak[6].decode() if streak[6] is not None else None
        streak_reward = int(streak[7])
        streak_tokens = int(streak[8])
        is_extra = streak[9]
        is_repeatable = streak[10]

        streak_info = (streak_id, goal, period_goal, period_number, period_time, period_type, is_periodic, streak_reward, streak_tokens, is_extra, is_repeatable)
        preloaded_streaks[streak_name] = streak_info


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


def update_streak_progression():
    """
    Updates all streak progression in bulk.
    """

    global streak_progression

    if len(streak_progression) > 0:
        query = "INSERT INTO streak_progression (course, user, streak, repetition, participation) VALUES %s;" % ", ".join(streak_progression)
        gc_db.execute_query(query, (), "commit")


def award_streak_to_target(target, name, logs):
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

        course_end_date = gamecourse_connector.get_course_dates(config.COURSE)[1]
        dl = gamecourse_connector.get_dates_of_period(last_dt, period_num, period_tm, not period_tp == "absolute")[1]
        return course_end_date if dl >= course_end_date else dl

    global streak_progression
    award_type = "streak"

    if "Streaks" in gamecourse_connector.modules_enabled:
        # Get streak info
        streak = preloaded_streaks[name]
        streak_id = streak[0]
        goal = streak[1]
        period_goal = streak[2]
        period_number = streak[3]
        period_time = streak[4]
        period_type = streak[5]
        is_periodic = streak[6]
        streak_reward = streak[7]
        streak_tokens = streak[8]
        is_extra = streak[9]
        is_repeatable = streak[10]



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
        awards_given = awards.get_awards(target, name + "%", award_type, streak_id)
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
                awards.remove_award(target, award_type, description, streak_id)

                # Remove tokens
                awards.remove_award(target, 'tokens', description, streak_id)

        # Award and/or update streaks
        for repetition in range(1, nr_repetitions + 1):
            description = get_description(name, repetition)

            # Calculate reward
            award_given = awards.get_award(target, description, award_type, streak_id)
            reward = awards.calculate_extra_credit_reward(target, award_type, streak_reward, award_given[config.AWARD_REWARD_COL] if award_given is not None else 0) if is_extra else streak_reward
            #print(f"I'm going to reward {reward} XP")
            # Award streak
            awards.award(target, award_type, description, reward, streak_id)

            # Award tokens
            if "VirtualCurrency" in gamecourse_connector.modules_enabled and streak_tokens > 0:
                from . import virtual_currency
                virtual_currency.award_tokens_to_target(target, description, [repetition], streak_tokens, streak_id)


def get_streaks_extra_xp():
    global preloaded_streaks
    ids = []
    for key in preloaded_streaks:
        if preloaded_streaks[key][9]:
            ids.append(preloaded_streaks[key][0])
    return ids