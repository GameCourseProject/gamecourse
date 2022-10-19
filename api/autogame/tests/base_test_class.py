#!/usr/bin/env python
# -*- coding: utf-8 -*-

import unittest
import os



# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# BaseTestClass - The most Abstract Test Class to define generic functions
# that will aid test writting
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class BaseTestClass(unittest.TestCase):

	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# GAMERULES Assert Functions
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	def assertBaseNode(self, node, fpath, line,
		ignore_fpath=False,ignore_line=False):
		from context import BaseNode
		# Assert the node instances types
		self.assertIsInstance(node,BaseNode)
		self.assertIsInstance(node.file(),str)
		self.assertIsInstance(node.line(),int)
		# Assert the test arguments types
		self.assertIsInstance(fpath,str)
		self.assertIsInstance(line,int)
		# Assert values
		if ignore_fpath is False:
			self.assertEqual(node.file(),fpath)
		if ignore_line is False:
			self.assertEqual(node.line(),line)

	def assertBlock(self, block, stmts, fpath, line,
		ignore_fpath=False,ignore_line=False):
		from context import Block, Statement
		# Assert BaseNode related values
		self.assertBaseNode(block,fpath,line,ignore_fpath,ignore_line)
		# Assert instances
		self.assertIsInstance(block,Block)
		# Assert Statements
		self.assertIsInstance(block.stmts(),list)
		self.assertIsInstance(stmts,list)
		# self.assertIsNot(block.stmts(),stmts)
		self.assertEqual(len(block.stmts()),len(stmts))
		if ignore_fpath is False and ignore_line is False:
			self.assertEqual(block.stmts(),stmts)
		for i in range(len(block.stmts())):
			stmt = block.stmts()[i]
			self.assertIsInstance(stmt,Statement)
			self.assertStatement(stmt,
				stmts[i].text(),stmts[i].file(),stmts[i].line(),
				ignore_fpath,ignore_line)

	def assertDataManager(self, dm):
		""" asserts if the given obj is a valid instance of DataManager """
		from context import DataManager
		self.assertIsInstance(dm,DataManager)
		self.assertIsInstance(dm.rules,(type(None),list))
		self.assertPathData(dm.paths)

	def assertFPaths(self, fp, expected=None):
		from context import FPaths
		self.assertIsInstance(fp,FPaths)
		if expected is not None:
			self.assertIsInstance(expected,dict)
		for p in fp:
			self.assertIsInstance(p,str)
			self.assertIn(p,fp.paths())
			if expected is None:
				self.assertModuleFunctions(fp[p])
			else:
				self.assertIn(p,list(expected.keys()))
				module = expected[p][0]
				functions = expected[p][1]
				self.assertModuleFunctions(fp[p],module,functions)

	def assertEffect(self,effect):
		from context import Effect
		self.assertIsInstance(effect,Effect)

	def assertListRules(self, result, expected,	ignore_fpath=False, ignore_line=False):
		""" assert two list of rules """
		self.assertIsInstance(result,list)
		self.assertIsInstance(expected,list)
		self.assertEqual(len(result),len(expected))

		for i in range(len(result)):
			rule1 = result[i]
			rule2 = expected[i]
			n = rule2.name()
			d = rule2.desc()
			p = rule2.precs()
			a = rule2.acts()
			f = rule2.file()
			l = rule2.line()
			self.assertRule(rule1,n,d,p,a,f,l,ignore_fpath,ignore_line)


	def assertModuleFunctions(self, mf, m=None, funcs=None):
		from context import ModuleFunctions
		self.assertIsInstance(mf,ModuleFunctions)
		self.assertIsInstance(mf.module,str)
		if m is not None:
			self.assertIsInstance(m,str)
			self.assertEqual(m,mf.module)
		self.assertIsInstance(mf.functions,list)
		if funcs is not None:
			self.assertIsInstance(funcs,list)
			self.assertEqual(len(mf.functions),len(funcs))
		for f in mf.functions:
			self.assertIsInstance(f,str)
			self.assertIn(f,funcs)

	def assertOutput(self,output):
		from context import Output
		self.assertIsInstance(output,Output)
		converted = [e.val() for e in output.effects()]
		self.assertEqual(len(output.convert()),len(converted))
		for e in output.effects():
			self.assertEffect(e)
			self.assertIn(e.val(),converted)

	def assertPath(self,path):
		""" asserts if a path is valid or not """
		self.assertIsInstance(path,str)
		self.assertTrue(os.path.exists(path),"path doesn't exist: " + path)

	def assertPathData(self,pd):
		""" asserts an instance of PathData """
		from context import PathData
		self.assertIsInstance(pd,PathData)
		self.assertIsInstance(pd.next,int)
		self.assertIsInstance(pd.unused,list)
		self.assertIsInstance(pd.indexpaths,dict)
		self.assertIsInstance(pd.datapaths,dict)
		self.assertIsInstance(pd.mtime,dict)
		self.assertGreater(pd.next,0)
		self.assertLess(len(pd.indexpaths),pd.next)
		self.assertEqual(len(pd.indexpaths),len(pd.datapaths))
		self.assertEqual(len(pd.datapaths),len(pd.mtime))
		for u in pd.unused:
			self.assertIsInstance(u,int)
			self.assertGreater(u,0)
			# since the unused values are to fix 'holes' in the sequence
			# they must be smaller than the highest next value
			self.assertLess(u,pd.next)
			# can only be one repetition of each value
			self.assertEqual(pd.unused.count(u),1)
		self.assertEqual(list(pd.indexpaths.keys()),list(pd.datapaths.keys()))
		for k in list(pd.indexpaths.keys()):
			# each key must be a path, hence a string
			# self.assertIsInstance(k,basestring)
			self.assertPath(k)
			value = pd.get_index(k)
			# each path must point to a positive integer, which cannot be an
			# available index
			self.assertIsInstance(value,int)
			self.assertGreater(value,0)
			self.assertLess(value,pd.next)
			self.assertNotIn(value,pd.unused)
			# each datapath must be valid datapath
			datapath = pd.get_datapath(k)
			self.assertIsInstance(datapath,str)
			self.assertIn(str(value),os.path.basename(datapath))
			# each time stamp must be a floating point time
			timestamp = pd.get_mtime(k)
			self.assertIsInstance(timestamp,float)

	def assertRule(self,rule,name,desc,precs,acts,fpath,line,
		ignore_fpath=False,ignore_line=False):
		from context import Rule, Preconditions, Actions
		# Assert BaseNode related values
		self.assertBaseNode(rule,fpath,line,ignore_fpath,ignore_line)
		# Assert name
		self.assertIsInstance(rule.name(),str)
		self.assertEqual(rule.name(),name)
		# Assert description
		self.assertIsInstance(rule.desc(),str)
		self.assertEqual(rule.desc(),desc)
		# Assert preconditions
		self.assertBlock(rule.precs(), precs.stmts(),precs.file(),precs.line(),ignore_fpath,ignore_line)
		self.assertIsInstance(rule.precs(),Preconditions)
		if ignore_fpath is False and ignore_line is False:
			self.assertEqual(rule.precs(),precs)
		self.assertEqual(rule.precs().file(),rule.file())
		self.assertGreaterEqual(rule.precs().line(),rule.line())
		# Assert actions
		self.assertBlock(rule.acts(),
			acts.stmts(),acts.file(),acts.line(),ignore_fpath,ignore_line)
		self.assertIsInstance(rule.acts(),Actions)
		if ignore_fpath is False and ignore_line is False:
			self.assertEqual(rule.acts(),acts)
		self.assertEqual(rule.acts().file(),rule.file())
		self.assertGreaterEqual(rule.acts().line(),rule.line())

	def assertRuleLog(self,log):
		from context import RuleLog, Rule, Output
		self.assertIsInstance(log,RuleLog)
		r = log.rule()
		self.assertRule(r,r.name(),r.desc(),r.precs(),r.acts(),r.path(),r.line())
		if not isinstance(log.output(),bool):
			self.assertOutput(log.output())
		self.assertIsInstance(log.timestamp(),float)
		self.assertGreaterEqual(log.timestamp(),0)

	def assertRuleSystem(self, rs, path, rules,
		ignore_fpath=False,ignore_line=False):
		""" assert a rules system instance with the expected path and rules """
		from context import RuleSystem
		self.assertIsInstance(rs,RuleSystem)
		self.assertEqual(rs.path(),path)
		self.assertListRules(rs.rules(),rules,ignore_fpath,ignore_line)

	def assertStatement(self, stmt, text, fpath, line,
		ignore_fpath=False,ignore_line=False):
		from context import Statement
		# Assert BaseNode related values
		self.assertBaseNode(stmt,fpath,line,ignore_fpath,ignore_line)
		self.assertIsInstance(stmt,Statement)
		self.assertIsInstance(stmt.text(),str)
		self.assertEqual(stmt.text(),text)
		self.assertEqual(type(stmt.code()).__name__,'code')

	def assertStatements (self, block):
		self.assertIsInstance(block.stmts(),list)
		from context import Statement
		for s in block.stmts():
			self.assertIsInstance(s, Statement)
			self.assertEqual(s.file(),block.file())
			self.assertTrue(s.line() >= block.line())

	def assertTargetData(self,td):
		""" asserts if an obj is a valid instance of TargetData or not """
		from context import TargetData
		self.assertIsInstance(td,TargetData)
		target_data = td.get_target_data()
		self.assertIsInstance(target_data,dict)
		# every output should be an Output,
		# should be in target_outputs, rule_outputs AND target_ruleoutput
		for o in td.outputs():
			self.assertOutput(o)
			self.assertIn(o,[o for l in td._to.values() for o in l])
			self.assertIn(o,td._tro.values())
		# every target should be in target_outputs AND target_rules
		# AND target_ruleoutput
		tro_targets = set([tr[0] for tr in list(td._tro.keys())])
		for t in td.targets():
			self.assertIn(t,list(td._to.keys()))
			self.assertIn(t,list(td._tr.keys()))
			self.assertIn(t,tro_targets)
			self.assertIn(t,list(target_data.keys()))
			self.assertTrue(td.target_exists(t))
			self.assertIsInstance(target_data[t],dict)
			self.assertEqual(len(td.target_rules(t)),len(list(target_data[t].keys())))
			# if a rule has target it should also have an output
			num_rulesANDoutputs = 0
			for r in td.target_rules(t):
				k = td.rule_key(r)
				num_rulesANDoutputs+=1
				self.assertTrue(td.target_hasrule(t,r))
				o = td.target_ruleoutput(t,r)
				self.assertOutput(o)
				self.assertTrue(k in target_data[t])
				self.assertEqual(target_data[t][k],o.convert())
			self.assertEqual(num_rulesANDoutputs,len(td.target_rules(t)))
			self.assertEqual(num_rulesANDoutputs,len(td.target_outputs(t)))
		# every rule in the TargetData MUST be associated with at least 1 target
		for r in td.rules():
			self.assertTrue(td.rule_exists(r))
			self.assertGreaterEqual(len(td.rule_targets(r)),1)

	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# COURSE Assert Functions
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	def assertAchievement(self,a):
		from course import Achievement
		self.assertIsInstance(a,Achievement)
		self.assertIn("name",dir(a))
		self.assertIn("description",dir(a))
		self.assertIn("criteria",dir(a))
		self.assertIn("xp",dir(a))
		self.assertIn("extra",dir(a))
		self.assertIn("is_counted",dir(a))
		self.assertIn("is_postbased",dir(a))
		self.assertIsInstance(a.name,str)
		self.assertIsInstance(a.description,str)
		self.assertEqual(len(a.criteria),len(a.xp))
		for i in range(len(a.criteria)):
			self.assertIsInstance(a.criteria[i],str)
			self.assertIsInstance(a.xp[i],int)
			self.assertGreaterEqual(a.xp[i],0)
			self.assertIsInstance(a.extra[i],bool)
		self.assertIsInstance(a.is_counted(),bool)
		self.assertIsInstance(a.is_postbased(),bool)
		self.assertEqual(a.unrewarded(), a.xp[0] == 0)
		self.assertGreaterEqual(a.top_level(),1)
		self.assertLessEqual(a.top_level(),3)

	def assertAward(self,a):
		from context import course
		self.assertIsInstance(a,course.Award)
		self.assertIn("student",dir(a))
		self.assertIn("achievement",dir(a))
		self.assertIn("level",dir(a))
		self.assertIn("xp",dir(a))
		self.assertIn("badge",dir(a))
		self.assertIn("timestamp",dir(a))
		self.assertIn("info",dir(a))

	def assertPreCondition (self, prec):
		from course import PreCondition
		self.assertIsInstance(prec,PreCondition)
		self.assertIn("nodes",dir(prec))

	def assertStudent(self,s):
		from course import Student
		self.assertIsInstance(s,Student)
		self.assertIn("num",dir(s))
		self.assertIn("name",dir(s))
		self.assertIn("email",dir(s))
		self.assertIn("campus",dir(s))
		self.assertIsInstance(hash(s),int)
		# encoding fix
		#name = s.name.encode("latin-1")
		#email = s.email.encode("latin-1")
		#campus = s.campus.encode("latin-1")
		name = str(s.name)
		email = str(s.email)
		campus = str(s.campus)

		student_tostring = "%s,%s,%s,%s" % (s.num,name,email,campus)
		student_representation = "Student(%s)" % str(s)
		self.assertEqual(student_tostring,str(s))
		self.assertEqual(student_representation,repr(s))
		self.assertEqual(s,Student(s.num,"","",""))
		num = str(int(s.num)+33)
		self.assertNotEqual(s,Student(num,s.name,s.email,s.campus))

	def assertTreeAward(self, ta):
		from course import TreeAward
		self.assertIsInstance(ta,TreeAward)
		self.assertIn("name",dir(ta))
		self.assertIn("PCs",dir(ta))
		self.assertIn("level",dir(ta))
		self.assertIn("color",dir(ta))
		self.assertIn("xp",dir(ta))

	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# other test functions
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	def capcombos (self,word):
		''' Return a list with all the combinations of
		upper and lower letters in the word
		'''
		def capcombos_aux (i, word):
			if i+1 > len(word):
				return [word]
			w1 = word[:i] + word[i].lower() + word[i+1:]
			w2 = word[:i] + word[i].upper() + word[i+1:]
			if w1 != w2:
				result = capcombos_aux(i+1,w1)
				result+= capcombos_aux(i+1,w2)
			else:
				result = capcombos_aux(i+1,w1)
			return result
		return capcombos_aux(0,word)

	def check_all(self, iterable, value=None):
		""" return True if all elements of the iterebale have the value """
		for element in iterable:
			if element is not value:
				return False
		return True

	def createActionsBlock(self, stmts=None, filepath=None, line=None):
		from context import Actions
		if stmts is None and filepath is None and line is None:
			block = Actions()
		elif stmts is None and filepath is None:
			block = Actions(line=line)
		elif stmts is None and line is None:
			block = Actions(fpath=filepath)
		elif filepath is None and line is None:
			block = Actions(stmts=stmts)
		elif stmts is None:
			block = Actions(fpath=filepath, line=line)
		elif filepath is None:
			block = Actions(stmts=stmts, line=line)
		elif line is None:
			block = Actions(stmts=stmts, fpath=filepath)
		else:
			block = Actions(stmts,filepath,line)
		return block

	def createPreconditionsBlock(self, stmts=None, filepath=None, line=None):
		from context import Preconditions
		if stmts is None and filepath is None and line is None:
			block = Preconditions()
		elif stmts is None and filepath is None:
			block = Preconditions(line=line)
		elif stmts is None and line is None:
			block = Preconditions(fpath=filepath)
		elif filepath is None and line is None:
			block = Preconditions(stmts=stmts)
		elif stmts is None:
			block = Preconditions(fpath=filepath, line=line)
		elif filepath is None:
			block = Preconditions(stmts=stmts, line=line)
		elif line is None:
			block = Preconditions(stmts=stmts, fpath=filepath)
		else:
			block = Preconditions(stmts,filepath,line)
		return block

	def convertStatements(self, text_stmts, fpath, line):
		""" Transforms a list of statements in string to Statement objects """
		from context import parser
		return [parser.parse_statement(s,0,fpath,line)[0] for s in text_stmts]

	def generateIndexRule(self, index, path, line):
		""" generate a sequential rule, where its attributes
		are based on an index number
		"""
		name = str(index)
		desc = name
		stmts = self.generateStatements(index,path,line)
		from context import Preconditions, Actions, Rule
		precs = Preconditions(stmts,path,line)
		acts = Actions(stmts,path,line)
		return Rule(name,desc,precs,acts,path,line)

	def generate_output(self,num):
		from context import Output, Effect
		return Output([Effect(i) for i in range(num)])

	def generateSequentialRules(self, num, path=None, line=None):
		""" Generates a list of rules that are sequential """
		if path == None:
			path = tp
		if line == None:
			line = 33
		return [self.generateIndexRule(i,path,line+i) for i in range(num)]

	def generateStatements(self, num, filepath='', start=1):
		"""	Generates a list of Expression Statements,
		consisting in a range of numbers
		"""
		ls = start
		fp = filepath
		from context import Expression
		return [Expression(str(i),fp,ls+i) for i in range(num)]

	def generate_stmts(self, num, filepath='', line_start=1):
		ls = line_start
		fp = filepath
		from context import Expression
		return [Expression(str(i),fp,ls+i) for i in range(num)]

	def get_fpath (self):
		from context import testfiles_path
		return testfiles_path
