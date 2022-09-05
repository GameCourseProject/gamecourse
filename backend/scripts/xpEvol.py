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

    # COMMAND : python3 xpDetailedEvolution.py 'course_id' 'target (OPTIONAL)'
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

    if len (sys.argv) == 2:
        query = "SELECT DISTINCT(DATE_FORMAT(date, '%d %c %Y')) AS day FROM award WHERE course = \"" + str(course) + "\"GROUP BY DATE_FORMAT(date, '%d %c %Y') ORDER BY date ASC; "
        cursor.execute(query)
        all_dates = cursor.fetchall()
    else:
        query = "SELECT DISTINCT(DATE_FORMAT(date, '%d %c %Y')) FROM award WHERE course = \"" + str(course) + "\" AND user = \"" + str(targets[0]) + "\" GROUP BY DATE_FORMAT(date, '%d %c %Y') ORDER BY date ASC;"
        cursor.execute(query)
        all_dates = cursor.fetchall()

    days = len(all_dates)
    a_list = list(range(1, days))

    day = [] # day[0] = [date, day]
    dayCounter = 0
    for date in all_dates:
        found = False
        for d in day:
            if date[0] == d[0]:
                found = True
        if not found:
            dayCounter = dayCounter + 1
            day.append([date[0], dayCounter])

    all_xp_evolution = []
    for target in targets:
        query = "SELECT user, course, DATE_FORMAT(date, '%d %c %Y'), @total:=@total + reward AS total_reward FROM award JOIN (SELECT @total:=0) AS t WHERE course = \"" + str(course) + "\" AND user = \"" + str(target) + "\"  AND type != 'tokens' ORDER BY date ASC;"
        cursor.execute(query)
        target_xp_evolution = cursor.fetchall()


        lst = [None] * 5
        new_array = []
        for i in range(len(target_xp_evolution)):
            line = target_xp_evolution[i]
            lst[0] = line[0]
            lst[1] = line[1]
            lst[2] = line[2]
            lst[3] = line[3]

            day_number = 0
            for d in day:
                if line[2].decode() == d[0].decode():
                    day_number = d[1]
            lst[4] = day_number

            new_array.append(tuple(lst))


        all_xp_evolution += new_array


    cnx.close()

    header = ["user", "course", "date", "total_reward", "day"]

    return header, all_xp_evolution

def export():
    columns, evolution = fetch_data()

    if len (sys.argv) == 2 :
        file_name = "all_xp_evol.csv"
    elif (len(sys.argv)) == 3:
        file_name = "xp_evol_" + sys.argv[2] + ".csv"
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