#!/usr/bin/env python
# -*- coding: utf-8 -*-

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# DECORATORS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def rule_effect(func):
	from ..rules import Effect
	def func_wrapper(*args, **kwargs):
		return Effect(func(*args, **kwargs))
	func_wrapper.__rule_effect__ = True
	return rule_function(func_wrapper)

def rule_function(func):
	""" decorator to flag functions to be imported """
	func.__gamerules__ = True
	return func


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# FUNCTIONS TO IMPORT FUNCTIONS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def import_gamefunctions (scope):
	""" return a dictionary with all the functions
	defined in module 'gamefunctions'
	"""
	if scope is None:
		scope = {}
	## import the module
	from . import gamefunctions
	## iterate through all name definitions in module 'functions'
	for name in dir(gamefunctions):
		# skip unwanted names
		if name.startswith('_'):
			continue
		obj = eval('gamefunctions.%s' % name)
		# only add callable objects that have '__gamerules__' attribute
		if callable(obj) \
		and hasattr(obj,"__gamerules__"):
			scope[name] = obj
	return scope

def import_functions_from_FuncPaths (fp):
	functions = {}
	import imp
	for p in fp:
		for f in fp[p].functions:
			m = imp.load_source(fp[p].module,p)
			stmt = fp[p].module + "." + f
			scope = {fp[p].module: m}
			obj = eval(stmt,scope)
			if hasattr(obj,"__rule_effect__"):
				obj = rule_effect(obj)
			functions[f] = obj
	return functions
import_functions_from_FPaths = import_functions_from_FuncPaths

def import_functions_from_rulepath (p, info=False):
	
	if not isinstance(p,str):
		raise TypeError("path must be a string")
	import os
	p = os.path.abspath(p)
	if os.path.isfile(p):
		p = os.path.dirname(p)
	from .func_paths import FPaths
	functions = {}
	fpaths = FPaths()
	if not os.path.exists(p):
		return functions, fpaths
	import imp
	import inspect
	effect_rules = []
	for entry in os.listdir(p):
		# skip private names
		if entry.startswith("_"):
			continue
		# skip if the entry is not a Python file
		if entry[-3:] != ".py":
			continue
		module_path = os.path.join(p,entry)
		module_name = entry[:-3]

		mod = imp.load_source(module_name,module_path)
		# import functions from module
		for name in dir(mod):
			# skip private names
			if name.startswith("_"):
				continue
			stmt = module_name + "." + name
			obj = eval(stmt,{module_name:mod})
			# only import specific callable objects
			if not callable(obj) \
			   or not hasattr(obj,"__gamerules__"):
				continue
			if hasattr(obj,"__rule_effect__"):
				rule_info = {}
				rule_info["name"] = name
				rule_info["description"] = obj.__doc__
				rule_info["args"] = []

				for param in inspect.signature(obj).parameters.values():
					argument = {}
					argument["name"] = param.name
					argument["description"] = None
					if (param.default is param.empty):
						argument["type"] = None
						argument["optional"] = "0"
					else:
						if param.default != None:
							argument["type"] = type(param.default).__name__
						else:
							argument["type"] = None
						argument["optional"] = "1"

					rule_info["args"].append(argument)

				effect_rules.append(rule_info)
				obj = rule_effect(obj)
			functions[name] = obj
			fpaths.add(module_path,name)
	if not info:
		return functions, fpaths
	else:
		from inspect import signature, getdoc, cleandoc
		info = []

		for func in functions:
			function_info = {}
			function_info["moduleId"] = "gamerules"
			function_info["name"] = "gamerules"
			function_info["keyword"] = func
			function_info["args"] = []

			func_args = functions[func].__code__.co_varnames[:functions[func].__code__.co_argcount]
			func_sig = signature(functions[func])

			found = next((dict for dict in effect_rules if dict["name"] == func), None)

			if found:
				description = found["description"]
				for param in found["args"]:
					function_info["args"].append(param)

			else:
				description = getdoc(functions[func])

				for param in func_sig.parameters.values():
					argument = {}
					argument["name"] = param.name
					argument["description"] = None
					if (param.default is param.empty):
						argument["type"] = None
						argument["optional"] = "0"
					else:
						if param.default != None:
							argument["type"] = type(param.default).__name__
						else:
							argument["type"] = None
						argument["optional"] = "1"

					function_info["args"].append(argument)

			function_info["description"] = "" if description is None else description.replace("  ", "")
			function_info["returnType"] = None
			info.append(function_info)
		return functions, fpaths, info



def import_functions_from_module (module):
	import inspect
	functions = {}
	for name in dir(module):
		# skip private names
		if name.startswith("_"):
			continue
		if "." in module.__name__:
			module_name = module.__name__[::-1]
			module_name = module_name[:module_name.find(".")]
			module_name = module_name[::-1]
		else:
			module_name = module.__name__
		stmt = module_name + "." + name
		obj = eval(stmt,{module_name:module})
		# only import functions
		if not callable(obj) \
		   or not hasattr(obj,"__gamerules__"):
			continue
		if hasattr(obj,"__rule_effect__"):
			functions[name] = rule_effect(obj)
		else:
			functions[name] = obj
	return functions


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# AUXILIAR FUNCTIONS
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def rm_extension(s):
	return s[::-1][s[::-1].find(".")+1:][::-1]