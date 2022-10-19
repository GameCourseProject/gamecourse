#!/usr/bin/env python
# -*- coding: utf-8 -*-

rule_lotr_head = "rule: The One Rule 	"
rule_lotr_desc = """

	Three Rings for the Elven-kings under the sky,
	Seven for the Dwarf-lords in their halls of stone,
	Nine for Mortal Men doomed to die,
	One for the Dark Lord on his dark throne
	In the Land of Mordor where the Shadows lie.
	One Ring to rule them all, One Ring to find them,
	One Ring to bring them all, and in the darkness bind them,
	In the Land of Mordor where the Shadows lie.

"""
rule_lotr_cond = """
	when:
		sauron = "Master of the One Ring"
		the_one_ring = "One Ring"
		the_one_ring in sauron
"""
rule_lotr_actions = """
	then:
		middle_earth_is_doomed = True
		sauron and the_one_ring
		middle_earth_is_doomed is True
"""
rule_lotr = rule_lotr_head + rule_lotr_desc
rule_lotr+= rule_lotr_cond + rule_lotr_actions
