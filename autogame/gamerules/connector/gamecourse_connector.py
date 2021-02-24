#!/usr/bin/env python3

import socket
import signal, os, sys
import json
import mysql.connector
import time
from datetime import datetime

from io import StringIO
from course.student import *
from course.logline import *
import config

from course.award import Award
from course.prize import Prize


from course.coursedata import read_achievements, read_tree
achievements = read_achievements()
tree_awards = read_tree()

#   TODO:
#   - check if dictionary only contains terms that are active for a given course id
#

DATABASE = "gamecourse"


def get_credentials():
	# -----------------------------------------------------------
	# Read db credentials from file
	# -----------------------------------------------------------
	with open(os.path.join(os.path.dirname(os.path.realpath(__file__)),'credentials.txt'), 'r') as f:
		data = f.readlines()
		un = data[0].strip('\n')
		if len(data) == 2:
			pw = data[1].strip('\n')
			return (un, pw)
		return (un, '')


def get_dictionary():
	# -----------------------------------------------------------
	# Pulls all GameCourse dictionary terms from the database.
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)
	# keyword must be queried first so it serves as key for a dictionary
	query = "SELECT keyword, moduleId FROM dictionary WHERE course = %s;"
	course = (config.course,)

	cursor.execute(query, course)
	table = cursor.fetchall()

	cnx.close()

	# results to a query are a list of tuples
	return table


def get_student_numbers(course):
	# -----------------------------------------------------------
	# Returns the student numbers of all active targets
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)
	query = "SELECT id, studentNumber FROM game_course_user;"

	cursor.execute(query)
	table = cursor.fetchall()
	cnx.close()

	student_numbers = {}

	for i in range(len(table)):
		student_numbers[str(table[i][0])] = str(table[i][1])

	return student_numbers




def course_exists(course):
	# -----------------------------------------------------------
	# Checks course exists and is active
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)
	query = "SELECT isActive FROM course WHERE id = %s;"
	args = (course,)

	cursor.execute(query, args)
	table = cursor.fetchall()
	cnx.close()

	if len(table) == 1:
		return table[0][0]
	else:
		return False


def check_dictionary(library, function):
	# -----------------------------------------------------------
	# Checks if a function exists in the gamecourse dictionary
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)
	query = "SELECT keyword, moduleId FROM dictionary WHERE course = %s and library=%s and keyword = %s;"
	args = (config.course, library, function)

	cursor.execute(query, args)
	table = cursor.fetchall()
	cnx.close()

	# returns empty list if term not in dictionary
	# TO DO : also will need to check number of arguments once the schema includes that information

	return len(table) == 1



def get_targets(course, timestamp=None, limit=None):
	"""	
	Returns targets for running rules (students)
	"""

	(username, password) = get_credentials()

	# query that joins roles with participations
	#select user, role.name from participation left join user_role on participation.user = user_role.id left join role on user_role.role = role.id where participation.course = 1 and role.name="Student";


	if timestamp == None:
		cnx = mysql.connector.connect(user=username, password=password,
		host='localhost', database=DATABASE)
		cursor = cnx.cursor(prepared=True)

		if limit != None:
			query = "SELECT DISTINCT user FROM participation LEFT JOIN user_role ON participation.user = user_role.id LEFT JOIN role ON user_role.role = role.id WHERE participation.course =%s AND role.name='Student' LIMIT %s;"
			cursor.execute(query, (course, limit))
		else:
			query = "SELECT user FROM participation LEFT JOIN user_role ON participation.user = user_role.id LEFT JOIN role ON user_role.role = role.id WHERE participation.course =%s AND role.name='Student';"
			cursor.execute(query, (course,))

		table = cursor.fetchall()
		cnx.close()

	elif timestamp != None:
		cnx = mysql.connector.connect(user=username, password=password,
		host='localhost', database=DATABASE)
		cursor = cnx.cursor(prepared=True)

		if limit != None:
			query = "SELECT user FROM participation LEFT JOIN user_role ON participation.user = user_role.id LEFT JOIN role ON user_role.role = role.id WHERE participation.course =%s AND role.name='Student' AND date > %s LIMIT %s;"
			cursor.execute(query, (course, timestamp, limit))
		else:	
			query = "SELECT user FROM participation LEFT JOIN user_role ON participation.user = user_role.id LEFT JOIN role ON user_role.role = role.id WHERE participation.course =%s AND role.name='Student' AND date > %s;"
			cursor.execute(query, (course, timestamp))

		table = cursor.fetchall()
		cnx.close()

	else:
		sys.exit("ERROR get_targets(): Could not pull participation logs from database!")

	targets = {}

	for line in table:
		(user,) = line
		targets[user] = 1

	return targets


