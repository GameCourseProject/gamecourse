#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv, glob
import mysql.connector
import subprocess

# List of python scripts to run when downloading data
# (path from current directory)
download_scripts = [
    '/course_data/copy_course_data.py',
    '/tables/download_tables.py'
]

# CLI prompt: python3 download_backup.py [dbHost] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
    """
        This script is the responsible for downloading all data to back up.
        This includes downloading all tables from DB and copying the course_data folder.
    """

    args = sys.argv[1:]
    args.append('backup')


    try:
        for script in download_scripts:
            full_path = os.path.dirname(os.path.abspath(__file__)) + script
            subprocess.run(['python3', full_path] + args, check=True)
            print(f"'{script}' done")

        print("---------------------------------")
        print("All data backed up successfully.")

    except subprocess.CalledProcessError as e:
        print(f"Error running : {e}")

    except Exception as e:
        print(f"Error: {e}")