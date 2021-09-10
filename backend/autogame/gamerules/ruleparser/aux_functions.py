#!/usr/bin/env python
# -*- coding: utf-8 -*-
from ..rules import Statement
from ..rules import Expression
from ..rules import Assignment
from .. import validate
from ..errors import ParseError

def get_stmt(stmt, fpath, line):
	"""
	Transform a String with a Python statement to a Node.
	If it's an assignment, an Assignment Node is returned
	If it's an Expression, an Expression Node is returned
	If it's other kind of statement, a Statement Node is returned
	If it's an empty statement return None
	"""
	stmt_type = get_stmt_type(stmt,fpath,line)
	if stmt_type == 1: # Expression
		return Expression(stmt,fpath,line)
	elif stmt_type == 2: # Assignment
		return Assignment(stmt,fpath,line)
	elif stmt_type == 0: # Empty
		return None

def get_stmt_type(stmt, fpath=None, line=None):
	if fpath is None:
		fpath = 'unknown file'
	if line is None:
		line = 1
	if len(stmt) == 0: # Empty statement
		return 0
	if validate.isassignment(stmt,fpath,line):
		return 2
	return 1

def isblank(char):
	from re import compile
	pattern = compile('\s')
	if pattern.match(char):
		return True
	return False

def isalpha (char):
	if isinstance(char, str) \
	and (inbetween(char,'a','z') \
		or inbetween(char,'A','Z')):
		return True
	return False

def isnum (char):
	if isinstance(char, str) \
	and inbetween(char,'0','9'):
		return True
	return False
	
def isalnum(char):
	if isalpha(char) or isnum(char):
		return True
	return False


def inbetween (char, min, max):
	if ord(char) >= ord(min) and ord(char) <= ord(max):
		return True
	return False

def is_followed(a,b,text,pos):
	"""
	check if any of the words in tuple 'a' is the next word in text starting
	from position 'pos'and is followed by any word in tuple 'b' 
	"""
	if not is_followed_guard(a,b,text,pos):
		return False

	if isinstance(a,str):
		a  = [a]
	else:
		a = list(a)
	if isinstance(b,str):
		b = [b]
	else:
		b = list(b)

	found = True
	for e in a:
		result = next_word(e,text,pos)
		if result:
			pos = result
			found = True
			break
		else:
			found = False
	
	if not found:
		return False

	for e in b:
		result = next_word(e,text,pos)
		if result:
			found = True
			break
		else:
			found = False
	
	return found

def is_followed_guard(a,b,text,pos):
	if not (isinstance(a,tuple) or isinstance(a,str)) \
	or not (isinstance(b,tuple) or isinstance(b,str)) \
	or not isinstance(text, str) \
	or not isinstance(pos, int) \
	or pos >= len(text) \
	or len(a) <= 0 \
	or len(b) <= 0:
		return False
	if isinstance(a,tuple):
		for e in a:
			if not isinstance(e,str):
				return False
	if isinstance(b,tuple):
		for e in b:
			if not isinstance(e,str):
				return False
	return True

def file_isvalid (file_path):
	""" Check if the file path exists and is a valid rule_file """
	from os.path import isfile
	if isfile(file_path) \
	and '.txt' in file_path \
	and '.txt' == file_path[len(file_path)-len('.txt'):]:
		return True
	return False

def next_word (word,text,pos=0):
	i = 0
	# skip blank
	for i in range(pos,len(text)):
		char = text[i]
		if char == "\n":
			return False
		if isblank(char):
			continue
		break
	pos = i+len(word)
	if word.lower() == text[i:pos].lower():
		return pos
	return False

def is_next_word(word, text, pos=0):
	""" find if next word in text 'text' at position 'pos' is 'word' """
	if len(word) == 0:
		return False
	if len(text) <= len(word):
		return False
	if pos < 0:
		return False
	if word.lower() == text[pos:pos+len(word)].lower():
		return True
	return False


def is_next (key=None, text=None, pos=0, line=1):
	"""
	find if next string in text 'text' starting from position 'pos' is 'key'
	if it is return the position to the start position of the key and the
	line in the text where it is
	if it's not return False
	"""
	if not isinstance(key,str) \
	or not isinstance(text,str) \
	or not isinstance(pos,int) \
	or not isinstance(line,int) \
	or len(key) < 1 \
	or len(text) < 1 \
	or len(text) < len(key) \
	or pos < 0 \
	or line < 1:
		return False

	while pos < len(text):
		char = text[pos]
		# count lines
		if char == "\n":
			line += 1
		# ignore blank space
		if isblank(char):
			pos += 1
			continue
		if is_next_word(key, text, pos):
			return pos, line
		break
	return False

def rm_blank_end(text=""):
	end = len(text)-1

	while end >= 0:
		if isblank(text[end]):
			end -= 1
			continue
		break

	return text[:end+1]

def skip_blank (text="",pos=0,line=1):
	while pos < len(text):
		char = text[pos]
		if char == "\n":
			line += 1
		if isblank(char):
			pos += 1
			continue
		if char == "#":
			from .rule_parser import parse_comment
			pos = parse_comment(text,pos)
			continue
		break
	return pos, line

