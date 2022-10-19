#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import Effect

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestEffect
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestEffect(BaseTestClass):

	def assertCreation(self,val):
		e = Effect(val)
		self.assertEffect(e)
		self.assertEqual(e.val(),val)

	def assertComparison(self,e1,e2,equals=True):
		self.assertEffect(e1)
		self.assertEffect(e2)
		if equals:
			self.assertEqual(e1,e2)
			self.assertTrue(e1 == e2)
			self.assertFalse(e1 != e2)
		else:
			self.assertNotEqual(e1,e2)
			self.assertTrue(e1 != e2)
			self.assertFalse(e1 == e2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Effect(val) --> Effect
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestEffect):

	def test_01(self):
		""" create several effects with different values """
		self.assertCreation(132)
		self.assertCreation("string")
		self.assertCreation(32.9)
		self.assertCreation(('asd',12,{}))
		self.assertCreation(None)
		self.assertCreation([1,2,3])

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# effect_1 == effect_2
# Two effects are equal if they have equal values
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestComparison(TestEffect):

	def test_01(self):
		""" assert equal effects """
		self.assertComparison(Effect(1),Effect(1),True)
		self.assertComparison(Effect('zero'),Effect('zero'),True)
		self.assertComparison(Effect([{},None]),Effect([{},None]),True)
		self.assertComparison(Effect((None,1,'yes')),Effect((None,1,'yes')),True)
		self.assertComparison(Effect(False),Effect(False),True)

	def test_02(self):
		""" assert differetn effects """
		self.assertComparison(Effect(1),Effect(3),False)
		self.assertComparison(Effect('zero'),Effect('zro'),False)
		self.assertComparison(Effect([{},None]),Effect([{}]),False)
		self.assertComparison(Effect((None,1,'yes')),Effect((None,0,'yes')),False)
		self.assertComparison(Effect(False),Effect(1),False)
