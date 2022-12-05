#!/usr/bin/env python
# -*- coding: utf-8 -*-

import time
from .. import validate

class RuleLog(object):
	
	def __init__(self,rule,target,output,ts=None):
		validate.rulelog_args(rule,output)
		self.__rule = rule
		self.__target = target
		self.__output = output
		if ts is None:
			self.__timestamp = time.time()
		else:
			validate.timestamp(ts)
			self.__timestamp = ts

	def __eq__(self,other):
		return isinstance(other,RuleLog) \
		and self.rule() == other.rule() \
		and self.target() == other.target() \
		and self.output() == other.output() \
		and self.timestamp() == other.timestamp()

	def __ne__(self,other):
		return not self == other

	def __str__(self):
		msg = "%s, " % self.rule().name()
		msg+= "%s, " % self.target()
		msg+= "%s, " % self.timestamp()
		msg+= "%s" % self.output()
		return msg

	def __repr__ (self):
		msg = "rule<%s>, " % self.rule().name()
		msg+= "target<%s>, " % self.target()
		msg+= "time<%s>, " % self.timestamp()
		msg+= "output<%s>" % self.output()
		return "RuleLog: " + msg

	def rule(self):
		return self.__rule

	def target(self):
		return self.__target

	def output(self):
		return self.__output

	def timestamp(self):
		return self.__timestamp