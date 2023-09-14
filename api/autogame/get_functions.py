#!/usr/bin/env python
# -*- coding: utf-8 -*-
import sys
import os
import json
from gamerules.functions.utils import import_functions_from_rulepath
from tests import context

# CLI prompt: python3 get_functions.py [courseId] [dbHost] [dbName] [dbUser] [dbPass]
if __name__ == "__main__":
    """ This script retrieves information regarding the custom functions installed
    on a given gamerules instance/course. """

    if len(sys.argv) == 6:
        course = sys.argv[1]

        # Initialize GameCourse connector
        from gamerules.connector.db_connector import connect_to_gamecourse_db
        connect_to_gamecourse_db(sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5])
        from gamerules.connector.gamecourse_connector import *

        path = os.path.join(os.path.dirname(os.path.realpath(__file__)), "imported-functions", course)
        res = import_functions_from_rulepath(path, info=True)

        if len(res) == 3:
            functions, fpaths, info = res

            info = sorted(info, key=lambda x: x["keyword"], reverse=False)

            if len(info) <= 0:
                print(json.dumps(None))
            else:
                print(json.dumps(info))

    else:
        print(json.dumps(None))