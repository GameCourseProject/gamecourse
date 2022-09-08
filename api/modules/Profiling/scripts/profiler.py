#!/usr/bin/env python

import os, sys, json, csv
import connector

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

def course_exists(course):
    """
    Checks if course exists and is active
	"""
    cursor = connector.cursor
    query = "SELECT isActive FROM course WHERE id = %s;"
    args = (course,)

    cursor.execute(query, args)
    table = cursor.fetchall()

    if len(table) == 1:
        return table[0][0]
    else:
        return False


def get_students(course):
    """
    Get IDs and names of the students enrolled in the course.
	"""
    cursor = connector.cursor
    query = "SELECT u.id, u.name FROM user_role ur left join course_user cu on cu.id = ur.user and ur.course=cu.course left join user u on cu.id = u.id join role r on ur.role = r.id WHERE ur.course = %s and r.name = 'Student' and cu.isActive = %s;"
    args = (course, True)

    cursor.execute(query, args)
    table = cursor.fetchall()
    return table


def get_badges(course):
    """
    Get badges in the course.
	"""
    cursor = connector.cursor
    query = "SELECT id, name FROM badge WHERE course = %s and isActive = %s;"
    cursor.execute(query, (course, True))
    badges = cursor.fetchall()

    badge_dict = {}
    for badge in badges:
        badge_dict[badge[0]] = badge[1].decode()

    return badge_dict


def get_awards(course, end_date):
    """
    Get awards given in the course.
	"""
    cursor = connector.cursor
    query = "SELECT * FROM award WHERE course = %s AND date <= %s ORDER BY date;"
    args = (course,end_date,)

    cursor.execute(query, args)
    table = cursor.fetchall()
    return table


def get_participations(course, end_date):
    """
    Get awards given in the course.
	"""
    cursor = connector.cursor
    query = "SELECT user, type, date FROM participation WHERE course = %s AND date <= %s AND type != 'lab grade' AND type not like 'attended %' AND type != 'initial bonus' AND type != 'quiz grade' AND type != 'graded post' AND type != 'suggested presentation subject' AND type != 'participated in lecture' ORDER BY date;"
    args = (course,end_date,)

    cursor.execute(query, args)
    table = cursor.fetchall()
    return table


def calculate_xp(course, awards, participations, students):
    """
	Calculate the total xp and the xp accumulated per day by each student in the course.
	"""
    cursor = connector.cursor

    student_dict = {}
    xp_per_day_dict = {}

    for student in students:
        student_id = student[0]
        student_dict[student_id] = {}
        student_dict[student_id]['total'] = 0

    for award in awards:
        if award[4].decode() == 'tokens':
            continue

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

            student_dict[student]['total'] += reward
            xp_per_day_dict[date][student]['xp'] = student_dict[student]['total']

            if award_type not in student_dict[student]:
                student_dict[student][award_type] = 0

            student_dict[student][award_type] += reward

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

    dates = list(sorted(data.items()))

    sdate = dates[0][0]  # start date
    edate = dates[-1][0]  # end date

    delta = edate - sdate  # as timedelta
    k = 0
    # for each day check how much xp each student had
    for i in range(delta.days + 1):
        day = sdate + timedelta(days=i)
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

    aux_df = xp_df.copy()
    constant_filter = VarianceThreshold(threshold=0)
    aux_df.drop(aux_df.tail(1).index, inplace=True)
    constant_filter.fit(aux_df)
    non_constant = constant_filter.transform(xp_df)  # this is a numpy

    dates_row = non_constant[-1]
    new_headers = aux_df.columns[constant_filter.get_support()]

    min_max_scaler = preprocessing.MinMaxScaler()
    normalized = min_max_scaler.fit_transform(constant_filter.transform(aux_df).astype(float))

    normalized_df = pd.DataFrame(np.vstack((normalized, dates_row)))
    normalized_df.columns = new_headers  # dataframe normalized, non-constant and with headers and dates

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
    boruta_feature_selector = BorutaPy(rf, n_estimators='auto', verbose=0, random_state=4242, max_iter=200)
    boruta_feature_selector.fit(X_res, y_res)
    X_filtered = boruta_feature_selector.transform(X_res)
    final_features = list()
    indexes = np.where(boruta_feature_selector.support_ == True)
    for x in np.nditer(indexes):
        final_features.append(headers.tolist()[x])

    # X_train, X_test, y_train, y_test = train_test_split(X_filtered, y_res, test_size=0.8, random_state=0)

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

    grid_search = GridSearchCV(estimator=rf, param_grid=param_grid, cv=10, verbose=0)
    grid_search.fit(X_filtered, y_res)
    X = boruta_feature_selector.transform(X)
    model = grid_search.predict(X)
    return ordered, model.astype(int).tolist()


# This python script will be invoked from the php side with
# an argument that indicated which course is being run
if __name__ == "__main__":
    if len(sys.argv) != 10:
        error_msg = "ERROR: Profiler didn't receive all the information."
        sys.exit(error_msg)

    course = sys.argv[1]
    num_clusters = int(sys.argv[2])
    min_cluster_size = int(sys.argv[3])
    end_date = sys.argv[4]
    logs_path = sys.argv[5]

    connector.init(sys.argv[6], sys.argv[7], sys.argv[8], sys.argv[9].strip())

    file = open(logs_path, "w")
    if course_exists(course):
        try:
            awards = get_awards(course, end_date)
            if not awards:
                error_msg = "ERROR: No awards to analyze."
                file.write(error_msg)
                file.close()
                connector.close()
                sys.exit(error_msg)

            participations = get_participations(course, end_date)
            if not participations:
                error_msg = "ERROR: No participations to analyze."
                file.write(error_msg)
                file.close()
                connector.close()
                sys.exit(error_msg)

            students = get_students(course)
            total_xp, data_per_day = calculate_xp(course, awards, participations, students)
            maindata, headers = create_maindata(data_per_day, course, students)
            order, clustered = clustering(total_xp, maindata, headers, num_clusters, min_cluster_size)

            file.write(str(order) + '+' + str(clustered))
            file.close()
            connector.close()
            sys.exit(0)

        except Exception as e:
            error_msg = "ERROR: " + str(e) + "\n" + str(traceback.format_exc())
            file.write(error_msg)
            file.close()
            connector.close()
            sys.exit(error_msg)

    else:
        error_msg = "ERROR: Course is not active or does not exist."
        connector.close()
        sys.exit(error_msg)
