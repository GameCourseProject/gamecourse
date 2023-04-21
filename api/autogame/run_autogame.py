#!/usr/bin/env python3
import os, sys, config, logging

from gamerules import *
from gamerules.functions.utils import import_functions_from_rulepath

# TODO find a mechanism to test socket before opening dat files, get rid of basic inconsistency


### ------------------------------------------------------ ###
###	------------------ Helper Functions ------------------ ###
### ------------------------------------------------------ ###

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
        raise Exception('Course is not active or does not exist.')

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
            lines = f.read().split("\n")
            metadata = {}
            for line in lines:
                parts = line.split(":")
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
    if len(students) > 5:
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

    else:
        targets_str = "\n".join(list(
            map(lambda s: "(" + str(s["studentNumber"]) + ") " + first_and_last_name(s["name"]),
                sorted(students.values(), key=lambda s: s["name"]))))

    # Log info
    logging.info("AutoGame finished running.\n\n" +
                 "AutoGame ran for the following targets:\n\n" +
                 "TOTAL = " + str(len(list(students.keys()))) + "\n" +
                 targets_str)


### ------------------------------------------------------ ###
###	-------------- Main GameRules Interface -------------- ###
### ------------------------------------------------------ ###

# This python script will be invoked from the php side.
# CLI prompt: python3 run_autogame.py [courseId] [all/new/targets] [rules_path] [logs_file] [dbHost] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
    # General variables
    error_msg = None
    course, start_date, finish_date = None, None, None

    try:
        if len(sys.argv) != 9:
            raise Exception('AutoGame didn\'t receive all the information.')

        # Configure logging
        configure_logging(sys.argv[4])

        # Initialize GameCourse connector
        from gamerules.connector.db_connector import connect_to_gamecourse_db
        connect_to_gamecourse_db(sys.argv[5], sys.argv[6], sys.argv[7], sys.argv[8])
        from gamerules.connector.gamecourse_connector import *

        # Process arguments
        (course, all_targets, targets_list) = process_args(sys.argv[1], sys.argv[3], sys.argv[2])

        # Initialize AutoGame
        start_date, finish_date = None, None
        checkpoint = autogame_init(course)

        # Get targets to run
        students = get_targets(course, checkpoint, all_targets, targets_list)
        if students:

            # Import custom course functions
            # FIXME: doesn't seem to be doing anything
            functions_path = os.path.join(config.IMPORTED_FUNCTIONS_FOLDER, course)
            functions, fpaths, info = import_functions_from_rulepath(functions_path, info=True)

            # Read and set Metadata
            METADATA = get_metadata()
            scope, logs = {"METADATA": METADATA, "null": None}, {}

            # Initialize Moodle connector
            if module_enabled("Moodle"):
                mdl_table = "moodle_config"
                query = "SELECT dbServer, dbName, dbUser, dbPass FROM " + mdl_table + " WHERE course = %s;"
                mdl_host, mdl_database, mdl_username, mdl_password = gc_db.execute_query(query, (config.COURSE,))[0]
                if mdl_password:
                    from gamerules.connector.db_connector import connect_to_moodle_db
                    connect_to_moodle_db(mdl_host.decode(), mdl_database.decode(), mdl_username.decode(),
                                         mdl_password.decode())
                    from gamerules.connector.moodle_connector import *

            # Save the start date
            start_date = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            log_start()

            # Preload information from the database
            preload_info(students.keys())

            # Clear all progression before calculating again
            clear_progression(students.keys())

            try:
                # Fire Rule System
                rs = RuleSystem(config.RULES_PATH, config.AUTOSAVE)
                rs_output = rs.fire(students, logs, scope)

            except Exception as e:
                raise e

            finally:
                # Update all progression
                update_progression()

                # Calculate new grade for each target
                for student in students.keys():
                    calculate_grade(student)

            # Save the end date
            finish_date = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            log_end()

    except Exception as e:
        error_msg = str(e)
        raise

    finally:
        # Terminate AutoGame
        if course:
            autogame_terminate(course, start_date, finish_date)

        # Close database connections
        from gamerules.connector.db_connector import close_all_connections
        close_all_connections()

        # Log errors found
        if error_msg is not None:
            logging.exception(error_msg)
            sys.exit("ERROR: " + error_msg)
