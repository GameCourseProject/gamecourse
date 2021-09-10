#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import rule_function

@rule_function
class Human:
	def __init__(self,name):
		self.name = name
	def gender(self):
		return determine_gender(self.name)

even = lambda x: x % 2 == 0
odd = lambda x: x % 2 == 1

def determine_gender(name):
	if even(len(name)) and not name.endswith("o"):
		return "female"
	if name.endswith("a"):
		return "female"
	return "male"
