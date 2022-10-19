#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import parser
from context import Preconditions
from context import ParseError

from .test_parse_nblock import TestParseNamedBlockBase

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Preconditions Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParsePreconditionsBase (TestParseNamedBlockBase):

	def assertResult (self, result, block, pos, line, text):
		""" assert the return value to see if it's a valid return value for
		parse_preconditions funtion
		"""
		super(TestParsePreconditionsBase,self).assertResult(
			result,block,pos,line,text)
		self.assertIsInstance(result[0],Preconditions)

	def assertParsePreconditions(self, stmts, text, endkey='',
		pos_start=0, line_start=1, pos_end=None, line_end=None, fpath=None):
		""" assert 'parse_preconditions' function with the given inputs """
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
		result = parser.parse_preconditions(text,pos_start,fpath,line_start)
		stmts = self.convertStatements(stmts,fpath,line_start+1)
		block = Preconditions(stmts,fpath,line_start)
		self.assertResult(result,block,pos_end,line_end,text)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_preconditions(text, pos, fpath, line)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> if 'when' followed by ':' is the next thing in the text starting in the
# >>> given position, it will parse a preconditions block from that position
# >>> and stop parsing when the EoF is reached or if 'then' or 'rule' keywords
# >>> are found
# >>> returns a PreconditionsBlock (with all the statements parsed till the stop
# >>> condition is met), the position in the text were the parse_block stopped
# >>> and then actual line number that is being parsed in the text
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParsePreconditions (TestParsePreconditionsBase):

	def test_v01 (self):
		""" test parse_preconditions with default arguments (omitted) """
		self.assertParsePreconditions([],'when:')

	def test_v02 (self):
		""" test parse_preconditions with whitespace in text """
		self.assertParsePreconditions([],'when:\n 	\t\n\n\n\n  \n')

	def test_v03 (self):
		""" test parse_preconditions with expressions in text """
		self.assertParsePreconditions(list('123'),'when:\n1\n2\n3\n')

	# def test_parse_preconditions_002(self):
	# 	# Input
	# 	f = "preconditions file is now.txt"
	# 	t = "when:\n"
	# 	b = Block([],f)

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
	# 	self.assertEqual(parser.parse_preconditions(text=t),expected)
	# 	assert_block(self,expected[0],stmts,f)

	# def test_parse_preconditions_003(self):
	# 	# Input
	# 	f = "preconditions file is now.txt"
	# 	t = "when:\n"
	# 	b = Block([],f)

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
	# 	self.assertEqual(parser.parse_preconditions(text=t),expected)
	# 	assert_block(self,expected[0],stmts,f)

	# def test_parse_preconditions_004(self):
	# 	# Input
	# 	f = "preconditions file is now.txt"
	# 	blank = "  	 	 	  \n\n\n\n\r\f\v   		"
	# 	t = blank + "when:\n" + blank
	# 	b = Block([],f)

	# 	b,t=update_block(b,t,"_valid_ = 5505 % 5","_valid_ = 5505 % 5\n")
	# 	b,t=update_block(b,t,"1","1\n")
	# 	t += blank
	# 	# Expected Output
	# 	pos = len(t)
	# 	line = 1 + t.count("\n")
	# 	expected = (b,pos,line)
	# 	stmts = []
	# 	for i in b.stmts():
	# 		stmts.append(i)
	# 	# Assertion
	# 	self.assertEqual(parser.parse_preconditions(text=t),expected)
	# 	assert_block(self,expected[0],stmts,f)

	# def test_parse_preconditions_005(self):
	# 	# Input
	# 	f = "preconditions file is now.txt"
	# 	t = rule_lotr_cond + rule_lotr_actions
	# 	s1 = Statement("sauron = \"Master of the One Ring\"",f,3)
	# 	s2 = Statement("the_one_ring = \"One Ring\"",f,4)
	# 	s3 = Statement("the_one_ring in sauron",f,5)
	# 	b = Block([s1,s2,s3],f)
	# 	text = rule_lotr_cond + rule_lotr_actions
	# 	# Expected Output
	# 	pos = len(rule_lotr_cond) + rule_lotr_actions.find("then")
	# 	line = 1 + rule_lotr_cond.count("\n")
	# 	line += rule_lotr_actions[:rule_lotr_actions.find("then")].count("\n")
	# 	expected = (b,pos,line)
	# 	# Assertion
	# 	result = parser.parse_preconditions(text=t)
	# 	self.assertEqual(result,expected)
	# 	assert_block(self,expected[0],[s1,s2,s3],f)

	def test_parse_preconditions_006(self):
		from .parser_aux import capcombos
		definitions = capcombos('when:')
		for d in definitions:
			expected = (Preconditions(),len('when:'),1)
			result = parser.parse_preconditions(text=d)
			self.assertEqual(result,expected)
			self.assertResult(result,expected[0],len('when:'),1,"when:")

	def test_parse_preconditions_i01(self):
		t = "then : it shoudn't reach this part"
		with self.assertRaises(ParseError) as cm:
			parser.parse_preconditions(t)
		msg = "parse_named_block:: Expected \'when\'"
		self.assertEqual(cm.exception.val,msg)
