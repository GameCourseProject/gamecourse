#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import course, decorators
from decorators import rule_effect, rule_function
from course import award_functions as awards
from course import coursefunctions as cfuncs

@rule_effect
def award_achievement(achievement,lvl,student,contributions,info=None):
	return awards.award_achievement(achievement,lvl,student,contributions,info)

@rule_effect
def award_grade_aux(description, student, contributions, xp=None, info=None):
	return awards.award_grade_aux(description,student,contributions,xp,info)

@rule_effect
def award_treeskill(skill, student, contributions=None):
	return awards.award_treeskill(skill,student,contributions)

@rule_function
def satisfied_skill (skill, logs):
	return cfuncs.satisfied_skill(skill,logs)