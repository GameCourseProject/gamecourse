#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import TargetData
from context import Rule, Output, Effect

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestPathData
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestTargetData (BaseTestClass):

	def assertAdd(self,target,rule,output,output_data=None):
		""" Asserts if the method TargetData.add(target,rule,output) is valid
		for the given arguments """
		if output_data is None:
			output_data = TargetData()
		od = output_data
		od.add(target,rule,output)
		rkey = od.rule_key(rule)
		self.assertTargetData(od)
		self.assertIn(target,od.targets())
		self.assertIn(rkey,od.target_rules(target))
		self.assertIn(output,od.outputs())
		self.assertIn(output,od.target_outputs(target))
		self.assertEqual(output,od.target_ruleoutput(target,rkey))


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation: tests related to obj creation/initialization
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestTargetData):

	def test_01(self):
		""" test TargetData creation with no arguments """
		self.assertTargetData(TargetData())

	def test_02(self):
		""" test TargetData creation with valid arguments """
		tro = {}
		for i in range(4):
			target = "target%d" % i
			tro[target] = {}
			for j in range(i):
				rule = Rule("rule%d" % j)
				tro[target][rule] = self.generate_output((j+i+1) % 5)
		self.assertTargetData(TargetData(tro))

	def test_03(self):
		""" test TargetData creation with invalid argument structure """
		tro = {"target1":[Rule(),self.generate_output(1)]}
		with self.assertRaises(TypeError):
			TargetData(tro)

	def test_04(self):
		""" test TargetData creation with invalid argument structure """
		tro = {"target1":Exception("Kaboom")}
		with self.assertRaises(TypeError):
			TargetData(tro)

	def test_05(self):
		""" test TargetData creation with invalid argument structure """
		tro = {"target1":{(Rule("1"),Rule("2")):self.generate_output(3)}}
		with self.assertRaises(TypeError):
			TargetData(tro)

	def test_06(self):
		""" test TargetData creation with invalid argument structure """
		tro = {"target1":{Rule("1"):[self.generate_output(3)]}}
		with self.assertRaises(TypeError):
			TargetData(tro)

	def test_07 (self):
		""" test TargetData creation with invalid argument """
		with self.assertRaises(Exception):
			TargetData(Exception)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Add: tests that verify elements are added correctly to the structure
# TargetData(target,rule,ouput)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAdd(TestTargetData):

	def test_01(self):
		""" add a valid target, rule and output to an empty TargetData """
		self.assertAdd("target1",Rule("MyRule"),self.generate_output(5))

	def test_02(self):
		""" replace an output from a previous target """
		t = "t2"; r = Rule("r2")
		t1 = self.generate_output(1)
		t2 = self.generate_output(2)
		td = TargetData()
		self.assertAdd(t,r,t1,td)
		self.assertAdd(t,r,t2,td)
		self.assertNotIn(t1,td.outputs())
		self.assertNotIn(t1,td.target_outputs(t))
		self.assertNotEqual(t1,td.target_ruleoutput(t,r))

	def test_03(self):
		""" try to add invalid rule in the TargetData """
		self.assertRaises(TypeError,TargetData().add,"t1","invalid",self.generate_output(1))

	def test_04(self):
		""" try to add invalid output in the TargetData """
		self.assertRaises(TypeError,TargetData().add,"t1",Rule(),"invalid")

	def test_05(self):
		""" test if the 'add' method doesn't add an output to a target from the
		same rule """
		o1 = self.generate_output(2)
		o2 = self.generate_output(2)
		self.assertEqual(o1,o2)
		t = "target"; r = Rule("The One Rule")
		td = TargetData()
		td.add(t,r,o1)
		td.add(t,r,o2)
		self.assertIs(td.target_ruleoutput(t,r),o1)
		self.assertEqual(td.target_ruleoutput(t,r),o2)
		self.assertIsNot(td.target_ruleoutput(t,r),o2)
		self.assertTargetData(td)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# remove: tests that verify if certain element is successfully removed
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestRemove(TestTargetData):

	@classmethod
	def setUpClass(cls):
		cls.tro = {}
		for i in range(1,5):
			target = "target%d" % i
			cls.tro[target] = {}
			for j in range(1,i+1):
				rule = Rule("rule%d" % j)
				cls.tro[target][rule] = None

	def setUp(self):
		td = dict(self.tro)
		i = 0
		for target in list(td.keys()):
			i+=1
			for rule in list(td[target].keys()):
				td[target][rule] = self.generate_output(i)
		self.td = TargetData(td)

	def tearDown(self):
		self.td = None

