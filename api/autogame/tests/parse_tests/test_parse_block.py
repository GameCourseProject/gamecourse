#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import parser
from context import Block
from context import Statement

from rule_tests.test_block import TestBlock

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Block Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseBlockBase (TestBlock):

	def assertResult (self, result, block, pos, line, text):
		""" assert the return value to see if it's a valid return value for
		parse_block funtion
		"""
		# parse_block must return a tuple with three values:
		# Block, position, line
		self.assertIsInstance(result, tuple)
		self.assertEqual(len(result),3)
		# the first element is the Block
		self.assertIsInstance(result[0],Block)
		self.assertParsedBlock(result[0],block.stmts(),block.file(),block.line())
		# a positionsan index to a string which must be a non-negative int
		# lesser or equals than the length of the text 
		self.assertIsInstance(result[1],int)
		self.assertGreaterEqual(result[1],0)
		self.assertLessEqual(result[1],len(text))
		self.assertEqual(result[1],pos)
		# a line is an integer number higher or equal than 1
		# and should be the same as the line of the block
		self.assertIsInstance(result[2],int)
		self.assertGreaterEqual(result[2],1)
		self.assertEqual(result[2],line)

	def assertParsedBlock(self, block, stmts, fpath, line):
		""" assert the type and value range of a block instance """
		# Assert BaseNode related values
		self.assertBaseNode(block, fpath, line)
		# Assert Block instances
		self.assertIsInstance(block,Block)
		# Assert Statements
		self.assertIsInstance(block.stmts(),list)
		self.assertEqual(len(block.stmts()),len(stmts))
		for i in range(len(block.stmts())):
			stmt = block.stmts()[i]
			self.assertIsInstance(stmt,Statement)
			self.assertEqual(stmt.text(),stmts[i].text())
			self.assertEqual(stmt.file(),fpath)
			self.assertTrue(stmt.line() >= line)

	def assertParseBlock(self, stmts, text, endkey='',
		pos_start=0, line_start=1, pos_end=None, line_end=None, fpath=None):
		""" test 'parse_block' function with the given inputs """
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
		result = parser.parse_block(text,pos_start,endkey,fpath,line_start)
		stmts = self.convertStatements(stmts,fpath,line_start+1)
		block = Block(stmts,fpath,line_start)
		self.assertResult(result,block,pos_end,line_end,text)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_block(text, pos, endkey, fpath, line, stmts)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> parses a block from the text starting in the given position
