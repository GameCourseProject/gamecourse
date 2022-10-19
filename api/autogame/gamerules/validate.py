#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .errors import RuleError
from .errors import PathError

def get_default_TypeError_msg (varname, expected_type, received): # pragma: no cover
	msg = "invalid type for '%s', " % varname
	if isinstance(expected_type,str):
		msg+= "expected '%s' " % expected_type
	elif isinstance(expected_type, (tuple,list)):
		msg+= "expected "
		for i in range(len(expected_type)):
			msg+= "'%s'" % expected_type[i]
			if i+2 < len(expected_type):
				msg+= ", "
			elif i+1 < len(expected_type):
				msg+= " or "
		msg+= " "
	msg+= "received '%s'." % type(received).__name__
	return msg

def actions (a,f,l):
	from .rules import Block
	if not isinstance(a,Block):
		msg = get_default_TypeError_msg('actions','Block',a)
		raise TypeError(msg)
	if a.file() != f:
		msg = "invalid actions file value\n"
		msg+= "rule and its actions must come from the same file.\n"
		msg+= "\trule file: '%s'\n\tactions file: '%s'" % (f,a.file())
		raise ValueError(msg)
	if a.line() < l:
		msg = "invalid actions line value\n"
		msg+= "actions must have an equal or higher line number "
		msg+= "than the rule where they are defined.\n"
		msg+= "\trule line: '%d'\n\tactions line: '%d'" % (l,a.line())
		raise ValueError(msg)

def effects (arg):
	if not hasattr(arg,'__iter__'):
		msg = get_default_TypeError_msg('effects','iterable',arg)
		raise TypeError(msg)
	from .rules import Effect
	for e in arg:
		if not isinstance(e,Effect):
			msg = get_default_TypeError_msg('effects element','Effect',e)
			raise TypeError(msg)

def description (arg):
	if not isinstance(arg,str):
		msg = get_default_TypeError_msg('description','str',arg)
		raise TypeError(msg)

def filepath (arg):
	if not isinstance(arg, str):
		msg = get_default_TypeError_msg('filepath','str',arg)
		raise TypeError(msg)

def isassignment(stmt_txt, filepath, line):
	""" check if a statement (in string) is a Python assignment or not """
	index = 0; is_expression = False; found = False
	for c in stmt_txt:
		if c == "=" and not is_expression: found = True; break
		if c == "(": is_expression = True
		elif c == ")": is_expression = False
		index += 1

	if not found:
		return False
	# Check for errors ..
	if index == 0:
		ex = SyntaxError("invalid assignment: missing left value")
		ex.text = stmt_txt
		ex.offset = 1
		raise RuleError(filepath, line, ex)
	if index+1 >= len(stmt_txt):
		ex = SyntaxError("invalid assignment: missing right value")
		ex.text = stmt_txt
		ex.offset = len(stmt_txt)+1
		raise RuleError(filepath, line, ex)

	prev_char = stmt_txt[index-1]
	next_char = stmt_txt[index+1]
	assignment_operators = "+-*/%"
	comparison_operators = "!><"

	if prev_char in assignment_operators:
		return True
	if prev_char not in comparison_operators and next_char != '=':
		return True
	return False

def line (arg):
	if not isinstance(arg, int):
		msg = get_default_TypeError_msg('line',('int','long'),arg)
		raise TypeError(msg)
	if arg <= 0:
		msg = "Invalid value for 'line', "
		msg+= "lines must be 1 or higher, "
		msg+= "received: '{}'".format(arg)
		raise ValueError(msg)

def name (arg):
	if not isinstance(arg,str):
		msg = get_default_TypeError_msg('name','str',arg)
		raise TypeError(msg)

def output(o):
	from .rules import Output
	if not isinstance(o,(Output,bool)):
		msg = get_default_TypeError_msg('output','Output',o)
		raise TypeError(msg)

