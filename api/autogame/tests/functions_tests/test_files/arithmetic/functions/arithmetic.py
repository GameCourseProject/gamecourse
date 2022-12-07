#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import rule_function

@rule_function
def add (x,y): return x+y
@rule_function
def sub (x,y): return x-y
@rule_function
def mul (x,y): return x*y
@rule_function
def div (x,y): return x/y
@rule_function
def mod (x,y): return x%y

def factorial(x):
	return 1 if x == 0 else x * factorial(x-1)

class ThisShouldNotBeImported:
	pass

error_var = 10
__this_definitely_shoudnt_be_imported__ = "Oh no..."