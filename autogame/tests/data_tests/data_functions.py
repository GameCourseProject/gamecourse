#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os

def rmdir (dirpath, flags=None):
	""" removes the directory and all its entries,
	specified in the path.
	"""
	if not os.path.isdir(dirpath):
		return

	if flags is None:
		flags = ''

	if 'r' in flags or 'R' in flags:
		rmdir_entries(dirpath, flags)
	
	if 'v' in flags or 'V' in flags:
		print("removing: '%s'" % dirpath)
	
	# safely removes the directory
	os.rmdir(dirpath)

def rmdir_entries(dirpath, flags=None):
	""" removes all the entries from a directory """
	
	if flags is None:
		flags = ''

	# to remove a directory all its files and sub-directories
	# have to be removed first
	entries = os.listdir(dirpath)
	verbose = True if ('v' in flags or 'V' in flags) else False

	for c in entries:
		path = os.path.join(dirpath,c)
		# if one of the entries is a directory,
		# it must be removed recursively
		if os.path.isdir(path):
			rmdir(path,flags)
			continue
		# other entries can be removed directly
		if verbose:
			print ("removing: '%s'" % path)
		os.remove(path)