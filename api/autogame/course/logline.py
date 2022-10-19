#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
This class models the concept of a 'log line'. This is to create a consistent
way to deal with logs, as not all of them have the same information.
"""

import json
import codecs

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# LogLine Main Class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class LogLine:

	def __init__(self, log_id, user, course, description, log_type, post, date, rating, evaluator):
		self.log_id = log_id
		self.user = user
		self.course = course
		self.description = description
		self.log_type = log_type
		self.post = post
		self.date = date
		self.rating = rating
		self.evaluator = evaluator

	def __str__(self):
		return self.__repr__()

	def __repr__(self):
		attributes = [self.log_id, self.user, self.course, self.description, self.log_type, \
			self.post, self.date, self.rating, self.evaluator]
		logline = "("
		for el in attributes:
			logline += str(el)
			logline += ", "
		logline = logline[:-2] + ")"
		return logline
