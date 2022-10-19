#!/usr/bin/env python
# -*- coding: utf-8 -*-
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# NOTE: 'pragma: no cover' is for the lines (or functions) that are marked with
# it don't show up in coverage tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from .logline import LogLine
from . import coursefunctions as cfuncs

import codecs
import csv
import inspect
import os
import urllib.request, urllib.parse, urllib.error
import time
import copy

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Aux Functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def get_path_of_script():
	""" returns the path of this script """
	return os.path.abspath(inspect.getfile(inspect.currentframe()))

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# file paths
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
METADATA_PATH = os.path.join(os.path.dirname(get_path_of_script()),"metadata/1920")
STUDENTS_FPATH = os.path.join(METADATA_PATH,"course/students.txt")
ACHIEVEMENTS_FPATH = os.path.join(METADATA_PATH,"gamification/achievements.txt")
TREE_FPATH = os.path.join(METADATA_PATH, "gamification/tree.txt")
MOODLEVOTES_FPATH = os.path.join(METADATA_PATH,"logs/moodlevotes.txt")
MOODLELOGS_FPATH = os.path.join(METADATA_PATH,"logs/moodlelogs.txt")
PCMSPREADSHEET_FPATH = os.path.join(METADATA_PATH,"logs/csv")

# temporary paths
QR_FPATH = os.path.join(METADATA_PATH,"temporary/report.txt")
MOODLEVOTESURL = "http://pcm.rnl.tecnico.ulisboa.pt/moodleVotes.php?c=5"
QUIZGRADES_FPATH = os.path.join(METADATA_PATH,"logs/moodlequizgrades.php.html")


# 1920 autogame file paths
AWARDS_1920 = os.path.join(METADATA_PATH, "output", "awards_final.txt")
AWARDS_AUTOGAME_1920 = os.path.join(METADATA_PATH, "output", "awards_autogame.txt")


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# URLs
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
#LOGFILEURL = "http://groups.ist.utl.pt/pcm/moodleLogs.php?c=8"
#RATINGSURL = "http://groups.ist.utl.pt/pcm/moodleVotes.php?c=8"
#PEERRATINGSURL = "http://groups.ist.utl.pt/pcm/moodlePeerVotes.php?c=8"
MOODLEBASEURL = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/mod/"
QRURL = "http://web.ist.utl.pt/daniel.j.goncalves/pcm/report.php"
#QUIZGRADESURL="http://pcm.rnl.tecnico.ulisboa.pt/moodlequizgrades.php?c=5"

# new urls
RATINGSURL = "http://pcm.rnl.tecnico.ulisboa.pt/moodleVotes.php?c=5"



# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Course INFOs
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
METADATA = {
	"all_lectures_alameda":22,
	"all_lectures_tagus":22,
	"all_lectures":20,
	"invited_alameda":0,
	"invited_tagus":0,
	"all_labs":10,
	"lab_max_grade":450,
	"lab_excellence_threshold":350,
	"lab_excellence_threshold_1":110,
	"lab_excellence_threshold_2":350,
	"quiz_max_grade":750,
	"initial_bonus":500,
	"max_xp":20000,
	"max_bonus":1000,
	"max_tree":5000,
	"level_grade":1000
}

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Data Read Functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def read_achievements (path=None):
	""" parse achievements from 'achievements.txt' and return a 'dict' with all
	the achievements parsed
	"""
	from .achievements import Achievement
	achievements = {}
	# get default path
	if not isinstance(path,str):
		path = ACHIEVEMENTS_FPATH

	with codecs.open(path,"r","utf-8") as file:
		for line in csv.reader(file,delimiter=";"):
			if len(line) > 0:
				name, description, criteria1, criteria2, criteria3, \
				xp1, xp2, xp3, is_counted, is_postbased, is_graded, \
				grade1, grade2, grade3 = line
				is_counted = True if is_counted == "True" else False
				is_postbased = True if is_postbased == "True" else False
				achievements[name] = Achievement(
					name, description, criteria1, criteria2, criteria3,
					xp1, xp2, xp3, is_counted, is_postbased)
		file.close()
	return achievements

