#!/usr/bin/env python
# -*- coding: utf-8 -*-

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Student Main Class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Student:
	def __init__(self,num,name,email,campus):
		# self.__validate__(num,name,email,campus)
		self.num = num
		self.name = name
		self.email = email
		self.campus = campus
	def __eq__ (self,other):
		try:
			return self.num == other.num
		except AttributeError:
			return False
	def __ne__(self,other):
		return not self == other
	def __hash__(self):
		return hash(self.num)
	def __repr__(self):
		name, email, campus = self.name, self.email, self.campus
		# encoding fix
		#if isinstance(name,str): name = name.encode("latin1")
		#if isinstance(email,str): email = self.email.encode("latin1")
		#if isinstance(campus,str): campus = self.campus.encode("latin1")
		args = (self.num, name, email, campus)
		return "Student(%s,%s,%s,%s)" % args
	def __str__ (self):
		name, email, campus = self.name, self.email, self.campus
		# encoding fix
		#if isinstance(name,str): name = name.encode("latin1")
		#if isinstance(email,str): email = self.email.encode("latin1")
		#if isinstance(campus,str): campus = self.campus.encode("latin1")
		return "%s,%s,%s,%s" % (self.num, name, email, campus)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Class to hold data on the students
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class StudentData:
	def __init__(self,students=None):
		if students is None:
			students = []
		self.__ids__ = {}
		self.__names__ = {}
		for student in students:
			try:
				self.__ids__[student.num] = student
				self.__names__[student.name] = self.__names__.get(student.name,[]) + [student]
			except AttributeError:
				raise Exception("Invalid student: %s" % student)
		self.__students__ = students

	def __getitem__ (self,num):
		return self.__ids__[num]

	def __len__ (self):
		return len(self.students())

	def __iter__(self):
		return iter(self.students())

	def __eq__(self,other):
		try:
			return self.__students__ == other.__students__
		except AttributeError:
			return False
	
	def __ne__(self,other):
		return not self == other
	
	def __repr__(self):
		m = "StudentData(["
		for s in self.students():
			m+= repr(s) + ", "
		if len(self.students()):
			m = m[:-2] # remove last comma
		return m + "])"

	def __str__(self):
		m = "["
		for s in self.students():
			m+= str(s) + ", "
		if len(self.students()):
			m = m[:-2] # remove last comma
		return m + "]"

	def add_student(self,student):
		try:
			self.__students__.append(student)
			self.__ids__[student.num] = student
			self.__names__[student.name] = self.__names__.get(student.name,[]) + [student]
		except AttributeError:
			raise Exception("Invalid student: %s" % student)

	def add_students(self,students):
		for s in students:
			self.add_student(s)

	def get_student_by_id(self,num):
		return self[num]

	def get_students_by_name(self,name):
		return self.__names__[name]

	def students(self):
		return self.__students__