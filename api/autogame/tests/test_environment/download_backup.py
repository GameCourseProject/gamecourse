#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, json, csv, glob
import mysql.connector
import subprocess

# List of python scripts to run when downloading data
download_scripts = [
    #'/course_data/copy_course_data.py',
    './tables/download_tables.py'
]

# CLI prompt: python3 [dbHost] [dbName] [dbUser] [dbPass] [table_name]
if __name__ == "__main__":
    """
        This script is the responsible for downloading all data to back up.
        This includes downloading all tables from DB and copying the course_data folder.
        
        Optional to pass a specific table_name to download
    """

    args = sys.argv[1:]
    print(args)

    for script in download_scripts:
        try:
            subprocess.run(['python', script] + args, check=True)
            print(f"'{script}' done")

        except subprocess.CalledProcessError as e:
            print(f"Error running '{script}': {e}")

        except Exception as e:
            print(f"Error: {e}")