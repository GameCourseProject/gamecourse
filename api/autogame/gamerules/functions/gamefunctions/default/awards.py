from gamerules.connector import gamecourse_connector
from gamerules.connector.db_connector import gc_db
from decorators import rule_effect, rule_function
import config
import importlib
core_functions = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.core_functions", package=__package__)

from datetime import datetime

"""
#####################################################
Award game functions available
#####################################################
"""

### Awarding items

@rule_effect
def award(target, award_type, description, reward, instance=None, unique=True, award_id=None):
    """
    :description: Awards a single prize to a specific target. NOTE: will not retract, but will not award twice if unique. Updates award if reward has changed.

    :arg target: Student id to award. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg award_type: Type of award to give to target
    :type award_type: str

    :arg description: Description of the award to give
    :type description: str

    :arg reward: Points to award
    :type reward: int

    :arg instance: Id with instance of the award
    :type instance: int

    :arg unique: Flag to not reward in case the award is already been given
    :type unique: bool

    :arg award_id: Id of the award to give
    :type award_id: int

    :returns: void
    """

    award_to_target(target, award_type, description, reward, instance, unique, award_id)

@rule_effect
def award_assignment_grade(target, logs, max_xp=1, max_grade=1):
    """
    :description: Awards assignment grades to a specific target. NOTE: will NOT retract if grade removed. Updates award if reward has changed.

    :arg target: Student id for whom the lab grade will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg logs: List of logs
    :type logs: list

    :arg max_xp: Determines how many XP should be awarded per assignment
    :type max_xp: int

    :arg max_grade: Determines the maximum grade to be awarded per assignment
    :type max_grade: int

    :returns: void
    """

    award_assignment_grade_to_target(target, logs, max_xp, max_grade)

@rule_effect
def award_bonus(target, name, logs, reward=None, instance=None, unique=True):
    """
    :description: Awards given bonus to a specific target. NOTE: will retract if bonus removed. Updates award if reward has changed.

    :arg target: Student id from whom the bonus will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Description of award
    :type name: str

    :arg logs: Lists of Logs
    :type logs: list

    :arg reward: Number of points to reward
    :type reward: int

    :arg instance: Id with instance of the award
    :type instance: int

    :arg unique: Flag to not reward in case the award is already been given
    :type unique: bool

    :returns: void
    """

    award_bonus_to_target(target, name, logs, reward, instance, unique)

@rule_effect
def award_exam_grade(target, name, logs, reward, max_xp=1, max_grade=1):
    """
    :description: Awards exam grades to a specific target. NOTE: will retract if grade removed. Updates award if reward has changed.

    :arg target: Student id for whom the lab grade will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Description of award
    :type name: str

    :arg logs: List of logs
    :type logs: list

    :arg reward: Number of points to reward
    :type reward: int

    :arg max_xp: Determines how many XP should be awarded for the exam
    :type max_xp: int

    :arg max_grade: Determines the maximum grade to be awarded for the exam
    :type max_grade: int

    :returns: void
    """

    award_exam_grade_to_target(target, name, logs, reward, max_xp, max_grade)

@rule_effect
def award_lab_grade(target, logs, max_xp=1, max_grade=1):
    """
    :description: Awards lab grades to a specific target. NOTE: will NOT retract if grade removed. Updates award if reward has changed.

    :arg target: Student id for whom the lab grade will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg logs: List of logs
    :type logs: list

    :arg max_xp: Determines how many XP should be awarded per lab
    :type max_xp: int

    :arg max_grade: Determines the maximum grade to be awarded per lab
    :type max_grade: int

    :returns: void
    """

    award_lab_grade_to_target(target, logs, max_xp, max_grade)

@rule_effect
def award_post_grade(target, logs, max_xp=1, max_grade=1):
    """
    :description: Awards post grades to a specific target. NOTE: will NOT retract if grade removed. Updates award if reward has changed.

    :arg target: Student id for whom the post grade will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg logs: List of logs
    :type logs: list

    :arg max_xp: Determines how many XP should be awarded per post
    :type max_xp: int

    :arg max_grade: Determines the maximum grade to be awarded per post
    :type max_grade: int

    :returns: void
    """

    award_post_grade_to_target(target, logs, max_xp, max_grade)

