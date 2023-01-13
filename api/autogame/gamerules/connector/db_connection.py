#!/usr/bin/env python3
import logging
import mysql.connector


class Database:

    connection = None
    cursor = None
    data_broker = None

    def __init__(self, database, username, password):
        try:
            # Connect to database
            Database.connection = mysql.connector.connect(user=username, password=password, host='localhost',
                                                          database=database)
            Database.cursor = Database.connection.cursor(prepared=True)

            self.connection = Database.connection
            self.cursor = Database.cursor
            self.data_broker = DataBroker()

        except Exception as e:
            error_msg = "Couldn't connect to database '" + database + "'.\n" + str(e)
            logging.exception(error_msg)
            raise

    def close(self):
        # Close connection to database
        self.connection.close()

    def execute_query(self, query, args=(), type="fetch"):
        # Execute a given SQL query
        self.cursor.execute(query, args)

        if type is "fetch":
            return self.cursor.fetchall()

        elif type is "commit":
            self.connection.commit()


class DataBroker:

    student_dict = {}
    course_dict = {}

    def __int__(self):
        pass

    def get(self, db, key, query, type="course"):
        try:
            dict = self.course_dict if type is "course" else self.student_dict
            return dict[key][query]

        except KeyError:
            result = db.execute_query(query)
            self.add(key, query, result, type)
            return result

    def add(self, key, query, result, type="course"):
        dict = self.course_dict if type is "course" else self.student_dict
        try:
            exist = dict[key][query]

        except:
            dict[key] = {}

        finally:
            dict[key][query] = result
