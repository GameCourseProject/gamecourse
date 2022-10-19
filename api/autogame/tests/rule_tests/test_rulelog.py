#!/usr/bin/env python
# -*- coding: utf-8 -*-

import time

from base_test_class import BaseTestClass
from context import RuleLog, Rule, Output, Effect

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestRuleLog
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestRuleLog(BaseTestClass):

	def assertCreation(self,rule,target,output):
		""" assert if rulelog creation with certain arguments is valid """
		log = RuleLog(rule,target,output)
		self.assertRuleLog(log)
		self.assertEqual(log.rule(),rule)
		self.assertEqual(log.target(),target)
		self.assertEqual(log.output(),output)
		# to test the timestamp, we create another and check if the timestamp
		# of the log is not higher than the one created now (since its older)
		self.assertLessEqual(log.timestamp(),time.time())

	def assertCreationRaises(self,error,rule,target,output,time=None):
		""" assert if rulelog creation with certain arguments raises exception """
		with self.assertRaises(error):
			if time is None:
				RuleLog(rule,target,output)
			else:
				RuleLog(rule,target,output,time)

	def assertEq(self,log1,log2):
		""" asserts if two rulelogs with certain arguments are Equal """
		self.assertEqual(log1,log2)
		self.assertTrue(log1 == log2)
		self.assertFalse(log1 != log2)

	def assertNotEq(self,log1,log2):
		""" asserts if two rulelogs with certain arguments are Equal """
		self.assertNotEqual(log1,log2)
		self.assertTrue(log1 != log2)
		self.assertFalse(log1 == log2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# RuleLog(val) --> RuleLog
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestRuleLog):

	def test_01(self):
		""" create a rule log with some default arguments """
		rule = Rule()
		target = 'student1'
		output = Output([Effect(i) for i in range(1,21)])
		self.assertCreation(rule,target,output)

	def test_02(self):
		""" test rule creation with invalid rule values """
		target = {'student':73844}
		output = Output([Effect(c) for c in 'randomword'])
		self.assertCreationRaises(TypeError,None,target,output)
		self.assertCreationRaises(TypeError,14,target,output)
		self.assertCreationRaises(TypeError,'rule1',target,output)
		self.assertCreationRaises(TypeError,output,target,output)
		self.assertCreationRaises(TypeError,target,target,output)
		self.assertCreationRaises(TypeError,ValueError,target,output)

	def test_03(self):
		""" test rule creation with invalid output values """
		rule = Rule()
		target = {'student':73844}
		self.assertCreationRaises(TypeError,rule,target,None)
		self.assertCreationRaises(TypeError,rule,target,534)
		self.assertCreationRaises(TypeError,rule,target,'not valid')
		self.assertCreationRaises(TypeError,rule,target,rule)
		self.assertCreationRaises(TypeError,rule,target,target)

	def test_04(self):
		""" test rule creation with invalid time values """
		rule = Rule()
		target = {'student':73844}
		output = Output([Effect(c) for c in 'randomword'])
		self.assertCreationRaises(TypeError,rule,target,output,'1.0')
		self.assertCreationRaises(TypeError,rule,target,output,(23.24,232.32))
		self.assertCreationRaises(TypeError,rule,target,output,output)
		self.assertCreationRaises(TypeError,rule,target,output,Exception)
		self.assertCreationRaises(TypeError,rule,target,output,[(1,2,3),20.3])
		self.assertCreationRaises(ValueError,rule,target,output,-1*time.time())

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Equals(RuleLog1,RuleLog2)
# NotEquals(RuleLog1,RuleLog2)
# Two RuleLogs are equal if they refer the same rule, target, output
# and timestamp
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestComparison(TestRuleLog):

	def test_01(self):
		""" assert equal rule logs """
		l1 = RuleLog(Rule(),'t1',Output([Effect('e1')]))
		l2 = RuleLog(Rule(),'t1',Output([Effect('e1')]),l1.timestamp())
		self.assertEq(l1,l2)

	def test_02(self):
		""" assert rule logs with different rules """
		l1 = RuleLog(Rule('rule2'),'t1',Output([Effect('e1')]))
		l2 = RuleLog(Rule('rule1'),'t1',Output([Effect('e1')]),l1.timestamp())
		self.assertNotEq(l1,l2)

	def test_03(self):
		""" assert rule logs with different rules """
		l1 = RuleLog(Rule(),'t1',Output([Effect('e1')]))
		l2 = RuleLog(Rule(),'t2',Output([Effect('e1')]),l1.timestamp())
		self.assertNotEq(l1,l2)

	def test_04(self):
		""" assert rule logs with different outputs """
		l1 = RuleLog(Rule(),'t1',Output([Effect('e1')]))
		l2 = RuleLog(Rule(),'t1',Output([Effect('e2')]),l1.timestamp())
		self.assertNotEq(l1,l2)

	def test_05(self):
		""" assert rule logs with different timestamps """
		l1 = RuleLog(Rule(),'t1',Output([Effect('e1')]))
		l2 = RuleLog(Rule(),'t1',Output([Effect('e1')]),l1.timestamp()-1)
		self.assertNotEq(l1,l2)
