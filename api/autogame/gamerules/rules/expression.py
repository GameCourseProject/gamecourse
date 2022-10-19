#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .stmt import Statement
from ..errors import RuleError
from .. import validate as _validate
import sys

class Expression (Statement):
	"""
	This class models the Expression Node. It corresponds to an Expression
	Statement and follows the same syntax as any valid Python Expression.
	"""

	def __init__(self, text, fpath="", line=1):
		""" Creates and validates an Expression Node """
		super(Expression, self).__init__(text, fpath, line)

	def validate (self):
		"""
		Checks if the code in the text correponds to a valid Python Expression
		if so, the code is compiled and converted to a code object ready to be
		evaluated. Returns RuleError if any Error is found.
		"""
		try:
			code = compile(self.text(),'<string>','eval')
		except Exception as ex:
			raise RuleError(self.path(), self.line(), ex)
		else:
			self.set_code(code)

	def fire (self, scope=None):
		"""
		Evaluates the Expression code. Returns a tuple with the result and
		the updated scope if there isn't any error, otherwise returns a
		RuleError with the error details
		"""
		if scope is None:
			scope = dict()
		_validate.scope(scope)

		try:
			result = eval(self.code(), scope)
		except Exception as ex:
			ex.text = self.text()
			raise RuleError(self.path(), self.line(), ex)

		return result

	def to_pickle(self):
		return PickableExpression(self)

class PickableExpression:

	def __init__(self,stmt):
		self.text = stmt.text()
		self.path = stmt.path()
		self.line = stmt.line()

	def unpickle(self):
		return Expression(self.text, self.path, self.line)
