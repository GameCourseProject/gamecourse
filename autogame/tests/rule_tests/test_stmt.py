#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import Statement, StatementError, RuleError
# from aux_functions import assert_statement

import unittest


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# TestStatement abstract class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestStatementNode (unittest.TestCase):
	
	def assertStatement(self, stmt, text=None, filepath=None, line=None):
		self.assertIsInstance(stmt,Statement)
		self.assertEqual(type(stmt.code()).__name__,'code')
		if text != None:
			self.assertEqual(stmt.text(),text)
		if filepath != None:
			self.assertEqual(stmt.path(),filepath)
		else:
			self.assertEqual(stmt.path(),'')
		if line != None:
			self.assertGreaterEqual(stmt.line(),1)
			self.assertEqual(stmt.line(),line)
		else:
			self.assertEqual(stmt.line(),1)

	def assertCreation(self, text=None, filepath=None, line=None):
		if text == None and filepath == None and line == None:
			stmt = Statement()
		elif text == None and filepath == None:
			stmt = Statement(line=line)
		elif text == None and line == None:
			stmt = Statement(fpath=filepath)
		elif filepath == None and line == None:
			stmt = Statement(text)
		elif filepath == None:
			stmt = Statement(text,line=line)
		elif line == None:
			stmt = Statement(text,filepath)
		elif text == None:
			stmt = Statement(fpath=filepath,line=line)
		else:
			stmt = Statement(text,filepath,line)
		self.assertStatement(stmt,text,filepath,line)

	def assertCreationRaises(self, error_msg, text=None, offset=None):
		filepath = "C:\\assert\\creation\\error.txt"
		line = 404
		with self.assertRaises(RuleError) as cm:
				Statement(text,filepath,line)
		msg = "in line %d of file '%s':\n" % (line,filepath)
		msg+= "\t%s\n" % text
		if not isinstance(offset,int):
			offset = 1
		blank = ' ' * (offset - 1)
		msg+= "\t%s^\n" % blank
		msg+= error_msg
		self.assertEqual(str(cm.exception), msg)

	def assertEqualsGenerate (self, eqtext, eqfile, eqline):
		if eqtext == False:
			# different text statements
			t1 = "a = 7"
			t2 = "b = b111"
		else: # equal text statements
			t1 = "True"
			t2 = t1
		if eqfile == False:
			# different file paths
			f1 = "C:\\test\\stmt_node\\assert_diff1.txt"
			f2 = "C:\\test\\stmt_node\\assert_diff2.txt"
		else: # equal file paths
			f1 = "C:\\test\\stmt_node\\assert_equals.txt"
			f2 = f1
		if eqline == False:
			# different lines
			l1 = 320
			l2 = 76
		else:
			l1 = 2
			l2 = l1
		# generate statements
		stmt1 = Statement(t1,f1,l1)
		stmt2 = Statement(t2,f2,l2)
		self.assertStatement(stmt1,t1,f1,l1)
		self.assertStatement(stmt2,t2,f2,l2)
		self.assertEquality(stmt1,stmt2,eqtext,eqfile,eqline)

	def assertEquality (self, stmt1, stmt2, eqtext, eqfile, eqline):
		# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		if eqtext:
			self.assertEqual(stmt1.text(),stmt2.text())
		else:
			self.assertNotEqual(stmt1.text(),stmt2.text())
		# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		if eqfile:
			self.assertEqual(stmt1.file(),stmt2.file())
		else:
			self.assertNotEqual(stmt1.file(),stmt2.file())
		# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		if eqline:
			self.assertEqual(stmt1.line(),stmt2.line())
		else:
			self.assertNotEqual(stmt1.line(),stmt2.line())
		# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		if all([eqtext, eqfile, eqline]):
			# if all are True, then the statements are EQUAL
			self.assertEqual(stmt1,stmt2)
			self.assertTrue(stmt1 == stmt2)
			self.assertFalse(stmt1 != stmt2)
		else:
			# if any of them is False, then the statements are NOT EQUAL
			self.assertNotEqual(stmt1,stmt2)
			self.assertTrue(stmt1 != stmt2)
			self.assertFalse(stmt1 == stmt2)

	def assertFire (self, text, scope=None):
		if scope == None:
			scope = {}
		filepath = "C:\\test\\stmt_node\\fire\\assert.txt"
		line = 43
		stmt = Statement(text,filepath,line)
		expected_scope = dict(scope)
		exec(text,expected_scope)
		result = stmt.fire(scope)
		self.assertIsNot(scope,expected_scope)
		self.assertEqual(scope,expected_scope)
		self.assertIsInstance(result,bool)
		self.assertTrue(result == True)


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestStatementNode):
	def test_i00 (self):
		with self.assertRaises(TypeError):
			Statement(text=1,fpath="file.txt",line=32)

	def test_i01 (self):
		arg = None
		with self.assertRaises(TypeError):
			Statement(text="a = 0",fpath=arg,line=32)

	def test_i02 (self):
		arg = {"invalid":"argument"}
		with self.assertRaises(TypeError):
			Statement(text="a = 0",fpath="valid_file.txt",line=arg)

	def test_i03 (self):
		stmt = "a = 0"
		fpath = "valid_file.txt"
		line = 0
		with self.assertRaises(ValueError) as cm:
			Statement(text=stmt,fpath=fpath,line=line)
		msg = "Invalid value for 'line', "
		msg+= "lines must be 1 or higher, "
		msg+= "received: '{}'".format(line)
		self.assertEqual(str(cm.exception), msg)

	def test_i04 (self):
		stmt = "a = 0"
		fpath = "valid_file.txt"
		line = -1
		with self.assertRaises(ValueError) as cm:
			Statement(text=stmt,fpath=fpath,line=line)
		msg = "Invalid value for 'line', "
		msg+= "lines must be 1 or higher, "
		msg+= "received: '{}'".format(line)
		self.assertEqual(str(cm.exception), msg)

	def test_i05 (self):
		stmt = "a = 0"
		fpath = "valid_file.txt"
		line = -98667218094
		with self.assertRaises(ValueError) as cm:
			Statement(text=stmt,fpath=fpath,line=line)
		msg = "Invalid value for 'line', "
		msg+= "lines must be 1 or higher, "
		msg+= "received: '{}'".format(line)
		self.assertEqual(str(cm.exception), msg)

	def test_i06 (self):
		m = "SyntaxError: invalid syntax"
		self.assertCreationRaises(m,'=',1)

	def test_i07 (self):
		stmt = "= 1 + 2"
		msg = "SyntaxError: invalid syntax"
		self.assertCreationRaises(msg, stmt)

	def test_i08 (self):
		stmt = "i ="
		msg = "SyntaxError: invalid syntax"
		self.assertCreationRaises(msg, stmt, 4)

	def test_v00 (self):
		self.assertCreation('','C:\\test\\valids\\stmt.txt',1)

	def test_creation_v01 (self):
		self.assertCreation('1',None,12)

	def test_creation_v02 (self):
		self.assertCreation('i=1',None,2432)

	def test_creation_v03 (self):
		text = "tuple < str < long < list < int < dict"
		self.assertCreation(text,None,2432)
		
	def test_creation_v04 (self):
		text = "tomorrow_land = 0<10<=10>=9>3==3!=5"
		self.assertCreation(text,None,2432)

	def test_creation_v05 (self):
		text = "arithmetic_ops = 0+10-2*3/5%7**8//9"
		self.assertCreation(text)

	def test_creation_v06 (self):
		text = "i += b"
		self.assertCreation(text)

	def test_creation_v07 (self):
		text = "_f_ -= 4234"
		self.assertCreation(text)

	def test_creation_v08 (self):
		text = "a *= f(5+8)"
		self.assertCreation(text)

	def test_creation_v09 (self):
		text = "lb /= f(a) * g(b)"
		self.assertCreation(text)

	def test_creation_v10 (self):
		text = "i %= 2>>2"
		self.assertCreation(text)

	def test_creation_v11 (self):
		text = "i **= 3<<2"
		self.assertCreation(text)

	def test_creation_v12 (self):
		text = "i //= 10&2"
		self.assertCreation(text)

	def test_creation_v13 (self):
		text = "i = True and False"
		self.assertCreation(text)

	def test_creation_v14 (self):
		text = "True == 1 != False <= 0"
		self.assertCreation(text)

	def test_creation_v15 (self):
		text = ""
		self.assertCreation(text)

	def test_creation_v16 (self):
		self.assertCreation()

	def test_creation_v17 (self):
		self.assertCreation(line=432)

	def test_creation_v18 (self):
		self.assertCreation(filepath="C:\\Python27\\rule.txt")

	def test_creation_v19 (self):
		self.assertCreation("reduce(lambda a,b:a+b,range(10))","C:\\rule.txt")

	def test_creation_v20 (self):
		self.assertCreation(filepath="C:\\rule.txt",line=404)


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# equals(stmt1,stmt2)
# Two statements are considered equals if:
# - they have the same text code
# - they come from the same file
# - they exist in the same line
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestEquals(TestStatementNode):

	def test_01 (self):
		""" equal statements """
		self.assertEqualsGenerate(True,True,True)

	def test_02 (self):
		""" different text """
		self.assertEqualsGenerate(False,True,True)

	def test_03 (self):
		""" different file """
		self.assertEqualsGenerate(True,False,True)

	def test_04 (self):
		""" different line """
		self.assertEqualsGenerate(True,True,False)

	def test_05 (self):
		""" different text, file """
		self.assertEqualsGenerate(False,False,True)

	def test_06 (self):
		""" different text, line """
		self.assertEqualsGenerate(False,True,False)

	def test_07 (self):
		""" different file, line """
		self.assertEqualsGenerate(True,False,False)

	def test_08 (self):
		""" different text, file, line """
		self.assertEqualsGenerate(False,False,False)

	def test_09 (self):
		""" eq(stmt, other obj) """
		stmt = Statement('1')
		other = '1'
		self.assertNotEqual(stmt, other)
		self.assertTrue(stmt != other)
		self.assertFalse(stmt == other)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Statement Getters:
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Statement.statement(), Statement.text()
# Statement.fpath(), Statement.filepath(), Statement.file_path(), ... 
# ... Statement.path(), Statement.location()
# Statement.line()
# Statement.type()
# Statement.isassignment(), Statement,is_assignment()
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestGetters(unittest.TestCase):

	def test_stmt (self):
		stmt = "mike = 'Michael Jordan'"
		s = Statement(stmt)
		self.assertEqual(s.text(),stmt)
		stmt_aux = s.text()
		stmt_aux+= "Exception!!"
		self.assertEqual(s.text(),stmt)

	def test_fpath (self):
		path = "C:\\mylocation\\no\\one\\can\\know\\about\\this\\stmt.txt"
		s = Statement(fpath=path)
		self.assertEqual(s.path(),path)
		p = s.path()
		p+= "\\oh\\no\\somebody\\discover\\my\\hideout\\gameover.txt"
		self.assertEqual(s.path(),path)
		self.assertEqual(s.file(),path)
		self.assertEqual(s.fpath(),path)
		self.assertEqual(s.filepath(),path)
		self.assertEqual(s.file_path(),path)
		self.assertEqual(s.location(),path)

	def test_line (self):
		line = 213
		s = Statement(line=line)
		self.assertEqual(s.line(),line)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Statement.fire(scope)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestFire (TestStatementNode):

	def test_01 (self):
		""" invalid argument type """
		with self.assertRaises(TypeError):
			Statement().fire("<ERROR>")

	def test_02 (self):
		""" empty statement """
		self.assertFire('')

	def test_03 (self):
		""" expression statement """
		self.assertFire("4123")

	def test_04 (self):
		""" assignment statement """
		self.assertFire("a = \"something\"")

	def test_05 (self):
		""" false statement """
		self.assertFire("False")

	def test_06 (self):
		""" statement with defined name dependencies """
		def print_str (string):
			return string
		mystring = "function call, I choose you!"
		self.assertFire("a = print_str(mystring)", locals())

	def test_07 (self):
		""" statement with undefined name dependencies """
		with self.assertRaises(RuleError):
			Statement('sum3(x,y,z)').fire()

	def test_08 (self):
		""" python statement which is not an expression or an assignment """
		self.assertFire("pass")