def path (p):
	if not isinstance(p,str):
		msg = get_default_TypeError_msg('path','string',p)
		raise TypeError(msg)
	import os
	if not os.path.exists(p):
		raise PathError(p)

def preconditions (p,f,l):
	from .rules import Block
	if not isinstance(p,Block):
		msg = get_default_TypeError_msg('preconditions','Block',p)
		raise TypeError(msg)
	if p.file() != f:
		msg = "invalid preconditions file value\n"
		msg+= "rule and its preconditions must come from the same file.\n"
		msg+= "\trule file: '%s'\n\tpreconditions file: '%s'" % (f,p.file())
		raise ValueError(msg)
	if p.line() < l:
		msg = "invalid preconditions line value\n"
		msg+= "preconditions must have an equal or higher line number "
		msg+= "than the rule where they are defined.\n"
		msg+= "\trule line: '%d'\n\tpreconditions line: '%d'" % (l,p.line())
		raise ValueError(msg)

def rule_args(n,d,p,a,f,l):
	name(n)
	description(d)
	preconditions(p,f,l)
	actions(a,f,l)

# def rule_system_args(p,r):
# 	filepath(p)
# 	rules(r,p)

def rulelog_args(r,o):
	from .rules import Rule
	if not isinstance(r,Rule):
		msg = get_default_TypeError_msg('rule','Rule',r)
		raise TypeError(msg)
	from .rules import Output
	if not isinstance(o,(Output,bool)):
		msg = get_default_TypeError_msg('output','Output',o)
		raise TypeError(msg)

def rule(r):
	from .rules import Rule
	if not isinstance(r,Rule):
		msg = get_default_TypeError_msg('rule','Rule',r)
		raise TypeError(msg)
	rule_args(r.name(),r.desc(),r.precs(),r.acts(),r.path(),r.line())

# def rules (r,p):
# 	if not isinstance(r,(list,tuple)):
# 		msg = get_default_TypeError_msg('rules','list',r)
# 		raise TypeError(msg)
# 	for rule in r:
# 		from rules import Rule
# 		if not isinstance(rule,Rule):
# 			msg = get_default_TypeError_msg('rule','Rule',rule)
# 			raise TypeError(msg)
# 		if p not in rule.file():
# 			msg = "invalid filepath value for rule parsed from path: %s\n" % p
# 			msg+= "rule filepath: %s" % rule.file()
# 			raise ValueError(msg)

def scope (scope):
	if not isinstance(scope,dict):
		msg = get_default_TypeError_msg('scope','dict',scope)
		raise TypeError(msg)

def stmt_txt (arg):
	if not isinstance(arg,str):
		msg = get_default_TypeError_msg('statement','str',arg)
		raise TypeError(msg)

def stmt (s,f,l):
	from .rules import Statement
	if not isinstance(s,Statement):
		msg = get_default_TypeError_msg('statement', 'Statement', s)
		raise TypeError(msg)
	if s.file() != f:
		msg = "invalid statement file value\n"
		msg+= "block and its statements must come from the same file.\n"
		msg+= "\tblock file: '%s'\n\t%s" % (f,s)
		raise ValueError(msg)
	if s.line() < l:
		msg = "invalid statement line value\n"
		msg+= "statements must have an equal or higher line number "
		msg+= "than the block where they are defined.\n"
		msg+= "\tblock line: %d\n\tstatement line: %d" % (l,s.line())
		raise ValueError(msg)

def stmts (stmts):
	if not isinstance(stmts,list):
		msg = get_default_TypeError_msg('statements', 'list', stmts)
		raise TypeError(msg)

def timestamp(ts):
	if not isinstance(ts,float):
		msg = get_default_TypeError_msg('timestamp', 'float', ts)
		raise TypeError(msg)
	if ts < 0:
		msg = "invalid timestamp value, time must be a positive number, "
		msg+= "received: %s" % ts
		raise ValueError(msg)
