#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from io import StringIO

import os, sys, shutil, json, csv, glob

# CLI prompt: python3 paste_course_data.py [dbHost] [dbName] [dbUser] [dbPass] backup
if __name__ == "__main__":
    """
        This script pastes all the information from course_data_files into the course_data folder.

        Last argument backup is optional only indicates if the file is being called from the upload_backup.py script.
    """

    # source and destination paths
    destination_folder = os.path.dirname(os.path.abspath(__file__)) + "/../../../../course_data/"
    source_folder = os.path.dirname(os.path.abspath(__file__)) + "/course_data_files/"

    try:
        if len(os.listdir(source_folder)) == 0 & len(sys.argv) == 5:
            print("Nothing to paste")

        else:
            # remove all items in destination folder before pasting
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
                print(f"\'course_data\' folder successfully pasted")

    except shutil.Error as e:
        print(f"Error: {e}")
    except Exception as e:
        print(f"Error: {e}")
