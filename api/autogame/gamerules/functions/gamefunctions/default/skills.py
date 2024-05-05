from gamerules.connector import gamecourse_connector
from gamerules.connector.db_connector import gc_db
from decorators import rule_effect, rule_function
import importlib
import config
awards = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.awards", package=__package__)


"""
#####################################################
Skills game functions available
#####################################################
"""

@rule_function
def get_skill_logs(target, name=None, rating=None, only_min_rating=False, only_latest=False):
    """
    :description: Gets skill logs for a specific target. Options to get logs for a specific skill by name,
    as well as with a certain rating. Additional options to get only logs that meet the minimum
    rating, as well as only the latest log for each skill.

    :arg target: Student id from whom to get the skills' logs from. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Name of the skill
    :type name: str

    :arg rating: Sum of points
    :type rating: int

    :arg only_min_rating: Limit search by search for minimum rating only
    :type only_min_rating: bool

    :arg only_latest: Limit search by specifying to look only for latest log
    :type only_latest: bool

    :returns: list
    """

    logs = gamecourse_connector.get_forum_logs(target, "Skill Tree", name, rating)

    # Get only logs that meet the minimum rating
    if only_min_rating:
        # Get min. rating
        global skillMinRating

        # Filter by minimum rating
        logs = [log for log in logs if int(log[config.LOG_RATING_COL]) >= skillMinRating]

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

@rule_function
def get_skill_tier_logs(target, tier, only_min_rating=True, only_latest=True):
    """
    :description: Gets skill tier logs for a specific target. Options to get only logs that meet the minimum rating,
                as well as only the latest log for each skill.

    :arg target: Student id from whom to get the skill tier logs from. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg tier: Id of tier where skill is located
    :type tier: int

    :arg only_min_rating: Limit search by search for minimum rating only
    :type only_min_rating: bool

    :arg only_latest: Limit search by specifying to look only for latest log
    :type only_latest: bool

    :returns: list
    """

    # Get skill names of tier
    skill_names = []
    for name in preloaded_skills_info:
        if preloaded_skills_info[name][8] == (tier - 1):
            skill_names.append(name)

    # Get logs
    logs = []
    for name in skill_names:
        logs += get_skill_logs(target, name, None, only_min_rating, only_latest)
    return logs

@rule_function
def skill_completed(target, name):
    """
    :description: Checks whether a given skill has already been awarded to a specific target.

    :arg target: Student id from whom to check if skill has been awarded to. If no specific student, use 'target'
                 keyword, which refers to the student for whom the rule is being executed.
    :type target: int

    :arg name: Skill's name
    :type name: str

    :returns: bool
    """

    return awards.award_received(target, "skill", name)

@rule_function
def has_wildcard_available(target, skill_tree_id, wildcard_tier):
    """
    Checks whether a given target has wildcards available to use.
    """

    award_type = 'skill'

    # Get all wildcard skill IDs
    global wildscards_ids
    wildcards_ids = [item[0] for item in wildscards_ids if item[1] == skill_tree_id]

    # Get completed skill wildcards
    nr_completed_wildcards = 0
    for a in awards.get_awards(target, None, award_type):
        if a[config.AWARD_INSTANCE_COL] in wildcards_ids:
            nr_completed_wildcards += 1

    # Get used wildcards
    query = "SELECT IFNULL(SUM(aw.nrWildcardsUsed), 0) " \
            "FROM " + awards.get_awards_table() + " a LEFT JOIN award_wildcard aw on a.id = aw.award " \
            "WHERE a.course = %s AND a.user = %s AND a.type = %s;"
    nr_used_wildcards = int(gc_db.execute_query(query, (config.COURSE, target, award_type))[0][0])

    return nr_completed_wildcards > 0 and nr_used_wildcards <= nr_completed_wildcards

