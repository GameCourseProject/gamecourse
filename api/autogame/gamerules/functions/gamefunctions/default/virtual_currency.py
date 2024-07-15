import config
import logging

from gamerules.connector import gamecourse_connector
from gamerules.connector.db_connector import gc_db
from decorators import rule_effect, rule_function
import importlib
import config
from datetime import datetime
awards = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.awards", package=__package__)
core_functions = importlib.import_module(f"gamerules.functions.gamefunctions.course_{config.COURSE}.core_functions", package=__package__)

"""
#####################################################
Virtual Currency game functions available
#####################################################
"""

@rule_effect
def spend_tokens(target, name, amount, repetitions=1):
    """
    :description: Spends a single item of a specific target.  NOTE: will not retract, but will not spend twice if is unique.
    Updates if amount has changed.

    :arg target: Student id from whom the tokens will be spent from. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Description of the spending
    :type name: str

    :arg amount: Amount of tokens to be spent
    :type amount: int

    :arg repetitions: Number of repetitions to spend the tokens
    :type repetitions: int

    :returns: void
    """

    spend_tokens_t(target, name, amount, repetitions)

@rule_effect
def award_tokens(target, name, logs, reward=None, instance=None, unique=True):
    """
    :description: Awards given tokens to a specific target. NOTE: will retract if tokens removed. Updates award if reward has changed.

    :arg target: Student id for whom the tokens will be awarded to. If no specific student, use 'target' keyword, which refers to
                 the student for whom the rule is being executed.
    :type target: int

    :arg name: Description of award
    :type name: str

    :arg logs: Lists of Logs
    :type logs: list

    :arg reward: Number of tokens to reward
    :type reward: int

    :arg instance: Id with instance of the award
    :type instance: int

    :arg unique: Flag to not reward in case the award is already been given
    :type unique: bool

    :returns: void
    """

    award_tokens_to_target(target, name, logs, reward, instance, unique)

"""
##############################################################################
"""

preloaded_spending = {}

def setup_module(target_ids):
    preload_spending(target_ids)

def preload_spending(targets_ids):
    """
    Preloads tokens' spending for given targets.
    Ensures the database is accessed only once to retrieve tokens' spending.
    """

    global preloaded_spending

    # Get spending for targets
    query = "SELECT * FROM virtual_currency_spending WHERE course = %s" % config.COURSE
    query += " AND user IN (%s)" % (', '.join([str(el) for el in targets_ids]))
    query += " ORDER BY date ASC;"
    spending = gc_db.execute_query(query)

    # Initialize preloaded spending
    preloaded_spending = {target_id: [] for target_id in targets_ids}

    # Organize spending by target
    for s in spending:
        s = (s[config.SPENDING_ID_COL],
             s[config.SPENDING_USER_COL],
             s[config.SPENDING_COURSE_COL], # can remove
             s[config.SPENDING_DESCRIPTION_COL].decode(),
             s[config.SPENDING_AMOUNT_COL],
             s[config.SPENDING_DATE_COL])

        target_id = s[config.SPENDING_USER_COL]
        if target_id in preloaded_spending:
            preloaded_spending[target_id].append(s)
        else:
            preloaded_spending[target_id] = [s]


def get_spending(target, description=None, amount=None, spending_id=None):
    global preloaded_spending

    amount = int(amount) if amount is not None else None
    spending_id = int(spending_id) if spending_id is not None else None

    return [s for s in preloaded_spending[target] if
            (core_functions.compare_with_wildcards(s[config.SPENDING_DESCRIPTION_COL], description) if description is not None else True) and
            (s[config.SPENDING_AMOUNT_COL] == amount if amount is not None else True) and
            (s[config.SPENDING_ID_COL] == spending_id if spending_id is not None else True)]


def award_tokens_to_target(target, name, logs, reward=None, instance=None, unique=True):
    """
    Awards given tokens to a specific target.

    NOTE: will retract if tokens removed.
    Updates award if reward has changed.
    """

    award_type = 'tokens'
    nr_logs = len(logs)

    # Get awards already given
    awards_given = awards.get_awards(target, name, award_type, instance)
    nr_awards_given = len(awards_given)

    # There are no logs nor awards to be removed
    # Simply return right away
    if nr_logs == 0 and nr_awards_given == 0:
        return

    # The rule/data sources have been updated, the 'award' table
    # has tokens attributed which are longer valid.
    # The tokens no longer valid must be deleted
    if nr_awards_given > nr_logs:
        # Delete latest invalid awards
        for diff in range(0, nr_awards_given - nr_logs):
            awards.remove_award(target, award_type, name, instance, awards_given[nr_awards_given - diff - 1][config.AWARD_ID_COL])
        nr_awards_given = nr_logs

    # Award and/or update tokens
    for i in range(0, nr_logs):
        log = logs[i]
        award_id = None
        if not unique and nr_awards_given > i:
            award_id = awards_given[i][config.AWARD_ID_COL]
        tokens = reward if reward is not None else logs[nr_logs - 1][config.LOG_RATING_COL] if unique else log[config.LOG_RATING_COL]
        awards.award_to_target(target, award_type, name, tokens, instance, unique, award_id)


