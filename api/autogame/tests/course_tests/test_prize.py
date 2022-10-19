#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import course
from course import Prize
from course import Award
from base_test_class import BaseTestClass

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Base class
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestPrize(BaseTestClass):

	def gen_awards(self, num_students, num_awards):
		awards = {}
		for i in range(num_students):
			student = "s%d" % i
			awards[student] = []
			for i in range(num_awards):
				achievement = "a%d" % i
				lvl = i
				xp = i*i
				badge = i % 2 == 0
				timestamp = i
				info = "info"
				awards[student].append(
					Award(student,achievement,lvl,xp,badge,timestamp,info))
		return awards

	def gen_indicators(self, num_students, num_achievements):
		indicators = {}
		for i in range(num_students):
			student = "s%d" % i
			indicators[student] = {}
			for j in range(num_achievements):
				achievement = "a%d" % j
				count = i+j
				logs = 'x' * count
				if count == 0:
					count = False
					logs = None
				indicators[student][achievement] = count, logs
		return indicators

	def gen_prizes(self, num):
		prizes = []
		for i in range(num):
			student = "s%d" % i
			achievement = "a%d" % i
			lvl = i
			xp = i * 50
			badge = i % 2 == 0
			timestamp = 1001
			info = "gen_prizes(%d)" % num
			award = Award(student,achievement,lvl,xp,badge,timestamp,info)
			a = {student: [award]}
			x = i % 4
			indicators = None if x == 0 else ["c%d" % j for j in range(x)]
			indicators = (False, indicators) if x == 0 else (x, indicators)
			i = {student: {achievement: indicators}}
			prizes.append(Prize(a,i))
		return prizes

	def assertPrize(self,p):
		self.assertIsInstance(p,Prize)
		self.assertIn("awards",dir(p))
		self.assertIn("indicators",dir(p))
		self.assertIsInstance(p.awards,dict)
		self.assertIsInstance(p.indicators,dict)
		self.assertAwards(p.awards)
		self.assertIndicators(p.indicators)

	def assertAwards(self,awards):
		for s in awards:
			for a in awards[s]:
				self.assertAward(a)

	def assertIndicators(self,indicators):
		for student in indicators:
			achievements = indicators[student]
			self.assertIsInstance(achievements,dict)
			for achievement in achievements:
				self.assertIsInstance(achievements[achievement],tuple)
				self.assertEqual(len(achievements[achievement]),2)
				count, ta_indicators = achievements[achievement]
				self.assertIsInstance(count,(str,int,bool))
				if isinstance(count,str):
					count = bool(count) if count == "False" else int(count)
				if count is False:
					self.assertEqual(count,0)
				else:
					self.assertGreater(count,0)
					self.assertEqual(count,len(ta_indicators))

	def assertCreation(self,awards,indicators):
		p = Prize(awards,indicators)
		self.assertPrize(p)
		for a in awards:
			self.assertIn(a,p.awards)
		for i in indicators:
			self.assertIn(i, list(p.indicators.keys()))
			self.assertEqual(indicators[i],p.indicators[i])

	def assertEQ(self,p1,p2):
		self.assertPrize(p1)
		self.assertEqual(p1,p2)
		self.assertTrue(p1 == p2)
		self.assertFalse(p1 != p2)

	def assertNE(self,p1,p2):
		self.assertPrize(p1)
		self.assertNotEqual(p1,p2)
		self.assertTrue(p1 != p2)
		self.assertFalse(p1 == p2)

	def assertJoin(self,p1,p2):
		self.assertPrize(p1)
		p1_awards = dict(p1.awards)
		p1_indicators = dict(p1.indicators)
		p1.join(p2)
		self.assertPrize(p1)
		num_awards = len(p1_awards) + len(p2.awards)
		self.assertLessEqual(len(p1.awards),num_awards)
		num_indicators = len(p1_indicators) + len(p2.indicators)
		self.assertLessEqual(len(p1.indicators),num_indicators)
		# validate awards
		for s in p1.awards:
			if s not in p1_awards:
				self.assertIn(s,p2.awards)
				self.assertEqual(p1.awards[s],p2.awards[s])
				continue
			if s not in p2.awards:
				self.assertEqual(p1.awards[s],p1_awards[s])
				continue
			for a in p1.awards[s]:
				self.assertIn(a,p2.awards[s]+p1_awards[s])
		# validate indicators
		for s in p1.indicators:
			if s not in p1_indicators:
				self.assertIn(s,p2.indicators)
				self.assertEqual(p1.indicators[s], p2.indicators[s])
				continue
			if s not in p2.indicators:
				self.assertEqual(p1.indicators[s], p1_indicators[s])
				continue
			for a in p1.indicators[s]:
				if a not in p1_indicators[s]:
					self.assertIn(a,p2.indicators[s])
					self.assertEqual(p1.indicators[s][a], p2.indicators[s][a])
					continue
				if a not in p2.indicators[s]:
					self.assertEqual(p1.indicators[s][a], p1_indicators[s][a])
					continue
				count, sa_indicators = p1.indicators[s][a]
				count1, sa_indicators1 = p1_indicators[s][a]
				count2, sa_indicators2 = p2.indicators[s][a]
				if count2 is False:
					# nothing was joined for this achivement of this student
					self.assertEqual(count, count1)
					self.assertEqual(sa_indicators, sa_indicators1)
				elif count1 is False:
					# the indicators for this achievement of the current student
					# are the same as the joined indicators of the same
					# achievement and student
					self.assertEqual(count, count2)
					self.assertEqual(sa_indicators, sa_indicators2)
				else:
					# a merge was performed
					unique = set()
					for c in sa_indicators1 + sa_indicators2:
						unique.add(c)
						self.assertIn(c,sa_indicators)
					self.assertEqual(count, len(unique))

	def assertJoinMul(self,prize,prizes):
		self.assertPrize(prize)
		p_awards = dict(prize.awards)
		p_indicators = dict(prize.indicators)
		prize.join_multiple(prizes)
		self.assertPrize(prize)
		prizes.append(Prize(p_awards,p_indicators))
		for p in prizes:
			# Validate awards
			for s in p.awards:
				self.assertIn(s,prize.awards)
				for a in p.awards[s]:
					self.assertIn(a,prize.awards[s])
			# Validate awards
			for s in p.indicators:
				self.assertIn(s,prize.indicators)
				for a in p.indicators[s]:
					self.assertIn(a,prize.indicators[s])
					p_count, p_indicators = p.indicators[s][a]
					prize_count, prize_indicators = prize.indicators[s][a]
					if p_count is False:
						continue
					for i in p_indicators:
						self.assertIn(i,prize_indicators)
					# refactor: this previous statement seems not to make sense:
					#self.assertLessEqual(p_count, p_indicators)
					self.assertLessEqual(p_count, prize_count)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Creation tests (__init__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestCreation(TestPrize):

	def test_01(self):
		""" test prize creation with valid arguments """
		a = {"s1":[Award("s1","Hero",3,450,False)]}
		i = {"s1":{"a1":(3,("a1","a2","a3"))}}
		self.assertCreation(a,i)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Comparison tests (__eq__, __ne__)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestComparison(TestPrize):

	def test_01(self):
		""" test two equal prizes generated with the same arguments """
		a = {"s1":[Award("s1","Hero",3,450,False)]}
		i = {"s1":{"a1":(3,("a1","a2","a3"))}}
		self.assertEQ(Prize(a,i),Prize(a,i))

	def test_02(self):
		""" test two diff prizes (one of the prizes as one more award) """
		a1 = {"s1":[Award("s1","Hero",3,450,False)]}
		a2 = {"s1":[Award("s1","Hero",3,450,False)]}
		a2["s1"].append(Award("s2","Villan",77,31,True))
		i = {"s1":{"a1":(3,("a1","a2","a3"))}}
		p1 = Prize(a1,i)
		p2 = Prize(a2,i)
		self.assertNE(p1,p2)

	def test_03(self):
		""" test two diff prizes (same amount of awards, but different) """
		a1 = {"s1":[Award("s1","Hero",3,450,False)]}
		a2 = {"s1":[Award("s2","Villan",43,72,True)]}
		i = {"s1":{"a1":(3,("a1","a2","a3"))}}
		p1 = Prize(a1,i)
		p2 = Prize(a2,i)
		self.assertNE(p1,p2)

	def test_04(self):
		""" test comparison between a prize and a different object """
		a = {"s1":[Award("s1","Hero",3,450,False)]}
		i = {"s1":{"a1":(3,("a1","a2","a3"))}}
		p = Prize(a,i)
		self.assertNE(p,repr(p))

	def test_05 (self):
		""" test comparison between two prizes with different amount of students
		in the awards
		"""
		a1 = {"s1":[Award("s1","Hero",3,450,False)]}
		a2 = {"s1":[Award("s1","Hero",3,450,False)]}
		a2["s2"] = [Award("s2","Villan",77,31,True)]
		i = {"s1":{"a1":(3,("a1","a2","a3"))}}
		p1 = Prize(a1,i)
		p2 = Prize(a2,i)
		self.assertNE(p1,p2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Prize.join(Prize p) --> joins the awards and indicators from a prize to this
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestJoin(TestPrize):

	def test_01(self):
		""" Test join: join two empty prizes """
		self.assertJoin(Prize(),Prize())

	def test_02(self):
		""" test join: one prize with awards but no indicators the other is just
		empty
		"""
		self.assertJoin(Prize(self.gen_awards(1,3)),Prize())

	def test_03(self):
		""" test join: one prize with awards and indicators the other is just
		empty
		"""
		p = Prize()
		self.assertJoin(Prize(self.gen_awards(1,3),self.gen_indicators(1,3)),p)

	def test_04(self):
		""" test join: one prize with awards but no indicators the other is just
		empty
		"""
		self.assertJoin(Prize(),Prize(self.gen_awards(1,3)))

	def test_05(self):
		""" test join: one prize with awards and indicators the other is just
		empty
		"""
		p = Prize(self.gen_awards(1,3),self.gen_indicators(1,3))
		self.assertJoin(Prize(),p)

	def test_06 (self):
		""" test join: same awards, indicators have different students """
		i1 = {"s0": {"a0": (False, None)}}
		i2 = {"s1": {"a0": (1, ["c0"])}}
		p1 = Prize(self.gen_awards(1,1),i1)
		p2 = Prize(self.gen_awards(1,1),i2)
		self.assertJoin(p1,p2)

	def test_07 (self):
		""" test join: same awards, indicators have different achievements, for
		the same students
		"""
		i1 = {"s0": {"a0": (2, ["c32","c7"])}}
		i2 = {"s0": {"a1": (1, ["c0"])}}
		p1 = Prize(self.gen_awards(3,1),i1)
		p2 = Prize(self.gen_awards(2,1),i2)
		self.assertJoin(p1,p2)

	def test_08 (self):
		""" test join: same awards, indicators have same achievements with same
		indicators, for	the same students
		"""
		i1 = {"s0": {"a0": (1, ["c0"])}}
		i2 = {"s0": {"a0": (1, ["c0"])}}
		p1 = Prize(self.gen_awards(2,1),i1)
		p2 = Prize(self.gen_awards(1,1),i2)
		self.assertJoin(p1,p2)

	def test_09 (self):
		""" test join: same awards, indicators have same achievements with diff
		indicators, for	the same students
		"""
		i1 = {"s0": {"a0": (False, None)}}
		i2 = {"s0": {"a0": (1, ["c0"])}}
		p1 = Prize(self.gen_awards(3,1),i1)
		p2 = Prize(self.gen_awards(1,1),i2)
		self.assertJoin(p1,p2)

	def test_10 (self):
		""" test join: same awards, indicators have same achievements with diff
		indicators, for	the same students
		"""
		i1 = {"s0": {"a0": (2, ["c1","c2"])}}
		i2 = {"s0": {"a0": (2, ["c3","c4"])}}
		p1 = Prize(self.gen_awards(1,1),i1)
		p2 = Prize(self.gen_awards(1,1),i2)
		self.assertJoin(p1,p2)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Prize.join_multiple(Prize p) --> joins all the awards and indicators from all
# the given prizes to this one
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class TestJoinMultiple(TestPrize):

	def test_01 (self):
		""" test join_multiple: join 2 prizes with an empty prize """
		self.assertJoinMul(Prize(), self.gen_prizes(2))

	def test_02 (self):
		""" test join_multiple: join 4 prizes together with a non-empty prize """
		p = Prize(self.gen_awards(1,2),self.gen_indicators(1,1))
		self.assertJoinMul(p, self.gen_prizes(4))
