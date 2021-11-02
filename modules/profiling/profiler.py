#!/usr/bin/env python
import os, sys, json, csv
import mysql.connector
import numpy as np
import pandas as pd
import warnings
import traceback
from datetime import date, timedelta, datetime

from boruta import BorutaPy
from imblearn.over_sampling import SMOTE
from sklearn import preprocessing
from sklearn.cluster import KMeans
from sklearn.feature_selection import VarianceThreshold
from sklearn.model_selection import RandomizedSearchCV, GridSearchCV, train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.ensemble import RandomForestRegressor



DATABASE = "gamecourse"
RESULTS_PATH = "/var/www/html/gamecourse/modules/profiling/"

def get_credentials():
	with open(os.path.join(os.path.dirname(os.path.realpath(__file__)),'credentials.txt'), 'r') as f:
		data = f.readlines()
		un = data[0].strip('\n')
		if len(data) == 2:
			pw = data[1].strip('\n')
			return (un, pw)
		return (un, '')

def course_exists(course):
	"""
    Checks if course exists and is active
	"""
	(username, password) = get_credentials()
	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE, charset="utf8")

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

def get_students(course):
	"""
    Get IDs and names of the students enrolled in the course.
	"""
	(username, password) = get_credentials()
	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE, charset="utf8")

	cursor = cnx.cursor(prepared=True)
	query = "SELECT g.id, g.name FROM user_role ur left join course_user u on u.id = ur.id and ur.course=u.course left join game_course_user g on u.id = g.id join role r on ur.role = r.id WHERE ur.course = %s and r.name = 'Student' and u.isActive = %s;"
	args = (course, True)

	cursor.execute(query, args)
	table = cursor.fetchall()
	cnx.close()
	return table

def get_badges(course):
	"""
    Get badges in the course.
	"""
	(username, password) = get_credentials()
	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE, charset="utf8")
	cursor = cnx.cursor(prepared=True)
	query = "SELECT id, name FROM badge WHERE course = %s and isActive = %s;"
	cursor.execute(query, (course, True))
	badges = cursor.fetchall()
	cnx.close()

	badge_dict = {}
	for badge in badges:
		badge_dict[badge[0]] = badge[1].decode()

	return badge_dict

def get_awards(course):
	"""
    Get awards given in the course.
	"""
	(username, password) = get_credentials()
	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE, charset="utf8")
	
	cursor = cnx.cursor(prepared=True)
	query = "SELECT * FROM award WHERE course = %s ORDER BY date;"
	args = (course,)

	cursor.execute(query, args)
	table = cursor.fetchall()
	cnx.close()
	return table

def get_participations(course):
	"""
    Get awards given in the course.
	"""
	(username, password) = get_credentials()
	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE, charset="utf8")

	cursor = cnx.cursor(prepared=True)
	query = "SELECT user, type, date FROM participation WHERE course = %s and type != 'lab grade' and type not like 'attended %' and type != 'initial bonus' and type != 'quiz grade' and type != 'graded post' and type != 'suggested presentation subject' and type != 'participated in lecture' order by date;"
	args = (course,)

	cursor.execute(query, args)
	table = cursor.fetchall()
	cnx.close()
	return table

def calculate_xp(course, awards, participations, students):
	"""
	Calculate the total xp and the xp accumulated per day by each student in the course.
	"""
	(username, password) = get_credentials()

	cnx = mysql.connector.connect(user=username, password=password,
	host='localhost', database=DATABASE, charset="utf8")
	
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

	query = "SELECT id, isExtra from badge where course = %s and isActive = %s;"
	cursor.execute(query, (course, True))
	badges = cursor.fetchall()
	cnx.close()

	student_dict = {}
	xp_per_day_dict = {}

	for student in students:
		student_id = student[0]
		student_dict[student_id] = {}
		student_dict[student_id]['total'] = 0
		student_dict[student_id]['skills'] = 0
		student_dict[student_id]['badges'] = 0

	badge_dict = {}
	for badge in badges:
		badge_dict[badge[0]] = badge[1]

	for award in awards:
		student = award[1]
		if student_dict.get(student) is not None:
			award_type = award[4].decode()
			mod_instance = award[5]
			reward = award[6]
			date = award[7].date()


			if xp_per_day_dict.get(date) is None:
				xp_per_day_dict[date] = {}

			if xp_per_day_dict[date].get(student) is None:
				xp_per_day_dict[date][student] = {}
				xp_per_day_dict[date][student]['xp'] = 0

			if ('skill' == award_type) and (student_dict[student]['skills'] < max_tree_reward):
				student_dict[student]['total'] += reward
				student_dict[student]['skills'] += reward
				xp_per_day_dict[date][student]['xp'] = student_dict[student]['total']
			
			elif 'badge' == award_type:

				if xp_per_day_dict[date].get('badges') is None:
					xp_per_day_dict[date]['badges'] = {}
				
				if badge_dict[mod_instance] == 1 and student_dict[student]['badges'] < max_badge_bonus_reward:
					maximum = max_badge_bonus_reward - student_dict[student]['badges']
					reward =  min(reward, maximum)
					student_dict[student]['total'] += reward
					student_dict[student]['badges'] += reward
					xp_per_day_dict[date][student]['xp'] = student_dict[student]['total']
				
				elif badge_dict[mod_instance] == 0:
					student_dict[student]['total'] += reward
					xp_per_day_dict[date][student]['xp'] = student_dict[student]['total']
				
				if xp_per_day_dict[date]['badges'].get(mod_instance) is None:
					xp_per_day_dict[date]['badges'][mod_instance] = []
				
				xp_per_day_dict[date]['badges'][mod_instance].append(student)
			
			else:
				student_dict[student]['total'] += reward
				xp_per_day_dict[date][student]['xp'] = student_dict[student]['total']

	for entry in participations:
		student = entry[0]
		if student_dict.get(student) is not None:
			action = entry[1].decode()
			date = entry[2].date()

			if xp_per_day_dict.get(date) is None:
				xp_per_day_dict[date] = {}
					
			if xp_per_day_dict[date].get('participations') is None:
				xp_per_day_dict[date]['participations'] = {}
			
			if xp_per_day_dict[date]['participations'].get(action) is None:
				xp_per_day_dict[date]['participations'][action] = []
			
			xp_per_day_dict[date]['participations'][action].append(student)

	return student_dict, xp_per_day_dict

