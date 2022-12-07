#!/usr/bin/env python
# -*- coding: utf-8 -*-

from context import decorators
from decorators import rule_function, rule_effect


@rule_effect
def award(target,achievement,lvl):
	from auxiliar import Award
	return Award(target,achievement,lvl)