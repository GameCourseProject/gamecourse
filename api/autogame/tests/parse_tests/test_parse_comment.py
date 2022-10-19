#!/usr/bin/env python
# -*- coding: utf-8 -*-

from aux_functions import test_parse_comment_aux

import unittest

### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Parse Comments Tests
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestParseComment (unittest.TestCase):
	def test_parse_comment_001(self):
		comment = "#with ####comments####"
		test_parse_comment_aux(self,comment,pos=len(comment))
	def test_parse_comment_002(self):
		test_parse_comment_aux(self,"#w\n###",pos=len("#w\n")-1)
