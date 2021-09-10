#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import BaseNode
from base_test_class import BaseTestClass


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestBlock
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestBaseNode(BaseTestClass):
	pass

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# BaseNode(line, fpath)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestBaseNode):

	def test_01 (self):
		line = 10
		fpath = "C:\\mypath"
		bn = BaseNode(fpath,line)
		self.assertIsInstance(bn,BaseNode)
		self.assertEqual(bn.line(),line)
		self.assertEqual(bn.path(),fpath)

	def test_02 (self):
		fpath = "C:\\mypath"
		line = [1,"sad",{1:'jj'}]
		with self.assertRaises(TypeError):
			BaseNode(fpath,line)

	def test_03 (self):
		fpath = 0b100011011
		line = 1002
		with self.assertRaises(TypeError):
			BaseNode(fpath,line)