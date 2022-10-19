#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .prize import Prize
from .logline import LogLine
from .coursedata import students
from .coursedata import read_tree

import time

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# CONSTANTS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
DEFAULT_MAXREPEATS = 3
MOODLEBASEURL = "http://groups.ist.utl.pt/~pcm.daemon/moodle/mod/"
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

def check_existance(logs, student, tag):
	"""
	returns TRUE if there is a log with an action equal to the tag,
	returns FALSE otherwise
	"""
	return len(FilterLogs(logs,student,action=tag))>0

def compute_lvl (val, *lvls):
	"""
	The purpose of this function is to determine which level a value belongs to.
	It does so by matching the 'val' against the lvl requirements in 'lvls',
	when the 'val' is GREATER OR EQUAL to a lvl requirement it returns that lvl
	EX:
		> compute_lvl(val=5, 2,4,6) ==> returns 2
		> compute_lvl(val=5, 1,5) ==> returns 2
		> compute_lvl(val=5, 10,10,10) ==> returns 0
		> compute_lvl(val=5, 10,5,1) ==> returns 3
		> compute_lvl(val=5 ) ==> returns 0
		> compute_lvl(val=5, [2,4,6]) ==> returns 2
		> compute_lvl(val=5, (10,5,1)) ==> returns 3
	"""

	if len(lvls)==0: # no level specified?
		return 0 # then return the least lvl specified
	if isinstance(lvls[0],(tuple,list)):
		lvls = lvls[0]
	index = len(lvls)-1
	while index >= 0:
		if val >= lvls[index]:
			return index+1
		index-= 1
	return 0

def find_student (name, students):
	"""
	finds and returns the first student in students with the given name,
	else, returns false
	"""
	if isinstance(name,str):
		# refactor : no longer needs decoding
		#name = name.decode("latin1")
		name = name
	for key in students:
		student = students[key]
		if student.name == name:
			return student
	return False

def satisfied_skill (skill, logs):
	"""
	PREDICATE - returns TRUE if the logs given can satisfy the skill from the
	tree skill
	"""
	from .award_functions import tree_awards
	ta = tree_awards[skill]
	satisfied_skills = [l.info[1] for l in logs]
	return substringin(ta.name,satisfied_skills) \
		and ta.Satisfied(satisfied_skills)

def substringin (string, list_strings):
	"""
	PREDICATE - returns TRUE if string 's' is IN any string of the list 'l',
	FALSE otherwise
	"""
	for s in list_strings:
		if string in s:
			return True
	return False

def transform_rulesystem_output(output):
	""" takes the rulesystem output (from the 'fire' method) and returns a list
	of awards and the student_achievement_indicators
	"""
	p = Prize()
	for student in output:
		for rule in output[student]:
			for effect in output[student][rule]:
				if isinstance(effect,Prize):
					p.join(effect)
	awards = []
	for k in p.awards: awards += p.awards[k]
	return awards, p.indicators

def get_tree():
	"""
	gets tree from course data so that it can be consulted when writing
	rules in text files
	"""
	tree = read_tree()
	return tree

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# NOT TESTED
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# FILTER FUNCTIONS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def FilterLogs(logs, student, action=None, info=None, xp=None):
	"""
	Filter logs by Student, log action (if specified), log info (if specified)
	and by log xp (if specified)
	returns a list with all the filtered logs
	"""
	# check if the student exists and corresponds  to the student ID
	try:
		if isinstance(student,str):
			student = students[student].num
		else:
			if not isinstance(student,int):
				student = student.num
			students[student] # check if the student exists
			logs[student] # check if the student has logs
	except KeyError:
		return []
	# if not (type(student)==int or type(student)==unicode or type(student)==str) :
	# 	student=student.num
	res=[]
	# if not student in logs:
	# 	return []
	for l in logs[student]:
		ts = l.timestamp
		a = l.action
		i = l.info
		x = l.xp

		if action:
			if info:
				if type(info)==list:
					if a==action and i in info:
						if xp and x==xp:
							res.append(l)
						else:
							res.append(l)
				else:
					if a==action and i == info:
						if xp and x==xp:
							res.append(l)
						else:
							res.append(l)
			elif xp:
				if a==action and x==xp:
					res.append(l)
			elif a == action:
				res.append(l)
		elif info:
			if type(info)==list:
				if i in info:
					if xp and x==xp:
						res.append(l)
					else:
						res.append(l)
			else:
				if i == info:
					if xp and x==xp:
						res.append(l)
					else:
						res.append(l)
		elif xp:
			if x==xp:
				res.append(l)
		else:
			res.append(l)
	return res

def FilterGrades(logs, student,
	crit=None, forum=None, max_repeats=DEFAULT_MAXREPEATS, grades=None):
	"""
	Filter logs by Student, criteria (if specified), by forum (if specified)
	and by grades (if specified). If specified, it also allow 'X' amount of
	repeated logs.
	It returns three things:
		> the filtered logs
		> the XP values of the filtered logs
		> and the sum of all the XP values of the filtered logs
	"""

	# check if the student exists and corresponds  to the student ID
	try:
		if isinstance(student,str):
			student = students[student].num
		else:
			if not isinstance(student,int):
				student = student.num
			students[student] # check if the student exists
			logs[student] # check if the student has logs
	except KeyError:
		return [],[],0

	views=[]
	vals=[]
	for l in logs[student]:
		a = l.action
		x = l.xp
		u = l.url
		if a == "graded post":
			f = l.info[0]
			t = l.info[1]
			if crit and forum:
				if crit in t and forum in f:
					views.append(l)
			elif crit:
				if crit in t:
					views.append(l)
			elif forum:
				if forum in f:
					views.append(l)
			else:
				# no criteria specified? ... Here goes everything!
				views.append(l)
	if max_repeats:
		tmp={}
		newviews=[]
		for l in views:
			tmp[l.info]=tmp.get(l.info,[])+[l]
		lst=tmp.values()
		for l in lst:
			if len(l)>max_repeats:
				newviews=newviews+l[:max_repeats]
			else:
				newviews=newviews+l
		views=newviews
	if not grades:
		vals = [int(l.xp) for l in views]
	else:
		tmpviews=[]
		vals=[]
		for v in views:
			if int(v.xp) in grades:
				tmpviews.append(v)
				vals.append(int(v.xp))
		views=tmpviews
	return views, vals, sum(vals)

def FilterLogsGlobal(logs, action = None, info = None):
	res=[]
	for l in logs:
		res+=FilterLogs(logs,l,action,info)
	return res
