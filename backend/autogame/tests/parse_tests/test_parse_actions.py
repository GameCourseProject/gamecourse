#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import parser
from context import Block
from context import Actions
from context import Statement
from context import ParseError
from aux_functions import assert_block
from sample_rules import rule_lotr_head
from sample_rules import rule_lotr_actions

from .test_parse_nblock import TestParseNamedBlockBase


### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Actions Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseActionsBase (TestParseNamedBlockBase):

	def assertResult (self, result, block, pos, line, text):
		""" assert the return value to see if it's a valid return value for
		parse_preconditions funtion
		"""
		super(TestParseActionsBase,self).assertResult(
			result,block,pos,line,text)
		self.assertIsInstance(result[0],Actions)

	def assertParseActions(self, stmts, text, endkey='',
		pos_start=0, line_start=1, pos_end=None, line_end=None, fpath=None):
		""" assert 'parse_actions' function with the given inputs """
		if fpath == None:
			from context import testfiles_path
			fpath = testfiles_path
		if pos_end == None:
			if endkey == '':
				pos_end = len(text)
			else:
				pos_end = len(text.split(endkey)[0])
		if line_end == None:
			if endkey == '':
				line_end = line_start + text.count('\n',pos_start)
			else:
				text_aux = text.split(endkey)[0]
				line_end = line_start + text_aux.count('\n',pos_start)
		result = parser.parse_actions(text,pos_start,fpath,line_start)
		stmts = self.convertStatements(stmts,fpath,line_start+1)
		block = Actions(stmts,fpath,line_start)
		self.assertResult(result,block,pos_end,line_end,text)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_actions(text, pos, fpath, line)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> if 'then' followed by ':' is the next thing in the text starting in the
