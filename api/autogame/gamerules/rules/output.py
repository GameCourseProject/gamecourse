#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .. import validate

class Output(object):
	
	def __init__(self,effects):
		validate.effects(effects)
		self.__effects = effects

	def __eq__(self,other):
		return isinstance(other,Output) \
		and self.effects() == other.effects()

	def __ne__(self,other):
		return not self == other

	def __str__(self):
		return str([e.val() for e in self.effects()])

	def __repr__(self):
		return "Output<" + str(self.effects()) + ">"

	def __hash__(self):
		return id(self)

	def effects (self):
		return self.__effects

	def convert (self):
		return [effect.val() for effect in self.effects()]