#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .rule_aux import get_eval_error_msg

import unittest

class AuxFunctions(unittest.TestCase):

	def test_get_eval_error_msg_00 (self):
		with self.assertRaises(TypeError):
			get_eval_error_msg(1231)

	def test_get_eval_error_msg_01 (self):
		with self.assertRaises(Exception):
			get_eval_error_msg("1")

	def test_get_eval_error_msg_02 (self):
		#expected = "SyntaxError: invalid token"
		expected = "SyntaxError"
		result = get_eval_error_msg("09")
		self.assertEqual(result,expected)

	def test_get_eval_error_msg_03 (self):
		#expected = "ValueError: invalid literal for int() with base 10: \'s\'"
		expected = "ValueError"
		result = get_eval_error_msg("int(\"s\")")
		self.assertEqual(result,expected)

	def test_get_eval_error_msg_04 (self):
		#expected = "TypeError: unsupported operand type(s) for +: \'int\' and \'list\'"
		expected = "TypeError"
		result = get_eval_error_msg("1 + [1]")
		self.assertEqual(result,expected)
