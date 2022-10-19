#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import course
from course import Award, Prize
cfuncs = course.coursefunctions


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCourseFunctions(BaseTestClass):

	def gen_awards(self, num):
		awards = []
		for i in range(num):
			student = "s%d" % i
			achievement = "a%d" % i
			lvl = i
			xp = i*i
			badge = i % 2 == 0
			timestamp = i
			info = "info"
			awards.append(Award(student,achievement,lvl,xp,badge,timestamp,info))
		return awards

	def gen_indicators(self, num_students, num_achievements):
		indicators = {}
		for i in range(num_students):
			student = "s%d" % i
			indicators[student] = {}
			for j in range(num_achievements):
				achievement = "a%d" % j
				count = i+j
				logs = 'x' * count
				if count == 0:
					count = False
					logs = None
				indicators[student][achievement] = count, logs
		return indicators

	def gen_prizes(self, num):
		prizes = []
		for i in range(num):
			student = "s%d" % i
			achievement = "a%d" % i
			lvl = i
			xp = i * 50
			badge = i % 2 == 0
			timestamp = 1001
			info = "gen_prizes(%d)" % num
			award = Award(student,achievement,lvl,xp,badge,timestamp,info)
			a = {student: [award]}
			x = i % 4
			indicators = None if x == 0 else ["c%d" % j for j in range(x)]
			indicators = (False, indicators) if x == 0 else (x, indicators)
			i = {student: {achievement: indicators}}
			prizes.append(Prize(a,i))
		return prizes

	def gen_logs(self, num, forum, thread):
		class Log:
			def __init__(self,xp,info):
				self.xp = xp
				self.info = info
			def __eq__(self,other):
				return isinstance(other,Log) \
				and self.xp == other.xp \
				and self.info == other.info
			def __ne__(self,other):
				return not self == other
		return [Log(0,(forum,thread)) for i in range(num)]


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# transform_rulesystem_output(output): awards and indicators
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestTransformRulesystemOutput(TestCourseFunctions):


	def test_01 (self):
		""" test transform_rulesystem_output: with an empty output """
		output = {}
		expected = [], {}
		result = cfuncs.transform_rulesystem_output(output)
		self.assertEqual(result,expected)

	def test_02 (self):
		""" test transform_rulesystem_output: with effects that aren't prizes """
		output = {"s1": {"R1": [1,"something empty"], "R3": [None, None, None]}}
		expected = [], {}
		result = cfuncs.transform_rulesystem_output(output)
		self.assertEqual(result,expected)

	def test_03 (self):
		""" test transform_rulesystem_output: with prize effects """
		p = Prize()
		prizes = self.gen_prizes(5)
		p.join_multiple(prizes)
		output = {"s1": {"R1": prizes}}
		awards = [];
		for k in p.awards:	awards += p.awards[k]
		expected = awards, p.indicators
		result = cfuncs.transform_rulesystem_output(output)
		self.assertEqual(result,expected)

	def test_04 (self):
		""" test transform_rulesystem_output: with prize effects and non prize
		effects
		"""
		p = Prize()
		prizes = self.gen_prizes(5)
		p.join_multiple(prizes)
		output = {"s1": {"R1": prizes + ["SURPRISE"], "R2": ["SURPRISE"]}}
		awards = [];
		for k in p.awards:	awards += p.awards[k]
		expected = awards, p.indicators
		result = cfuncs.transform_rulesystem_output(output)
		self.assertEqual(result,expected)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# substringin(string, list_strings): TRUE / FALSE
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestSubStringIn(TestCourseFunctions):

	def test_01 (self):
		""" test subtringin in with a match in the first element of the list """
		string = "reMIDI"
		list_strings = ["reMIDI","eBook","Course Logo","Zombies and Monsters"]
		self.assertTrue(cfuncs.substringin(string,list_strings))

	def test_02 (self):
		""" test subtringin in with a match in the last element of the list """
		string = "Zombies and Monsters"
		list_strings = ["reMIDI","eBook","Course Logo","Zombies and Monsters"]
		self.assertTrue(cfuncs.substringin(string,list_strings))

	def test_03 (self):
		""" test subtringin in with a match in some middle element of the list """
		string = "Course Logo"
		list_strings = ["reMIDI","eBook","Course Logo","Zombies and Monsters"]
		self.assertTrue(cfuncs.substringin(string,list_strings))

	def test_04 (self):
		""" test subtringin in with the string just being part of the match """
		string = "ourse"
		list_strings = ["reMIDI","eBook","Course Logo","Zombies and Monsters"]
		self.assertTrue(cfuncs.substringin(string,list_strings))

	def test_05 (self):
		""" test subtringin in with the string not in the list """
		string = "Zombies and Monsters 22"
		list_strings = ["reMIDI","eBook","Course Logo","Zombies and Monsters"]
		self.assertFalse(cfuncs.substringin(string,list_strings))

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# satisfied_skill(skill, logs): TRUE || FALSE
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestSatisfiedSkill(TestCourseFunctions):

	def test_01 (self):
		""" test satisfied_skill with inexistent skill name """
		self.assertRaises(KeyError,cfuncs.satisfied_skill,"error",[])

	def test_02 (self):
		""" test satisfied_skill with invalid logs """
		self.assertRaises(AttributeError,cfuncs.satisfied_skill,"reTrailer",[1])

	def test_03 (self):
		""" test satisfied_skill where the skill is not in the logs """
		logs = self.gen_logs(3,"skill tree","Reporter")
		self.assertFalse(cfuncs.satisfied_skill("Series Intro",logs))
		# Series Intro was not completed by any student in 19/20
		# neither was Fake Speech

	def test_04 (self):
		"""
		test satisfied_skill where the skill is in the logs but the
		preconditions are not satisfied
		"""
		logs = self.gen_logs(3,"skill tree","reTrailer")
		self.assertFalse(cfuncs.satisfied_skill("reTrailer",logs))

	def test_05 (self):
		"""
		test satisfied_skill where the skill is in the logs and the
		preconditions are satisfied
		"""
		# this test is hardcoded to each year
		"""
		logs = self.gen_logs(1,"skill tree","reTrailer")
		logs += self.gen_logs(1,"skill_tree","Publicist")
		logs += self.gen_logs(1,"skill_tree","Looping GIF")
		logs += self.gen_logs(1,"skill_tree","Reporter")
		logs += self.gen_logs(1,"skill_tree","Movie Poster")
		logs += self.gen_logs(1,"skill_tree","Course Logo")
		"""

		logs = self.gen_logs(1,"skill tree","reTrailer")

		logs += self.gen_logs(1,"skill_tree","Publicist")
		logs += self.gen_logs(1,"skill_tree","Course Image")

		logs += self.gen_logs(1,"skill_tree","Course Logo")
		logs += self.gen_logs(1,"skill_tree","Reporter")
		logs += self.gen_logs(1,"skill_tree","Album Cover")
		logs += self.gen_logs(1,"skill_tree","Movie Poster")

		self.assertTrue(cfuncs.satisfied_skill("reTrailer",logs))

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# compute_lvl(value, *lvls): integer (>= 0) (corresponding to the lvl)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestComputeLvl (TestCourseFunctions):

	def test_01 (self):
		""" test compute_lvl: with no lvl preconditions """
		self.assertEqual(cfuncs.compute_lvl(43),0)
		self.assertEqual(cfuncs.compute_lvl(-1),0)
		self.assertEqual(cfuncs.compute_lvl(0),0)

	def test_02 (self):
		""" test compute_lvl: with 1 lvl precondition """
		self.assertEqual(cfuncs.compute_lvl(5, 1),1)
		self.assertEqual(cfuncs.compute_lvl(5, 6),0)
		self.assertEqual(cfuncs.compute_lvl(5, 5),1)
		self.assertEqual(cfuncs.compute_lvl(5, [1]),1)
		self.assertEqual(cfuncs.compute_lvl(5, [6]),0)
		self.assertEqual(cfuncs.compute_lvl(5, [5]),1)

	def test_03 (self):
		""" test compute_lvl: with multiple lvl preconditions """
		self.assertEqual(cfuncs.compute_lvl(5, 2,4,6),2)
		self.assertEqual(cfuncs.compute_lvl(5, 1,5),2)
		self.assertEqual(cfuncs.compute_lvl(5, 10,10,10),0)
		self.assertEqual(cfuncs.compute_lvl(5, 10,5,1),3)
		self.assertEqual(cfuncs.compute_lvl(5, [2,4,6]),2)
		self.assertEqual(cfuncs.compute_lvl(5, (10,5,1)),3)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# find_student(name, students_list): student
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFindStudent (TestCourseFunctions):

	def get_alphabet (self):
		return [str(chr(i)) for i in range(ord("a"),ord("z")+1)]

	def gen_names(self, number):
		lst = []
		alphabet = self.get_alphabet()
		for i in range(number):
			name = alphabet[i % len(alphabet)]
			lst.append(name)
		return lst

	def gen_student (self,name):
		class Student:
			def __init__(self,name):
				self.name = name
			def __eq__ (self,other):
				try:
					return self.name == other.name
				except:
					return False
			def __ne__ (self,other):
				return not self == other
		return Student(name)

	def gen_students (self, lst_names):
		d = {}
		num = 0
		for name in lst_names:
			d[num] = self.gen_student(name)
			num+= 1
		return d

	def test_01 (self):
		""" test find_student: with empty students """
		students = {}
		name = str("a")
		result = cfuncs.find_student(name,students)
		self.assertFalse(result)

	def test_02 (self):
		""" test find_student: with name in students """
		students = self.gen_students(self.gen_names(3))
		name = str("a")
		result = cfuncs.find_student(name,students)
		self.assertEqual(result,self.gen_student(name))

	def test_03 (self):
		""" test find_student: with string name in students """
		students = self.gen_students(self.gen_names(3))
		name = "a"
		result = cfuncs.find_student(name,students)
		self.assertEqual(result,self.gen_student(name))
