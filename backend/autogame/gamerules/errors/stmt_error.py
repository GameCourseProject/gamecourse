#!/usr/bin/env python
# -*- coding: utf-8 -*-

class StatementError(Exception):
	def __init__(self, file=None, line=None, val=None):
		self.file = file
		self.line = line
		self.val = val

	def __str__(self):
		msg = "Invalid Statement(file: "
		if self.file:
			msg += str(self.file)
		else:
			msg += "???"
		msg += ", line: "
		if self.line:
			msg += str(self.line)
		else:
			msg += "???"
		msg += ")"
		if self.val:
			msg += ": " + str(self.val)
		return msg
	
	def __repr__(self):
		return str(self)