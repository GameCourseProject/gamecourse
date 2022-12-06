from gamerules.connector.db_connection import Database

gc_db = None


def connect_to_gamecourse_db(database, username, password):
    global gc_db
    gc_db = Database(database, username, password)
