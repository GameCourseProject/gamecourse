#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Block
from context import Statement

from .test_basenode import TestBaseNode

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestBlock
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestBlock(TestBaseNode):

	def assertEqualBlocks(self, b1, b2):
		""" assert if two blocks are equal """
		self.assertIsNot(b1,b2)
		self.assertEqual(b1,b2)
		self.assertTrue(b1 == b2)
		self.assertFalse(b1 != b2)

	def assertNotEqualBlocks(self, b1, b2):
		""" assert if two blocks are not equal """
		self.assertIsNot(b1,b2)
		self.assertNotEqual(b1,b2)
		self.assertTrue(b1 != b2)
		self.assertFalse(b1 == b2)

	def assertCreation(self, stmts=None, fpath=None, line=None):
		if fpath == None:
			fpath = ''
		if line == None:
			line = 1
		if stmts == None:
			stmts = []
		else:
			if not isinstance(stmts[0],Statement):
				stmts = self.convertStatements(stmts,fpath,line)
		block = self.createBlock(stmts,fpath,line)
		self.assertBlock(block,stmts,fpath,line)

	def assertCreationRaises(self, error, stmts=None, fpath=None, line=None):
		if error == 'TypeError':
			with self.assertRaises(TypeError):
				self.createBlock(stmts,fpath,line)
		elif error == 'ValueError':
			with self.assertRaises(ValueError):
				self.createBlock(stmts,fpath,line)

	def assertAddStmtRaises(self, error, arg):
		b = Block(fpath='C:\\addstmtraises\\block.txt',line=44403)
		with self.assertRaises(Exception) as cm:
			b.add_stmt(arg)
		self.assertEqual(type(cm.exception).__name__, error)

	def assertAddStmtsRaises(self, error, arg):
		b = Block(fpath='C:\\addstmtsraises\\block.txt',line=44403)
		with self.assertRaises(Exception) as cm:
			b.add_stmts(arg)
		self.assertEqual(type(cm.exception).__name__, error)

	def assertFire(self,stmts,expected,scope_in=None,scope_out=None):
		f = "C:\\test\\block\\fire.txt"
		l = 22
		stmts = self.convertStatements(stmts,f,l+5)
		block = Block(stmts,f,l)
		if scope_in == None:
			scope_in = dict()
		result = block.fire(scope_in)
		self.assertEqual(result,expected)
		self.assertIsNot(scope_in,scope_out)
		self.assertDictContainsSubset(scope_out,scope_in)

	def assertFireRaises(self, error, arg):
		f = 'C:\\addstmtsraises\\block.txt'
		l = 44403
		b = Block(fpath=f,line=l)
		stmts = ('a=1','b=a*2','c=b+a','(a+b+c)+sum(a,b,c)')
		stmts = self.convertStatements(stmts,f,l)
		with self.assertRaises(Exception) as cm:
			b.add_stmts(arg)
		self.assertEqual(type(cm.exception).__name__, error)

	def createBlock(self, stmts=None, filepath=None, line=None):
		if stmts == None and filepath == None and line == None:
			block = Block()
		elif stmts == None and filepath == None:
			block = Block(line=line)
		elif stmts == None and line == None:
			block = Block(fpath=filepath)
		elif filepath == None and line == None:
			block = Block(stmts=stmts)
		elif stmts == None:
			block = Block(fpath=filepath, line=line)
		elif filepath == None:
			block = Block(stmts=stmts, line=line)
		elif line == None:
			block = Block(stmts=stmts, fpath=filepath)
		else:
			block = Block(stmts,filepath,line)
		return block

	def get_testfilespath (self):
		""" return the path to test files directory """
		from context import testfiles_path
		return testfiles_path

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestBlock):
	def test_i01 (self):
		""" test invalid type for stmts argument """
		self.assertCreationRaises('TypeError','')
		self.assertCreationRaises('TypeError',1)
		self.assertCreationRaises('TypeError',-2.0)
		self.assertCreationRaises('TypeError',{'stmt':1})
		self.assertCreationRaises('TypeError',Statement())
		self.assertCreationRaises('TypeError',[1,'s'])
		self.assertCreationRaises('TypeError',[Statement(),'a=0'])

	def test_i02 (self):
		""" test invalid type for fpath argument """
		self.assertCreationRaises('TypeError',fpath=[])
		self.assertCreationRaises('TypeError',fpath=1)
		self.assertCreationRaises('TypeError',fpath=-2.0)
		self.assertCreationRaises('TypeError',fpath={'stmt':1})
		self.assertCreationRaises('TypeError',fpath=Statement())

	def test_i03 (self):
		""" test invalid type for line argument """
		self.assertCreationRaises('ValueError',line=0)
		self.assertCreationRaises('ValueError',line=-1)
		self.assertCreationRaises('TypeError',line='')
		self.assertCreationRaises('TypeError',line=-2.0)
		self.assertCreationRaises('TypeError',line={'stmt':1})
		self.assertCreationRaises('TypeError',line=Statement())
		self.assertCreationRaises('TypeError',line=[1,'s'])
		self.assertCreationRaises('TypeError',line=[Statement(),'a=0'])

	def test_i04 (self):
		"""
		test block creation with statements with
		a smaller line number than the block
		"""
		self.assertCreationRaises('ValueError',[Statement(line=2)],line=20)

	def test_i05 (self):
		"""
		test block creation with statements with
		a different filepath than the block
		"""
		self.assertCreationRaises('ValueError',[Statement(fpath='file1')],'file2')

	def test_v00 (self):
		""" test block creation with no args """
		self.assertCreation()

	def test_v01 (self):
		""" test block creation with just stmts """
		self.assertCreation(self.generateStatements(99))

	def test_v02 (self):
		""" test block creation with just filepath """
		self.assertCreation(fpath="C:\\rules\\test\\block.txt")

	def test_v03 (self):
		""" test block creation with just line """
		self.assertCreation(line=23)

	def test_v04 (self):
		""" test block creation with stmts, filepath """
		fp = self.get_testfilespath()
		self.assertCreation(stmts=self.generateStatements(1,fp),fpath=fp)

	def test_v05 (self):
		""" test block creation with stmts, line """
		self.assertCreation(stmts=self.generateStatements(4,start=3),line=3)

	def test_v06 (self):
		""" test block creation with filepath and line """
		self.assertCreation(fpath=self.get_testfilespath(),line=33)

	def test_v07 (self):
		""" test block creation with stmts, filepath and line """
		l=89; f=self.get_testfilespath(); s=self.generateStatements(33,f,l)
		self.assertCreation(s,f,l)

	def test_v08 (self):
		""" test block creation with assignments statements """
		s = ['a=1','b=2','c=3','a+b+c <= sum([a,b,c])']
		self.assertCreation(s)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Equals(block1, block2)
