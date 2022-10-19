#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import course
cdata = course.coursedata

import os

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCourseData(BaseTestClass):

	def assertTreeAwards (self, tree):
		self.assertIsInstance(tree,dict)
		for ta in tree:
			self.assertTreeAward(tree[ta])
			self.assertIsInstance(tree[ta].name,str)
			self.assertIsInstance(tree[ta].level,int)
			self.assertIsInstance(tree[ta].color,str)
			self.assertIsInstance(int(tree[ta].xp),int)
			self.assertIsInstance(tree[ta].PCs,list)
			for pc in tree[ta].PCs:
				self.assertPreCondition(pc)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# read_tree([path]): dictionary containning all the course TreeAwards by name
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestReadTree(TestCourseData):

	def test_01 (self):
		"""
		test read_tree with default arguments, meaning the default tree
		is expected
		"""
		tree = cdata.read_tree()
		self.assertTreeAwards(tree)
		self.assertEqual(len(tree),20)

	def test_02 (self):
		""" test read_tree with given path to the default tree file	"""
		path = os.path.join(os.getcwd(),"course_tests","metadata","tree_orig.txt")
		tree = cdata.read_tree(path)
		self.assertTreeAwards(tree)
		self.assertEqual(len(tree),25)

	def test_03(self):
		""" test read_tree with given path to the default tree file	"""
		path = os.path.join(os.getcwd(),"course_tests","metadata","tree_1.txt")
		tree = cdata.read_tree(path)
		self.assertTreeAwards(tree)
		self.assertEqual(len(tree),4)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# read_student_list([path]): dictionary containning all the course students
# by name
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestReadStudentList (TestCourseData):

	def test_01 (self):
		"""
		test read_student_list with default arguments, meaning the students list
		from PCM1718 is expected
		1920 actually
		"""
		students = cdata.read_student_list()
		self.assertIsInstance(students,dict)
		for sk in students:
			self.assertStudent(students[sk])
		self.assertEqual(len(students),100)

	def test_02 (self):
		"""
		test read_student_list with a path to the default student list
		"""
		path = os.path.join(os.getcwd(),"course_tests","metadata","1920","course","students.txt")
		students = cdata.read_student_list(path)
		self.assertIsInstance(students,dict)
		for sk in students:
			self.assertStudent(students[sk])
		self.assertEqual(len(students),100)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# read_achievements([path]): dictionary containning all the course achievemetns
# by name
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAchievements (TestCourseData):

	def test_01 (self):
		"""
		test read_achievements with default arguments, meaning the default
		achievements from PCM1718 are expected
		"""
		achievements = cdata.read_achievements()
		self.assertIsInstance(achievements,dict)
		for ak in achievements:
			self.assertAchievement(achievements[ak])
		self.assertEqual(len(achievements),25)

	def test_02 (self):
		"""
		test read_achievements with a path to the default achievements file
		from PCM1718
		"""
		path = os.path.join(os.getcwd(),"course_tests","metadata","achievements.txt")
		achievements = cdata.read_achievements()
		self.assertIsInstance(achievements,dict)
		for ak in achievements:
			self.assertAchievement(achievements[ak])
		self.assertEqual(len(achievements),25)

	def test_03 (self):
		"""
		test read_achievements with a path to the default achievements file
		from PCM1718 but with only the first 4 achievements and they have blank
		lines between them
		"""
		path = os.path.join(os.getcwd(),"course_tests","metadata","achievements_alt.txt")
		achievements = cdata.read_achievements(path)
		self.assertIsInstance(achievements,dict)
		for ak in achievements:
			self.assertAchievement(achievements[ak])
		self.assertEqual(len(achievements),4)
