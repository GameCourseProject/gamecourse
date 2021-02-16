#!/usr/bin/env python
# -*- coding: utf-8 -*-

# from os import listdir
# from os.path import join
# from context import Rule, testfiles_path
# from aux_functions import get_0fields_rules
# from aux_functions import get_1field_rules
# from aux_functions import get_2fields_rules
# from aux_functions import get_3fields_rules
# from aux_functions import get_allfields_rules

# from rs_aux import get_rules_end, get_rules_start, get_start_path

# import unittest

# class TestLoad(unittest.TestCase):
# 	pass

	# def test_get_rules_end_01 (self):
	# 	self.assertEquals(get_rules_end("new_empty_dir"),[])

	# def test_get_rules_end_02 (self):
	# 	self.assertEquals(get_rules_end("empty_file"),[])

	# def test_get_rules_end_03 (self):
	# 	self.assertEquals(get_rules_end("0 fields"),get_0fields_rules())

	# def test_get_rules_end_04 (self):
	# 	self.assertEquals(get_rules_end("1 field"),get_1field_rules())

	# def test_get_rules_end_05 (self):
	# 	self.assertEquals(get_rules_end("2 fields"),get_2fields_rules())

	# def test_get_rules_end_06 (self):
	# 	self.assertEquals(get_rules_end("3 fields"),get_3fields_rules())

	# def test_get_rules_end_07 (self):
	# 	self.assertEquals(get_rules_end("all fields"),get_allfields_rules())

	# def test_get_rules_start_01 (self):
	# 	dirnames = listdir(testfiles_path)
	# 	result = [Rule(),Rule()]
	# 	for dn in dirnames:
	# 		self.assertEquals(get_rules_start(dn,"same",result),result)

	# def test_get_rules_start_02 (self):
	# 	result = get_0fields_rules()
	# 	self.assertEquals(get_rules_start("new_empty_dir","diff",result),result)

	# def test_get_rules_start_03 (self):
	# 	result = []
	# 	self.assertEquals(get_rules_start("empty_file","diff",result),result)

	# def test_get_rules_start_04 (self):
	# 	result = get_0fields_rules()
	# 	self.assertEquals(get_rules_start("0 fields","diff",result),result)

	# def test_get_rules_start_05 (self):
	# 	result = get_1field_rules()
	# 	self.assertEquals(get_rules_start("1 field","diff",result),result)

	# def test_get_rules_start_06 (self):
	# 	result = get_2fields_rules()
	# 	self.assertEquals(get_rules_start("2 fields","diff",result),result)

	# def test_get_rules_start_07 (self):
	# 	result = get_3fields_rules()
	# 	self.assertEquals(get_rules_start("3 fields","diff",result),result)

	# def test_get_rules_start_08 (self):
	# 	result = get_allfields_rules()
	# 	self.assertEquals(get_rules_start("all fields","diff",result),result)

	# def test_get_rules_start_09 (self):
	# 	dirnames = listdir(testfiles_path)
	# 	dirnames.append("new_empty_dir")
	# 	for dn in dirnames:
	# 		self.assertEquals(get_rules_start(dn,"empty",[]),[])

	# def test_get_rules_start_10 (self):
	# 	dirname = "invalid dir"
	# 	with self.assertRaises(Exception):
	# 		get_rules_start(dirname,"diff",None)

	# def test_get_rules_start_11 (self):
	# 	with self.assertRaises(Exception):
	# 		get_rules_start(None,"invalid",None)

	# def test_get_start_context_01 (self):
	# 	path = testfiles_path
	# 	dn = "mydir"
	# 	self.assertEquals(get_start_context(dn,"same"),join(path,dn))

	# def test_get_start_context_02 (self):
	# 	path = testfiles_path
	# 	dn = "0 fields"
	# 	self.assertEquals(get_start_context(dn,"diff"),join(path,"1 field"))

	# def test_get_start_context_03 (self):
	# 	path = testfiles_path
	# 	dn = "another"
	# 	self.assertEquals(get_start_context(dn,"diff"),join(path,"2 fields"))

	# def test_get_start_context_04 (self):
	# 	path = testfiles_path
	# 	self.assertEquals(get_start_context(Exception(),None),None)

	# def test_get_start_context_05 (self):
	# 	path = testfiles_path
	# 	with self.assertRaises(Exception):
	# 		get_start_context(Exception(),135)	