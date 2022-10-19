#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .basenode import BaseNode
from .preconditions import Preconditions
from .actions import Actions
from .output import Output
from .. import validate

class Rule (BaseNode):

	def __init__(self, name='',desc='',precs=None,acts=None,fpath="",line=1):
		if precs is None:
			precs = Preconditions([],fpath,line)
		if acts is None:
			acts = Actions([],fpath,line)
		super(Rule,self).__init__(fpath,line)
		validate.rule_args(name,desc,precs,acts,fpath,line)
		self.__name = name
		self.__description = desc
		if not isinstance(precs, Preconditions):
			precs = Preconditions(precs.stmts(),precs.file(),precs.line())
		self.__preconditions = precs
		if not isinstance(acts, Actions):
			acts = Actions(acts.stmts(),acts.file(),acts.line())
		self.__actions = acts
		self.__targets = {}
		self.__init_alternatenames()

	def __eq__(self, other):
		return isinstance(other,Rule)\
			and self.name() == other.name() \
			and self.desc() == other.desc() \
			and self.preconditions() == other.preconditions() \
			and self.actions() == other.actions()

	def __ne__ (self, other):
		return not self == other

	def __str__(self):
		to_print = "rule: " + self.name() + "\n"
		to_print+= self.description()
		if len(self.description()) > 0:
			to_print += "\n"
		to_print+= "\twhen:\n"
		to_print+= str(self.preconditions())
		to_print+= "\tthen:\n"
		to_print+= str(self.actions())
		return to_print

	def __repr__(self):
		import os
		keys = self.name(), self.line(), os.path.basename(self.path())
		return "Rule<%s> in line %d of file '%s'" % keys

	def __hash__(self):
		return id(self)

	def name (self):
		return self.__name

	def description (self):
		return self.__description

	def preconditions (self):
		return self.__preconditions

	def actions (self):
		return self.__actions

	def targets (self):
		return list(self.__targets.keys())

	def target_output (self,target):
		return self.__targets[target]

	def fire(self, target=None, scope=None):
		"""	Executes the rule preconditions,
		if they're valid ... executes the actions!
		"""
		if not isinstance(scope,dict):
			scope = {}
		else:
			scope = dict(scope) # preserve the original scope

		scope["target"] = target
		scope["this"] = target

		result = self.preconditions().fire(scope)
		if result is True:
			result = Output(self.actions().fire(scope))
			if target is not None:
				self.__targets[target] = result
		elif target in self.__targets:
			self.__targets.pop(target)
		return result

	def to_pickle(self):
		return PickableRule(self)

	def __init_alternatenames (self):
		self.desc = self.description
		self.precs = self.preconditions
		self.acts = self.actions


class PickableRule:
	def __init__(self,rule):
		self.name = rule.name()
		self.desc = rule.desc()
		self.precs = rule.precs().to_pickle()
		self.acts = rule.acts().to_pickle()
		self.path = rule.path()
		self.line = rule.line()

	def unpickle(self):
		return Rule(
			self.name,
			self.desc,
			self.precs.unpickle(),
			self.acts.unpickle(),
			self.path,
			self.line)
