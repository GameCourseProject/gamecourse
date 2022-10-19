#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import parser
from context import Block
from context import ParseError
from aux_functions import get_parse_nblock_iargs
from aux_functions import test_parse_nblock_raises

from .test_parse_block import TestParseBlockBase

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Named Block Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseNamedBlockBase (TestParseBlockBase):

	def assertParseNamedBlock(self, stmts, name, text, endkey='',
		pos_start=0, line_start=1, pos_end=None, line_end=None, fpath=None):
		""" test 'parse_named_block' function with the given inputs """
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
		result = parser.parse_named_block(name,text,
			pos_start,endkey,fpath,line_start)
		stmts = self.convertStatements(stmts,fpath,line_start+1)
		block = Block(stmts,fpath,line_start)
		self.assertResult(result,block,pos_end,line_end,text)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# parser.parse_named_block(name, text, pos, endkey, fpath, line)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> if the name followed by ':' is the next thing in the text starting
# >>> in the given position, it will parse a block from that position onward
# >>> and stops parsing when the EoF is reached or the 'endkey' is found
# >>> returns a Block (with all the statements parsed till the stop condition
# >>> is met), the position in the text were the parse_block stopped and the
# >>> actual line number that is being parsed in the text
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestParseNamedBlock (TestParseNamedBlockBase):

	def test_v01 (self):
		""" test parse_named_block with default arguments (omitted) """
		self.assertParseNamedBlock([],'when','when:')

	def test_v02 (self):
		""" test parse_named_block with whitespace in text """
		self.assertParseNamedBlock([],'when','when:\n 	\t\n\n\n\n  \n')

	def test_v03 (self):
		""" test parse_named_block with expressions in text """
		self.assertParseNamedBlock(list('123'),'then','then:\n1\n2\n3\n')

	def test_v04(self):
		""" test parse_named_block with weird name """
		name = "_blockname-2_"
		self.assertParseNamedBlock(list('123'),name,name+':\n1\n2\n3\n')

	# def test_parse_named_block_003(self):
	# 	name = "isthisnamevalid?"
	# 	blank = "   \t\t\f\v 	\r"
	# 	text = blank+name+(blank*1000)+":"+(blank*(3**7))+"a=2\na"

	# 	s1 = Statement("a=2","",1)
	# 	s2 = Statement("a","",2)
	# 	block = Block([s1,s2],"")

	# 	pos = len(text)
	# 	line = text.count("\n")+1
	# 	expected = (block, pos, line)
	# 	self.assertEqual(parser.parse_named_block(name,text),expected)
	# 	assert_block(self,expected[0],[s1,s2],"")

	# def test_parse_named_block_004(self):
	# 	name = "(myblock)"
	# 	decls = "x = f(10.0,-30,047,0xff)\ny = g(x)+h(x**2)"
	# 	exprs = "x + y == [20,30,40]\n"
	# 	text = name + ":" + decls + "\n" + exprs

	# 	s1 = Statement("x = f(10.0,-30,047,0xff)","",1)
	# 	s2 = Statement("y = g(x)+h(x**2)","",2)
	# 	s3 = Statement("x + y == [20,30,40]","",3)
	# 	block = Block([s1,s2,s3],"")

	# 	pos = len(text)
	# 	line = text.count("\n")+1
	# 	expected = (block, pos, line)
	# 	self.assertEqual(parser.parse_named_block(name,text),expected)
	# 	assert_block(self,expected[0],[s1,s2,s3],"")

	# def test_parse_named_block_005(self):
	# 	n,t,p,s,f,l,result = get_parse_nblock_iargs()
	# 	stmts = []
	# 	for i in result[0].stmts():
	# 		stmts.append(i)
	# 	self.assertEqual(parser.parse_named_block(n,t,p,s,f,l),result)
	# 	assert_block(self,result[0],stmts,f)

	# def test_parse_named_block_006(self):
	# 	n = "then"
	# 	t = "then: x+y"
	# 	s1 = Statement("x = 1")
	# 	s2 = Statement("y = 92743")
	# 	s3 = Statement("x+y")
	# 	result=(Block([s1,s2,s3]),len(t),1)
	# 	self.assertEqual(parser.parse_named_block(n,t,stmts=[s1,s2]),result)
	# 	assert_block(self,result[0],[s1,s2,s3],"")

	# Invalid
	def test_parse_named_block_i001(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(1)
		msg = "parse_block:: Invalid \'name\' arg, expected"
		msg += "<type \'str\'> received " + str(type(None))
		test_parse_nblock_raises(self,None,t,p,f,l,msg)

	def test_parse_named_block_i002(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(2)
		msg = "parse_block:: Invalid \'text\' arg, expected"
		msg += "<type \'str\'> received " + str(type(None))
		test_parse_nblock_raises(self,n,None,p,f,l,msg)

	def test_parse_named_block_i003(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(3)
		msg = "parse_block:: Invalid \'pos\' arg, expected"
		msg += "<type \'Int\' or \'int\'> received " + str(type(None))
		test_parse_nblock_raises(self,n,t,None,f,l,msg)

	def test_parse_named_block_i004(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(4)
		msg = "parse_block:: Invalid \'pos\' arg, expected"
		msg += "<type \'Int\' or \'int\'> received " + str(type(-5.8))
		test_parse_nblock_raises(self,n,t,-5.8,f,l,msg)

	def test_parse_named_block_i005(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(5)
		msg = "parse_block:: Invalid \'fpath\' arg, expected"
		msg += "<type \'str\'> received " + str(type(None))
		test_parse_nblock_raises(self,n,t,p,None,l,msg)

	def test_parse_named_block_i006(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(6)
		msg = "parse_block:: Invalid \'line\' arg, expected"
		msg += "<type \'str\'> received " + str(type(None))
		test_parse_nblock_raises(self,n,t,p,f,None,msg)

	def test_parse_named_block_i007(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(7)
		msg = "parse_block:: Invalid \'line\' arg, expected"
		msg += "<type \'str\'> received " + str(type(10.1))
		test_parse_nblock_raises(self,n,t,p,f,10.1,msg)

	def test_parse_named_block_i008(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(8)
		msg = "parse_block:: size of \'text\' can't be less than size of"
		msg += " \'name\'"
		test_parse_nblock_raises(self,n,"",p,f,l,msg)

	def test_parse_named_block_i009(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(9)
		msg = "parse_block:: Name of block can't be empty"
		test_parse_nblock_raises(self,"",t,p,f,l,msg)

	def test_parse_named_block_i010(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(10)
		msg = "parse_block:: size of \'text\' can't be less than size of"
		msg += " \'name\'"
		test_parse_nblock_raises(self,n,"a",p,f,l,msg)

	def test_parse_named_block_i011(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(11)
		msg = "parse_block:: \'pos\' can't be a negative number"
		test_parse_nblock_raises(self,n,t,-1,f,l,msg)

	def test_parse_named_block_i012(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(12)
		msg = "parse_block:: \'pos\' can't be equal or bigger than size of text"
		test_parse_nblock_raises(self,n,t,len(t),f,l,msg)

	def test_parse_named_block_i013(self):
		n,t,p,s,f,l,result = get_parse_nblock_iargs(13)
		msg = "parse_block:: \'line\' must be non-zero positive Integer"
		test_parse_nblock_raises(self,n,t,p,f,0,msg)

	def test_parse_named_block_i014(self):
		with self.assertRaises(ParseError) as cm:
			parser.parse_named_block("name","wrong")
		error_msg = "parse_named_block:: Expected \'name\'"
		self.assertEqual(cm.exception.val,error_msg)

	def test_parse_named_block_i015(self):
		with self.assertRaises(ParseError) as cm:
			parser.parse_named_block("wrong","name in named block")
		msg = str(cm.exception.val)
		expected = "parse_named_block:: Expected \'wrong\'"
		self.assertEqual(msg,expected)

	def test_parse_named_block_i016(self):
		with self.assertRaises(ParseError) as cm:
			parser.parse_named_block("n","n body")
		msg = cm.exception.val
		expected = "parse_named_block:: Expected \':\' after the block name"
		self.assertEqual(msg,expected)

	def test_parse_named_block_i017(self):
		with self.assertRaises(ParseError) as cm:
			n = "when"
			b = n + "  this is supposed to be wrong because "
			b += "the colon \':\' is missing"
			parser.parse_named_block(n,b)
		msg = str(cm.exception.val)
		expected = "parse_named_block:: Expected \':\' after the block name"
		self.assertEqual(msg,expected)
