#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .. import validate

class BaseNode(object):
	'''
	This class specifies objects of type BaseNode. They represent the most
	abstract concept of rule elements.
	They contain just two things:
		--> a file path
		--> a line
	'''
	def __init__(self, filepath, line):
		validate.line(line)
		validate.filepath(filepath)
		self.__filepath = filepath
		self.__line = line
		self.__init_alternatenames()

	def __eq__ (self, other):
		return isinstance(other, BaseNode) \
		and self.line() == other.line() \
		and self.file() == other.file()

	def __ne__ (self, other):
		return not isinstance(other, BaseNode) \
		or self.file() != other.file() \
		or self.line() != other.line()

	def line (self):
		return self.__line

	def path (self):
		return self.__filepath

	def __init_alternatenames (self):
		""" Alternate names for certain functions """
		self.file = self.path
		self.fpath = self.path
		self.filepath = self.fpath
		self.file_path = self.fpath
		self.location = self.fpath