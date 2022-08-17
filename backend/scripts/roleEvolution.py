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

    # COMMAND : python3 xpEvolution.py 'course_id' 'target (OPTIONAL)'
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

    first = ["user"]
    # gets all unique dates from award table
    if len (sys.argv) == 2:
        query = "SELECT DISTINCT(DATE_FORMAT(date, '%d %c %Y')) AS day FROM user_profile WHERE course = \"" + str(course) + "\"GROUP BY DATE_FORMAT(date, '%d %c %Y') ORDER BY date ASC; "
        cursor.execute(query)
        all_dates = cursor.fetchall()
    else:
        query = "SELECT DISTINCT(DATE_FORMAT(date, '%d %c %Y')) FROM user_profile WHERE course = \"" + str(course) + "\" AND user = \"" + str(target) + "\" GROUP BY DATE_FORMAT(date, '%d %c %Y') ORDER BY date ASC;"
        cursor.execute(query)
        all_dates = cursor.fetchall()

    header = ["user"]
    for date in all_dates:
        header.append(date[0].decode())

    # header = ['user', 'date_1', 'date_2', 'date_3', ... , 'date_n']

    all_role_evolution = []
    for target in targets:
        query = "SELECT user_profile.user, DATE_FORMAT(user_profile.date, '%d %c %Y'), user_profile.cluster, role.name FROM user_profile JOIN role ON user_profile.cluster = role.id WHERE user_profile.course = \"" + str(course) + "\"  AND user_profile.user = \"" + str(target) + "\" ORDER BY user_profile.date ASC; "
        cursor.execute(query)
        target_role_evolution = cursor.fetchall()

        lst = [None] * len(header)
        for i in range(len(target_role_evolution)):
            line = target_role_evolution[i]
            date_index = header.index(line[1].decode())
            lst[0] = line[0]
            lst[date_index] = line[3]

        if lst[1] is None:
            lst[1] = 0

        for i in range(2, len(lst)):
            if lst[i] is None and lst[i-1] is not None:
                lst[i] = lst[i-1]

        all_role_evolution.append(lst,)

    cnx.close()
    return header, all_role_evolution


def export():
    columns, evolution = fetch_data()

    if len (sys.argv) == 2 :
        file_name = "all_role_evolution_course" + sys.argv[1] +".csv"
    elif (len(sys.argv)) == 3:
        file_name = "role_evolution_course" + sys.argv[1] + "_"+  sys.argv[2] + ".csv"
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