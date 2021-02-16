#!/usr/bin/env python
# -*- coding: utf-8 -*-

class ParseError(Exception):
	""" Exception if there is an error in a Path given """
	def __init__(self, file=None, line=None, val=None):
		self.file = file
		self.line = line
		self.val = val

	def __str__(self):
		if (self.file
		and len(str(self.file)) > 0
		and self.line):
			error_msg = "Couldn't parse rule in line " \
			+ str(self.line) + ", file: " + str(self.file)
		elif self.file and len(str(self.file)) > 0:
			error_msg = "Couldn't parse rule in file: "\
			+ str(self.file)
		elif self.line:
			error_msg = "Couldn't parse rule in line: "\
			+ str(self.line)
		else:
			error_msg = "Couldn't parse rule"
		if self.val:
			error_msg += "\n" + str(self.val)
		return error_msg

	def __repr__(self):
		return str(self)