class TestRemoveTarget(TestRemove):

	def test_01(self):
		""" tries to remove a target that doesn't exist """
		self.assertRaises(ValueError,self.td.rm_target,"ghost-target")

	def test_02(self):
		""" removes a target and all its associations in the TargetData """
		target = "target2"
		rules = list(self.td.target_rules(target))
		self.td.rm_target(target)
		self.assertTargetData(self.td)
		for rule in rules:
			self.assertNotIn((target,rule), list(self.td._tro.keys()))
		self.assertNotIn(target, list(self.td._to.keys()))
		self.assertNotIn(target, list(self.td._tr.keys()))
		self.assertNotIn(target, self.td.targets())

class TestRemoveRule(TestRemove):

	def test_01(self):
		""" tries to remove an invalid rule """
		self.assertRaises(TypeError,self.td.rm_rule,"invalid rule")

	def test_02(self):
		""" tries to remove a rule that doesn't exist """
		self.assertRaises(ValueError,self.td.rm_rule,Rule("phantom"))

	def test_03(self):
		""" removes a rule and all its associations in a TargetData """
		rule = Rule("rule4")
		rkey = self.td.rule_key(rule)
		count_to = {}
		to_instances = {}
		for t in self.td.targets():
			count_to[t] = len(self.td.target_outputs(t))
			if t in self.td.rule_targets(rule):
				o = self.td._tro[(t,rkey)]
				count = self.td.target_outputs(t).count(o)
				to_instances[t] = o, count
		self.td.rm_rule(rule)
		self.assertTargetData(self.td)
		for target in self.td.targets():
			if target in self.td.rule_targets(rule):
				count = self.td.target_outputs(t).count(to_instances[t][0])
				# asserting if the number of the output instances has decreased
				self.assertEqual(count,to_instances[t][1]-1)
				# if the rule was associated to the target then the number of
				# outputs should've decrease by 1
				self.assertEqual(len(self.td.target_outputs(t)),count_to[t]-1)
			else:
				# otherwise it should remain the same
				self.assertEqual(len(self.td.target_outputs(t)),count_to[t])
			self.assertRaises(KeyError,self.td.target_ruleoutput,target,rkey)
			self.assertNotIn(rkey,self.td.target_rules(target))

class TestRemoveTargetOutput(TestRemove):

	def test_01(self):
		""" tries to remove an output from an invalid rule """
		self.assertRaises(TypeError,self.td.rm_target_output,
			"target1",Exception())

	def test_02(self):
		""" tries to remove an output from an from a valid rule activated by a
		non-existent target
		"""
		self.assertRaises(ValueError,self.td.rm_target_output,
			"ghost-target",Rule("rule1"))

	def test_03(self):
		""" tries to remove an output from an non-existen rule activated by a
		valid target """
		self.assertRaises(ValueError,self.td.rm_target_output,
			"target5",Rule("rule3"))

	def test_04(self):
		""" removes an output from a rule activated by a target """
		target = "target1"
		rule = Rule("rule1")
		output = self.td.target_ruleoutput(target,rule)
		num_target_outputs = len(self.td.target_outputs(target))
		self.td.rm_target_output(target,rule)
		self.assertTargetData(self.td)
		self.assertEqual(num_target_outputs-1,len(self.td.target_outputs(target)))
		self.assertNotIn(rule,self.td.target_rules(target))
		self.assertRaises(KeyError,self.td.target_ruleoutput,target,output)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Predicates: rule_exists(rule), target_exists(target),
# target_hasrule(target,rule)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestPredicates(TestTargetData):

	def test_01 (self):
		""" test rule_exists(rule): add a rule to the system and check for its
		existance
		"""
		rule = Rule("MyRule")
		td = TargetData()
		self.assertFalse(td.rule_exists(rule))
		td.add("t1",rule,Output([Effect(1)]))
		self.assertTrue(td.rule_exists(rule))

	def test_02 (self):
		""" test target_exists(target): add a target to the system and check for
		its existance
		"""
		target = "MyTarget"
		td = TargetData()
		self.assertFalse(td.target_exists(target))
		td.add(target,Rule("MyRule"),Output([Effect(1)]))
		self.assertTrue(td.target_exists(target))

	def test_03 (self):
		""" test target_hasrule(target): add a target_rule to the system and
		check if the target has a connection to the rule in target_rule while
		not having connection with the other rule
		"""
		t1 = "Target1"
		r1 = Rule("Rule1")
		r2 = Rule("Rule2")
		td = TargetData()
		self.assertRaises(KeyError,td.target_hasrule,t1,r1)
		td.add(t1,r1,Output([Effect(1)]))
		self.assertTrue(td.target_hasrule(t1,r1))
		self.assertFalse(td.target_hasrule(t1,r2))
