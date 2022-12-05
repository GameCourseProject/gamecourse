#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .basenode import BaseNode
from ..errors import RuleError
from .. import validate


class Statement(BaseNode):
	"""
	This class models a Statement Node.
	Statements are any valid unit of code that can be executed
	ending with a newline or a semi-colon.
	"""

	def __init__(self, text="", fpath="", line=1):
		""" Creates and validates a Statement Node """
		validate.stmt_txt(text)
		self.__text = text
		super(Statement,self).__init__(fpath,line)
		self.validate()

	def __eq__(self,other):
		return isinstance(other, Statement) \
		and self.text() == other.text() \
		and super(Statement, self).__eq__(other)

	def __ne__(self,other):
		return not isinstance(other, Statement) \
		or self.text() != other.text() \
		or super(Statement, self).__ne__(other)

	def __str__ (self):
		msg = "%s<%s> " % (type(self).__name__, self.text())
		msg+= "in line %d of file: '%s'" % (self.line(), self.path())
		return msg

	def __repr__ (self):
		return str(self)

	def text (self):
		return self.__text

	def code (self):
		return self.__code

	def set_code(self, code):
		self.__code = code

	def validate (self):
		"""
		Checks if the code in the text correponds to a valid Python Statement,
		if so, the code is compiled and converted to a code object ready to be
		executed. Returns RuleError if any Error is found.
		"""
		try:
			code = compile(self.text(), self.path(), 'exec')
		except Exception as ex:
			ex.text = self.text()
			raise RuleError(self.path(), self.line(), ex)
		else:
			self.set_code(code)

	def fire(self, scope=None):
		"""
		Executes the statement and returns a tuple
		with the result and updated scope
		"""
		if scope is None:
			scope = {} # the default scope is an empty dictionary
		validate.scope(scope)
		try:
			#scope['__builtins__'] = None
			exec(self.code(), scope)
		except Exception as ex:
			ex.text = self.text()
			raise RuleError(self.path(), self.line(), ex)
		return True

	def to_pickle(self):
		return PickableStatement(self)

class PickableStatement:

	def __init__(self,stmt):
		self.text = stmt.text()
		self.path = stmt.path()
		self.line = stmt.line()

	def unpickle(self):
		return Statement(self.text, self.path, self.line)