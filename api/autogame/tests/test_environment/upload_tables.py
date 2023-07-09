#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv
import mysql.connector

# CLI prompt: python3 download_tables.py [dbHost] [dbName] [dbUser] [dbPass] [table_name]
if __name__ == "__main__":
    """
        This script uploads the information from cvs files inside the directory 
        tables_files into the DB. It acts as a re-instantiation of the DB for the testing environment.
        Optional to pass a specific table_name to upload
    """

    if len(sys.argv) >= 5:

        input_directory = "./tables_files/"

        cnx = mysql.connector.connect(user=sys.argv[3], password=sys.argv[4], host=sys.argv[1], database=sys.argv[2])
        cursor = cnx.cursor(prepared=True)

        if len(sys.argv) == 6:
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

                print(table_name + " - IMPORTED SUCCESSFULLY")

        print("---------------------------------")
        print(str(len(list)) + " file(s) imported.")

        # Enable foreign key checks again
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1")

        # Close the cursor and connection
        cursor.close()
        cnx.close()