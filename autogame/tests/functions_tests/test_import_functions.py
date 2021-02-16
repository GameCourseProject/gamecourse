#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import import_functions_from_rulepath
from context import import_functions_from_module
from context import import_functions_from_FuncPaths
from context import import_gamefunctions
from context import FPaths

from base_test_class import BaseTestClass

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestPathData
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestImportFunctions (BaseTestClass):

	def assertFunction(self,functions,f,x,y,result):
		self.assertIn(f,list(functions.keys()))
		self.assertEqual(functions[f](x,y),result)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# import_functions_from_module
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestImportFunctionsFromModule(TestImportFunctions):
	
	def test_01 (self):
		""" test import functions from arithmetic module """
		from .test_files.arithmetic.functions import arithmetic
		functions = import_functions_from_module(arithmetic)
		self.assertIsInstance(functions,dict)
		self.assertEqual(len(functions),5)
		self.assertFunction(functions,"add",3,-1,2)
		self.assertFunction(functions,"sub",-4,-4,0)
		self.assertFunction(functions,"mul",7,0,0)
		self.assertFunction(functions,"div",8,-1,-8)
		self.assertFunction(functions,"mod",10,3,1)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# import_functions_from_rulepath
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestImportFunctionsFromRulePath(TestImportFunctions):

	def test_01 (self):
		""" test import functions from arithmetic module """
		import os
		p = os.getcwd()
		# p = os.path.join(p,"functions_tests","test_files")
		p = os.path.join(p,"functions_tests","test_files","arithmetic")
		functions, fpaths = import_functions_from_rulepath(p)
		# Assert functions
		self.assertIsInstance(functions,dict)
		self.assertGreaterEqual(len(functions),5)
		self.assertFunction(functions,"add",3,-1,2)
		self.assertFunction(functions,"sub",-4,-4,0)
		self.assertFunction(functions,"mul",7,0,0)
		self.assertFunction(functions,"div",8,-1,-8)
		self.assertFunction(functions,"mod",10,3,1)
		# Assert fpaths
		p = os.path.join(p,"functions","arithmetic.py")
		expected = {p:("arithmetic",["add","sub","mul","div","mod"])}
		self.assertFPaths(fpaths,expected)

	def test_02 (self):
		""" test import functions with invalid argument type """
		self.assertRaises(TypeError,import_functions_from_rulepath,None)
		self.assertRaises(TypeError,import_functions_from_rulepath,123)
		self.assertRaises(TypeError,import_functions_from_rulepath,(1,2,3))
		self.assertRaises(TypeError,import_functions_from_rulepath,list(range(6)))
		self.assertRaises(TypeError,import_functions_from_rulepath,Exception())

	def test_03 (self):
		""" test import functions with invalid path """
		functions, fpaths = import_functions_from_rulepath('what')
		self.assertIsInstance(functions,dict)
		self.assertEqual(len(functions),0)
		self.assertEqual(functions,{})
		self.assertFPaths(fpaths)
		self.assertEqual(fpaths,FPaths())
		self.assertFalse(fpaths != FPaths())
		self.assertTrue(fpaths == FPaths())

	def test_04 (self):
		""" test import functions from a path that doesn't have the
		functions folder
		"""
		functions, fpaths = import_functions_from_rulepath('.')
		self.assertIsInstance(functions,dict)
		self.assertEqual(len(functions),0)
		self.assertEqual(functions,{})
		self.assertFPaths(fpaths)
		self.assertEqual(fpaths,FPaths())
		self.assertFalse(fpaths != FPaths())
		self.assertTrue(fpaths == FPaths())

	def test_05 (self):
		""" test import functions from arithmetic module """
		import os
		p = os.getcwd()
		p = os.path.join(p,"functions_tests","test_files","arithmetic","__init__.py")
		functions, fpaths = import_functions_from_rulepath(p)
		# Assert functions
		self.assertIsInstance(functions,dict)
		self.assertEqual(len(functions),5)
		self.assertFunction(functions,"add",3,-1,2)
		self.assertFunction(functions,"sub",-4,-4,0)
		self.assertFunction(functions,"mul",7,0,0)
		self.assertFunction(functions,"div",8,-1,-8)
		self.assertFunction(functions,"mod",10,3,1)
		# Assert fpaths
		p = os.path.dirname(p)
		p = os.path.join(p,"functions","arithmetic.py")
		expected = {p:("arithmetic",["add","sub","mul","div","mod"])}
		self.assertFPaths(fpaths,expected)

	def test_06 (self):
		""" test import classes from humans module """
		import os
		p = os.getcwd()
		p = os.path.join(p,"functions_tests","test_files","classes")
		functions, fpaths = import_functions_from_rulepath(p)
		# Assert functions
		self.assertIsInstance(functions,dict)
		self.assertEqual(len(functions),3)
		self.assertIn("Barbarian", list(functions.keys()))
		self.assertIn("Thief", list(functions.keys()))
		self.assertIn("Wizard", list(functions.keys()))
		# Assert fpaths
		p = os.path.join(p,"functions","humans.py")
		expected = {p:("humans",["Barbarian","Thief","Wizard"])}
		self.assertFPaths(fpaths,expected)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# import_functions_from_FPaths
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestImportFunctionsFromFPaths(TestImportFunctions):

	def test_01 (self):
		""" test import functions from arithmetic module """
		import os
		p = os.getcwd()
		p = os.path.join(p,"functions_tests","test_files","arithmetic")
		expected, fpaths = import_functions_from_rulepath(p)
		functions = import_functions_from_FuncPaths(fpaths)
		self.assertIsInstance(functions,dict)
		self.assertEqual(len(functions),len(expected))
		self.assertEqual(len(functions),5)
		self.assertEqual(sorted(list(functions.keys())),sorted(list(expected.keys())))
		for f in functions:
			self.assertIsInstance(f,str)
			self.assertTrue(f in expected)
			self.assertTrue(callable(functions[f]))
			self.assertEqual(type(functions[f]).__name__,"function")