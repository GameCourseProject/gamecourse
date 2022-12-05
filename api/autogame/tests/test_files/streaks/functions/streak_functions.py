#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import course, decorators
from decorators import rule_effect, rule_function
from gamerules.functions import gamefunctions as gcfuncs

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Functions used for actions (THEN)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_effect
def award_streak(target, streak, to_award, participations, type=None):
	return gcfuncs.award_streak(target, streak, to_award, participations, type)

@rule_effect
def award_rating_streak(target, streak, rating, contributions=None, info=None):
    return gcfuncs.award_rating_streak(target, streak, rating, contributions, info)

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Functions used for preconditions (WHEN)
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
@rule_function
def get_username(target):
    return gcfuncs.get_username(target)

@rule_function
def get_logs(target, type):
    return gcfuncs.get_logs(target, type)

@rule_function
def get_graded_skill_logs(target, minRating):
    return gcfuncs.get_graded_skill_logs(target, minRating)

@rule_function
def get_graded_logs(target, minRating, include_skills):
    return gcfuncs.get_graded_logs(target, minRating, include_skills)

@rule_function
def consecutive_peergrading(target):
    return gcfuncs.consecutive_peergrading(target)

@rule_function
def awards_to_give(target, streak_name):
    return gcfuncs.awards_to_give(target, streak_name)

@rule_effect
def get_consecutive_peergrading_logs(target, streak, contributions):
    return gcfuncs.get_consecutive_peergrading_logs(target, streak, contributions)

@rule_effect
def get_consecutive_rating_logs(target, streak, type, rating, only_skill_posts):
    return gcfuncs.get_consecutive_rating_logs(target, streak, type, rating, only_skill_posts)

@rule_effect
def get_consecutive_logs(target, streak, type):
    return gcfuncs.get_consecutive_logs(target, streak, type)

@rule_effect
def get_periodic_logs(target, streak_name, contributions):
    return gcfuncs.get_periodic_logs(target, streak_name, contributions)