def read_student_list (path=None):
	""" parse students from 'students.txt' and return a StudentData object """
	from .student import Student
	students = {}
	# get default path
	if not isinstance(path,str):
		path = STUDENTS_FPATH

	with open(path) as f:
		for s in f:
			num,name,email,campus = s.strip().split(";")
			num = int(num)
			students[num] = Student(num,name,email,campus)

	return students

def read_tree (path=None):
	""" parse tree awards from 'tree.txt' a return a 'dict' with all the
	tree awards parsed
	"""
	from .skilltree import TreeAward, PreCondition
	tree_awards = {}
	# get default path
	if not isinstance(path,str):
		path = TREE_FPATH

	with codecs.open(path,"r","utf-8") as file:
		for line in csv.reader(file,delimiter=";"):
			if len(line) > 0:
				lvl,name,pcs,color,xp = line
				pcs = pcs.strip().split("|") #different possible preconditions
				precs = []
				for pc in pcs:
					if len(pc) > 0:
						nodes = pc.split("+")
						precs.append(PreCondition(nodes))
				tree_awards[name] = TreeAward(name,int(lvl),precs,color,xp)
		file.close()
	return tree_awards

def read_logs (students, path=None):
	logs = {}

	# read Moodle Logs
	# logs = read_moodle_logs(students,logs)

	# read Moodle Post Ratings
	# logs = read_ratings_logs(students,logs)
	# logs = read_ratings_logs(students,logs,True)

	# read Manual Logs (for the Spreadsheet in GoogleDrive)
	# logs = read_PCMSpreadsheet_logs(logs)

	# read QR logs
	# logs = read_QR_logs(students,logs)

	# read Quizz Grades

	return logs


def read_moodle_logs (students, logs=None, path=None, remote=True):
	"""
	Get and Parse all Moodle Logs that contain every action any Moodle User
	does, like loggin into the system, clicking an a link, basically every
	interaction that users perform with the interface of the system.
	These logs come from two places, one is local the other is remote:
		* REMOTE - these come by quering MoodleDB for these logs. In this way
		it's always posible to get all MoodleLogs. So, why don't we just use
		the remote? Time! Quering the MoodleDB for all logs of all actions is
		quite expensive, one instance of PCM in Moodle can generate more than
		200 000 logs! So ideally, we just want the most recent logs.
		* LOCAL - these contain all MoodleLogs that have been processed and
		saved! Also becomes very expensive after some time.
	"""
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	def parse_moodle_logs(students, logs, loglines):
		nlines = 0				# number of non-empty lines
		ignored = 0				# some lines are not well formated ...
		maxts = 0.0				# highest timestamp
		unrec_students = {}

		for line in loglines:
			if len(line.strip()) > 0: # skip empty lines
				nlines += 1
				# retrieve information from the log line
				log_info = line.strip().split("\t")
				if len(log_info) == 7:
					course, timestamp, ip, name, action, info, url = log_info
				elif len(log_info) == 5:
					course, timestamp, ip, name, action = log_info
				else:
					# skip malformed line
					ignored += 1
					continue
				# create and add LogLine instance
				timestamp = time.strptime(timestamp.strip(),"%d %B %Y, %I:%M:%S %p")
				timestamp = time.mktime(timestamp)
				if timestamp > maxts: maxts = timestamp
				try:
					s = students[name]
				except KeyError:
					unrec_students[name] = name
				else:
					logline = LogLine(s.num, name,timestamp, action, 0, info, url)
					student_logs = logs.get(s.num,[]) # get prev logs
					student_logs.append(logline) # add the new one
					logs[s.num] = student_logs # update structure
		return logs,unrec_students,ignored,nlines, maxts
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	def read_moodle_logs_local (students, logs, path):
		""" read logs of moodle from a 'moodlelogs.txt' file """
		try:
			with open(MOODLELOGS_FPATH) as f:
				log_lines = f.readlines()[1:]
		except:
			log_lines = []
		return parse_moodle_logs(students,logs,log_lines)
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	def read_moodle_logs_remote (students, logs, path, maxts):
		""" read logs of moodle by quering MoodleDB """
		try:
			# get the new log lines from moodle
			f = urllib.request.urlopen("%s&t=%s" %(LOGFILEURL, maxts+1))
			log_lines = f.readlines()[1:]
			f.close()
			# update the remote file with the new ones
			with open(path,"a") as f:
				f.write("".join(log_lines))
		except:
			log_lines = []
		return parse_moodle_logs(students,logs,log_lines)
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if logs is None: logs = {}
	if path is None: path = MOODLELOGS_FPATH
	else: path = os.path.join(path,"moodlelogs.txt")
	# get local logs
	local = read_moodle_logs_local(students,logs,path)
	logs, unrec_students, ignored_lines, nlines, maxts = local
	if remote is False: return logs
	# get remote logs
	#if maxts == 0: maxts = 1396264388 # sometime in 2014
	#remote = read_moodle_logs_remote(students,logs,path,maxts)
	#logs, u, i, n, m = remote
	#return logs


