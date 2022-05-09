#!/usr/bin/env python
# -*- coding: utf-8 -*-

from . import validate
from . import namespace

from .rules import RuleLog, Output
from .functions import utils

import os, sys, logging


class RuleSystem:
	def __init__(self, path=None, autosave=True):
		from .data import DataManager
		self.__data__ = DataManager(autosave)
		if path is not None:
			self.load(path)
		utils.import_gamefunctions(self.__data__.functions)

	def path(self):
		return self.__data__.active_path
	
	def rules(self):
		return self.__data__.rules

	def load(self, path):
		""" Load the rules from the given path """
		self.__data__.load(path)

	def fire(self, targets=None, facts=None, scope=None):
		"""	Execute all rules for each student """
		if self.rules() is None: return []
		if targets is None: targets = [None]
		if not isinstance(scope,dict): scope = {}
		scope.update(self.__data__.functions)
		scope["targets"] = targets
		scope["facts"] = facts
		# altering the effect of eval and compile so the rules don't do
		# unwanted operations
		def f(*args, **kwargs): pass
		scope["eval"] = f
		scope["compile"] = f
		rulelogs = []
		namespace.rule_system = self
		namespace.targets = targets

		for target in targets:
			for rule in self.rules():
				namespace.target = target
				output = rule.fire(target,scope)
				if output is False:
					# remove the last target output from the target (if it exists)
					# EAFP: "EASIER to ASK for FORGIVENESS than PERMISSION"
					try:
						if self.__data__.target_data.target_hasrule(target,rule):
							self.__data__.target_data.rm_target_output(target,rule)
					except ValueError: pass # unknown target or rule
					except KeyError: pass # unknown target
				else:
					self.__data__.target_data.add(target,rule,output)
				rulelogs.append(RuleLog(rule,target,output))
		# save state and write the new logs to a file
		if self.__data__.autosave:
			self.__data__.store_logs(rulelogs)
			self.__data__.save_state()
		return self.__data__.target_data.get_target_data()

	def save(self):
		""" saves the current state of the rulesystem in a persistent file """
		self.__data__.save()