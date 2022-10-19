#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Expression, Statement, RuleError
# from aux_functions import assert_expression

import unittest


class TestExpression(unittest.TestCase):
	""" Abstract class to define common test functions """

	def assertExpression(self, expression, text=None, filepath=None, line=None):
		self.assertIsInstance(expression,Expression)
		self.assertIsInstance(expression,Statement)
		self.assertEqual(type(expression.code()).__name__,'code')
		if text != None:
			self.assertEqual(expression.text(),text)
		if filepath != None:
			self.assertEqual(expression.path(),filepath)
		if line != None:
			self.assertEqual(expression.line(),line)

	def assertCreation(self, text=None, filepath=None, line=None):
		if filepath == None and line == None:
			expression = Expression(text)
		elif filepath == None:
			expression = Expression(text,line=line)
		elif line == None:
			expression = Expression(text)
		else:
			expression = Expression(text,filepath,line)
		self.assertExpression(expression,text,filepath,line)

	def assertCreationError(self, error_msg, text=None, filepath=None, line=None, offset=None):
		if filepath == None:
			filepath = "C:\\assert\\creation\\error.txt"
		if line == None:
			line = 404
		with self.assertRaises(RuleError) as cm:
			Expression(text,filepath,line)
		msg = "in line %d of file '%s':\n" % (line,filepath)
		msg+= "\t%s\n" % text
		if offset != None:
			blank = ' ' * (offset - 1)
			msg+= "\t%s^\n" % blank
		msg+= error_msg
		self.assertEqual(str(cm.exception), msg)

	def assertFire(self,text,scope=None):
		""" Asserts method Fire(self [,scope]) """
		expression = Expression(text)
		self.assertExpression(expression,text)
		if scope == None:
			result = expression.fire()
			scope = {}
		else:
			expected_scope = dict(scope)
			result = expression.fire(scope)
			eval(text,expected_scope)
			self.assertEqual(scope,expected_scope)
		expected = eval(text,dict(scope))
		self.assertEqual(result,expected)

	def assertFireError(self, error_msg, text):
		filepath = "C:\\examples\\rules\\rule303.txt"
		line = 123
		assignment = Expression(text,filepath,line)
		with self.assertRaises(RuleError) as cm:
			assignment.fire()
		msg = "in line %d of file '%s':\n" % (line,filepath)
		msg+= "\t%s\n" % text
		#msg+= error_msg
		self.assertEqual(str(cm.exception), msg)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Expression(self, fpath, line) -> Returns a validated Expression Node
# Expression.validate(self) -> Validates the Expression Node
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Creation(TestExpression):

	def test_00 (self):
		t = "callable(1,2,3) + (lambda x: x**x)(2)"
		self.assertCreation(t)

	def test_01 (self):
		self.assertCreation("33 + 4","my_file.txt",43)


	def test_02 (self):
		f = "C:\\rules\\pcm2018.txt"
		error_msg = "SyntaxError: invalid syntax"
		self.assertCreationError(error_msg,"a = 4",f,3,3)

	def test_03 (self):
		f = "C:\\rules\\pcm2018.txt"
		error_msg = "SyntaxError: invalid syntax"
		self.assertCreationError(error_msg,"a += 4",f,24,3)

	def test_04 (self):
		error_msg = "SyntaxError: invalid syntax"
		self.assertCreationError(error_msg,"a -= 4",offset=3)

	def test_05 (self):
		error_msg = "SyntaxError: invalid syntax"
		self.assertCreationError(error_msg,"a *= 4",offset=3)

	def test_06 (self):
		error_msg = "SyntaxError: unexpected EOF while parsing"
		f = "C:\\rules\\1933.txt"
		l = 323
		self.assertCreationError(error_msg,"",f,l,1)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Expression.fire(self, scope) -> Evaluates the Expression code
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Fire(TestExpression):

	def test_01 (self):
		t = "[1,2,3]"
		self.assertFire(t)

	def test_02 (self):
		s1 = 'tomorrow'
		s2 = 'is not today'
		scope = dict()
		scope['s1'] = locals()['s1']
		scope['s2'] = locals()['s2']
		t = "s1 + ' ' + s2"
		self.assertFire(t,scope)

	def test_03 (self):
		def mul(l,m):
			return [m * e for e in l]
		scope = dict()
		scope['mul'] = locals()['mul']
		t = "mul([1,2,3],2)"
		self.assertFire(t,scope)

	def test_04 (self):
		t = "mul([1,2,3],2)"
		error_msg = "NameError: name 'mul' is not defined"
		self.assertFireError(error_msg,t)
