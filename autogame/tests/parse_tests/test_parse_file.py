#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os

from context import parser
from context import Rule
from context import testfiles_path as fpath

from .test_parse_list_rules import TestParseListRulesBase

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse File
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseFileBase(TestParseListRulesBase):

	def assertParseFile(self,name,dirname):
		from context import testfiles_path
		from os.path import join
		filename = 'rule_%s.txt' % name
		path = join(testfiles_path,dirname,filename)
		function_name = 'get_rule_%s' % name
		exec('from aux_functions import %s' % function_name)
		expected = eval('%s()' % function_name, locals())
		result = parser.parse_file(path)
		self.assertListRules(expected,result,
			ignore_fpath=True,ignore_line=True)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_file(fpath)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> parse all the rule definitions in the file specificied by the fpath
# >>> return a list with all the rule definitions parsed
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParseFile (TestParseFileBase):

	def test_invalid_args (self):
		with self.assertRaises(OSError):
			# refactor : parse_file(1) opens file descriptor
			#parser.parse_file(1) 
			parser.parse_file(10) 

	def test_invalid_file_type(self):
		fp = os.path.join(fpath, "empty_file", "invalid.py")
		result = parser.parse_file(fp)
		self.assertEqual(result,[])


	def test_emptyfile_00 (self):
		""" test parse empty file """
		fn = "empty_file_0.txt"
		directory = "empty_file"
		fp = os.path.join(fpath, directory, fn)
		expected = []
		result = parser.parse_file(fp)
		self.assertListRules(result,expected)

	# Empty files
	def test_emptyfile_01 (self):
		""" test parse empty file """
		fn = "empty_file_1.txt"
		directory = "empty_file"
		fp = os.path.join(fpath, directory, fn)
		expected = []
		result = parser.parse_file(fp)
		self.assertListRules(result,expected)

	# Empty files
	def test_emptyfile_02 (self):
		""" test parse empty file """
		fn = "empty_file_2.txt"
		directory = "empty_file"
		fp = os.path.join(fpath, directory, fn)
		expected = []
		result = parser.parse_file(fp)
		self.assertListRules(result,expected)

	# Empty Rule
	def test_00_empty (self):
		""" test parse file with empty rule definitions """
		self.assertParseFile('empty','0 fields')

	def test_01_name (self):
		""" test parse file with rule that have just the name """
		self.assertParseFile('name','1 field')

	def test_02_desc (self):
		""" test parse file with rule that have just the description """
		self.assertParseFile('desc','1 field')
		
	def test_03_precs (self):
		""" test parse file with rule that have just the preconditions """
		self.assertParseFile('precs','1 field')

	def test_04_acts (self):
		""" test parse file with rule that have just the actions """
		self.assertParseFile('acts','1 field')

	def test_05_nameXdesc (self):
		""" test parse file with rule that have name and description """
		self.assertParseFile('nameXdesc','2 fields')

	def test_06_nameXprecs (self):
		""" test parse file with rule that have name and preconditions """
		self.assertParseFile('nameXprecs','2 fields')

	def test_07_nameXacts (self):
		""" test parse file with rule that have name and actions """
		self.assertParseFile('nameXacts','2 fields')

	def test_08_descXprecs (self):
		""" test parse file with rule that have description and preconditions """
		self.assertParseFile('descXprecs','2 fields')

	def test_09_descXacts (self):
		""" test parse file with rule that have description and actions """
		self.assertParseFile('descXacts','2 fields')

	def test_10_precsXacts (self):
		""" test parse file with rule that have preconditions and actions """
		self.assertParseFile('precsXacts','2 fields')

	def test_11_nameXdescXprecs (self):
		""" test parse file with rule that have name,desc and precs """
		self.assertParseFile('nameXdescXprecs','3 fields')

	def test_12_nameXdescXacts (self):
		""" test parse file with rule that have name,desc and acts """
		self.assertParseFile('nameXdescXacts','3 fields')

	def test_13_nameXprecsXacts (self):
		""" test parse file with rule that have name,precs and acts """
		self.assertParseFile('nameXprecsXacts','3 fields')

	def test_14_descXprecsXacts (self):
		""" test parse file with rule that have desc,precs and acts """
		self.assertParseFile('descXprecsXacts','3 fields')

	def test_15_nameXdescXprecsXacts (self):
		""" test parse file with rule that have all fields """
		self.assertParseFile('nameXdescXprecsXacts','all fields')