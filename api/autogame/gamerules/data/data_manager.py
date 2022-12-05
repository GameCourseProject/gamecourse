#!/usr/bin/env python
# -*- coding: utf-8 -*-

import shelve
import os
import config
import sys

from .. import validate
from .. import rule_parser
from ..errors import PathError
from .path_data import PathData, DATADIR
from .target_data import TargetData

SETUP_FILE = 'setup.dat'
PATHS_KEY = 'paths'
RULELOGS = "rulelogs.csv"

class DataManager(object):

	def __init__ (self, autosave=True):
		self.paths = None
		self.autosave = autosave
		self.reset()
		self.boot()

	def reset(self):
		""" resets the state of the data manager """
		self.active_path = None # the path from where the rules in used were parsed
		self.rules = [] # list of active rules
		self.target_data = TargetData() # structure containing data on the targets
		self.scope = {}
		self.functions = {}
		self.fpaths = None

	def boot(self):
		""" reads from setup data file (setup.dat) to initialize paths data """
		if config.rules_folder == None:
			data_location = os.path.join(os.getcwd(), "data")
		else: 
			data_location = os.path.join(os.getcwd(), config.rules_folder, "data")	

		if not os.path.exists(data_location):
			os.mkdir(data_location)
			#os.system("mkdir " + data_location)

		setup_location = os.path.join(data_location, SETUP_FILE)
		setup = shelve.open(setup_location, writeback=True)
		if PATHS_KEY in setup:
			self.paths = setup[PATHS_KEY]
		else:
			paths = PathData()
			setup[PATHS_KEY] = paths
			self.paths = paths
		setup.close()

	def load(self, path):
		""" loads all the rules and related data associated with the given path,
		and sets this to the active path
		"""
		validate.path(path)
		path = os.path.abspath(path)
		# if it's the same path as the active, skip operation
		if path == self.active_path \
		and os.path.getmtime(path) == self.paths.get_mtime(path):
			return
		if self.autosave:
			self.save_state()
		# Check if this is the first time loading the path
		# OR if the path has been modified since last time
		if not self.paths.exists(path) \
		or os.path.getmtime(path) != self.paths.get_mtime(path):
			# parse rules from the path
			self.rules = rule_parser.parse(path)
			from ..functions.utils import import_functions_from_rulepath
			self.functions, self.fpaths = import_functions_from_rulepath(path)
			self.paths.add(path)
			if self.autosave:
				self.save_paths()
		else:
			# load rules from data file
			datapath = self.paths[path]
			# TO DO: this is a hack, check side effects
			# datapath += ".db"
			validate.path(datapath)
			data = shelve.open(datapath)
			self.rules = [r.unpickle() for r in data['rules']]
			self.target_data = data['target_data']
			self.scope = data['scope']
			self.fpaths = data['fpaths']
			data.close()
			from ..functions.utils import import_functions_from_FPaths
			self.functions = import_functions_from_FPaths(self.fpaths)
		self.active_path = path

	def save(self):
		""" saves the current state of the DataManager into a datafile specific
		to the active file
		"""
		# 1 - save the state into the data file
		self.save_state()
		# 2 - update the setup file
		self.save_paths()

	def save_state(self):
		""" saves only the state of the system """
		if self.active_path is not None:
			savepath = self.paths[self.active_path]
			# check if the paths were not corrupted
			try:
				validate.path(self.active_path)
				validate.path(os.path.dirname(os.path.dirname(savepath)))
			except PathError as pe:
				self.remove(self.active_path)
				return
			# check if the data folder were the datafile is stored exists
			if not os.path.exists(os.path.dirname(savepath)):
				if os.path.isdir(self.active_path):
					dirpath = os.path.join(self.active_path,DATADIR)
				else:
					dirpath = os.path.dirname(self.active_path)
					dirpath = os.path.join(dirpath,DATADIR)
				os.mkdir(dirpath)
			data = shelve.open(savepath)
			data['rules'] = [r.to_pickle() for r in self.rules]
			data['target_data'] = self.target_data
			data['scope'] = self.scope
			data['fpaths'] = self.fpaths
			data.close()

	def save_paths(self):
		""" saves the paths structure """
		if config.rules_folder == None:
			data_location = os.path.join(os.getcwd(), "data")
		else: 
			data_location = os.path.join(os.getcwd(), config.rules_folder, "data")	

		if not os.path.exists(data_location):
				os.mkdir(data_location)

		setup_location = os.path.join(data_location, SETUP_FILE)
		data = shelve.open(setup_location)
		data['paths'] = self.paths
		data.close()

	def store_logs(self,logs):
		""" saves a list of rule logs into a text file """
		if self.active_path is None:
			# ERROR: can't save the state if there is no 'active_path'
			# since it's the key
			return
		text = ""
		for log in logs:
			text+= "%s\n" % log
		datapath = self.paths[self.active_path]
		logspath = datapath[:-4] + "_" + RULELOGS
		if not os.path.exists(logspath):
			text = "RULE, TARGET, TIMESTAMP, OUTPUT\n" + text
			# create 'data' directory if it doesn't already exist
			datadir = os.path.dirname(logspath)
			if not os.path.exists(datadir):
				os.mkdir(datadir)
		with open(logspath,"a") as f:
			f.write(text)

	# def verify_obsolete_paths(self):
	# 	""" verifies if any path in the system was removed from the system,
	# 	if so, removes the path and all data associated to it
	# 	"""
	# 	if self.paths is None:
	# 		return
	# 	to_remove = []
	# 	for p in self.paths.datapaths.keys():
	# 		if not os.path.exists(p):
	# 			to_remove.append(p)
	# 	for p in to_remove:
	# 		self.remove(p)

	def remove(self, path):
		""" removes the path (if it exists in the system) and all associated
		data
		"""
		self.paths.remove(path)
		self.save_paths()
		if path == self.active_path:
			self.reset()
