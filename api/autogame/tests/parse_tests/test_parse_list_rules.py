#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import parser
from context import Rule
from context import Block
from context import Statement
from sample_rules import rule_lotr_head
from sample_rules import rule_lotr_cond
from sample_rules import rule_lotr_actions
from sample_rules import rule_lotr

from .test_parse_rule import TestParseRuleBase


### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse List Rules Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseListRulesBase (TestParseRuleBase):
	pass

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_list_rules(text, fpath)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> parse all the rule definitions in the text from the fpath
# >>> return a list with all the rule definitions parsed
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParseListRules (TestParseListRulesBase):

	def test_v00 (self):
		""" assert parse_list_rules with default arguments """
		result = parser.parse_list_rules()
		self.assertListRules(result,[])

	def test_v01 (self):
		## arrange
		#### function args
		x = 3
		text = "rule:\n" * x
		file = "test_parse_list_rules"
		#### expected result args
		list_rules = []
		for i in range(x):
			list_rules.append(Rule(fpath=file,line=i+1))
		expected = list_rules
		## act
		result = parser.parse_list_rules(text,file)
		## assert
		self.assertListRules(result,expected)

	def test_v02 (self):
		""" parse multiple empty rule definitions with a preconditions body """
		x = 4
		text = "rule:\n\twhen:\n" * x
		file = "test_parse_list_rules"
		list_rules = []
		for i in range(x):
			precs=Block(fpath=file,line=(i*2)+2)
			list_rules.append(Rule(precs=precs,fpath=file,line=(i*2)+1))
		expected = list_rules
		result = parser.parse_list_rules(text,file)
		self.assertListRules(result,expected)

	def test_v03 (self):
		## arrange
		#### function args
		x = 3
		text = "rule:\n\tthen:\n" * x
		file = "test_parse_list_rules"
		#### expected result args
		list_rules = []
		for i in range(x):
			acts=Block(fpath=file,line=(i*2)+2)
			list_rules.append(Rule(acts=acts,fpath=file,line=(i*2)+1))
		expected = list_rules
		## act
		result = parser.parse_list_rules(text,file)
		## assert
		msg = "\nExpected same number of created rules.\n"
		msg +=">>> len(result) = " + str(len(result)) + "\n"
		msg +=">>> len(expected) = " + str(len(expected)) + "\n"
		self.assertEqual(len(result),len(expected),msg)
		self.assertEqual(result,expected)

	def test_v04 (self):
		## arrange
		#### function args
		x = 4
		text = "rule:\n\twhen:\n\tthen:\n" * x
		file = "test_parse_list_rules"
		#### expected result args
		list_rules = []
		for i in range(x):
			p=Block(fpath=file,line=(i*3)+2)
			a=Block(fpath=file,line=(i*3)+3)
			list_rules.append(Rule(precs=p,acts=a,fpath=file,line=(i*3)+1))
		expected = list_rules
		## act
		result = parser.parse_list_rules(text,file)
		## assert
		msg = "\nExpected same number of created rules.\n"
		msg +=">>> len(result) = " + str(len(result)) + "\n"
		msg +=">>> len(expected) = " + str(len(expected)) + "\n"
		self.assertEqual(len(result),len(expected),msg)
		self.assertEqual(result,expected)
