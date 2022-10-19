#!/usr/bin/env python
# -*- coding: utf-8 -*-

class Effect(object):

	def __init__(self,val):
		self.__val = val if not isinstance(val,Effect) else val.val()

	def __eq__(self,other):
		return isinstance(other,Effect) \
		and self.val() == other.val()

	def __ne__(self,other):
		return not self == other

	def __str__(self):
		return str(self.val())

	def __repr__(self):
		return "Effect<%s>" % self

	def val(self):
		return self.__val
