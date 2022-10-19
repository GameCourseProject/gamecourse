#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import RuleError

import unittest

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# RuleError(self, filepath, line, message)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Creation(unittest.TestCase):

	def test_00 (self):
		f = "C:\\rules\\course2018.txt"
		l = 30
		m = "RuleError"
		ex = RuleError(f,l,m)
		assert_RuleError(self,ex,f,l,m)

	def test_01 (self):
		ex = RuleError()
		m = "RuleError"
		assert_RuleError(self,ex,'?','?', m)

	def test_02 (self):
		with self.assertRaises(TypeError) as cm:
			RuleError(filepath=123)

	def test_03 (self):
		with self.assertRaises(TypeError) as cm:
			RuleError(line={'a':453})

	def test_04 (self):
		with self.assertRaises(TypeError) as cm:
			RuleError(message=[RuleError(),self,unittest])

	def test_05 (self):
		m = "error message ..."
		ex = RuleError(message=Exception(m))
		m = "RuleError"
		assert_RuleError(self,ex,'?','?',m)

	def test_06 (self):
		f = "C:\\rules\\rule01.txt"
		l = 2
		stmt = "a +=+ error"
		try:
			eval(stmt)
		except SyntaxError as se:
			ex = RuleError(f,l,se)
			m = "RuleError"
			"""m = "\t{}\n".format(stmt)
			m+= "\t   ^\n"
			m+= "{}: {}".format(type(se).__name__,se.msg)"""
		assert_RuleError(self,ex,f,l,m)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Aux Functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def assert_RuleError(self,e,f,l,m):
	self.assertIsInstance(e,RuleError)
	self.assertIsInstance(e.filepath,str)
	self.assertIsInstance(e.line,(int,str))
	self.assertIsInstance(e.message,(str))
	self.assertEqual(e.filepath,f)
	self.assertEqual(e.line,l)
	self.assertEqual(type(e).__name__,m)
	"""msg = "in line {} of file '{}':\n{}".format(l,f,m)
	self.assertEqual(str(e),msg)
	msg = "RuleError<file:{}, line:{}>".format(f,l)
	self.assertEqual(repr(e),msg)"""
