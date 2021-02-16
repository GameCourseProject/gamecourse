#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import course
from course import Student, StudentData
from .test_student import TestStudent

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestStudentData(TestStudent):

	def assertStudentData(self,sd):
		self.assertIsInstance(sd,StudentData)
		self.assertIn("add_student",dir(sd))
		self.assertIn("add_students",dir(sd))
		self.assertIn("get_student_by_id",dir(sd))
		self.assertIn("get_students_by_name",dir(sd))
		self.assertIn("students",dir(sd))
		self.assertIsInstance(sd.students(),list)
		self.assertEqual(len(sd),len(sd.students()))
		tostring = "["
		representation = "StudentData(["
		for s in sd:
			self.assertStudent(s)
			self.assertIs(s,sd[s.num])
			self.assertIs(s,sd.get_student_by_id(s.num))
			self.assertIn(s,sd.get_students_by_name(s.name))
			tostring += str(s) + ", "
			representation += repr(s) + ", "
		if len(sd.students()):
			tostring = tostring[:-2]
			representation = representation[:-2]
		tostring += "]"
		representation += "])"
		self.assertEqual(str(sd),tostring)
		self.assertEqual(repr(sd),representation)


	def assertCreation(self,students):
		sd = StudentData(students)
		self.assertStudentData(sd)
		self.assertEqual(len(sd.students()),len(students))
		for s in students:
			self.assertEqual(sd[s.num],s)
	
	def assertCreationRaises(self,error,students):
		with self.assertRaises(error):
			StudentData(students)

	def assertEQ(self,sd1,sd2):
		self.assertStudentData(sd1)
		self.assertEqual(sd1,sd2)
		self.assertTrue(sd1 == sd2)
		self.assertFalse(sd1 != sd2)

	def assertNE(self,sd1,sd2):
		self.assertStudentData(sd1)
		self.assertNotEqual(sd1,sd2)
		self.assertTrue(sd1 != sd2)
		self.assertFalse(sd1 == sd2)

	def assertAdd(self,students):
		sd = StudentData()
		sd.add_students(students)
		self.assertEQ(sd,StudentData(students))

	def assertAddRaises(self,error,students):
		self.assertRaises(error,StudentData().add_students,students)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation tests (__init__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestStudentData):
	
	@classmethod
	def setUpClass(cls):
		cls.students = []
		for num in range(1,4):
			name = "student_%d" % num
			email = "%s@email.com" % name
			campus = "A" if num % 2 == 0 else "T"
			cls.students.append(Student(num,name,email,campus))

	def test_01 (self):
		""" test StudentData creation with valid inputs """
		self.assertCreation(self.students)

	def test_02 (self):
		""" test StudentData creation with valid inputs """
		self.assertCreation([])

	def test_03 (self):
		""" test StudentData creation with an invalid student """
		students = list(self.students) + ["oh no! it's a trap"]
		self.assertCreationRaises(Exception,students)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Comparison tests (__eq__, __ne__, __lt__, __le__, __gt__, __ge__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCompare(TestStudentData):
	
	@classmethod
	def setUpClass(cls):
		cls.students1 = []
		cls.students2 = []
		cls.students3 = []
		for num in range(1,4):
			name = "student_%d" % num
			email = "%s@email.com" % name
			campus = "A" if num % 2 == 0 else "T"
			cls.students1.append(Student(num,name,email,campus))
			num2 = num * num
			cls.students2.append(Student(num2,name,email,campus))
			name = "__student_%d" % num
			email = "__%s@email.com" % name
			campus = "A" if num % 2 == 1 else "T"
			cls.students3.append(Student(num,name,email,campus))
	
	def test_01 (self):
		""" test two StudentData objects created with the same students """
		self.assertEQ(StudentData(self.students1),StudentData(self.students1))
	
	def test_02 (self):
		""" test two StudentData objects created with the diff students """
		self.assertNE(StudentData(self.students1),StudentData(self.students2))
	
	def test_03 (self):
		""" test two StudentData objects created with the eq students """
		self.assertEQ(StudentData(self.students1),StudentData(self.students3))
	
	def test_04 (self):
		""" test two StudentData objects created with the same students
		but one has one more student than the other
		"""
		s = self.students1 + [self.students1[0]]
		self.assertNE(StudentData(self.students1),StudentData(s))
	
	def test_05 (self):
		""" test two StudentData objects created with the same students
		except for the last one that is a different student
		"""
		s = self.students1[:-1] + [Student(432,"ss","ss@ss","F")]
		self.assertNE(StudentData(self.students1),StudentData(s))
	
	def test_06 (self):
		""" test two StudentData objects created with the same students except
		for the last one that is not a student but also has a num and name
		"""
		class S:
			def __init__(self,num,name):
				self.num = num
				self.name = name
		s = self.students1[:-1]
		s.append(S(self.students1[-1].num,self.students1[-1].name))
		self.assertEQ(StudentData(self.students1),StudentData(s))
	
	def test_07 (self):
		""" test a StudentData obj agains an object that is not a StudentData """
		self.assertNE(StudentData([]),None)
		self.assertNE(StudentData([]),1)
		self.assertNE(StudentData([]),"student2")
		self.assertNE(StudentData([]),Exception("kaboom"))
		self.assertNE(StudentData([]),self.students1)

	def test_08 (self):
		""" test two StudentData objects created from the same students, but one
		of the objects receives the students from the 'init' while other receives
		them through 'add_students' """
		self.assertAdd(self.students1)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Add (add_student, add_students)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAdd(TestStudentData):
	
	@classmethod
	def setUpClass(cls):
		cls.students1 = []
		cls.students2 = []
		cls.students3 = []
		for num in range(1,4):
			name = "student_%d" % num
			email = "%s@email.com" % name
			campus = "A" if num % 2 == 0 else "T"
			cls.students1.append(Student(num,name,email,campus))
			num2 = num * num
			cls.students2.append(Student(num2,name,email,campus))
			name = "__student_%d" % num
			email = "__%s@email.com" % name
			campus = "A" if num % 2 == 1 else "T"
			cls.students3.append(Student(num,name,email,campus))

	def test_01 (self):
		""" test add_students with an invalid student """
		s = self.students1 + ["invalid"]
		self.assertAddRaises(Exception,s)
