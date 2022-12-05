#!/usr/bin/env python
# -*- coding: utf-8 -*-

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# IMPORTANT: This is to import the decorators module which is outside of the
# package, normally this shoudn't be done in this fashion, it would be
# preferable if the decorators package was directly copied into this package
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
import os
import sys
path = os.path.join(os.path.dirname(__file__), '..\\..\\..\\..')
sys.path.insert(0, os.path.abspath(path))
import decorators
from decorators import rule_effect, rule_function
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

from .award import Award
from .prize import Prize
# from coursedata import ACHIVEMENTS, TREE_AWARDS
from . import coursedata
achievements = coursedata.read_achievements()
tree_awards = coursedata.read_tree()

@rule_effect
def award_achievement(
	achievement_name, lvl, student, contributions, info=None):
	""" awards the student with the given achievement """
	achievement = achievements[achievement_name]
	info = str(False) if info is None else str(info)
	awards = []
	for level in range(lvl):
		xp = achievement.xp[lvl-1]
		awards.append(Award(student,achievement_name,level+1,xp,True,info=info))
	awards = {student: awards}
	indicators = {student: {achievement_name: (info, contributions)}}
	return Prize(awards,indicators)

@rule_effect
def award_grade_aux(description, student, contributions, xp=None, info=None):
	""" awards the student based on a contributions to a grade component """
	awards = []
	indicators = {}
	lvl = 0
	
	# contributions can either be a collection of contributions that are
	# relevant for the grade or an integer corresponding to the amount
	# of awards to be awarded with a certain XP
	if isinstance(contributions,int):
		# if it's just an integer than the number of awards will be the number
		# of the contributions
		xp = xp if xp else 0
		info = info if info else ""
		for i in range(contributions):
			awards.append(Award(student,description,lvl,xp,False,info=info))
	else:
		# otherwise, for each contribution an award will be given
		# the XP and INFO of the awards will depend on the arguments passed onto
		# the function

		if xp is None and info is None:
			# in this case, since no XP or INFO was indicated, the values that
			# are going to be used will be the values of each contribution
			for c in contributions:
				awards.append(Award(student,description,lvl,c.xp,False,
					info=c.info if c.info else ""))
		elif xp is None:
			# in this case, only the XP was left blank, so the info used will
			# the given info (from the argument) and the XP will come from the
			# contributions
			for c in contributions:
				awards.append(Award(student,description,lvl,c.xp,False,
					info=info))
		else:
			# last case scenario, the XP used will be the given XP (passed into
			# the function as argument) and the value of the INFO will come from
			# each contribution
			for c in contributions:
				awards.append(Award(student,description,lvl,xp,False,
					info=c.info if c.info else ""))
	awards = {student: awards}
	
	return Prize(awards, indicators)

@rule_effect
def award_treeskill (skill, student, contributions=None):
	""" awards the student based on the skill """
	if contributions is None:
		contributions = []

	ta = tree_awards[skill]
	award = [Award(student,"Skill Tree",0,int(ta.xp),False,info=skill)]
	awards = {student: award}
	indicators = {}

	for c in contributions:
		if skill in c.info[1]:
			indicators[student] = {skill: (int(c.xp), [c])}
	
	return Prize(awards, indicators)