from gamerules import *
from course.coursedata import students
from gamerules.functions.utils import import_functions_from_rulepath
from io import StringIO

import os, sys, config, logging, json

# ------------------------------------------------------------
#   TEST VERSION OF RULE SYSTEM
# ------------------------------------------------------------

# Folder where rule test file is defined
RULES_TESTS_FOLDER = "rule-tests"
RULES_TESTS_FILE = "rule.txt"

AUTOSAVE = False
LOGFILE = '/var/www/html/gamecourse/api/logs/autogame-python.log'


def get_config_metadata(course):
	CONFIGFILE = "config_" + str(config.COURSE) + ".txt"
	CONFIGPATH = os.path.join(config.ROOT_PATH, "config", CONFIGFILE)

	try:
		with open(CONFIGPATH, 'r') as f:
			lines = f.read()
			data = lines.split("\n")
			metadata = {}
			for el in data:
				if len(el.split(":")) == 2:
					[key,val] = el.split(":")
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

# This python script will be invoked from the php side with
# an argument that indicated which course is being run

# CLI prompt: python3 run_autogame_test.py [courseId] [all/new/targets] [rules_path] [logs_file] [dbHost] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
	#error = ""
	all_targets = False
	targets_list = None

	# Process Arguments
	if len(sys.argv) >= 8:

		# Initialize GameCourse connector
		from gamerules.connector.db_connector import connect_to_gamecourse_db
		connect_to_gamecourse_db(sys.argv[5], sys.argv[6], sys.argv[7], sys.argv[8])
		from gamerules.connector.gamecourse_connector import *

		if course_exists(sys.argv[1]):
			course = sys.argv[1]
			config.COURSE = course
			config.TEST_MODE = True
			rulespath = sys.argv[3]
			config.RULES_PATH = os.path.join(rulespath, RULES_TESTS_FOLDER)
			output_file = os.path.join(config.RULES_PATH, "rule-test-output.txt")

		else:
			sys.exit("ERROR: Course passed is not active or does not exist.")
		if sys.argv[2] == "all":
			all_targets = True
		else:
			targets_list = sys.argv[2].strip("[]").split(",")
	else:
		sys.exit()
		

	with open(output_file ,'w') as lfile:
		# No need to init autogame, since there are no concurrency issues,
		# and server socket will already be open
		
		# Folder path of rules
		# Course folder + rule tests folder
		path = os.path.join(rulespath, RULES_TESTS_FOLDER)
		# Read and set Metadata
		METADATA = get_config_metadata(course)
		scope, logs = {"METADATA" : METADATA, "null": None}, {}

		try:
			rs = RuleSystem(path, AUTOSAVE)

			# Process targets
			if targets_list != None:
				# if targets were passed in cli
				students = {}
				for target in targets_list:
					students[target] = 1
			else:
				# get targets
				students = get_targets(course, all_targets, None)

			# Import custom course functions
			functions_path = os.path.join(config.IMPORTED_FUNCTIONS_FOLDER, course)
			functions, fpaths, info = import_functions_from_rulepath(functions_path, info=True)

			try:
				rs_output = rs.fire(students,logs,scope)

			except Exception as e:
				logging.exception('Exception raised when firing rulesystem.\n\n\n')
				lfile.write(str(e) + "\n")
				#error += str(e)

			try:
				# In this test case, dates are NOT updated, as they should
				# However the socket needs to be closed anyway
				autogame_terminate(course, None, None)
				
			except Exception as e:
				logging.error('Connection Refused in autogame_terminate().')
				lfile.write(str(e) + "\n")
				#error += str(e)

		except Exception as e:
			# In case of error, also close the socket
			autogame_terminate(course, None, None)
			lfile.write(str(e) + "\n")
			#error += str(e)
		#sys.exit(error)