def create_maindata(data, course, students):

	index = []
	headers = []
	result = []
	
	badges = get_badges(course)

	for student in students:
		index.append(student[1].decode())

	index.append("Dates")
	xp_df = pd.DataFrame({'Index Title': index}).set_index('Index Title')
	xp_df.index.name = None

	badges_df = pd.DataFrame({'Index Title': index}).set_index('Index Title')
	badges_df.index.name = None

	dates = list(sorted(data.items()))
	sdate = dates[0][0]   	# start date
	edate = dates[-1][0]   	# end date

	delta = edate - sdate   # as timedelta
	k = 0
	# for each day check how much xp each student had
	for i in range(delta.days + 1):			
		day = sdate + timedelta(days = i)
		day_data = data.get(day)

		values = []
		
		if day_data is not None:
			for student in students:
				xp = day_data.get(student[0])
				if xp is not None:
					values.append(xp['xp'])
				else:
					end = len(values)
					if len(result) > 0:
						previous_xp = result[-1][end]
					else:
						previous_xp = 0
					values.append(previous_xp)

			if day_data.get('badges') is not None:
				for badge in day_data['badges']:
					badge_values = []
					badge_name = badges[badge]
					headers.append(badge_name)
					for student in students:
						count = day_data['badges'][badge].count(student[0])
						badge_values.append(count)

					badge_values.append(day.strftime("%Y-%m-%d"))
					xp_df[k] = badge_values
					k += 1
			
			if day_data.get('participations') is not None:
				for p in day_data['participations']:
					part_values = []
					headers.append(p)
					for student in students:
						count = day_data['participations'][p].count(student[0])
						part_values.append(count)

					part_values.append(day.strftime("%Y-%m-%d"))
					xp_df[k] = part_values
					k += 1

		else:
			j = 0
			for student in students:
				if len(result) > 0:
					previous_xp = result[-1][j]
				else:
					previous_xp = 0
				values.append(previous_xp)
				j += 1
		values.append(day.strftime("%Y-%m-%d"))
		xp_df[k] = values
		k += 1
		result.append(values)
		headers.append('xp')

	
	xp_df.columns = headers
	#xp_df.to_excel('xp.xlsx', sheet_name='sheet1', index=index)

	aux_df = xp_df.copy()
	constant_filter = VarianceThreshold(threshold=0)
	aux_df.drop(aux_df.tail(1).index,inplace=True)
	constant_filter.fit(aux_df)
	non_constant = constant_filter.transform(xp_df) # this is a numpy

	dates_row = non_constant[-1]
	new_headers = aux_df.columns[constant_filter.get_support()]

	min_max_scaler = preprocessing.MinMaxScaler()
	normalized = min_max_scaler.fit_transform(constant_filter.transform(aux_df).astype(float))

	normalized_df = pd.DataFrame(np.vstack((normalized, dates_row))) 
	normalized_df.columns = new_headers # dataframe normalized, non-constant and with headers and dates

	#normalized_df.to_excel('maindata.xlsx', sheet_name='sheet1', index=index)
	return normalized, new_headers