@rule_effect
def award_skill(target, name, rating, logs, dependencies=True, use_wildcard=False):
    """
    :description: Awards a given skill to a specific target. Option to spend a wildcard to give award.
                  NOTE: will retract if rating changed. Updates award if reward has changed.

    :arg target: Student id for whom the skills will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Description of award
    :type name: str

    :arg rating: Sum of points to be awarded
    :type rating: int

    :arg logs: Lists of Logs
    :type logs: list

    :arg dependencies: Flag to whether check for dependencies before awarding or not. Default is true
    :type dependencies: bool

    :arg use_wildcard: Flag to whether spend a wildcard when awarding or not. Default is false
    :type use_wildcard: bool

    :returns: void
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
        target_skill_tree_reward = awards.get_total_reward(target, "skill")
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
            raise Exception(
                "Attempt number for target with ID = %s at skill '%s' needs to be bigger than zero." % (target, name))

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

    def get_attempt_description(att):
        attempt_info = " (%s%s attempt)" % (att, "st" if att == 1 else "nd" if att == 2 else "rd" if att == 3 else "th")
        return name + attempt_info

    global skill_progression
    award_type = "skill"

    # Get min. rating
    global skillMinRating
    min_rating = skillMinRating

    # Get skill info
    global preloaded_skills_info
    table_skill = preloaded_skills_info[name]
    skill_id = table_skill[0]

    # Update skill progression
    for log in logs:
        skill_progression.append('(%s, %s, %s, %s)' % (config.COURSE, target, skill_id, log[config.LOG_ID_COL]))

    # Rating is not enough to win the award or dependencies haven't been met
    if rating < min_rating or not dependencies:
        # Get awards already given
        awards_given = awards.get_awards(target, name, award_type, skill_id)
        nr_awards_given = len(awards_given)

        # The rule/data sources have been updated, the 'award' table
        # has a skill award attributed which is longer valid.
        # The award no longer valid must be deleted
        if nr_awards_given > 0:
            # Delete invalid award
            # NOTE: wildcards used to pay for skill will be automatically
            #       deleted because of foreign key binding to the award
            awards.remove_award(target, award_type, name, skill_id)

    # Award and/or update skill award
    else:
        # Calculate reward
        is_extra = table_skill[2]
        skill_reward = int(table_skill[1])
        award_given = awards.get_award(target, name, award_type, skill_id)
        reward = awards.calculate_extra_credit_reward(target, award_type, skill_reward, award_given[
            config.AWARD_REWARD_COL] if award_given is not None else 0) if is_extra else skill_reward
        reward = calculate_skill_tree_reward(reward,
                                             award_given[config.AWARD_REWARD_COL] if award_given is not None else 0)

        # Award skill
        awards.award(target, award_type, name, reward, skill_id)

        # Spend wildcard
        if use_wildcard:
            skill_award = awards.get_award(target, name, award_type, skill_id)
            if skill_award is None:
                query = "SELECT id FROM " + awards.get_awards_table() + " " \
                                                                 "WHERE course = %s AND user = %s AND type = %s AND description = %s AND moduleInstance = %s;"
                award_id = gc_db.execute_query(query, (config.COURSE, target, award_type, name, skill_id))[0][0]
            else:
                award_id = skill_award[config.AWARD_ID_COL]
            spend_wildcard(award_id)

    # Spend tokens, if virtual currency enabled
    if "VirtualCurrency" in gamecourse_connector.modules_enabled:
        from . import virtual_currency
        tier_cost_info = {"costType": table_skill[4], "cost": int(table_skill[5]), "increment": int(table_skill[6]),
                          "minRating": int(table_skill[7])}

        nr_attempts = len(logs)
        spending_done = len(virtual_currency.get_spending(target, name + '%'))

        if spending_done > nr_attempts:
            # Remove excess spending
            for attempt in range(nr_attempts + 1, spending_done + 1):
                description = get_attempt_description(attempt)
                virtual_currency.remove_spending(target, description)

        elif dependencies:
            # Perform and/or update spending
            for attempt in range(1, nr_attempts + 1):
                attempt_cost = get_attempt_cost(attempt)
                description = get_attempt_description(attempt)
                virtual_currency.spend_tokens(target, description, attempt_cost)

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
                                if not awards.award_received(target, award_type, dep_name)]

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


"""
##############################################################################
"""

preloaded_skills_info = {}
skill_progression = []
wildscards_ids = []
skillMinRating = None

def setup_module(target_ids):
    preload_skills_info()
    preload_wildcard_skills()
    clear_skill_progression(target_ids)

    global skillMinRating
    query = "SELECT minRating FROM skills_config WHERE course = %s;" % config.COURSE
    skillMinRating = int(gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0])

def preload_skills_info():
    global preloaded_skills_info
    query = "SELECT s.name, s.id, t.reward, s.isExtra, st.maxReward, tc.costType, tc.cost, tc.increment, tc.minRating, t.position " \
            "FROM skill s LEFT JOIN skill_tier t on s.tier = t.id " \
            "LEFT JOIN skill_tier_cost tc on tc.tier = t.id " \
            "LEFT JOIN skill_tree st on t.skillTree = st.id " \
            "WHERE s.course = %s;" % (config.COURSE)
    table_skills = gc_db.data_broker.get(gc_db, config.COURSE, query)
    for skill_info in table_skills:
        skill_name = skill_info[0].decode()
        skill_info = [skill_info[1], skill_info[2], skill_info[3], skill_info[4], skill_info[5], skill_info[6], skill_info[7], skill_info[8], skill_info[9]]
        preloaded_skills_info[skill_name] = skill_info


def preload_wildcard_skills():
    global wildscards_ids

    query = "SELECT s.id, t.skillTree FROM skill s LEFT JOIN skill_tier t on s.tier = t.id " \
            "WHERE s.course = %s AND t.name = 'Wildcard' AND t.isActive = True AND s.isActive = True;" \
            % (config.COURSE)
    result = gc_db.data_broker.get(gc_db, config.COURSE, query)
    for res in result:
        wildcard_id = res[0]
        skill_tree_id = res[1]
        wilcards_ids.append((wildcard_id, skill_tree_id))

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


def update_skill_progression():
    """
    Updates all skill progression in bulk.
    """

    global skill_progression

    if len(skill_progression) > 0:
        query = "INSERT INTO skill_progression (course, user, skill, participation) VALUES %s;" % ", ".join(skill_progression)
        gc_db.execute_query(query, (), "commit")

def get_skills_extra_xp():
    global preloaded_skills_info
    ids = []
    for key in preloaded_skills_info:
        if preloaded_skills_info[key][2]:
            ids.append(preloaded_skills_info[key][0])
    return ids