def do_spending(target, description, amount):
    """
    Spends a certain amount of tokens from a specific target.
    """

    global preloaded_spending

    # Parse params
    amount = int(amount)

    # Add spending to database
    query = "INSERT INTO virtual_currency_spending (user, course, description, amount) VALUES (%s, %s, %s, %s);"
    gc_db.execute_query(query, (target, config.COURSE, description, amount), "commit")

    # Get spending info
    query = "SELECT LAST_INSERT_ID();"
    spending_id = gc_db.execute_query(query)[0][0]

    date_now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # Add spending to preloaded spending
    spending_to_preload = (spending_id, target, config.COURSE, description, amount, date_now)
    preloaded_spending[target].append(spending_to_preload)


def remove_spending(target, description, spending_id=None):
    """
    Removes a tokens' spending from a specific target.
    """

    global preloaded_spending

    # Remove spending from database
    query = "DELETE FROM virtual_currency_spending WHERE course = %s AND user = %s AND description LIKE %s"
    if spending_id is not None:
        query += " AND id = %s" % spending_id
    query += ";"
    gc_db.execute_query(query, (config.COURSE, target, description), "commit")

    # Remove spending from preloaded spending
    index = get_spending(target).index(get_spending(target, description, None, spending_id)[0])
    preloaded_spending[target] = preloaded_spending[target][:index] + preloaded_spending[target][index + 1:]

def update_spending(target, description, new_amount, spending_id=None):
    """
    Updates a spending amount from a specific target.
    """

    global preloaded_spending

    # Update spending in database
    query = "UPDATE virtual_currency_spending SET amount = %s WHERE course = %s AND user = %s AND description LIKE %s"
    if spending_id is not None:
        query += " AND id = %s" % spending_id
    query += ";"
    gc_db.execute_query(query, (new_amount, config.COURSE, target, description), "commit")

    # Update spending in preloaded spending
    s = get_spending(target, description, None, spending_id)[0]
    index = get_spending(target).index(s)
    new_spending = (s[config.SPENDING_ID_COL] if spending_id is None else spending_id, target, config.COURSE,
                    description, new_amount, s[config.SPENDING_DATE_COL])
    preloaded_spending[target].insert(index, new_spending)
    preloaded_spending[target] = preloaded_spending[target][:index + 1] + preloaded_spending[target][index + 2:]


def spend_tokens_t(target, description, amount, unique=True, spending_id=None):
    """
    Spends a certain amount of tokens of a specific target.

    NOTE: will not retract, but will not spend twice if unique.
    Updates spending if amount has changed.
    """

    spending = get_spending(target, description, None, spending_id)
    spending_done = len(spending)

    if unique and spending_done > 1:
        logging.warning("Spending '%s' has been performed more than once for target with ID = %s." % (description, target))
        return

    if spending_done == 0 or (not unique and spending_id is None) and amount > 0:  # Spend
        do_spending(target, description, amount)

    elif unique or spending_id is not None:  # Update spending, if changed
        old_amount = int(spending[0][4])
        if amount != old_amount:
            if amount > 0:
                update_spending(target, description, amount, spending[0][config.SPENDING_ID_COL])
            else:
                remove_spending(target, description, spending[0][config.SPENDING_ID_COL])

def calculate_tokens(target):
    """
    Calculates total tokens for a given target based on awards received
    and spending performed.
    """

    # Calculate total tokens received
    total_received = awards.get_total_tokens_reward(target)

    # Calculate total tokens spent
    total_spent = 0
    for s in get_spending(target):
        total_spent += s[config.SPENDING_AMOUNT_COL]

    global preloaded_spending

    # Update target wallet
    query = "UPDATE user_wallet SET tokens = %s WHERE course = %s AND user = %s;"
    gc_db.execute_query(query, (total_received - total_spent, config.COURSE, target), "commit")