#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv
import mysql.connector

# bytearray(b\'text\') -> transformed into 'text' (as string)
def decode_item(item):
    if isinstance(item, str) and item.startswith("bytearray(b'") and item.endswith("')"):
        byte_array = bytearray(eval(item[10:-1]))
        decoded_string = byte_array.decode()
        return decode_item(decoded_string)

    #bytearray(b'Profile')
    elif isinstance(item, bytearray):
        return decode_item(item.decode())

    else:
        return item

# CLI prompt: python3 download_tables.py [dbHost] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
    """
        This script downloads information from the db's tables so later the DB can be re-instantiated for testing.
    """

    if len(sys.argv) == 5:

        output_directory = "./tables_files/"

        cnx = mysql.connector.connect(user=sys.argv[3], password=sys.argv[4], host=sys.argv[1], database=sys.argv[2])
        cursor = cnx.cursor(prepared=True)

        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()

        for table in tables:
            table_name = table[0]
            file_name = f"{table_name.decode()}.csv"
            file_path = output_directory + file_name

            # Retrieve data from the table
            query = "SELECT * FROM " + table_name.decode() + ";"
            cursor.execute(query)
            data = cursor.fetchall()

            # Export data to CSV file
            with open(file_path, "w", newline="") as csvfile:
                csv_writer = csv.writer(csvfile)

                for row in data:
                    decoded_row = []
                    for item in row:
                        decoded_row.append(decode_item(item))
                    csv_writer.writerow(decoded_row)

            print(table_name.decode() + "Exported successfully")

        cursor.close()
        cnx.close()