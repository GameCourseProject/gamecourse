import os

ROOT_PATH = os.path.dirname(os.path.abspath(__file__))
IMPORTED_FUNCTIONS_FOLDER = os.path.join(ROOT_PATH, "imported-functions")
RULES_PATH = None

COURSE = None
TEST_MODE = False
AUTOSAVE = False

DESCRIPTION_COL = 4
DATE_COL = 7
RATING_COL = 8

HOST = "127.0.0.1"  # The server's hostname or IP address
PORT = 8004         # The port used by the server
