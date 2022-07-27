import socket
import signal, os, sys, logging
import json
import mysql.connector

def get_credentials():
    # -----------------------------------------------------------
    # Read db credentials from file
    # -----------------------------------------------------------
    with open(os.path.join(os.path.dirname(os.path.realpath(__file__)),'credentials.txt'), 'r') as f:
        data = f.readlines()
        if len(data) == 3:
            db = data[0].strip('\n')
            un = data[1].strip('\n')
            pw = data[2].strip('\n')
            return (db, un, pw)


def fetch_data ():
    (database, username, password) = get_credentials()
    cnx = mysql.connector.connect(user=username, password=password,
    host='localhost', database=database)
    cursor = cnx.cursor(prepared=True)

    # COMMAND : python3 profillingResults.py 'course_id' 'target (OPTIONAL)'
    if len(sys.argv) <= 1:
        print("Missing argument: Please specify the course.")
    else:
        course = sys.argv[1]
        targets = []
        if len (sys.argv) == 2:
            query = "SELECT user_role.id FROM user_role left join role on user_role.role=role.id WHERE user_role.course = %s AND role.name= 'Student'; "
            cursor.execute(query, course)
            table = cursor.fetchall()
            for target in table:
                targets.append(target[0])
        elif len (sys.argv) == 3:
            # target was given
            targets.append(sys.argv[2])

    all_role_evolution = []
    for target in targets:
        query = "SELECT user_profile.user, user_profile.date, user_profile.cluster, role.name FROM user_profile JOIN role ON user_profile.cluster = role.id WHERE user_profile.course = %s  AND user_profile.user = %s ORDER BY date ASC;"
        cursor.execute(query, (course, target))
        target_role_evolution = cursor.fetchall()

        for line in target_role_evolution:
            line_list =  list(line)
            line_list[3] = line_list[3].decode("utf-8")

        all_role_evolution += target_role_evolution

    cnx.close()

    header = ["user", "date", "cluster", "cluster_name"]

    return header, all_role_evolution


def export():
    columns, evolution = fetch_data()

    if len (sys.argv) == 2 :
        file_name = "all_role_evolution.csv"
    elif (len(sys.argv)) == 3:
        file_name = "role_evolution_" + sys.argv[2] + ".csv"
    else:
        file_name = ''
        print("Incorrect number of arguments given.")

    if os.path.exists("results/" + file_name):
        try:
            os.remove("results/" + file_name)
        except OSError:
            pass

    # Create csv file
    f = open('results/' + file_name, 'w')

    # Write header
    f.write(','.join(columns) + '\n')

    for row in evolution:
        f.write(','.join(str(r) for r in row) + '\n')

    f.close()
    print(str(len(evolution)) + ' rows written successfully to ' + f.name)




export()