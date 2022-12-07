#!/usr/bin/env python
# -*- coding: utf-8 -*-

class PathError(IOError):
	""" Exception if there is an error in a Path given """
	def __init__(self, path):
		msg = "no such file or directory"
		super(PathError, self).__init__(2,msg,path)
		self.message = msg
		self.path = path

	def __str__(self):
		return self.message + ": " + self.path