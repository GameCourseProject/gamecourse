#!/usr/bin/env python
# -*- coding: utf-8 -*-

class Prize:

	def __init__(self,awards=None,indicators=None):
		self.awards = {} if awards is None else awards
		self.indicators = {} if indicators is None else indicators

	def __str__(self):
		return "AWARDS: %s\nINDICATORS: %s\n" % (self.awards, self.indicators)

	def __repr__(self):
		m = "Prize{Awards(%d): %s" % (len(self.awards), str(self.awards))
		m += ", Indicators(%s):" % len(self.indicators) + str(self.indicators)
		return m +"}"

	def __eq__(self,other):
		""" equal returns true if both objects have the same awards and
		indicators, the is not relevant, returns false in any other case
		"""
		try: # EAFP
			if len(self.awards) != len(other.awards) \
			or len(self.indicators) != len(other.indicators):
				return False
			for s in self.awards:
				if len(self.awards[s]) != len(other.awards[s]):
					return False
				for award in self.awards[s]:
					if award not in other.awards[s]:
						return False
			return True
		except AttributeError:
			return False # other object didn't have awards or indicators

	def __ne__(self,other):
		return not self == other

	def join(self,prize):
		""" joins (merges) the awards and indicators of the given prize with
		the awards and indicators of other prize
		"""
		# joinning the awards is straightforward, just append the new ones to
		# the end of the awards list of each student
		for s in prize.awards:
			if s not in self.awards:
				self.awards[s] = prize.awards[s]
			else:
				self.awards[s].extend(prize.awards[s])
		# the indicators can be a little bit tricky, imagining both prizes have
		# the same student and for that student the same achievement, if we
		# simply use the method 'update' from the 'dict', it will overwright
		# the indicators of this prize for that achievement and student
		# Example, imagine we have:
		# >>> self.indicators["student1"]["PostMaster"] = (2, [log1,log2])
		# >>> prize.indicators["student"]["PostMaster"] = (1, [log333])
		# if we simply use the 'update' we will get:
		# >>> self.indicators["student1"]["PostMaster"] = (1, [log333])
		# since it will overwrite what is on the prize.indicators into the
		# self.indicators. But, what we really woul like to obtain is:
		# >>> self.indicators["stuent1"]["PostMaster"] = (3, [log1,log2,log333])
		# so in order to achieve this result we have to scan through the
		# students and achievements of 'prize.indicators' and see if they don't
		# exist in this prize, if they dd, instead of just updating the results
		# we have to "merge" them

		for s in prize.indicators:
			if s not in self.indicators:
				# if the student doesn't exist --> update
				self.indicators[s] = prize.indicators[s]
				continue
			for a in prize.indicators[s]:
				# if the achievement doesn't exist for this student --> update
				if a not in self.indicators[s]:
					self.indicators[s][a] = prize.indicators[s][a]
					continue
				# in this scenario we will have to merge the indicators
				count1, indicators1 = prize.indicators[s][a]
				# if the count1 is False it means there actually isn't no
				# indicators for this achievement of this student on the prize
				# to be joined, so we can just skip to the next achievement

				# TODO: Check if this count1 == False works

				if count1 is False:
					continue
				# if count2 is False it's the same scenario but for this prize
				# in this case we simply do an update
				count2, indicators2 = self.indicators[s][a]
				if count2 is False:
					self.indicators[s][a] = count1, indicators1
					continue
				# if all else fails, then a merge will occur
				for i in indicators1:
					if i not in indicators2:
						indicators2.append(i)
				self.indicators[s][a] = len(indicators2), indicators2

	def join_multiple(self,prizes):
		""" joins all the awards and indicators from all the given prizes to
		this one
		"""
		for prize in prizes:
			self.join(prize)
