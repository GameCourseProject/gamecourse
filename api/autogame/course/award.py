#!/usr/bin/env python
# -*- coding: utf-8 -*-

class Award:

	def __init__(self, student, achievement, lvl, xp, badge, ts=None,info=None):
		# student number
		self.student = student
		# achievement name
		self.achievement = achievement
		# achievement level (0 if not applicable)
		self.level = lvl
		# awarded XP
		self.xp = xp
		# true or false, depending on whether this is a badge award
		# or just XP (quizes, etc.)
		self.badge = badge
		# when was this awarded
		if ts is not None:
			self.timestamp = ts
		else:
			import time
			self.timestamp = time.time()
		# how was this obtained? (lecture num, url, etc)
		self.info = info

	def __str__(self):
		m = "%s;%s;%s;" % (self.timestamp, self.student, self.achievement)
		m+= str(self.level) if self.badge else str(self.xp)
		return m + ";" + str(self.info)

	def __repr__(self):
		return "Award<" + str(self) + ">"

	def __eq__(self, other):
		try:
			if self.student == other.student \
			and self.achievement == other.achievement \
			and self.level == other.level \
			and self.badge == other.badge:
				if not self.badge and self.info != other.info:
						return False
				return 2 if self.xp == other.xp else 1
			return False
		except AttributeError:
			return False

	def __ne__ (self,other):
		return not self == other
