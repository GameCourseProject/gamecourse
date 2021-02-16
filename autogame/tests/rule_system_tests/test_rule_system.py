#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os

from context import testfiles_path as tp
from context import RuleSystem
from context import Rule
from context import Output, Effect
from context import TargetData
from context import RuleError
from context import course
from course import Award, Prize
from .rs_aux import test_load
from rule_tests.test_basenode import TestBaseNode
cfuncs = course.coursefunctions


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# TestRuleSystem - Abstract Test Class to define generic functions that will
# aid test writting
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestRuleSystem(TestBaseNode):

	def assertLoad(self,folder,name=None):
		if name is None:
			self.assertLoadFolder(folder)
		else:
			self.assertLoadFile(folder,name)

	def assertLoadFile(self,folder,name):
		from context import testfiles_path
		from os.path import join
		import aux_functions
		filename = 'rule_%s.txt' % name
		path = join(testfiles_path,folder,filename)
		function_name = 'get_rule_%s' % name
		expected = eval('aux_functions.%s()' % function_name, locals())
		rs = RuleSystem(autosave=False)
		rs.load(path)
		self.assertRuleSystem(rs,path,expected,
			ignore_fpath=True,ignore_line=True)

	def assertLoadFolder(self,folder):
		from context import testfiles_path
		from os.path import join
		import aux_functions
		if folder == 'empty':
			path = join(testfiles_path,'0 fields')
		else:
			path = join(testfiles_path,folder)
		name = folder.replace(' ','')
		function_name = 'get_%s_rules' % name
		if function_name not in dir(aux_functions):
			msg = "%s doens't exist in module 'aux_functions'" % function_name
			raise NameError(msg)
		expected = eval('aux_functions.%s()' % function_name, locals())
		rs = RuleSystem(path,autosave=False)
		self.assertRuleSystem(rs,path,expected,
			ignore_fpath=True,ignore_line=True)

	def assertFire(self, path, targets=None, facts=None, scope=None,
		expected=None, autosave=False):
		""" asserts if the RuleSystem executes all rules in the given path for
		all given targets """
		rs = RuleSystem(path,autosave)
		result = rs.fire(targets,facts,scope)
		self.assertIsInstance(result,dict)
		if isinstance(expected,dict):
			self.assertEqual(len(list(result.keys())),len(list(expected.keys())))
			for target in list(result.keys()):
				self.assertIsInstance(result[target],dict)
				self.assertIsInstance(expected[target],dict)
				self.assertEqual(len(result[target]),len(expected[target]))
				for rule in list(result[target].keys()):
					self.assertIsInstance(rule,str)
					self.assertIsInstance(result[target][rule],(list,bool))
					self.assertEqual(result[target][rule],expected[target][rule])
		return result

	def assertFireRaises(self, error, path, targets=None, facts=None,
		scope=None):
		""" asserts if the RuleSystem raises the error when executing method
		fire with the given arguments
		"""
		rs  = RuleSystem(path,False)
		self.assertRaises(error,rs.fire,targets,facts,scope)

	def assertLogOutput(self,log,expected):
		self.assertIn(log.target(),list(expected.keys()))
		out = expected[log.target()]
		self.assertEqual(log.output(),out)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# RuleSystem.load(path)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestLoad(TestRuleSystem):
	
	def test_00 (self):
		""" test parse file with empty rule definitions """
		self.assertLoad('0 fields','empty')

	def test_01 (self):
		""" test parse file with rule that have just the name """
		self.assertLoad('1 field','name')

	def test_02 (self):
		""" test parse file with rule that have just the description """
		self.assertLoad('1 field','desc')
		
	def test_03 (self):
		""" test parse file with rule that have just the preconditions """
		self.assertLoad('1 field','precs')

	def test_04 (self):
		""" test parse file with rule that have just the actions """
		self.assertLoad('1 field','acts')

	def test_05 (self):
		""" test parse file with rule that have name and description """
		self.assertLoad('2 fields','nameXdesc')

	def test_06 (self):
		""" test parse file with rule that have name and preconditions """
		self.assertLoad('2 fields','nameXprecs')

	def test_07 (self):
		""" test parse file with rule that have name and actions """
		self.assertLoad('2 fields','nameXacts')

	def test_08 (self):
		""" test parse file with rule that have description and preconditions """
		self.assertLoad('2 fields','descXprecs')

	def test_09 (self):
		""" test parse file with rule that have description and actions """
		self.assertLoad('2 fields','descXacts')

	def test_10 (self):
		""" test parse file with rule that have preconditions and actions """
		self.assertLoad('2 fields','precsXacts')

	def test_11 (self):
		""" test parse file with rule that have name,desc and precs """
		self.assertLoad('3 fields','nameXdescXprecs')

	def test_12 (self):
		""" test parse file with rule that have name,desc and acts """
		self.assertLoad('3 fields','nameXdescXacts')

	def test_13 (self):
		""" test parse file with rule that have name,precs and acts """
		self.assertLoad('3 fields','nameXprecsXacts')

	def test_14 (self):
		""" test parse file with rule that have desc,precs and acts """
		self.assertLoad('3 fields','descXprecsXacts')

	def test_15 (self):
		""" test parse file with rule that have all fields """
		self.assertLoad('all fields','nameXdescXprecsXacts')

	def test_16 (self):
		""" test load a folder with empty files """
		path = os.path.join(tp,"empty_file")
		rs = RuleSystem(path,autosave=False)
		self.assertRuleSystem(rs,path,[],
			ignore_fpath=True,ignore_line=True)

	def test_17 (self):
		""" test load a folder with files that only contain rules with empty
		definitions
		"""
		self.assertLoad('empty')

	def test_18 (self):
		""" test load a folder with files that only contain rules with just
		one of the fields initialized
		"""
		self.assertLoad('1 field')

	def test_19 (self):
		""" test load a folder with files that only contain rules with two
		fields initialized
		"""
		self.assertLoad('2 fields')

	def test_20 (self):
		""" test load a folder with files that only contain rules with three
		fields initialized
		"""
		self.assertLoad('3 fields')

	def test_21 (self):
		""" test load a folder with files that only contain rules with all
		fields initialized
		"""
		self.assertLoad('all fields')

	def test_i01 (self):
		""" test rule_system.load() with an invalid argument type """
		self.assertRaises(TypeError,RuleSystem(autosave=False).load,12421)
		self.assertRaises(TypeError,RuleSystem(autosave=False).load,Exception)
		self.assertRaises(TypeError,RuleSystem(autosave=False).load,lambda x:x)

	def test_i02 (self):
		""" test rule_system.load() with an invalid path """
		from context import PathError
		self.assertRaises(PathError,RuleSystem(autosave=False).load,'C:\\invalid\\path')

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# RuleSystem.fire(path)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFire (TestRuleSystem):

	def test_00 (self):
		""" test fire rule system with no rules loaded """
		self.assertFire(None)

	def test_01 (self):
		""" fire rules from folder: 'empty_file' """
		path = os.path.join(tp,"empty_file")
		self.assertFire(path)

	def test_02 (self):
		""" fire rules from folder: '0 fields' """
		path = os.path.join(tp,"0 fields")
		self.assertFire(path)

	def test_03 (self):
		""" fire rules from folder: '1 field' """
		path = os.path.join(tp,"1 field")
		self.assertFire(path)

	def test_04 (self):
		""" fire rules from folder: '2 fields' """
		path = os.path.join(tp,"2 fields")
		self.assertFire(path)

	def test_05 (self):
		""" fire rules from folder: '3 fields' """
		path = os.path.join(tp,"3 fields")
		self.assertFire(path)

	def test_06 (self):
		""" fire rules from folder: 'all fields' """
		path = os.path.join(tp,"all fields")
		self.assertFire(path)

	def test_07 (self):
		""" fire rules with custom funtions """
		somatorio = lambda l: sum(l)
		path = os.path.join(tp,"withfunctions","rule1.txt")
		self.assertFire(path,scope={'somatorio':somatorio})

	def test_08 (self):
		""" fire rules with using global variables """
		path = os.path.join(tp,"withfunctions","rule2.txt")
		self.assertFire(path,targets=["s1","s2"],scope={"logs":{}})

	def test_09 (self):
		""" fire rules with custom funtions using global variables """
		students = ["t1"]
		student = "t1"
		logs = ["log1"]
		f = lambda : "%s%s%s" % (students,student,logs)
		path = os.path.join(tp,"withfunctions","rule3.txt")
		self.assertFire(path,scope={'f':f})

	def test_10 (self):
		""" fire rules with custom funtions producing effects """
		f = lambda x,y,z: Effect((len("%s%s" % (x,y)), len(z)))
		path = os.path.join(tp,"withfunctions","rule4.txt")
		targets = ["s1","s22","s333"]
		logs = list(range(8))
		expected = {}
		for t in targets: expected[t] = {"R4": [f(t,targets,logs).val()]}
		self.assertFire(path,targets,logs,{"f":f},expected)

	def test_11 (self):
		""" fire rules with custom funtions from 'functions' module """
		path = os.path.join(tp,"withfunctions","rule5.txt")
		self.assertFire(path)

	def test_12 (self):
		""" fire rules with custom funtions from 'functions' module """
		path = os.path.join(tp,"withfunctions","rule6.txt")
		targets = ["s%d" % i for i in range(5)]
		expected = {}
		for t in targets: expected[t] = {"R6": [t]}
		self.assertFire(path,targets,expected=expected)

	def test_13 (self):
		""" fire rule system rules with an invalid scope argument """
		path = os.path.join(tp,"withfunctions","rule6.txt")
		self.assertFire(path,scope=Exception("kaboom"))

	def test_14 (self):
		""" test if the result rulesystem.fire() includes previous targets
		that activated rules but weren't targets in the last execution
		"""
		path = os.path.join(tp,"withfunctions","rule6.txt")
		targets = ["s%d" % i for i in range(10)]
		expected = {}
		for t in targets[0:5]: expected[t] = {"R6": [t]}
		self.assertFire(path,targets[0:5],expected=expected,autosave=True)
		for t in targets[5:]: expected[t] = {"R6": [t]}
		self.assertFire(path,targets,expected=expected)

	def test_15 (self):
		""" test if target that no longer activates any rule of the rulesystem
		still belongs in the targets of the rulesystem
		"""
		path = os.path.join(tp,"withfunctions","rule7.txt")
		targets = ["t1"]
		def f(t): return True if t == "t1" else False
		expected = {"t1":{"R7":["success"]}}
		self.assertFire(path,targets,scope={'f':f},expected=expected,autosave=True)
		def f(t): return False
		self.assertFire(path,targets,scope={'f':f},expected={})

	def test_16 (self):
		""" test if functions in the functions folder of the path are correctly
		imported into the rulesystem
		"""
		path = os.path.join(tp,"withfunctions","rule8.txt")
		targets = ["t1"]
		expected = {"t1":{"R8":[1,24]}}
		self.assertFire(path,targets,expected=expected,autosave=True)
		self.assertFire(path,targets,expected=expected)

	def test_17 (self):
		""" test if decorator functions 'rule_effect' and 'rule_function' are
		not allowed in the rules
		"""
		path = os.path.join(tp,"withfunctions","rule9.txt")
		self.assertFireRaises(RuleError,path)

	def test_18 (self):
		""" test if rules allow object creation in rules """
		path = os.path.join(tp,"withfunctions","rule10.txt")
		targets = ["joao","ana"]
		expected = {"joao":{"R10(M)":["male"]},"ana":{"R10(F)":["female"]}}
		self.assertFire(path,targets,expected=expected)

	def test_19 (self):
		""" test if decorator functions 'odd', 'even' and 'determine_gender'
		from module "human.py" are not allowed in the rules
		"""
		path = os.path.join(tp,"withfunctions","rule11.txt")
		self.assertFireRaises(RuleError,path)

	def test_20 (self):
		""" test if built-in function "rule_unlocked" is working """
		path = os.path.join(tp,"withfunctions","rule12.txt")
		targets = ["t1","t2"]
		expected = {}
		expected["t1"] = {"R12-1":["R12-1"],"R12-2":["R12-2"]}
		expected["t2"] = {"R12-1":["R12-1"],"R12-2":["R12-2"]}
		self.assertFire(path,targets,expected=expected)

	def test_21 (self):
		""" test if built-in function "effect_unlocked" is working """
		path = os.path.join(tp,"withfunctions","rule13.txt")
		targets = ["t1"]
		expected = {"t1":{"R13-1":["R13-1"],"R13-2":["R13-2"]}}
		self.assertFire(path,targets,expected=expected)

	def test_22 (self):
		""" test if a user function that produces an effect, works properly """
		from auxiliar import Award
		path = os.path.join(tp,"withfunctions","rule14.txt")
		targets = ["t1"]
		award = Award("t1","RuleMaster",3)
		expected = {"t1":{"R14":[award]}}
		self.assertFire(path,targets,expected=expected,autosave=True)
		expected = {"t1":{"R14":[award]}}
		self.assertFire(path,targets,expected=expected)

	def test_23 (self):
		""" test if builtin functions 'eval' and 'compile' are ignored """
		from auxiliar import Award
		path = os.path.join(tp,"withfunctions","rule15.txt")
		targets = ["t1"]
		expected = {"t1":{"R15":[Award("t1","Security Engineer",1)]}}
		self.assertFire(path,targets,expected=expected)

	def test_24 (self):
		""" test if an output is completly removed for a target that no longer
		activates a rule
		"""
		path = os.path.join(tp,"withfunctions","rule16.txt")
		targets = ["t1"]
		expected = {"t1":{"R16":["R16"]}}
		self.assertFire(path,targets,expected=expected,autosave=True)
		self.assertFire(path,targets,expected={},autosave=True)

	def test_25 (self):
		""" test if the output is updated for rules that can return different
		output for the same target and test if an output is completly removed
		for a target that no longer activates a rule
		"""
		path = os.path.join(tp,"withfunctions","rule17.txt")
		targets = ["t1"]
		expected = {"t1":{"R17":[1]}}
		self.assertFire(path,targets,expected=expected,autosave=True)
		expected = {"t1":{"R17":[2,1]}}
		self.assertFire(path,targets,expected=expected,autosave=True)
		self.assertFire(path,targets,expected={},autosave=True)
		expected = {"t1":{"R17":[1]}}
		self.assertFire(path,targets,expected=expected)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# This section is merely for convenience. This is just the continuation of
