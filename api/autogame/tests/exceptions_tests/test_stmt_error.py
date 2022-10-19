#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import gamerules, StatementError

import unittest

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Error
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestStatementError(unittest.TestCase):

	def test_stmt_error_00 (self):
		with self.assertRaises(StatementError) as cm:
			raise StatementError()
		msg = "Invalid Statement(file: ???, line: ???)"
		self.assertIsInstance(cm.exception,Exception)
		self.assertIsInstance(cm.exception,StatementError)
		self.assertEqual(str(cm.exception),msg)
		self.assertEqual(repr(cm.exception),msg)

	def test_stmt_error_01 (self):
		val = "test string"
		with self.assertRaises(StatementError) as cm:
			raise StatementError(val=val)
		msg = "Invalid Statement(file: ???, line: ???): " + val
		self.assertIsInstance(cm.exception,Exception)
		self.assertIsInstance(cm.exception,StatementError)
		self.assertEqual(str(cm.exception),msg)
		self.assertEqual(repr(cm.exception),msg)

	def test_stmt_error_02 (self):
		fpath = "my file"
		with self.assertRaises(StatementError) as cm:
			raise StatementError(file=fpath)
		msg = "Invalid Statement(file: "+fpath+", line: ???)"
		self.assertIsInstance(cm.exception,Exception)
		self.assertIsInstance(cm.exception,StatementError)
		self.assertEqual(str(cm.exception),msg)
		self.assertEqual(repr(cm.exception),msg)

	def test_stmt_error_03 (self):
		line = 213
		with self.assertRaises(StatementError) as cm:
			raise StatementError(line=line)
		msg = "Invalid Statement(file: ???, line: "+str(line)+")"
		self.assertIsInstance(cm.exception,Exception)
		self.assertIsInstance(cm.exception,StatementError)
		self.assertEqual(str(cm.exception),msg)
		self.assertEqual(repr(cm.exception),msg)

	def test_stmt_error_04 (self):
		file = "file_7239.txt"
		line = 61927093
		with self.assertRaises(StatementError) as cm:
			raise StatementError(file=file,line=line)
		msg = "Invalid Statement(file: "+file+", line: "+str(line)+")"
		self.assertIsInstance(cm.exception,Exception)
		self.assertIsInstance(cm.exception,StatementError)
		self.assertEqual(str(cm.exception),msg)
		self.assertEqual(repr(cm.exception),msg)

	def test_stmt_error_05 (self):
		file = "__123__.txt"
		line = 57124
		val = " some string"
		with self.assertRaises(StatementError) as cm:
			raise StatementError(file=file,line=line,val=val)
		msg = "Invalid Statement(file: "+file+", line: "+str(line)+")"
		msg += ": " + val
		self.assertIsInstance(cm.exception,Exception)
		self.assertIsInstance(cm.exception,StatementError)
		self.assertEqual(str(cm.exception),msg)
		self.assertEqual(repr(cm.exception),msg)