def read_ratings_logs(students, logs=None, peer=False):
	"""
	read rated moodle post logs that are graded either by professors or
	students, these do not include quizzes nor submitted projects
	(ex: the multimedia presentation)
	"""
	url = PEERRATINGSURL if peer else RATINGSURL
	if logs is None: logs = {}
	nlines = 0
	ignored_lines = 0
	unrec_students = []

	f = urllib.request.urlopen(url)
	for l in f.readlines()[1:]:
		if len(l.strip())>0: # skip empty lines
			nlines+=1

			try:
				result = l.strip().split("\t")
			except:
				# skipping malformed line ...
				ignored_lines += 1
				continue
			ts, course, name, forum, thread, grade, url = result
			url = MOODLEBASEURL + ("peerforum/" if peer else "forum/") + url
			ts = time.strptime(ts.strip(),"%d %B %Y, %H:%M %p")
			ts = time.mktime(ts)

			s = cfuncs.find_student(name,students)
			if s:
				info = forum, thread
				action = "graded post"
				log = LogLine(s.num, name,ts, action, grade, info, url)
				student_logs = logs.get(s.num,[]) # get prev logs
				student_logs.append(log) # add the new one
				logs[s.num] = student_logs # update structure
			else:
				unrec_students.append(name)
	f.close()
	return logs



def read_moodle_votes_local(students, logs=None, peer=False):
	"""
	read rated moodle post logs that are graded either by professors or
	students, these do not include quizzes nor submitted projects
	(ex: the multimedia presentation)
	"""
	path = MOODLEVOTES_FPATH
	if logs is None: logs = {}
	nlines = 0
	ignored_lines = 0
	unrec_students = []

	with open(os.path.join(path)) as file:
		for l in file.readlines()[1:]:
			if len(l.strip())>0: # skip empty lines
				nlines+=1
				try:
					result = l.strip().split("\t")
				except:
					# skipping malformed line ...
					ignored_lines += 1
					continue

				ts, course, name, forum, thread, grade, url = result
				url = MOODLEBASEURL + ("peerforum/" if peer else "forum/") + url
				ts = time.strptime(ts.strip(),"%d %B %Y, %H:%M %p")
				ts = time.mktime(ts)

				s = cfuncs.find_student(name,students)
				if s:
					info = forum, thread
					action = "graded post"
					log = LogLine(s.num, name,ts, action, grade, info, url)
					student_logs = logs.get(s.num,[]) # get prev logs
					student_logs.append(log) # add the new one
					logs[s.num] = student_logs # update structure
				else:
					unrec_students.append(name)
	return logs

def read_QR_logs(students, logs=None):
	"""
	read logs generated from QR codes. These come from a different server than
	Moodle's, because they don't belong to that system. They are given to
	students that give relevant participations in lectures and they contribute
	to unlock some badges.
	"""
	if logs is None: logs = {}
	#f = urllib.request.urlopen(QRURL)
	#result = f.read()
	#result.split("\n")


	with codecs.open(QR_FPATH,"r") as file:
		for l in file.readlines():
			num, name, campus, action, info = l.strip().split(";")
			num = int(num)
			timestamp = time.time()
			log = LogLine(num, name,timestamp, action, 0, info)
			student_logs = logs.get(num,[]) # get prev logs
			student_logs.append(log) # add the new one
			logs[num] = student_logs # update structure
	file.close()

	return logs

