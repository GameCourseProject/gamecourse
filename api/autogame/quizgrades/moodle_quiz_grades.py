# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# Python Script by
# @author:  Joao Rego
# @email-1:	jrego_41@hotmail.com
# @email-2:	joaorego41@gmail.com
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
"""
	Python Script to execute a .PHP script that will retrieve
	students quizz data from a Moodle course. The data will be
	stored in a file called 'moodlequizgrades_COURSENAME.txt'.
"""
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# IMPORTS
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
from time import mktime, strptime
from course_achievements_lib import read_student_list, find_student, Student, LogLine
import urllib.request, urllib.parse, urllib.error

# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# DEFINITIONS
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
## URL to call the PHP and PARAMETERS to be sent as arguments
SERVER = 'http://localhost/'
PATH = '_mywork/moodlequizgrades.php'
## ATENTION ---> This is the course SHORTNAME <--- ATENTION
COURSE = '\'pcm\''
QUIZGRADESURL = SERVER + PATH + '?course=' + COURSE

# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# FUNCTIONS
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
def read_moodle_quiz_grades (students, course, url) :
	"""
	Query Moodle database and retrieve all quiz grades of
	students enrolled in the input course
	"""
	
	# Return values:
	loglines = []			# List of LogLines containing regarding quiz grades
	nlines = 0				# Number of quiz grades obtained
	ignored_lines = 0		# Sometimes a quiz grade may be discarded (hope not!)
	unrec_students = []		# Some students may be invalid
	
	print ('\nReading quiz grades from course: ' + course)
	print ('PHP script URL: ' + url + '\n')

	# Query Moodle database to obtain student grades (from quizzes)
	response = urllib.request.urlopen(url)				# Execute PHP script to query database
	query_results = response.readlines()		# Acquire results from the response
	response.close()							# Response is no longer needed

	# File to save the results
	filename = "moodlequizgrades_" + course + ".txt"
	file = open(filename, "w")					# The previous file is always destroyed
	file.write(query_results[0])				# Write the header to the file

	# For each 'line' of the 'results' (except the heading, line: 0)
	for l in query_results[1:] :
		# If the line is non-empty
		if len(l.strip()) > 0 :
			nlines += 1 # Increase the of number of lines processed
			file.write(l)
			# Parse content of lines (separated by 'tab' ("\t"))
			try:
				# Retrieve: (UserID, UserName, QuizID, Grade, CourseID, CourseName) from the line
				(timestamp, quiz, studentname, grade, quizurl) = l.strip().split("\t")
			except:
				print ("Warning! Could not parse following line. Skipping!")
				print (l + "\n")
				ignored_lines += 1
				continue

			# Because that is what we get from the logs "now" (15/02/2018)
			studentname = str(studentname)
			timestamp = mktime(strptime(timestamp.strip(), "%d %B %Y, %H:%M %p"))
			s = find_student(studentname, students)
			
			# Check if the student exists (is valid)
			if not s :
				print ("Invalid student: " + studentname)
				# (case-TRUE) If the student doesn't exist
				# add the student to the unrecognized student list
				if not studentname in unrec_students :
					unrec_students.append(studentname)
			else :
				action = "quiz grade"
				grade = round(float(grade),2) # using 2 decimal values (float)
				# grade = int(round(float(grade)) # using 0 decimal values (int)
				loglines = loglines + [LogLine(s.num, studentname, timestamp, action, grade, quiz, quizurl)]

	# End of For-Loop
	file.close()
	print("\nDone!")
	if len(unrec_students) > 0 :
		print("Unrecognized Students:", unrec_students)
	print("Read %s lines" % (nlines))
	if ignored_lines:
		print("Could not parse %s lines (see above)" % ignored_lines)
	return loglines

# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
# MAIN (For Testing purposes)
# %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
def main():
	students = read_student_list()
	logs = {}

	## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	## Copy this into function definition:
	## read_log_files ('CourseAchievements.py')
	## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	quiz_loglines = read_moodle_quiz_grades(students, COURSE, QUIZGRADESURL)
	for line in quiz_loglines:
		logs[line.num] = logs.get(line.num,[])+[line]
	## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	print ("\n\nContent of \'logs\':")
	for key in list(logs.keys()) :
		print ("student-" +str(key) + ":")
		print (str(logs[key]))
		print ("\n")
	print ('\n')

if __name__ == '__main__':
	main()