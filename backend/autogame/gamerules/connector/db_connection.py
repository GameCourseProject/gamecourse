import mysql.connector
from gamerules.connector.db_connector import get_db_credentials

class Database:

    connection = None
    cursor = None

    queries = []
    results = []

    def __init__(self):
        if Database.connection is None:
            try:
                (database, username, password) = get_db_credentials()
                Database.connection = mysql.connector.connect(user=username, password=password, host='localhost', database=database)
                Database.cursor = Database.connection.cursor(prepared=True)
            except Exception as error:
                print("Error: Connection not established {}".format(error))
            else:
                print("Connection established")

        if Database.queries:
            Database.queries = []
            Database.results = []


        self.connection = Database.connection
        self.cursor = Database.cursor

    def close_db(self):
        self.connection.close()

    def data_broker(self, query):

        if query in self.queries:
            # if exists, get the results and return it.
            index = Database.queries.index(query)
            return Database.results[index]
        else:
            return False

    def query(self,sql):
        cursor = Database.connection.cursor()
        cursor.execute(sql)