def delete_awards(course):
	# -----------------------------------------------------------	
	# Deletes all awards of a given course
	# -----------------------------------------------------------
	
	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)
	query = "DELETE FROM award where course = %s;"

	cursor.execute(query, (course,))
	cnx.commit()
	cnx.close()

	return


def count_awards(course):
	# -----------------------------------------------------------	
	# Deletes all awards of a given course
	# -----------------------------------------------------------
	
	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)
	query = "SELECT count(*) FROM award where course = %s;"

	cursor.execute(query, (course,))
	table = cursor.fetchall()

	cnx.commit()
	cnx.close()

	return table[0][0]


def calculate_xp(course, target):
	# -----------------------------------------------------------   
	# Insert current XP values into user_xp table
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)

	# get max values for each type of award
	query = "SELECT maxReward from skill_tree where course = %s;"
	cursor.execute(query, (course,))
	tree_table = cursor.fetchall()

	query = "SELECT maxBonusReward from badges_config where course = %s;"
	cursor.execute(query, (course,))
	badge_table = cursor.fetchall()

	if len(tree_table) == 1:
		max_tree_reward = int(tree_table[0][0])

	if len(badge_table) == 1:
		max_badge_bonus_reward = int(badge_table[0][0])


	# get atributed xp so far

	query = "SELECT sum(reward) from award where course=%s and type=%s and user=%s group by user;"
	cursor.execute(query, (course, "skill", target))
	tree_xp = cursor.fetchall()

	# if query returns empty set    
	if len(tree_xp) > 0:
		if tree_xp[0][0] != None:
			user_tree_xp = int(tree_xp[0][0])
		else:
			user_tree_xp = 0

	else:
		user_tree_xp = 0


	query = "SELECT sum(reward) from award where course=%s and (type !=%s and type !=%s) and user=%s group by user;"
	cursor.execute(query, (course, "badge", "skill", target))
	other_xp = cursor.fetchall()

	# if query returns empty set
	if len(other_xp) > 0:
		if other_xp[0][0] != None:
			user_other_xp = int(other_xp[0][0])
		else:
			user_other_xp = 0
	else:
		user_other_xp = 0


	# rewards from badges where isExtra = 1
	query = "SELECT sum(reward) from award left join badge on award.moduleInstance=badge.id where award.course=%s and type=%s and isExtra =%s and user=%s";
	cursor.execute(query, (course, "badge", True, target))
	badge_xp_extra = cursor.fetchall()

	# if query returns empty set
	if len(badge_xp_extra) > 0:
		sys.stderr.write("\n\n")
		if badge_xp_extra[0][0] != None:
			user_badge_xp_extra = int(badge_xp_extra[0][0])
		else:
			user_badge_xp_extra = 0

	else:
		user_badge_xp_extra = 0


	# rewards from badges where isExtra = 0
	query = "SELECT sum(reward) from award left join badge on award.moduleInstance=badge.id where award.course=%s and type=%s and isExtra =%s and user=%s";
	cursor.execute(query, (course, "badge", False, target))
	badge_xp = cursor.fetchall()

	# if query returns empty set
	if len(badge_xp) > 0:
		if badge_xp[0][0] != None:
			user_badge_xp = int(badge_xp[0][0])
		else:
			user_badge_xp = 0

	else:
		user_badge_xp = 0

	total_skill_xp = min(user_tree_xp, max_tree_reward)
	total_other_xp = user_other_xp
	total_badge_extra_xp = min(user_badge_xp_extra, max_badge_bonus_reward)
	total_badge_xp = user_badge_xp + total_badge_extra_xp

	total_xp = total_badge_xp + total_skill_xp + total_other_xp

	query = "UPDATE user_xp set xp= %s where course=%s and user=%s;"
	cursor.execute(query, (total_xp, course, target))
	cnx.commit()
	cnx.close()


