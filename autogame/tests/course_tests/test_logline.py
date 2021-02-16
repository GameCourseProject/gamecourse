#!/usr/bin/env python
# -*- coding: utf-8 -*-

from base_test_class import BaseTestClass
from context import course
from course import LogLine

import time

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestLogLine(BaseTestClass):

	def assertLogLine(self,log):
		self.assertIsInstance(log,LogLine)
		self.assertIn("log_id",dir(log))
		self.assertIn("user",dir(log))
		self.assertIn("course",dir(log))
		self.assertIn("description",dir(log))
		self.assertIn("log_type",dir(log))
		self.assertIn("module_instance",dir(log))
		self.assertIn("post",dir(log))
		self.assertIn("date",dir(log))
		self.assertIn("rating",dir(log))
		self.assertIn("evaluator",dir(log))


	def assertCreation(self, p_id, user, course, description, log_type, module_instance, post, date, rating, evaluator):
		log = LogLine(p_id, user, course, description, log_type, module_instance, post, date, rating, evaluator)
		self.assertLogLine(log)
		self.assertEqual(log.log_id,p_id)
		self.assertEqual(log.user,user)
		self.assertEqual(log.course,course)
		self.assertEqual(log.description,description)
		self.assertEqual(log.log_type,log_type)
		self.assertEqual(log.module_instance,module_instance)
		self.assertEqual(log.post,post)
		self.assertEqual(log.date,date)
		self.assertEqual(log.rating,rating)
		self.assertEqual(log.evaluator,evaluator)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation tests (__init__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestLogLine):
	
	def test_01 (self):
		""" test valid LogLine creation """
		
		self.assertCreation(12345,"82433", 1, "Hello World","add post",
			300, "https://www.youtube.com/watch?v=hCuMWrfXG4E", "2020-11-17 21:06:03", 4, 6)