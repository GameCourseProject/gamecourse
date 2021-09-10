#!/usr/bin/env python
# -*- coding: utf-8 -*-

import random
from context import rule_function

class Human:
	def __init__(self,name,gender,intelligence,strength,speed):
		self.name = name
		self.gender = gender
		self.intelligence = intelligence
		self.strength = strength
		self.speed = speed
	
	def __repr__(self):
		msg = type(self).__name__ + "<name: " + self.name + ", gender" + gender
		msg+= ", strength: {}, intelligence: {}, speed: {}"
		msg.format(self.strength, self.intelligence, self.speed)
		return msg

	def __eq__(self,other):
		return isinstance(other,Human) \
		and self.power() == other.power()

	def __ne__(self,other):
		return not self == other

	def __ge__(self,other):
		return isinstance(other,Human) \
		and self.power() >= other.power()

	def __gt__(self,other):
		return isinstance(other,Human) \
		and self.power() > other.power()

	def __le__(self,other):
		return isinstance(other,Human) \
		and self.power() <= other.power()

	def __lt__(self,other):
		return isinstance(other,Human) \
		and self.power() < other.power()

	def power(self):
		return self.strength + self.intelligence + self.speed

	def fight(self,other):
		return self if self > other else other if self < other else None


@rule_function
class Barbarian(Human):
	def __init__(self,name,gender):
		st = random.randint(5,10)
		it = random.randint(1,4)
		sp = random.randint(4,6)
		super(Barbarian, self).__init__(self,name,gender,it,st,sp)

@rule_function
class Thief(Human):
	def __init__(self,name,gender):
		st = random.randint(1,6)
		it = random.randint(2,8)
		sp = random.randint(5,10)
		super(Thief, self).__init__(self,name,gender,it,st,sp)

@rule_function
class Wizard(Human):
	def __init__(self,name,gender):
		st = random.randint(3,4)
		it = random.randint(8,10)
		sp = random.randint(1,4)
		super(Wizard, self).__init__(self,name,gender,it,st,sp)