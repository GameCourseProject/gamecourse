#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv, glob
import mysql.connector


# bytearray(b\'text\') -> transformed into 'text' (as string)
def decode_item(item):
    if isinstance(item, str) and item.startswith("bytearray(b'") and item.endswith("')"):
        byte_array = bytearray(eval(item[10:-1]))
        decoded_string = byte_array.decode()
        return decode_item(decoded_string)

    elif isinstance(item, bytearray):
        return decode_item(item.decode())

    else:
        return item


# CLI prompt: python3 download_tables.py [dbHost] [dbName] [dbUser] [dbPass] [table_name] [backup]
if __name__ == "__main__":
    """
        This script downloads information from the db's tables so later the DB can be re-instantiated for testing.

        Last argument its either [table_name] or [backup]:
            * table_name: When running this script only by itself, its optional to pass a specific table_name to download
            * backup: Indicates when this script is being called from the download_backup.py script
    """

    if len(sys.argv) >= 5:

        output_directory = os.path.dirname(os.path.abspath(__file__)) + "/tables_files/"

        cnx = mysql.connector.connect(user=sys.argv[3], password=sys.argv[4], host=sys.argv[1], database=sys.argv[2])
        cursor = cnx.cursor(prepared=True)

        if len(sys.argv) == 6 and sys.argv[5] != 'backup':
            # Delete file before downloading
            file_path = os.path.join(output_directory, sys.argv[5] + '.csv')
            os.remove(file_path)

            query = "SELECT * FROM " + sys.argv[5] + ";"

        else:
            # Delete all files before starting download
            file_list = glob.glob(os.path.join(output_directory, '*'))

            # Iterate over the files and delete 1 by 1
            for file_path in file_list:
                if os.path.isfile(file_path):
                    os.remove(file_path)

            query = "SHOW TABLES"

        cursor.execute(query)
        tables = cursor.fetchall()

        for table in tables:
            if len(sys.argv) == 6 and sys.argv[5] != 'backup':
                table_name = sys.argv[5]
            else:
                table_name = table[0].decode()

            file_name = f"{table_name}.csv"
            file_path = os.path.join(output_directory, file_name)

            # Retrieve data from the table
            query = "SELECT * FROM " + table_name + ";"
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

            if (len(sys.argv) == 6 and sys.argv[5] != 'backup') or len(sys.argv) == 5:
                print(table_name + " - EXPORTED SUCCESSFULLY")

            if len(sys.argv) == 6 and sys.argv[5] != 'backup':
                break

        if len(sys.argv) == 6 and sys.argv[5] != 'backup':
            msg = "1 file exported"

        else:
            msg = str(len(tables)) + " file(s) exported."

        if len(sys.argv) == 6 and sys.argv[5] != 'backup':
            print("---------------------------------")
            print(msg)

        cursor.close()
        cnx.close()

    else:
        print("Arguments missing.")