# >>> given position, it will parse a preconditions block from that position
# >>> and stop parsing when the EoF is reached or if 'rule' keyword is found
# >>> returns an ActionsBlock (with all the statements parsed till the stop 
# >>> condition is met), the position in the text were the parse_block stopped 
# >>> and the actual line number that is being parsed in the text
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParseActions (TestParseActionsBase):

	def test_v01 (self):
		""" test parse_actions with default arguments (omitted) """
		self.assertParseActions([],'then:')

	def test_v02 (self):
		""" test parse_actions with whitespace in text """
		self.assertParseActions([],'then:\n 	\t\n\n\n\n  \n')

	def test_v03 (self):
		""" test parse_actions with expressions in text """
		self.assertParseActions(list('123'),'then:\n1\n2\n3\n')

	# def test_parse_actions_001(self):
	# 	# Input
	# 	f = "some_file.txt"
	# 	b = Block([],f)
	# 	t = "\tthen:\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("x = 2",f,l)
	# 	b.add_stmt(s)
	# 	t += "\t\tx = 2\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("y = x**2",f,l)
	# 	b.add_stmt(s)
	# 	t += "\t\ty = x**2\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("y == x << 1",f,l)
	# 	b.add_stmt(s)
	# 	t += "\t\ty == x << 1\n"
	# 	# Expected Output
	# 	pos = len(t)
	# 	line = 1 + t.count("\n")
	# 	expected = (b, pos, line)
	# 	stmts = []
	# 	for i in b.stmts():
	# 		stmts.append(i)
	# 	# Assertion
	# 	self.assertEqual(parser.parse_actions(t),expected)
	# 	assert_block(self,expected[0],stmts,f)

	# def test_parse_actions_002(self):
	# 	# Input
	# 	f = "some_file.txt"
	# 	b = Block([],f)
	# 	t = "then:\n"
		
	# 	l = 1 + t.count("\n")
	# 	s = Statement("a = \"something\"",f,l)
	# 	b.add_stmt(s)
	# 	t += "a = \"something\"\n"

	# 	# Expected Output
	# 	pos = len(t)
	# 	line = 1 + t.count("\n")
	# 	expected = (b,pos,line)
	# 	stmts = []
	# 	for i in b.stmts():
	# 		stmts.append(i)
	# 	# Assertion
	# 	self.assertEqual(parser.parse_actions(text=t),expected)
	# 	assert_block(self,expected[0],stmts,f)
	
	# def test_parse_actions_003(self):
	# 	# Input
	# 	f = "some_file.txt"
	# 	b = Block([],f)
	# 	t = "then:\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("l1 = [1,2,3]",f,l)
	# 	b.add_stmt(s)
	# 	t += "l1 = [1,2,3]\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("s1 = 2",f,l)
	# 	b.add_stmt(s)
	# 	t += "s1 = 2\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("s1 in l1",f,l)
	# 	b.add_stmt(s)
	# 	t += "s1 in l1\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("\"invalid_student_id\" not in l1",f,l)
	# 	b.add_stmt(s)
	# 	t += "\"invalid_student_id\" not in l1\n"

	# 	# Expected Output
	# 	pos = len(t)
	# 	line = 1 + t.count("\n")
	# 	expected = (b,pos,line)
	# 	stmts = []
	# 	for i in b.stmts():
	# 		stmts.append(i)
	# 	# Assertion
	# 	self.assertEqual(parser.parse_actions(text=t),expected)
	# 	assert_block(self,expected[0],stmts,f)
	
	# def test_parse_actions_004(self):
	# 	# Input
	# 	f = "some_file.txt"
	# 	b = Block([],f)
	# 	blank = "  	 	 	  \n\n\n\n\r\f\v   		"
	# 	t = blank + "then:\n" + blank

	# 	l = 1 + t.count("\n")
	# 	s = Statement("_valid_ = 5505 % 5",f,l)
	# 	b.add_stmt(s)
	# 	t += "_valid_ = 5505 % 5\n"

	# 	l = 1 + t.count("\n")
	# 	s = Statement("1",f,l)
	# 	b.add_stmt(s)
	# 	t += "1\n" + blank

	# 	# Expected Output
	# 	pos = len(t)
	# 	line = 1 + t.count("\n")
	# 	expected = (b,pos,line)
	# 	stmts = []
	# 	for i in b.stmts():
	# 		stmts.append(i)
	# 	# Assertion
	# 	self.assertEqual(parser.parse_actions(text=t),expected)
	# 	assert_block(self,expected[0],stmts,f)
	
	# def test_parse_actions_005(self):
	# 	# Input
	# 	f = "some_file.txt"
	# 	t = rule_lotr_actions + rule_lotr_head
	# 	s1 = Statement("middle_earth_is_doomed = True",f,3)
	# 	s2 = Statement("sauron and the_one_ring",f,4)
	# 	s3 = Statement("middle_earth_is_doomed is True",f,5)
	# 	b = Block([s1,s2,s3],f)

	# 	# Expected Output
	# 	pos = len(rule_lotr_actions)
	# 	line = 1 + t.count("\n")
	# 	expected = (b,pos,line)
	# 	stmts = []
	# 	for i in b.stmts():
	# 		stmts.append(i)
	# 	# Assertion
	# 	self.assertEqual(parser.parse_actions(text=t),expected)
	# 	assert_block(self,expected[0],stmts,f)
	
	# def test_parse_actions_006(self):
	# 	# Input
	# 	f = "some_file.txt"
	# 	t = rule_lotr_actions + rule_lotr_head
	# 	s0 = Statement("sauron = \"Master of the One Ring\"",f,2)
	# 	s1 = Statement("middle_earth_is_doomed = True",f,3)
	# 	s2 = Statement("sauron and the_one_ring",f,4)
	# 	s3 = Statement("middle_earth_is_doomed is True",f,5)
	# 	b = Block([s0,s1,s2,s3],f)

	# 	# Expected Output
	# 	pos = len(rule_lotr_actions)
	# 	line = 1 + t.count("\n")
	# 	expected = (b,pos,line)
	# 	# Assertion
	# 	self.assertEqual(parser.parse_actions(text=t,stmts=[s0]),expected)
	# 	assert_block(self,expected[0],b.stmts(),f)

	def test_parse_actions_007(self):
		from .parser_aux import capcombos
		definitions = capcombos('then:')
		for d in definitions:
			expected = (Block(),len('then:'),1)
			result = parser.parse_actions(text=d)
			self.assertEqual(result,expected)
			assert_block(self,result[0],[],"")

	def test_parse_actions_i01(self):
		t = "what? is this even valid"
		with self.assertRaises(ParseError) as cm:
			parser.parse_actions(t)
		msg = "parse_named_block:: Expected \'then\'"
		self.assertEqual(cm.exception.val,msg)