def skip_blank_inline (text="",pos=0):
	while pos < len(text):
		char = text[pos]
		if char == "\n":
			break 
		if isblank(char):
			pos += 1
			continue
		break
	return pos




# def get_stmt_type (stmt):
# 	if len(stmt) == 0:
# 		return 0 # empty statement code
# 	assignment_operators = "+-*/%"
# 	comparison_operators = "!><"
# 	# look for assignment operator in the statement ('=')
# 	for i in range(len(stmt)):
# 		char = stmt[i]
# 		if char == "=":
# 			# Error: operator '=' must be have a left and right arguments
# 			# so if at least there isn't a character before and after
# 			# it means there is Syntax Error
# 			if (i <= 0) or (i + 1 >= len(stmt)):
# 				return -1 # Error code
# 			prev_char = stmt[i-1]
# 			next_char = stmt[i+1]
# 			# check if it's an Arithmetic assignment
# 			if prev_char in assignment_operators:
# 				return 2 # assignment code
# 			# check if it's a simple assignment
# 			if prev_char not in comparison_operators \
# 			and next_char != "=":
# 				return 2 # assignment code
# 			# at this point it either is an expression or a Syntax Error
# 			# because it's not part of the responsability of this function
# 			# to check for syntax errors, will just hope it's an expression
# 			return 1 # expression code
# 	return 1 # expression code




def validate_block_args(text,pos,endkey,fpath,line,stmts):
	if not isinstance(text,str):
		msg = "parse_block:: Invalid \'text\' arg, expected"
		msg += "<type \'str\'> received " + str(type(text))
		raise ParseError(val=msg)
	if not isinstance(pos,int):
		msg = "parse_block:: Invalid \'pos\' arg, expected"
		msg += "<type \'Int\' or \'int\'> received " + str(type(pos))
		raise ParseError(val=msg)
	if not (isinstance(endkey,str) or isinstance(endkey,tuple)):
		msg = "parse_block:: Invalid \'endkey\' arg, expected"
		msg += "<type \'str\'> or <type \'tuple\'>"
		msg += " received " + str(type(endkey))
		raise ParseError(val=msg)
	if not isinstance(fpath,str):
		msg = "parse_block:: Invalid \'fpath\' arg, expected"
		msg += "<type \'str\'> received " + str(type(fpath))
		raise ParseError(val=msg)
	if not isinstance(line,int):
		msg = "parse_block:: Invalid \'line\' arg, expected"
		msg += "<type \'basestring\'> received " + str(type(line))
		raise ParseError(val=msg)
	if not isinstance(stmts,list):
		msg = "parse_block:: Invalid \'stmts\' arg, expected"
		msg += "<type \'list\'> received " + str(type(stmts))
		raise ParseError(val=msg)
	if not pos >= 0:
		msg = "parse_block:: \'pos\' can't be a negative number"
		raise ParseError(val=msg)
	if not line > 0:
		msg = "parse_block:: \'line\' must be non-zero positive Integer"
		raise ParseError(val=msg)
	for s in stmts:
		if not isinstance(s,Statement):
			msg = "parse_block:: Invalid \'stmts\' arg."
			msg += " All elements of this list"
			msg += " must be Statements, but found an element of "
			msg += str(type({"stmt":1})) + ", in the list."
			raise ParseError(val=msg)
	return True

def validate_named_block_args(name,text,pos,fpath,line):
	if not isinstance(name,str):
		msg = "parse_block:: Invalid \'name\' arg, expected"
		msg += "<type \'str\'> received " + str(type(name))
		raise ParseError(val=msg)
	if not isinstance(text,str):
		msg = "parse_block:: Invalid \'text\' arg, expected"
		msg += "<type \'str\'> received " + str(type(text))
		raise ParseError(val=msg)
	if not isinstance(pos,int):
		msg = "parse_block:: Invalid \'pos\' arg, expected"
		msg += "<type \'Int\' or \'int\'> received " + str(type(pos))
		raise ParseError(val=msg)
	if not isinstance(fpath,str):
		msg = "parse_block:: Invalid \'fpath\' arg, expected"
		msg += "<type \'str\'> received " + str(type(fpath))
		raise ParseError(val=msg)
	if not isinstance(line,int):
		msg = "parse_block:: Invalid \'line\' arg, expected"
		msg += "<type \'str\'> received " + str(type(line))
		raise ParseError(val=msg)
	if len(text) < len(name):
		msg = "parse_block:: size of \'text\' can't be less than size of"
		msg += " \'name\'"
		raise ParseError(val=msg)
	if len(name) == 0:
		msg = "parse_block:: Name of block can't be empty"
		raise ParseError(val=msg)
	if pos < 0:
		msg = "parse_block:: \'pos\' can't be a negative number"
		raise ParseError(val=msg)
	if pos >= len(text):
		msg = "parse_block:: \'pos\' can't be equal or bigger than size of text"
		raise ParseError(val=msg)
	if not line >= 1:
		msg = "parse_block:: \'line\' must be non-zero positive Integer"
		raise ParseError(val=msg)
	return True