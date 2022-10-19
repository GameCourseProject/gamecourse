#!/usr/bin/env python
# -*- coding: utf-8 -*-

from os import listdir
from os.path import isfile, isdir, join
from ..errors import PathError
from ..errors import ParseError
from ..rules import Rule
from ..rules import Block
from ..rules import Preconditions
from ..rules import Actions

from .aux_functions import isblank
from .aux_functions import file_isvalid
from .aux_functions import next_word
from .aux_functions import is_next
from .aux_functions import is_followed
from .aux_functions import skip_blank
from .aux_functions import skip_blank_inline
from .aux_functions import rm_blank_end
from .aux_functions import validate_block_args
from .aux_functions import validate_named_block_args

import re

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Functions
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
def parse(opath):
	"""
	Parse all the rules from the origin_path file/directory.
	Returns a list with the instantiation of all the rules parsed this way.
	"""
	rules = []
	if file_isvalid(opath):
		rules += parse_file(opath)
	elif isdir(opath):
		rules += parse_folder(opath)
	else:
		raise PathError('origin path is not valid: \"'+str(opath)+'\"')
	return rules

def parse_folder(dir_path):
	rules = []
	# note : this was not returning a sorted list, the order of files returned
	# was completely arbitrary; there might be some nuances in different os'
	for f in sorted(listdir(dir_path)):
		fpath = join(dir_path,f)
		if file_isvalid(fpath):
			rules += parse_file(fpath)
	return rules

def parse_file(fpath):
	"""
	Parse a rule file and returns a list where each element is an instantiation
	of a rule parsed
	"""
	list_rules = []
	text = ""

	with open(fpath, 'r') as f:
		text = f.read()
	# remove tags line before parsing
	new_text = re.sub('\ntags:.+\n', '\n', text)
	list_rules = parse_list_rules(new_text,fpath)

	return list_rules


def parse_list_rules(text="", fpath=""):
	"""
	parses all the rule definitions from the text 'text' in file 'fpath'
	return a list with all the rule parsed
	"""
	# init
	list_rules = []
	pos = 0
	line = 1

	while pos < len(text):
		if text[pos] == "\n":
			line += 1
		if isblank(text[pos]):
			pos += 1
			continue
		if text[pos] == "#":
			pos = parse_comment(text, pos)
			continue
		rule, pos, line = parse_rule(text,pos,fpath,line)
		if rule == None:
			## Case where rule is ignored because it is inactive
			continue
		else:
			list_rules.append(rule)

	return list_rules


def parse_rule(text="", pos=0, fpath="", line=1):
	"""
	Parses the next rule definition from text 'text' starting in position 'pos'
	returns:
	1) the rule created. An object from the rule class (rule.py)
	2) the position to the end of the rule definition in the text
	3) the line to the end of the rule definition in the text
	"""
	## init
	name = ""
	desc = ""			# description of the rule
	precs = Block([],fpath,line)	# preconditions block
	acts = Block([],fpath,line)		# actions block
	start_line = line
	inactive = False				# whether a rule is inactive or not

	## All rule-definitions start with the keyword 'rule' at the beggining
	## of the line (excluding blank-space)
	pos = next_word("rule",text,pos)
	if pos is False:
		msg ="parse_rule:: invalid rule definition, expected \'rule\' "
		msg +="in the beggining of the definition"
		raise ParseError(fpath,line,msg)
	## after the keyword 'rule', it must be follower by a ':' in the same line
	pos = next_word(":",text,pos)
	if pos is False:
		msg = "parse_rule:: invalid rule definition, expected \':\' "
		msg +="after \'rule\'"
		raise ParseError(fpath,line,msg)
	## Parse Name
	name, pos = parse_name(text,pos)
	if text[pos-1] == "\n": line += 1
	if is_next("INACTIVE",text,pos):
		inactive = True

	## Parse Description if there is any
	desc, pos, line = parse_description(text,pos,line)
	## Parse Preconditions & Actions
	if pos < len(text):
		if is_next("when",text,pos):
			precs, pos, line = parse_preconditions(text,pos,fpath,line)
		if is_next("then",text,pos):
			acts, pos, line = parse_actions(text,pos,fpath,line)

	if inactive:
		return None, pos, line
	else:
		return Rule(name,desc,precs,acts,fpath,start_line), pos, line


