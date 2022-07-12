#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Rule
from context import Block
from context import Preconditions
from context import Actions
from context import Statement
from context import Output
from context import Effect
from context import RuleError

from .test_basenode import TestBaseNode

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main Class - TestRule
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestRule(TestBaseNode):

	def assertCreation(
		self,name=None,desc=None,precs=None,acts=None,fpath=None,line=None):
		result = self.getDefaultValues(name,desc,precs,acts,fpath,line)
		name,desc,precs,acts,fpath,line = result
		rule = Rule(name,desc,precs,acts,fpath,line)
		self.assertRule(rule,name,desc,precs,acts,fpath,line)

	def assertCreationRaises (self, error,
			name=None, desc=None, precs=None, acts=None,
			fpath=None, line=None):
		values = self.getDefaultValues(name,desc,precs,acts,fpath,line)
		name,desc,precs,acts,fpath,line = values
		with self.assertRaises(Exception) as cm:
			Rule(name,desc,precs,acts,fpath,line)
		execption_name = type(cm.exception).__name__
		self.assertEqual(execption_name,error)

	def assertEqualRules (self,r1,r2):
		""" assert if two rules are equal """
		self.assertIsNot(r1,r2)
		self.assertEqual(r1,r2)
		self.assertTrue(r1==r2)
		self.assertFalse(r1!=r2)

	def assertDifferentRules (self,r1,r2):
		""" assert if two rules are different """
		self.assertIsNot(r1,r2)
		self.assertNotEqual(r1,r2)
		self.assertTrue(r1!=r2)
		self.assertFalse(r1==r2)

	def getDefaultValues (self,n,d,p,a,f,l):
		if n == None:
			n = 'TestRule: createRule'
		if d == None:
			d = 'Random description for rule'
		if f == None:
			f = 'C:\\test\\rule\\TestRule\\createRule.txt'
		if l == None:
			l = 33
		if p == None:
			p = Preconditions(self.generateStatements(3,f,l+2),f,l+1)
		elif isinstance(p,(list,tuple)) \
		and len(p) > 0 and isinstance(p[0],str):
			p = Preconditions(self.convertStatements(p,f,l+2),f,l+1)
		if a == None:
			a = Actions(self.generateStatements(2,f,l+6),f,l+5)
		elif isinstance(a,(list,tuple)) \
		and len(a) > 0 and isinstance(a[0],str):
			line = p.stmts()[len(p.stmts())-1].line() + 2
			a = Actions(self.convertStatements(a,f,line+1),f,line)
		return n,d,p,a,f,l

	def assertFire(self, target=None,
		precs=None, acts=None, scope=None, expected=None):
		""" asserts if the rule is executed correctly for the given args """
		f = "C:\\tests\\rule_tests\\test_rule\\assert_fire.txt"
		l = 782
		precs = self.createPreconditionsBlock(self.convert_stmts(precs,f,l),f,l)
		acts = self.createActionsBlock(self.convert_stmts(acts,f,l),f,l)
		if expected != False:
			expected = [] if expected == None else [Effect(e) for e in expected]
			expected = Output(expected)
		rule = Rule("assertFire","",precs,acts,f,l)
		if isinstance(scope,dict):
			# make a new copy of the original scope, to check if it was not modified
			orig = dict(scope)
		result = rule.fire(target,scope)
		self.assertEqual(result,expected)
		if target == None or expected == False:
			self.assertEqual(rule.targets(),[])
		else:
			self.assertEqual(rule.target_output(target),expected)
		if isinstance(scope,dict):
			self.assertEqual(orig,scope)

	def assertFireRaises(self,error,target=None,precs=None,acts=None,scope=None):
		""" asserts if firing the rule with the given args raises an error """
		f = "C:\\tests\\rule_tests\\test_rule\\assert_fire_raises.txt"
		l = 92334
		name = "assertFireRaises(%s)" % error.__name__
		precs = self.createPreconditionsBlock(self.convert_stmts(precs,f,l),f,l)
		acts = self.createActionsBlock(self.convert_stmts(acts,f,l),f,l)
		rule = Rule(name,"",precs,acts,f,l)
		self.assertRaises(error,rule.fire,target,scope)

	def convert_stmts(self,stmts,f,l):
		""" converts a list of strings into a list of statements
		parsed from file f in line l """
		from context import parser
		if stmts == None:
			return []
		else:
			return [parser.parse_statement(t,0,f,l)[0] for t in stmts]

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Rule(name, description, preconditions, actions, fpath, line)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# >>> name: a string with the name given to the rule
# >>> description: a string with some sort of description of the rule behaviour
# >>> preconditions: a Block with the statements to be executed as preconditions
# >>> actions: a Block with the statements to be executed as actions
# >>> fpath: a string with the path to the file were the rule is defined
# >>> line: an integer (or long) with the line were the rule is defined
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestRule):
	def test_v01 (self):
		""" test rule creation with default values """
		self.assertRule(Rule(),'','',Preconditions(),Actions(),'',1)

	def test_v02 (self):
		""" test rule creation with normal values """
		self.assertCreation()

	def test_v03 (self):
		""" test rule creation with customed values """
		p = ['a=1','b=2','a+b>0']
		a = ['a=a*10','b=b**4','a-b']
		self.assertCreation('Magic Rule','makes magic come true',p,a,'f1.txt',7)
	
	def test_v04 (self):
		""" create a rule with a generic Block as preconditions """
		b = Block()
		self.assertRule(Rule(precs=b),'','',Preconditions(),Actions(),'',1)
	
	def test_v05 (self):
		""" create a rule with an Actions Block as preconditions """
		b = Actions()
		self.assertRule(Rule(precs=b),'','',Preconditions(),Actions(),'',1)
	
	def test_v06 (self):
		""" create a rule with a generic Block as actions """
		b = Block()
		self.assertRule(Rule(acts=b),'','',Preconditions(),Actions(),'',1)
	
	def test_v07 (self):
		""" create a rule with an Actions Block as actions """
		b = Preconditions()
		self.assertRule(Rule(acts=b),'','',Preconditions(),Actions(),'',1)

	def test_i01 (self):
		""" test rule creation with invalid name argument """
		self.assertCreationRaises('TypeError',name=Exception())
		self.assertCreationRaises('TypeError',name=124)
		self.assertCreationRaises('TypeError',name=Preconditions())
		self.assertCreationRaises('TypeError',name=Actions())
		self.assertCreationRaises('TypeError',name={'ss':'ss'})
		self.assertCreationRaises('TypeError',name=-1.234)

	def test_i02 (self):
		""" test rule creation with invalid desc argument """
		self.assertCreationRaises('TypeError',desc=Exception())
		self.assertCreationRaises('TypeError',desc=124)
		self.assertCreationRaises('TypeError',desc=Preconditions())
		self.assertCreationRaises('TypeError',desc=Actions())
		self.assertCreationRaises('TypeError',desc={'ss':'ss'})
		self.assertCreationRaises('TypeError',desc=-1.234)

	def test_i03 (self):
		""" test rule creation with invalid precs argument """
		self.assertCreationRaises('TypeError',precs=Exception())
		self.assertCreationRaises('TypeError',precs=124)
		self.assertCreationRaises('TypeError',precs={'ss':'ss'})
		self.assertCreationRaises('TypeError',precs=-1.234)
		self.assertCreationRaises('TypeError',precs='error')
		self.assertCreationRaises('TypeError',precs=[Preconditions()])

	def test_i04 (self):
		""" test rule creation with invalid acts argument """
		self.assertCreationRaises('TypeError',acts=Exception())
		self.assertCreationRaises('TypeError',acts=124)
		self.assertCreationRaises('TypeError',acts={'ss':'ss'})
		self.assertCreationRaises('TypeError',acts=-1.234)
		self.assertCreationRaises('TypeError',acts='error')
		self.assertCreationRaises('TypeError',acts=[Actions()])

	def test_i05 (self):
		""" test rule creation with different fpath for rule and precs """
		f = "C:\\tests\\rule\\creation\\test_i05.txt"
		self.assertCreationRaises('ValueError',
			precs=Preconditions(fpath='different'),fpath=f)

	def test_i06 (self):
		""" test rule creation with invalid line in the rule preconditions """
		f = "C:\\tests\\rule\\creation\\test_i06.txt"
		self.assertCreationRaises('ValueError',
			precs=Preconditions(fpath=f,line=22),fpath=f,line=303)

	def test_i07 (self):
		""" test rule creation with different fpath for rule and acts """
		f = "C:\\tests\\rule\\creation\\test_i07.txt"
		self.assertCreationRaises('ValueError',
			acts=Actions(fpath='different'),fpath=f)

	def test_i08 (self):
		""" test rule creation with invalid line in the rule actions """
		f = "C:\\tests\\rule\\creation\\test_i08.txt"
		self.assertCreationRaises('ValueError',
			acts=Actions(fpath=f,line=22),fpath=f,line=303)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Equals(rule1, rule2)
