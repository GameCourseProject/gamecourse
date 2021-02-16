from gamerules import *
from course.coursedata import students
from gamerules.connector.gamecourse_connector import *

import os, sys
import config

from io import StringIO
import json

# Folder where all rules will be defined rules will be stored
RULES_FOLDER = "rules"
AUTOSAVE = True


def config_metadata(course):
	CONFIGFILE = "config_" + str(course) + ".txt"
	CONFIGPATH = os.path.join(os.getcwd(),"config",CONFIGFILE)

	with open(CONFIGPATH, 'r') as f:
		try:
			lines = f.read()
		except:
			sys.exit("ERROR: No config file found for course ", str(course), ".")

	data = lines.split("\n")
	
	metadata = {}
	for el in data:
		[key,val] = el.split(":")
		metadata[key] = int(val)
	return metadata


def read_indicators():
	with open('indicators.json', 'r') as file:
		indicators_raw = file.read()
	
	io = StringIO(indicators_raw)
	indicators = json.load(io)

	print(type(indicators))
	print(len(indicators))
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
					# TODO very dumb case of simple substitution, 
					# but if you get the removed indicators you might also be able to easily remove!



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


	with open("outputt2.txt", "w+") as file:
		file.write(str(indicators))



# ------------------------------------------------------------
#
#	Main GameRules interface
#
# ------------------------------------------------------------

# This python script will be invoked from the php side with
# an argument that indicated which course is being run
if __name__ == "__main__":
	if len(sys.argv) != 2:
		sys.exit("ERROR: GameRules received no course information.")
	
	# change the scripts running location to the folder in which it is located
	os.chdir(os.path.dirname(sys.argv[0]))
	rulespath = os.path.join(os.getcwd(),RULES_FOLDER)


	if course_exists(sys.argv[1]):
		config.course = sys.argv[1]
		course = config.course
		config.rules_folder = os.path.join(os.getcwd(), RULES_FOLDER, course)
	else:
		sys.exit("ERROR: Course passed is not active or does not exist.")


	METADATA = config_metadata(course)	

	last_activity, is_running = autogame_init(course)
	#is_running = False # temporary to circumvent errors


	if is_running:
		sys.exit("ERROR: GameRules is already running for this course.")

	# TODO find a mechanism to test socket before opening dat files, get rid of basic inconsistency

	# TODO : fix this when all done, now we are not generating new participations yet
	

	# read all rules relating to current path	
	path = os.path.join(rulespath, course)
	

	# TODO check timestamp table from last time the rulesystem ran
	
	#logs is now a list, must adapt list to dict
	scope, logs = {"METADATA" : METADATA, "null": None}, {}
	rs = RuleSystem(path, AUTOSAVE)


	students = get_targets(course, last_activity)

	rs_output = rs.fire(students,logs,scope)
	
	# set this instance as non-running
	autogame_terminate(course)

