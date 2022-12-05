#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .coursefunctions import substringin

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# TreeAward Class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# This class modules the concept of an 'skill from the skill tree', which is
# something players can achieve during the PCM "game". Skills have a name that
# identifies it, a level, corresponding to the level of the skill tree where
# this skill is located, a color, just for design, XP, which players earn once
# they made a successful attempt to achieve the skill. Skills can have 0 or more
# preconditions. Each precondition is corresponds to another skill or skills
# that must be achieved first in order to unlock this.
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TreeAward:

	def __init__(self,name,level,pcs,color,xp):
		self.name = name
		self.level = level
		self.PCs = pcs
		self.color = color
		self.xp = xp

	def __str__(self):
		return "TreeAward("+self.name+", "+str(self.level)+", "+str(self.PCs)+")"

	def __repr__(self):
		return str(self)

	def Satisfied(self,prevnodes):
		"""
		a TreeAward is satisfied if at least one of its PreConditions is
		satisfied. If a TreeAward is satisfied, it means the skill is unlocked
		and can be achieved with some meaningfull contribution.
		"""
		if len(self.PCs)==0:
			return True
		for pc in self.PCs:
			# if any of the preconditions is fulfilled, than this TreeAward
			# (skill) is unlocked
			if pc.Satisfied(prevnodes):
				return True
		return False


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Precondition Class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# This class modules the concept of 'preconditions of skills from the skill
# tree', that consists in a set of nodes. These nodes are usually a list of
# strings, each string is skill name (<TreeAward-obj>.name) that is required for
# some TreeAward to be unlocked.
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class PreCondition:
	
	def __init__(self,nodes):
		self.nodes=nodes
	
	def __str__(self):
		return "PC(%s)" % (self.nodes)
	
	def __repr__(self):
		return str(self)
	
	def Printable(self):
		"""
		Returns a string with all the nodes of the precondition
		separated with a '+'
		"""
		tmp=""
		if self.nodes:
			tmp=" + ".join(self.nodes)
		return tmp
	
	def Satisfied(self,prevnodes):
		""" PREDICATE:
		>>> returns TRUE if all the nodes of the nodes PreCondition can be found
		in the given nodes
		>>> returns FALSE if at least of the nodes of the PreCondition is not in
		the given nodes
		"""
		for node in self.nodes:
			if not substringin(node,prevnodes):
				return False
		return True