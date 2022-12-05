# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# Python Script by
# @author:  Joao Rego
# @email-1:	jrego_41@hotmail.com
# @email-2:	joaorego41@gmail.com
# @created: 15 fev 2018
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# IMPORTS
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
import codecs
import json
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# CLASS
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
class Student:
	def __init__(self,num,name,email,campus):
		self.name=name
		self.num=num
		self.campus=campus
		self.email=email

	def __repr__(self):
		return "Student(%s,%s,%s,%s)" % (self.num, self.name, self.email, self.campus)

class LogLine:
	def __init__(self, num, name, timestamp, action, xp, info=None, url=None):
		self.num = num
		self.name = name
		self.timestamp = timestamp
		self.action = action
		self.xp = xp
		self.info = info
		self.url = url


	def __repr__(self):
		preinfo = self.info

		if type(self.info)==tuple:
			# decoding fix
			# ul = "("+codecs.decode(self.info[0],"latin1")+", "+codecs.decode(self.info[1],"latin1")+")"				
			ul = "("+self.info[0]+", "+self.info[1]+")"				
		elif type(self.info)==str:
			#ul = codecs.decode(self.info,"latin1")
			# decoding fix
			#ul = self.info.decode("latin1")				
			ul = self.info
		else:
			ul = self.info
		ul = str(ul)
		#ul = ul.replace('\x96','-')
		#ul = ul.replace('\xe9','_')
		self.info = ul
		tmpurl = self.url
		if tmpurl == None:
			self.url = "null"
		#print self.__dict__
		tmp = json.dumps(self.__dict__)
		self.info = preinfo
		self.url = tmpurl
		#print tmp
		return tmp
	#return "LogLine(%s, %s,%s,%s,%s,%s)" % (self.num, self.timestamp, self.action, self.xp, self.info, self.url)
	#try:
		if type(self.info)==tuple:
			# decoding fix
			#ul = "("+codecs.decode(self.info[0],"latin1")+", "+codecs.decode(self.info[1],"latin1")+")"				
			ul = "("+self.info[0]+", "+self.info[1]+")"				
		elif type(self.info)==str:
			#ul = codecs.decode(self.info,"latin1")
			# decoding fix
			#ul = self.info.decode("latin1")				
			ul = self.info
		else:
			ul = self.info
		ul = str(ul)
	#except:
		#print self.info, self.action
		return 'LogLine(%s, %s,%s,%s,%s,%s)' % (self.num, self.timestamp, self.action, self.xp, ul, self.url)


	def __str__(self):
		return self.__repr__()
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# FUNCTIONS
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
def find_student(name,students):
	for s in students:
		if s.name==name:
			return s

def read_student_list():
	
	students_list = codecs.open("students.txt","r")
	students = []
	
	for student in students_list :
		(num, name, email, campus) = student.strip().split(";")
		students.append(Student(num,name,email,campus))	
	
	return students
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# MAIN (for testing)
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