@rule_effect
def award_presentation_grade(target, name, logs, max_xp=1, max_grade=1):
    """
    :description: Awards presentation grades to a specific target. NOTE: will retract if grade removed. Updates award if reward has changed.

    :arg target: Student id for whom the presentation grade will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Description of award
    :type name: str

    :arg logs: List of logs
    :type logs: list

    :arg max_xp: Determines how many XP should be awarded for the presentation
    :type max_xp: int

    :arg max_grade: Determines the maximum grade to be awarded for the presentation
    :type max_grade: int

    :returns: void
    """

    award_presentation_grade_to_target(target, name, logs, max_xp, max_grade)

@rule_effect
def award_quiz_grade(target, logs, max_xp=1, max_grade=1):
    """
    :description: Awards quiz grades to a specific target. NOTE: will NOT retract if grade removed. Updates award if reward has changed.

    :arg target: Student id for whom the quiz grade will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg logs: List of logs
    :type logs: list

    :arg max_xp: Determines how many XP should be awarded per quiz
    :type max_xp: int

    :arg max_grade: Determines the maximum grade to be awarded per quiz
    :type max_grade: int

    :returns: void
    """

    award_quiz_grade_to_target(target, logs, max_xp, max_grade)

### Getting total reward

@rule_function
def get_total_reward(target, award_type=None):
    """
    :description: Gets total reward for a given target. Option to filter by a specific award type.

    :arg target: Student id from whom to get the total reward. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg award_type: Type of award. E.g. "badges", "assignment"
    :type award_type: str

    :returns: int
    """

    return get_target_total_reward(target, award_type)

"""
##############################################################################
"""
max_xp_config = []
max_xp_by_type_config = {}
preloaded_awards = {}

def setup_module(target_ids):
    preload_awards(targets_ids)
    preload_awards_rewards_config()

def preload_awards(targets_ids):
    """
    Preloads awards for given targets.
    Ensures the database is accessed only once to retrieve awards.
    """

    global preloaded_awards

    # Get awards for targets
    query = "SELECT * FROM " + get_awards_table() + " WHERE course = %s" % config.COURSE
    query += " AND user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    query += " ORDER BY date ASC;"
    awards = gc_db.execute_query(query)

    # Initialize preloaded awards
    preloaded_awards = {target_id: [] for target_id in targets_ids}

    # Organize awards by target
    for a in awards:
        a = (a[config.AWARD_ID_COL],
             a[config.AWARD_USER_COL], # can remove
             a[config.AWARD_COURSE_COL], # can remove
             a[config.AWARD_DESCRIPTION_COL].decode(),
             a[config.AWARD_TYPE_COL].decode(),
             a[config.AWARD_INSTANCE_COL],
             a[config.AWARD_REWARD_COL],
             a[config.AWARD_DATE_COL])

        target_id = a[config.AWARD_USER_COL]
        if target_id in preloaded_awards:
            preloaded_awards[target_id].append(a)
        else:
            preloaded_awards[target_id] = [a]

def preload_awards_rewards_config():
    global max_xp_config

    query = "SELECT maxXP, maxExtraCredit FROM xp_config WHERE course = %s;" % config.COURSE
    result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0]
    max_xp_config = [result[0], result[1]]

### Awards

def get_awards_table():
    """
    Gets awards table for the current AutoGame mode.
    """

    return "award" if not config.TEST_MODE else "award_test"

def get_award(target, description=None, award_type=None, instance=None, reward=None, award_id=None):
    """
    Gets an award for a specific target.
    """

    awards = get_awards(target, description, award_type, instance, reward, award_id)
    nr_awards = len(awards)

    if nr_awards > 1:
        raise Exception("Couldn't get award for target with ID = %s: more than one award found." % target)

    elif nr_awards == 0:
        return None

    else:
        return awards[0]

def get_awards(target, description=None, award_type=None, instance=None, reward=None, award_id=None):
    global preloaded_awards

    instance = int(instance) if instance is not None else None
    reward = int(reward) if reward is not None else None
    award_id = int(award_id) if award_id is not None else None

    return [a for a in preloaded_awards[target] if
            (core_functions.compare_with_wildcards(a[config.AWARD_DESCRIPTION_COL],
                                    description) if description is not None else True) and
            (a[config.AWARD_TYPE_COL] == award_type if award_type is not None else True) and
            (a[config.AWARD_INSTANCE_COL] == instance if instance is not None else True) and
            (a[config.AWARD_REWARD_COL] == reward if reward is not None else True) and
            (a[config.AWARD_ID_COL] == award_id if award_id is not None else True)]

