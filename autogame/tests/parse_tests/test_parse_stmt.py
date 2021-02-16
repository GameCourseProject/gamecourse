#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import parser
from context import Expression
from context import Assignment

from base_test_class import BaseTestClass

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Statement Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseStatement (BaseTestClass):

	def assertResult(self, result, expected, stype):
		""" assert the result of 'parse_statement()' function with the expected
		output given the expected statement type (stype)
		"""
		self.assertIsInstance(result, tuple)
		self.assertEqual(len(result),2)
		self.assertIsInstance(expected, tuple)
		self.assertEqual(len(expected),2)
		self.assertEqual(result, expected)
		if stype == 0:
			self.assertIsNone(result[0])
			self.assertIsNone(expected[0])
		else:
			if stype == 1:
				self.assertIsInstance(result[0],Expression)
				self.assertIsInstance(expected[0],Expression)
			elif stype == 2:
				self.assertIsInstance(result[0],Assignment)
				self.assertIsInstance(expected[0],Assignment)
			else:
				msg = "invalid value for statement type: 'stype' = %d" % stype
				raise ValueError(msg)
			self.assertEqual(result[0].path(),expected[0].path())
			self.assertEqual(result[0].line(),expected[0].line())

	def assertEmpty(self, result, expected):
		self.assertResult(result, expected, 0)

	def assertExpression(self, result, expected):
		self.assertResult(result, expected, 1)

	def assertAssignment(self, result, expected):
		self.assertResult(result, expected, 2)

	def assertAssignmentRaises(self, text):
		from context import RuleError
		f = "C:\\test\\parse_stmt\\assert\\assignment\\raises\\text.txt"
		l = 404
		with self.assertRaises(RuleError):
			parser.parse_statement(text,0,f,l)

	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	def test_01 (self):
		result = parser.parse_statement()
		expected = (None,0)
		self.assertEmpty(result,expected)

	def test_02 (self):
		text = "1"
		stype = 1
		pos = 0
		fpath = "C:\\rules\\file.txt"
		line = 423
		result = parser.parse_statement(text,pos,fpath,line)
		pos_end = len(text)
		stmt = Expression(text,fpath,line)
		expected = (stmt,pos_end)
		self.assertExpression(result,expected)

	def test_03 (self):
		# RESULT
		text = "i + 40.5 and True"
		pos = 0
		fpath = "C:\\rules\\file.txt"
		line = 23
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		pos_end = len(text)
		stmt = Expression(text,fpath,line)
		expected = (stmt,pos_end)

		self.assertExpression(result, expected)


	def test_04 (self):
		# RESULT
		text = "i=1"
		pos = 0
		fpath = "C:\\rules\\file.txt"
		line = 23
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		pos_end = len(text)
		stmt = Assignment(text,fpath,line)
		expected = (stmt,pos_end)
		# ASSERT
		self.assertAssignment(result,expected)

	def test_05(self):
		# RESULT
		s1 = "i=1"
		s2 = "i+3"
		text = s1 + "\n" + s2
		pos = len(s1+"\n")
		fpath = "C:\\rules\\file.txt"
		line = 5324
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		pos_end = len(text)
		stmt = Expression(s2,fpath,line)
		expected = (stmt,pos_end)
		# ASSERT
		self.assertExpression(result, expected)

	def test_06 (self):
		# RESULT
		text = "i\t\v   \r\f=1\f  "
		pos = 0
		fpath = "fall of the hobbits"
		line = 34
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		parsed = "i =1"
		pos_end = len(text)
		stmt = Assignment(parsed,fpath,line)
		expected = (stmt,pos_end)
		self.assertAssignment(result, expected)

	def test_07 (self):
		# RESULT
		text = "\ti\t=\t1\t\nerror"
		pos = 0
		fpath = "fall of the hobbits"
		line = 34
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		parsed = "i = 1"
		pos_end = len("\ti\t=\t1\t\n")
		stmt = Assignment(parsed,fpath,line)
		expected = (stmt,pos_end)
		# ASSERT
		self.assertAssignment(expected,result)

	def test_08 (self):
		# RESULT
		text = " \t\v\r 		 \f\n"
		pos = 0
		fpath = "the tower of sauron"
		line = 293578
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		parsed = ""
		pos_end = len(text)
		stmt = None
		expected = (stmt,pos_end)
		# ASSERT
		self.assertEmpty(result,expected)

	def test_09 (self):
		# RESULT
		text = "1 + 2 #comment\n wrong"
		pos = 0
		fpath = "the tower of sauron"
		line = 293578
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		parsed = "1 + 2"
		pos_end = len("1 + 2 #comment\n")
		stmt = Expression(parsed,fpath,line)
		expected = (stmt,pos_end)
		# assert
		self.assertExpression(result,expected)

	def test_10 (self):
		# RESULT
		text = "1  + 2"
		pos = 0
		fpath = "the tower of sauron"
		line = 293578
		result = parser.parse_statement(text,pos,fpath,line)
		# EXPECTED
		parsed = "1 + 2"
		pos_end = len(text)
		stmt = Expression(parsed,fpath,line)
		expected = (stmt,pos_end)
		# assert
		self.assertExpression(result,expected)

	def test_11 (self):
		self.assertAssignmentRaises("!=")
	def test_12 (self):
		self.assertAssignmentRaises("==")
	def test_13 (self):
		self.assertAssignmentRaises("=")
	def test_14 (self):
		self.assertAssignmentRaises("1=1")
	def test_15 (self):
		self.assertAssignmentRaises("\"this string never ends...")