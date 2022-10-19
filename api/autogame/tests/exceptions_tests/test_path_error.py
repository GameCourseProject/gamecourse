#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import PathError

import unittest
import os


### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
### Path Error
### %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ###
class TestPathError(unittest.TestCase):

	def test_path_error_01(self):
		path = os.path.join(os.getcwd(), 'test_path_error_INVALID.py')
		with self.assertRaises(PathError) as cm:
			raise PathError(path)
		msg = "no such file or directory"
		errno = 2
		ex = cm.exception

		self.assertIsInstance(ex,IOError)
		self.assertEqual(ex.message,msg)
		#self.assertEqual(ex.strerror,msg)
		self.assertEqual(ex.errno,errno)
		self.assertEqual(ex.filename,path)
		self.assertEqual(ex.path,path)

		self.assertEqual(str(ex),"%s: %s" % (msg,path))