def give_award(target, award_type, description, reward, instance=None):
    """
    Gives an award to a specific target.
    """
    #print(f"Here to award: {description}")
    global preloaded_awards

    # Parse params
    reward = int(reward)
    instance = int(instance) if instance is not None else None

    # Add award to database
    query = "INSERT INTO " + get_awards_table() + " (user, course, description, type, moduleInstance, reward) " \
            "VALUES (%s, %s, %s, %s, %s, %s);"
    gc_db.execute_query(query, (target, config.COURSE, description, award_type, instance, reward), "commit")
    # Get award info
    query = "SELECT LAST_INSERT_ID();"
    award_id = gc_db.execute_query(query)[0][0]

    date_now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # Add award to preloaded awards
    award_to_preload = (award_id, target, config.COURSE, description, award_type, instance, reward, date_now)
    preloaded_awards[target].append(award_to_preload)

def remove_award(target, award_type, description, instance=None, award_id=None):
    """
    Removes an award from a specific target.
    """

    global preloaded_awards

    # Remove award from database
    query = "DELETE FROM " + get_awards_table() + " WHERE course = %s AND user = %s AND type = %s AND description LIKE %s"
    if instance is not None:
        query += " AND moduleInstance = %s" % instance
    if award_id is not None:
        query += " AND id = %s" % award_id
    query += ";"
    gc_db.execute_query(query, (config.COURSE, target, award_type, description), "commit")

    # Remove award from preloaded awards
    index = get_awards(target).index(get_award(target, description, award_type, instance, None, award_id))
    preloaded_awards[target] = preloaded_awards[target][:index] + preloaded_awards[target][index + 1:]

def update_award(target, award_type, description, new_reward, instance=None, award_id=None):
    """
    Updates an award's reward from a specific target.
    """

    global preloaded_awards

    # Update award in database
    query = "UPDATE " + get_awards_table() + " SET reward = %s " \
            "WHERE course = %s AND user = %s AND type = %s AND description LIKE %s"
    if instance is not None:
        query += " AND moduleInstance = %s" % instance
    if award_id is not None:
        query += " AND id = %s" % award_id
    query += ";"
    gc_db.execute_query(query, (new_reward, config.COURSE, target, award_type, description), "commit")

    # Update award in preloaded awards
    a = get_award(target, description, award_type, instance, None, award_id)
    index = get_awards(target).index(a)
    new_award = (a[config.AWARD_ID_COL] if award_id is None else award_id, target, config.COURSE, description,
                 award_type, instance, new_reward, a[config.AWARD_DATE_COL])
    preloaded_awards[target].insert(index, new_award)
    preloaded_awards[target] = preloaded_awards[target][:index + 1] + preloaded_awards[target][index + 2:]

def award_received(target, award_type, description, instance=None, award_id=None):
    """
    Checks whether a given award has already been received
    by a specific target.
    """

    return get_award(target, description, award_type, instance, None, award_id) is not None

# End awards

