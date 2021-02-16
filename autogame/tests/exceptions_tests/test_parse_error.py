#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import gamerules, ParseError

import unittest


### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Error
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseError(unittest.TestCase):

	def test_parse_error_01(self):
		try:
			raise ParseError()
		except ParseError as pe:
			self.assertEqual(str(pe), "Couldn't parse rule")

	def test_parse_error_02(self):
		file = "\\path\\to\\file.txt"
		msg = "Couldn't parse rule in file: "
		msg += file
		try:
			raise ParseError(file)
		except ParseError as pe:
			self.assertEqual(pe.file,file)
			self.assertTrue(pe.file and len(str(pe.file)) > 0)
			self.assertEqual(repr(pe), msg)

	def test_parse_error_03(self):
		line = 1
		msg = "Couldn't parse rule in line: " + str(line)
		try:
			raise ParseError(line=line)
		except ParseError as pe:
			self.assertEqual(pe.line,line)
			self.assertTrue(pe.line and len(str(pe.line)) > 0)
			self.assertEqual(str(pe), msg)

	def test_parse_error_04(self):
		file = "\\path\\to\\file.txt"
		line = 1
		msg = "Couldn't parse rule in line " + str(line)
		msg += ", file: " + file
		try:
			raise ParseError(file,line)
		except ParseError as pe:
			self.assertEqual(pe.line,line)
			self.assertEqual(pe.file,file)
			self.assertEqual(str(pe), msg)

	def test_parse_error_05(self):
		file = "\\parse\\error\\rule_file.txt"
		line = 45678
		val = "test_parse_error_05:: Error Message"
		msg = "Couldn't parse rule in line " + str(line)
		msg += ", file: " + file
		msg += "\n" + val
		try:
			raise ParseError(file,line,val)
		except ParseError as pe:
			self.assertEqual(pe.line,line)
			self.assertEqual(pe.file,file)
			self.assertEqual(pe.val,val)
			self.assertEqual(str(pe), msg)