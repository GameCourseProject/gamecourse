#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Actions, Block, Statement, Effect

from base_test_class import BaseTestClass

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Abstract Class TestActions
# > use this class to define generic methods to support the tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestActions(BaseTestClass):

	def assertCreation(self, stmts=None, filepath=None, line=None):
		precs = self.createActionsBlock(stmts,filepath,line)
		self.assertIsInstance(precs,Actions)
		self.assertIsInstance(precs,Block)
		self.assertIsInstance(precs.stmts(),list)
		self.assertIsInstance(precs.file(),str)
		self.assertIsInstance(precs.line(),int)
		if stmts == None:
			self.assertEqual(precs.stmts(),[])
		else:
			self.assertEqual(precs.stmts(),stmts)
			self.assertIsNot(precs.stmts(),stmts)
			self.assertStatements(precs)
		if filepath == None:
			self.assertEqual(precs.file(),'')
		else:
			self.assertEqual(precs.file(),filepath)
		if line == None:
			self.assertEqual(precs.line(),1)
		else:
			self.assertEqual(precs.line(),line)

	def assertFire (self, stmts, scope=None, expected=None):
		f = "C:\\rules\\tests\\actions\\assert_fire.txt"
		l = 32
		if len(stmts) > 0:
			from context import parser
			stmts = [parser.parse_statement(t,0,f,l)[0] for t in stmts]
		block = self.createActionsBlock(stmts,f,l)
		if scope == None:
			scope = {}
		expected_scope = dict(scope)
		for s in stmts:
			exec(s.code(),expected_scope)
		result = block.fire(scope)
		expected = [] if expected == None else [Effect(e) for e in expected]
		self.assertIsNot(scope,expected_scope)
		self.assertEqual(scope,expected_scope)
		self.assertEqual(result,expected)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Actions([stmts [, fpath [, line]]]) --> Actions Block
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestActions):

	def test_01 (self):
		""" test actions creation with no args """
		self.assertCreation()

	def test_02 (self):
		""" test actions creation with just line """
		self.assertCreation(line=23)

	def test_03 (self):
		""" test actions creation with just filepath """
		self.assertCreation(filepath="C:\\rules\\test\\actions.txt")

	def test_04 (self):
		""" test actions creation with just stmts """
		self.assertCreation(stmts=self.generate_stmts(43))

	def test_05 (self):
		""" test actions creation with stmts, line """
		self.assertCreation(stmts=self.generate_stmts(4,line_start=3),line=3)

	def test_06 (self):
		""" test actions creation with stmts, filepath """
		fp = self.get_fpath()
		self.assertCreation(stmts=self.generate_stmts(1,fp),filepath=fp)

	def test_07 (self):
		""" test actions creation with filepath and line """
		self.assertCreation(filepath=self.get_fpath(),line=33)

	def test_08 (self):
		""" test actions creation with stmts, filepath and line """
		l = 89; f = self.get_fpath(); s = self.generate_stmts(33,f,l)
		self.assertCreation(s,f,l)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Actions.fire(scope) --> bool, scope
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFire (TestActions):

	def test_01 (self):
		""" fire empty actions """
		self.assertFire([])

	def test_02 (self):
		""" fire multiple assignments """
		s = ['a=1','b=a','c=b']
		self.assertFire(s)

	def test_03 (self):
		""" fire multiple expressions """
		s = ['True','False','True']
		self.assertFire(s)

	def test_04 (self):
		""" fire multiple expressions and assignments """
		s = ['a=1','b=2','a+b','c=3','a==0','a+b>=c']
		self.assertFire(s)

	def test_05 (self):
		""" fire multiple expressions and assignments with scope """
		factorial = lambda x: sum(range(x))
		scope = {}
		scope['factorial'] = locals()['factorial']
		stmts = ['x = factorial(4)', 'x', 'y = factorial(3)', 'x >= y']
		self.assertFire(stmts,scope)

	def test_06 (self):
		""" fire functions that produce effects """
		f = lambda x: Effect(x)
		scope = {'f':f}
		stmts = ['f(1)','f(2)','3 > 4']
		self.assertFire(stmts,scope,(1,2))