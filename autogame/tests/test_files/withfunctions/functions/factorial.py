#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import rule_function

@rule_function
def factorial(n):
	return 1 if n == 0 else n * factorial(n-1)