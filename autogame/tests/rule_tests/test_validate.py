#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import validate
from context import RuleError

import unittest

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Abstract Class to define functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestValidate (unittest.TestCase):
	
	def assertIsAssignmentTrue(self,stmt):
		fpath = "C:\\test\\validate\\isassignment\\true\\stmt.txt"
		line = 412
		self.assertTrue(validate.isassignment(stmt,line,fpath))
	
	def assertIsAssignmentFalse(self,stmt):
		fpath = "C:\\test\\validate\\isassignment\\true\\stmt.txt"
		line = 412
		self.assertFalse(validate.isassignment(stmt,line,fpath))

	def assertIsAssignmentRaises(self,stmt,error_msg,offset):
		fpath = "C:\\test\\validate\\isassignment\\raises\\stmt.txt"
		line = 12
		with self.assertRaises(RuleError) as cm:
			validate.isassignment(stmt,fpath,line)
		msg = "in line %d of file '%s':\n" % (line, fpath)
		msg+= "\t%s\n" % stmt
		blank = ' ' * (offset-1)
		msg+= "\t%s^\n" % blank
		msg+= "SyntaxError: invalid assignment: %s" % error_msg
		ex = cm.exception
		self.assertEqual(str(ex),msg)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# validate.isassignment(stmt_txt, filepath, line)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestIsAssignment(TestValidate):

	def test_01 (self):
		self.assertIsAssignmentTrue("a = 1")

	def test_02 (self):
		self.assertIsAssignmentRaises("= 1", "missing left value", 1)

	def test_03 (self):
		stmt = "v ="
		msg = "missing right value"
		offset = len(stmt)+1
		self.assertIsAssignmentRaises(stmt,msg,offset)
	
	def test_04 (self):
		self.assertIsAssignmentTrue("a+= 1")
	
	def test_05 (self):
		self.assertIsAssignmentFalse("a + 1")
	
	def test_06 (self):
		self.assertIsAssignmentFalse("a >= 1")
	
	def test_07 (self):
		self.assertIsAssignmentFalse("a == 1")
	
	def test_08 (self):
		self.assertIsAssignmentFalse("myfunc(arg=1)")