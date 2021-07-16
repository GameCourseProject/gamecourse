#!/usr/bin/env python

from profiler import *
from kneed import KneeLocator
from sklearn.metrics import silhouette_samples, silhouette_score
import matplotlib.pyplot as plt
import operator

def predict_kmeans_silhouette(data):

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

    for n_clusters in range(2, 11):
        clusterer = KMeans(n_clusters=n_clusters, **kmeans_kwargs)
        clusterer.fit(xp)
        silhouette_avg = silhouette_score(xp, clusterer.labels_)
        scores[n_clusters] = silhouette_avg
    
    return max(scores.items(), key=operator.itemgetter(1))[0]

def predict_kmeans_elbow(data):
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
    for k in range(2, 11):
        kmeans = KMeans(n_clusters=k, **kmeans_kwargs)
        kmeans.fit(xp)
        sse.append(kmeans.inertia_)

    kl = KneeLocator(range(2, 11), sse, curve="convex", direction="decreasing")

    return kl.elbow

# This python script will be invoked from the php side with
# an argument that indicated which course is being run and
# an argument that indicated the prediction method to be used
if __name__ == "__main__":
    if len(sys.argv) != 3:
        error_msg = "ERROR: Profiler didn't receive all the information."
        f = open(RESULTS_PATH + "prediction.txt", "w")
        f.write(error_msg)
        f.close()
        sys.exit(1)

    file = open(RESULTS_PATH + "prediction.txt", "w")
    course = sys.argv[1]
    method = sys.argv[2]
    if course_exists(course):
        try:
            awards = get_awards(course)

            if not awards:
                error_msg = "ERROR: No awards to analyze."
                print(error_msg)
                file.write(error_msg)
                file.close()
                sys.exit(1)

            students = get_students(course)
            total_xp, data_per_day = calculate_xp(course, awards, [], students)

            if method == 'e':
                result = predict_kmeans_elbow(total_xp)
                file.write(str(result))
                file.close()
                sys.exit(0)
            
            elif method == 's':
                result = predict_kmeans_silhouette(total_xp)
                file.write(str(result))
                file.close()
                sys.exit(0)
            


            else:
                error_msg = "ERROR: Unknown method. \n - Type 's' for silhouette. \n - Type 'e' for elbow."
                print(error_msg)
                file.write(error_msg)
                file.close()
                sys.exit(1)

        
        except Exception as e:
            print("ERROR: " + str(e) + "\n" + str(traceback.format_exc()))
            file.write("ERROR: " + str(e) + "\n" + str(traceback.format_exc()))
            file.close()
            sys.exit(1)