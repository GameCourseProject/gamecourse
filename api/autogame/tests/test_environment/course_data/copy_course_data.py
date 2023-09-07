#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, shutil, json, csv, glob

# CLI prompt: python3 copy_course_data.py [dbHost] [dbName] [dbUser] [dbPass] backup
if __name__ == "__main__":
    """
        This script copies all the information inside the course_data folder into 
        the course_data_files folder of this directory.

        Last argument backup only indicates if the file is being called from the download_backup.py script.
    """

    # source and destination paths
    source_folder = os.path.dirname(os.path.abspath(__file__)) + "/../../../../course_data/"
    destination_folder = os.path.dirname(os.path.abspath(__file__)) + "/course_data_files/"

    try:
        if len(os.listdir(source_folder)) == 0 & len(sys.argv) == 5:
            print("Nothing to copy.")
        else:
            # remove all items in destination folder before copying
            shutil.rmtree(destination_folder)
            os.mkdir(destination_folder)  # Recreate empty folder

            for item in os.listdir(source_folder):
                source_item = os.path.join(source_folder, item)
                destination_item = os.path.join(destination_folder, item)

                if os.path.isfile(source_item):  # is file
                    shutil.copy(source_item, destination_item)

                elif os.path.isdir(source_item):  # is directory
                    shutil.copytree(source_item, destination_item)

            if len(sys.argv) == 5:
                print(f"\'course_data\' folder successfully copied")

    except shutil.Error as e:
        print(f"Error: {e}")
    except Exception as e:
        print(f"Error: {e}")
