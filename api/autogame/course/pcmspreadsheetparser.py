#!/usr/bin/env python
# -*- coding: utf-8 -*-

from .coursedata import METADATA_PATH
from .logline import LogLine

import os
import gdata
import time

CLIENT_ID = '370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com'
CLIENT_SECRET = 'hC4zsuwH1fVIWi5k0C4zjOub'
SCOPE = 'https://spreadsheets.google.com/feeds/'
auth_file = 'google_auth.txt'
application_name = 'spreadsheets'
AUTHENTICATION_FPATH = os.path.join(METADATA_PATH,"google",auth_file)

class PCMSpreadsheetParser:

	def __init__(self):
		self._Authorize()
		self.curr_key = ''
		self.curr_wksht_id = ''

	def _Authorize(self):
		token = None

		if not(os.path.exists(AUTHENTICATION_FPATH)):

			print("authentication file wasn't found in:", AUTHENTICATION_FPATH)
			print("creating a new authentication file in the same location")
			print("generating a new token ... ")

			token = gdata.gauth.OAuth2Token(
				client_id=CLIENT_ID,
				client_secret=CLIENT_SECRET,
				scope=SCOPE,
				user_agent=application_name);

			print("DONE!")
			print("authorize token url ... ")

			url = token.generate_authorize_url()

			print("DONE!")
			print('Use this url to authorize the application: \n')
			print(url)

			code = input('What is the verification code? ').strip()

			print("getting access token ... ")

			token.get_access_token(code)

			print("DONE!")
			print("saving token to authentication file:", AUTHENTICATION_FPATH)

			with open(AUTHENTICATION_FPATH, 'w') as file:
				file.write(token.refresh_token + '\n')
				file.write(token.access_token + '\n')
		else:
			refresh_token = ''
			access_token = ''
			with open(AUTHENTICATION_FPATH, 'r') as file:
				refresh_token = file.readline().strip()
				access_token = file.readline().strip()

			token = gdata.gauth.OAuth2Token(
				client_id=CLIENT_ID,
				client_secret=CLIENT_SECRET,
				scope=SCOPE,
				user_agent=application_name,
				refresh_token=refresh_token,
				access_token=access_token)

		self.gd_client = gdata.spreadsheets.client.SpreadsheetsClient()
		token.authorize(self.gd_client)

	def _FindSpreadsheet(self):
		# Find the spreadsheet
		feed = self.gd_client.GetSpreadsheets()
		for f in feed.entry:
			if f.title.text=="PCMLogs":
				entry=f
				#break
		id_parts = entry.id.text.split('/')
		self.curr_key = id_parts[len(id_parts) - 1]
#		print self.curr_key

	def _FindWorksheet(self, name):
		# Get the list of worksheets
		feed = self.gd_client.GetWorksheets(self.curr_key)
		for f in feed.entry:
			if f.title.text==name:
				entry=f
				break
		id_parts = entry.id.text.split('/')
		self.curr_wksht_id = id_parts[len(id_parts) - 1]
#		print self.curr_wksht_id


	def _ReadWorksheet(self, logs):
		nlogs = 0
		feed = self.gd_client.GetListFeed(self.curr_key, self.curr_wksht_id)

		for f in feed.entry:
			#d = dict(map(lambda e: (e[0],e[1].text), f.custom.items()))
			d = f.to_dict()
			if d["num"]:
				num = int(d["num"])
				# encoding fix 2 lines
				#name = d["name"].encode("latin1")
				#action = d["action"].encode("latin1")
				name = d["name"]
				action = d["action"]
				xp = 0 if d["xp"] is None else int(d["xp"])
				if d["info"] is None:
					# encoding fix
					#info = "".encode("latin1")
					info = ""
				else:
					# encoding fix
					#info = d["info"].encode("latin1")
					info = d["info"]
				log = LogLine(num,name,time.time(),action,xp,info)
				student_logs = logs.get(num,[]) # get prev logs
				student_logs.append(log) # add the new one
				logs[num] = student_logs # update structure
				nlogs+= 1
		return logs, nlogs

	def Run(self, logs=None):
		if logs is None: logs = {}

		self._FindSpreadsheet()
		for name in ["JAJ", "Sandra", "Daniel", "HN"]:
			self._FindWorksheet(name)
			logs, nlogs = self._ReadWorksheet(logs)
			# print name, nlogs
		return logs