# >>> stops parsing when the EoF is reached or the 'endkey' is found
# >>> returns a Block (with all the statements parsed till the stop condition
# >>> is met), the position in the text were the parse_block stopped and the
# >>> actual line number that is being parsed in the text
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParseBlock (TestParseBlockBase):

	def test_v00(self):
		""" test parse_block with default arguments (omitted) """
		self.assertParseBlock([],'')

	def test_v01(self):
		""" test parse_block with whitespace in text """
		self.assertParseBlock([]," \t \n 		\n")

	def test_v02(self):
		""" test parse_block with some expression stmts """
		text = "1\n2\n3\n"
		stmts = list('123')
		self.assertParseBlock(stmts,text)

	def test_v3(self):
		""" test parse_block with some assignment stmts """
		text = "a=1\nb=2\nc=3\na+b+c"
		stmts = text.split('\n')
		self.assertParseBlock(stmts,text)

	def test_v4(self):
		""" test parse_block with expressions and endkey """
		endkey = 'end'
		text = "1\n2\n3\n%s:\n4\n5\n6" % endkey
		stmts = list('123')
		self.assertParseBlock(stmts,text,endkey)

	def test_v5(self):
		""" test parse_block in an advanced position and line in the text """
		text = "thisisanerror\n1\n2\n3\n"
		stmts = list('123')
		pos = len(text.split('\n')[0])+1
		line = 44
		self.assertParseBlock(stmts,text,pos_start=pos,line_start=line)

	def test_v6(self):
		""" test parse_block with comments in the text """
		text = "1#ignoreme\n2#ignoreme\n3#ignoreme\n#ignoreme"
		stmts = list('123')
		self.assertParseBlock(stmts,text)

	def test_v7(self):
		""" test parse_block with commented endkey in the text """
		text = "1\n2\n#end:\n3\n"
		stmts = list('123')
		pos = len(text)
		line = 1 + text.count('\n')
		self.assertParseBlock(stmts,text,endkey='end',pos_end=pos,line_end=line)

	# Invalid
	# def test_parse_block_i000(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(0)
	# 	self.assertEqual(parser.parse_block(t,p,e,f,l),result)
	# def test_parse_block_i001(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(1)
	# 	msg = "parse_block:: Invalid \'text\' arg, expected"
	# 	msg += "<type \'basestring\'> received " + str(type(None))
	# 	test_parse_block_raises(self,None,p,e,f,l,msg=msg)
	# def test_parse_block_i002(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(2)
	# 	msg = "parse_block:: Invalid \'pos\' arg, expected"
	# 	msg += "<type \'Int\' or \'Long\'> received " + str(type(None))
	# 	test_parse_block_raises(self,t,None,e,f,l,msg)
	# def test_parse_block_i003(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(3)
	# 	msg = "parse_block:: Invalid \'pos\' arg, expected"
	# 	msg += "<type \'Int\' or \'Long\'> received " + str(type(9.6))
	# 	test_parse_block_raises(self,t,9.6,e,f,l,msg)
	# def test_parse_block_i004(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(4)
	# 	msg = "parse_block:: Invalid \'endkey\' arg, expected"
	# 	msg += "<type \'basestring\'> or <type \'tuple\'>"
	# 	msg += " received " + str(type(None))
	# 	test_parse_block_raises(self,t,p,None,f,l,msg)
	# def test_parse_block_i005(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(5)
	# 	msg = "parse_block:: Invalid \'fpath\' arg, expected"
	# 	msg += "<type \'basestring\'> received " + str(type(None))
	# 	test_parse_block_raises(self,t,p,e,None,l,msg)
	# def test_parse_block_i006(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(6)
	# 	msg = "parse_block:: Invalid \'line\' arg, expected"
	# 	msg += "<type \'basestring\'> received " + str(type(None))
	# 	test_parse_block_raises(self,t,p,e,f,None,msg)
	# def test_parse_block_i007(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(7)
	# 	msg = "parse_block:: Invalid \'line\' arg, expected"
	# 	msg += "<type \'basestring\'> received " + str(type(-561.51100))
	# 	test_parse_block_raises(self,t,p,e,f,-561.51100,msg)
	# def test_parse_block_i008(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(8)
	# 	msg = "parse_block:: \'pos\' can't be a negative number"
	# 	test_parse_block_raises(self,t,-1,e,f,l,msg)
	# def test_parse_block_i009(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(9)
	# 	msg = "parse_block:: \'line\' must be non-zero positive Integer"
	# 	test_parse_block_raises(self,t,p,e,f,0,msg)
	# def test_parse_block_i010(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(10)
	# 	msg = "parse_block:: Invalid \'stmts\' arg, expected"
	# 	msg += "<type \'list\'> received " + str(type((1,2)))
	# 	test_parse_block_raises(self,t,p,e,f,l,msg,(1,2))
	# def test_parse_block_i011(self):
	# 	t,p,e,f,l,result = get_parse_block_iargs(11)
	# 	msg = "parse_block:: Invalid \'stmts\' arg. All elements of this list "
	# 	msg += "must be Statements, but found an element of "
	# 	msg += str(type({"stmt":1})) + ", in the list."
	# 	test_parse_block_raises(self,t,p,e,f,l,msg,[{"stmt":1}])

	# def test_parse_block_iXXX(self):
		# self.assertRaises(ParseError,parser.parse_block,text="invalid expression")