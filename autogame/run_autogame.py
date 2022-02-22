from gamerules import *
from course.coursedata import students
from gamerules.connector.gamecourse_connector import *
from gamerules.functions.utils import import_functions_from_rulepath
from io import StringIO

import os, sys, config, logging, json

# TODO find a mechanism to test socket before opening dat files, get rid of basic inconsistency


# Folder where rule files will be defined
RULES_FOLDER = "rules"
AUTOSAVE = False
BASE_DIR = '/var/www/html/gamecourse/'
LOGFILE_BASE = BASE_DIR + 'logs/log_course_'
IMPORTED_FUNCTIONS_FOLDER = "/var/www/html/gamecourse/autogame/imported-functions"

def write_to_log(text, course, logfile):
	logfile_path = logfile + str(course) + ".txt"
	separator = "-" * 55
	separator += "\n\n"
	timestamp = datetime.now()
	time = timestamp.strftime("%d/%m/%Y %H:%M:%S")
	date = "\t" + str(time) + "\n\n"
	with open(logfile_path, 'a+') as file:
		file.write(separator)
		file.write(date)	
		file.write(separator)
		file.write(text)
		file.write("\n\n\n")

def get_config_metadata(course):
	configfile = "config_" + str(course) + ".txt"
	configpath = os.path.join(BASE_DIR, "autogame", "config", configfile)
	try:
		with open(configpath, 'r') as f:
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


def read_indicators():
	with open('indicators.json', 'r') as file:
		indicators_raw = file.read()
	
	io = StringIO(indicators_raw)
	indicators = json.load(io)

	for el in indicators:
		print(el["num"])


def add_indicators(new_indicators, removed_indicators=None):
	with open('indicators.json', 'w+') as file:
		indicators_raw = file.read()
	
	io = StringIO(indicators_raw)
	indicators = json.load(io)

	# indicators: old indicators to which we will be adding or subtracting
	# new_indicators: indicators to be added to the old ones
	# removed_indicators: indicators to be removed

	for student in new_indicators: # for each student dictionary
		student_num = student["num"]

		for rule in student["indicators"]:
			# rule is equal to the name of the rule aka key of dict

			for st in indicators:
				# check if indicator already exists
				if st["num"] == student_num:
					st["indicators"]["rule"] = student["indicators"]["rule"]

def process_indicators(output, course):

	indicators = []
	student_nrs = get_student_numbers(course)

	for el in output:
		target = student_nrs[str(el)] 
		student = {"num" : str(target), "indicators" : {}}

		for badge in output[el]:
			if output[el][badge] == None:
				# set as null
				student["indicators"][badge] = ["0", []] #badge value fix
			elif output[el][badge] != None:
				student["indicators"][badge] = output[el][badge]

				# fix the target nr
				if isinstance(student["indicators"][badge], list) and student["indicators"][badge][1] != ["0", []]:
					for indi in student["indicators"][badge][1]:
						
						indi["num"] = target

		indicators.append(student)

	


def log_start(course, start_date, logfile):
	separator = "=" * 80
	separator += "\n"
	date = "[" + str(start_date) + "] : AutoGame started running.\n"
	with open(logfile, 'a+') as file:
		file.write(separator + date + separator + "\n")

def log_end(course, end_date, targets, logfile):
	separator = "=" * 80
	separator += "\n"
	date = "[" + str(end_date) + "] : AutoGame finished running.\n\n"
	details = "Autogame ran for the following targets:\n\n"
	details += "Unordered:\t" + str(list(targets.keys())) + "\n\n"
	details += "Ordered:\t" + str(sorted(list(targets.keys()))) + "\n\n"

	with open(logfile, 'a+') as file:
		file.write(separator + date + details + separator + "\n")


# ------------------------------------------------------------
#
#	Main GameRules interface
#
# ------------------------------------------------------------

# This python script will be invoked from the php side with
# an argument that indicated which course is being run
if __name__ == "__main__":

	all_targets = False
	targets_list = None

	# Process Arguments
	# cli prompt: python3 run_autogame.py [courseId] [rule_path] [all/targets]

	if len(sys.argv) < 2:
		sys.exit("ERROR: GameRules received no course information.")

	elif len(sys.argv) == 2:
		sys.exit("ERROR: GameRules received no rule folder information.")

	elif len(sys.argv) == 3:
		if course_exists(sys.argv[1]):
			course = sys.argv[1]
			config.course = course
			rulespath = sys.argv[2]
			config.rules_folder = os.path.join(rulespath, RULES_FOLDER)
		else:
			sys.exit("ERROR: Course passed is not active or does not exist.")
	
	elif len(sys.argv) == 4:
		if course_exists(sys.argv[1]):
			course = sys.argv[1]
			config.course = course
			rulespath = sys.argv[2]
			config.rules_folder = os.path.join(rulespath, RULES_FOLDER)
		else:
			sys.exit("ERROR: Course passed is not active or does not exist.")

		if sys.argv[3] == "all":
			all_targets = True
		else:
			targets_list = sys.argv[3].strip("[]").split(",")

	logfile = LOGFILE_BASE + str(course) + '.txt'

	# change the scripts running location to the folder in which it is located
	#os.chdir(os.path.dirname(sys.argv[0]))
	#rulespath = os.path.join(os.getcwd(),RULES_FOLDER)

	# Configure Logging
	sep = "=" * 80 + "\n"
	log_date = "[%(asctime)s] [%(levelname)s] : %(message)s\n"
	error_msg = "=" * 80 + "\n"
	log_format = sep + log_date + error_msg
	logging.basicConfig(filename=logfile, filemode='a', format=log_format, datefmt='%Y-%m-%d %H:%M:%S', level=logging.DEBUG)

	# Check Autogame Status
	last_activity, is_running = autogame_init(course)

	if is_running:
		error_msg = "ERROR: GameRules is already running for this course."
		logging.error(error_msg)
		sys.exit(error_msg)
	
	# Folder path of rules
	# Course folder + rules folder
	path = os.path.join(rulespath, RULES_FOLDER)

	# Read and set Metadata
	METADATA = get_config_metadata(course)
	scope, logs = {"METADATA" : METADATA, "null": None}, {}
	rs = RuleSystem(path, AUTOSAVE)

	# Process targets
	if targets_list != None:
		# if targets were passed in cli
		students = {}
		for target in targets_list:
			students[target] = 1
	else:
		# get targets
		students = get_targets(course, last_activity, all_targets)


	# Clear badge progression before calculating again
	for el in students.keys():
		clear_badge_progression(el)
		clear_streak_progression(el)

	
	# Save the start date
	timestamp = datetime.now()
	start_date = timestamp.strftime("%Y/%m/%d %H:%M:%S")
	log_start(course, start_date, logfile)

	# Import custom course functions
	functions_path = os.path.join(IMPORTED_FUNCTIONS_FOLDER, course)
	functions, fpaths, info = import_functions_from_rulepath(functions_path, info=True)

	try:
		rs_output = rs.fire(students,logs,scope)
		
		try:
			timestamp = datetime.now()
			finish_date = timestamp.strftime("%Y/%m/%d %H:%M:%S")
			# if no errors in RS - set this instance as non-running
			autogame_terminate(course, start_date, finish_date)
			log_end(course, finish_date, students, logfile)
			
			# calculate new XP value for each student in targets
			for el in students.keys():
				calculate_xp(course, el)
			
			sys.exit()

		except Exception as e:
			logging.error('Connection Refused in autogame_terminate().')
			logging.error(str(e))
			sys.exit()

	except Exception as e:
		logging.exception('Exception raised when firing rulesystem.\n\n\n')
		sys.exit()