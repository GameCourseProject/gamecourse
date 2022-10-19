#!/usr/bin/env python
# -*- coding: utf-8 -*-

class FPaths:

	def __init__ (self):
		self._fp = {} # paths to (module_name, function_names)

	def __getitem__(self,p):
		return self._fp[p]

	def __iter__ (self):
		return iter(self._fp)

	def __repr__ (self):
		return repr(self._fp)

	def __str__ (self):
		return repr(self)

	def __eq__ (self,other):
		return isinstance(other,FPaths) \
		and self._fp == other._fp

	def __ne__ (self,other):
		return not self == other

	def add (self, path, function):
		if path not in self._fp:
			self._fp[path] = ModuleFunctions(path)
		self._fp[path].add(function)

	def paths(self):
		return list(self._fp.keys())


class ModuleFunctions:

	def __init__(self,path):
		import os
		from .utils import rm_extension
		self.module = rm_extension(os.path.basename(path))
		self.functions = []

	def __repr__(self):
		return "<" + self.module + ": " + repr(self.functions) + ">"

	def __str__ (self):
		return repr(self)

	def __eq__ (self,other):
		return isinstance(other,ModuleFunctions) \
		and self.module == other.module \
		and self.functions == other.functions

	def __ne__ (self,other):
		return not self == other

	def add(self,function):
		self.functions.append(function)
