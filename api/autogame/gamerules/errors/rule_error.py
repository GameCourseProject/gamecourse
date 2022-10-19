#!/usr/bin/env python
# -*- coding: utf-8 -*-

class RuleError (Exception):

	def __init__(self, filepath=None, line=None, message=None):
		self.set_filepath(filepath)
		self.set_line(line)
		self.set_message(message)

	def __str__(self):
		l = self.line
		f = self.filepath
		m = self.message
		return "in line {} of file '{}':\n{}".format(l,f,m)

	def __repr__(self):
		f = self.filepath; l = self.line
		return "{}<file:{}, line:{}>".format('RuleError',f,l)

	def set_filepath(self,filepath):
		if filepath == None:
			self.filepath = "?"
		else:
			from .. import validate
			validate.filepath(filepath)
			self.filepath = filepath

	def set_line(self,line):
		if line == None:
			self.line = "?"
		else:
			from .. import validate
			validate.line(line)
			self.line = line

	def set_message(self,message):
		if message == None:
			self.message = ''
		elif isinstance(message,str):
			self.message = message
		elif isinstance(message,Exception):
			ex = message
			message = str()
			if hasattr(ex, 'text'):
				message+= "\t%s\n" % ex.text
			if hasattr(ex, 'offset') and ex.offset is not None:
				blank_space = ' ' * (ex.offset-1)
				message+= "\t%s^\n" % blank_space
			if hasattr(ex, 'msg'):
				message+= "%s: %s" % (type(ex).__name__, ex.msg)
			elif hasattr(ex, 'message'):
				message+= "%s: %s" % (type(ex).__name__, ex.message)
			self.message = message
		else:
			msg = "Invalid type for 'message'. "
			msg+= "Expected string or exception received {}"
			msg.format(type(message).__name__)
			raise TypeError(msg)
