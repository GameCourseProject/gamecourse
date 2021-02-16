#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import shutil
import shelve

from base_test_class import BaseTestClass
from context import DataManager
from context import PathError
from context import testfiles_path


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestPathData
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestDataManager(BaseTestClass):
	
	def assertLoadRaises(self,error,arg):
		""" asserts if the method load raises an exception with the given arg """
		dm = DataManager()
		self.assertRaises(error,dm.load,arg)

	def assertLoad(self,dm,f,d):
		""" asserts if the dm (DataManager) can load the rules and all
		associated information from the file f in directory d """ 
		filename = 'rule_%s.txt' % f
		path = os.path.join(testfiles_path,d,filename)
		function_name = 'get_rule_%s' % f
		exec('from aux_functions import %s' % function_name)
		expected = eval('%s()' % function_name, locals())
		dm.load(path)
		self.assertListRules(dm.rules,expected,
			ignore_fpath=True,ignore_line=True)
		self.assertEqual(dm.active_path,path)
		self.assertIn(os.path.dirname(path),dm.paths[path])


	def assertSave(self,dm):
		""" asserts if the dm (DataManager) can save its current state in a
		persistent file """
		if dm.active_path is None:
			self.assertEqual(dm.rules,[])
		else:
			expected = dm.rules
			dm.save()
			data_path = dm.paths[dm.active_path]
			data = shelve.open(data_path)
			result = [r.unpickle() for r in data['rules']]
			data.close()
			self.assertListRules(result,expected)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation:
# DataManager() --> new data manager object
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestDataManager):
	def test_01(self):
		""" creates a new DataManager obj """
		self.assertDataManager(DataManager(autosave=False))

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# DataManager.load(path) --> loads all the rules and related data associated
# with the given path and set it to be the active path
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestLoad(TestDataManager):

	def test_01(self):
		""" load with an invalid type argument """
		self.assertLoadRaises(TypeError,None)
		self.assertLoadRaises(TypeError,421)
		self.assertLoadRaises(TypeError,-44.3)
		self.assertLoadRaises(TypeError,("what","is","this","?"))
		self.assertLoadRaises(TypeError,[])
		self.assertLoadRaises(TypeError,{})

	def test_02(self):
		""" load with an invalid path """
		self.assertLoadRaises(PathError,"C:\\this\\path\\doesnt\\exist")

	def test_03(self):
		""" test load rules from a file path that wasn't loaded before """
		self.assertLoad(DataManager(False),'nameXdescXprecsXacts','all fields')
		

	def test_04(self):
		""" test load a previously loaded file that, meanwhile, has been removed """
		src = os.path.join(testfiles_path,"1\ field",'rule_acts.txt')
		dst = os.getcwd()
		os.system("cp " + src + " " + dst)
		#shutil.copy(src,dst)
		path1 = os.path.join(dst,'rule_acts.txt')
		path2 = os.path.join(testfiles_path,'1 field','rule_desc.txt')
		dm = DataManager(autosave=False)
		dm.load(path1)
		dm.load(path2)
		os.remove(path1)
		self.assertLoadRaises(PathError,path1)

	def test_05(self):
		""" test load a previously loaded file that was modified """
		src = os.path.join(testfiles_path,'1\ field','rule_acts.txt')
		dst = os.getcwd()
		os.system("cp " + src + " " + dst)
		#shutil.copy(src,dst)
		path1 = os.path.join(dst,'rule_acts.txt')
		dm = DataManager()
		dm.load(path1)
		num_rules_before = len(dm.rules)
		# change the contents of the path
		with open(path1,'r') as f:
			text = f.read()
		with open(path1,'a') as f:
			f.write('\n' + text)
		dm.load(path1)
		self.assertEqual(num_rules_before * 2, len(dm.rules))
		os.remove(path1)
		dm.remove(path1)
		self.assertDataManager(dm)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# DataManager.save(path) --> saves the current state of the DataManager into
# a datafile specific to the active file
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestSave(TestDataManager):

	@classmethod
	def tearDownClass(cls):
		# cleanup for test_02
		from tests.data_tests.data_functions import rmdir
		path = os.path.join(testfiles_path,'all fields','data')
		rmdir(path,'r')
		setup_path = os.path.join(os.getcwd(), 'data', 'setup.dat.db')
		os.remove(setup_path)
		# TO DO: check this

	def test_01(self):
		""" save a default data manager """
		self.assertSave(DataManager(autosave=False))

	def test_02(self):
		""" save a loaded Data Manager """
		dm = DataManager(autosave=False)
		path = os.path.join(testfiles_path,'all fields','rule_nameXdescXprecsXacts.txt')
		dm.load(path)
		self.assertSave(dm)
