#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .basenode import BaseNode
from .stmt import Statement
from ..errors import StatementError
from .. import validate

class Block (BaseNode):
	"""
	This class represents objects of type Block
	a block is a set of statements
	"""
	def __init__(self, stmts=None, fpath="", line=1):
		if stmts is None:
			stmts = []
		super(Block,self).__init__(fpath,line)
		self.__stmts = []
		self.add_stmts(stmts)
		self.__alternate_names__()

	def __eq__ (self, other):
		return isinstance(other,Block) \
		and self.stmts() == other.stmts() \
		and super(Block, self).__eq__(other)

	def __ne__ (self, other):
		return not isinstance(other,Block) \
		or self.stmts() != other.stmts() \
		or super(Block, self).__ne__(other)

	def __str__ (self):
		t = type(self).__name__
		msg = "%s in line %d of file '%s':\n" % (t,self.line(), self.path())
		for s in self.stmts():
			msg += "\t%s\n" % s
		return msg

	def __repr__ (self):
		return str(self)

	def stmts(self):
		# return list(self.__stmts) # <------ Safer
		return self.__stmts # <-------------- Efficient

	def add_stmt(self,stmt):
		validate.stmt(stmt,self.file(),self.line())
		self.__stmts.append(stmt)

	def add_stmts(self,stmts):
		validate.stmts(stmts)
		for s in stmts:
			self.add_stmt(s)

	def fire(self, scope=None):
		"""
		Executes all statements in the block. Returns the last return value
		of the last statement execution.
		A scope corresponding to a dictionary with name definitions
		can be passed as argument and will be used at execution time.
		This scope is updated with each execution.
		"""
		if scope is None:
			scope = {}
		result = None # default return value
		# execute all statements defined in the block
		for s in self.stmts():
			result = s.fire(scope)		
		return result

	def to_pickle(self):
		return PickableBlock(self)

	def __alternate_names__ (self):
		""" alternate names for certain methods """
		# Block.stmts(self)
		self.statements = self.stmts
		self.get_statements = self.stmts
		self.get_stmts = self.stmts		
		# Block.fpath(self)
		self.fpath = self.path
		self.location = self.fpath
		self.filepath = self.fpath
		self.file_path = self.fpath


class PickableBlock:

	def __init__(self,block):
		self.stmts = [stmt.to_pickle() for stmt in block.stmts()]
		self.path = block.path()
		self.line = block.line()

	def unpickle(self):
		return Block(
			[stmt.unpickle() for stmt in self.stmts],
			self.path,
			self.line)