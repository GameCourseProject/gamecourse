#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .block import Block

class Preconditions(Block):

	def fire (self, scope=None):
		"""
		Executes all statements in the block.
		If at least one statement in the block returns False
		the returned result is False. Otherwise returns True.
		A scope corresponding to a dictionary with name definitions
		can be passed as argument and will be used at execution time.
		This scope is updated with each execution.
		"""
		if scope is None:
			scope = {}
		return_val = True # the default value
		# execute all statements defined in the block
		for s in self.stmts():
			result = s.fire(scope)
			# if at least one statement returns 'False'
			# the result should ALWAYS return 'False'
			if result is False and return_val is True:
				return_val = False

		return return_val

	def to_pickle(self):
		return PickablePreconditions(self)

class PickablePreconditions:

	def __init__(self,block):
		self.stmts = [stmt.to_pickle() for stmt in block.stmts()]
		self.path = block.path()
		self.line = block.line()

	def unpickle(self):
		return Preconditions(
			[stmt.unpickle() for stmt in self.stmts],
			self.path,
			self.line)
