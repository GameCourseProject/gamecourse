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
from . import CourseAchievements

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# CONSTANTS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
RULESPATH = os.path.join(os.getcwd(),"pcm_tests","pcm_rules")
METAPATH = os.path.join(os.getcwd(),"pcm_tests","metadata")
# Flag to enable/disable autosave in created RuleSystem
# Leave it on 'False' for time issues, since saving does cost some time
AUTOSAVE = False
# course data
STUDENTS = students
ACHIEVEMENTS = achievements
TREE = tree_awards
STUDENT_LIST = [students[s] for s in STUDENTS] # this is for CourseAchievements
# course logs
# MANUAL_LOGS = cdata.read_PCMSpreadsheet_logs()
MANUAL_LOGS = cdata.read_PCMSpreadsheet_logs_from_csv()
MOODLE_LOGS = cdata.read_moodle_logs(STUDENTS,remote=False)
MOODLE_VOTES = cdata.read_moodle_votes_local(STUDENTS)
RATINGS_LOGS = cdata.read_ratings_logs(STUDENTS)
QUIZ_LOGS = cdata.read_quiz_grades(STUDENTS)
QRLOGS = cdata.read_QR_logs(STUDENTS)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class MatchTest (BaseTestClass):
	
	def assertAchievementOutputs(self, achievement, rs_out, ca_out):
		# filter and transform CourseAchievements output
		ca_awards, ca_indicators = ca_out
		awards = {}
		count_ca_awards = 0
		for award in ca_awards:
			if award.achievement != achievement:
				continue
			count_ca_awards+= 1
			student = award.student
			student_awards = awards.get(student,[])
			student_awards.append(award)
			awards[student] = student_awards
		ca_awards = awards
		# filter and transform RuleSystem output
		rs_awards, rs_indicators = cfuncs.transform_rulesystem_output(rs_out)
		awards = {}
		count_rs_awards = 0
		for award in rs_awards:
			if award.achievement != achievement:
				continue
			count_rs_awards+= 1
			student = award.student
			student_awards = awards.get(student,[])
			student_awards.append(award)
			awards[student] = student_awards
		rs_awards = awards
		# assert results
		# 1st) assert awards
		# assert number of awards
		self.assertEqual(count_rs_awards,count_ca_awards)
		# assert number of students
		self.assertEqual(len(rs_awards),len(ca_awards))
		for student in rs_awards:
			# assert if students in rs_awards are the same in the ca_awards
			self.assertIn(student,ca_awards)
			# assert if all students have the same number of awards
			# in the RuleSystem and the CourseAchievements outputs
			self.assertEqual(len(rs_awards[student]),len(ca_awards[student]))
			# assert if all those awards are the EQUAL
			for award in rs_awards[student]:
				self.assertIn(award,ca_awards[student])
		# 2nd) assert indicators
		for student in rs_indicators:
			# students with indicators must also have awards (from the
			# RuleSystem) and must also have indicators (from CourseAchievements)
			self.assertIn(student,rs_awards)
			self.assertIn(student,ca_indicators)
			# assert if students have indicators in the given achivement in both
			# outputs
			self.assertIn(achievement,rs_indicators[student])
			self.assertIn(achievement,ca_indicators[student])
			# assert if the student indicators for the achievement have the same
			# value and lines
			rs_sindicators = rs_indicators[student][achievement]
			ca_sindicators = ca_indicators[student][achievement]
			self.assertEqual(len(rs_sindicators),2)
			self.assertEqual(len(ca_sindicators),2)
			val1 = str(rs_sindicators[0])
			val2 = str(ca_sindicators[0])
			self.assertEqual(val1,val2) # assert val
			self.assertEqual(rs_sindicators[1],ca_sindicators[1]) # assert lines

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Match tests with every single achievement in PCM1718
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAchievements (MatchTest):

	def test_01 (self):
		for el in MANUAL_LOGS:
			print(el, MANUAL_LOGS[el], "\n\n")
		print("\n\n\n\n\n\n\n\n\n\n\n\n\n")
		for el in MOODLE_LOGS:
			print(el, MOODLE_LOGS[el],"\n\n")
		print("\n\n\n\n\n\n\n\n\n\n\n\n\n")
		for el in MOODLE_VOTES:
			print(el, MOODLE_VOTES[el],"\n\n")


		""" Match Test - Amphitheatre Lover """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_amphitheatre_lover.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Amphitheatre Lover"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_02 (self):
		""" Match Test - Apprentice """
		logs = dict(RATINGS_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_apprentice.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Apprentice"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_03 (self):
		""" Match Test - Artist """
		logs = dict(RATINGS_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_artist.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Artist"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_04 (self):
		""" Match Test - Attentive Student """
		logs = dict(RATINGS_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_attentive_student.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Attentive Student"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_05 (self):
		""" Match Test - Book Master """
		logs = dict(RATINGS_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_attentive_student.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Attentive Student"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_06 (self):
		""" Match Test - Class Annotator """
		logs = dict(MOODLE_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_class_annotator.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Class Annotator"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_07 (self):
		""" Match Test - Course Emperor """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_course_emperor.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Course Emperor"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_08 (self):
		""" Match Test - Golden Star """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_golden_star.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Golden Star"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_09 (self):
		""" Match Test - Hall of Fame """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_hall_of_fame.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Hall of Fame"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	# test 10 no longer used
	"""
	def test_10 (self):
		# Match Test - Hollywood Wannabe
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_hollywood_wannabe.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Hollywood Wannabe"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	"""
	
	def test_11 (self):
		""" Match Test - Lab King """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_lab_king.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Lab King"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_12 (self):
		""" Match Test - Lab Lover """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_lab_lover.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Lab Lover"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_13 (self):
		""" Match Test - Lab Master """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_lab_master.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Lab Master"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	
	def test_14 (self):
		""" Match Test - Popular Choice Award """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_popular_choice_award.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Popular Choice Award"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_15 (self):
		""" Match Test - Post Master """
		logs = dict(MOODLE_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_post_master.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Post Master"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_16 (self):
		""" Match Test - Presentation King """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_presentation_king.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Presentation King"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	# test 17 no longer used 
	"""
	def test_17 (self):
		# Match Test - Presentation Zen Master
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_presentation_zen_master.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Presentation Zen Master"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)
	"""

	def test_18 (self):
		""" Match Test - Quiz King """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_quiz_king.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Quiz King"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_19 (self):
		""" Match Test - Quiz Master """
		logs = dict(QUIZ_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_quiz_master.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Quiz Master"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_20 (self):
		""" Match Test - Replier Extraordinaire """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_replier_extraordinaire.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Replier Extraordinaire"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_21 (self):
		""" Match Test - Right on Time """
		logs = dict(MANUAL_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_right_on_time.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Right on Time"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_22 (self):
		""" Match Test - Squire """
		logs = dict(RATINGS_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_squire.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Squire"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_23 (self):
		""" Match Test - Talkative """
		logs = dict(QRLOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_talkative.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Talkative"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_24 (self):
		""" Match Test - Tree Climber """
		logs = dict(MOODLE_VOTES)
		scope = {"METADATA": METADATA}
		# fire rule system"
		path = os.path.join(RULESPATH,"rule_tree_climber.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Tree Climber"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_25 (self):
		""" Match Test - Wild Imagination """
		logs = dict(MOODLE_VOTES)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_wild_imagination.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Wild Imagination"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)



	# new tests: 2019/2020
	# *****************************++

	def test_26 (self):
		""" Match Test - Suggestive """
		logs = dict(RATINGS_LOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_suggestive.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Suggestive"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)

	def test_27 (self):
		""" Match Test - Focused """
		logs = dict(QRLOGS)
		scope = {"METADATA": METADATA}
		# fire rule system
		path = os.path.join(RULESPATH,"rule_focused.txt")
		rs = RuleSystem(path,AUTOSAVE)
		rs_output = rs.fire(STUDENTS,logs,scope)
		# run course achievements
		a = "Focused"
		achievements = [ACHIEVEMENTS[a]]
		ca_output = run_CourseAchievements(STUDENT_LIST,logs,achievements,[])
		# match the outputs
		self.assertAchievementOutputs(a,rs_output,ca_output)



# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# AUXILIAR functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def run_CourseAchievements (students, logs, achievements=None, tree_awards=None):
	"""
	For the given students with the given logs compute the awards and indicators
	from the achievements and tree.
	This is function was made for TEST PURPOSES. One of the objectives of
	GameRules is to be more efficient than the old system. But, since it
	will only overwrite the achievements computation part, we will focus exactly
	on that, thus, everything else (like reading and storing data) will be
	removed from the function.
	Another thing we wanna make sure GameRules fulfills is producing the same
	effects as the old one. So this function will actually return the 'awards'
	and 'indicators' instead of just storing them in a file.
	"""
	if achievements is None:
		from course.award_functions import achievements
		achievements = [achievements[k] for k in achievements]
	if tree_awards is None:
		from course.award_functions import tree_awards
		tree_awards = [tree_awards[k] for k in tree_awards]
	return CourseAchievements.run(students,logs,achievements,tree_awards)