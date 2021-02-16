#!/usr/bin/env python
# -*- coding: utf-8 -*-


# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Achievements Main Class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Achievement:
	def __init__ (self,n,d,c1,c2,c3,xp1,xp2,xp3,is_counted,is_postbased):
		self.name = n
		self.description = d
		self.criteria = c1, c2, c3
		self.counted = is_counted
		self.post_based = is_postbased
		self._top_level = 3 if xp3 != "" else 2 if xp2 != "" else 1
		xp1, xp2, xp3 = convert_xp(xp1), convert_xp(xp2), convert_xp(xp3)
		self.extra = xp1<0, xp2<0, xp3<0
		self.xp = abs(xp1), abs(xp2), abs(xp3)
		self._unrewarder = self.xp[0]==0

	def __eq__(self,other):
		try:
			return self.name == other.name \
				   and compare_iterable(self.criteria, other.criteria) \
				   and compare_iterable(self.xp, other.xp)
		except Exception:
			return False

	def __ne__(self,other):
		return not self == other

	def __str__(self):
		m = "%s (%s)\n" % (self.name, self.description)
		m += "\tlevel_1: %d XP (%s)" % (self.xp[0], self.criteria[0])
		if self.top_level() >= 2:
			m += "\n\tlevel_2: %d XP (%s)" % (self.xp[1], self.criteria[1])
		if self.top_level() >= 3:
			m += "\n\tlevel_3: %d XP (%s)" % (self.xp[2], self.criteria[2])
		return m

	def __repr__(self):
		m = "Achievement('%s'" % self.name
		m += xp_tostring(self.xp)
		if self.is_counted():
			m += ", COUNTED"
		if self.is_postbased():
			m += ", POSTBASED"
		return m + ")"

	def has_extra(self):
		return self.extra[0] or self.extra[1] or self.extra[2]

	def is_counted(self):
		return self.counted

	def is_postbased(self):
		return self.post_based

	def top_level(self):
		return self._top_level

	def unrewarded(self):
		 return self._unrewarder

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
def convert_xp(value):
	""" converts a value into an integer, if it's not possible returns zero """
	try:
		return int(value)
	except Exception:
		return 0

def xp_tostring(xp):
	""" converts a itrable object of xp to a string """
	def xp_tostring_lvl(val,lvl):
		return ", %d: %d" % (lvl,val) if val > 0 else ""
	m = ""
	if xp[0] > 0:
		m += ", [1: %d" % xp[0]
		for i in range(1,len(xp)):
			m += xp_tostring_lvl(xp[i],i+1)
		m += "]"
	return m

def compare_iterable(i1,i2):
	if len(i1) != len(i2):
		return False
	for i in range(len(i1)):
		if i1[i] != i2[i]:
			return False
	return True