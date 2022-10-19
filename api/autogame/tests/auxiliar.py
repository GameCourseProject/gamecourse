#!/usr/bin/env python
# -*- coding: utf-8 -*-

class Award:
	def __init__(self,target,achievement,lvl):
		self.target = target
		self.achievement = achievement
		self.lvl = lvl
	def __eq__ (self,other):
		try:
			return self.target == other.target \
				and self.achievement == other.achievement
		except Exception:
			return False
	def __ne__(self,other):
		return not self == other
	def __gt__(self,other):
		return self == other and self.lvl > other.lvl
	def __lt__(self,other):
		return self == other and self.lvl < other.lvl
	def __ge__(self,other):
		return self == other and self.lvl >= other.lvl
	def __le__(self,other):
		return self == other and self.lvl <= other.lvl
	def __repr__(self):
		return "Award(%s,%s,%s)" % (self.target,self.achievement,self.lvl)
	def __str__(self):
		return "%s<target:%s, lvl:%d>" % (self.achievement,self.target,self.lvl)
