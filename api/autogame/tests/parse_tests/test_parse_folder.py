#!/usr/bin/env python
# -*- coding: utf-8 -*-

# from os import makedirs, rmdir
from os.path import join, isdir
from context import parser, testfiles_path as fpath

from .test_parse_file import TestParseFileBase

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse List File
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseFolderBase(TestParseFileBase):
	
	def assertParseFolder(self, folder):
		from context import testfiles_path
		from os.path import join
		import aux_functions
		if folder == 'empty':
			path = join(testfiles_path,'0 fields')
		else:
			path = join(testfiles_path,folder)
		name = folder.replace(' ','')
		function_name = 'get_%s_rules' % name
		if function_name not in dir(aux_functions):
			msg = "%s doens't exist in module 'aux_functions'" % function_name
			raise NameError(msg)
		expected = eval('aux_functions.%s()' % function_name, locals())
		result = parser.parse_folder(path)
		self.assertListRules(expected,result,ignore_fpath=True,ignore_line=True)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_folder(path)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> parse all the rule definitions from a folder
# >>> return a list with all the rule definitions parsed
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParseFolder (TestParseFolderBase):

	def test_parse_folder_v00 (self):
		# arrange
		### args
		fp = join(fpath, "empty_file")
		### expected
		list_rules = []
		expected = list_rules
		# act
		result = parser.parse_folder(fp)
		# assert
		msg = "\nExpected same number of created rules.\n"
		msg +=">>> len(result) = " + str(len(result)) + "\n"
		msg +=">>> len(expected) = " + str(len(expected)) + "\n"
		self.assertEqual(len(result),len(expected),msg)
		self.assertEqual(result,expected)

	def test_invalid_args_0 (self):
		with self.assertRaises(TypeError) as cm:
			parser.parse({0:fpath})

	def test_invalid_args_1 (self):
		with self.assertRaises(OSError) as cm:
			parser.parse_folder(124)

	def test_invalid_args_2 (self):
		with self.assertRaises(TypeError) as cm:
			parser.parse_folder(None)

	def test_invalid_args_3 (self):
		with self.assertRaises(TypeError) as cm:
			parser.parse_folder(["some","path"])

	def test_empty_dir (self):
		## create an empty directory
		dn = "empty_folder" # dirname
		dirpath = join(fpath,dn)
		# if not isdir(dirpath):
		# 	makedirs(dirpath)
		# refactor
		#self.assertRaises(OSError)
		#self.assertEqual(parser.parse_folder(dirpath),[])
		# try:
		# 	rmdir(dirpath)
		# except OSError:
		# 	print '''
		# 	ERROR: Failed to remove directory.
		# 	Reason: directory not empty
		# 	'''

	def test_empty_files (self):
		dn = "empty_file"
		dirpath = join(fpath,dn)
		result = parser.parse_folder(dirpath)
		self.assertListRules(result,[],
			ignore_fpath=True,ignore_line=True)

	def test_empty_rules (self):
		self.assertParseFolder('empty')

	def test_1field_rules (self):
		self.assertParseFolder('1 field')

	def test_2fields_rules (self):
		self.assertParseFolder('2 fields')

	def test_3fields_rules (self):
		self.assertParseFolder('3 fields')

	def test_allfields_rules (self):
		self.assertParseFolder('all fields')