def calculate_reward(target, award_type, reward_to_give, reward_given=0):
    """
    Calculates reward for a given target
    based on max. values.
    """

    reward_to_give = int(reward_to_give)
    reward_given = int(reward_given)

    def get_max_xp_by_type():
        """
        query = "SELECT maxXP FROM %s WHERE course = %s;" % (award_type + "s_config", config.COURSE)
        result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0]
        return int(result) if result else None
        """
        global max_xp_by_type_config

        award_type_config = award_type + "s_config"
        if award_type_config in max_xp_by_type_config:
            return max_xp_by_type_config[award_type_config][0]
        else:
            query = "SELECT maxXP, maxExtraCredit FROM %s WHERE course = %s;" % (award_type_config, config.COURSE)
            result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0]
            max_xp_by_type_config[award_type_config] = [result[0], result[1]]
            return result[0]

    def get_target_xp():
        xp = {"total": 0}

        # Get badges XP
        if "Badges" in gamecourse_connector.modules_enabled:
            badges_total = get_total_badge_reward(target)
            xp["badges"] = badges_total
            xp["total"] += badges_total

        # Get skills XP
        if "Skills" in gamecourse_connector.modules_enabled:
            skills_total = get_total_skill_reward(target)
            xp["skills"] = skills_total
            xp["total"] += skills_total

        # Get streaks extra credit
        if "Streaks" in gamecourse_connector.modules_enabled:
            streaks_total = get_total_streak_reward(target)
            xp["streaks"] = streaks_total
            xp["total"] += streaks_total

        return xp

    def calculate_by_type():
        return award_type == 'badge' or award_type == 'skill' or award_type == 'streak'

    if "XPLevels" in gamecourse_connector.modules_enabled:
        # Get max. XP
        #max_xp = get_max_xp()
        global max_xp_config
        max_xp = max_xp_config[0]
        max_xp_for_type = get_max_xp_by_type() if calculate_by_type() else None

        # No max. threshold set, nothing to calculate
        if max_xp is None and max_xp_for_type is None:
            return reward_to_give

        # Calculate reward
        target_xp = get_target_xp()
        if max_xp is not None:
            reward_to_give = max(min(max_xp - (target_xp['total'] - reward_given), reward_to_give), 0)
            reward_given = reward_to_give

        if max_xp_for_type is not None:
            reward_to_give = max(min(max_xp_for_type - (target_xp[award_type + "s"] - reward_given), reward_to_give), 0)

        return reward_to_give

    return 0

def calculate_extra_credit_reward(target, award_type, reward_to_give, reward_given=0):
    """
    Calculates reward of a certain type for a given target
    based on max. extra credit values.
    """

    reward_to_give = int(reward_to_give)
    reward_given = int(reward_given)

    def get_max_extra_credit():
        global max_xp_by_type_config
        return max_xp_by_type_config[1]
        """
        query = "SELECT maxExtraCredit FROM xp_config WHERE course = %s;" % config.COURSE
        result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0]
        return int(result) if result else None
        """

    def get_max_extra_credit_by_type():
        """
        query = "SELECT maxExtraCredit FROM %s WHERE course = %s;" % (award_type + "s_config", config.COURSE)
        result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0][0]
        return int(result) if result else None
        """
        global max_xp_by_type_config

        award_type_config = award_type + "s_config"
        if award_type_config in max_xp_by_type_config:
            return max_xp_by_type_config[award_type_config][1]
        else:
            query = "SELECT maxXP, maxExtraCredit FROM %s WHERE course = %s;" % (award_type_config, config.COURSE)
            result = gc_db.data_broker.get(gc_db, config.COURSE, query)[0]
            max_xp_by_type_config[award_type_config] = [result[0], result[1]]
            #print(f"Config for {award_type_config}: {max_xp_by_type_config[award_type_config][0]} and {max_xp_by_type_config[award_type_config][1]}")
            return result[1]


    def get_target_extra_credit():
        extra_credit = {"total": 0}

        # Get badges extra credit
        if "Badges" in gamecourse_connector.modules_enabled:
            # Get badges IDs which are extra credit
            from . import badges
            badges_ids = badges.get_badges_extra_xp()

            # Calculate badges extra credit already awarded
            badges_total = 0
            for a in get_awards(target, None, "badge"):
                # Ignore badges which are not extra credit
                if a[config.AWARD_INSTANCE_COL] not in badges_ids:
                    continue

                badges_total += a[config.AWARD_REWARD_COL]

            extra_credit["badges"] = badges_total
            extra_credit["total"] += badges_total

        # Get skills extra credit
        if "Skills" in gamecourse_connector.modules_enabled:
            # Get skill IDs which are extra credit
            from . import skills
            skills_ids = skills.get_skills_extra_xp()

            # Calculate skills extra credit already awarded
            skills_total = 0
            for a in get_awards(target, None, "skill"):
                # Ignore skills which are not extra credit
                if a[config.AWARD_INSTANCE_COL] not in skills_ids:
                    continue

                skills_total += a[config.AWARD_REWARD_COL]

            extra_credit["skills"] = skills_total
            extra_credit["total"] += skills_total

        # Get streaks extra credit
        if "Streaks" in gamecourse_connector.modules_enabled:
            # Get streaks IDs which are extra credit
            from . import streaks
            streaks_ids = streaks.get_streaks_extra_xp()

            # Calculate streaks extra credit already awarded
            streaks_total = 0
            for a in get_awards(target, None, "streak"):
                # Ignore streaks which are not extra credit
                if a[config.AWARD_INSTANCE_COL] not in streaks_ids:
                    continue

                streaks_total += a[config.AWARD_REWARD_COL]

            extra_credit["streaks"] = streaks_total
            extra_credit["total"] += streaks_total

        return extra_credit

    def calculate_by_type():
        return award_type == 'badge' or award_type == 'skill' or award_type == 'streak'

    if "XPLevels" in gamecourse_connector.modules_enabled:
        # Get max. extra credit
        #max_extra_credit = get_max_extra_credit()
        global max_xp_config
        max_extra_credit = max_xp_config[1]
        max_extra_credit_for_type = get_max_extra_credit_by_type() if calculate_by_type() else None
        #print(f"Max Extra Credit: {max_extra_credit} and max for {award_type} is {max_extra_credit_for_type}")
        # No max. thresholds set, nothing to calculate
        if max_extra_credit is None and max_extra_credit_for_type is None:
            return reward_to_give

        # Calculate reward
        target_extra_credit = get_target_extra_credit()
        if max_extra_credit is not None:
            reward_to_give = max(min(max_extra_credit - (target_extra_credit['total'] - reward_given), reward_to_give), 0)
            reward_given = reward_to_give

        if max_extra_credit_for_type is not None:
            #print(f"Target Rewards: {target_extra_credit}")
            reward_to_give = max(min(max_extra_credit_for_type - (target_extra_credit[award_type + "s"] - reward_given), reward_to_give), 0)

        #print(f"Reward to give for type {award_type} is {reward_to_give}")
        return reward_to_give

    return 0


