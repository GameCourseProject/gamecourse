#!/usr/bin/env python
# -*- coding: utf-8 -*-

from aux_functions import test_parse_name_aux

import unittest

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Name Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseName (unittest.TestCase):
	def test_parse_name_000(self):
		test_parse_name_aux(self,"",pos=0)
	def test_parse_name_001(self):
		test_parse_name_aux(self,"simplename")
	def test_parse_name_002(self):
		test_parse_name_aux(self,"with 	\tspaces\r\v\f","with 	\tspaces")
	def test_parse_name_003(self):
		test_parse_name_aux(self,"ALL CAPS")
	def test_parse_name_004(self):
		test_parse_name_aux(self,"_-.:,;")
	def test_parse_name_005(self):
		test_parse_name_aux(self,"ºª~^´`¨+*")
	def test_parse_name_006(self):
		test_parse_name_aux(self,"|!@£$§%&/()[]=?«»<>")
	def test_parse_name_007(self):
		test_parse_name_aux(self,"\\\"\'\a\b}{","\\\"\'\x07\x08}{")
	def test_parse_name_008(self):
		test_parse_name_aux(self,"line1\nline2","line1",pos=len("line1\n"))
	def test_parse_name_009(self):
		test_parse_name_aux(self,"name#with comments\n","name")
	def test_parse_name_010(self):
		test_parse_name_aux(self,"áéíóúàèìòùâêîôûãõäëïöüç")
	def test_parse_name_011(self):
		blank = "\t\r\v\f 	"
		name = "name"+blank+"starts"+blank+"here"
		test_parse_name_aux(self,blank+name+blank,name)