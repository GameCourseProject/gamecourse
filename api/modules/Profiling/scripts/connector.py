#!/usr/bin/env python
import mysql.connector

def init(dbHost, dbName, dbUser, dbPass):
    global cnx
    global cursor

    cnx = mysql.connector.connect(user=dbUser, password=dbPass, host=dbHost, database=dbName)
    cursor = cnx.cursor(prepared=True)

def close():
    cnx.close()