### Getting total reward

def get_target_total_reward(target, award_type=None):
    """
    Gets total reward for a given target.
    Option to filter by a specific award type.
    """

    total_reward = 0
    for a in get_awards(target, None, award_type):
        total_reward += int(a[config.AWARD_REWARD_COL])
    return total_reward

def get_total_assignment_reward(target):
    """
    Gets total reward for a given target from assignments.
    """

    return get_target_total_reward(target, "assignment")

def get_total_badge_reward(target):
    """
    Gets total reward for a given target from badges.
    """

    return get_target_total_reward(target, "badge")

def get_total_bonus_reward(target):
    """
    Gets total reward for a given target from bonus.
    """

    return get_target_total_reward(target, "bonus")

def get_total_exam_reward(target):
    """
    Gets total reward for a given target from exams.
    """

    return get_target_total_reward(target, "exam")

def get_total_lab_reward(target):
    """
    Gets total reward for a given target from labs.
    """

    return get_target_total_reward(target, "labs")

def get_total_presentation_reward(target):
    """
    Gets total reward for a given target from presentations.
    """

    return get_target_total_reward(target, "presentation")

def get_total_quiz_reward(target):
    """
    Gets total reward for a given target from quizzes.
    """

    return get_target_total_reward(target, "quiz")

def get_total_skill_reward(target):
    """
    Gets total reward for a given target from skills.
    """

    return get_target_total_reward(target, "skill")

def get_total_streak_reward(target):
    """
    Gets total reward for a given target from streaks.
    """

    return get_target_total_reward(target, "streak")

def get_total_tokens_reward(target):
    """
    Gets total reward for a given target from tokens.
    """

    return get_target_total_reward(target, "tokens")


### Awarding items

def award_to_target(target, award_type, description, reward, instance=None, unique=True, award_id=None):
    """
    Awards a single prize to a specific target.

    NOTE: will not retract, but will not award twice if unique.
    Updates award if reward has changed.
    """

    awards_given = get_awards(target, description, award_type, instance, None, award_id)
    nr_awards_given = len(awards_given)

    if unique and nr_awards_given > 1:
        logging.warning("Award '%s' has been awarded more than once for target with ID = %s." % (description, target))
        return

    #print(f"I have to go for an option. Reward: {reward}")
    if nr_awards_given == 0 or (not unique and award_id is None):  # Award
        #print("Option 1")
        reward = calculate_reward(target, award_type, reward)
        #print(f"Option 1 reward: {reward}")
        give_award(target, award_type, description, reward, instance)

    elif unique or award_id is not None:  # Update award, if changed
        #print("Option 2")
        old_reward = awards_given[0][config.AWARD_REWARD_COL]
        #print(f"Option 2 Old reward: {old_reward}")
        #reward = calculate_reward(target, award_type, reward, old_reward)
        #print(f"Option 2 New reward: {reward}")
        if reward != old_reward:
            update_award(target, award_type, description, reward, instance, awards_given[0][config.AWARD_ID_COL])

