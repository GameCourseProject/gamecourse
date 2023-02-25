from gamerules.connector.db_connection import Database

gc_db: Database
moodle_db: Database


def connect_to_gamecourse_db(host, database, username, password):
    global gc_db
    gc_db = Database(host, database, username, password)

def connect_to_moodle_db(host, database, username, password):
    global moodle_db
    moodle_db = Database(host, database, username, password)
