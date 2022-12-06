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
from gamerules.connector.gamecourse_connector import *

import os
import sys
import config
import time
import warnings
import shutil

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# CONSTANTS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

# course data
STUDENTS = students
ACHIEVEMENTS = achievements
TREE = tree_awards
STUDENT_LIST = [students[s] for s in STUDENTS] # this is for CourseAchievements

SETUP_DAT_PATH = os.path.join(os.getcwd(), "data")

LOAD_STUDENTS_PATH = os.path.join(os.getcwd(),"autogame_tests","rules","load-tests")
LOAD_RULES_PATH = os.path.join(os.getcwd(),"autogame_tests","rules","rule-load-tests")
AUTOSAVE = False

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class MatchTest (BaseTestClass):
	
	def assertAchievementOutputs(self, achievement, ca_out, ag_out):
		return

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Incremental load tests for number of targets
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestStudentLoad (MatchTest):

	def setUp(self):
		warnings.filterwarnings(action="ignore", category=DeprecationWarning)
		"""
		with warnings.catch_warnings():
			warnings.simplefilter('ignore', DeprecationWarning)"""

	def tearDown(self):
		delete_awards("3")

	def test_01 (self):
		""" Student Load - 10 students """
		course_nr = "3"
		config.COURSE = course_nr

		start = time.time()

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		rs = RuleSystem(LOAD_STUDENTS_PATH, AUTOSAVE)

		students = get_targets(course_nr, limit=10)
		
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_01")
		print(60 * "-")
		print("Nr students: ", str(len(students)))
		print("Elapsed time: ", stop-start)
		nr = count_awards(course_nr)
		print("Awards given: ", nr)
		print("\n")
		
		

	def test_02 (self):
		""" Student Load - 50 students """
		course_nr = "3"
		config.COURSE = course_nr

		start = time.time()

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		rs = RuleSystem(LOAD_STUDENTS_PATH, AUTOSAVE)

		students = get_targets(course_nr, limit=50)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_02")
		print(60 * "-")
		print("Nr students: ", str(len(students)))
		print("Elapsed time: ", stop-start)
		nr = count_awards(course_nr)
		print("Awards given: ", nr)
		print("\n")
		


	def test_03 (self):
		""" Student Load - 100 students """
		course_nr = "3"
		config.COURSE = course_nr

		start = time.time()

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		rs = RuleSystem(LOAD_STUDENTS_PATH, AUTOSAVE)

		students = get_targets(course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_03")
		print(60 * "-")
		print("Nr students: ", str(len(students)))
		print("Elapsed time: ", stop-start)
		nr = count_awards(course_nr)
		print("Awards given: ", nr)
		print("\n")
		


	def test_04 (self):
		""" Student Load - 200 students """
		course_nr = "3"
		config.COURSE = course_nr

		start = time.time()

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		rs = RuleSystem(LOAD_STUDENTS_PATH, AUTOSAVE)

		students = get_targets(course_nr, limit=200)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_04")
		print(60 * "-")
		print("Nr students: ", str(len(students)))
		print("Elapsed time: ", stop-start)
		nr = count_awards(course_nr)
		print("Awards given: ", nr)
		print("\n")
		
	def test_05 (self):
		""" Student Load - 400 students """
		course_nr = "3"
		config.COURSE = course_nr

		start = time.time()

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		rs = RuleSystem(LOAD_STUDENTS_PATH, AUTOSAVE)

		students = get_targets(course_nr, limit=400)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_05")
		print(60 * "-")
		print("Nr students: ", str(len(students)))
		print("Elapsed time: ", stop-start)
		nr = count_awards(course_nr)
		print("Awards given: ", nr)
		print("\n")
		


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Incremental load tests for number of targets
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestRuleLoad (MatchTest):


	def setUp(self):
		warnings.filterwarnings(action="ignore", category=DeprecationWarning)

	def setUp(self):
		self.course_nr = "3"
		config.COURSE = self.course_nr

	def tearDown(self):
		delete_awards("3")
		shutil.rmtree(SETUP_DAT_PATH)

	
	def test_01(self):
		""" Rule Load - 5 rules """

		start = time.time()
		number_rules = "5"

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		RULES_PATH = os.path.join(LOAD_RULES_PATH, number_rules)
		rs = RuleSystem(RULES_PATH, AUTOSAVE)

		students = get_targets(self.course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_01")
		print(60 * "-")
		print("Nr rules: ", number_rules)
		print("Elapsed time: ", stop-start)
		nr = count_awards(self.course_nr)
		print("Awards given: ", nr)
		print("\n")

	def test_02(self):
		""" Rule Load - 10 rules """

		start = time.time()
		number_rules = "10"

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		RULES_PATH = os.path.join(LOAD_RULES_PATH, number_rules)
		rs = RuleSystem(RULES_PATH, AUTOSAVE)

		students = get_targets(self.course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_02")
		print(60 * "-")
		print("Nr rules: ", number_rules)
		print("Elapsed time: ", stop-start)
		nr = count_awards(self.course_nr)
		print("Awards given: ", nr)
		print("\n")


	def test_03(self):
		""" Rule Load - 25 rules """

		start = time.time()
		number_rules = "25"

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		RULES_PATH = os.path.join(LOAD_RULES_PATH, number_rules)
		rs = RuleSystem(RULES_PATH, AUTOSAVE)

		students = get_targets(self.course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_03")
		print(60 * "-")
		print("Nr rules: ", number_rules)
		print("Elapsed time: ", stop-start)
		nr = count_awards(self.course_nr)
		print("Awards given: ", nr)
		print("\n")


	def test_04(self):
		""" Rule Load - 50 rules """

		start = time.time()
		number_rules = "50"

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		RULES_PATH = os.path.join(LOAD_RULES_PATH, number_rules)
		rs = RuleSystem(RULES_PATH, AUTOSAVE)

		students = get_targets(self.course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_04")
		print(60 * "-")
		print("Nr rules: ", number_rules)
		print("Elapsed time: ", stop-start)
		nr = count_awards(self.course_nr)
		print("Awards given: ", nr)
		print("\n")

	

	def test_05(self):
		""" Rule Load - 75 rules """

		start = time.time()
		number_rules = "75"

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		RULES_PATH = os.path.join(LOAD_RULES_PATH, number_rules)
		rs = RuleSystem(RULES_PATH, AUTOSAVE)

		students = get_targets(self.course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_05")
		print(60 * "-")
		print("Nr rules: ", number_rules)
		print("Elapsed time: ", stop-start)
		nr = count_awards(self.course_nr)
		print("Awards given: ", nr)
		print("\n")


	def test_06(self):
		""" Rule Load - 100 rules """

		start = time.time()
		number_rules = "100"

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		RULES_PATH = os.path.join(LOAD_RULES_PATH, number_rules)
		rs = RuleSystem(RULES_PATH, AUTOSAVE)

		students = get_targets(self.course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_06")
		print(60 * "-")
		print("Nr rules: ", number_rules)
		print("Elapsed time: ", stop-start)
		nr = count_awards(self.course_nr)
		print("Awards given: ", nr)
		print("\n")


	def test_07(self):
		""" Rule Load - 150 rules """

		start = time.time()
		number_rules = "150"

		scope, logs = {"METADATA" : METADATA, "null": None}, {}
		RULES_PATH = os.path.join(LOAD_RULES_PATH, number_rules)
		rs = RuleSystem(RULES_PATH, AUTOSAVE)

		students = get_targets(self.course_nr, limit=100)
		rs_output = rs.fire(students,logs,scope)

		stop = time.time()

		print("\n" + 60 * "-")
		print("test_05")
		print(60 * "-")
		print("Nr rules: ", number_rules)
		print("Elapsed time: ", stop-start)
		nr = count_awards(self.course_nr)
		print("Awards given: ", nr)
		print("\n")
		
