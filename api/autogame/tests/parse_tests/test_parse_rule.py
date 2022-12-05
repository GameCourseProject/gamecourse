#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import parser
from context import Rule
from context import Block
from context import ParseError

from rule_tests.test_rule import TestRule


### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Rule Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseRuleBase(TestRule):
	
	def assertParseRule(self, text=None, pos=0, fpath=None, line=1,
		name=None, desc=None, precs=None, acts=None,
		after=None, pos_end=None, line_end=None):
		""" assert parse_rule function with a simplified version of arguments
		"""
		if fpath is None:
			from context import testfiles_path
			fpath = testfiles_path
		if text is None:
			text = "rule:"
		if name == None:
			name = ''
		if desc is None:
			desc = ''
		text += " %s\n%s" % (name,desc)
		if precs is None:
			precs = Block([],fpath,line)
		else:
			l = line + text.count('\n')
			precs, txt = self.generatePreconditions(precs,fpath,l)
			text+= txt
		if acts is None:
			acts = Block([],fpath,line)
		else:
			l = line + text.count('\n')
			acts, txt = self.generateActions(acts,fpath,l)
			text+= txt
		rule = Rule(name,desc,precs,acts,fpath,line)
		if after is not None:
			text+= '\n%s' % after
		if pos_end is None:
			pos_end = len(text)
		if line_end is None:
			line_end = line + text.count('\n',pos)
		result = parser.parse_rule(text,pos,fpath,line)
		self.assertResult(result,rule,pos_end,line_end)

	def assertResult (self, result, rule, pos, line):
		""" assert a result of call to parse_rule """
		self.assertIsInstance(result,tuple)
		self.assertIsInstance(rule,Rule)
		self.assertIsInstance(pos,int)
		self.assertIsInstance(line,int)
		self.assertEqual(len(result),3)
		self.assertRule(result[0],rule.name(),rule.desc(),
			rule.precs(),rule.acts(),rule.file(),rule.line())
		self.assertEqual(result[0],rule)
		self.assertEqual(result[1],pos)
		self.assertEqual(result[2],line)


	def generateBlock(self, stmts, name, fpath, line):
		""" generate a named block text and block node based on stmts
		(as string) from the given fpath starting in the given line 
		"""
		text = "\n\t%s:" % name
		line += 1
		start_line = line
		converted = []
		for stmt_text in stmts:
			text += "\n\t\t%s" % stmt_text
			line += 1
			stmt = parser.parse_statement(stmt_text,0,fpath,line)[0]
			converted.append(stmt)
		block = Block(converted,fpath,start_line)
		return block, text

	def generatePreconditions(self, stmts, fpath, line):
		""" generate a preconditions text and preconditions node based on stmts
		(as string) from the given fpath starting in the given line 
		"""
		block, text = self.generateBlock(stmts,'when',fpath,line)
		from context import Preconditions
		return Preconditions(block.stmts(),block.file(),block.line()), text

	def generateActions(self, stmts, fpath, line):
		""" generate an actions text and actions node based on stmts
		(as string) from the given fpath starting in the given line 
		"""
		block, text = self.generateBlock(stmts,'then',fpath,line)
		from context import Actions
		return Actions(block.stmts(),block.file(),block.line()), text

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_rule(text, pos, fpath, line)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> if 'rule' followed by ':' is the next thing in the text starting
# >>> in the given position, it will parse a rule from that position onward
# >>> and stop parsing when the EoF is reached or another rule is found
# >>> returns the Rule parsed, the position in the text were parse_rule stopped
# >>>> and the actual line number that is being parsed in the text
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParseRule (TestParseRuleBase):

	def test_00 (self):
		""" parse empty rule definition """
		self.assertParseRule('rule:')

	def test_01 (self):
		""" parse a rule with just name """
		self.assertParseRule('rule:',name='my rule')

	def test_02 (self):
		""" parse a rule with just descrition """
		self.assertParseRule('rule:',desc='description')

	def test_03 (self):
		""" parse a rule with just preconditions """
		s = ['x=100','y="student"','(x+y-1)%2==0']
		self.assertParseRule(precs=s)

	def test_04 (self):
		""" parse a rule with just actions """
		s = ['y="student"','x=100','(x+y-1)%2==0']
		self.assertParseRule(acts=s)

	def test_05 (self):
		""" parse a rule with all fields """
		self.assertParseRule(
			name='RuleName',
			desc='this is the rule description',
			precs=list('123'),
			acts=list('456'))

	def test_06 (self):
		""" parse a rule with empty fields (precs and acts) """
		self.assertParseRule(
			precs=[],
			acts=[])

	def test_07 (self):
		""" parse a rule with empty fields (precs) """
		self.assertParseRule(
			precs=[])

	def test_08 (self):
		""" parse a rule with empty fields (acts) """
		self.assertParseRule(
			acts=[])

	def test_09 (self):
		""" parse empty rule definitions for all combinations of lower case
		letters and upper case letters of 'rule:'
		"""
		definitions = self.capcombos('rule:')
		for d in definitions:
			self.assertParseRule(d)

	def test_parse_rule_i00 (self):
		# Arrange
		text = ""
		pos = 0
		fpath = "test_parse_rule_i00"
		line = 1

		with self.assertRaises(ParseError) as cm:
			parser.parse_rule(text,pos,fpath,line)

		msg = "Couldn't parse rule in line " + str(line)
		msg +=", file: " + str(fpath) + "\n"
		msg +="parse_rule:: invalid rule definition, expected \'rule\' "
		msg +="in the beggining of the definition"
		self.assertEqual(str(cm.exception),msg)

	def test_parse_rule_i01 (self):
		# Arrange
		text = "rule"
		pos = 0
		fpath = "test_parse_rule_i00"
		line = 1

		with self.assertRaises(ParseError) as cm:
			parser.parse_rule(text,pos,fpath,line)

		msg = "Couldn't parse rule in line " + str(line)
		msg +=", file: " + str(fpath) + "\n"
		msg +="parse_rule:: invalid rule definition, expected \':\' "
		msg +="after \'rule\'"
		self.assertEqual(str(cm.exception),msg)