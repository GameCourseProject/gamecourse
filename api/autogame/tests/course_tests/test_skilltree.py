#!/usr/bin/env python
# -*- coding: utf-8 -*-


from context import course
from course import PreCondition, TreeAward
from base_test_class import BaseTestClass

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestSkillTree(BaseTestClass):

	def assertCreationPreCondition (self,nodes):
		prec = PreCondition(nodes)
		self.assertPreCondition(prec)
		self.assertEqual(len(nodes),len(prec.nodes))
		for node in nodes:
			self.assertIn(node, prec.nodes)

	def assertCreationTreeAward (self,name,lvl,precs,color,xp):
		ta = TreeAward(name,lvl,precs,color,xp)
		self.assertTreeAward(ta)
		self.assertEqual(ta.name,name)
		self.assertEqual(ta.PCs,precs)
		self.assertEqual(ta.level,lvl)
		self.assertEqual(ta.color,color)
		self.assertEqual(ta.xp,xp)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestPreCondtion (TestSkillTree):

	def test_00 (self):
		""" test creation with empty preconditions """
		self.assertCreationPreCondition([])

	def test_01 (self):
		""" creation of preconditions """
		requirements = "eBook+reMIDI"
		self.assertCreationPreCondition(requirements.split("+"))

	def test_02 (self):
		""" test PreCondition.Printable, with regular nodes """
		requirements = "eBook,reMIDI,Reporter,3D GIF,reTrailer"
		pc = PreCondition(requirements.split(","))
		self.assertPreCondition(pc)
		self.assertEqual(pc.Printable(),requirements.replace(","," + "))

	def test_03 (self):
		""" test PreCondition.Printable with empty nodes """
		pc = PreCondition([])
		self.assertPreCondition(pc)
		self.assertEqual(pc.Printable(),"")

	def test_04 (self):
		""" test PreCondition.Satisfied with empty nodes and empty match_nodes """
		pc = PreCondition([])
		self.assertTrue(pc.Satisfied([]))

	def test_05 (self):
		""" test PreCondition.Satisfied with empty nodes and regular match_nodes """
		nodes = []
		match = "eBook,reMIDI,Reporter,3D GIF,reTrailer".split(",")
		pc = PreCondition(nodes)
		self.assertTrue(pc.Satisfied(match))

	def test_06 (self):
		""" test PreCondition.Satisfied with regular nodes and empty match_nodes """
		nodes = "eBook,reMIDI,Reporter,3D GIF,reTrailer".split(",")
		match = []
		pc = PreCondition(nodes)
		self.assertFalse(pc.Satisfied(match))

	def test_07 (self):
		""" test PreCondition.Satisfied with equal nodes and match_nodes """
		nodes = "eBook,reMIDI,Reporter,3D GIF,reTrailer".split(",")
		match = nodes
		pc = PreCondition(nodes)
		self.assertTrue(pc.Satisfied(match))

	def test_08 (self):
		""" test PreCondition.Satisfied with equal nodes and match_nodes """
		nodes = "eBook,reMIDI,Reporter".split(",")
		match = "eBook of legend,reMIDI my Music,Reporter of the unknown".split(",")
		pc = PreCondition(nodes)
		self.assertTrue(pc.Satisfied(match))

class TestTreeAward (TestSkillTree):

	def test_01 (self):
		""" creation of tree award with regular args and no preconditions """
		name = "Reporter"; lvl = 1; color = "#c1004f"; xp = 100
		precs = []
		self.assertCreationTreeAward(name,lvl,precs,color,xp)

	def test_02 (self):
		""" creation of tree award with regular args and preconditions """
		name = "Lip Sync"; lvl = 3; color = "#00a8a8"; xp = 9571
		pc1 = PreCondition(["Fake Speech","Cartoonist"])
		pc2 = PreCondition(["Fake News","Stop Motion"])
		precs = [pc1,pc2]
		self.assertCreationTreeAward(name,lvl,precs,color,xp)

	def test_03 (self):
		""" test TreeAward.Satisfied with no preconditions """
		name = "ABcc11"; lvl = 22; color = "#cf32ac"; xp = 3350
		precs = []
		ta = TreeAward(name,lvl,precs,color,xp)
		self.assertTrue(ta.Satisfied([]))

	def test_04 (self):
		""" test TreeAward.Satisfied with 1 precondition """
		name = "1º1"; lvl = 7442; color = "#cf32ac"; xp = -0xfeed
		precs = [PreCondition(["a1","b2"])]
		ta = TreeAward(name,lvl,precs,color,xp)
		self.assertTrue(ta.Satisfied(["a1","b2"]))

	def test_05 (self):
		""" test TreeAward.Satisfied with 3 preconditions """
		name = "1º1"; lvl = 7442; color = "#cf32ac"; xp = -0xfeed
		pc1 = PreCondition(["a1"])
		pc2 = PreCondition(["b1","b2"])
		precs = [pc1,pc2]
		ta = TreeAward(name,lvl,precs,color,xp)
		self.assertTrue(ta.Satisfied(["b1","b2"]))
		self.assertTrue(ta.Satisfied(["b2","a1"]))
		self.assertFalse(ta.Satisfied(["b1"]))
