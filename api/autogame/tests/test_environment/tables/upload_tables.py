#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv
import mysql.connector

# CLI prompt: python3 upload_tables.py [dbHost] [dbName] [dbUser] [dbPass] [table_name] [backup]
if __name__ == "__main__":
    """
        This script uploads the information from cvs files inside the directory 
        tables_files into the DB. It acts as a re-instantiation of the DB for the testing environment.
        
        Last argument its either [table_name] or [backup]:
            * table_name: When running this script only by itself, its optional to pass a specific table_name to upload
            * backup: Indicates when this script is being called from the upload_backup.py script
    """

    if len(sys.argv) >= 5:

        input_directory = os.path.dirname(os.path.abspath(__file__)) + "/tables_files/"

        cnx = mysql.connector.connect(user=sys.argv[3], password=sys.argv[4], host=sys.argv[1], database=sys.argv[2])
        cursor = cnx.cursor(prepared=True)

        if len(sys.argv) == 6 and sys.argv[5] != 'backup':
            list = [sys.argv[5]+ ".csv"]
        else:
            list = os.listdir(input_directory)

        # Disable foreign key checks temporarily
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0")

        for filename in list:
            if filename.endswith(".csv"):
                file_path = os.path.join(input_directory, filename)
                table_name = os.path.splitext(filename)[0]

                # Truncate the table
                cursor.execute("TRUNCATE TABLE " + table_name + ";")

                # Read the data from the CSV file
                with open(file_path, "r") as csvfile:
                    csv_reader = csv.reader(csvfile)
                    data = [row for row in csv_reader]

                if len(data) > 0:
                    # Construct the SQL INSERT statement and execute it to insert the data into the table
                    query = "INSERT INTO " + table_name + f" VALUES ({', ' . join(['%s'] * len(data[0]))});"

                    parsed_data = []
                    for column in data:
                        parsed_values = []
                        for value in column:
                            if value == '':
                                parsed_values += [None]
                            else:
                                parsed_values += [value]

                        parsed_data += [parsed_values]

                    cursor.executemany(query, parsed_data)
                    cnx.commit()

                if (len(sys.argv) == 6 and sys.argv[5] != 'backup') or len(sys.argv) == 5:
                    print(table_name + " - IMPORTED SUCCESSFULLY")


        if len(sys.argv) == 6 and sys.argv[5] != 'backup':
            msg = "1 file exported"

        else:
            msg = str(len(list)) + "file(s) exported."

        if len(sys.argv) == 6 and sys.argv[5] != 'backup':
            print("---------------------------------")
            print(msg)

        # Enable foreign key checks again
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1")

        # Close the cursor and connection
        cursor.close()
        cnx.close()

    else:
        print("Arguments missing.")