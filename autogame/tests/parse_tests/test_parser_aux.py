#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import gamerules, parser, PathError, ParseError, testfiles_path
from context import RuleError
from .parser_aux import get_is_followed_valid_values
from .parser_aux import create_folder_from_folders
from .parser_aux import remove_folder_from_path
from .parser_aux import copy_folders_files, copy_files
from .parser_aux import remove_folder_from_path
from gamerules.ruleparser.aux_functions import isalnum, isalpha, isnum
from gamerules.ruleparser.aux_functions import is_next_word
from gamerules.ruleparser.aux_functions import skip_blank
from gamerules.ruleparser.aux_functions import skip_blank_inline
from gamerules.ruleparser.aux_functions import is_next
from gamerules.ruleparser.aux_functions import is_followed
from gamerules.ruleparser.aux_functions import get_stmt_type

import unittest

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Test Case
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParserAux(unittest.TestCase):
	""" Test Case for gamerules.rule_parser module auxiliar functions """

	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	### Aux Functions Tests
	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	def test_isalnum_utf8_t0 (self):
		letters = [chr(i) for i in range(ord('a'),ord('z')+1)]
		letters += [chr(i) for i in range(ord('A'),ord('Z')+1)]
		letters += [chr(i) for i in range(ord('0'),ord('9')+1)]
		for l in letters:
			self.assertTrue(isalnum(l), \
				"isalnum(\'"+l+"\') should return \'True\'")

	def test_isalnum_utf8_f0 (self):
		letters = [chr(i) for i in range(0,48)]
		letters += [chr(i) for i in range(58,65)]
		letters += [chr(i) for i in range(91,97)]
		letters += [chr(i) for i in range(123,128)]
		for l in letters:
			self.assertFalse(isalnum(l), \
				"isalnum(\'"+l+"\') should return \'False\'")

	def test_isalpha_utf8(self):
		letters = [chr(i) for i in range(ord('a'),ord('z')+1)]
		letters += [chr(i) for i in range(ord('A'),ord('Z')+1)]
		for l in letters:
			self.assertTrue(isalpha(l), \
				"isalpha(\'"+l+"\') should return \'True\'")

	def test_isnum_t0 (self):
		letters = [chr(i) for i in range(ord('0'),ord('9')+1)]
		for l in letters:
			self.assertTrue(isnum(l), \
				"isnum(\'"+l+"\') should return \'True\'")

	def test_isnum_f0 (self):
		letters = [chr(i) for i in range(ord('a'),ord('z')+1)]
		letters += [chr(i) for i in range(ord('A'),ord('Z')+1)]
		for l in letters:
			self.assertFalse(isnum(l), \
				"isnum(\'"+l+"\') should return \'False\'")

	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	### is_next([key][,text][,pos][,line])
	### @arg key <String>
	### @arg text <String>
	### @arg pos <Int>
	### @arg line <Int>
	### @return <Boolean>
	### if 'key' is the next non-blank string to appear in text, return a tuple
	### with the position to the start position of 'key' in text and the line
	### returns false otherwise
	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	def test_is_next_000(self):
		self.assertFalse(is_next())
	def test_is_next_001(self):
		self.assertFalse(is_next(None,None,None,None))
	def test_is_next_002(self):
		key = ""
		text = "sometext"
		self.assertFalse(is_next(key, text))
	def test_is_next_003(self):
		key = "s"
		text = "sometext"
		result = (0,1)
		self.assertEqual(is_next(key, text),result)
	def test_is_next_004(self):
		key = "mykey"
		blank = "  \t\t\r\f\v \n\n\t\t"
		text = blank + key
		pos = len(blank)
		line = blank.count("\n")+1
		result = (pos,line)
		self.assertEqual(is_next(key, text),result)
	def test_is_next_005(self):
		key = "mykey"
		to_ignore = "invalid words, these should be ignored"
		blank = "  \t\t\r\f\v \n\n\t\t"
		text = to_ignore + blank + key
		start_pos = len(to_ignore)
		start_line = 378
		pos = start_pos + len(blank)
		line = blank.count("\n")+start_line
		result = (pos,line)
		self.assertEqual(
			is_next(key, text, start_pos, start_line),
			result)

	def test_is_next_006(self):
		self.assertFalse(is_next("",None,None,None))
	
	def test_is_next_007(self):
		self.assertFalse(is_next("","",None,None))
	
	def test_is_next_008(self):
		self.assertFalse(is_next("","",1.0,None))
	
	def test_is_next_009(self):
		self.assertFalse(is_next("","",25,None))

	def test_is_next_010(self):
		self.assertFalse(is_next("","",0x678,None))

	def test_is_next_011(self):
		self.assertFalse(is_next("","",-5,None))
	
	def test_is_next_012(self):
		self.assertFalse(is_next("","",-53,1.0))
	
	def test_is_next_013(self):
		self.assertFalse(is_next("","",-53,623))
	
	def test_is_next_014(self):
		self.assertFalse(is_next("","",-53,0x3bfe))
	
	def test_is_next_015(self):
		self.assertFalse(is_next("",""))
	
	def test_is_next_016(self):
		self.assertFalse(is_next("a",""))
	
	def test_is_next_017(self):
		self.assertFalse(is_next("aa","a"))
	
	def test_is_next_018(self):
		self.assertFalse(is_next("x","x",-567))
	
	def test_is_next_019(self):
		self.assertFalse(is_next("x","x",90))
	
	def test_is_next_020(self):
		self.assertFalse(is_next("bob","bob",15,0))
	
	def test_is_next_021(self):
		key = "why?"
		text = "long text but the word is not found"
		pos = 4
		line = 350
		self.assertFalse(is_next(key,text,pos,line))

	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	### is_next_word(str word, str text, int pos)
	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	def test_is_next_word_001(self):
		self.assertTrue(is_next_word(
			"when","when I'm king I'll rule the world"))

	def test_is_next_word_002(self):
		self.assertTrue(is_next_word(
			"when",
			"I'll rule the world when I'm king",
			len("I'll rule the world ")))

	def test_is_next_word_f0 (self):
		word = ""
		text = "valid text"
		pos = 0
		self.assertFalse(is_next_word(word,text,pos))

	def test_is_next_word_f1 (self):
		word = "text"
		text = "text"
		pos = 0
		self.assertFalse(is_next_word(word,text,pos))

	def test_is_next_word_f2 (self):
		word = "valid"
		text = "valid text"
		pos = -1
		self.assertFalse(is_next_word(word,text,pos))

	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	### is_followed (list )
	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	# Guard Tests
	def test_is_followed_g01 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(None,b,t,p))
	def test_is_followed_g02 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,[],t,p))
	def test_is_followed_g03 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,b,1.0,p))
	def test_is_followed_g04 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,b,t,{}))
	def test_is_followed_g05 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,b,t,len(t)))
	def test_is_followed_g06 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed((1,2),b,t,p))
	def test_is_followed_g07 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,(1,"s"),t,p))
	def test_is_followed_g08 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed((),(),t,p))
	def test_is_followed_g09 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed("",(),t,p))
	def test_is_followed_g10 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed((),"",t,p))
	def test_is_followed_g11 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed("","",t,p))
	def test_is_followed_g12 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed((),b,t,p))
	def test_is_followed_g13 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed("",b,t,p))
	def test_is_followed_g14 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,(),t,p))
	def test_is_followed_g15 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,"",t,p))
	# Return True
	def test_is_followed_t_rand (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertTrue(is_followed(a,b,t,p))
	def test_is_followed_t04 (self):
		a = "a"
		b = "b"
		t = "ab"
		p = 0
		self.assertTrue(is_followed(a,b,t,p))
	def test_is_followed_t05 (self):
		a = "firstkey"
		b = ("1","2","3")
		blank = " \t \v 	\r"
		p = 0
		t = blank + a
		self.assertFalse(is_followed(a,b,t,p))
		t = blank + a + b[0]
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a + b[1]
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a + b[2]
		self.assertTrue(is_followed(a,b,t,p))
	def test_is_followed_t06 (self):
		a = ("aa","b1b","c44c")
		b = "my:my"
		blank = " \t \v 	\r"
		p = 0
		t = blank + a[0] + blank
		self.assertFalse(is_followed(a,b,t,p))
		t = blank + a[0] + blank + b
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a[1] + blank + b
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a[2] + blank + b
		self.assertTrue(is_followed(a,b,t,p))
	def test_is_followed_t07 (self):
		a = ("_awkward_","myliege","hohohoh")
		b = ("my:my","01", ".:.")
		blank = " \t \v 	\r"
		p = 0
		t = blank + a[0] + blank + b[2]
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a[1] + blank + b[1]
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a[2] + blank + b[0]
		self.assertTrue(is_followed(a,b,t,p))
	def test_is_followed_t08 (self):
		a = ("when","then","rule")
		b = ":"
		blank = " \t \v 	\r"
		p = 0
		t = blank + a[0] + blank + b
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a[1] + blank + b
		self.assertTrue(is_followed(a,b,t,p))
		t = blank + a[2] + blank + b
		self.assertTrue(is_followed(a,b,t,p))
	# Return False
	def test_is_followed_f00 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed("a",b,t,p))
	def test_is_followed_f01 (self):
		a,b,t,p = get_is_followed_valid_values()
		self.assertFalse(is_followed(a,"b",t,p))
	def test_is_followed_f02 (self):
		a,b,t,p = get_is_followed_valid_values()
		a = ("a","b","c")
		self.assertFalse(is_followed(a,b,t,p))
	def test_is_followed_f03 (self):
		a,b,t,p = get_is_followed_valid_values()
		b = ("a","b","c")
		self.assertFalse(is_followed(a,b,t,p))
	def test_is_followed_f04 (self):
		a,b,t,p = get_is_followed_valid_values()
		p = 0
		t = "\n" + t
		self.assertFalse(is_followed(a,b,t,p))

	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	### get_stmt_type(???)
	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	def test_get_stmt_type_0_00(self):
		self.assertEqual(get_stmt_type(""),0)
	def test_get_stmt_type_1_00(self):
		self.assertEqual(get_stmt_type("1 + 1"),1)
	def test_get_stmt_type_1_01(self):
		self.assertEqual(get_stmt_type("2 - 0"),1)
	def test_get_stmt_type_1_02(self):
		self.assertEqual(get_stmt_type("-3 * 3"),1)
	def test_get_stmt_type_1_03(self):
		self.assertEqual(get_stmt_type("8.0 / 2"),1)
	def test_get_stmt_type_1_04(self):
		self.assertEqual(get_stmt_type("valid % _expr"),1)
	def test_get_stmt_type_1_05(self):
		self.assertEqual(get_stmt_type("2 ** 1"),1)
	def test_get_stmt_type_1_06(self):
		self.assertEqual(get_stmt_type("9 // 4"),1)
	def test_get_stmt_type_1_07(self):
		self.assertEqual(get_stmt_type("i == j != 0 != 3"),1)
	def test_get_stmt_type_1_08(self):
		self.assertEqual(get_stmt_type("i or False and not None"),1)
	def test_get_stmt_type_1_09(self):
		self.assertEqual(get_stmt_type("i > j < i"),1)
	def test_get_stmt_type_1_10(self):
		self.assertEqual(get_stmt_type("i <= j >= i"),1)
	def test_get_stmt_type_2_00(self):
		self.assertEqual(get_stmt_type("i = 0"),2)
	def test_get_stmt_type_2_01(self):
		self.assertEqual(get_stmt_type("i += 12"),2)
	def test_get_stmt_type_2_02(self):
		self.assertEqual(get_stmt_type("i -= 9342"),2)
	def test_get_stmt_type_2_03(self):
		self.assertEqual(get_stmt_type("i *= f(5+2)"),2)
	def test_get_stmt_type_2_04(self):
		self.assertEqual(get_stmt_type("i /= f(x)+g(x)"),2)
	def test_get_stmt_type_2_05(self):
		self.assertEqual(get_stmt_type("i %= 2>>2"),2)
	def test_get_stmt_type_2_06(self):
		self.assertEqual(get_stmt_type("i **= 3<<2"),2)
	def test_get_stmt_type_2_07(self):
		self.assertEqual(get_stmt_type("i //= 10&2"),2)
	def test_get_stmt_type_2_08(self):
		self.assertEqual(get_stmt_type("i = True and False"),2)
	def test_get_stmt_type_2_09(self):
		self.assertEqual(get_stmt_type("i = [1,2,3]"),2)
	def test_get_stmt_type_2_10(self):
		self.assertEqual(get_stmt_type("i = {something wrong]]"),2)

	def test_get_stmt_type_invalid_00(self):
		with self.assertRaises(RuleError):
			get_stmt_type("=")
	def test_get_stmt_type_invalid_01(self):
		with self.assertRaises(RuleError):
			get_stmt_type("= 1 + 2")
	def test_get_stmt_type_invalid_02(self):
		with self.assertRaises(RuleError):
			get_stmt_type("i =")		

	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	### skip_blank(<str>text, <int> pos, <int> line, <bool> inline)
	### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
	def test_skip_blank_00 (self):
		# Arrange
		## Args
		## Expected
		pos = 0
		line = 1
		expected = (pos, line)
		## Act
		result = skip_blank()
		## Assert
		self.assertEqual(result,expected)
	
	def test_skip_blank_01 (self):
		# Arrange
		## Args
		text = " " * 6
		## Expected
		pos = len(text)
		line = 1
		expected = (pos, line)
		## Act
		result = skip_blank(text)
		## Assert
		self.assertEqual(result,expected)

	def test_skip_blank_02 (self):
		# Arrange
		## Args
		text = " 	\t\r\v\n\f" * 33
		## Expected
		pos = len(text)
		line = 1 + text.count("\n")
		expected = (pos, line)
		## Act
		result = skip_blank(text)
		## Assert
		self.assertEqual(result,expected)

	def test_skip_blank_03 (self):
		# Arrange
		## Args
		text = " 	\t\r\v\n\f"
		## Expected
		pos = len(" 	\t\r\v")
		expected = pos
		## Act
		result = skip_blank_inline(text)
		## Assert
		self.assertEqual(result,expected)

	def test_skip_blank_04 (self):
		# Arrange
		## Args
		text = " stop	\t\r\v\n\f"
		## Expected
		pos = len(" ")
		line = 1
		expected = (pos, line)
		## Act
		result1 = skip_blank(text)
		result2 = skip_blank_inline(text)
		## Assert
		self.assertEqual(result1,expected)
		self.assertEqual(result2,expected[0])

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Tests on functions manipulating folders
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# empty_folder = join(testfiles_path,"new_empty_folder_H91827498")
# class TestFolders(unittest.TestCase):

	# @classmethod
	# def setUpClass(cls):
	# 	makedirs(empty_folder)

	# @classmethod
	# def tearDownClass(cls):
	# 	rmdir(empty_folder)

	# def test_folder_all_creation_i01 (self):
	# 	path = testfiles_path
	# 	with self.assertRaises(TypeError) as cm:
	# 		create_folder_from_folders(path, ["all"])

	# def test_folder_all_creation_i02 (self):
	# 	path = testfiles_path
	# 	with self.assertRaises(TypeError) as cm:
	# 		create_folder_from_folders(21341, "all")

	# def test_folder_all_creation_i03 (self):
	# 	path = testfiles_path
	# 	with self.assertRaises(TypeError) as cm:
	# 		create_folder_from_folders("C:\\invalid\\path", "all")

	# def test_folder_all_creation_01 (self):
	# 	path = testfiles_path
	# 	create_folder_from_folders (path, "all")
	# 	self.assertTrue(isdir(join(path,"all")))
	# 	remove_folder_from_path (path,"all")
	# 	self.assertFalse(isdir(join(path,"all")))

	# def test_folder_all_creation_02 (self):
	# 	path = testfiles_path
	# 	create_folder_from_folders (path, "all")
	# 	create_folder_from_folders (path, "all")
	# 	create_folder_from_folders (path, "all")
	# 	self.assertTrue(isdir(join(path,"all")))
	# 	remove_folder_from_path (path,"all")
	# 	self.assertFalse(isdir(join(path,"all")))

	# def test_copy_folders_files_01 (self):
	# 	path = testfiles_path
	# 	with self.assertRaises(TypeError):
	# 		copy_folders_files({"0 fields"},join(path,"all"))

	# def test_copy_folders_files_02 (self):
	# 	path = testfiles_path
	# 	with self.assertRaises(TypeError):
	# 		copy_folders_files([],(2,3,4))

	# def test_copy_folders_files_03 (self):
	# 	path = testfiles_path
	# 	with self.assertRaises(TypeError):
	# 		copy_folders_files([],"C:\\wrong\\path")

	# def test_copy_folders_files_04 (self):
	# 	path = join(testfiles_path,"empty_file")
	# 	initial = listdir(path)
	# 	copy_folders_files([],path)
	# 	final = listdir(path)
	# 	self.assertEquals(initial,final)

	# def test_copy_folders_files_05 (self):
	# 	path = join(testfiles_path,"empty_file")
	# 	with self.assertRaises(TypeError):
	# 		copy_folders_files(listdir(path),path)

	# def test_copy_files_01 (self):
	# 	path = join(testfiles_path,"empty_file")
	# 	initial = listdir(path)
	# 	with self.assertRaises(TypeError):
	# 		copy_files(True,path)
	# 	final = listdir(path)
	# 	self.assertEquals(final,initial)

	# def test_copy_files_02 (self):
	# 	path = join(testfiles_path,"empty_file")
	# 	initial = listdir(path)
	# 	with self.assertRaises(TypeError):
	# 		copy_files(path,False)
	# 	final = listdir(path)
	# 	self.assertEquals(final,initial)

	# def test_copy_files_03 (self):
	# 	path = join(testfiles_path,"empty_file")
	# 	initial = listdir(path)
	# 	with self.assertRaises(TypeError):
	# 		copy_files("C:\\invalid\\dir\\",path)
	# 	final = listdir(path)
	# 	self.assertEquals(final,initial)

	# def test_copy_files_04 (self):
	# 	path = join(testfiles_path,"empty_file")
	# 	initial = listdir(path)
	# 	with self.assertRaises(TypeError):
	# 		copy_files(path,"C:\\invalid\\dir\\")
	# 	final = listdir(path)
	# 	self.assertEquals(final,initial)

	# def test_remove_folder_from_path_01 (self):
	# 	path = join(empty_folder,"to_delete")
	# 	with self.assertRaises(TypeError):
	# 		remove_folder_from_path(142,"to_delete")

	# def test_remove_folder_from_path_02 (self):
	# 	path = empty_folder
	# 	with self.assertRaises(TypeError):
	# 		remove_folder_from_path("C:\\invalid\\dir\\","to_delete")

	# def test_remove_folder_from_path_03 (self):
	# 	path = join(empty_folder,"to_delete")
	# 	with self.assertRaises(TypeError):
	# 		remove_folder_from_path("safe_path",{"to_delete"})

	# def test_remove_folder_from_path_04 (self):
	# 	path = empty_folder
	# 	try:
	# 		makedirs(join(empty_folder,"to_delete"))
	# 		with self.assertRaises(Exception):
	# 			remove_folder_from_path(path,"what")
	# 	except Exception as e:
	# 		pass
	# 	finally:
	# 		to_delete = join(empty_folder,"to_delete")
	# 		if isdir(to_delete):
	# 			rmdir(to_delete)

	# def test_remove_folder_from_path_05 (self):
	# 	path = empty_folder
	# 	try:
	# 		makedirs(join(empty_folder,"to_delete"))
	# 		makedirs(join(join(empty_folder,"to_delete"),"to_delete"))
	# 		with self.assertRaises(Exception):
	# 			remove_folder_from_path(path,"to_delete")
	# 	except Exception as e:
	# 		pass
	# 	finally:
	# 		to_delete = join(empty_folder,"to_delete")
	# 		if isdir(to_delete):
	# 			if isdir(join(to_delete,"to_delete")):
	# 				rmdir(join(to_delete,"to_delete"))
	# 			rmdir(to_delete)