# TestFire class, but these unittests will be validating functions from the
# course package. This way we can run solely these specific tests without
# running the others
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFire2 (TestRuleSystem):

	def gen_awards(self, achievement, lvl, student, xp, info=None):
		a = achievement
		s = student
		awards = []
		for i in range(lvl):
			x = xp[i] if isinstance(xp,(tuple,list)) else xp
			awards.append(Award(s,a,i+1,x,True,0,info))
		return {student: awards}
		
	def gen_gradelogs(self, num, xp=None, info=None):
		class Log:
			def __init__(self,xp,info):
				self.xp = xp
				self.info = info
			def __eq__(self,other):
				return isinstance(other,Log) \
				and self.xp == other.xp \
				and self.info == other.info
			def __ne__(self,other):
				return not self == other
		return [Log(xp if xp else i,info if info else str(i)) for i in range(num)]

	def test_26 (self):
		""" test award_achievement function: test if the function outputs a
		prize with the correct awards and indicators
		"""
		path = os.path.join(tp,"withcoursefunctions","rule01.txt")
		targets = ["s1"]
		indicators = {"s1":{"Post Master":"contributions?"}}
		p = Prize({"s1":[Award("s1","Post Master",1,0,True,0,None)]},indicators)
		expected = {"s1":{"R1":[p]}}
		self.assertFire(path,targets,expected=expected)

	def test_27 (self):
		""" test award_achievement function: test if contributions are correctly
		tranformed into indicators
		"""
		path = os.path.join(tp,"withcoursefunctions","rule02.txt")
		targets = ["s1"]
		facts = {"s1":(1,2,3)}
		indicators = {"s1":{"Tree Climber":(1,2,3)}}
		p = Prize({"s1":[Award("s1","Tree Climber",1,0,True,0,None)]},indicators)
		expected = {"s1":{"R2":[p]}}
		self.assertFire(path,targets,facts,expected=expected)

	def test_28 (self):
		""" test award_achievement function: test if awarding for level 2
		automatically unlocks level 1
		"""
		path = os.path.join(tp,"withcoursefunctions","rule03.txt")
		targets = ["s1"]; facts = {"s1":"x"}
		indicators = {"s1":{"Lab Lover":"x"}}
		awards = self.gen_awards("Lab Lover",2,"s1",(50,50))
		p = Prize(awards,indicators)
		expected = {"s1":{"R3":[p]}}
		self.assertFire(path,targets,facts,expected=expected)

	def test_29 (self):
		""" test award_achievement function: test if awarding for level 3
		automatically unlocks levels 2 and 1
		"""
		path = os.path.join(tp,"withcoursefunctions","rule04.txt")
		targets = ["s1"]; facts = {"s1":"x"}
		indicators = {"s1":{"Lab Lover":"x"}}
		awards = self.gen_awards("Lab Lover",3,"s1",(50,50,50))
		p = Prize(awards,indicators)
		expected = {"s1":{"R4":[p]}}
		self.assertFire(path,targets,facts,expected=expected)

	def test_30 (self):
		""" test award_achievement function: test if info is correctly passed
		into the awards
		"""
		path = os.path.join(tp,"withcoursefunctions","rule05.txt")
		targets = ["s1"]; facts = {"s1":(1,2,3)}
		indicators = {"s1":{"Course Emperor":(1,2,3)}}
		awards = {"s1":[Award("s1","Course Emperor",1,0,True,0,"myinfo")]}
		p = Prize(awards,indicators)
		expected = {"s1":{"R5":[p]}}
		self.assertFire(path,targets,facts,expected=expected)

	def test_31 (self):
		""" test award_achievement function: test if RuleError is thrown when
		trying to award with an unexisting award
		"""
		path = os.path.join(tp,"withcoursefunctions","rule06.txt")
		targets = ["s1"]
		self.assertFireRaises(RuleError,path,targets)

	def assertTransformedOutput (self,output,expected):
		awards, indicators = output
		# assert number of awards in both outputs
		self.assertEqual(len(awards),len(expected[0]))
		# assert if the awards are equal in both outputs
		self.assertEqual(awards,expected[0])
		# assert number of indicators
		self.assertEqual(len(indicators),len(expected[1]))
		# assert if the indicators are equal
		for target in indicators:
			# assert if targets exist in both indicators
			self.assertIn(target,expected[1])
			# assert if each target has the same number of indicators
			tindicators = indicators[target]
			eindicators = expected[1][target]
			self.assertEqual(len(tindicators),len(eindicators))
			for achievement in tindicators:
				self.assertIn(achievement,eindicators)
				self.assertEqual(len(tindicators[achievement]),2)
				self.assertEqual(len(eindicators[achievement]),2)
				val1 = tindicators[achievement][0]
				val2 = eindicators[achievement][0]
				self.assertEqual(val1,val2)
				lines1 = tindicators[achievement][1]
				lines2 = eindicators[achievement][1]
				self.assertEqual(lines1,lines2)


	def test_32 (self):
		""" test award_achievement & transform_rulesystem_output functions:
		test if all awards and indicators created from award_achievement are
		retrived from the output (ONLY the awards and the indicators)
		"""
		path = os.path.join(tp,"withcoursefunctions","rule07.txt")
		logs = ["l%s" % i for i in range(2)]
		targets = ["s1"]; facts = {"s1": logs}
		indicators_1 = {"s1": {"Golden Star":("R7-1",logs)}}
		awards_1 = self.gen_awards("Golden Star",3,"s1",100,"R7-1")
		p1 = Prize(awards_1,indicators_1)
		indicators_2 = {"s1": {"Replier Extraordinaire":("R7-2",logs)}}
		awards_2 = {"s1":[Award("s1","Replier Extraordinaire",1,50,True,0,"R7-2")]}
		p2 = Prize(awards_2,indicators_2)
		expected = {"s1":{"R7_1":[p1,"ignore"],"R7_2":["ignore",p2]}}
		output = self.assertFire(path,targets,facts,expected=expected)
		p1.join(p2)
		awards = []
		for k in p1.awards: awards += p1.awards[k]
		expected = awards, p1.indicators
		output = cfuncs.transform_rulesystem_output(output)
		self.assertTransformedOutput(output,expected)

	def test_33 (self):
		""" test award_grade function: test award grade 1 time with 500 XP """
		path = os.path.join(tp,"withcoursefunctions","rule08.txt")
		targets = ["s1"]; facts = {"s1":(1,2,3)}
		indicators = {}
		awards = {"s1":[Award("s1","Initial Bonus",0,500,False,0,"")]}
		p = Prize(awards,indicators)
		expected = {"s1":{"R8":[p]}}
		output = self.assertFire(path,targets,facts,expected=expected)
		awards = []
		for k in p.awards: awards += p.awards[k]
		expected = awards, indicators
		self.assertEqual(cfuncs.transform_rulesystem_output(output),expected)

	def test_34 (self):
		""" test award_grade function: test award grade 5 times with default XP
		"""
		path = os.path.join(tp,"withcoursefunctions","rule09.txt")
		targets = [73844]
		indicators = {}
		awards = [Award(73844,"Random",0,0,False,0,"hello") for i in range(3)]
		awards = {73844: awards}
		p = Prize(awards,indicators)
		expected = {73844:{"R9":[p]}}
		output = self.assertFire(path,targets,expected=expected)

	def test_35 (self):
		""" test award_grade function: test award grade with 1 contributions and
		no XP or INFO
		"""
		path = os.path.join(tp,"withcoursefunctions","rule10.txt")
		targets = ["s1"]; facts = {"s1": self.gen_gradelogs(2)}
		indicators = {}
		awards = [Award("s1","Random",0,i,False,0,str(i)) for i in range(2)]
		awards = {"s1": awards}
		p = Prize(awards,indicators)
		expected = {"s1": {"R10": [p]}}
		output = self.assertFire(path,targets,facts,expected=expected)

	def test_36 (self):
		""" test award_grade function: test award grade with 2 contributions and
		XP but without INFO
		"""
		path = os.path.join(tp,"withcoursefunctions","rule11.txt")
		targets = ["s1"]; facts = {"s1": self.gen_gradelogs(2,50)}
		indicators = {}
		awards = [Award("s1","Random",0,50,False,0,str(i)) for i in range(2)]
		awards = {"s1": awards}
		p = Prize(awards,indicators)
		expected = {"s1": {"R11": [p]}}
		output = self.assertFire(path,targets,facts,expected=expected)

	def test_37 (self):
		""" test award_grade function: test award grade with 3 contributions and
		no XP but with INFO
		"""
		path = os.path.join(tp,"withcoursefunctions","rule12.txt")
		targets = ["s1"]; facts = {"s1": self.gen_gradelogs(3,info="hello")}
		indicators = {}
		awards = [Award("s1","Random",0,i,False,0,"hello") for i in range(3)]
		awards = {"s1": awards}
		p = Prize(awards,indicators)
		expected = {"s1": {"R12": [p]}}
		output = self.assertFire(path,targets,facts,expected=expected)

	def test_38 (self):
		""" test award tree skill: kinectic with a student with no logs """
		path = os.path.join(tp,"withcoursefunctions","rule13.txt")
		targets = ["s1"]; facts = {"s1":(1,2,3)}
		indicators = {}
		awards = {"s1":[Award("s1","Skill Tree",0,750,False,0,"Kinetic")]}
		p = Prize(awards,indicators)
		expected = {"s1":{"R13":[p]}}
		output = self.assertFire(path,targets,facts,expected=expected)
		awards = []
		for k in p.awards: awards += p.awards[k]
		expected = awards, indicators
		self.assertEqual(cfuncs.transform_rulesystem_output(output),expected)

	def test_39 (self):
		""" test award tree skill: Podcast with a student with matching logs """
		path = os.path.join(tp,"withcoursefunctions","rule14.txt")
		logs = self.gen_gradelogs(1,3,("Skill Tree","Podcast"))
		targets = ["s1"]; facts = {"s1": logs}
		indicators = {"s1": {"Podcast": (3, [logs[0]]) }}
		awards = {"s1":[Award("s1","Skill Tree",0,150,False,0,"Podcast")]}
		p = Prize(awards,indicators)
		expected = {"s1":{"R14":[p]}}
		output = self.assertFire(path,targets,facts,expected=expected)
		awards = []
		for k in p.awards: awards += p.awards[k]
		expected = awards, indicators
		self.assertEqual(cfuncs.transform_rulesystem_output(output),expected)

	def test_40 (self):
		""" test award tree skill: Podcast with a student with no matching logs """
		path = os.path.join(tp,"withcoursefunctions","rule14.txt")
		logs = self.gen_gradelogs(10,5,("Skill Tree","Kinetic"))
		targets = ["s1"]; facts = {"s1": logs}
		indicators = {}
		awards = {"s1":[Award("s1","Skill Tree",0,150,False,0,"Podcast")]}
		p = Prize(awards,indicators)
		expected = {"s1":{"R14":[p]}}
		output = self.assertFire(path,targets,facts,expected=expected)
		awards = []
		for k in p.awards: awards += p.awards[k]
		expected = awards, indicators
		self.assertEqual(cfuncs.transform_rulesystem_output(output),expected)

	def test_41 (self):
		""" test award tree skill: Podcast with a student with some matching logs """
		path = os.path.join(tp,"withcoursefunctions","rule14.txt")
		logs = self.gen_gradelogs(2,4,("Skill Tree","Kinetic"))
		logs += self.gen_gradelogs(4,3,("Skill Tree","Podcast"))
		logs += self.gen_gradelogs(5,1,("Skill Tree","Alien Invasion"))
		targets = ["s1"]; facts = {"s1": logs}
		indicators = {"s1": {"Podcast": (3, [logs[4]]) }}
		awards = {"s1":[Award("s1","Skill Tree",0,150,False,0,"Podcast")]}
		p = Prize(awards,indicators)
		expected = {"s1":{"R14":[p]}}
		output = self.assertFire(path,targets,facts,expected=expected)
		awards = []
		for k in p.awards: awards += p.awards[k]
		expected = awards, indicators
		self.assertEqual(cfuncs.transform_rulesystem_output(output),expected)

	def test_42 (self):
		""" using satisfied_skill and award_treeskill test if a student with no
		contributions on "eBook" is NOT awarded """
		path = os.path.join(tp,"withcoursefunctions","rule15.txt")
		logs = self.gen_gradelogs(5,1,("Skill Tree","Alien Invasion"))
		targets = ["s1"]; facts = {"s1": logs}
		expected = {}
		self.assertFire(path,targets,facts,expected=expected)

	def test_43 (self):
		""" using satisfied_skill and award_treeskill test if a student with
		contributions on the prequesites for "Pixel Art" and no contributions on
		"Pixel Art", is NOT awarded """
		path = os.path.join(tp,"withcoursefunctions","rule16.txt")
		logs = self.gen_gradelogs(1,3,("Skill Tree","Course Logo"))
		logs += self.gen_gradelogs(1,5,("Skill Tree","Podcast"))
		targets = ["s1"]; facts = {"s1": logs}
		expected = {}
		self.assertFire(path,targets,facts,expected=expected)

	def test_44 (self):
		""" using satisfied_skill and award_treeskill test if a student with
		contributions for "Pixel Art" and no contributions for its prequisites
		is NOT awarded """
		path = os.path.join(tp,"withcoursefunctions","rule16.txt")
		logs = self.gen_gradelogs(1,4,("Skill Tree","Pixel Art"))
		targets = ["s1"]; facts = {"s1": logs}
		expected = {}
		self.assertFire(path,targets,facts,expected=expected)

	def test_45 (self):
		""" using satisfied_skill and award_treeskill test if a student with
		contributions for "Pixel Art" and no contributions for one of its
		prequisites, is NOT awarded 
		In the 19/20 skill tree: Pixel Art = Podcast + Course Logo """
		path = os.path.join(tp,"withcoursefunctions","rule16.txt")
		logs = self.gen_gradelogs(1,3,("Skill Tree","Podcast"))
		logs += self.gen_gradelogs(1,5,("Skill Tree","Pixel Art"))
		targets = ["s1"]; facts = {"s1": logs}
		expected = {}
		self.assertFire(path,targets,facts,expected=expected)

	def test_46 (self):
		""" using satisfied_skill and award_treeskill test if a student with
		contributions for "Pixel Art" and no contributions for one of its
		prequisites, is NOT awarded 
		In the 19/20 skill tree: Pixel Art = Podcast + Course Logo """
		path = os.path.join(tp,"withcoursefunctions","rule16.txt")
		logs = self.gen_gradelogs(1,3,("Skill Tree","Course Logo"))
		logs += self.gen_gradelogs(1,5,("Skill Tree","Pixel Art"))
		targets = ["s1"]; facts = {"s1": logs}
		expected = {}
		self.assertFire(path,targets,facts,expected=expected)

	def test_47 (self):
		""" using satisfied_skill and award_treeskill test if a student with
		contributions for "Pixel Art" and its prequisites, is awarded """
		path = os.path.join(tp,"withcoursefunctions","rule16.txt")
		logs = self.gen_gradelogs(1,4,("Skill Tree","Podcast"))
		logs += self.gen_gradelogs(1,3,("Skill Tree","Course Logo"))
		logs += self.gen_gradelogs(1,5,("Skill Tree","Pixel Art"))
		targets = ["s1"]; facts = {"s1": logs}
		indicators = {"s1": {"Pixel Art": (5, [logs[2]]) }}
		awards = {"s1":[Award("s1","Skill Tree",0,400,False,0,"Pixel Art")]}
		p = Prize(awards,indicators)
		expected = {"s1":{"R16":[p]}}
		output = self.assertFire(path,targets,facts,expected=expected)
		awards = []
		for k in p.awards: awards += p.awards[k]
		expected = awards, indicators
		self.assertEqual(cfuncs.transform_rulesystem_output(output),expected)