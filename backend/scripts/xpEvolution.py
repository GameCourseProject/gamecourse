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
            
    all_xp_evolution = []
    for target in targets:
        query = "SELECT user, course, description, reward, date, @total:=@total + reward AS total_reward FROM award JOIN (SELECT @total:=0) AS t WHERE course = %s AND user = %s AND type != 'tokens' ORDER BY date ASC;"
        cursor.execute(query, (course, target))

        target_xp_evolution = cursor.fetchall()

        all_xp_evolution += target_xp_evolution

    cnx.close()

    header = ["user", "course", "description", "reward", "date", "total_reward"]

    return header, all_xp_evolution

def export():
    columns, evolution = fetch_data()

    if os.path.exists("xp_evolution.csv"):
        try:
            os.remove("xp_evolution.csv")
        except OSError:
            pass

    # Create csv file
    f = open('xp_evolution' + '.csv', 'w')

    # Write header
    f.write(','.join(columns) + '\n')

    for row in evolution:
        f.write(','.join(str(r) for r in row) + '\n')

    f.close()
    print(str(len(evolution)) + ' rows written successfully to ' + f.name)


export()