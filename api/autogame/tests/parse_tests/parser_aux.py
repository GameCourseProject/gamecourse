#!/usr/bin/env python
# -*- coding: utf-8 -*-

from os import listdir, makedirs, remove, rmdir
from os.path import isdir, isfile, join
from shutil import copy
from random import randint, choice


def create_folder_from_folders (path, foldername):
	"""
	creates a folder named 'foldername' and copies all files
	from all folders in path to it
	"""
	if not isinstance(foldername,str):
		msg = "in create_folder(path,foldername), expected type \'str\' "
		msg+= "for argument \'foldername\', instead received type \'"
		msg+= type(foldername).__name__ + "\'."
		raise TypeError(msg)
	if not isinstance(path,str):
		msg = "in create_folder(path,foldername), expected type \'str\' "
		msg+= "for argument \'path\', instead received type \'"
		msg+= type(path).__name__ + "\'."
		raise TypeError(msg)
	if not isdir(path):
		raise TypeError("expected a dirpath for argument \'path\'")
	if foldername in listdir(path):
		return
	src_folders = [join(path, f) for f in listdir(path) if isdir(join(path,f))]
	dst = join(path,foldername)
	makedirs(dst)
	copy_folders_files(src_folders,dst)

def copy_folders_files (folders, dest):
	"""
	copy all files of a the list of folders named 'folders'
	into the folder 'dest'
	"""
	if not isinstance(folders,list):
		raise TypeError("expected a \'list\' for argument \'folders\'")
	if not isinstance(dest, str):
		raise TypeError("expected a \'str\' for argument \'dest\'")
	if not isdir(dest):
		raise TypeError("expected a \'dirpath\' for argument \'dest\'")
	if len(folders) > 0:
		for folder in folders:
			if not isdir(folder):
				raise TypeError("expected a \'dirpath\' for elements of \'folders\'")
			copy_files(folder,dest)

def copy_files (orig, dest):
	""" copy all files in folder 'orig' to folder 'dest' """
	if not isinstance(orig, str):
		raise TypeError("expected a \'str\' for argument \'orig\'")
	if not isinstance(dest, str):
		raise TypeError("expected a \'str\' for argument \'dest\'")
	if not isdir(orig):
		raise TypeError("expected a dirpath for argument \'orig\'")
	if not isdir(dest):
		raise TypeError("expected a dirpath for argument \'dest\'")
	files = [join(orig,f) for f in listdir(orig) if isfile(join(orig,f))]
	for file in files:
		copy(file,dest)

def remove_folder_from_path (path, foldername):
	""" Remove folder 'foldername' in 'path' and all its contents """
	if not isinstance(foldername,str):
		raise TypeError("expected a \'string\' for argument \'foldername\'")
	if not isinstance(path, str):
		raise TypeError("expected a \'str\' for argument \'orig\'")
	if not isdir(path):
		raise TypeError("expected a \'dirpath\' for argument \'path\'")
	src = join(path,foldername)
	if not isdir(src):
		raise Exception("\'"+src+"\' must be a path to a directory")
	to_delete = listdir(src)
	for name in to_delete:
		obj = join(src,name)
		if isfile(obj):
			remove(obj)
		# elif isdir(obj):
		# 	remove_folder_from_path(obj) # Recursion!
	rmdir(src)


def get_is_followed_valid_values():
	a = (str(randint(0,9)),str(randint(0,9)),str(randint(0,9)))
	b = (str(randint(0,9)),str(randint(0,9)),str(randint(0,9)))
	t = ""
	for i in range(10):
		t += choice(" \t\r\v")
	t += a[randint(0,len(a)-1)]
	for i in range(10):
		t += choice(" \t\r\v")
	t += b[randint(0,len(b)-1)]
	p = randint(0,10)
	return a,b,t,p

def capcombos (word):
	'''
	Return a list with all the combinations of
	upper and lower letters in the word
	'''
	def capcombos_aux (i, word):
		if i+1 > len(word):
			return [word]
		w1 = word[:i] + word[i].lower() + word[i+1:]
		w2 = word[:i] + word[i].upper() + word[i+1:]
		if w1 != w2:
			result = capcombos_aux(i+1,w1)
			result+= capcombos_aux(i+1,w2)
		else:
			result = capcombos_aux(i+1,w1)
		return result
	return capcombos_aux(0,word)
