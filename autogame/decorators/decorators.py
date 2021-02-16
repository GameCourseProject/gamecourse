#!/usr/bin/env python
# -*- coding: utf-8 -*-

def rule_effect(func):
	""" decorator that wraps the result of a function in a special Output """
	func.__rule_effect__ = True
	return rule_function(func)

def rule_function(func):
	""" decorator to flag functions to be imported """
	func.__gamerules__ = True
	return func