def award_assignment_grade_to_target(target, logs, max_xp=1, max_grade=1):
    """
    Awards assignment grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per assignment
     > max_grade --> maximum grade per assignment

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL]
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award_to_target(target, "assignment", name, reward)

def award_bonus_to_target(target, name, logs, reward=None, instance=None, unique=True):
    """
    Awards given bonus to a specific target.

    NOTE: will retract if bonus removed.
    Updates award if reward has changed.
    """

    award_type = 'bonus'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = get_awards(target, name, award_type, instance)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has bonus attributed which are longer valid.
    # The bonus no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete latest invalid awards
        for diff in range(0, nr_awards_given - nr_logs):
            remove_award(target, award_type, name, instance, awards_given[nr_awards_given - diff - 1][config.AWARD_ID_COL])
        nr_awards_given = nr_logs

    # Award and/or update bonus
    for i in range(0, nr_logs):
        log = logs[i]
        award_id = None
        if not unique and nr_awards_given > i:
            award_id = awards_given[i][config.AWARD_ID_COL]
        bonus = reward if reward is not None else logs[nr_logs - 1][config.LOG_RATING_COL] if unique else log[config.LOG_RATING_COL]
        award_to_target(target, award_type, name, bonus, instance, unique, award_id)

def award_exam_grade_to_target(target, name, logs, reward, max_xp=1, max_grade=1):
    """
    Awards exam grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP for exam
     > max_grade --> maximum grade for exam

    NOTE: will retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'exam'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = get_awards(target, name, award_type)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has an exam grade attributed which is longer valid.
    # The grade no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete invalid awards
        remove_award(target, award_type, name)

    # Award and/or update exam grade
    if nr_logs > 0:
        reward = round((reward / max_grade) * max_xp)
        award_to_target(target, award_type, name, reward)

def award_lab_grade_to_target(target, logs, max_xp=1, max_grade=1):
    """
    Awards lab grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per lab
     > max_grade --> maximum grade per lab

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'labs'

    for log in logs:
        lab_nr = int(log[config.LOG_DESCRIPTION_COL])
        name = "Lab %s" % lab_nr
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award_to_target(target, award_type, name, reward, lab_nr)

def award_post_grade_to_target(target, logs, max_xp=1, max_grade=1):
    """
    Awards post grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per post
     > max_grade --> maximum grade per post

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL].split(",")[0]
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award_to_target(target, "post", name, reward)

def award_presentation_grade_to_target(target, name, logs, max_xp=1, max_grade=1):
    """
    Awards presentation grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP for presentation
     > max_grade --> maximum grade for presentation

    NOTE: will retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'presentation'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = get_awards(target, name, award_type)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has a presentation grade attributed which is longer valid.
    # The grade no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete invalid awards
        remove_award(target, award_type, name)

    # Award and/or update exam grade
    if nr_logs > 0:
        reward = round((logs[nr_logs - 1][config.LOG_RATING_COL] / max_grade) * max_xp)
        award_to_target(target, award_type, name, reward)

def award_quiz_grade_to_target(target, logs, max_xp=1, max_grade=1):
    """
    Awards quiz grades to a specific target.

    Option to calculate how many XP should be awarded:
     > max_xp --> maximum XP per quiz
     > max_grade --> maximum grade per quiz

    NOTE: will NOT retract if grade removed.
    Updates award if reward has changed.
    """

    award_type = 'quiz'

    for log in logs:
        name = log[config.LOG_DESCRIPTION_COL]
        reward = round((int(log[config.LOG_RATING_COL]) / max_grade) * max_xp)
        award_to_target(target, award_type, name, reward)

