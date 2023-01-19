#!/usr/bin/env python3
import os, sys, config, logging
from datetime import datetime

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
        db.close()
        sys.exit("ERROR: Course is not active or does not exist.")

def configure_logging(logs_file):
    sep = "=" * 80
    header = "[%(asctime)s] [%(levelname)s] : "
    msg = "%(message)s\n"

    log_format = sep + "\n" + header + msg + sep + "\n\n"
    logging.basicConfig(filename=logs_file, filemode='a', format=log_format, datefmt='%Y-%m-%d %H:%M:%S',
                        level=logging.DEBUG)

def get_metadata():
    metadata_file = "config_" + str(config.COURSE) + ".txt"
    metadata_path = os.path.join(config.ROOT_PATH, "config", metadata_file)

    try:
        with open(metadata_path, 'r') as f:
            lines = f.read()
            data = lines.split("\n")
            metadata = {}
            for el in data:
                parts = el.split(":")
                if len(parts) == 2:
                    [key, val] = parts
                    metadata[key] = int(val)
            return metadata

    except IOError:
        raise Exception("No config file found for course with ID = %s." % config.COURSE)

def log_start():
    logging.info("AutoGame started running.")

def log_end():
    def first_and_last_name(_name):
        first, *middle, last = _name.split()
        return first + " " + last

    def truncate(_info, _max, _gap):
        return _info[:(_max - _gap)] + "..." if len(_info) > _max else _info

    # Process targets name and student number
    targets_info = list(
        map(lambda s: truncate("(" + str(s["studentNumber"]) + ") " + first_and_last_name(s["name"]), 25, 2),
            sorted(students.values(), key=lambda s: s["name"])))

    # Divide targets info into columns
    col = 3
    targets_info = [targets_info[i:i+col] for i in range(0, len(targets_info), col)]

    fill_with_empty = col - len(targets_info[-1])
    for i in range(fill_with_empty):
        targets_info[-1].append("")

    targets_str = ""
    for _info in targets_info:
        targets_str += "{: <27} {: <27} {: <27}\n".format(*_info)

    # Log info
    logging.info("AutoGame finished running.\n\n" +
                 "AutoGame ran for the following targets:\n\n" +
                 "TOTAL = " + str(len(list(students.keys()))) + "\n" +
                 targets_str + "\n")


### ------------------------------------------------------ ###
###	-------------- Main GameRules Interface -------------- ###
### ------------------------------------------------------ ###

# This python script will be invoked from the php side.
# CLI prompt: python3 run_autogame.py [courseId] [all/new/targets] [rules_path] [logs_file] [dbName] [dbUser] [dbPass]
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

        # Clear all progression before calculating again
        for el in students.keys():
            clear_progression(el)

        # Import custom course functions
        # FIXME: doesn't seem to be doing anything
        functions_path = os.path.join(config.IMPORTED_FUNCTIONS_FOLDER, course)
        functions, fpaths, info = import_functions_from_rulepath(functions_path, info=True)

        # Read and set Metadata
        METADATA = get_metadata()
        scope, logs = {"METADATA": METADATA, "null": None}, {}

        try:
            # Save the start date
            start_date = datetime.now().strftime("%Y/%m/%d %H:%M:%S")
            log_start()

            # Fire Rule System
            rs = RuleSystem(config.RULES_PATH, config.AUTOSAVE)
            rs_output = rs.fire(students, logs, scope)

            try:
                # Terminate AutoGame
                finish_date = datetime.now().strftime("%Y/%m/%d %H:%M:%S")
                autogame_terminate(course, start_date, finish_date)
                log_end()

                # Calculate new grade for each target
                for el in students.keys():
                    calculate_grade(el)

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
        db.close()
        if error_msg is not None:
            logging.exception(error_msg)
            sys.exit("ERROR: " + error_msg)
