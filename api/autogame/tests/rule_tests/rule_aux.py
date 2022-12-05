#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Statement, StatementError, Block

def get_stmts (keyword):
	return {
		'expressions': get_stmt_expressions(),
		'literals': get_stmt_literals(),
		'numbers': get_stmt_numbers(),
		'strings': get_stmt_strings(),
		'functions': get_stmt_functions()
	}[keyword]

def get_stmt_expressions ():
	l = []
	l+= get_stmt_literals()
	l+= get_stmt_functions()
	return l

def get_stmt_literals ():
	l = []
	l+= get_stmt_numbers()
	l+= get_stmt_strings()
	return l

def get_stmt_numbers ():
	l = []
	l.append(Statement("1132"))
	l.append(Statement("421L"))
	l.append(Statement(".111e3"))
	l.append(Statement("5235.123"))
	l.append(Statement("1j+10"))
	l.append(Statement("0xfafa"))
	l.append(Statement("0o412412"))
	l.append(Statement("0b10101001"))
	return l

def get_stmt_strings ():
	l = []
	l.append(Statement("\'simple string\'"))
	l.append(Statement("r\"raw string\""))
	l.append(Statement("u\"Unicode string\""))
	l.append(Statement("b\"Unicode string\""))
	l.append(Statement("\'%d\'%(1234567890)"))
	l.append(Statement("\'{1}. {0} {1}.\'.format(\'James\',\'Bond\')"))
	return l

def get_stmt_functions ():
	l = []
	l.append(Statement("(lambda x: x*x)(2)"))
	return l

def get_eval_error_msg (stmt):
	if not isinstance(stmt, str):
		raise TypeError("<stmt> must be of type string.")
	try:
		eval(stmt)
	except Exception as e:
		# refactor
		"""if hasattr(e,'msg'):
			return type(e).__name__ + ": " + e.msg
		else:
			return type(e).__name__ + ": " + e.message"""
		return type(e).__name__
	else:
		msg = "Exception was not thrown with:\neval(" + stmt + ")"
		raise Exception(msg)


def get_block_01 ():
	""" positives, negatives and neutrals """
	b = Block()
	b.add_stmt(Statement("add = lambda x,y: x+y"))
	b.add_stmt(Statement("positives = range(1,10+1)"))
	b.add_stmt(Statement("positives"))
	b.add_stmt(Statement("neg = lambda x: x*-1"))
	b.add_stmt(Statement("negatives = map(neg,positives)"))
	b.add_stmt(Statement("negatives"))
	b.add_stmt(Statement("sum = lambda x: reduce(add,x)"))
	b.add_stmt(Statement("neutral = map(add,positives,negatives)"))
	b.add_stmt(Statement("neutral"))
	b.add_stmt(Statement("sum(positives) > sum(neutral) > sum(negatives)"))
	return b

def get_block_02 ():
	""" Dogs """
	b = Block()
	b.add_stmt(Statement("dogs = ['Becky']"))
	b.add_stmt(Statement("dogs.append('Freddy')"))
	b.add_stmt(Statement("dogs.append('Sammy')"))
	b.add_stmt(Statement("join_names = lambda n1,n2: n1 + ', ' + n2"))
	b.add_stmt(Statement("dogs = reduce(join_names,dogs)"))
	b.add_stmt(Statement("'Freddy' in dogs"))
	return b

def get_block_03 ():
	""" MARVEL """
	b = Block()
	b.add_stmt(Statement("spidey = ('Spider-Man','Peter Parker','daily bugle')"))
	b.add_stmt(Statement("captain = ('Captain America','Steve Rogers','shield')"))
	b.add_stmt(Statement("stark = ('Iron Man','Anthony Stark','shield')"))
	b.add_stmt(Statement("wolverine = ('wolverine','James Howlett','x-men')"))
	b.add_stmt(Statement("storm = ('Storm','Ororo Munroe','x-men')"))
	b.add_stmt(Statement("professor = ('X','Charles Xavier','x-men')"))
	b.add_stmt(Statement("isshield = lambda x: x[2] == 'shield'"))
	b.add_stmt(Statement("isxman = lambda x: x[2] == 'x-man'"))
	return b

def test_invalid_validate (test,stmt):
		block = Block([Statement(stmt)])
		with test.assertRaises(StatementError) as cm:
			block.validate()
		msg = "Invalid Statement(file: ???, line: 1): "
		msg+= get_eval_error_msg(stmt)
		test.assertEqual(str(cm.exception), msg)
		test.assertFalse(block.isvalid())