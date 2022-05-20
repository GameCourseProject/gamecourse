#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .block import Block
from .effect import Effect

import time
import logging

class Actions(Block):

	def fire(self, scope=None):
		"""
		Executes all statements in the block.
		Returns a tuple with all the effects outputted by the block.  
		A scope corresponding to a dictionary with name definitions
		can be passed as argument and will be used at execution time.
		This scope is updated with each execution.
		"""
		if scope is None:
			scope = {}
		effects = [] # default return value
		# execute all statements defined in the block
		i = 0
		for s in self.stmts():
		    start = time.time()
			result = s.fire(scope)
			
			i +=1
			if isinstance(result,Effect):
				if isinstance(result, list):
					effects = result
				else:
					effects.append(result)

			end = time.time()
			logging.exception(s)
			logging.exception(end-start)
		
		return effects

	def to_pickle(self):
		return PickableActions(self)

class PickableActions:

	def __init__(self,block):
		self.stmts = [stmt.to_pickle() for stmt in block.stmts()]
		self.path = block.path()
		self.line = block.line()

	def unpickle(self):
		return Actions(
			[stmt.unpickle() for stmt in self.stmts],
			self.path,
			self.line)