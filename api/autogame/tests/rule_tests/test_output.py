#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import Output, Effect

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestOutput
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestOutput(BaseTestClass):

	def assertCreation(self,vals):
		effects = [Effect(e) for e in vals]
		o = Output(effects)
		self.assertOutput(o)
		self.assertEqual(o.effects(),effects)

	def assertCreationRaises(self,error,arg):
		with self.assertRaises(error):
			Output(arg)

	def assertComparison(self,e1,e2,equals=True):
		o1 = Output([Effect(e) for e in e1])
		o2 = Output([Effect(e) for e in e2])
		self.assertOutput(o1)
		self.assertOutput(o2)
		if equals:
			self.assertEqual(o1,o2)
			self.assertTrue(o1 == o2)
			self.assertFalse(o1 != o2)
		else:
			self.assertNotEqual(o1,o2)
			self.assertTrue(o1 != o2)
			self.assertFalse(o1 == o2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Output(val) --> Output
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestOutput):
	
	def test_01(self):
		""" create an output with several effects """
		self.assertCreation((132,"string",32.9,('asd',12,{}),None,[1,2,3]))
	
	def test_02(self):
		""" try to create an output with illegal values """
		self.assertCreationRaises(TypeError,132)
		self.assertCreationRaises(TypeError,32.9)
		self.assertCreationRaises(TypeError,None)
		self.assertCreationRaises(TypeError,"string")
		self.assertCreationRaises(TypeError,('asd',12,{}))
		self.assertCreationRaises(TypeError,[1,2,3])

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Equals(Output1,Output2)
# NotEquals(Output1,Output2)
# Two outpurs are equal if they have equal effects, False otherwise
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestComparison(TestOutput):

	def test_01(self):
		""" assert equal effects """
		val = 1,2,3
		self.assertComparison(val,val,True)
		val = Output([Effect(1)]),True,'something different',Exception,lambda x:x
		self.assertComparison(val,val,True)
		self.assertComparison((None,1,'asd'),(None,1,'asd'),True)

	def test_02(self):
		""" assert different effects """
		val1 = 'sd',None,234,'asd',{'a':10,'b':None}
		val2 = 'sd',None,234,'asd',{'a':10,'b':None},0
		self.assertComparison(val1,val2,False)