def autogame_init(course):
	# -----------------------------------------------------------	
	# Pulls gamerules related info for a given course
	# -----------------------------------------------------------
	
	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)

	cursor = cnx.cursor(prepared=True)
	query = "SELECT * FROM autogame where course = %s;"

	cursor.execute(query, (course,))
	table = cursor.fetchall()
	
	last_activity = None

	if len(table) == 0:
		# if course does not exist, add to table
		query = "INSERT INTO autogame (course,date,isRunning) VALUES(%s, %s, %s);"

		timestamp = datetime.now()
		time = timestamp.strftime("%Y/%m/%d %H:%M:%S")
		# create line as running
		cursor.execute(query, (course, time, True))
	else:
		if table[0][3] == True:
			cnx.close()
			is_running = True
			return last_activity, is_running
		
		last_activity = table[0][2]
		query = "UPDATE autogame SET date=%s, isRunning=%s WHERE course=%s;"

		# set as running
		timestamp = datetime.now()
		time = timestamp.strftime("%Y/%m/%d %H:%M:%S")

		cursor.execute(query, (time, True, course))

	cnx.commit()
	cnx.close()
	return last_activity, False


def autogame_terminate(course):
	# -----------------------------------------------------------	
	# Finishes execution of gamerules, sets isRunning to False
	# and notifies server to close the socket
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)
	
	cursor = cnx.cursor(prepared=True)
	query = "UPDATE autogame SET isRunning=%s WHERE course=%s;"
	cursor.execute(query, (False, course))

	cnx.commit()

	query = "SELECT * from autogame WHERE isRunning=%s AND course != %s;"
	cursor.execute(query, (True, 0))
	table = cursor.fetchall()

	cnx.close()
	

	if len(table) == 0:
		HOST = '127.0.0.1' # The server's hostname or IP address
		PORT = 8002 # The port used by the server

		with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
			s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

			try:
				s.connect((HOST, PORT))
				s.settimeout(600.0)
				end_message = "end gamerules;"
				s.send(end_message.encode())
				s.send("\n".encode())

			except socket.timeout as e:
				print("\nError: Socket timeout, operation took longer than 3 minutes.")
			
			except KeyboardInterrupt:
				print("\nInterrupt: You pressed CTRL+C!")
				exit()

	return


