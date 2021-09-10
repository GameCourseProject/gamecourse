#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
from .. import validate
from ..errors import PathError

DATADIR = 'data'

class PathData(object):
	""" PathData represents a structure to hold information on paths.
	Each path is associated with an index from which a datafile will be
	generated and the path to this file will also be associated with the
	original path.
	So we have:
		path --> index (stored in the 'indexpaths' dictionary)
		path --> datafile path (stored in the 'datapaths' dictionary)
	> An index is assigned to each path given. These indexes are the name of
	the	file where the data of the rules parsed (and more) from the path will be
	stored. These indexes are positive integers generated sequentially. For
	example, if pathA, pathB and pathC are respectivelly added to the system
	they will be assigned indexes 1, 2 and 3 respectivelly. If a path is later
	deleted on the system the index number assigned to it will be available to
	be assigned to other path. This is done with the 'next' and 'unused'. The
	next is an integer which represents the next sequential number, the unused
	is a list of indexes which were disassociated with their paths and were not
	the last index number.
	"""

	def __init__(self):
		self.next = 1
		self.unused = []
		self.datapaths = {}		# path --> data file path
		self.indexpaths = {}	# path --> index
		self.mtime = {}			# path --> time of last modification

	def __getitem__ (self,path):
		return self.get_datapath(path)

	def get_index(self,path):
		path = os.path.abspath(path)
		return self.indexpaths[path]

	def get_datapath(self,path):
		""" return the path to the datafile of the given path """
		return self.datapaths[path]

	def get_mtime(self,path):
		return self.mtime[path]

	def get_paths(self):
		return list(self.indexpaths.keys())

	def add(self,path):
		""" adds the path to the known paths """
		# check if the given path is valid
		validate.path(path)
		path = os.path.abspath(path) # convert to an absolute path
		index = self.generate_index()
		self.indexpaths[path] = index
		self.update_indexes(index)
		# generate the data file path and add it to the system
		if not os.path.isdir(path):
			datadir = os.path.join(os.path.dirname(path),DATADIR)
			base = os.path.basename(path)
			base = base[:base.find('.')] # remove the extension from the name
			filename = '%s_file_%s.dat' % (base,index)
		else:
			datadir = os.path.join(path,DATADIR)
			filename = '%s_dir_%s.dat' % (os.path.basename(path),index)
		datapath = os.path.join(datadir,filename)
		self.datapaths[path] = datapath
		self.mtime[path] = os.path.getmtime(path)

	def generate_index (self):
		""" returns an index for the next path
		to be added to the system """
		if len(self.unused) == 0:
			return self.next
		else:
			# return the lowest index number of the unused
			return min(self.unused)

	def update_indexes (self, index):
		""" Updates the paths with the given index """
		if index >= self.next:
			self.next += 1
		else:
			self.unused.remove(index)

	def exists (self, path):
		""" returns True if the path was added before,
		False otherwise """
		return path in self.indexpaths

	def remove (self, path):
		""" removes the path from the structure and removes all datafiles
		associated with it """
		if not self.exists(path):
			msg = "cannot remove a path that doesn't belong to the system: "
			msg+= str(path)
			raise PathError(msg)
		# remove the data file associated with it
		if os.path.isfile(self.get_datapath(path)):
			os.remove(self.get_datapath(path))
		# updates the index structure
		index = self.get_index(path)
		if index == (self.next-1):
			self.next -= 1
		else:
			self.unused.append(index)
		# remove the path from the structure
		self.datapaths.pop(path)
		self.indexpaths.pop(path)
		self.mtime.pop(path)