# > Two blocks are equal if:
# >		- they have EQUAL statements
# >		- they have the same filepath
# > 	- they start in the same line
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestEquals(TestBlock):

	def test_f00 (self):
		""" test if a block obj and other obj are diff """
		self.assertNotEqualBlocks(Block(),Statement())

	def test_f01 (self):
		""" test if two blocks with different statements are different """
		block1 = Block([Statement('1')])
		block2 = Block([Statement('2')])
		self.assertNotEqualBlocks(block1,block2)

	def test_f02 (self):
		""" test if two empty blocks with diff file and same line are diff """
		f1 = 'C:\\file1.txt'; f2 = 'C:\\file2.txt'
		self.assertNotEqualBlocks(Block(fpath=f1),Block(fpath=f2))

	def test_f03 (self):
		""" test if two empty blocks with same file and diff line are diff """
		self.assertNotEqualBlocks(Block(line=2),Block(line=303))

	def test_t01 (self):
		""" test if two empty blocks with same file and line are equal """
		self.assertEqualBlocks(Block(),Block())

	def test_t02 (self):
		""" test if two non-empty blocks with same file and line are equal """
		s = Statement('432')
		b1 = Block([s for i in range(3)])
		b2 = Block([s for i in range(3)])
		self.assertEqualBlocks(b1,b2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Block getters
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Block.get_statements(), Block.get_stmts(), Block.stmts(), Block.statements()
# Block.fpath(), Block.path(), Block.location(), Block.filepath(), ...
# ... Block.file_path
# Block.isvalid, Block.is_valid
# Block.get_assignments
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestGetStmts(TestBlock):

	def test_stmts (self):
		stmts = [Statement("i = 0"), Statement("i")]
		stmts2 = list(stmts)
		block = Block(stmts2)
		self.assertEqual(block.stmts(),stmts)
		# bug = block.get_stmts()
		# bug.append(Statement("Exception('kaboom')"))
		stmts2.append(Statement("Exception('kaboom')"))
		self.assertEqual(len(block.stmts()),2)
		self.assertEqual(block.stmts(),stmts)
		self.assertEqual(block.statements(),stmts)
		self.assertEqual(block.get_stmts(),stmts)
		self.assertEqual(block.get_statements(),stmts)

	def test_path (self):
		path = "C:\\my\\new\\secret\\hideout\\block.txt"
		block = Block(fpath=path)
		self.assertEqual(block.fpath(),path)
		self.assertEqual(block.path(),path)
		self.assertEqual(block.location(),path)
		self.assertEqual(block.filepath(),path)
		self.assertEqual(block.file_path(),path)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# add_stmt
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAddStatement(TestBlock):

	def test_i01 (self):
		""" test add stmt with an invalid argument type """
		self.assertAddStmtRaises('TypeError','invalid_arg_type')
		self.assertAddStmtRaises('TypeError',123)
		self.assertAddStmtRaises('TypeError',Exception('ups'))
		self.assertAddStmtRaises('TypeError',[Statement('a = 0')])
		self.assertAddStmtRaises('TypeError',-32.2)
		self.assertAddStmtRaises('TypeError',None)

	def test_i02 (self):
		""" test add stmt with a different file than the block """
		self.assertAddStmtRaises('ValueError',Statement(fpath='diff'))

	def test_i03 (self):
		""" test add stmt with a lower line than the block """
		self.assertAddStmtRaises('ValueError',Statement(line=32))

	def test_v00 (self):
		""" test add a valid statement to the block """
		s = Statement()
		b = Block()
		b.add_stmt(s)
		self.assertIn(s,b.stmts())

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Block.add_stmts(list<Statement>)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestAddStmts(TestBlock):

	def test_i01 (self):
		""" test add stmts with an invalid argument type """
		self.assertAddStmtsRaises('TypeError','invalid_arg_type')
		self.assertAddStmtsRaises('TypeError',123)
		self.assertAddStmtsRaises('TypeError',Exception('ups'))
		self.assertAddStmtsRaises('TypeError',Statement('a = 0'))
		self.assertAddStmtsRaises('TypeError',-32.2)
		self.assertAddStmtsRaises('TypeError',None)

	def test_i02 (self):
		""" test add stmts with a different file than the block """
		self.assertAddStmtsRaises('ValueError',[Statement(line=999999)])

	def test_i03 (self):
		""" test add stmts with a lower line than the block """
		self.assertAddStmtsRaises('ValueError',[Statement(line=32)])

	def test_v01 (self):
		""" test add valid stmts """
		s1 = Statement('1'); s2 = Statement('2')
		block = Block()
		block.add_stmts([s1,s2])
		self.assertIn(s1,block.stmts())
		self.assertIn(s2,block.stmts())

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Block.fire(scope)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFire(TestBlock):

	def test_i01 (self):
		""" test block.fire() with invalid arg """
		self.assertFireRaises('TypeError','error')

	def test_v01 (self):
		""" test block.fire() with an empty block """
		self.assertIsNone(Block().fire())

	def test_v02 (self):
		""" test block.fire() with local assignment dependencies """
		stmts = ('a=1','b=a*2','c=b+a')
		scope = {'a':1,'b':2,'c':3}
		self.assertFire(stmts,True,None,scope)

	def test_v03 (self):
		""" test block.fire() with name dependencies """
		factorial = lambda x: sum(range(1,x+1))
		stmts = ('a = factorial(4)', 'a * factorial(4)')
		scope_in = {}
		scope_in['factorial'] = locals()['factorial']
		scope_out = {'a':factorial(4)}
		self.assertFire(stmts,factorial(4)**2,scope_in,scope_out)