def award_badge(target, badge, lvl, contributions=None, info=None):
	# -----------------------------------------------------------	
	# Writes and updates 'award' table with badge levels won by 
	# the user. Will retract if rules/participations have been
	# changed.
	# Is also responsible for creating indicators.
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	course = config.course
	typeof = "badge"

	achievement = {}
	"""
	achievement = achievements[badge]
	reward = achievement.xp[lvl-1]"""

	

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)


	cursor = cnx.cursor(prepared=True)
	query = "SELECT * FROM award where user = %s AND course = %s AND description like %s AND type=%s;"

	badge_name = badge + "%"
	cursor.execute(query, (target, course, badge_name, typeof))
	table = cursor.fetchall()




	# get badge info
	query = "SELECT number, badgeId, reward from badge_level left join badge on badge.id = badge_level.badgeId where badge.course = %s and badge.name = %s order by number;"
	cursor.execute(query, (course, badge))
	table_badge = cursor.fetchall()
	
	
	
	# Case 0: lvl is zero and there are no lines to be erased
	# Simply return right away
	if lvl == 0 and len(table) == 0:
		return

	# Case 1: no level of this badge has been attributed yet
	# Simply write the badge level(s) to the 'award' table
	elif len(table) == 0:
		# if table is empty, just write
		for level in range(1, lvl+1):
			# if the user doesnt have the respective badge and level, adds it
			lvl_info = " (level " + str(level) + ")"
			description = badge + lvl_info

			badge_id = table_badge[level-1][1]
			reward = table_badge[level-1][2]


			query = "INSERT INTO award (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s,%s);"
			cursor.execute(query, (target, course, description, typeof, badge_id, reward))
			cnx.commit()
			cursor = cnx.cursor(prepared=True)

			# insert in award_participation
			if level == 1:
				query = "SELECT id from award where user = %s AND course = %s AND description=%s AND type=%s;"
				cursor.execute(query, (target, course, description, "badge"))
				table_id = cursor.fetchall()
				award_id = table_id[0][0]
								
				for el in contributions:
					participation_id = el.log_id
					query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
					cursor.execute(query, (award_id, participation_id))

			
			if contributions != None and len(contributions) != 0:
				nr_contributions = str(len(contributions))
			else:
				nr_contributions = ''

			config.award_list.append([str(target), str(badge), str(level), nr_contributions])
	
	# Case 2: the rule/data sources have been updated, the 'award' table
	# has badge levels attributed which are no longer valid. All levels
	# no longer valid must be deleted
	elif len(table) > lvl:
		for diff in range(lvl, len(table)):

			lvl_info = " (level " + str(diff + 1) + ")"
			description = badge + lvl_info

			badge_id = table_badge[diff][1]

			query = "DELETE FROM award WHERE user = %s AND course = %s AND description = %s AND moduleInstance = %s AND type=%s;"
			cursor.execute(query, (target, course, description, badge_id, typeof))
			cnx.commit()
	
	# Case 3: there have been new participations, the new levels won
	# by the user must be inserted into table 'award'
	elif len(table) < lvl:
		for diff in range(len(table), lvl):

			lvl_info = " (level " + str(diff + 1) + ")"
			description = badge + lvl_info

			badge_id = table_badge[diff][1]
			reward = table_badge[diff][2]

			query = "INSERT INTO award (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s, %s);"
			cursor.execute(query, (target, course, description, typeof, badge_id, reward))
			cnx.commit()

			level = diff + 1
			# insert in award_participation
			if level == 1:
				query = "SELECT id from award where user = %s AND course = %s AND description=%s AND type=%s;"
				cursor.execute(query, (target, course, description, "badge"))
				table_id = cursor.fetchall()
				award_id = table_id[0][0]
								
				for el in contributions:
					participation_id = el.log_id
					query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
					cursor.execute(query, (award_id, participation_id))

			"""
			if contributions != None and len(contributions) != 0:
				nr_contributions = str(len(contributions))
			else:
				nr_contributions = ''

			config.award_list.append([str(target), str(badge), str(level), nr_contributions]) 
			"""
			
	cnx.close()
	
	"""
	if lvl > 0 and contributions != None:
		info = str(False) if info is None else str(info)
		awards = []
		for level in range(lvl):
			xp = achievement.xp[lvl-1]
			awards.append(Award(target,badge,level+1,int(xp),True,info=info))
		awards = {target: awards}
		indicators = {target: {badge: (info, contributions)}}
		return Prize(awards,indicators)
	"""

	"""
	if lvl > 0 and contributions != None:
		info = str(False) if info is None else str(info)
		awards = []

		nr = len(contributions)
		result = [str(nr)]
		info = []

		for el in contributions:
			indicator = {"info": str(el.description), "url": el.post , "timestamp": el.date, "num": el.user, "action": el.log_type, "xp": 0}
			info.append(indicator)

		result.append(info)

		return result"""



