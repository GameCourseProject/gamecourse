#!/usr/bin/env python
# -*- coding: utf-8 -*-
import sys
import os
import config
import json
from run_autogame import get_metadata
from tests import context

# CLI prompt: python3 get_functions.py [courseId]
if __name__ == "__main__":
    """
    This script retrieves information regarding the metadata of autogame on a given course
    """

    if len(sys.argv) == 2:
        config.COURSE = sys.argv[1]

        try:
            metadata = get_metadata()
            print(json.dumps(metadata))

        except IOError:
            raise Exception("No config file found for course with ID = %s." % course)

    else:
        print(json.dumps(None))