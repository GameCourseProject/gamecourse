import os, sys, json
import config, logging

from gamerules import *
from gamerules.functions.utils import import_functions_from_rulepath

# TODO find a mechanism to test socket before opening dat files, get rid of basic inconsistency

def process_args(_course, _rules_path, _targets):
    _all_targets = False
    _targets_list = None

    if course_exists(_course):
        config.COURSE = _course
        config.RULES_PATH = _rules_path

        if _targets == "all":
            _all_targets = True
        elif _targets != "new":
            _targets_list = _targets.strip("[]").split(r",", )

        return _course, _all_targets, _targets_list

    else:
        db.close_db()
        sys.exit("ERROR: Course is not active or does not exist.")

def configure_logging(logs_file):
    sep = "=" * 80
    header = "[%(asctime)s] [%(levelname)s] : "
    msg = "%(message)s\n"

    log_format = sep + "\n" + header + msg + sep + "\n\n"
    logging.basicConfig(filename=logs_file, filemode='a', format=log_format, datefmt='%Y-%m-%d %H:%M:%S',
                        level=logging.DEBUG)

def get_config_metadata(course):
    configfile = "config_" + str(course) + ".txt"
    configpath = os.path.join(config.ROOT_PATH, "config", configfile)
    try:
        with open(configpath, 'r') as f:
            lines = f.read()
            data = lines.split("\n")
            metadata = {}
            for el in data:
                if len(el.split(":")) == 2:
                    [key, val] = el.split(":")
                    metadata[key] = int(val)
            return metadata

    except IOError:
        error_msg = "ERROR: No config file found for course " + str(course) + "."
        logging.exception(error_msg)
        sys.exit(error_msg)


# ------------------------------------------------------------
#
#	Main GameRules interface
#
# ------------------------------------------------------------

# This python script will be invoked from the php side.
# cli prompt: python3 run_autogame.py [courseId] [all/new/targets] [rules_path] [logs_file] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
    if len(sys.argv) != 8:
        error_msg = "ERROR: AutoGame didn't receive all the information."
        sys.exit(error_msg)

    # Configure logging
    configure_logging(sys.argv[4])
    error_msg = None

    # Initialize connector
    from gamerules.connector.db_connector import connect_to_gamecourse_db

    connect_to_gamecourse_db(sys.argv[5], sys.argv[6], sys.argv[7])
    from gamerules.connector.gamecourse_connector import *

    try:
        # Process arguments
        (course, all_targets, targets_list) = process_args(sys.argv[1], sys.argv[3], sys.argv[2])

        # Initialize AutoGame
        last_activity = autogame_init(course)

        # Get targets to run
        students = get_targets(course, last_activity, all_targets, targets_list)

        # Clear badge progression before calculating again
        # FIXME: dependent on modules being active
        for el in students.keys():
            clear_badge_progression(el)
            clear_streak_progression(el)
            clear_streak_participations(el)

        # Import custom course functions
        functions_path = os.path.join(config.IMPORTED_FUNCTIONS_FOLDER, course)
        functions, fpaths, info = import_functions_from_rulepath(functions_path, info=True)

        # Read and set Metadata
        METADATA = get_config_metadata(course)
        scope, logs = {"METADATA": METADATA, "null": None}, {}

        try:
            # Save the start date
            start_date = datetime.now().strftime("%Y/%m/%d %H:%M:%S")
            logging.info("AutoGame started running.")

            # Fire Rule System
            rs = RuleSystem(config.RULES_PATH, config.AUTOSAVE)
            rs_output = rs.fire(students, logs, scope)

            try:
                # Terminate AutoGame
                finish_date = datetime.now().strftime("%Y/%m/%d %H:%M:%S")
                autogame_terminate(course, start_date, finish_date)
                targets_info = " | ".join(list(map(lambda s: "(" + str(s["studentNumber"]) + ") " + s["name"], list(students.values()))))
                logging.info("AutoGame finished running.\n\n" +
                             "AutoGame ran for the following targets (total=" + str(len(list(students.keys()))) + "):\n\n" +
                             "\n".join(targets_info[i:i+80] for i in range(0, len(targets_info), 80)) +"\n")

                # Calculate new XP value for each target
                for el in students.keys():
                    calculate_xp(course, el)
                    calculate_teams_xp(course, el)

            except Exception as e:
                error_msg = str(e)
                raise

        except Exception as e:
            error_msg = "Exception raised when firing Rule System."
            raise

    except Exception as e:
        error_msg = str(e)
        raise

    finally:
        db.close_db()
        if error_msg is not None:
            logging.exception(error_msg)
            sys.exit("ERROR: " + error_msg)
