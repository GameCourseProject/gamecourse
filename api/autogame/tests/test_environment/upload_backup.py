#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv, glob
import mysql.connector
import subprocess

# List of python scripts to run when uploading data
# (path from current directory)
upload_scripts = [
    '/course_data/paste_course_data.py',
    '/tables/upload_tables.py'
]

# CLI prompt: python3 upload_backup.py [dbHost] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
    """
        This script is the responsible for uploading all the backed up data.
        This includes uploading all tables into the DB and uploading info into the course_data folder.
    """

    args = sys.argv[1:]
    args.append('backup')


    try:
        for script in upload_scripts:
            full_path = os.path.dirname(os.path.abspath(__file__)) + script
            subprocess.run(['python3', full_path] + args, check=True)
            print(f"'{script}' done")

        print("---------------------------------")
        print("All data uploaded successfully.")

    except subprocess.CalledProcessError as e:
        print(f"Error running : {e}")

    except Exception as e:
        print(f"Error: {e}")