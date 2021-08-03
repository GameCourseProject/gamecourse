#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .utils import rule_effect, rule_function
from gamerules.connector import gamecourse_connector as connector
import sys
import config

from datetime import datetime 

## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## Regular Functions
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_function
def count(collection):
	""" just another name for function 'len' """
	return len(collection)

@rule_function
def rule_unlocked(name,target=None):
	""" Returns True if the target has unlocked any rule with the given name """
	from ..namespace import rule_system as rs
	if target is None:
		from ..namespace import target as t
		target = t
	try:
		rules = rs.__data__.target_data.target_rules(target)
	except Exception:
		return False
	else:
		for rule in rules:
			if rule == name:
				return True
		return False

@rule_function
def effect_unlocked(val,target=None):
	""" Returns True if the target has unlocked the given effect """
	from ..namespace import rule_system as rs
	if target is None:
		from ..namespace import target as t
		target = t
	try:
		to = rs.__data__.target_data.target_outputs(target)
	except Exception:
		return False
	else:
		for output in to:
			for effect in output.effects():
				if effect.val() == val:
					return True
		return False

@rule_function
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


@rule_function
def compute_rating (logs):
	"""
	Sums the ratings of a series of logs
	"""
	rating = 0
	for logline in logs:
		rating += logline.rating
	return rating

@rule_function
def get_rating (logs):
	"""
	Returns the rating column of a logline
	"""
	if len(logs) == 0:
		return 0
	elif len(logs) == 1:
		for logline in logs:
			rating = logline.rating
			return rating
	else:
		ts = datetime.min
		for logline in logs:
			if logline.date > ts:
				rating = logline.rating
				ts = logline.date
		return rating
	

@rule_function
def get_campus(target):
	"""
	Returns the campus of a given student
	"""
	result = connector.get_campus(target)
	return result


@rule_function
def filter_excellence(logs, tiers, classes):
	"""
	Filters the list of logs in a way that only
	participations within excellence thresholds are 
	returned.
	"""

	if len(tiers) != len(classes):
		print("ERROR: number of tiers does not match number of classes")

	if len(logs) > 0:
		user = logs[0].user
		desc = logs[0].log_type

	labs = list(range(1, sum(classes) + 1))
	filtered = []

	for i in range(0, len(tiers)):
		tier = tiers[i]
		nr_classes = classes[i]
		tier_labs = labs[:nr_classes]

		for line in logs:
			if int(line.description) in tier_labs and int(line.rating) >= tier:
				filtered.append(line)
		labs = labs[nr_classes:]
	
	return filtered


@rule_function
def filter_quiz(logs, desc):
	"""
	Filters the list of logs in a way that quiz 9
	is removed
	"""

	filtered_logs = []

	for line in logs:
		if line.description != desc or line.description != "Dry Run":
			filtered_logs.append(line)	
	return filtered_logs

@rule_function
def exclude_worst(logs, last):
	"""
	Will calculate the adjustment for getting rid of the
	worst quiz in the bunch
	"""

	worst = int(config.metadata["quiz_max_grade"])
	fix = last

	if len(logs) == 9:
		for line in logs:
			worst = min(int(line.rating), worst)

		if len(last) == 1:
			last_quiz = max(int(last[0].rating) - worst, 0)
			fix[0].rating = last_quiz
	
	return fix
	
@rule_effect
def print_info(text):
		""" 
		returns the output of a skill and writes the award to database
		"""
		sys.stderr.write(str(text))

		

## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## Decorated Functions
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_effect
def transform (val):
	""" wraps any value into a rule effect, this way it will be part of the rule
	output
	"""
	return val


@rule_effect
def award_badge(target, badge, lvl, contributions=None, info=None):
	""" 
	returns the output of a badge and writes the award to database
	"""
	result = connector.award_badge(target, badge, lvl, contributions, info)
	return result


@rule_effect
def award_skill(target, skill, rating, contributions=None, use_wildcard=False, wildcard_tier=None):
	""" 
	returns the output of a skill and writes the award to database
	"""
	result = connector.award_skill(target, skill, rating, contributions, use_wildcard, wildcard_tier)
	return result

@rule_effect
def award_prize(target, reward_name, xp, contributions=None):
	""" 
	returns the output of a skill and writes the award to database
	"""
	connector.award_prize(target, reward_name, xp, contributions)
	# TODO possible upgrade: returning indicators to include these types of prizes as well
	return

@rule_effect
def award_grade(target, item, contributions=None, extra=None):
	""" 
	returns the output of a skill and writes the award to database
	"""
	connector.award_grade(target, item, contributions, extra)
	# TODO possible upgrade: returning indicators to include these types of prizes as well
	return



## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## GameCourse Wrapper Functions
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_function
def gc(library, name, *args):
		# this is a wrapper that handles gamecourse specific functions.
	# every rule with a function that has syntax 
	#
	# 	GC.library.function(arg1, arg2)
	#
	# will be mapped on the parser to function
	#
	#	gc("library", "function", arg1, arg2)
	#
	# This wrapper will handle the function request and pass it to php

	from ..connector.gamecourse_connector import get_dictionary, check_dictionary, call_gamecourse
	
	# TODO fix condition : must check the dictionary first
	#in_dictionary = check_dictionary(library, name)
	#dictionary = get_dictionary()

	if True:
		data = call_gamecourse(config.course, library, name, list(args)) 

	return data
	
