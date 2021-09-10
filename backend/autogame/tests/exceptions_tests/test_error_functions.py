#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import get_error_msg

import unittest

class TestFunctions(unittest.TestCase):

	def test_get_error_msg_01 (self):
		msg = "my message"
		ex = TypeError(msg)
		# refactor
		#msg = "TypeError: " + msg
		msg = "TypeError"
		self.assertEqual(get_error_msg(ex),msg)

	def test_get_error_msg_02 (self):
		msg = "another message"
		ex = SyntaxError(msg)
		# refactor
		#msg = "SyntaxError: " + msg
		msg = "SyntaxError"
		self.assertEqual(get_error_msg(ex),msg)