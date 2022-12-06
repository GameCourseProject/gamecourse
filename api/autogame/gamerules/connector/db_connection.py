import logging
import sys

import mysql.connector


class Database:

    connection = None
    cursor = None

    student_dict = {}
    course_dict = {}

    def __init__(self, database, username, password):
        if Database.connection is None:
            try:
                Database.connection = mysql.connector.connect(user=username, password=password, host='localhost',
                                                              database=database)
                Database.cursor = Database.connection.cursor(prepared=True)

                self.connection = Database.connection
                self.cursor = Database.cursor

            except Exception as e:
                error_msg = "Couldn't connect to database '" + database + "'.\n" + str(e)
                logging.exception(error_msg)
                raise

        if Database.student_dict or Database.course_dict:
            Database.student_dict = {}
            Database.course_dict = {}

    def close_db(self):
        self.connection.close()

    def query(self,sql):
        cursor = Database.connection.cursor()
        cursor.execute(sql)

    def student_data_broker(self, id, query):
         try:
            result = self.student_dict[id][query]
            return result
         except KeyError:
            return False

    def course_data_broker(self, id, query):
         try:
            result = self.course_dict[id][query]
            return result
         except KeyError:
            return False


    def data_broker_add(self, dict, id, query, result):
         try:
           exist = dict[id][query]
         except:
           dict[id] = {}
         finally:
            dict[id][query] = result