def award_skill(target, skill, rating, contributions=None):
	# -----------------------------------------------------------	
	# Writes and updates 'award' table with skills completed by 
	# the user. Will retract if rules/participations have been
	# changed.
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	course = config.course
	typeof = "skill"

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)
	
	cursor = cnx.cursor(prepared=True)

	query = "SELECT * FROM award where user = %s AND course = %s AND description=%s AND type=%s;"
	cursor.execute(query, (target, course, skill, typeof))
	table = cursor.fetchall()




	query = "SELECT s.id, reward FROM skill s natural join skill_tier join skill_tree t on t.id=s.treeId where s.name = %s and course = %s;"
	cursor.execute(query, (skill, course))
	table_skill = cursor.fetchall()


	# If rating is not enough to win the award, return
	if rating < 3 and len(table) == 0:
		return

	# If this skill has not been awarded to this user
	# and rating is greater or equal to 3, award skill
	elif len(table) == 0:
		skill_id, skill_reward = table_skill[0][0], table_skill[0][1]

		query = "INSERT INTO award (user, course, description, type, moduleInstance, reward) VALUES(%s, %s , %s, %s, %s, %s);"
		cursor.execute(query, (target, course, skill, typeof, skill_id, skill_reward))

		config.award_list.append([str(target), "Skill Tree", str(skill_reward), skill])

		query = "SELECT id from award where user = %s AND course = %s AND description=%s AND type=%s;"
		cursor.execute(query, (target, course, skill, typeof))
		table_id = cursor.fetchall()
		award_id = table_id[0][0]
		# contributions is always len == 1, ensured by getSkillParticipations
		participation_id = contributions[0].log_id

		query = "INSERT INTO award_participation (award, participation) VALUES(%s, %s);"
		cursor.execute(query, (award_id, participation_id))
	
	# If skill has already been awarded to used
	# compare ratings given before and now
	elif len(table) == 1:
		# If new rating is lesser than 3
		# delete the awarded skill
		if rating < 3:
			query = "DELETE FROM award WHERE user = %s AND course = %s AND description = %s AND type=%s;"
			cursor.execute(query, (target, course, skill, typeof))
		
		# If new rating is greater or equal to 3
		# no changes to table award, so continue!
		# There might be a change to the award_participation:
		if contributions[0].rating > table[0][5]:
			award_id = table[0][0]
			participation_id = contributions[0].log_id

			query = "UPDATE award_participation set participation=%s where award=%s;"
			cursor.execute(query, (participation_id, award_id))
		
	else:
		print("ERROR: More than one line for a skill found on the database.")

	cnx.commit()
	cnx.close()


	"""
	# TODO INDICATORS ? check rating details
	if rating >= 3 and contributions != None:

		award = [Award(target,"Skill Tree",0,int(ta.xp),False,info=skill)]
		awards = {target: award}
		indicators = {}

		for c in contributions: # should return only one
			indicators[target] = {skill: (ta.xp, [c])}
		return Prize(awards, indicators)"""


def award_prize(target, reward_name, xp, contributions=None):
	# -----------------------------------------------------------	
	# Writes 'award' table with reward that is not a badge or a
	# skill. Will not retract effects, but will not award twice
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	course = config.course
	typeof = "bonus"
	reward = int(xp)

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)
	
	cursor = cnx.cursor(prepared=True)
	query = "SELECT moduleInstance FROM award where user = %s AND course = %s AND description = %s AND type=%s;"

	cursor.execute(query, (target, course, reward_name, typeof))
	table = cursor.fetchall()
	
	if len(table) == 0:
		# simply award the prize
		query = "INSERT INTO award (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
		cursor.execute(query, (target, course, reward_name, typeof, reward))
		cnx.commit()

		config.award_list.append([str(target), reward_name, str(reward), ""])


	cnx.close()

	return




def award_grade(target, item, contributions=None):
	# -----------------------------------------------------------	
	# Writes 'award' table with reward that is not a badge or a
	# skill. Will not retract effects, but will not award twice
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	course = config.course
	
	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)
	cursor = cnx.cursor(prepared=True)

	if item == "Lab":
		description = "Lab Grade"
		typeof = "labs"
	elif item == "Quiz":
		description = "Quiz Grade"
		typeof = "quiz"
	elif item == "Presentation":
		description = "Presentation Grade"
		typeof = "presentation"

	query = "SELECT moduleInstance, reward FROM award where user = %s AND course = %s AND type=%s AND description = %s;"
	cursor.execute(query, (target, course, typeof, description))
	table = cursor.fetchall()

	
	if item == "Presentation":
		for line in contributions:
			grade = int(line.rating)
			if len(table) == 0:
				query = "INSERT INTO award (user, course, description, type, reward) VALUES(%s, %s , %s, %s, %s);"
				cursor.execute(query, (target, course, description, typeof, grade))
				cnx.commit()
				config.award_list.append([str(target), "Grade from " + item, str(grade), ""])

			elif len(table) == 1:
				query = "UPDATE award SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s"
				cursor.execute(query, (grade, course, target, typeof, description))
				cnx.commit()

	elif item == "Quiz" or item == "Lab":
		for line in contributions:
			if line.description == "Dry Run" or line.description == "Quiz 9":
				continue

			grade = int(line.rating)
			nums = [int(s) for s in line.description.split() if s.isdigit()]
			number = nums[0]

			found = False
			for row in table:
				if row[0] == number:
					found = True
					line = row
					break

			if found:
				# if the line already existed and the new rating is different, update the line
				old_grade = int(line[1])
				if old_grade != grade:
					query = "UPDATE award SET reward=%s WHERE course=%s AND user = %s AND type=%s AND description=%s AND moduleInstance = %s;"
					cursor.execute(query, (grade, course, target, typeof, description, number))
					cnx.commit()

			else:
				# if the line did not exist, add it
				query = "INSERT INTO award (user, course, description, type, reward, moduleInstance) VALUES(%s, %s , %s, %s, %s, %s);"
				cursor.execute(query, (target, course, description, typeof, grade, number))
				cnx.commit()
				config.award_list.append([str(target), "Grade from " + item, str(grade), str(number)])
		
	cnx.close()
	return




