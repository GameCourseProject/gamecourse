import os

ROOT_PATH = os.path.dirname(os.path.abspath(__file__))
IMPORTED_FUNCTIONS_FOLDER = os.path.join(ROOT_PATH, "imported-functions")
RULES_PATH = None

COURSE = None
TEST_MODE = False
AUTOSAVE = False

LOG_USER_COL = 1
LOG_SOURCE_COL = 3
LOG_DESCRIPTION_COL = 4
LOG_TYPE_COL = 5
LOG_POST_COL = 6
LOG_DATE_COL = 7
LOG_RATING_COL = 8
LOG_EVALUATOR_COL = 9

AWARD_DESCRIPTION_COL = 3
AWARD_TYPE_COL = 4
AWARD_INSTANCE_COL = 5
AWARD_REWARD_COL = 6
AWARD_DATE_COL = 7

HOST = "127.0.0.1"  # The server's hostname or IP address
PORT = 8004         # The port used by the server
