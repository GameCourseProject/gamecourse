#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Preconditions, Block, Statement

from base_test_class import BaseTestClass

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Abstract Class TestPreconditions
# > use this class to define generic methods to support the tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestPreconditions(BaseTestClass):

	def assertCreation(self, stmts=None, filepath=None, line=None):
		precs = self.createPreconditionsBlock(stmts,filepath,line)
		self.assertIsInstance(precs,Preconditions)
		self.assertIsInstance(precs,Block)
		self.assertIsInstance(precs.stmts(),list)
		self.assertIsInstance(precs.file(),str)
		self.assertIsInstance(precs.line(),int)
		if stmts is None:
			self.assertEqual(precs.stmts(),[])
		else:
			self.assertEqual(precs.stmts(),stmts)
			self.assertIsNot(precs.stmts(),stmts)
			self.assertStatements(precs)
		if filepath is None:
			self.assertEqual(precs.file(),'')
		else:
			self.assertEqual(precs.file(),filepath)
		if line is None:
			self.assertEqual(precs.line(),1)
		else:
			self.assertEqual(precs.line(),line)

	def assertFire (self, stmts, expected, scope=None):
		f = "C:\\rules\\tests\\preconditions\\assert_fire.txt"
		l = 320
		if len(stmts) > 0:
			from context import parser
			stmts = [parser.parse_statement(t,0,f,l)[0] for t in stmts]
		block = self.createPreconditionsBlock(stmts,f,l)
		if scope is None:
			scope = {}
		expected_scope = dict(scope)
		for s in stmts:
			exec(s.code(),expected_scope)
		result = block.fire(scope)
		self.assertIsNot(scope,expected_scope)
		self.assertEqual(scope,expected_scope)
		self.assertEqual(result,expected)
		self.assertTrue(result is expected)
		self.assertFalse(result is not expected)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Preconditions([stmts [, fpath [, line]]]) --> Preconditions Block
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestPreconditions):

	def test_01 (self):
		""" test preconditions creation with no args """
		self.assertCreation()

	def test_02 (self):
		""" test preconditions creation with just line """
		self.assertCreation(line=23)

	def test_03 (self):
		""" test preconditions creation with just filepath """
		self.assertCreation(filepath="C:\\rules\\test\\preconditions.txt")

	def test_04 (self):
		""" test preconditions creation with just stmts """
		self.assertCreation(stmts=self.generate_stmts(43))

	def test_05 (self):
		""" test preconditions creation with stmts, line """
		self.assertCreation(stmts=self.generate_stmts(4,line_start=3),line=3)

	def test_06 (self):
		""" test preconditions creation with stmts, filepath """
		fp = self.get_fpath()
		self.assertCreation(stmts=self.generate_stmts(1,fp),filepath=fp)

	def test_07 (self):
		""" test preconditions creation with filepath and line """
		self.assertCreation(filepath=self.get_fpath(),line=33)

	def test_08 (self):
		""" test preconditions creation with stmts, filepath and line """
		l = 89; f = self.get_fpath(); s = self.generate_stmts(33,f,l)
		self.assertCreation(s,f,l)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Preconditions.fire(scope) --> bool, scope
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFire (TestPreconditions):

	def test_01 (self):
		""" fire empty preconditions """
		self.assertFire([],True)

	def test_02 (self):
		""" fire multiple assignments """
		s = ['a=1','b=a','c=b']
		self.assertFire(s,True)

	def test_03 (self):
		""" fire multiple expressions """
		s = ['True','False','True']
		self.assertFire(s,False)

	def test_04 (self):
		""" fire multiple expressions and assignments """
		s = ['a=1','b=2','a+b','c=3','a==0','a+b>=c']
		self.assertFire(s,False)

	def test_05 (self):
		""" fire multiple expressions and assignments with scope """
		factorial = lambda x: sum(range(x))
		scope = {}
		scope['factorial'] = locals()['factorial']
		stmts = ['x = factorial(4)', 'x', 'y = factorial(3)', 'x >= y']
		self.assertFire(stmts,True,scope)