def read_PCMSpreadsheet_logs (logs=None):
	"""
	reads the logs manually inputed by the course professors in a Spreadsheet
	from GoogleDrive. These logs refer to some bonus, attendance logs and other
	"""
	from .pcmspreadsheetparser import PCMSpreadsheetParser
	if logs is None: logs = {}
	parser = PCMSpreadsheetParser()
	return parser.Run(logs)

def read_PCMSpreadsheet_logs_from_csv (logs=None, path=None):
	"""
	reads the logs manually inputed by the course professors in a Spreadsheet
	from GoogleDrive which is transfered as a CSV.
	These logs refer to some bonus, attendance logs and other
	"""
	"""
	if logs is None: logs = {}
	if path is None: path = PCMSPREADSHEET_FPATH

	with codecs.open(path,"r","utf-8") as f:
		for line in f.readlines()[1:]:
			# parse line
			line_elements = line.strip().split(";")
			if len(line_elements) != 6:
				print("skipping malformed line:",line_elements)
				continue
			num,name,campus,action,xp,info = line_elements
			# transform data
			num = int(num)
			# encoding fix 2 lines
			#name = name.encode("latin1")
			#action = action.encode("latin1")
			xp = 0 if xp == "" else int(xp)

			# encoding fix
			#info = "" if info == "" else info.encode("latin1")
			# store data
			log = LogLine(num,name,time.time(),action,xp,info)
			student_logs = logs.get(num,[]) # get prev logs
			student_logs.append(log) # add the new one
			logs[num] = student_logs # update structure
	return logs
	"""

	if logs is None: logs = {}
	if path is None: path = PCMSPREADSHEET_FPATH

	log = []

	for f in ["djvg.csv", "dsl.csv", "jm.csv", "ta.csv"]:
		first = True
		for d in csv.DictReader(open(os.path.join(path,f))):
			if not first:
				num = int(d["Num"])
				xp = d["XP"]
				xp = 0 if xp == "" else int(xp)

				log = LogLine(num, d["Name"], time.time(), d["Action"], xp, d["Info"])

				student_logs = logs.get(num,[]) # get prev logs
				student_logs.append(log) # add the new one
				logs[num] = student_logs # update structure
			else:
				first = False
	return logs



def read_quiz_grades (students, logs=None):
	"""
	Query Moodle database and retrieve all quiz grades of students
	enrolled in the input course
	"""
	if logs is None: logs = {}
	nlines = 0			# Number of quiz grades obtained
	ignored_lines = 0	# Sometimes a quiz grade may be discarded (hope not!)
	unrec_students = []	# Some students may be invalid
	#url = QUIZGRADESURL

	# Query Moodle database to obtain student grades (from quizzes)
	#response = urllib.request.urlopen(url)		# Execute PHP script to query database
	#query_results = response.readlines()# Acquire results from the response
	#response.close()					# Response is no longer needed

	with open(QUIZGRADES_FPATH, "r") as file:
		query_results = file.readlines()

		# For each 'line' of the 'results' (except the heading, line: 0)
		for l in query_results[1:] :
			if len(l.strip()) > 0 : # If the line is non-empty
				nlines += 1 # Increase the of number of lines processed

				try: # Parse content of lines (separated by 'tab' ("\t"))
					result = l.strip().split("\t")
				except:
					print("Warning! Could not parse following line. Skipping!")
					print(l + "\n")
					ignored_lines += 1
					continue

				ts, course, quiz, name, grade, url = result
				# format timestamp
				ts = time.mktime(time.strptime(ts.strip(), "%d %B %Y, %H:%M %p"))
				# Because that is what we get from the logs "now" (15/02/2018)
				#name = str(name, "latin1")
				s = cfuncs.find_student(name, students)
				# Check if the student exists (is valid)
				if not s :
					# print "Invalid student: " + name
					# If the student doesn't exist add it to the
					# unrecognized student list
					if not name in unrec_students:
						unrec_students.append(name)
				else:
					num = s.num
					action = "quiz grade"
					grade = str(int(round(float(grade),0)))
					quiz_name = quiz.lower()
					if "dry run" not in quiz_name and "quiz 9" not in quiz_name:
						log = LogLine(num,name,ts,action,grade,quiz,url)
						student_logs = logs.get(num,[]) # get prev logs
						student_logs.append(log) # add the new one
						logs[num] = student_logs # update structure
	# if len(unrec_students) > 0 :
	# 	print "Unrecognized Students:", unrec_students
	# if ignored_lines:
	# 	print "Could not parse %s lines (see above)" % ignored_lines
	return logs


