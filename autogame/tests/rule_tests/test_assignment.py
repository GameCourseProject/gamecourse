#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Assignment, Statement, RuleError

import unittest


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Abstract Class to define common functions that aid the testing of class
# Assignment
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAssignment(unittest.TestCase):
	
	def assertAssignment(self, assignment, text=None, filepath=None, line=None):
		self.assertIsInstance(assignment,Assignment)
		self.assertIsInstance(assignment,Statement)
		self.assertEqual(type(assignment.code()).__name__,'code')
		if text != None:
			self.assertEqual(assignment.text(),text)
		if filepath != None:
			self.assertEqual(assignment.path(),filepath)
		if line != None:
			self.assertEqual(assignment.line(),line)

	def assertCreation(self, text, filepath=None, line=None):
		if filepath == None and line == None:
			assignment = Assignment(text)
		elif filepath == None:
			assignment = Assignment(text)
		elif line == None:
			assignment = Assignment(text)
		else:
			assignment = Assignment(text,filepath,line)
		self.assertAssignment(assignment,text,filepath,line)

	def assertCreationError(self, error_msg, text, filepath=None, line=None, offset=None):
		if filepath == None:
			filepath = "C:\\assert\\creation\\error.txt"
		if line == None:
			line = 404
		with self.assertRaises(RuleError) as cm:
				Assignment(text,filepath,line)
		msg = "in line %d of file '%s':\n" % (line,filepath)
		msg+= "\t%s\n" % text
		if offset != None:
			blank = ' ' * (offset - 1)
			msg+= "\t%s^\n" % blank
		msg+= error_msg
		self.assertEqual(str(cm.exception), msg)

	def assertFire(self, text, scope=None):
		assignment = Assignment(text)
		self.assertAssignment(assignment,text,'',1)
		if scope == None:
			result = assignment.fire()
		else:
			result = assignment.fire(scope)
			expected_scope = dict(scope)
			exec(text,expected_scope)
			self.assertIsNot(scope,expected_scope)
			self.assertEqual(scope,expected_scope)
		self.assertIsInstance(result,bool)
		self.assertTrue(result == True)

	def assertFireError(self, text, error_msg):
		filepath = "C:\\examples\\rules\\rule303.txt"
		line = 123
		assignment = Assignment(text,filepath,line)
		with self.assertRaises(RuleError) as cm:
			assignment.fire()
		msg = "in line %d of file '%s':\n" % (line,filepath)
		msg+= "\t%s\n" % text
		#msg+= error_msg
		self.assertEqual(str(cm.exception), msg)
		

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Assignment(self, text, filepath, line) - Creates a new Assignment Node
# Assignment.validate(self) - Validates the input text and compiles it
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Creation(TestAssignment):
	
	def test_00 (self):
		self.assertCreation("what = callable(1,2,3) + (lambda x: x**x)(2)")

	def test_01 (self):
		self.assertCreation("y = 33 + 4", "my_file.txt", 43)

	def test_02 (self):
		f = "C:\\rules\\pcm2018.txt"
		l = 308321
		s = "a = 09"
		self.assertCreationError("SyntaxError: leading zeros in decimal integer literals are not permitted; use an 0o prefix for octal integers",s,f,l,6)

	def test_03 (self):
		msg = "SyntaxError: missing assignment operator"
		self.assertCreationError(msg,"a + 4")

	def test_04 (self):
		msg = "SyntaxError: invalid syntax"
		f = "C:\\rules\\pcm2018.txt"
		self.assertCreationError(msg,"+-=2",f,offset=2)

	def test_05 (self):
		msg = "SyntaxError: missing assignment operator"
		l = 23
		self.assertCreationError(msg,"a!=2",line=l)

	def test_06 (self):
		msg = "SyntaxError: missing assignment operator"
		self.assertCreationError(msg,"a>=2")

	def test_07 (self):
		msg = "SyntaxError: missing assignment operator"
		self.assertCreationError(msg,"a<=2")

	def test_08 (self):
		msg = "SyntaxError: missing assignment operator"
		self.assertCreationError(msg,"a==2")

	def test_09 (self):
		self.assertCreation("y += 33 + 4")

	def test_10 (self):
		self.assertCreation("y -= 66")

	def test_11 (self):
		self.assertCreation("y /= 2")

	def test_12 (self):
		msg = "SyntaxError: missing assignment operator"
		self.assertCreationError(msg,"a<=2","my_file.txt",431)

	def test_13 (self):
		self.assertCreation("i=1")

	def test_14 (self):
		""" python statement that != an assignment """
		msg = "SyntaxError: missing assignment operator"
		self.assertCreationError(msg,"pass")

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Assignment.fire(self, scope) -> Executes the Assignment code
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Fire (TestAssignment):
	

	def test_01 (self):
		t = "x = 9+8-1"
		self.assertFire(t)

	def test_02 (self):
		def double (l):
			return map(lambda x: 2*x, l)
		t = "d = double([1,2,3])"
		scope = {}
		scope['double'] = locals()['double']
		self.assertFire(t, scope)

	def test_03 (self):
		text = "d = double([1,2,3])"
		error_msg = "NameError: name 'double' != defined"
		self.assertFireError(text, error_msg)