def parse_preconditions(text="", pos=0, fpath="", line=1):
	"""
	Parse valid preconditions of a rule.
	Preconditions start with a 'when' followed by a ':' and a block, consisting
	in a list of statements delimited by a newline and where each statement,
	can either be an assignment or an expression.
	The expressions represent the preconditions. The assignments belong to a
	diferent section of the rule called 'variables'.
	This function returns a Tuple with four elements:
	1) preconditions: list, where each element consists in a String that
	contains an expression to be evaluated
	2) variables: list, where each element consists in a String that contains
	an assigment that must occur before the preconditions
	3) pos: index to next character of the 'text' to be parsed
	4) line: current line being parsed from the file 'fpath'
	"""
	result = parse_named_block("when",text,pos,("then","rule"),fpath,line)
	block, pos, line = result
	return Preconditions(block.stmts(),block.file(),block.line()), pos, line

def parse_actions(text="", pos=0, fpath="", line=1):
	"""
	Parse valid actions of a rule.
	Actions start with a 'then' followed by a ':' and a block, consisting
	in a list of statements delimited by a newline and where each statement,
	can either be an assignment or an expression.
	The expressions represent the actions. The assignments belong to a
	diferent section of the rule called 'variables'.
	This function returns a Tuple with four elements:
	1) actions: list, where each element consists in a String that contains
	an expression to be evaluated
	2) variables: list, where each element consists in a String that contains
	an assigment that must occur before the preconditions
	3) pos: index to next character of the 'text' to be parsed
	4) line: current line being parsed from the file 'fpath'
	"""
	block, pos, line = parse_named_block("then",text,pos,"rule",fpath,line)
	return Actions(block.stmts(),block.file(),block.line()), pos, line

def parse_named_block(name="", text="", pos=0, endkey="", fpath="", line=1):
	"""
	A named block is a name followed by a ':' followed by a block
	returns a list of variable declarations, a list of expressions
	and the position and line parsed
	"""
	validate_named_block_args(name,text,pos,fpath,line)

	# a non-empty Block name must be the next non blank string
	result = is_next(name,text,pos,line)
	if not result:
		msg = "parse_named_block:: Expected \'" + name + "\'"
		raise ParseError(file=fpath,line=line,val=msg)
	# update pos and line
	pos = result[0] + len(name)
	line = result[1]

	# Colon (':') must be the next non blank character in the same line
	result = is_next(":",text,pos,line)
	if not result or result[1] != line:
		msg = "parse_named_block:: Expected \':\' after the block name"
		raise ParseError(file=fpath,line=line,val=msg)

	# update pos
	pos = result[0] + 1
	# parse block
	block, pos, line = parse_block(text,pos,endkey,fpath,line)

	return block, pos, line

def parse_block (text="", pos=0, endkey="", fpath="", line=1):
	""" Parse a block of statements from 'text' starting in position 'pos'.
	Returns a list of expressions, a list of declarations  and the position
	of the last parsed character.
	"""
	# init
	stmts = []
	start_line = line
	# guard
	validate_block_args(text,pos,endkey,fpath,line,stmts)
	# In case of an empty body and end of file is reached
	if pos >= len(text):
		return Block([],fpath,line), pos, line
	# Main-Loop
	while pos < len(text):
		char = text[pos]
		# Skip blank space
		if char == "\n":
			pos += 1; line += 1; continue
		if isblank(char):
			pos += 1; continue
		# Stop condition
		if is_followed(endkey,":",text,pos):
			break
		# parse statement and add it to the expressions or declarations
		# depending on its type (if it's an assignment then goto decls)
		stmt, pos = parse_statement(text,pos,fpath,line)
		if stmt is not None:
		# if (type(stmt) is Assignment) or (type(stmt) is Expression):
			stmts.append(stmt)
		# increment the number of lines if the last char one was a newline
		if text[pos-1] == "\n":
			line += 1

	# TODO: Must verify block
	# if verify_block(exprs,decls)
	return Block(stmts,fpath,start_line), pos, line

