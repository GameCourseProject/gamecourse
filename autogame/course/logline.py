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

	def __init__(self, log_id, user, course, description, log_type, module_instance, post, date, rating, evaluator):
		self.log_id = log_id
		self.user = user
		self.course = course
		self.description = description
		self.log_type = log_type
		self.module_instance = module_instance
		self.post = post
		self.date = date
		self.rating = rating
		self.evaluator = evaluator

	"""
	def __init__(self, num, name, timestamp, action, xp, info=None, url=None):
		self.num = num
		self.name = name
		self.timestamp = timestamp
		self.action = action
		self.xp = xp
		self.info = info
		self.url = url
	"""

	def __str__(self):
		return self.__repr__()


	def __repr__(self):
		attributes = [self.log_id, self.user, self.course, self.description, self.log_type, \
			self.module_instance, self.post, self.date, self.rating, self.evaluator]
		logline = "("
		for el in attributes:
			logline += str(el)
			logline += ", "
		logline = logline[:-2] + ")"
		return logline


	"""
	def __repr__(self):
		preinfo = self.info

		if isinstance(self.info,tuple):
			ul = "(" + self.info[0] + ", "
			ul+= self.info[1] +")"		

		elif isinstance(self.info,str):	
			ul = self.info
		else:
			ul = self.info

		self.info = ul
		tmpurl = self.url

		if tmpurl == None:
			self.url = "null"

		tmp = json.dumps(self.__dict__)
		
		self.info = preinfo
		self.url = tmpurl
		
		return tmp
	"""

