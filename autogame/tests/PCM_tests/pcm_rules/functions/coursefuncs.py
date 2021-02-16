#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import course, decorators
from decorators import rule_effect, rule_function
from course import award_functions as awards
from course import coursefunctions as cfuncs

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Award Functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_effect
def award_achievement(achievement,lvl,student,contributions,info=None):
	return awards.award_achievement(achievement,lvl,student,contributions,info)
@rule_effect
def award_grade(description, student, contributions, xp=None, info=None):
	return awards.award_grade(description,student,contributions,xp,info)
@rule_effect
def award_treeskill(skill, student, contributions=None):
	return awards.award_treeskill(skill,student,contributions)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Regular Functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_function
def check_existance(logs, name, tag):
	return cfuncs.check_existance(logs, name, tag)
@rule_function
def compute_lvl(val, *lvls):
	return cfuncs.compute_lvl(val,*lvls)
@rule_function
def filter_grades(logs,student,crit=None,forum=None,max_repeats=3,grades=None):
	return cfuncs.FilterGrades(logs,student,crit,forum,max_repeats,grades)
@rule_function
def filter_logs(logs, student, action=None, info=None, xp=None):
	return cfuncs.FilterLogs(logs,student,action,info,xp)
@rule_function
def get_tree():
	return cfuncs.get_tree()
@rule_function
def satisfied_skill(skill, contributions):
	return cfuncs.satisfied_skill(skill,contributions)