def calculate_kmeans(data, num_clusters, min_cluster_size):
	"""
	Perform k-means clustering.
	"""
	xp_array = []
	for key in data:
		xp_array.append(np.array(data[key]['total']))

	xp = np.array(xp_array).reshape(-1, 1)
	kmeans = KMeans(n_clusters=num_clusters, random_state=0)
	prediction = kmeans.fit_predict(xp)

	unique, counts = np.unique(prediction, return_counts=True)

	if np.all(counts > min_cluster_size):
		kmeans = KMeans(n_clusters=num_clusters, random_state=0).fit(xp)
	
	else:
		kmeans = KMeans(n_clusters=(num_clusters - 1), random_state=0).fit(xp)

	return kmeans

def order_clusters(centers):
	ordered = {}
	i = 0
	for center in centers:
		ordered[center[0].astype(int)] = i
		i += 1
	
	return ordered

def clustering(total_xp, maindata, headers, num_clusters, min_cluster_size):

	prediction = calculate_kmeans(total_xp, num_clusters, min_cluster_size)
	ordered = order_clusters(prediction.cluster_centers_)
	grades = []
	clusters = []
	for entry in total_xp:
		grades.append(np.array(total_xp[entry]['total']))

	grades_np = np.array(grades).reshape(-1, 1)
	for el in prediction.predict(grades_np):
		clusters.append([el])

	data = np.append(maindata, clusters, axis=1)
	new_headers = headers.tolist()
	new_headers.append('Class')

	df = pd.DataFrame(data) 
	df.columns = new_headers

	X = np.array(df.iloc[:, df.columns != 'Class'])
	y = np.array(df.iloc[:, df.columns == 'Class'])

	sm = SMOTE(random_state=2)
	X_res, y_res = sm.fit_resample(X, y.ravel())

	rf = RandomForestClassifier(n_jobs=-1, class_weight='balanced', max_depth=5)
	boruta_feature_selector = BorutaPy(rf, n_estimators='auto', verbose=0, random_state=4242, max_iter = 200)
	boruta_feature_selector.fit(X_res, y_res)
	X_filtered = boruta_feature_selector.transform(X_res)
	final_features = list()
	indexes = np.where(boruta_feature_selector.support_ == True)
	for x in np.nditer(indexes):
		final_features.append(headers.tolist()[x])
	
	#X_train, X_test, y_train, y_test = train_test_split(X_filtered, y_res, test_size=0.8, random_state=0)

	rf = RandomForestRegressor()
	# Number of trees in random forest
	n_estimators = [1400]
	# Number of features to consider at every split
	max_features = ['auto']
	# Maximum number of levels in tree
	max_depth = [10]
	# Minimum number of samples required to split a node
	min_samples_split = [2]
	# Minimum number of samples required at each leaf node
	min_samples_leaf = [1]
	# Method of selecting samples for training each tree
	bootstrap = [False]
	# Create the random grid
	param_grid = {'n_estimators': n_estimators,
				'max_features': max_features,
				'max_depth': max_depth,
				'min_samples_split': min_samples_split,
				'min_samples_leaf': min_samples_leaf,
				'bootstrap': bootstrap}

	grid_search = GridSearchCV(estimator = rf, param_grid = param_grid, cv = 10, verbose = 0)
	grid_search.fit(X_filtered, y_res)
	X = boruta_feature_selector.transform(X)
	model = grid_search.predict(X)
	return ordered, model.astype(int).tolist()


# This python script will be invoked from the php side with
# an argument that indicated which course is being run
if __name__ == "__main__":
	#np.set_printoptions(threshold=sys.maxsize)
	#warnings.filterwarnings("ignore", category=DeprecationWarning)
	if len(sys.argv) != 4:
		error_msg = "ERROR: Profiler didn't receive all the information."
		#print(error_msg)
		f = open(RESULTS_PATH + "results.txt", "w")
		f.write(error_msg)
		f.close()
		sys.exit(1)
	
	course = sys.argv[1]
	file = open(RESULTS_PATH + course + "-results.txt", "w")

	if course_exists(course):
		num_clusters = int(sys.argv[2])
		min_cluster_size = int(sys.argv[3])
		try:
			awards = get_awards(course)
			participations = get_participations(course)

			if not awards:
				error_msg = "ERROR: No awards to analyze."
				file.write(error_msg)
				file.close()
				sys.exit(1)

			if not participations:
				error_msg = "ERROR: No participations to analyze."
				file.write(error_msg)
				file.close()
				sys.exit(1)

			students = get_students(course)
			total_xp, data_per_day = calculate_xp(course, awards, participations, students)
			maindata, headers = create_maindata(data_per_day, course, students)
			order, clustered = clustering(total_xp, maindata, headers, num_clusters, min_cluster_size)
			#print(order, '+', clustered)
			file.write(str(order) + '+' + str(clustered))
			file.close()

		except Exception as e:
			file.write("ERROR: " + str(e) + "\n" + str(traceback.format_exc()))
			file.close()
			sys.exit(1)


	else:
		error_msg = "ERROR: Course is not active or does not exist."
		file.write(error_msg)
		file.close()
		sys.exit(1)
		