# > Two rules are equal if:
# >		- they have the same name
# >		- they have the same description
# > 	- they have the same preconditions
# >		- they have the same actions
# >		- they have the same filepath
# >		- they have the same line
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestComparison(TestRule):
	
	def test_t01 (self):
		""" test rule comparison with two equal rules (default values) """
		self.assertEqualRules(Rule(),Rule())
	
	def test_t02 (self):
		""" test rule comparison with two equal rules (customized values) """
		n = 'rule name'
		d = 'rule description'
		f = 'C:\\rule_file.txt'
		l = 303
		p = Preconditions(self.convertStatements(['a=0','b=a+2'],f,l+2),f,l+1)
		line = p.stmts()[len(p.stmts())-1].line()+2
		a = Actions(self.convertStatements(['a+b-2==0'],f,line+1),f,line)
		self.assertEqualRules(Rule(n,d,p,a,f,l),Rule(n,d,p,a,f,l))

	def test_f00 (self):
		""" assert equality of a rule with another obj that is not a rule """
		self.assertDifferentRules(Rule(),Actions())

	def test_f01 (self):
		""" test rule comparison with two diff rules (diff name) """
		self.assertDifferentRules(Rule(name='1'),Rule(name='2'))

	def test_f02 (self):
		""" test rule comparison with two diff rules (diff desc) """
		self.assertDifferentRules(Rule(desc='1'),Rule(desc='2'))

	def test_f03 (self):
		""" test rule comparison with two diff rules (diff precs) """
		p1 = Preconditions(self.convertStatements(['1'],'',1),'',1)
		p2 = Preconditions(self.convertStatements(['2'],'',1),'',1)
		self.assertDifferentRules(Rule(precs=p1),Rule(precs=p2))

	def test_f04 (self):
		""" test rule comparison with two diff rules (diff acts) """
		a1 = Actions(self.convertStatements(['1'],'',1),'',1)
		a2 = Actions(self.convertStatements(['2'],'',1),'',1)
		self.assertDifferentRules(Rule(acts=a1),Rule(acts=a2))

	def test_f05 (self):
		""" test rule comparison with two diff rules (diff fpath) """
		self.assertDifferentRules(Rule(fpath='1'),Rule(fpath='2'))

	def test_f06 (self):
		""" test rule comparison with two diff rules (diff line) """
		self.assertDifferentRules(Rule(line=1),Rule(line=3))

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Rule Getters:
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Rule.name()
# Rule.description(), Rule.desc()
# Rule.preconditions(), Rule.precs()
# Rule.actions(), Rule.acts()
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestGetters (TestRule):
	
	def test_name (self):
		name = "The Amazing Spiderman"
		rule = Rule(name)
		self.assertEqual(rule.name(),name)

	def test_desc (self):
		desc = \
		"""
		writen by:
			smiling Stan Lee
		"""
		rule = Rule(desc=desc)
		self.assertEqual(rule.description(),desc)
		self.assertEqual(rule.desc(),desc)

	def test_precs (self):
		precs = Preconditions([Statement("spidey = 'Peter Parker'")])
		rule = Rule(precs=precs)
		self.assertEqual(rule.preconditions(),precs)
		self.assertEqual(rule.preconditions(),rule.precs())

	def test_acts (self):
		acts = Actions([Statement("spidey = 'Peter Parker'")])
		rule = Rule(acts=acts)
		self.assertEqual(rule.actions(),acts)
		self.assertEqual(rule.actions(),rule.acts())

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Rule.fire(student, logs)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFire(TestRule):

	def test_i01 (self):
		""" fire rule with errors in the preconditions """
		self.assertFireRaises(RuleError,precs=["x+y==z"])

	def test_i02 (self):
		""" fire rule with errors in the actions """
		self.assertFireRaises(RuleError,acts=["func(l,g)"])

	def test_01 (self):
		""" fire empty rule with no target """
		self.assertFire(precs=None,acts=None)

	def test_02 (self):
		""" fire rule with just preconditions that pass """
		self.assertFire(precs=['x = 10','True'])

	def test_03 (self):
		""" fire rule with just preconditions that fail """
		self.assertFire(precs=['f=lambda x,y:x+y','x=10','f(x,4)>25'],expected=False)

	def test_04 (self):
		""" fire rule with just actions without effects """
		self.assertFire(acts=['x = 33 and 44 or 2','x > 2'])

	def test_05 (self):
		""" fire rule with actions and preconditions without effects """
		self.assertFire(precs=['x=4','y=2'],acts=['x+y%2==0'])

	def test_06 (self):
		""" fire rule with actions and preconditions with effects """
		f = lambda v: Effect(v)
		scope = {'f':f}
		self.assertFire(
			precs=["len('word') > 3"],
			acts=['x = 10','f(x)'],
			scope=scope,
			expected=[10])

	def test_07 (self):
		""" fire empty rule with target """
		self.assertFire("target07",precs=None,acts=None)

	def test_08 (self):
		""" fire rule with just preconditions that pass and a target """
		self.assertFire("target08",precs=['x = 10','True'])

	def test_09 (self):
		""" fire rule with just preconditions that fail and a target """
		self.assertFire("target09",
			precs=['f=lambda x,y:x+y','x=10','f(x,4)>25'],
			expected=False)

	def test_10 (self):
		""" fire rule with just actions without effects and a target """
		self.assertFire("target10",acts=['x = 33 and 44 or 2','x > 2'])

	def test_11 (self):
		""" fire rule with actions and preconditions without effects w/target """
		self.assertFire("target11",precs=['x=4','y=2'],acts=['x+y%2==0'])

	def test_12 (self):
		""" fire rule with actions and preconditions with effects w/target """
		f = lambda v: Effect(v)
		scope = {'f':f}
		self.assertFire(
			"target12",
			precs=["len('word') > 3"],
			acts=['x = 10','f(x)'],
			scope=scope,
			expected=[10])

	def test_13 (self):
		""" test if a target is removed from a rule after failing to trigger a
		a rule that was triggered previously """
		f = "C:\\tests\\rule_tests\\test_rule\\TestFire\\test_XX.txt"
		l = 1002
		precs = ["condition"]
		precs = self.createPreconditionsBlock(self.convert_stmts(precs,f,l),f,l)
		rule = Rule("test_XX","",precs,Actions([],f,l),f,l)
		target = "student73844"
		rule.fire(target,scope={"condition":True})
		rule.fire(target,scope={"condition":False})
		self.assertNotIn(target,rule.targets())
		self.assertRaises(KeyError,rule.target_output,target)

	def test_14 (self):
		""" test if student variable is accessible within the rule """
		scope = {"student":"JJ"}
		self.assertFire("JJ",precs=['len(student)>0'],scope=scope)

	def test_15 (self):
		""" test if logs variable is accessible within the rule """
		scope = {"logs":[1,2,3]}
		self.assertFire(precs=['len(logs)==3'],scope=scope)

	def test_16 (self):
		""" test if exceptions can be thrown inside functions from withing the
		rule """
		def f ():
			raise Exception("MyException")
		self.assertFireRaises(Exception,precs=["f()"],scope={"f":f})

	def test_17(self):
		""" test if the scope that is passed into the rule is not modified """
		scope = {"myvar":2}
		self.assertFire(precs=["myvar = 3"],scope=scope)

	def test_18 (self):
		""" test if the rule ignores an invalid scope argument """
		self.assertFire(scope=Exception(""))

	def test_19 (self):
		""" test if 'targets' variables are accessible inside the rule """
		precs = ["this == target","this in targets"]
		scope = {"targets":["s1","s2"]}
		self.assertFire("s1",precs=precs,scope=scope)


    # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    # STREAKS RULES TESTING
    # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    def test_s01 (self):
        """ fire streak rule with preconditions and actions with effects w/ target """
        f = lambda v: Effect(v)
        scope = {'f':f}
        self.assertFire(
            "target12",
            precs=["len('word') > 3"],
            acts=['x = 10','f(x)'],
            scope=scope,
            expected=[10])


