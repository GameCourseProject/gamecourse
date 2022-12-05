#!/usr/bin/env python
# -*- coding: utf-8 -*-


from base_test_class import BaseTestClass
from context import course
from course import Achievement

import unittest

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAchievement (BaseTestClass):

	def assertCreation(self,n,d,c,x,is_counted,is_postbased):
		c1,c2,c3 = c.split(";")
		x1,x2,x3 = x.split(";")
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertAchievement(a)
		self.assertEqual(a.name,n)
		self.assertEqual(a.description,d)
		criteria = c1,c2,c3
		xp = x1,x2,x3
		converted = []
		for i in range(3):
			if xp[i] == "":
				self.assertEqual(a.xp[i],0)
			else:
				_xp_ = int(xp[i])
				converted.append(_xp_)
				self.assertEqual(a.xp[i],abs(_xp_))
				self.assertGreaterEqual(a.top_level(),i+1)
				if _xp_ < 0:
					self.assertTrue(a.has_extra())
					self.assertTrue(a.extra[i])
				else:
					self.assertFalse(a.extra[i])
			self.assertEqual(a.criteria[i],criteria[i])
		if len(list(filter(lambda a: a<0,converted))) > 0:
			self.assertTrue(a.has_extra())
		self.assertEqual(a.is_counted(),is_counted)
		self.assertEqual(a.is_postbased(),is_postbased)

	def assertEQ(self,a1,a2):
		self.assertAchievement(a1)
		self.assertEqual(a1,a2)
		self.assertTrue(a1 == a2)
		self.assertFalse(a1 != a2)

	def assertNE(self,a1,a2):
		self.assertAchievement(a1)
		self.assertNotEqual(a1,a2)
		self.assertTrue(a1 != a2)
		self.assertFalse(a1 == a2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation tests (__init__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestAchievement):
	
	def test_01 (self):
		""" test valid student creation """
		self.assertCreation("name","description","c1;c2;c2","1;3;9",False,False)
	
	def test_02 (self):
		""" test valid student creation """
		self.assertCreation("4232","12312","33;22;11","13;23;39",True,True)
	
	def test_03 (self):
		""" test valid student creation """
		self.assertCreation("ºãàáäâç",".:,_-;","#;!;?","999;312;0",True,False)
	
	def test_04 (self):
		""" test valid student creation """
		self.assertCreation("(&%#$\"\'\\","ìóüê","12;asd;?§€","0;0;0",False,False)
	
	def test_05 (self):
		""" test valid student creation """
		self.assertCreation("}{wqe","&%2",";;",";;",False,False)
	
	def test_06 (self):
		""" test valid student creation """
		self.assertCreation("j%&","'self'","bº;;","21;;",True,False)
	
	def test_07 (self):
		""" test valid student creation """
		self.assertCreation("\t\r 	","    ","`;´;","3;2;",False,True)
	
	def test_08 (self):
		""" test valid student creation """
		self.assertCreation("jo\t12"," _ ","1;;","-1;;",True,True)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# representation tests (__repr__, __str__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestRepresentation(TestAchievement):

	def test_01 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = 10, 100, 10000
		is_counted = False; is_postbased = False
		expected = "Achievement('Hero', [1: 10, 2: 100, 3: 10000])"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

	def test_02 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = None, 123, 23
		is_counted = False; is_postbased = False
		expected = "Achievement('Hero')"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

	def test_03 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = 231, None, None
		is_counted = False; is_postbased = False
		expected = "Achievement('Hero', [1: 231])"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

	def test_04 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = 231, None, 75
		is_counted = False; is_postbased = False
		expected = "Achievement('Hero', [1: 231, 3: 75])"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

	def test_05 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = 231, 90, None
		is_counted = False; is_postbased = False
		expected = "Achievement('Hero', [1: 231, 2: 90])"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

	def test_06 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = 0xf, 7, 0b111
		is_counted = False; is_postbased = False
		expected = "Achievement('Hero', [1: 15, 2: 7, 3: 7])"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

	def test_07 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = None,None,None
		is_counted = True; is_postbased = False
		expected = "Achievement('Hero', COUNTED)"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

	def test_08 (self):
		""" check if the representation matches the specification """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = None,None,None
		is_counted = False; is_postbased = True
		expected = "Achievement('Hero', POSTBASED)"
		a = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEqual(repr(a),expected)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Comparison tests (__eq__, __ne__, __lt__, __le__, __gt__, __ge__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCompare(TestAchievement):
	
	def test_01 (self):
		""" test comparison between two equal achievements """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = None,None,None
		is_counted = False; is_postbased = True
		a1 = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		a2 = Achievement(n,None,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertEQ(a1,a2)
	
	def test_02 (self):
		""" test comparison between two unequal achievements """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = None,None,None
		is_counted = False; is_postbased = True
		a1 = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		a2 = Achievement(n+"2",d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertNE(a1,a2)
	
	def test_03 (self):
		""" test comparison between an achievement equal to other obj """
		class A:
			def __init__(self,n,c,x):
				self.name = n; self.criteria = c; self.xp = x
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = 0,0,0
		is_counted = False; is_postbased = True
		a1 = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		a2 = A(n,(c1,c2,c3),(x1,x2,x3))
		self.assertEQ(a1,a2)
	
	def test_04 (self):
		""" test comparison between an achievement equal to other obj """
		class A:
			def __init__(self,n,c,x):
				self.name = n; self.criteria = c; self.xp = x
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = "dasd",None,[222,22]
		is_counted = False; is_postbased = True
		a1 = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		a2 = A(n,(c1,c2,c3),(x1,x2,x3))
		self.assertNE(a1,a2)
	
	def test_05 (self):
		""" test comparison between an achievement equal to other obj """
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = "dasd",None,[222,22]
		is_counted = False; is_postbased = True
		a1 = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		self.assertNE(a1,"achievement")
	
	def test_06 (self):
		""" test comparison between two unequal achievements """
		class A:
			def __init__(self,n,c,x):
				self.name = n; self.criteria = c; self.xp = x
		n = "Hero"; d = "Be a hero!"
		c1,c2,c3 = "Save a person", "Save 10 persons", "Save a city"
		x1,x2,x3 = 0,0,0
		is_counted = False; is_postbased = True
		a1 = Achievement(n,d,c1,c2,c3,x1,x2,x3,is_counted,is_postbased)
		a2 = A(n,(c1,c2,c3),(x1,x2,x3,x1))
		self.assertNE(a1,a2)

# 	def test_03 (self):
# 		s1 = Student(1,"Mr.Anderson","the.one@matrix","Humans")
# 		s2 = Student(2,"Mr.Anderson","the.one@matrix","Humans")
# 		self.assertNE(s1,s2)

# 	def test_04 (self):
# 		s1 = Student(1,"Mr.Anderson","the.one@matrix","Humans")
# 		self.assertNE(s1,1)