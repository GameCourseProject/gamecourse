#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import course, decorators
from decorators import rule_effect, rule_function
from gamerules.functions import gamefunctions as gcfuncs

@rule_effect
def award_tokens(target, reward_name, tokens = None, contributions=None):
    return gcfuncs.award_tokens(target, reward_name, tokens, contributions)
