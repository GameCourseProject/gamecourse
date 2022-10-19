#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import course
from course import Award
from base_test_class import BaseTestClass

import time

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAward(BaseTestClass):

	def assertCreation(self,student,achievement,lvl,xp,badge,ts=None,info=None):
		t1 = time.time()
		a = Award(student,achievement,lvl,xp,badge,ts,info)
		self.assertAward(a)
		self.assertEqual(a.student,student)
		self.assertEqual(a.achievement,achievement)
		self.assertEqual(a.level,lvl)
		self.assertEqual(a.xp,xp)
		self.assertEqual(a.badge,badge)
		if ts is not None:
			self.assertEqual(a.timestamp,ts)
		else:
			self.assertGreaterEqual(a.timestamp,t1)
			self.assertLessEqual(a.timestamp,time.time())
		self.assertEqual(a.info,info)

	def assertEQ(self,a1,a2,val):
		self.assertAward(a1)
		self.assertEqual(a1,a2)
		self.assertTrue(a1 == a2)
		self.assertFalse(a1 != a2)
		self.assertEqual(a1 == a2, val)

	def assertNE(self,a1,a2):
		self.assertAward(a1)
		self.assertNotEqual(a1,a2)
		self.assertTrue(a1 != a2)
		self.assertFalse(a1 == a2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation tests (__init__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestAward):

	def test_01 (self):
		""" test valid award creation """
		self.assertCreation("s1","a2",2,234,True,time.time(),"aot")

	def test_02 (self):
		""" test valid award creation """
		self.assertCreation("33","r2d2",9995262623,-50,False)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Comparison tests (__eq__, __ne__, __lt__, __le__, __gt__, __ge__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCompare(TestAward):

	def test_01 (self):
		""" test comparison between two equal awards
		(all attributes are the same)
		"""
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		self.assertEQ(a1,a2,2)

	def test_02 (self):
		""" test comparison between two equal awards
		(different time)
		"""
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = Award(student,achievement,lvl,xp,hasbadge,time.time(),info)
		self.assertEQ(a1,a2,2)

	def test_03 (self):
		""" test comparison between two equal awards
		(different info)
		"""
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = Award(student,achievement,lvl,xp,hasbadge,timestamp,"did nothing")
		self.assertEQ(a1,a2,2)

	def test_04 (self):
		""" test comparison between two diff awards
		(different badge value)
		"""
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = Award(student,achievement,lvl,xp,False,timestamp,info)
		self.assertNE(a1,a2)

	def test_05 (self):
		""" test comparison between two diff awards
		(different info, same badge value (False))
		"""
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,False,timestamp,info)
		a2 = Award(student,achievement,lvl,xp,False,timestamp,"save McDonalds")
		self.assertNE(a1,a2)

	def test_06 (self):
		""" test comparison between two equal awards
		(different xp)
		"""
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = Award(student,achievement,lvl,9500,hasbadge,timestamp,info)
		self.assertEQ(a1,a2,1)

	def test_07 (self):
		""" test comparison between an award and another object with the same
		attributes and values
		"""
		class Award2:
			def __init__(self,s,a,l,x,b,t,i):
				self.student = s; self.achievement = a; self.level = l;
				self.xp = x; self.badge = b; self.timestamp = t; self.info = i
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = Award2(student,achievement,lvl,xp,hasbadge,timestamp,info)
		self.assertEQ(a1,a2,2)

	def test_08 (self):
		""" test comparison between an award and another object with different
		attributes
		"""
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = True; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = "Award(student,achievement,lvl,xp,hasbadge,timestamp,info)"
		self.assertNE(a1,a2)

	def test_09 (self):
		""" test comparison between an award and another object with same
		attributes but with different values
		"""
		class Award2:
			def __init__(self,s,a,l,x,b,t,i):
				self.student = s; self.achievement = a; self.level = l;
				self.xp = x; self.badge = b; self.timestamp = t; self.info = i
		student = 73844; achievement = "Hero of the World"; lvl = 2; xp = 10000
		hasbadge = False; timestamp = time.time(); info = "saved a city"
		a1 = Award(student,achievement,lvl,xp,hasbadge,timestamp,info)
		a2 = Award2(student,achievement,lvl,xp,hasbadge,timestamp,False)
		self.assertNE(a1,a2)