def read_awards_file():
	"""
	Reads the awards.txt files generated by course achievements in 19/20
	and the awards.txt generated by autogame.
	Returns dictionaries.
	"""

	awards = {"Talkative" : {}, "Class Annotator" : {}, "Apprentice" : {}, "Attentive Student" : {}, "Squire" : {}, "Replier Extraordinaire" : {}, "Focused" : {}, "Artist" : {}, "Hall of Fame" : {}, "Right on Time" : {}, "Amphitheatre Lover" : {}, "Lab Lover" : {}, "Wild Imagination" : {}, "Popular Choice Award" : {}, "Suggestive" : {}, "Golden Star" : {}, "Lab Master" : {}, "Quiz Master" : {}, "Post Master" : {}, "Book Master" : {}, "Tree Climber" : {}, "Lab King" : {}, "Presentation King" : {}, "Quiz King" : {}, "Course Emperor" : {}}
	#awards_ag = {"Talkative" : {}, "Class Annotator" : {}, "Apprentice" : {}, "Attentive Student" : {}, "Squire" : {}, "Replier Extraordinaire" : {}, "Focused" : {}, "Artist" : {}, "Hall of Fame" : {}, "Right on Time" : {}, "Amphitheatre Lover" : {}, "Lab Lover" : {}, "Wild Imagination" : {}, "Popular Choice Award" : {}, "Suggestive" : {}, "Golden Star" : {}, "Lab Master" : {}, "Quiz Master" : {}, "Post Master" : {}, "Book Master" : {}, "Tree Climber" : {}, "Lab King" : {}, "Presentation King" : {}, "Quiz King" : {}, "Course Emperor" : {}}

	queue = [AWARDS_1920, AWARDS_AUTOGAME_1920]

	results = []

	for f in queue:
		output = copy.deepcopy(awards)

		with open(f, 'r') as file:
			lines = file.readlines()
			for line in lines:
				params = line.strip("\n").split(";")
				if len(params) == 5:
					params = params[1:]

				target, field_1, field_2, field_3 = params

				if field_1 == "Grade from Quiz" or field_1 == "Grade from Lab":
					if field_1 not in output.keys():
						# if target not in dict, add
						output[field_1] = {target : [[field_2,field_3]]}
					else:
						# else, just append to key the new list
						if target not in output[field_1].keys():
							output[field_1][target] = [[field_2,field_3]]
						else:
							output[field_1][target].append([field_2,field_3])

				elif field_1 != "Skill Tree": # initial bonus + grade from presentation + badges
					if field_1 not in output.keys():
						# if target not in dict, add
						output[field_1] = {target : [[field_2]]}
					else:
						if target not in output[field_1].keys():
							output[field_1][target] = [[field_2]]
						else:
							# else, just append to key the new list
							output[field_1][target].append([field_2])

				else: # skill tree skill
					if field_3 not in output.keys():
						output[field_3] = {target : [[field_2]]}
					else:
						if target not in output[field_3].keys():
							output[field_3][target] = [[field_2]]
						else:
							output[field_3][target].append([field_2])

		if f == AWARDS_1920:
			awards_ca = output
		elif f == AWARDS_AUTOGAME_1920:
			awards_ag = output

	return awards_ca, awards_ag


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Variables
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
students = read_student_list()
# ACHIEVEMENTS = read_achievements()
# TREE_AWARDS = read_tree()
# achievements = read_achievements()
# tree_awards = read_tree()

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Print Functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def print_achievements(achievements=None): # pragma: no cover
	if achievements is None:
		achievements = read_achievements()
	i = 1
	for a in achievements:
		print("%d: %s\n" % (i,str(achievements[a])))
		i += 1

def print_students(students=None): # pragma: no cover
	if students is None:
		students = read_student_list()
	i = 1
	print("\n")
	for s in students:
		# refactor: no longer needs decoding
		#print("%d:", s.decode('latin-1').encode('utf8'))
		print("%d:", s)
		i += 1
