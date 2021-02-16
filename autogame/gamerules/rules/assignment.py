#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .stmt import Statement

from ..errors import RuleError

class Assignment (Statement):
	"""
	This class models the Assignment Node. It corresponds to an Assignment
	Statement, composed of a left value followed by an assignment operator
	followed by an Expression.
	"""

	def __init__(self, text, fpath="", line=1):
		""" Creates and validates an Assignment Node """
		super(Assignment, self).__init__(text, fpath, line)

	def validate (self):
		"""
		Checks if the code in the text is a valid Python assignment,
		if so, the code is compiled and converted into a code object
		ready to be executed. Returns RuleError if any Error is found
		"""
		try:
			code = compile(self.text(),'<string>','exec')
		except Exception as ex:
			ex.text = self.text()
			raise RuleError(self.path(), self.line(), ex)
		else:
			if self.has_assignment_operator():
				self.set_code(code)
				return
			ex = SyntaxError("missing assignment operator")
			msg = "\t%s\n%s: %s" % (self.text(), type(ex).__name__, str(ex))
			raise RuleError(self.path(), self.line(), msg)

	def has_assignment_operator(self):
		"""
		Returns true if there is a valid assignment operator in the text
		false, otherwise
		"""
		index = self.text().find('=')
		if index < 1:
			return False

		invalid_operators = "!><"
		prev_char = self.text()[index-1]
		next_char = self.text()[index+1]
		
		return prev_char not in invalid_operators \
		and next_char is not '='

	def to_pickle(self):
		return PickableAssignment(self)

class PickableAssignment:

	def __init__(self,stmt):
		self.text = stmt.text()
		self.path = stmt.path()
		self.line = stmt.line()

	def unpickle(self):
		return Assignment(self.text, self.path, self.line)