def parse_statement(text="", pos=0, fpath="", line=1):
	"""
	Parse the next statement in the text starting in position 'pos'.
	A statement can be an assignment if there is an assignment operation
	otherwise it's an expression. Returns the statement and position of the
	last character parsed in the text
	"""
	stmt = ""
	whitespace_allowed = False # flag to control the whitespaces in the stmt

	while pos < len(text):
		char = text[pos]
		if char == "\n":
			break
		if char == "#":
			pos = parse_comment(text,pos)
			break
		if isblank(char):
			# only allows a single whitespace
			if whitespace_allowed:
				stmt += " "
				whitespace_allowed = False
			pos += 1
			continue

		# necessary condition for not checking the last char of the line
		if pos < len(text) - 1:
			# this branch will check the syntax for a function
			# functions are of the format:
			# GC.library.function(arg1, arg2, ...)

			# detect function
			if char == 'G' and text[pos+1] == 'C' and text[pos+2] == '.':
				#skip the prefix "GC."
				pos += 3

				# hold the module
				library = ''
				# hold the function call
				fn_call = ''
				char = text[pos]

				while char != '.':
					library += char
					pos +=1
					char = text[pos]

				# ignore the dot
				pos +=1
				char = text[pos]

				while char != '(':
					fn_call += char
					pos +=1
					char = text[pos]

				parenthesis = 1
				args = char

				while parenthesis != 0:
					pos += 1
					char = text[pos]
					args += char
					if char == '(':
						parenthesis += 1
					if char == ')':
						parenthesis -= 1
					if char == '"':
						pos += 1
						char = text[pos]
						args += char
						while char != '"':
							pos += 1
							char = text[pos]
							args += char

					if char == '\'':
						pos += 1
						char = text[pos]
						args += char
						while char != '\'':
							pos += 1
							char = text[pos]
							args += char

				stmt += "gc(\"" + library + "\",\"" + fn_call + "\""
				if args != "()": # if the function has arguments
					stmt += ", " + args[1:-1]

		stmt += char
		pos += 1
		whitespace_allowed = True

	# if last character was a whitespace, delete it
	if len(stmt) > 0 \
	and isblank(stmt[len(stmt)-1]):
		stmt = stmt[:len(stmt)-1]
	# returned position must point to the end of the string
	if len(text) > 0 and pos < len(text):
		pos+=1

	# convert the statement string into a StatementNode
	from .aux_functions import get_stmt
	stmt = get_stmt(stmt,fpath,line)

	return stmt, pos


def parse_name(text="", pos=0):
	"""
	Parse a valid rule name from text starting in positon pos.
	Return a string with the name
	"""
	name = ""
	pos = skip_blank_inline(text,pos)

	while pos < len(text):
		char = text[pos]
		if char == "\n":
			pos += 1
			break
		if char == "#":
			pos = parse_comment(text,pos)
			continue
		name += char
		pos += 1

	name = rm_blank_end(name)

	return name, pos

def parse_description(text="", pos=0, line=1):
	"""
	Parse a valid rule description from text starting in positon pos.
	Return a string with the description
	"""
	description = ""
	pos, line = skip_blank(text,pos,line)

	while pos < len(text):
		char = text[pos]
		if char == "\n":
			line += 1
		if isblank(char):
			pos += 1
			description += char
			continue
		## Stop if the preconditions, actions sections
		## or a new rule definition is found
		if is_followed(("when","then","rule"),":",text,pos):
			break
		description, pos = parse_description_line(text, pos, description)

	# remove blank space after the last non-blank character
	description = rm_blank_end(description)

	return description, pos, line

def parse_description_line(text, pos, description):
	while pos < len(text):
		char = text[pos]
		if char == "\n":
			return description, pos
		if char == "#":
			pos = parse_comment(text, pos)
			continue
		description += char
		pos += 1

	return description, pos


def parse_comment(text="", pos=0):
	""" Parse through a line comment """
	while pos < len(text) and text[pos] != "\n":
		pos += 1
	return pos
