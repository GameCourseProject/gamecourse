#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv
import mysql.connector

# CLI prompt: python3 download_tables.py [dbHost] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
    """
        This script uploads the information from cvs files inside the directory 
        tables_files into the DB. It acts as a re-instantiation of the DB for the testing environment.
    """

    if len(sys.argv) == 5:

        input_directory = "./tables_files/"

        cnx = mysql.connector.connect(user=sys.argv[3], password=sys.argv[4], host=sys.argv[1], database=sys.argv[2])
        cursor = cnx.cursor(prepared=True)

        # Disable foreign key checks temporarily
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0")

        for filename in os.listdir(input_directory):
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
                    cursor.executemany(query, data)
                    cnx.commit()

            print(os.path.splitext(filename)[0] + "Imported successfully")

        # Enable foreign key checks again
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1")

        # Close the cursor and connection
        cursor.close()
        cnx.close()