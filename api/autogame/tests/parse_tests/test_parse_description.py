#!/usr/bin/env python
# -*- coding: utf-8 -*-

from aux_functions import test_parse_description_aux

import unittest

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Description Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseDescription (unittest.TestCase):
	def test_parse_description_000(self):
		test_parse_description_aux(self,"",pos=0)
	def test_parse_description_001(self):
		test_parse_description_aux(self,"sample description to be parse;;")
	def test_parse_description_002(self):
		test_parse_description_aux(self,"with##comments##","with")
	def test_parse_description_003(self):
		test_parse_description_aux(self,"with\nnewline")
	def test_parse_description_004(self):
		t = "with##comments\nnewline"
		f = "with\nnewline"
		test_parse_description_aux(self,t,f)
	def test_parse_description_005(self):
		test_parse_description_aux(self,"\n","")
	def test_parse_description_006(self):
		test_parse_description_aux(self," #","")
	def test_parse_description_007(self):
		desc = "ultimate description\n" * 50
		test_parse_description_aux(self,desc,desc[:len(desc)-1])
	def test_parse_description_008(self):
		d = "my#\n#\n\n#my"
		f = "my"
		test_parse_description_aux(self,d,f)

	def test_parse_description_009(self):
		d = "sample_description\n"
		f = "sample_description"
		p = len(d)
		l = d.count("\n") + 1
		d += "when: this shoudn't be parsed as description\n\n"
		test_parse_description_aux(self,d,f,p,l)

	def test_parse_description_010(self):
		d = "this is part of the description__ when __but this also this is\n"
		f = d[:len(d)-1]
		p = len(d)
		l = d.count("\n") + 1
		d += "when: this shoudn't be parsed as description\n\n"
		test_parse_description_aux(self,d,f,p,l)

	def test_parse_description_011 (self):
		d = "\t\t 		 \r\v  \n\n \t  	 \t"
		f = ""
		p = len(d)
		l = d.count("\n") + 1
		d += "then: this shoudn't be parsed as description\n\n"
		test_parse_description_aux(self,d,f,p,l)

	def test_parse_description_012(self):
		d = ""
		f = d
		d += "rule: this shoudn't be parsed as description\n\n"
		p = len(f)
		l = f.count("\n") + 1
		test_parse_description_aux(self,d,f,p,l)

	def test_parse_description_013(self):
		d = """This is the valid part of a description.
		As one can see, multiple sentences are allowed.
		Other thing that is allowed is:
			when,
			then,
			rule.
		As long as they're not at the beggining of the line
		and are followed by a colon \':\', they SHOULD be parsed."""
		f = d
		p = len(d)
		l = d.count("\n") + 1
		test_parse_description_aux(self,d,f,p,l)

	def test_parse_description_014 (self):
		d = "start\n then"
		f = d
		d += "#: stop"
		d1 = "\nthen start again"
		f += d1
		d += d1
		p = len(d)
		l = f.count("\n") + 1
		test_parse_description_aux(self,d,f,p,l)

	def test_parse_description_015 (self):
		d = """
		 	\t\r\f\v\n
		start 	\t\r\f\v\n
		end
		 	\t\r\f\v\n
		"""
		f = "start 	\t\r\f\v\n\n		end"
		p = len(d)
		l = d.count("\n") + 1
		test_parse_description_aux(self,d,f,p,l)
