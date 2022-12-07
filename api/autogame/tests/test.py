#!/usr/bin/env python
# -*- coding: utf-8 -*-

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Rule System Class Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from rule_system_tests import test_rule_system as rule_system
rs = rule_system
rule_sys = rule_system
rulesystem = rule_system
rulesys = rule_system
from rule_system_tests import test_rule_system_aux as rule_system_aux
rs_aux = rule_system_aux
rsaux = rule_system_aux
rule_sys_aux = rule_system_aux
rulesystemaux = rule_system_aux
rulesysaux = rule_system_aux
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Rules Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from rule_tests import test_basenode as basenode
from rule_tests import test_rule as rule
from rule_tests import test_block as block
from rule_tests import test_preconditions as preconditions
from rule_tests import test_actions as actions
precs = preconditions
acts = actions
from rule_tests import test_stmt as stmt
from rule_tests import test_expression as expression
from rule_tests import test_assignment as assignment
from rule_tests import test_rule_aux as rule_aux
from rule_tests import test_validate as validate
from rule_tests import test_effect as effect
from rule_tests import test_output as output
from rule_tests import test_rulelog as rulelog
rule_log = rulelog
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Parse Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from parse_tests import test_parser as parser
from parse_tests import test_parse_folder as parse_folder
parse_dir = parse_folder
parse_directory = parse_folder
from parse_tests import test_parse_file as parse_file
from parse_tests import test_parse_list_rules as parse_list_rules
parse_lrules = parse_list_rules
from parse_tests import test_parse_rule as parse_rule
from parse_tests import test_parse_preconditions as parse_preconditions
parse_precs = parse_preconditions
from parse_tests import test_parse_actions as parse_actions
from parse_tests import test_parse_nblock as parse_nblock
parse_named_block = parse_nblock
from parse_tests import test_parse_block as parse_block
from parse_tests import test_parse_stmt as parse_stmt
from parse_tests import test_parse_name as parse_name
from parse_tests import test_parse_description as parse_description 
parse_desc = parse_description
from parse_tests import test_parse_comment as parse_comment
from parse_tests import test_parser_aux as parser_aux
parse_aux = parser_aux
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Data Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from data_tests import test_data_manager as data_manager
from data_tests import test_path_data as path_data
from data_tests import test_targetdata as target_data
targetdata = target_data
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Functions Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from functions_tests import test_import_functions as import_functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Exceptions Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from exceptions_tests import test_parse_error as parse_error
from exceptions_tests import test_path_error as path_error
from exceptions_tests import test_stmt_error as stmt_error
from exceptions_tests import test_rule_error as rule_error
from exceptions_tests import test_error_functions as error_functions
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Course Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
from course_tests import test_student as student
from course_tests import test_studentdata as studentdata
from course_tests import test_achievement as achievement
from course_tests import test_award as award
from course_tests import test_prize as prize
from course_tests import test_skilltree as skilltree
from course_tests import test_coursefunctions as coursefunctions
cfuncs = course_functions = cfunctions = coursefunctions
from course_tests import test_coursedata as coursedata
cdata = course_data = coursedata
from course_tests import test_logline as logline
logs = logline
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# PCM Tests
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# from pcm_tests import pcm_match_test as pcm