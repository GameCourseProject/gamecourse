#!/usr/bin/env python
# -*- coding: utf-8 -*-

from os import listdir, makedirs, rmdir
from os.path import join, isdir
from context import RuleSystem, testfiles_path as tp
from aux_functions import get_1field_rules
from aux_functions import get_2fields_rules
from aux_functions import get_3fields_rules
from aux_functions import get_allfields_rules

test_folders = [n for n in listdir(tp) if isdir(join(tp,n))]

def test_load (test,dirname,path,rules):
	# if dirname == "new_empty_dir" and not isdir(join(tp,dirname)):
	# 	makedirs(join(tp,dirname))
	path_end = join(tp,dirname)
	rules_end = get_rules_end (dirname)
	path_start = get_start_path(dirname,path)
	rules_start = get_rules_start(dirname,rules,rules_end)
	# act
	rs = RuleSystem(path=path_start,rules=rules_start)
	test.assertEqual(rs.path(),path_start)
	test.assertEqual(rs.rules(),rules_start)
	rs.load(path_end)
	test.assertEqual(rs.path(),path_end)
	test.assertEqual(rs.rules(),rules_end)
	# if dirname == "new_empty_dir" and isdir(join(tp,dirname)):
	# 	rmdir(join(tp,dirname))

def get_rules_end(dirname):
	if dirname == "new_empty_dir" or dirname == "empty_file":
		rules_end = []
	else:
		fn = dirname.split(" ")
		fn = "get_" + fn[0] + fn[1] + "_rules()"
		rules_end = eval(fn)
	return rules_end

def get_start_path(dirname,path):
	if path == "same":
		path_start = join(tp,dirname)
	elif path == "diff":
		if dirname in test_folders:
			index = test_folders.index(dirname) + 1
			index = index % len(test_folders)
			path_start = join(tp,test_folders[index])
		else:
			path_start = join(tp,"2 fields")
	elif path == None:
		path_start = None
	else:
		raise Exception("invalid value for \'path\'")
	return path_start

def get_rules_start (dirname,rules,rules_end):
	if rules == "same":
		return rules_end
	elif rules == "diff":
		if dirname == "new_empty_dir":
			return get_0fields_rules()
		elif dirname == "empty_file":
			return []
		elif dirname in test_folders:
			fn = dirname.split(" ")
			fn = "get_" + fn[0] + fn[1] + "_rules()"
			return eval(fn)
		else:
			raise Exception("Invalid \'dirname\'.")
	elif rules == "empty":
		return []
	raise Exception("Invalid \'rules\' value.")
