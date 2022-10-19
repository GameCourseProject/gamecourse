#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import course
from course import Student

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestStudent(BaseTestClass):

	def assertCreation(self,num,name,email,campus):
		s = Student(num,name,email,campus)
		self.assertStudent(s)
		self.assertEqual(s.num,num)
		self.assertEqual(s.name,name)
		self.assertEqual(s.email,email)
		self.assertEqual(s.campus,campus)

	def assertCreationRaises(self,error,num,name,email,campus):
		with self.assertRaises(error):
			Student(num,name,email,campus)

	def assertEQ(self,s1,s2):
		self.assertStudent(s1)
		self.assertEqual(s1,s2)
		self.assertTrue(s1 == s2)
		self.assertFalse(s1 != s2)

	def assertNE(self,s1,s2):
		self.assertStudent(s1)
		self.assertNotEqual(s1,s2)
		self.assertTrue(s1 != s2)
		self.assertFalse(s1 == s2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation tests (__init__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestStudent):

	def test_01 (self):
		""" test valid student creation """
		self.assertCreation(73844,"Jolly John","myfake_email@fake.com","A")

	def test_02 (self):
		""" test valid student creation """
		self.assertCreation(1,"Anna Woods","none@yes.do.it","P")

	def test_03 (self):
		""" test valid student creation """
		self.assertCreation(32186110546,"SS","c@d.d","GG")

	def test_04 (self):
		""" test valid student creation """
		name = str("ãàáâä")
		self.assertCreation(2,name,"r2d2@star.wars.universe","_F_")

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Comparison tests (__eq__, __ne__, __lt__, __le__, __gt__, __ge__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCompare(TestStudent):

	def test_01 (self):
		s1 = Student(1,"Mr.Anderson","the.one@matrix","Humans")
		s2 = Student(1,"Neo","hackerman@real.world","Batteries")
		self.assertEQ(s1,s2)

	def test_02 (self):
		class A:
			def __init__(self,num):
				self.num = num
		a = A(1)
		s1 = Student(1,"Mr.Anderson","the.one@matrix","Humans")
		self.assertEQ(s1,a)

	def test_03 (self):
		s1 = Student(1,"Mr.Anderson","the.one@matrix","Humans")
		s2 = Student(2,"Mr.Anderson","the.one@matrix","Humans")
		self.assertNE(s1,s2)

	def test_04 (self):
		s1 = Student(1,"Mr.Anderson","the.one@matrix","Humans")
		self.assertNE(s1,1)
