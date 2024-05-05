from gamerules.connector import gamecourse_connector
from gamerules.connector.db_connector import gc_db
from decorators import rule_effect, rule_function
import config
import importlib
awards = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.awards", package=__package__)

"""
#####################################################
Badges game functions available
#####################################################
"""

@rule_function
def get_total_badge_reward(target):
    """
    :description: Gets total reward for a given target from badges.

    :arg target: Student id from whom to get the total badge reward. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :returns: int
    """

    return awards.get_total_badge_reward(target)

@rule_effect
def award_badge(target, name, lvl, logs, progress=None):
    """
    :description: Awards a given level to a specific target. NOTE: will retract if level changed. Updates award if reward has changed.

    :arg target: Student id to award badge to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Badges's name
    :type name: str

    :arg lvl: Level of the badge to award
    :type lvl: int

    :arg logs: Lists of Logs
    :type logs: list

    :arg progress: Number of events that determine its progress. E.g. could be attendances, points, slides that student has read...etc
    :type progress: int

    :returns: void
    """

    award_badge_to_target(target, name, lvl, logs, progress)

"""
##############################################################################
"""

preloaded_badges = {}
badge_progression = []

def setup_module(target_ids):
    preload_badges()
    clear_badge_progression(target_ids)

def preload_badges():
    query = "SELECT b.name, bl.badge, bl.number, bl.reward, bl.tokens, b.isExtra, bl.goal, b.description, bl.description " \
            "FROM badge_level bl LEFT JOIN badge b on b.id = bl.badge " \
            "WHERE b.course = %s ORDER BY number;" % (config.COURSE)

    table_badge = gc_db.data_broker.get(gc_db, config.COURSE, query)

    for badge_info in table_badge:
        badge_name = badge_info[0].decode()
        badge = (badge_info[1], badge_info[2], badge_info[3], badge_info[4], badge_info[5], badge_info[6], badge_info[7].decode(), badge_info[8].decode())

        if badge_name in preloaded_badges:
            preloaded_badges[badge_name].append(badge)
        else:
            preloaded_badges[badge_name] = [badge]

def get_badges_extra_xp():
    global preloaded_badges
    ids = []
    for key in preloaded_badges:
        if preloaded_badges[key][0][4]:
            ids.append(preloaded_badges[key][0][0])
    return ids

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

def update_badge_progression():
    """
    Updates all badge progression in bulk.
    """

    global badge_progression

    if len(badge_progression) > 0:
        query = "INSERT INTO badge_progression (course, user, badge, participation) VALUES %s;" % ", ".join(badge_progression)
        gc_db.execute_query(query, (), "commit")


def award_badge_to_target(target, name, lvl, logs, progress=None):
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

    # Get badge info
    table_badge = preloaded_badges[name]
    badge_id = table_badge[0][0]

    # Update badge progression
    for log in logs:
        badge_progression.append('(%s, %s, %s, %s)' % (config.COURSE, target, badge_id, log[config.LOG_ID_COL]))

    # Get awards already given
    awards_given = awards.get_awards(target, name + "%", award_type, badge_id)
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
            awards.remove_award(target, award_type, description, badge_id)

            # Remove tokens
            if len(get_awards(target, description, 'tokens', badge_id)) > 0:
                awards.remove_award(target, 'tokens', description, badge_id)

    # Award and/or update badge levels
    for level in range(1, lvl + 1):
        description = get_description(name, level)

        # Calculate reward
        is_extra = table_badge[level - 1][4]
        badge_reward = int(table_badge[level - 1][2])
        award_given = awards.get_award(target, description, award_type, badge_id)
        reward = awards.calculate_extra_credit_reward(target, award_type, badge_reward, award_given[config.AWARD_REWARD_COL] if award_given is not None else 0) if is_extra else badge_reward

        # Award badge
        awards.award(target, award_type, description, reward, badge_id)

        # Award tokens
        badge_tokens = int(table_badge[level - 1][3])
        if "VirtualCurrency" in gamecourse_connector.modules_enabled and badge_tokens > 0:
            from . import virtual_currency
            virtual_currency.award_tokens_to_target(target, description, [level], badge_tokens, badge_id)