#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import PathData, PathError

import os

DATADIR = 'data'
TESTDIR = os.path.join(os.getcwd(),'testdir')

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestPathData
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestPathData (BaseTestClass):

	def assertAdd(self,pd,path):
		""" adds a valid path to the PathData and checks if the operation was
		done properly
		"""
		self.assertPathData(pd)
		next_index = pd.next
		unused = list(pd.unused)
		paths = dict(pd.indexpaths)
		pd.add(path)
		self.assertPathData(pd)
		if len(unused) > 0:
			self.assertEqual(pd.next, next_index)
			self.assertEqual(len(pd.unused),len(unused)-1)
		else:
			self.assertEqual(pd.next, next_index+1)
			self.assertEqual(len(pd.unused),len(unused))
		self.assertIn(path, list(pd.indexpaths.keys()))
		# checks if the data structure, folders and files, were properly added
		datapath = os.path.dirname(path) if os.path.isfile(path) else path
		datapath = os.path.join(datapath, DATADIR)
		datafile = '%s.dat' % pd.get_index(path)
		datapath = os.path.join(datapath,datafile)
		self.assertTrue(pd[path],datapath)

	def assertAddMultiple(self, pd, paths):
		""" same as assertAdd but instead of testing the addition of just 1 path
		test the addition of multiple paths
		"""
		for p in paths:
			self.assertAdd(pd,p)		

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# PathData() ---> return a PathData object
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestPathData):

	def test_01(self):
		pd = PathData()
		self.assertPathData(pd)
		self.assertEqual(pd.next,1)
		self.assertEqual(pd.unused,[])
		self.assertEqual(pd.indexpaths,{})
		self.assertEqual(pd.mtime,{})

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# PathData.add(path) ---> adds the path
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAdd(TestPathData):

	@classmethod
	def setUpClass(cls):
		# create a base test directory
		os.mkdir(TESTDIR)
		# create some empty directories
		for i in range(1,4):
			dirname = "dir%d" % i
			os.mkdir(os.path.join(TESTDIR,dirname))
		# create some empty dummy files
		for i in range(1,4):
			fname = "r%d.txt" % i
			fpath = os.path.join(TESTDIR,fname)
			f = open(fpath,'w')
			f.close()

	@classmethod
	def tearDownClass(cls):
		from tests.data_tests.data_functions import rmdir
		rmdir(TESTDIR,'r')

	def test_i01(self):
		""" try to perform add with an invalid argument type """
		pd = PathData()
		self.assertRaises(TypeError,pd.add,1234)
		self.assertRaises(TypeError,pd.add,54.23)
		self.assertRaises(TypeError,pd.add,True)
		self.assertRaises(TypeError,pd.add,('C:\\Python27',23))
		self.assertRaises(TypeError,pd.add,[])
		self.assertRaises(TypeError,pd.add,{})
		self.assertRaises(TypeError,pd.add,Exception)
		self.assertRaises(TypeError,pd.add,TypeError())

	def test_i02(self):
		""" try to perform add with non existent path """
		path = "C:\\this\\folder\\doesnt\\exist"
		pd = PathData()
		self.assertRaises(PathError,pd.add,path)

	def test_v01(self):
		""" add a path to a directory to an empty PathData """
		self.assertAdd(PathData(),TESTDIR)

	def test_v02(self):
		""" add three different paths """
		pd = PathData()
		l = [os.path.abspath(os.path.join(TESTDIR,'dir%d')) % i for i in range(1,4)]
		self.assertAddMultiple(pd,l)

	def test_v03(self):
		""" add a path with some unused indexed number """
		pd = PathData()
		pd.indexpaths = {TESTDIR: 2}
		pd.next = 3
		pd.unused = [1]
		pd.datapaths = {TESTDIR: TESTDIR + '/data/2.dat'}
		pd.mtime = {TESTDIR: os.path.getmtime(TESTDIR)}
		self.assertAdd(pd,TESTDIR)

	def test_v04(self):
		""" add a file path """
		self.assertAdd(PathData(),TESTDIR + '/r1.txt')