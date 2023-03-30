from gamerules.connector.db_connection import Database

gc_db: Database
moodle_db: Database


### GameCourse

def connect_to_gamecourse_db(host, database, username, password):
    global gc_db
    gc_db = Database(host, database, username, password)

def close_connection_to_gamecourse_db():
    global gc_db
    try:
        gc_db
    except NameError:
        return
    else:
        if gc_db:
            gc_db.close()


### Moodle

def connect_to_moodle_db(host, database, username, password):
    global moodle_db
    moodle_db = Database(host, database, username, password)

def close_connection_to_moodle_db():
    global moodle_db
    try:
        moodle_db
    except NameError:
        return
    else:
        if moodle_db:
            moodle_db.close()


### Utils

def close_all_connections():
    close_connection_to_gamecourse_db()
    close_connection_to_moodle_db()
