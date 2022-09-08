#!/usr/bin/env python

import connector
from profiler import *
from kneed import KneeLocator
from sklearn.metrics import silhouette_samples, silhouette_score
import matplotlib.pyplot as plt
import operator

def predict_kmeans_silhouette(data):
    nr_students = len(data)

    xp_array = []
    for key in data:
        xp_array.append(np.array(data[key]['total']))

    xp = np.array(xp_array).reshape(-1, 1)

    kmeans_kwargs = {
        "init": "random",
        "n_init": 10,
        "max_iter": 300,
        "random_state": 42,
    }

    scores = {}

    for n_clusters in range(2, min(11, nr_students)):
        clusterer = KMeans(n_clusters=n_clusters, **kmeans_kwargs)
        clusterer.fit(xp)
        silhouette_avg = silhouette_score(xp, clusterer.labels_)
        scores[n_clusters] = silhouette_avg
    
    return max(scores.items(), key=operator.itemgetter(1))[0]

def predict_kmeans_elbow(data):
    nr_students = len(data)

    kmeans_kwargs = {
        "init": "random",
        "n_init": 10,
        "max_iter": 300,
        "random_state": 42,
    }

    xp_array = []
    for key in data:
        xp_array.append(np.array(data[key]['total']))

    xp = np.array(xp_array).reshape(-1, 1)

    # A list holds the SSE values for each k
    sse = []
    for k in range(2, min(11, nr_students)):
        kmeans = KMeans(n_clusters=k, **kmeans_kwargs)
        kmeans.fit(xp)
        sse.append(kmeans.inertia_)

    kl = KneeLocator(range(2, min(11, nr_students)), sse, curve="convex", direction="decreasing")

    return kl.elbow

# This python script will be invoked from the php side with
# an argument that indicated which course is being run and
# an argument that indicated the prediction method to be used
if __name__ == "__main__":
    if len(sys.argv) != 9:
        error_msg = "ERROR: Predictor didn't receive all the information."
        sys.exit(error_msg)

    course = sys.argv[1]
    method = sys.argv[2]
    end_date = sys.argv[3]
    logs_path = sys.argv[4]

    connector.init(sys.argv[5], sys.argv[6], sys.argv[7], sys.argv[8].strip())

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

            students = get_students(course)
            total_xp, data_per_day = calculate_xp(course, awards, [], students)

            if method == 'e':
                result = predict_kmeans_elbow(total_xp)
                file.write(str(result))
                file.close()
                connector.close()
                sys.exit(0)
            
            elif method == 's':
                result = predict_kmeans_silhouette(total_xp)
                file.write(str(result))
                file.close()
                connector.close()
                sys.exit(0)

            else:
                error_msg = "ERROR: Unknown method. \n - Type 's' for silhouette. \n - Type 'e' for elbow."
                file.write(error_msg)
                file.close()
                connector.close()
                sys.exit(error_msg)

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