#!/usr/bin/env python
# -*- coding: utf-8 -*-


from base_test_class import BaseTestClass
from context import course, RuleSystem
from course import coursedata as cdata
from course import coursefunctions as cfuncs
from course.coursedata import students
from course.coursedata import METADATA
from course.award_functions import achievements
from course.award_functions import tree_awards

import os
import sys


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# CONSTANTS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
RULESPATH = os.path.join(os.getcwd(),"pcm_tests","pcm_rules")
METAPATH = os.path.join(os.getcwd(),"pcm_tests","metadata")

# course data
STUDENTS = students
ACHIEVEMENTS = achievements
TREE = tree_awards
STUDENT_LIST = [students[s] for s in STUDENTS] # this is for CourseAchievements

awards_ca, awards_ag = cdata.read_awards_file()

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class MatchTest (BaseTestClass):
	
	def assertAchievementOutputs(self, achievement, ca_out, ag_out):
		
		ca_achievement = ca_out[achievement]
		ag_achievement = ag_out[achievement]
	
		# check if nr of students is the same
		self.assertEqual(len(ca_achievement.keys()), len(ag_achievement.keys()))

		for student in ca_achievement.keys():

			if achievement == "Post Master" and student == "86379":
				# exception for the student who is missing upload post participations
				continue

			# check if student key is also in autogame results
			self.assertIn(student, ag_achievement.keys())
			
			for el in ca_achievement[student]:
				self.assertIn(el, ag_achievement[student])


			for el in ag_achievement[student]:

				self.assertIn(el, ca_achievement[student])


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Match tests with every single badge/achievement in PCM1718
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestBadges (MatchTest):

	def test_01 (self):
		""" Badge Test - Amphitheatre Lover """
		a = "Amphitheatre Lover"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_02 (self):
		""" Badge Test - Apprentice """
		a = "Apprentice"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_03 (self):
		""" Badge Test - Artist """
		a = "Artist"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_04 (self):
		""" Badge Test - Attentive Student """
		a = "Attentive Student"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_05 (self):
		""" Badge Test - Book Master """
		a = "Book Master"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_06 (self):
		""" Badge Test - Class Annotator """
		a = "Class Annotator"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_07 (self):
		""" Badge Test - Course Emperor """
		a = "Course Emperor"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_08 (self):
		""" Badge Test - Focused """
		a = "Focused"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_09 (self):
		""" Badge Test - Golden Star """
		a = "Golden Star"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_10 (self):
		""" Badge Test - Hall of Fame """
		a = "Hall of Fame"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_11 (self):
		""" Badge Test - Lab King """
		a = "Lab King"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_12 (self):
		""" Badge Test - Lab Lover """
		a = "Lab Lover"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_13 (self):
		""" Badge Test - Lab Master """
		a = "Lab Master"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_14 (self):
		""" Badge Test - Popular Choice Award """
		a = "Popular Choice Award"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_15 (self):
		""" Badge Test - Post Master """
		a = "Post Master"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_16 (self):
		""" Badge Test - Presentation King """
		a = "Presentation King"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_17 (self):
		""" Badge Test - Quiz Kind """
		a = "Quiz King"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_18 (self):
		""" Badge Test - Quiz Master """
		a = "Quiz Master"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_19 (self):
		""" Badge Test - Replier Extraordinaire """
		a = "Replier Extraordinaire"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_20 (self):
		""" Badge Test - Right on Time """
		a = "Right on Time"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_21 (self):
		""" Badge Test - Squire """
		a = "Squire"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_22 (self):
		""" Badge Test - Suggestive """
		a = "Suggestive"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_23 (self):
		""" Badge Test - Talkative """
		a = "Talkative"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_24 (self):
		""" Badge Test - Tree Climber """
		a = "Tree Climber"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_25 (self):
		""" Badge Test - Wild Imagination """
		a = "Wild Imagination"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Match tests with every single grade in PCM1718
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestGrades (MatchTest):

	def test_01 (self):
		""" Grade Test - Lab """
		a = "Grade from Lab"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_02 (self):
		""" Grade Test - Quiz """
		a = "Grade from Quiz"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


	def test_03 (self):
		""" Grade Test - Presentation """
		a = "Grade from Presentation"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Match tests with every single prize of type "other" in PCM1718
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestOther (MatchTest):

	def test_01 (self):
		""" Other Test - Initial Bonus """
		a = "Initial Bonus"
		self.assertAchievementOutputs(a, awards_ca, awards_ag)