def get_campus(target):
	# -----------------------------------------------------------	
	# Returns the campus of a target user
	# -----------------------------------------------------------

	(username, password) = get_credentials()

	course = config.course

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE)
	
	cursor = cnx.cursor(prepared=True)
	query = "select major from course_user left join game_course_user on course_user.id=game_course_user.id where course = %s and course_user.id = %s;"

	cursor.execute(query, (course, target))
	table = cursor.fetchall()
	cnx.close()
	
	if len(table) == 1:
		major = table[0][0]
		if major in config.majors_alameda:
			campus = 'A'
		elif major in config.majors_tagus:
			campus = 'T'
		else:
			campus = 'A'

	elif len(table) == 0:
		print("ERROR: No student with given id found in course_user database.")
		campus = None
	else:
		print("ERROR: More than one student with the same id in course_user database.")
		campus = None

	return campus


def call_gamecourse(course, library, function, args):
	# converts args to json
	args_json = json.dumps(args)


	HOST = '127.0.0.1' # The server's hostname or IP address
	PORT = 8002 # The port used by the server

	with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
		s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

		try:
			while True:
				s.connect((HOST, PORT))
				s.settimeout(50.0)

				# send course number
				s.send(course.encode())
				s.send("\n".encode())
				# send name of parent module
				s.send(library.encode())
				s.send("\n".encode())
				# send name of function to execute

				#sys.stderr.write("\n>>> " + function +"\n")

				s.send(function.encode())
				s.send("\n".encode())
				# send args of function

				s.send(args_json.encode())
				s.send("\n".encode())

				data = s.recv(1024)
				datatype = data.decode()
				# send an ok message for syncing up the socket
				s.send("ok\n".encode())

				# if data received is a collection, it will be sent in chunks
				# as not to break the socket
				if datatype == "collection":
					data = s.recv(1024)
					datad = data.decode()
					collection = datad
					while data:
						data = s.recv(1024)
						datad = data.decode()
						collection += datad
					
					if not data:
						all_logs = collection.split("\n")[:-1]
						
						# create loglines from each el in list
						participations = []
						for el in all_logs:
							io = StringIO(el)
							log = json.load(io)

							# TODO - might want to check for errors in this part

							ll_id = int(log["id"])
							ll_user = int(log["user"])
							ll_course = int(log["course"])
							ll_desc = log["description"]
							ll_type = log["type"]
							ll_moduleInstance = log["moduleInstance"]
							ll_post = log["post"]
							ll_date = log["date"]
							ll_rating = None if log["rating"] is None else int(log["rating"])
							ll_evaluator = None if log["evaluator"] is None else int(log["evaluator"])
							
							logline = LogLine(ll_id,ll_user,ll_course,ll_desc,ll_type,ll_moduleInstance,ll_post,ll_date,ll_rating,ll_evaluator)
							participations.append(logline)
							
						result = participations
						break

				# if data received is a value (int, float, str, etc)
				# then it will be json decoded into the correct type
				elif datatype == "other":
					data = s.recv(1024)
					io = StringIO(data.decode())
					result = json.load(io)
					break

				else:
					print("\nError: Return type not valid.")
					break

		except socket.timeout as e:
			print("\nError: Socket timeout, operation took longer than 3 minutes.")
		
		except KeyboardInterrupt:
			print("\nInterrupt: You pressed CTRL+C!")
			exit()

	return result
