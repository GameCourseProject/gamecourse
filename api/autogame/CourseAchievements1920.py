# -*- coding: latin-1 -*-

import os
import codecs
import datetime,time
import csv
try:
  from xml.etree import ElementTree
except ImportError:
  from elementtree import ElementTree
import gdata.spreadsheet.service
import gdata.service
import atom.service
import gdata.spreadsheet
import atom
import getopt
import sys
import string
import urllib
import json
import os.path
import gdata.gauth
import gdata.spreadsheets.client
import time
import pprint


__VERSION__="3.3"

LOGFILEDIR="logs"
QRURL="http://web.ist.utl.pt/daniel.j.goncalves/pcm/report.php"
ATTURL="https://classcheck.tk/tsv/course?s=f8c691b7fc14a0455386d4cb599958d3"
RATINGSURL="http://pcm.rnl.tecnico.ulisboa.pt/moodleVotes.php?c=5"
QUIZGRADESURL="http://pcm.rnl.tecnico.ulisboa.pt/moodlequizgrades.php?c=5"
PEERRATINGSURL="http://pcm.rnl.tecnico.ulisboa.pt/moodlePeerVotes.php?c=5"
#LOGFILEURL="htt://groups.ist.utl.pt/pcm/moodleLogs.php?t=1396264388&c=4"
LOGFILEURL="http://pcm.rnl.tecnico.ulisboa.pt/moodleLogs.php?c=5"
METADIR="meta"
SITEDIR = "../web/PCM-Achievements-site"
SITEDIR = "../web/PAS"
MOODLEBASEURL="https://pcm.rnl.tecnico.ulisboa.pt/moodle/mod/"
DEFAULT_MAXREPEATS=3
AVATARDIR="../web/AvatarWorld/pcm"

METADATA = { "all_lectures_alameda": 22,  #excl. invited lectures
             "all_lectures_tagus": 22,  #excl. invited lectures
             "all_lectures": 20,  #number of slide sets
             "invited_alameda": 0,
             "invited_tagus": 0,
             "all_labs": 10,
             "lab_max_grade": 450,
             "lab_excellence_threshold": 400,
             "quiz_max_grade": 750,
             "initial_bonus": 500,
             "max_xp": 20000,
             "max_bonus": 1000,
             "max_tree": 5000,
             "level_grade": 1000
            }

CLIENT_ID = '370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com'
CLIENT_SECRET = 'hC4zsuwH1fVIWi5k0C4zjOub'
SCOPE = 'https://spreadsheets.google.com/feeds/'
auth_file = 'google_auth.txt'
application_name = 'spreadsheets'



class memoized(object):
   """Decorator that caches a function's return value each time it is called.
   If called later with the same arguments, the cached value is returned, and
   not re-evaluated.
   """
   def __init__(self, func):
      self.func = func
      self.cache = {}
   def __call__(self, *args):
      try:
         return self.cache[args]
      except KeyError:
         value = self.func(*args)
         self.cache[args] = value
         return value
      except TypeError:
         # uncachable -- for instance, passing a list as an argument.
         # Better to not cache than to blow up entirely.
         return self.func(*args)
   def __repr__(self):
      """Return the function's docstring."""
      return self.func.__doc__
   def __get__(self, obj, objtype):
      """Support instance methods."""
      return functools.partial(self.__call__, obj)



class Achivement:
    def __init__(self,name, descr,criteria1, criteria2, criteria3, xp1, xp2, xp3, counted, posts):
        self.name = name
        self.description = descr
        self.criteria = [criteria1, criteria2, criteria3]
        if xp1=='':
            xxp1=0
        else:
            xxp1=int(xp1)
        if xp2=='':
            xxp2=0
        else:
            xxp2=int(xp2)
        if xp3=='':
            xxp3=0
        else:
            xxp3=int(xp3)
        self.xp = [abs(xxp1), abs(xxp2), abs(xxp3)]
        self.extra = [xxp1<0, xxp2<0, xxp3<0]
        self.counted = counted
        self.post_based = posts
        if self.xp[0]==0:
            self._unrewarder = True
        else:
            self._unrewarder = False
        if xp3!='':
            self._top_level=3
        elif xp2!='':
            self._top_level=2
        else:
            self._top_level=1

    def top_level(self):
        return self._top_level

    def unrewarded(self):
         return self._unrewarder

    def has_extra(self):
        return self.extra[0] or self.extra[1] or self.extra[2]


class Award:
    def __init__(self,student,achievement,level,xp,badge,timestamp, info = None):
        self.student=student           # student number
        self.achievement=achievement   # achievement NAME
        self.level=level               # achievement level (0 if not applicable)
        self.xp=xp                     # awarded XP
        self.badge=badge               # true or false, depending on whether this is a badge award or just XP (quizes, etc.)
        self.timestamp=timestamp       # when was this awarded
        self.info = info               # how was this obtained? (lecture num, url, etc)
    def __str__(self):
        if self.badge:
            return str(self.timestamp)+";"+self.student+";"+self.achievement+";"+str(self.level)+";"+str(self.info)
        else:
            return str(self.timestamp)+";"+self.student+";"+self.achievement+";"+str(self.xp)+";"+str(self.info)
    def __repr__(self):
        return str(self)
    def __eq__(self, other):
        #if self.student=="64770" and other.student=="64770" and self.achievement=="Grade from Quiz": print self, other
        if self.student==other.student and \
           self.achievement == other.achievement and \
           self.level == other.level and \
           self.badge == other.badge: #and \
            if not self.badge:
                if self.info!=other.info:
                    return False
#           ((not self.badge and self.info == other.info) or False): #crazy dangerous hack!!!!!!!!!!!!11
            if self.xp == other.xp:
                #if self.student=="64770" and other.student=="64770" and self.achievement=="Grade from Quiz": print "2", self.info, other.info
                return 2
            else:
                #if self.student=="64770" and other.student=="64770" and self.achievement=="Grade from Quiz": print "1"
                return 1
        else:
            #if self.student=="64770" and other.student=="64770" and self.achievement=="Grade from Quiz": print False
            return False



_debug = False

class PreCondition:
    def __init__(self,nodes):
        self.nodes=nodes
    def __str__(self):
        return "PC(%s)" % (self.nodes)
    def __repr__(self):
        return str(self)
    def Printable(self):
        tmp=""
        if self.nodes:
            tmp=" + ".join(self.nodes)
        return tmp
    def Satisfied(self,prevnodes):
        for n in self.nodes:
            if not substringin(n,prevnodes):
                return False
        return True



class TreeAward:
    def __init__(self,name,level,pcs,color,xp):
        self.name = name
        self.PCs=pcs
        self.level=level
        self.color=color
        self.xp=xp
    def __str__(self):
        return "TreeAward("+self.name+", "+str(self.level)+", "+str(self.PCs)+")"
    def __repr__(self):
        return str(self)
    def Satisfied(self,prevnodes):
        if len(self.PCs)==0:
            return True
        for pc in self.PCs:
            if _debug:
                print pc,pc.Satisfied(prevnodes),prevnodes
            if pc.Satisfied(prevnodes):
                return True
        return False



class Student:
    def __init__(self,num,name,email,campus):
        self.name=name
        self.num=num
        self.campus=campus
        self.email=email

    def __repr__(self):
        return u"Student(%s,%s,%s,%s)" % (self.num, self.name, self.email, self.campus)



class LogLine:
    def __init__(self, num, name, timestamp, action, xp, info=None, url=None):
        self.num = num
        self.name = name
        self.timestamp = timestamp
        self.action = action
        self.xp = xp
        self.info = info
        self.url = url


    def __eq__(self,other):
        return self.num==other.num and self.name==other.name and self.action==other.action and self.info==other.info

    def __repr__(self):
            preinfo = self.info

            if type(self.info)==tuple:
                ul = "("+codecs.decode(self.info[0],"latin1")+", "+codecs.decode(self.info[1],"latin1")+")"
            elif type(self.info)==str:
                #ul = codecs.decode(self.info,"latin1")
                ul = self.info.decode("latin1")
            else:
                ul = self.info
            #ul = unicode(ul)
            #ul = ul.replace(u'\x96','-')
            #ul = ul.replace(u'\xe9','_')
            self.info = str(ul)
            tmpurl = self.url
            if tmpurl == None:
                self.url = "null"
            #print self.__dict__
            tmp = json.dumps(self.__dict__, encoding="latin1")
            self.info = preinfo
            self.url = tmpurl
            #print tmp
            return tmp
        #return "LogLine(%s, %s,%s,%s,%s,%s)" % (self.num, self.timestamp, self.action, self.xp, self.info, self.url)
        #try:
            if type(self.info)==tuple:
                ul = "("+codecs.decode(self.info[0],"latin1")+", "+codecs.decode(self.info[1],"latin1")+")"
            elif type(self.info)==str:
                #ul = codecs.decode(self.info,"latin1")
                ul = self.info.decode("latin1")
            else:
                ul = self.info
            ul = unicode(ul)
        #except:
            #print self.info, self.action
            return u'LogLine(%s, %s,%s,%s,%s,%s)' % (self.num, self.timestamp, self.action, self.xp, ul, self.url)


    def __str__(self):
        return self.__repr__()




###############################################################################
###############################################################################
##########################    Computing Achievements  #########################
###############################################################################
###############################################################################




def PostMaster(logs, student):
    views=FilterLogs(logs,student,action="forum add post") + \
          FilterLogs(logs,student,action="forum add discussion")
    return award_by_reiteration(len(views),20,30,50), len(views), views


def Talkative(logs, student):
    views=FilterLogs(logs,student,action="participated in Lecture")
    views=views+FilterLogs(logs,student,action="participated in Invited Lecture")
    return award_by_reiteration(len(views),2,6,12), len(views), views


def BookMaster(logs, student):
    lecture_names=[]
    for i in range(1,METADATA["all_lectures"]+10):
        lecture_names.append("Lecture "+str(i)+" Slides")
        lecture_names.append("Lecture "+str(i)+" - Slides")
        lecture_names.append("Lecture "+str(i)+"&"+str(i+1)+" Slides")
        lecture_names.append("Lecture "+str(i)+"&"+str(i+1)+" - Slides")
    total=0
    views=[]
    for n in lecture_names:
        if len(FilterLogs(logs,student,action="resource view", info=n))>0:
            total+=1
            tmp=FilterLogs(logs,student,action="resource view", info=n)
            for t in tmp:
                t.info="L"+t.info.split(" ")[1]
                if t.info not in [v.info for v in views]:
                    views=views+[t]
    return award_by_reiteration(total,int(METADATA["all_lectures"]*0.5),int(METADATA["all_lectures"]*0.75),METADATA["all_lectures"]), total, views



def ProficientToolUser(logs, student):
    views,values, total = FilterGrades(logs,student,"Proficient Tool User")
    return award_by_reiteration(total,4,8,14), total, views


def Apprentice(logs, student):
    views,values, total = FilterGrades(logs,student,forum="Questions",max_repeats=None)
    views2,values2, total2 = FilterGrades(logs,student,forum="Labs Forum",max_repeats=None)
#    views2a,values2a, total2a = FilterGrades(logs,student,"Lab Challenge",forum="Archive/Showcase",max_repeats=None)
    views3,values3, total3 = FilterGrades(logs,student,"Proficient Tool User",forum="Labs Forum",max_repeats=None)
    views_aux=[]
    values_aux=[]
#    if student.num=="76606":
#        print len(views),len(views2),len(views3)
#        for x in views2: print x
#        print "x"
#        for x in views3: print x
    for v in range(0,len(views2)):
        if not views2[v] in views3:
            views_aux.append(views2[v])
            values_aux.append(values2[v])
#    if student.num=="76606":
#        print "A\n",len(views_aux)
#        for x in views_aux: print x.url
    views+=views_aux
    values+=values_aux
    total=total+total2-total3
    return award_by_reiteration(total,4,8,12), total, views


def AttentiveStudent(logs, student):
    views,values, total = FilterGrades(logs,student,forum = "Bugs Forum",max_repeats=None)
    return award_by_reiteration(total,4,8,12), total, views


def Suggestive(logs, student):
    views,values, total = FilterGrades(logs,student,"Suggestions", forum = "Participation Forum",max_repeats=None)
    return award_by_reiteration(total,4,8,12), total, views



def ClassAnnotator(logs, student):
    views, values, total = FilterGrades(logs,student,"Class Annotator",max_repeats=None) # JAJ Prob: aluno com cinco posts
    return award_by_reiteration(total,4,8,12), total, views


def Squire(logs, student):
    views,values, total = FilterGrades(logs,student,"Tutorials",forum="Participation Forum",max_repeats=None)
    return award_by_reiteration(total,4,10,16), total, views


def ChallengeroftheUnknown(logs, student):
    # manually given in the spreadsheet
    views=FilterLogs(logs,student,action="quest")

    # this next bit is just to give us a list of students to paste into the spreadsheet
    # we could automate this. Next year, perhaps!
    views2,values2, total2 = FilterGrades(logs,student,"Quest",forum = "Participation Forum",max_repeats=None)
    if len(views2)>0:
        f=codecs.open("quest_participants.txt",encoding="utf8",mode="a")
        f.write(student.num+";"+student.name+";"+str(len(views2))+"\n")
        f.close()

    # here, finally, we get *all* quest posts, for all quests
    views2,values2, total2 = FilterGrades(logs,student,"Quest",forum = "Participation Forum",max_repeats=None)
    if len(views)>0:
#        print "COTU",student, views
        return len(views), len(views2), views2
    else:
#        print "COTU: Nope!"
        return False, len(views2), views2
#    return award_by_reiteration(len(views),1,2,3), len(views), views



# TODO!
def LabMaster(logs, student):
    views=FilterLogs(logs,student,action="lab grade")
    count = 0
    for v in views:
        if int(v.info)<=5:
            if int(v.xp)>=110: #hack
                count+=1
        else:
            try:
                if int(v.xp)>=METADATA["lab_excellence_threshold"]:
                    count+=1
            except:
                print "AAAAA",v
    return award_by_reiteration(count,4,6,10), count, None


def ReplierExtraordinaire(logs, student):
    views=FilterLogs(logs,student,action="replied to questionnaires")
    return award_by_reiteration(len(views),1,2,3), len(views), views


def Focused(logs, student):
    views=FilterLogs(logs,student,action="participated in focus groups")
    if len(views)>0:
      return 1, False, views
    else:
      return False, False, views




def WildImagination(logs, student):
    views=FilterLogs(logs,student,action="suggested presentation subject")
    if len(views)>0:
        return 1, False, views
    else:
        return False, False, views


def num_fours(vals):
    res=0
    for v in vals:
        if v==4:
            res+=1
    return res



def Artist(logs, student):
    views,values, total = FilterGrades(logs,student,max_repeats=None,grades=[4,5])
    return award_by_reiteration(len(views),4,6,12), len(views), views


def HallofFame(logs, student):
    views=FilterLogs(logs,student,action="hall of fame")
    return award_by_reiteration(len(views),1,2,3), len(views), views


def RightonTime(logs, student):
    views=FilterLogs(logs,student,action="attended lecture")
    views1=FilterLogs(logs,student,action="attended lecture (late)")
    if student.campus=="A":
        total=METADATA["all_lectures_alameda"]+METADATA["invited_alameda"]
    else:
        total=METADATA["all_lectures_tagus"]+METADATA["invited_tagus"]
    num=len(views)
    #total=total-2 # HACK FOR THIS YEAR!!! SEMANA INFORMATICA
#    return award_by_reiteration(len(views),13,17,total), len(views), views
    return award_by_reiteration(len(views),int(total*0.5),int(total*0.75),total), len(views), views

def AmphitheatreLover(logs, student):
    views=FilterLogs(logs,student,action="attended lecture")+ \
          FilterLogs(logs,student,action="attended lecture (late)")
    num=len(views)
# Invited lectures count! We're doing videoconferencing!
    if student.campus=="A":
        total=METADATA["all_lectures_alameda"]+METADATA["invited_alameda"]
    else:
        total=METADATA["all_lectures_tagus"]+METADATA["invited_tagus"]
    #total=-2 # HACK FOR THIS YEAR!!! INVITED LECTURES 2014 (disse na aula te�rica que havia duas de toler�ncia
    return award_by_reiteration(len(views),int(total*0.5),int(total*0.75),total), len(views), views


def LabLover(logs, student):
    views=FilterLogs(logs,student,action="attended lab")
#    if student.num=="86463": print views
    total=METADATA["all_labs"]
    return award_by_reiteration(len(views),int(total*0.6),int(total*0.8),total), len(views), views


# Old version, manual
#def StudentWorker(logs, student):
#    # regular classes only. invited don't count
#    views=FilterLogs(logs,student,action="student worker")
#    total=METADATA["all_lectures"]
#    return award_by_reiteration(len(views),int(total*0.5),int(total*0.75),total), len(views), views


# New version, automatic!
def StudentWorker(logs, student):
    views,values, total_grades = FilterGrades(logs,student,"Working Student")
    total=METADATA["all_lectures"]
    return award_by_reiteration(total_grades,int(total*0.5),int(total*0.75),total), total_grades, views




def PopularChoiceAward(logs, student):
    views=FilterLogs(logs,student,action="popular choice award (presentation)")
    if len(views)>0:
        pos=int(views[0].info)
        level=4-pos
        return min(max(level,1),3), False, None
    else:
        return False, False, None


def HollywoodWannabe(logs, student):
    views=FilterLogs(logs,student,action="great video")
    if len(views)>0:
        return min(3,int(views[0].info)), False, None
    else:
        return False, False, None


def PresentationZenMaster(logs, student):
    if check_existance(logs, student, "document describing presentation"):
        return 1, False, None
    else:
        return False, False, None

def GuildWarrior(logs, student):
    views=FilterLogs(logs,student,action="guild warrior")
    return award_by_reiteration(len(views),1,2,5), len(views), views


def GuildMaster(logs, student):
    views=FilterLogs(logs,student,action="guild master")
    return award_by_reiteration(len(views),1,2,5), len(views), views


def GoldenStar(logs, student):
    views=FilterLogs(logs,student,action="golden star award")
    if len(views)>0:
        return min(3,len(views)), len(views), views
    else:
        return False, False, None


def LabKing(logs, student):
    if check_existance(logs, student, "lab king"):
        return 1, False, None
    else:
        return False, False, None

def PresentationKing(logs, student):
    if check_existance(logs, student, "presentation king"):
        return 1, False, None
    else:
        return False, False, None


def CourseEmperor(logs, student):
    if check_existance(logs, student, "course emperor"):
        return 1, False, None
    else:
        return False, False, None


def TreeClimber(logs,student):
    tree = read_tree()

    tree_awards, vals, totals=FilterGrades(logs,student,forum="Skill Tree")

    maxlevel = 0
    for ta in tree_awards:
        for l in tree:
            if l.name in ta.info[1] and l.Satisfied([a.info[1] for a in tree_awards]):
                if l.level>maxlevel:
                    maxlevel = l.level
    return max(0,maxlevel-1), totals, tree_awards




def QuizMaster(logs, student):
    views=FilterLogs(logs,student,action="quiz grade", xp=str(METADATA["quiz_max_grade"]))
    total=len(views)
    return award_by_reiteration(len(views),4,6,8), len(views), views


def QuizKing(logs, student):
    if check_existance(logs, student, "quiz king"):
        return 1, False, None
    else:
        return False, False, None



##def Archivist(logs, student):
##    views=FilterLogs(logs,student,action="archivist")
##    return award_by_reiteration(len(views),3,5,10), len(views), views
##
##def Proactive(logs, student):
##    views=FilterLogs(logs,student,action="was first to answer to a challenge (theo/lab)")
##    return award_by_reiteration(len(views),1,4,10), len(views), views

##def ExamKing(logs, student):
##    if check_existance(logs, student, "exam king"):
##        return 1, False, None
##    else:
##        return False, False, None



##def GoodHost(logs, student):
##    views=FilterLogs(logs,student,action="student worker")
##    if student.campus=="A":
##        self=METADATA["invited_alameda"]
##        other=METADATA["invited_tagus"]
##    else:
##        other=METADATA["invited_alameda"]
##        self=METADATA["invited_tagus"]
##    c1 = len(FilterLogs(logs,student,action="attended invited lecture (own campus)"))
##    c2 = len(FilterLogs(logs,student,action="attended invited lecture (other campus)"))
##    if c1>=self:
##        if c2>=other:
##            return 3, False, None
##        elif c2 == 1:
##            return 2, False, None
##        else:
##            return 1, False, None
##    else:
##        return False, False, None




##def BugSquasher(logs, student):
##    views=FilterLogs(logs,student,action="found bug in PCM Media Mixer")
##    if len(views)>0:
##        return min(3,len(views)), len(views), views
##    else:
##        return False, len(views), []


##
##def RisetotheChallenge(logs, student):
##    views,values, total = FilterGrades(logs,student,"Theoretical Challenge")
##    return award_by_reiteration(total,4,12,24), total, views
##
##
##def Blacksmith(logs, student):
##    views,values, total = FilterGrades(logs,student,"Avatar World - Objects",max_repeats=None)
##    return award_by_reiteration(total,4,8,16), total, views
##
##
##def MasterBuilder(logs, student):
##    views,values, total = FilterGrades(logs,student,"AvatarWorld - Buildings",max_repeats=None)
##    return award_by_reiteration(total,4,8,16), total, views
##




#################### GENERIC AWARD CHECKING FUNCTIONS ######################

def award_by_reiteration(count,l1,l2,l3):
    if count>=l3:
        return 3
    elif count>=l2:
        return 2
    elif count>=l1:
        return 1
    else:
        return False

def check_existance(logs, name, tag):
    return len(FilterLogs(logs,name,action=tag))>0


def FilterLogs(logs, student, action=None, info=None, xp=None):
    if not (type(student)==int or type(student)==unicode or type(student)==str) :
        student=student.num
    res=[]
    if not student in logs:
        return []
    for l in logs[student]:
        ts = l.timestamp
        a = l.action
        i = l.info
        x = l.xp

        if action:
            if info:
                if type(info)==list:
                    if a==action and i in info:
                        if xp and x==xp:
                          res.append(l)
                        else:
                          res.append(l)
                else:
                    if a==action and i == info:
                        if xp and x==xp:
                          res.append(l)
                        else:
                          res.append(l)
            elif xp:
                if a==action and x==xp:
                    res.append(l)
            elif a == action:
                res.append(l)
        elif info:
            if type(info)==list:
                if i in info:
                    if xp and x==xp:
                       res.append(l)
                    else:
                        res.append(l)
            else:
                if i == info:
                    if xp and x==xp:
                        res.append(l)
                    else:
                        res.append(l)
        elif xp:
            if x==xp:
                res.append(l)
        else:
            res.append(l)
    return res



def FilterLogsGlobal(logs, action = None, info = None):
    res=[]
    for l in logs:
        res+=FilterLogs(logs,l,action,info)
    return res




def verify_grade_components(logs, student):
    res = []
    views=FilterLogs(logs,student,action="quiz grade")
    c = 1
    for v in views:
        res = res + [Award(student.num,"Grade from Quiz",0,int(v.xp),False,time.time(),v.info[-1:])]
        c+=1
    views=FilterLogs(logs,student,action="lab grade")
    c = 1
    for v in views:
        res = res + [Award(student.num,"Grade from Lab",0,int(v.xp),False,time.time(),v.info)]
        c+=1
    views=FilterLogs(logs,student,action="presentation grade")
    for v in views:
        res = res + [Award(student.num,"Grade from Presentation",0,int(v.xp),False,time.time(),"")]
    views=FilterLogs(logs,student,action="initial bonus")
    if len(views)>0:
        res = res + [Award(student.num,"Initial Bonus",0,METADATA["initial_bonus"],False,time.time(),"")]
##    views=FilterLogs(logs,student,action="exam grade")
##    for v in views:
##        res = res + [Award(student.num,"Grade from Exam",0,int(v.xp),False,time.time(),v.info)]
##
## DEPRECATED: Once upon a time this was manual
##    views=FilterLogs(logs,student,action="tree award")
##    for v in views:
##        res = res + [Award(student.num,"Grade from Skill Tree: "+v.info,0,int(v.xp),False,time.time(),v.info)]
    return res




def verify_achievements(logs, students, achievement_list):
    res={}
    indicators = {}

    print "\n\nChecking achievements",
    for s in students:
        tmp=[]
        print ".",
#        print "Verifying achievements for", unicode.encode(s.name,"latin1")," - ",
        for a in achievement_list:
            fn_name=a.name.replace(" ","")
            try:
#                print fn_name
                tmp, val, lines=eval("%s(logs, s)" % fn_name)
                val=unicode(val)
            except NameError:
                tmp=None
            if tmp:
                res[s.num]=res.get(s.num,[])+[Award(s.num,a.name,tmp,a.xp[tmp-1],True,time.time(),val)]
                                                   #student,achievement,level,xp,badge,timestamp, info...
            if val or lines:
                if not s.num in indicators:
                    indicators[s.num]={}
                indicators[s.num][a.name]=(val,lines)
#                if a.name=="Quiz Master":
#                  print indicators[s.num][a.name]
        res[s.num]=res.get(s.num,[])+verify_grade_components(logs, s)
#        print len(res[s.num])
    return res, indicators



# res = res + [LogLine(s.num, name,0, "graded post", grade, threadurl)]


def FilterGrades(logs,student,crit=None,forum=None,max_repeats=DEFAULT_MAXREPEATS,grades=None):
    if not (type(student)==int or type(student)==unicode or type(student)==str) :
        student=student.num
    views=[]
    vals=[]
    if not student in logs:
        return views,vals,0
    for l in logs[student]:
        a = l.action
        x = l.xp
        u = l.url
        if a == "graded post":
            f = l.info[0]
            t = l.info[1]
            if crit and forum:
                if crit in t and forum in f:
                    views.append(l)
            elif crit:
                if crit in t:
                    views.append(l)
            elif forum:
                if forum in f:
                    views.append(l)
            else:  #no creiteria specified? Here goes everything!
                views.append(l)
    if max_repeats:
        tmp={}
        newviews=[]
        for l in views:
            tmp[l.info]=tmp.get(l.info,[])+[l]
        lst=tmp.values()
        for l in lst:
            if len(l)>max_repeats:
                newviews=newviews+l[:max_repeats]
            else:
                newviews=newviews+l
        views=newviews
    if not grades:
        vals = [int(l.xp) for l in views]
    else:
        tmpviews=[]
        vals=[]
        for v in views:
            if int(v.xp) in grades:
                tmpviews.append(v)
                vals.append(int(v.xp))
        views=tmpviews
    return views, vals, sum(vals)





###############################################################################
###############################################################################
###################### Reading the required information...  ###################
###############################################################################
###############################################################################




class PCMSpreadsheetParser:
  def __init__(self):
    self._Authorize()
    self.curr_key = ''
    self.curr_wksht_id = ''
  def _Authorize(self):
    token = None
    if not(os.path.exists(auth_file)):
      token = gdata.gauth.OAuth2Token(
        client_id=CLIENT_ID,
        client_secret=CLIENT_SECRET,
        scope=SCOPE,
        user_agent=application_name);

      url = token.generate_authorize_url()
      print 'Use this url to authorize the application: \n'
      print url;
      code = raw_input('What is the verification code? ').strip()
      token.get_access_token(code)

      with open(auth_file, 'w') as file:
        file.write(token.refresh_token + '\n')
        file.write(token.access_token + '\n')
    else:
      refresh_token = ''
      access_token = ''
      with open(auth_file, 'r') as file:
        refresh_token = file.readline().strip()
        access_token = file.readline().strip()

      token = gdata.gauth.OAuth2Token(
        client_id=CLIENT_ID,
        client_secret=CLIENT_SECRET,
        scope=SCOPE,
        user_agent=application_name,
        refresh_token=refresh_token,
        access_token=access_token);

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
#    print self.curr_key

  def _FindWorksheet(self, name):
    # Get the list of worksheets
    feed = self.gd_client.GetWorksheets(self.curr_key)
    for f in feed.entry:
        if f.title.text==name:
            entry=f
            break
    id_parts = entry.id.text.split('/')
    self.curr_wksht_id = id_parts[len(id_parts) - 1]
#    print self.curr_wksht_id


  def _ReadWorksheet(self):
    res=[]
    feed = self.gd_client.GetListFeed(self.curr_key, self.curr_wksht_id)
    for f in feed.entry:
        d = f.to_dict() #d = dict(map(lambda e: (e[0],e[1].text), f.custom.items()))
        if d["num"]:
            res = res+[ LogLine(d["num"],d["name"].encode("latin1"),time.time(), d["action"],
                                d["xp"], d["info"])]
    return res

  def Run(self):
    res=[]
    self._FindSpreadsheet()
    for name in ["Daniel", "DanielSL", "Hugo", "Joao", "Tomas"]:
      self._FindWorksheet(name)
      tmp = self._ReadWorksheet()
      print name,len(tmp)
      res+=tmp
    return res


import pprint

def read_QR_logs():
    res = []

    print 'Reading QRCodes from \"'+QRURL+'"'
    f=urllib.urlopen(QRURL)
    for l in f.readlines():
        (num,name,campus,action,info) = l.strip().split(";")
        timestamp=time.time()
        line = LogLine(num, name,timestamp, action, 0, info)
        res.append(line)
    return res



def readIDtoNum():
    f = open("meta/idtonum.csv")
    res = {}
    for l in f.readlines():
        id,num = l.strip().split(",")
        res[id]=num
    f.close()
    return res


def read_attendance_logs():
    res = []

    print 'Reading Attendance from \"'+ATTURL+'"'
    idtonum = readIDtoNum()
    f=urllib.urlopen(ATTURL)
    for l in f.readlines():
#        print l.strip().split("\t")
        (profid,num,studentid,name,action,att_type,info,courseid) = l.strip().split("\t")
        timestamp=time.time()
        line = LogLine(idtonum[studentid], name,timestamp, action, 0, info)
        if not line in res:
          res.append(line)
        else:
          print "Repeated Attendance!",line
    return res



def read_ratings_logs(students, peer=False):
    res=[]
    nlines=0
    ignored_lines=0
    unrec_students=[]


    if peer:
      url = PEERRATINGSURL
    else:
      url = RATINGSURL
    print 'Reading ratings logs from \"'+url+'"'
    f=urllib.urlopen(url)
    #f=open("meta/localvotes.txt")
    for l in f.readlines()[1:]:
##    for l in ["PCM\tDaniel Gon�alves\tQuestions\tThe rain in spain\t3\txpto.html",
##              "PCM\tDaniel Gon�alves\tLabs Forum\tThe rain in the labs\t3\txpto.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tLab Challenge #5\t3\txpto.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tTheoretical Challenge #1\t4\txpto1a.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tTheoretical Challenge #1\t4\txpto1b.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tTheoretical Challenge #2\t2\txpto2a.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tTheoretical Challenge #2\t3\txpto2b.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tTheoretical Challenge #2\t1\txpto2c.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tTheoretical Challenge #2\t2\txpto2d.html",
##              "PCM\tDaniel Gon�alves\tParticipation\tTheoretical Challenge #2\t2\txpto2e.html",
##              "PCM\tDaniel Gon�alves\tSkill Tree\treMIDI\t2\txpto2e.html",
##              "PCM\tDaniel Gon�alves\tSkill Tree\teBook\t2\txpto2e.html",
##              "PCM\tDaniel Gon�alves\tSkill Tree\tAlien Invasion\t2\txpto2e.html",
##              "PCM\tDaniel Gon�alves\tSkill Tree\tPodcast\t2\txpto2e.html",
##              "PCM\tDaniel Gon�alves\tSkill Tree\tCourse Logo\t2\txpto2e.html",
##              "PCM\tDaniel Gon�alves\tSkill Tree\treTrailer\t2\txpto2e.html",
##              "PCM\tGabriel Barata\tParticipation\tTheoretical Challenge #4\t2\txpto.html"]:
      if len(l.strip())>0:
          nlines+=1
          try:
              (timestamp,course, name, forum, thread, grade, url) = l.strip().split("\t")
              if peer:
                url=MOODLEBASEURL+"peerforum/"+url
              else:
                url=MOODLEBASEURL+"forum/"+url
          except:
              print "Warning! Could not parse following line. Skipping!"
              print l,"\n"
              ignored_lines+=1
              continue
          name=unicode(name,"utf8")  # because that is what we get from the logs now
          timestamp=time.mktime(time.strptime(timestamp.strip(),"%d %B %Y, %H:%M %p"))
          s = find_student(name,students)
          if not s:
              if not name in unrec_students:
                  unrec_students.append(name)
          else:
              res = res + [LogLine(s.num, name,timestamp, "graded post", grade, (forum,thread), url)]
    print "\nDone!"
    if len(unrec_students)>0:
        print "Unrecognized Students:", unrec_students
    print "Read %s lines" % (nlines)
    if ignored_lines:
        print "Could not parse %s lines (see above)" % ignored_lines
    return res




def read_moodle_logs(students):
    logs={}
    nlines=0
    ignored_lines=0
    unrec_students=[]
    maxts = 0.0
#t=1396264388

    try:
        print "  Reading from moodlelogs.txt"
        f=open("logs/moodlelogs.txt")
        all_lines = f.readlines()[1:]
        f.close()
    except:
        all_lines = []
    logs,unrec_students,ignored_lines,nlines,maxts = parse_log_lines(students,all_lines)
    print "  Locally, found %s lines" % nlines
    if maxts == 0:
        maxts = 1396264388 # sometime in 2014
    try:
        print "  Reading from %s&t=%s" % (LOGFILEURL, maxts+1)
        f=urllib.urlopen("%s&t=%s" %(LOGFILEURL, maxts+1))
        all_lines = f.readlines()[1:]
        f.close()
        ff = open("logs/moodlelogs.txt","a")
        ff.write("".join(all_lines))
        ff.close()
    except:
        all_lines = []
    l,u,i,n,m = parse_log_lines(students,all_lines)
    print "  Remotely, found %s lines" % n
    for s in u:
        if not s in unrec_students:
            unrec_students.append(s)
    ignored_lines = ignored_lines + i
    nlines = nlines + n
    for s in l.keys():
        logs[s]=logs.get(s,[])+l[s]
    return logs,unrec_students,ignored_lines,nlines


def parse_log_lines(students,all_lines):
    logs={}
    nlines=0
    ignored_lines=0
    unrec_students=[]

    maxts = 0.0

    for l in all_lines:
       if len(l.strip())>0:
          nlines+=1
          try:
              (course, timestamp, ip, name, action, information, url) = l.strip().split("\t")
              url=MOODLEBASEURL+action.split(" ")[0]+"/"+url
          except:
              try:
                  (course, timestamp, ip, name, action) = l.strip().split("\t")
                  information=""
                  url=""
              except:
                  print "Warning! Could not parse following line. Skipping!"
                  print l,"\n"
                  ignored_lines+=1
                  continue
#          print timestamp.strip(),
          timestamp=time.mktime(time.strptime(timestamp.strip(),"%d %B %Y, %I:%M:%S %p"))
#          print timestamp, maxts
          if timestamp > maxts: maxts = timestamp
          name=unicode(name,"utf8")  # because that is what we get from the logs now
          s = find_student(name,students)
          if not s:
              if not name in unrec_students:
                  unrec_students.append(name)
          else:
              logs[s.num]=logs.get(s.num,[])+[LogLine(s.num, name,timestamp, action, 0, information, url)]
    return logs,unrec_students,ignored_lines,nlines, maxts



def read_csv_logs():
    res=[]
    #for d in csv.DictReader(codecs.open(os.path.join(METADIR,"manual_logs.csv"),encoding="latin1")):
    for f in ["djvg.csv","jaj.csv","rui.csv"]:
        first = True
        for d in csv.DictReader(open(os.path.join(METADIR,f))):
            if not first: #len(d)>0:
                #print d
                res = res+[ LogLine(d["Num"],d["Name"],time.time(), d["Action"],d["XP"], d["Info"])]
            else:
                first = False
    return res


def read_quiz_grades (students):
	# Description:
	"Query Moodle database and retrieve all quiz grades of students enrolled in the input course"

	# Return values:
	loglines = []			# List of LogLines containing regarding quiz grades
	nlines = 0				# Number of quiz grades obtained
	ignored_lines = 0		# Sometimes a quiz grade may be discarded (hope not!)
	unrec_students = []		# Some students may be invalid

        url = QUIZGRADESURL
	print ('PHP script URL: ' + url + '\n')

	# Query Moodle database to obtain student grades (from quizzes)
	response = urllib.urlopen(url)				# Execute PHP script to query database
	query_results = response.readlines()		# Acquire results from the response
	response.close()							# Response is no longer needed

	# File to save the results
	filename = "moodlequizgrades" + ".txt"
	file = open(filename, "w")					# The previous file is always destroyed
	file.write(query_results[0])				# Write the header to the file

	# For each 'line' of the 'results' (except the heading, line: 0)
	for l in query_results[1:] :
		# If the line is non-empty
		if len(l.strip()) > 0 :
			# Increase the of number of lines processed
			nlines += 1
			file.write(l)
                        #l.decode("utf8")

			# Parse content of lines (separated by 'tab' ("\t"))
			try:
				# Retrieve: (UserID, UserName, QuizID, Grade, CourseID, CourseName) from the line
				(timestamp, course, quiz, studentname, grade, quizurl) = l.strip().split("\t")
			except:
				print ("Warning! Could not parse following line. Skipping!")
				print (l + "\n")
				ignored_lines += 1
				continue

			studentname = unicode(studentname, "utf8")			# Because that is what we get from the logs "now" (15/02/2018)
			timestamp = time.mktime(time.strptime(timestamp.strip(), "%d %B %Y, %H:%M %p"))
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
                                grade = int(round(float(grade),0))
                                if not "dry run" in quiz.lower():
                                    if not "quiz 9" in quiz.lower():
    			                loglines = loglines + [LogLine(s.num, studentname, timestamp, action, str(grade), quiz, quizurl)]

	# End of For-Loop
	file.close()
	print "\nDone!"
	if len(unrec_students) > 0 :
		print "Unrecognized Students:", unrec_students
	print "Read %s lines" % (nlines)
	if ignored_lines:
		print "Could not parse %s lines (see above)" % ignored_lines
	return loglines



def read_log_files(students):
    print 'Reading logfiles from \"'+LOGFILEURL+'"'
    logs,unrec_students,ignored_lines,nlines = read_moodle_logs(students)
    print "\nDone!"
    if len(unrec_students)>0:
        print "Unrecognized Students:", unrec_students
    print "Read %s lines, %s students" % (nlines,len(logs.keys()))
    if ignored_lines:
        print "Could not parse %s lines (see above)" % ignored_lines
    print "\nReading Manual logs"
    parser = PCMSpreadsheetParser() #"pcmmeic", "batterystaplehorse")
    manual_lines = parser.Run()
   #manual_lines = read_csv_logs()
    for line in manual_lines:
#        if line.num=="86463": print line
        logs[line.num]=logs.get(line.num,[])+[line]
    print "\nReading QR logs"
    QRLines = read_QR_logs()
    for line in QRLines:
        logs[line.num]=logs.get(line.num,[])+[line]
    print "\nReading Attendance logs"
    ATTLines = read_attendance_logs()
    for line in ATTLines:
        logs[line.num]=logs.get(line.num,[])+[line]
    print "\nReading post ratings"
    PostRatingsLines = read_ratings_logs(students)
    for line in PostRatingsLines:
        logs[line.num]=logs.get(line.num,[])+[line]
    print "\nReading quiz grades"
    QuizGradesLines = read_quiz_grades(students)
    att_exceptions = read_attendance_exceptions()
    bad_att = read_bad_attendances()
    for line in QuizGradesLines:
        if line.name in att_exceptions[line.info.split(" ")[-1]] or attended_lecture(ATTLines+manual_lines,line.num,att_exceptions[line.info.split(" ")[-1]][0]):
            if not line.name in bad_att[line.info.split(" ")[-1]]:
                logs[line.num]=logs.get(line.num,[])+[line]
            else:
                print "INVALID QUIZ "+line.info.split(" ")[-1]+" grade for student "+line.name+". Was here but not really..."
        else:
            print "INVALID QUIZ "+line.info.split(" ")[-1]+" grade for student "+line.name+". No attendance record for the lecture"
#    print "\nReading Peergraded post ratings"
#    PostRatingsLines = read_ratings_logs(students,peer=True)
#    for line in PostRatingsLines:
#        logs[line.num]=logs.get(line.num,[])+[line]
    return logs


def attended_lecture(ATTLines, num, lec_num):
    for l in ATTLines:
        if l.num==num and l.info == lec_num:
            return True
    return False


def read_attendance_exceptions():
    res = {}
    f=codecs.open(os.path.join("meta","distance_quiz.txt"),mode="r",encoding="latin1")
    for line in f.readlines():
        line = line.strip()
        num,students = line.split(":")
        students = students.split(",")
        res[num] = [x.strip() for x in students]
    return res

def read_bad_attendances():
  res = {}
  f=open(os.path.join("meta","bad_attendances.txt"))
  for line in f.readlines():
    line = line.strip()
    num,students = line.split(":")
    students = students.split(",")
    res[num] = [x.strip() for x in students]
  return res



def find_student(name,students):
    for s in students:
        if s.name==name:
            return s

def read_student_list():
    res=[]
    for s in codecs.open(os.path.join(METADIR,"students.txt"),"r","latin1"):
        (num,name,email,campus)=s.strip().split(";")
        res.append(Student(num,name,email,campus))
    return res




def read_achievements():
    res=[]
    for s in csv.reader(codecs.open(os.path.join(METADIR,"achievements.txt"),"r","utf8"),delimiter=";"):
        if len(s)>0:
            (name, descr,criteria1, criteria2, criteria3, xp1, xp2, xp3, counted, posts, graded, g1, g2, g3) = s
            if counted=="True":
                counted = True
            else:
                counted = False
            if posts == "True":
                posts = True
            else:
                posts = False
            res.append(Achivement(name, descr, criteria1, criteria2, criteria3, xp1, xp2, xp3, counted, posts))
    return res




############################ ACHIEVEMENT COMMITAL ############################


def find_achievement(achievement,achievement_list):
    for a in achievement_list:
        if a.name == achievement:
            return a
    return None


def read_previous_awards(achievement_list):
    res=[]
    if not os.path.exists(os.path.join(METADIR,"awards.txt")):
        return []
    for s in codecs.open(os.path.join(METADIR,"awards.txt"),"r","utf8"):
        s=s.strip()
        if len(s)>0:
            (timestamp,student_num,achievement,value,info) = s.strip().split(";")
            value=int(value)
            a=find_achievement(achievement,achievement_list)
            if a:
                xp=a.xp[value-1]
                badge=True
                level=value
            else:
                xp=value
                level=0
                badge=False
            res.append(Award(student_num,achievement,level,xp,badge,float(timestamp),info))
            ## (self,student,achievement,level,xp,badge,timestamp, info = None):
    return res



def commit_awards(achieved, achievement_list):
    new=[]
    previous_awards=read_previous_awards(achievement_list)
    print "\n\n\n"
    for s in achieved.keys():   # go through all the students
        for a in achieved[s]:     # all awards for each student
            if not a in previous_awards:  # something new!
                print "New award: %s, %s lvl %s. %s XP" % (s, a.achievement,a.level,a.xp)
                # The following lines ensure no "skipped" levels are missed. Say you already had lvl 1 and get 3. You should get 2...
                achievement=find_achievement(a.achievement,achievement_list)
                if a.level>=2:
                    na=Award(a.student,a.achievement,1,achievement.xp[0],a.badge,a.timestamp,a.info)
                    if not na in previous_awards:
                        print "New award: %s, %s lvl %s. %s XP, %s" % (s, na.achievement,na.level,na.xp,na.info)
                        new.append(na)
                if a.level==3:
                    na=Award(a.student,a.achievement,2,achievement.xp[1],a.badge,a.timestamp,a.info)
                    if not na in previous_awards:
                        print "New award: %s, %s lvl %s. %s XP, %s" % (s, na.achievement,na.level,na.xp,na.info)
                        new.append(na)
                new.append(a)
            else:
              for aa in previous_awards:
                # DANGEROUS HACK BELOW! CHECKING INFO LIKE THIS WORKS???
                if a==aa and a.xp!=aa.xp and a.info != aa.info:
                  print a.student,"- Rescored",aa.achievement,aa.xp,"->",a.xp
                  aa.xp=a.xp
    print "\nTotal %s new awards in this run" % len(new)
    f=codecs.open(os.path.join(METADIR,"awards.txt"),"a","utf8")
    for n in new:
        f.write(str(n)+"\n")
    f.close()
    tmp = previous_awards+new
#    tmp = recycled+new
    tmp.reverse()
    return tmp   # returns list of all awards



###############################################################################
###############################################################################
##########################   HTML Page Generation  ############################
###############################################################################
###############################################################################

def read_levels():
    res=[]
    if not os.path.exists(os.path.join(METADIR,"levels.txt")):
        return []
    for s in codecs.open(os.path.join(METADIR,"levels.txt"),"r","utf8"):
        (name,xp) = s.strip().split(";")
        xp=int(xp)
        res.append((name,xp))
    return res


LEVELS = read_levels()


def write_page_header(f,title,achievements):
  award_styles=".award_gradefromexam {}\n.award_gradefromquiz {}\n.award_gradefromlab {}\n.award_initialbonus {}\n.award_gradefrompresentation {}\n.award_skilltree {}\n"
  award_ids='var all_award_ids=[".award_gradefromexam",".award_gradefromquiz",".award_gradefromlab",".award_initialbonus",".award_gradefrompresentation",".award_skilltree",'
  award_types=[]
  award_types=[x.name for x in achievements]
  award_types.sort()
  for a in award_types:
    award_styles+=".award_"+a.lower().replace(" ","")+" {}\n"
    award_ids+='".award_'+a.lower().replace(" ","")+'",'
  award_ids+="];\n"

  f.write("""<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=latin-1">
<title>Multimedia Content Production Scores - %s</title>

<script type="text/javascript" src="d3.v2.js"></script>

<script language='javascript'>
var no_go=false;

function toggleVisibility(controlId)
{
   var control = document.getElementById(controlId);
   if(control.style.visibility == "visible" || control.style.visibility == "")
      control.style.visibility = "hidden";
   else
      control.style.visibility = "visible";
}


function changecss(myclass,element,value) {
	var CSSRules
	if (document.all) {
		CSSRules = 'rules'
	}
	else if (document.getElementById) {
		CSSRules = 'cssRules'
	}
	for (var i = 0; i < document.styleSheets[0][CSSRules].length; i++) {
		if (document.styleSheets[0][CSSRules][i].selectorText == myclass) {
			document.styleSheets[0][CSSRules][i].style[element] = value
		}
	}
}


%s


function filterXPList(name)
{
   if (name=="") {
      for(x=0;x<all_award_ids.length;x++) {
        changecss(all_award_ids[x],"display","");
     }
   }
   else {
      for(x=0;x<all_award_ids.length;x++) {
        changecss(all_award_ids[x],"display","none");
     }
     changecss(name,"display","");
   }
}

</script>

<style>
%s

body {
    font-family: calibri, helvetica, arial, sans serif;
    background: #fefefe;
    background-image: url(bg.jpg);
    background-repeat: no-repeat;
    background-size: 100%%;
    padding:0;
    margin: 0;

}



table.badges {
    border-spacing: 0;
    padding: 0em;
    width: 100%%;
    background:  #f8f8ec;
    margin-left: auto;
    margin-right: auto;
    margin-bottom: 20px;
    margin-top: 0;
    border-top: solid 1px #919673;
    font-size: 12pt;
}

td.badges {
     vertical-align: middle;
     text-align: left;
     border-top: solid 1px #919673;
     padding-left: 0em;
     padding-right: 0.5em;
     padding-top:1em;
     padding-bottom: 1em;
}


th.badges {
     text-align: left;
     background: #f2fbc1;
     padding-left: 12pt;
     padding-right: 2em;
     padding-top:0.5em;
     padding-bottom:0.5em;

}


td.xp {
     width: 7em;
     text-align: center;
}


th.xp {
     width: 7em;
     text-align: center;
}



td.level {
   width: 12em;
   text-align: left;
}

th.level {
   padding:0;
   margin:0;
}

td.badgedescr {
   vertical-align: middle;
}

td.name {
   width: 18em;
   text-align: left;
   font-weight: bold;
}

th.name {
   width: 18em;
   padding:0;
   font-weight: bold;
}

th.photo {
   width: 32px;
   text-align: center;
   padding:0;
}

td.photo {
   width: 32px;
   text-align: center;
   padding: 0;
}


td.campus {
   width: 1em;
   text-align: center;
}

th.campus {
   text-align: center;

}


td.pos {
   width: 3em;
   text-align: center;
   font-size: 18pt;
   font-weight: bold;
   padding: 0;
}


th.pos {
   text-align: center;
   padding: 0;
}


tr.badges:hover {
    background-color: #f9b67a;
}


atr:nth-child(odd) {
    background: #ffffdd; }


atr:nth-child(even) {
    background: #ffffee; }


a {
   text-decoration: none;
   color: #2f80cb;
}


#wrapper {
    width: 80%%;
    margin-left: auto;
    margin-right: auto;
    margin-top: 0px;
}

#table-wrapper {
    background: #dce4af;
    margin-top: 50px;
    border: solid 1px #39486C;
    -moz-border-radius: 20px;
    -webkit-border-radius:20px;
    -opera-border-radius:20px;
    -khtml-border-radius:20px;
    box-shadow:rgba(220,228,175,1) 0px 10px 50px;
}

#stats-wrapper {
    background: #dce4af;
    margin-top: 50px;
    border: solid 1px #39486C;
    -moz-border-radius: 20px;
    -webkit-border-radius:20px;
    -opera-border-radius:20px;
    -khtml-border-radius:20px;
    box-shadow:rgba(220,228,175,1) 0px 10px 50px;
}

#top {
    height: 60px;
    awidth: 100%%;
    font-size: 24pt;
    text-align: left;
    color: yellow;
    font-weight: bold;
    padding-top: 20px;
    padding-left: 10%%;
    background: #9a4900;
    border-bottom: solid 2px #9a4900;
    color: gold; #f9b67a;
}

#bottom {
    height: 25px;
    awidth: 100%%;
    font-size: 10pt;
    text-align: left;
    color: yellow;
    padding-top: 4px;
    padding-left: 10%%;
    background: #9a4900;
    border-top: solid 2px #9a4900;
    margin-top: 100px;
    color:  gold;
}


h1 {
   font-size: 18pt;
   margin-left: 12pt;
   color: brown;
}


h2 {
   font-size: 16pt;
   margin: 0;
   padding: 0;
   margin-bottom:0.5em;
   color: brown;
}


.nextlevel {
     font-size: 10pt;
     font-weight: normal;
}

img.badge {
    vertical-align: middle;
    width: 48px;
}

img.largebadge {
    vertical-align: middle;
    width: 74px;
    padding: 5px;
}

td.badgedescr {
   font-size: 18pt;
   font-weight: bold;
   padding: 5px;
   padding-left: 0;
}

.descr {
   font-size: 14pt;
   font-weight: normal;
}


.statsline {
   font-size:10pt;
   font-weight: normal;
}

td.badgelist {
   width: 270px;
   text-align: left;
   padding-left: 1em;
}

th {
   text-align: left;
   padding-left: 0;
}

.goback {
   font-size: 10pt;
   margin-right: 12pt;
   margin-top: 24pt;
   float: right;
}

.lastmodified {
   font-size: 10pt;
   margin-right: 12pt;
   margin-top: 24pt;
   float: right;
}


.extraAvailable {
   color: darkred;
   float: right;
   font-size: 8pt;
}

.unrewarded {
   color: darkgreen;
   float: right;
   font-size: 8pt;
}


table.stats {
   padding:0;
   margin:0;
   font-size: 14pt;
}

tr.stats {
   margin: 0;
   padding: 0;
}


th.stats {
   margin: 0;
   margin-left: 1em;
   padding: 0;
   padding-right: 0.5em;
   color:darkred;
}


.rule {
   font-size: 8pt;
}

.rule line {
  stroke: #bbb;
  shape-rendering: crispEdges;
}

.rule line.axis {
  stroke: #000;
}

.line {
  fill: none;
  stroke: #2f80cb;
  stroke-width: 1.5px;

.highlightline {
  fill: none;
  stroke: #000000;
  stroke-width: 1.5px;
}


</style>
</head>


<body>

<div id="top">
<a href='http://groups.ist.utl.pt/~pcm.daemon/moodle/course/view.php?id=4' style="color: gold;">Multimedia Content Production</a> &ndash; %s
</div>


<div id="wrapper">



""" % (title,award_ids,award_styles,title))



def write_page_footer(f):
    f.write("""
<span class="lastmodified"><a href="awards_log.html">Last Modified: %s</a>| <a href="tree.html">Tree Stats</a></span>
</div> <!-- wrapper -->

<div id="bottom">
&copy;2011-2015 Daniel Gon&ccedil;alves, PCM faculty. This page has been automatically generated by course_achievements.py v%s. If you spot any errors, please contact us.
</div>


</body>

</html>
""" % (time.strftime("%a, %d %b %Y %H:%M:%S +0000", time.localtime()),__VERSION__))




def count_xp(s,awards,achievement_list,from_date=0, to_date=999999999999999):
    total_plus = 0
    total_minus=0
    total_tree=0

    for a in awards:
        if a.timestamp>=from_date and a.timestamp<=to_date:
            if a.student==s.num:
              if a.level:
                if find_achievement(a.achievement,achievement_list).extra[a.level-1]:
                    total_minus+=a.xp
                else:
                    total_plus+=a.xp
              elif a.achievement=="Skill Tree":
                  total_tree+=a.xp
              else:
                  total_plus+=a.xp
    if total_minus>METADATA["max_bonus"]:
        total_minus=METADATA["max_bonus"]
    if total_tree>METADATA["max_tree"]:
        total_tree=METADATA["max_tree"]
    return total_plus+total_minus+total_tree, total_plus, total_minus, total_tree



def count_badge_xp(s,awards):
    total = 0

    for a in awards:
        if a.student==s.num and a.badge:
            total+=a.xp
    return total



def count_completed_achievements(student,achievement_list, awards,from_date=0, to_date=999999999999999):
    total = 0
    for a in awards:
        if a.timestamp>=from_date and a.timestamp<=to_date:
            if student.num==a.student and a.badge:
                total+=1
    return total



def compute_level(xp):
    for l in range(len(LEVELS)-1,-1,-1):
        if xp==LEVELS[l][1]:
            return l
        if xp>LEVELS[l][1]:
            return l
    return 0


def get_leaderboard_data(students, achievement_list, awards, from_date=0, to_date=999999999999999):
    res=[]
    for s in students:
        xp,plus,minus,tree=count_xp(s,awards,achievement_list,from_date,to_date)
        completed=count_completed_achievements(s,achievement_list, awards,from_date,to_date)
        level=compute_level(xp)
        res.append((s,xp,level,completed))
    res.sort(lambda x,y:cmp(y[1],x[1]))
    return res


def total_badges(achievement_list):
    total=0
    for a in achievement_list:
        total+=a.top_level()
    return total


##def format_stats(stats):
##    res=""
##    for s in ["T","A","I","V","D"]:
##        res+=s+":"+str(stats[s])+", "
##    res=res[:-2]
##    return res


def gen_leaderboard(students, achievement_list, awards):
    data=get_leaderboard_data(students, achievement_list, awards)
    f=codecs.open(os.path.join(SITEDIR,"index.html"),"w","latin1")
    write_page_header(f,"Leaderboard",achievement_list)
    c=1
    f.write("""
<div id="table-wrapper">
<h1>Leaderboard</h1>

<table class="badges">
<tr class="badges">
<th class="badges pos">Pos</th>
<th class="badges photo">Photo</th>
<th class="badges campus">Campus</th>
<th class="badges name">Name</th>
<th class="badges xp">Experience</th>
<th class="badges">Level</th>
<th class="badges">Achievements</th>
</tr>

""")
    for d in data:
        f.write("""
<tr class="badges" onClick='document.location.href="%s.html"'>
<td class="badges pos">%s</td>
<td class="badges photo"><img style="border: 1px solid #666; width: 48px;" class="photo" src="%s" /></td>
<td class="badges campus">%s</td>
<td class="badges name">%s</td>
<td class="badges xp"><a href="%s.html#xp">%s XP</a></td>
<td class="badges level"><a href="%s.html#xp">%s</a><br /><span class='nextlevel'>%s</span></td>
<td class="badges badges"><a href="%s.html">%s</a>
<br /><div style="height:5px; border:2px solid #dce4af; padding:0; width:80px;" ><div style="height:100%%; border: None; padding:0; width:%spx; background-color:#dce4af;" /></div></td>
</tr>

""" % (d[0].num,
       c,
       get_student_photo(d[0]),
       d[0].campus,
       d[0].name,
#       format_stats(stats[d[0].num]),
       d[0].num,
       d[1],
       d[0].num,
       "%s - %s" % (d[2],LEVELS[d[2]][0]),
       d[2]<len(LEVELS)-1 and "%s XP for L%s at %s XP" % (LEVELS[d[2]+1][1]-d[1], d[2]+1, LEVELS[d[2]+1][1]) or "Top Level!",
       d[0].num,
       "%s out of %s" % (d[3],total_badges(achievement_list)),
       int(80.0/total_badges(achievement_list)*d[3])))
        c+=1
    f.write("""</table>\n
</div> <!-- table-wrapper -->""")
    write_page_footer(f)
    f.close()



def get_student_photo(student):
    if os.path.exists(os.path.join(SITEDIR,"photos",str(student.num)+".png")):
        return "photos/"+str(student.num)+".png"
    elif os.path.exists(os.path.join(SITEDIR,"photos",str(student.num)+".jpg")):
        return "photos/"+str(student.num)+".jpg"
    else:
        return "photos/no_photo.gif"


def write_student_xp_list(f,student, achievement_list, awards):
    student_awards=[]
    for a in awards:
        if a.student==student.num:
            if not a.achievement in student_awards:
              student_awards.append(a.achievement)
    student_awards.sort()
    xp, plus, minus, tree = count_xp(student,awards,achievement_list)
    select_options=""
    for a in student_awards:
      select_options+='<option value=".award_%s">%s</option>\n' % (a.lower().replace(" ",""),a)
    f.write("""

<a id="xp"></a>
<div id="table-wrapper">
<div class="goback"><a href="index.html">< back to leaderboard</a></div>
<h1>XP List (%s total, %s mandatory, %s from tree, %s bonus)</h1>

<table class="badges">
<tr class="badges">
<th class="badges xp">Experience</th>
<th class="badges">Date</th>
<th class="badges">Description</th>
  <th class="badges" style="text-align:right;">Filter:
  <select id="filter_combo" onchange='filterXPList(this.value);'>
  <option value=""></option>
  %s
  </select>
  <button onclick="document.getElementById('filter_combo').selectedIndex=0;filterXPList('');">X</button>
  </th>
</tr>
""" % (xp, plus, tree, minus,  select_options))
    c=0
    for a in awards:
        if a.student==student.num:
            c+=1
            descr=a.achievement
            if not a.level:
              descr=descr+" "+a.info
            if a.level:
                descr = "Earned "+descr + " badge (level %s) %s" % (a.level, get_badge_image(a.achievement, achievement_list, a.level))
            f.write("""<tr class="badges award_%s">
<td class="badges xp">%s XP</td>
<td class="badges">%s</td>
<td colspan="2" class="badges badgedescr">%s</td>
</tr>

""" % (a.achievement.lower().replace(" ",""), a.xp, time.strftime("%d-%b-%Y",time.localtime(float(a.timestamp))),descr))
    if c==0:
        f.write("<tr class='badges'><td  class='badges' style='padding: 1em; font-size: 18pt; text-align: center;' colspan='2'>You have no XP yet! Wake up and go get some!</td></tr>\n")
    f.write("""</table>\n
</div> <!-- table-wrapper -->""")



def has_badge(student, achievement, awards):
    maxlevel=0
    maxaward=None
    for a in awards:
        if a.student==student.num and \
           a.achievement == achievement.name:
            if a.level > maxlevel:
                maxlevel = a.level
                maxaward = a
    return maxlevel, maxaward



def gen_html_barchart(data,maxval=None,height=300,barwidth=5,labels=None, \
                      labelfontsize=8,labelsep=1,barsep=1, highlight=0, \
                      barcolor="red",highlightcolor="blue",axiscolor="black", \
                      yaxisval=True,minbarheight=2):
    if maxval==None:
        maxval=max(data)
    res="""<table cellspacing="0" cellpadding="0">
<tr>\n"""
    if yaxisval:
        res+="""<td style="font-size:%spt; vertical-align: top;">%s</td>\n""" % (labelfontsize,maxval)
    res+="""<td style="padding:0; margin:0; border-bottom: 1px solid %s;
vertical-align: bottom;"><div style="background: %s; width: %spx; height:%spx;"></td>\n""" % (axiscolor,axiscolor,1,height)
    count = 0
    for d in data:
        if not (highlight is None) and count==highlight:
            color = highlightcolor
        else:
            color = barcolor
        count+=1
        if d>0:
            barheight=max(minbarheight,int(((d*1.0)/maxval)*height))
        else:
            barheight=0
        res+="""<td style="padding:0; padding-right: %spx; margin:0; border-bottom: 1px solid %s;
vertical-align: bottom;"><div style="background: %s; width: %spx; height:%spx;"></td>\n""" % (barsep,axiscolor,color,barwidth,barheight)
    res+="</tr>\n"
    if labels:
        res+="""<tr style="font-size: %spt;">\n""" % (labelfontsize)
        if yaxisval:
            res+="<td></td>\n"  # the label
        res+="<td></td>\n"  # the axis
        for l in labels:
            res+="""<td colspan="%s">%s</td>\n""" % (labelsep,l)
        res+="</tr>\n"
    res+="</table>"
    return res



def write_student_stats(f,student, achievement_list, awards, indicators, histograms, students, logs):
    xp, plus, minus, tree = count_xp(student,awards,achievement_list)
    badges = count_completed_achievements(student,achievement_list, awards)
    level = compute_level(xp)
    a, b, karma = FilterGrades(logs,student)
    f.write("""
<div id="stats-wrapper">
<table style="margin:12px; width:100%%">
<tr>
<td style="vertical-align: top; border-right:1px solid black;padding:0; padding-right:1em;width:1px;">
  <h1 style="padding:0; margin:0;margin-bottom:0.5em;">%s (%s) - Level %s</h1>

<table>
<tr>
<td style="vertical-align: top;">
  <img style="margin:0; padding:0; border: 1px solid #666; width: 120px;" src="%s" />
</td>

<td style="vertical-align: top;">
    <table class="stats" style="padding:0; margin:0; font-size: 14pt; ">
    <tr><th class="stats">XP:<br /><div style="height:9px; " /></th><td>%s %s
      <br /><div style="height:5px; border:2px solid #F8F8EC; padding:0; width:100%%;" ><div style="height:100%%; border: None; padding:0; width:%s%%; background-color:#F8F8EC" /></td></tr>
    <tr style="font-size:1pt;"><td colspan="2">&nbsp;</td></tr>
    <tr><th class="stats">Badges:<br /><div style="height:9px; " /></th><td>%s of %s (%s XP)
    <br /><div style="height:5px; border:2px solid #F8F8EC; padding:0; width:100%%;" ><div style="height:100%%; border: None; padding:0; width:%s%%; background-color:#F8F8EC" /></td></tr>
    <tr style="font-size:1pt;"><td colspan="2">&nbsp;</td></tr>
    <tr><th class="stats">Karma:</th><td>%s</td></tr>
    </table>
</td>
</tr>
<tr>

<td colspan="2" style="vertical-align: top; padding-top:2em;">
<h2>Your Badges and the world's...</h2>
%s
</td>
</tr>
</table>

</td>
<td style="padding-left: 1em;">
<h2>Your XP and the world's...</h2>
%s
<br />
<div id="xp_linechart"><h2>XP Evolution</h2></div>
<div style="margin-top:-40px;" id="leaderboard_linechart"><h2>Leaderboard Evolution</h2></div>

</td>
</tr>

</table>

</td>


</tr>
</table>
<div style="margin-left: 1em;text-align:left;"><a href="index.html">&larr; Leaderboard</a> | <a href="#badgelist">&darr;badges</a> | <a href="#tree">&darr;tree</a> | <a href="#xp">&darr;xp list</a></div>

</div>


""" % (student.name,
       student.num,
       level,
       get_student_photo(student),
       xp,
       level<len(LEVELS)-1 and "(%s for L%s)" % (LEVELS[level+1][1]-xp,level+1) or "(Top Level!)",
       min(100,int((METADATA["level_grade"]-LEVELS[min(20,level+1)][1]+xp)/(METADATA["level_grade"]*1.0)*100)),
       badges,
       total_badges(achievement_list),
       count_badge_xp(student, awards),
       int(badges/(total_badges(achievement_list)*1.0)*100),
       karma,
       # (((xp_hist_vals,xp_hist_labels), (bd_hist_vals,bd_hist_labels)), xp_highlight, bd_highlight)
       gen_html_barchart(histograms[0][1][0],labels=histograms[0][1][1],highlight=histograms[2],labelsep=4, barwidth=5, height=120, barcolor="darkred", highlightcolor="#2f80cb"),
       gen_html_barchart(histograms[0][0][0],labels=histograms[0][0][1],highlight=histograms[1],labelsep=4, barwidth=8, height=80, barcolor="darkred", highlightcolor="#2f80cb")
       ))
    xpbydate=get_xp_by_date(student, achievement_list, awards)
    f.write("""
<script language='javascript'>

var data = %s;

var w = 370,
    h = 70,
    p = 35,
    x = d3.scale.linear().domain([0, 150]).range([0, w]),
    y = d3.scale.linear().domain([0, %s]).range([h,0]);


var vis = d3.select("#xp_linechart")
    .data([data])
  .append("svg")
    .attr("width", w + p * 2)
    .attr("height", h + p * 2)
  .append("g")
    .attr("transform", "translate(" + p + "," + 0.2 * p + ")");

vis.append("text")
    .attr("x", w)
    .attr("y", h + 30)
    .attr("text-anchor", "end")
    .text("days since beginning");


var rules = vis.selectAll("g.rule")
    .data(x.ticks(10))
  .enter().append("g")
    .attr("class", "rule");

rules.append("line")
    .attr("class", function(d) { return d ? null : "axis"; })
    .attr("x1", x)
    .attr("x2", x)
    .attr("y1", 0)
    .attr("y2", h - 1);

rules.append("text")
    .attr("x", x)
    .attr("y", h + 3)
    .attr("dy", ".71em")
    .attr("text-anchor", "middle")
    .text(x.tickFormat(10));


var rules3 = vis.selectAll("g.rule3")
    .data(y.ticks(5))
  .enter().append("g")
    .attr("class", "rule");

rules3.append("line")
    .attr("class", function(d) { return d ? null : "axis"; })
    .attr("y1", function (d) { return y(d); })
    .attr("y2", function (d) { return y(d); })
    .attr("x1", 0)
    .attr("x2", w + 1);

rules3.append("line")
    .attr("class", "axis")
    .attr("y1", h)
    .attr("y2", h)
    .attr("x1", 0)
    .attr("x2", w + 1);


var rules2 = vis.selectAll("g.rule2")
    .data(y.ticks(5))
  .enter().append("g")
    .attr("class", "rule");

rules2.append("text")
    .attr("y", function(d) { return y(d); })
    .attr("x", -3)
    .attr("dy", "0.35em")
    .attr("text-anchor", "end")
    .text(y.tickFormat(5));

vis.append("path")
    .attr("class", "line")
    .attr("d", d3.svg.line()
    .x(function(d) { return x(d.x); })
    .y(function(d) { return y(d.y); }));

var maxy=d3.max(data, function (d) { return d.y; });

</script>




<script language='javascript'>

var data = %s;

var w = 370,
    h = 70,
    p = 35,
    x = d3.scale.linear().domain([0, 150]).range([0, w]),
    y = d3.scale.linear().domain([1, 65]).range([0,h]);


var vis = d3.select("#leaderboard_linechart")
    .data([data])
  .append("svg")
    .attr("width", w + p * 2)
    .attr("height", h + p * 2)
  .append("g")
    .attr("transform", "translate(" + p + "," + 0.2 * p + ")");

vis.append("text")
    .attr("x", w)
    .attr("y", h + 30)
    .attr("text-anchor", "end")
    .text("days since beginning");


var rules = vis.selectAll("g.rule")
    .data(x.ticks(10))
  .enter().append("g")
    .attr("class", "rule");

rules.append("line")
    .attr("class", function(d) { return d ? null : "axis"; })
    .attr("x1", x)
    .attr("x2", x)
    .attr("y1", 0)
    .attr("y2", h - 1);

rules.append("text")
    .attr("x", x)
    .attr("y", h + 4)
    .attr("dy", ".71em")
    .attr("text-anchor", "middle")
    .text(x.tickFormat(10));


var rules3 = vis.selectAll("g.rule3")
    .data([1,10,20,30,40,50,60,65])
  .enter().append("g")
    .attr("class", "rule");

rules3.append("line")
    .attr("class", function(d) { return (d!=55) ? null : "axis"; })
    .attr("y1", function (d) { return y(d); })
    .attr("y2", function (d) { return y(d); })
    .attr("x1", 0)
    .attr("x2", w + 1);

rules3.append("line")
    .attr("class", "axis")
    .attr("y1", h)
    .attr("y2", h)
    .attr("x1", 0)
    .attr("x2", w + 1);



var rules2 = vis.selectAll("g.rule2")
    .data([1,10,20,30,40,50,60,65])
  .enter().append("g")
    .attr("class", "rule");

rules2.append("text")
    .attr("y", function(d) { return y(d); })
    .attr("x", 0)
    .attr("dy", "0.35em")
    .attr("text-anchor", "end")
    .text(y.tickFormat(5));

vis.append("path")
    .attr("class", "line")
    .attr("d", d3.svg.line()
    .x(function(d) { return x(d.x); })
    .y(function(d) { return y(d.y); }));

var maxy=d3.max(data, function (d) { return d.y; });

//vis.append("svg:path")
//    .attr("style","stroke: #eee;")
//    .attr("d", "M0,"+y(maxy)+"L" + w + "," +y(maxy));


</script>



""" %  (xpbydate,xpbydate[-1]["y"],
        get_leaderboard_by_date(student, students, achievement_list, awards)))



def write_student_badge_list(f,student, achievement_list, awards, indicators):
    f.write("""
<a  id="badgelist" />
<div id="table-wrapper">
<div class="goback"><a href="index.html">< back to leaderboard</a></div>
<h1 style='margin-top:24px;'>Badges (%s of %s, %s XP)</h1>

<table class="badges">
<tr class="badges">
<th class='badges pos'>Badge</th>
<th class="badges">Description</th>
</tr>
""" % (count_completed_achievements(student,achievement_list, awards),
       total_badges(achievement_list),
       count_badge_xp(student, awards)))
    c=0
    for a in achievement_list:
        level, award=has_badge(student,a,awards)
        #level=a.top_level()
        if not level:
            img=get_badge_image("blank",None,None,True)
            badgedescr=a.name
        else:
            img=get_badge_image(a.name,achievement_list,level,True)
            badgedescr="%s &ndash; level %s (%s XP%s)" % (a.name,level,a.xp[level-1],a.extra[level-1] and " <span style='font-size: 10pt; color:darkred;'>EXTRA!</span>" or "")
        if level==a.top_level():
            next_level_criteria="Top level!"
        else:
            if a.unrewarded:
                next_level_criteria="Level %s: %s" % (level+1,a.criteria[level])
            else:
                next_level_criteria="Level %s: %s (%s XP%s)" % (level+1,a.criteria[level], a.xp[level], a.extra[level] and "<span style='color:darkred'> EXTRA CREDIT</span>" or "")
        id_name=a.name.replace(" ","")
        if a.counted:
            try:
              if indicators[student.num][a.name][0]:
                counts = " [%s so far]" % indicators[student.num][a.name][0]
            except:
                counts = ""
        else:
            counts = ""
#        print a.name, a.post_based
#        print a.name
        if a.post_based and indicators.has_key(student.num) and indicators[student.num].has_key(a.name) and len(indicators[student.num][a.name][1])>0:
            if counts=="":
                counts=" [Posts]"
            counts = """<a href='' onClick='no_go=true; toggleVisibility("%s"); return false;'>""" % id_name+counts+"</a>"
            counts += "<br><span id='%s' style='visibility: hidden;'>" % id_name
            counter=1
            for p in indicators[student.num][a.name][1]:
              #CHANGED was p.info now is p.url
                if p.url and p.url[:4]=="http":
                  #funny=p.url.replace("#p","&parent="); #print "*** ", p.url, "-->", funny, "\n"; #JAJ
                  counts+="<a onClick='no_go=true; ' href='%s'>P%s</a>" % (p.url, counter) #JAJ p.url -> funny
                else:
                    #funny=p.info.replace("#p","&parent="); #print "*** ", p.url, "-->", funny, "\n"; #JAJ
                    counts+="<a onClick='no_go=true; ' href='%s'>P%s</a>" % (p.url, counter) #JAJ p.url -> funny
                  #  counts+=p.info
                if counter<len(indicators[student.num][a.name][1]):
                    counts+=" | "
                counter+=1
            counts+="</span>"
        elif a.counted and indicators[student.num][a.name][1]:
            counts = """<a href='' onClick='no_go=true; toggleVisibility("%s"); return false;'>""" % id_name+counts+"</a>"
            counts += "<br><span id='%s' style='visibility: hidden;'>" % id_name
            counter=1
            for p in indicators[student.num][a.name][1]:
              #PREVIOUSLY CHANGED was p.info now is p.url. REVERTED!
                if p.info:
#                    print p
#                    print a.name,"\n\n"
                    counts+=p.info
                if counter<len(indicators[student.num][a.name][1]):
                    counts+=" | "
                counter+=1
            counts+="</span>"

        badgeurl=a.name.replace(" ","")+".html"
        f.write("""<tr class="badges" onClick='if (!no_go) {document.location.href="%s";} else { no_go=false; }'>\n""" % badgeurl)
        f.write('<td class="badges badgelist">')
        for i in range(0,level-1):
            f.write(get_badge_image(a.name,achievement_list,i+1,True))
        if level:f.write(img)
        for i in range(level,a.top_level()):
            f.write(get_badge_image("blank",achievement_list,i+1,True))
        f.write('</td>\n' )
        if a.has_extra():
            extra_txt='<span class="extraAvailable">Extra Credit Available!</span>'
        elif a.unrewarded():
            extra_txt='<span class="unrewarded">Bragging Rights!</span>'
        else:
            extra_txt=""
        f.write('<td class="badges badgedescr">%s%s<br /><span class="descr">%s</span><br /><span class="nextlevel">%s</span></td>' % (extra_txt, badgedescr, a.description, next_level_criteria+counts))

    f.write("""</table>\n
</div> <!-- table-wrapper -->""")





def write_overall_XP_list(students,achievement_list, awards):
    f=codecs.open(os.path.join(SITEDIR,"awards_log.html"),"w","latin1")
    write_page_header(f,"Awards Log", achievement_list)

    student_awards=[]
    for a in awards:
        if not a.achievement in student_awards:
          student_awards.append(a.achievement)
    student_awards.sort()
    select_options=""
    for a in student_awards:
      select_options+='<option value=".award_%s">%s</option>\n' % (a.lower().replace(" ",""),a)

    f.write("""

<div id="table-wrapper">
<div class="goback"><a href="index.html">< back to leaderboard</a></div>
<h1>Awards List</h1>

<table class="badges">
<tr class="badges">
<th class="badges xp">Experience</th>
<th class="badges">Date</th>
<th class="badges">Name</th>
<th class="badges">Description</th>
<th  class="badges" style="text-align:right;">Filter:
  <select id="filter_combo" onchange='filterXPList(this.value);'>
  <option value=""></option>
  %s
  </select>
  <button onclick="document.getElementById('filter_combo').selectedIndex=0;filterXPList('');">X</button>
</th>
</tr>
""" % select_options )
    c=0
    for a in awards:
        c+=1
        descr=a.achievement
        if a.level:
            descr = descr + " (level %s) %s" % (a.level, get_badge_image(a.achievement, achievement_list, a.level))
        else:
            descr=descr+" "+a.info
        name="nonamed"
        for s in students:
            if s.num==a.student:
                name=s.name
        if a.badge:
            badgeurl=a.achievement.replace(" ","")+".html"
            f.write("""<tr class="badges award_%s" onClick='document.location.href="%s"'>""" % (a.achievement.lower().replace(" ",""),badgeurl))
        else:
            f.write("<tr class='badges award_%s' >\n" % a.achievement.lower().replace(" ",""))
        f.write("""
<td class="badges xp">%s XP</td>
<td class="badges">%s</td>
<td class="badges">%s</td>
<td colspan="2" class="badges badgedescr">%s</td>
</tr>

""" % (a.xp, time.strftime("%d-%b-%Y",time.localtime(float(a.timestamp))),name,descr))
    if c==0:
        f.write("<tr class='badges'><td  class='badges' style='padding: 1em; font-size: 18pt; text-align: center;' colspan='2'>None Yet!</td></tr>\n")
    f.write("""</table>\n
</div> <!-- table-wrapper -->""")

    write_page_footer(f)
    f.close()


def tree_level_count(tree,level):
    res=0
    for t in tree:
        if t.level==level:
            res+=1
    return res


def tree_level_awards(tree,level):
    res=[]
    for t in tree:
        if t.level==level:
            res.append(t)
    return res

## This is the icon version. See below for the current color block version
##
##def write_student_tree(f,tree, won, unlocked):
##    f.write("""
##<a  id="tree" />
##<div id="table-wrapper">
##<div class="goback"><a href="index.html">< back to leaderboard</a></div>
##<h1 style='margin-top:24px;'>Skill Tree</h1>
##<table class="badges" style="text-align:center">
##<tr class="badges">
##<td>
##""")
##    maxh=max(tree_level_count(tree,1), tree_level_count(tree,2),
##             tree_level_count(tree,4), tree_level_count(tree,4))
##    tmp=[]
##    for l in range(0,4):
##        tmp.append([None]*maxh)
##    for l in range(1,5):
##        start=(maxh-tree_level_count(tree,l))/2
##        for t in tree_level_awards(tree,l):
##            tmp[l-1][start]=t
##            start+=1
##    for a in range(0,maxh):
##        f.write("<tr>")
##        for l in range(0,4):
##            if tmp[l][a]:
##                award_descr="<span style='font-weight: bold;'>%s</span><br />" % tmp[l][a].name
##                for pc in tmp[l][a].PCs:
##                    award_descr+=pc.Printable()+"<br />"
##                if tmp[l][a].name in won:
##                    award_descr="<span style='color: #0687ed;'>"+award_descr+"</span>"
##                    f.write("<td>%s</br>%s</td>" % (get_tree_image(tmp[l][a],locked=2),award_descr))
##                elif tmp[l][a].name in unlocked:
##                    f.write("<td>%s</br>%s</td>" % (get_tree_image(tmp[l][a]),award_descr))
##                else:
##                    award_descr="<span style='color: #aaaaaa;'>"+award_descr+"</span>"
##                    f.write("<td>%s</br>%s</td>" % (get_tree_image(tmp[l][a],locked=1),award_descr))
##
##    f.write("""
##
##</td>
##</tr>
##</table>\n
##</div> <!-- table-wrapper -->""")


import math

def write_student_tree(f,tree, won, unlocked):
    f.write("""
<a  id="tree" />
<div id="table-wrapper">
<div class="goback"><a href="index.html">< back to leaderboard</a></div>
<h1 style='margin-top:24px;'>Skill Tree</h1>
<table class="badges" style="text-align:center">
<tr class="badges">
<td>
""")
    maxh=max(tree_level_count(tree,1), tree_level_count(tree,2),
             tree_level_count(tree,4), tree_level_count(tree,4))
    tmp=[]
    for l in range(0,4):
        tmp.append([None]*maxh)
    for l in range(1,5):
        start=int(math.ceil((maxh-tree_level_count(tree,l))/2.0))
        for t in tree_level_awards(tree,l):
            tmp[l-1][start]=t
            start+=1
    for a in range(0,maxh):
        f.write("<tr>")
        for l in range(0,4):
            if tmp[l][a]:
                award_descr=""
                for pc in tmp[l][a].PCs:
                    award_descr+=pc.Printable()+"<br />"
                if tmp[l][a].name in won:
                    award_descr="<span style='color: #0687ed;'>"+award_descr+"</span>"
                    f.write("<td style='padding: 1em 0 0 0; font-size: 9pt;'>%s%s</td>" % (get_tree_image(tmp[l][a],locked=2),award_descr))
                elif tmp[l][a].name in unlocked:
                    f.write("<td style='padding: 1em 0 0 0; font-size:9pt;'>%s%s</td>" % (get_tree_image(tmp[l][a]),award_descr))
                else:
                    award_descr="<span style='color: #aaaaaa;'>"+award_descr+"</span>"
                    f.write("<td style='padding:1em 0 0 0; font-size: 9pt;'>%s%s</td>" % (get_tree_image(tmp[l][a],locked=1),award_descr))

    f.write("""

</td>
</tr>
</table>\n
</div> <!-- table-wrapper -->""")


## This is the icon version. See below for the current color block version
##
##
##def get_tree_image(tree_award,locked=0):
##    if tree_award==None:
##        return ""
##    else:
##        tree_award=tree_award.name
##        if locked==1:
##            return "<img style='border:2px solid #dadada; border-radius:7px; background:white; width: 60px; opacity: 0.1; filter: alpha(opacity=10);' src='tree/%s.png' title='%s' /></a>" % (
##                                                                                 tree_award.replace(" ",""),
##                                                                                 tree_award)
##        elif locked==0: # active
##            return "<img style='border:2px solid #dadada; border-radius:7px; background:white; width: 60px;' src='tree/%s.png' title='%s' /></a>" % (
##                                                                                 tree_award.replace(" ",""),
##                                                                                 tree_award)
##        else: # won!
##            return "<img style='border:2px solid #dadada; border-radius:7px; background:white; width: 60px; border-color:#9ecaed; box-shadow: 0 0 20px #9ecaed;' src='tree/%s.png' title='%s' /></a>" % (
##                                                                                 tree_award.replace(" ",""),
##                                                                                 tree_award)



def get_tree_image(tree_award,locked=0):
    if tree_award==None:
        return ""
    else:
        tree_award_name=tree_award.name
        color=tree_award.color
        xp = tree_award.xp
        if locked==1:
            return "<div title='%s - %sxp\n\nClick for description' onclick='document.location.href=\"tree/%s.html\"' style='width:60px; height:60px; color: white; margin: auto; background-color: #aaaaaa; text-align: left; font-size: 10pt; padding:5px; border-color:#dadada; '>%s</div>" % (tree_award_name,xp,tree_award_name.replace(" ",""),tree_award_name)
        elif locked==0: # active
            return "<div title='%s - %sxp\n\nClick for description' onclick='document.location.href=\"tree/%s.html\"' style='width:60px; height:60px; color: white; margin: auto; background-color: %s; text-align: left; font-size: 10pt; padding:5px;'>%s</div>" % (tree_award_name,xp,tree_award_name.replace(" ",""),color,tree_award_name)
        else: # won!
            return "<div title='%s - %sxp\n\nClick for description' onclick='document.location.href=\"tree/%s.html\"' style='width:60px; height:60px; color: white; margin: auto; background-color: %s; text-align: left; font-size: 10pt; padding:5px; border-color:#dadada; box-shadow: 0 0 30px cyan;'>%s</div>" % (tree_award_name,xp,tree_award_name.replace(" ",""),color,tree_award_name)





def get_badge_image(achievement, achievement_list, level, large=False):
    if large:
        size="largebadge"
    else:
        size="badge"
    name=achievement.replace(" ","")+".html"
    if achievement=="blank":
        return "<img class='%s' src='badges/blank.png' title='%s' />" % (size,achievement)
    for a in achievement_list:
        if a.name==achievement:
            return "<a style='border: 0;' href='%s'><img style='border: 0;' class='%s' src='badges/%s-%s.png' title='%s' /></a>" % (name,
                                                                             size,
                                                                             a.name.replace(" ",""),
                                                                             level,
                                                                             a.name+" (level %s)" % level)

def gen_student_page(student, achievement_list, awards, indicators, histograms,tree,tree_won,tree_unlocked,students,logs):
#    print "generating page for",student.num
    print ".",
    f=codecs.open(os.path.join(SITEDIR,str(student.num)+".html"),"w","latin1")
    write_page_header(f,student.name, achievement_list)
    write_student_stats(f,student, achievement_list, awards, indicators, histograms, students, logs)
    write_student_badge_list(f,student, achievement_list, awards, indicators)
    write_student_tree(f,tree,tree_won,tree_unlocked)
    write_student_xp_list(f,student, achievement_list, awards)
    write_page_footer(f)
    f.close()


def get_histograms(students,achievement_list, awards, xpstep = METADATA["level_grade"]/2,badgestep=1,xplegendstep=4,badgelegendstep=4):
    # two histograms: badges and xp
    maxbadges=total_badges(achievement_list)
    xp_hist = []
    xp = [x[0] for x in [count_xp(s,awards,achievement_list) for s in students]]
    for v in range(xpstep,METADATA["max_xp"]+xpstep+1,xpstep):
        count=0
        for x in xp:
            if x<v and x>=v-xpstep:
                count+=1
        xp_hist.append(count)

    xp_legend=[]
    for x in range(0,METADATA["max_xp"]/xpstep,xplegendstep):
      xp_legend.append(xpstep*x)
    if xp_legend[-1]!=METADATA["max_xp"]:
        xp_legend.append(METADATA["max_xp"])

    badge_hist = []
    badges = [count_completed_achievements(s,achievement_list, awards) for s in students]
    for x in range(0,maxbadges+1):
        count = 0
        for b in badges:
            if b==x:
                count+=1
        badge_hist.append(count)

    badge_legend=[]
    for x in range(0,int(maxbadges/(badgestep*1.0)),badgelegendstep):
        badge_legend.append(badgestep*x)
    if badge_legend[-1]!=maxbadges:
        badge_legend.append(maxbadges)
    return (xp_hist,xp_legend),(badge_hist,badge_legend)


def tomorrow(last_date):
  tmp=datetime.datetime(int(last_date[:4]),int(last_date[5:7]), int(last_date[8:10]))+datetime.timedelta(days=1)
  return "%4d-%02d-%02d" % (tmp.year,tmp.month,tmp.day)

def yesterday(last_date):
  tmp=datetime.datetime(int(last_date[:4]),int(last_date[5:7]), int(last_date[8:10]))+datetime.timedelta(days=-1)
  return "%4d-%02d-%02d" % (tmp.year,tmp.month,tmp.day)


def get_xp_by_date(s, achievement_list, awards):
    last_date="2015-02-17"
    end_date=tomorrow(tomorrow(datetime.date.fromtimestamp(time.time()).strftime("%Y-%m-%d")))
    res=[]
    counter=0
    while last_date<end_date:
        tmp=datetime.datetime(int(last_date[:4]),int(last_date[5:7]),int(last_date[8:10]))
        tmp=time.mktime(tmp.timetuple())
        res.append({"x": counter, "y": count_xp(s,awards,achievement_list,to_date=tmp)[0]})
        last_date=tomorrow(last_date)
        counter+=1
    return res


def get_achievements_by_date(s, achievement_list, awards):
    last_date="2015-02-17"
    end_date=tomorrow(tomorrow(datetime.date.fromtimestamp(time.time()).strftime("%Y-%m-%d")))
    res=[]
    while last_date<end_date:
        tmp=datetime.datetime(int(last_date[:4]),int(last_date[5:7]),int(last_date[8:10]))
        tmp=time.mktime(tmp.timetuple())
        res.append(count_completed_achievements(s,achievement_list,awards,to_date=tmp))
        last_date=tomorrow(last_date)
    return res


lbs={}

def get_leaderboard_by_date(s, students, achievement_list, awards):
    last_date="2015-02-17"
    end_date=tomorrow(tomorrow(datetime.date.fromtimestamp(time.time()).strftime("%Y-%m-%d")))
    #end_date=tomorrow(tomorrow("2014-07-01"))
    res=[]
    counter=0
    while last_date<end_date:
        tmp=datetime.datetime(int(last_date[:4]),int(last_date[5:7]),int(last_date[8:10]))
        tmp=time.mktime(tmp.timetuple())
        if not lbs.has_key(tmp):
            lb=get_leaderboard_data(students, achievement_list, awards, to_date=tmp)
            lbs[tmp]=lb
        else:
            lb=lbs[tmp]
        #(s,xp,level,completed)
        pos=1
        for p in lb:
            if p[0].num == s.num:
                res.append({"x": counter, "y": pos})
            pos+=1
        last_date=tomorrow(last_date)
        counter+=1
    return res



def gen_student_pages(students, achievement_list, awards, indicators, tree, tree_won, tree_unlocked, logs):
    hl1=0
    hl2=0
    histograms = get_histograms(students,achievement_list, awards, xpstep = METADATA["level_grade"]/2,badgestep=1,xplegendstep=4,badgelegendstep=4)
    for s in students:
        hl1=count_xp(s,awards,achievement_list)[0]/(METADATA["level_grade"]/2)
        hl2=count_completed_achievements(s,achievement_list, awards)
        gen_student_page(s,
                         achievement_list,
                         awards,
                         indicators,
                         (histograms,hl1,hl2),
                         tree,
                         tree_won[s.num],
                         tree_unlocked[s.num],
                         students,
                         logs)


def has_achieved_above(student, award, awards, level):
    for l in range(level+1,4):
        for a in awards:
            if a.achievement==award.achievement and a.level==l and a.student==student.num:
                return True
    return False


def write_achievement_winners(f, achievement, students, achievement_list, awards, level):
    imgtxt=""
    for n in range(0,level):
        imgtxt+=get_badge_image(achievement.name, achievement_list, n+1, True)
    if achievement.unrewarded():
        criteria="%s" % (achievement.criteria[level-1])
    else:
        criteria="%s (%s XP%s)" % (achievement.criteria[level-1], achievement.xp[level-1], achievement.extra[level-1] and "<span style='color:darkred'> EXTRA CREDIT</span>" or "")
    f.write("""

<div id="table-wrapper"'>
<div class="goback"><a href="index.html">< back to leaderboard</a></div>
<div class="badgedescr"><div style='float:left; margin: 0.5em 0.5em 0.5em 1em;'>%s</div><div style='font-size:32px;font-weight:bold;color:brown;padding-top: 24px;'>Level %s <br /><span class="nextlevel" style='color:black;'>%s</span></div></div>



<table class="badges">
<tr class="badges">
<th class="badges" style="width: 8em; padding-left: 2em;">Date</th>
<th class="badges photo">Photo</th>
<th class="badges campus">Campus</th>
<th class="badges">Name</th>
</tr>
""" % (imgtxt, level, criteria))

    count_a=0
    count_t=0
    for a in awards:
        if a.achievement==achievement.name and a.level==level:
            for s in students:
                if s.num==a.student and not has_achieved_above(s, a, awards, level):
                    student=s
                    if s.campus=="A":
                        count_a+=1
                    else:
                        count_t+=1
                    f.write("""
<tr class="badges" onClick='document.location.href="%s.html"'>
<td class="badges" style="padding-left: 2em;">%s</td>
<td class="badges"><img style="border: 1px solid #666; width: 48px;" class="photo" src="%s" /></td>
<td class="badges campus">%s</td>
<td class="badges">%s</td>
</tr>
""" % (str(student.num), time.strftime("%d-%b-%Y",time.localtime(float(a.timestamp))), get_student_photo(student), student.campus, student.name))


    f.write("""</table>\n
    <span style="font-weight: bold; padding: 2em;"> Alameda: %s | Tagus: %s</span>
    <p />
</div> <!-- table-wrapper -->""" % (count_a, count_t))



def gen_achievement_page(achievement, students, achievement_list, awards):
    f=codecs.open(os.path.join(SITEDIR,achievement.name.replace(" ","")+".html"),"w","latin1")
    write_page_header(f,achievement.name, achievement_list)
    if achievement.top_level()>=3:
        write_achievement_winners(f, achievement, students, achievement_list, awards, 3)
    if achievement.top_level()>=2:
        write_achievement_winners(f, achievement, students, achievement_list, awards,2)
    write_achievement_winners(f, achievement, students, achievement_list, awards,1)
    write_page_footer(f)
    f.close()



def gen_achievements_page(students, achievement_list, awards):
    for a in achievement_list:
        gen_achievement_page(a, students, achievement_list, awards)






def gen_tree_page(students, achievement_list, awards, indicators, tree, tree_won, tree_unlocked,logs):
    f=codecs.open(os.path.join(SITEDIR,"tree.html"),"w","latin1")
    write_page_header(f,"Skill Tree", achievement_list)
    f.write("""
<a  id="tree" />
<div id="table-wrapper">
<div class="goback"><a href="index.html">< back to leaderboard</a></div>
<h1 style='margin-top:24px;'>Skill Tree</h1>
<table class="badges" style="text-align:center">
<tr class="badges">
<td>
""")
    maxh=max(tree_level_count(tree,1), tree_level_count(tree,2),
             tree_level_count(tree,4), tree_level_count(tree,4))
    tmp=[]
    for l in range(0,4):
        tmp.append([None]*maxh)
    for l in range(1,5):
        start=int(math.ceil((maxh-tree_level_count(tree,l))/2.0))
        for t in tree_level_awards(tree,l):
            tmp[l-1][start]=t
            start+=1
    for a in range(0,maxh):
        f.write("<tr>")
        for l in range(0,4):
            if tmp[l][a]:
                num_won=0
                for w in tree_won.keys():
                    if tmp[l][a].name in tree_won[w]:
                        num_won+=1
                award_descr=""
                for pc in tmp[l][a].PCs:
                    award_descr+=pc.Printable()+"<br />"
                    award_descr="<span style='color: #0687ed;'>"+award_descr+"</span>"
                f.write("<td style='padding: 1em; font-size: 10pt;'>%s%s</td>" % (get_tree_stats_image(tmp[l][a],num_won,len(students)),award_descr))
#                elif tmp[l][a].name in unlocked:
#                    f.write("<td style='padding: 1em; font-size: 10pt;'>%s%s</td>" % (get_tree_image(tmp[l][a]),award_descr#))
#                else:
#                    award_descr="<span style='color: #aaaaaa;'>"+award_descr+"</span>"
#                    f.write("<td style='padding: 1em; font-size: 10pt;'>%s%s</td>" % (get_tree_image(tmp[l][a],locked=1),award_descr))

    f.write("""

</td>
</tr>
</table>\n
</div> <!-- table-wrapper -->""")
    write_page_footer(f)
    f.close()



import colorsys

def hex_to_rgb(value):
    value = value.lstrip('#')
    lv = len(value)
    return tuple(int(value[i:i+lv/3], 16) for i in range(0, lv, lv/3))

def rgb_to_hex(rgb):
    return '#%02x%02x%02x' % rgb

def get_tree_stats_image(tree_award,num_won,max_students):
    if tree_award==None:
        return ""
    else:
        tree_award_name=tree_award.name
        color=tree_award.color
        color=[x/255.0 for x in hex_to_rgb(color)]
        (h,s,v)=colorsys.rgb_to_hsv(color[0],color[1],color[2])
        v=v/4+num_won*v/(max_students*0.75)
        (r,g,b)=colorsys.hsv_to_rgb(h,s,v)
        color=rgb_to_hex((int(r*255),int(g*255),int(b*255)))
        xp = tree_award.xp
        return "<div title='%s - %sxp\n\nClick for description' onclick='document.location.href=\"tree/%s.html\"' style='width:60px; height:60px; color: white; margin: auto; background-color: %s; text-align: left; font-size: 10pt; padding:5px;'>%s<br />%s</div>" % (tree_award_name,xp,tree_award_name.replace(" ",""),color,tree_award_name,num_won)





def gen_pages(students, achievement_list, awards, indicators, tree, tree_won, tree_unlocked,logs):
    gen_leaderboard(students, achievement_list, awards)
    gen_student_pages(students, achievement_list, awards, indicators, tree, tree_won, tree_unlocked,logs)
    write_overall_XP_list(students,achievement_list, awards)
    gen_achievements_page(students, achievement_list,awards)
    gen_tree_page(students, achievement_list, awards, indicators, tree, tree_won, tree_unlocked,logs)

###############################################################################
###############################################################################
#################################   Stats!  ###################################
###############################################################################
###############################################################################


def WeeklyStats(logs):
    numweeks = 28
    d1 = datetime.datetime(2010,1,31)
    delta = datetime.timedelta(7)
    d2 = d1+delta
    res=[]
    for n in range(0,numweeks):
        tmp=[]
        dd1 = time.mktime(d1.timetuple())
        dd2 = time.mktime(d2.timetuple())
        for l in logs:
            if (l.timestamp>=dd1) and (l.timestamp<dd2):
                tmp=tmp+[l]
        res=res+[tmp]
        d1=d2
        d2=d2+delta
    return res


def stats(logs):
    all_logs={}
    for l in logs:
#        if not l in [u"Joaquim Jorge",u"Daniel Gon�alves",u"Sandra Gama"]:
            all_logs[l]=logs[l]
    print "Resource View"
    res=WeeklyStats(FilterLogsGlobal(all_logs,action="resource view"))
    for r in res:
        print len(r)
    for i in ["Lecture 1","Lecture 2","Lecture 3","Lecture 4","Lecture 5",
              "Lecture 6","Lecture 7","Lecture 8","Lecture 9","Lecture 10",
              "Lecture 11","Lecture 12","Lecture 13","Lecture 14","Lecture 15",
              "Lecture 16","Lecture 17","Lecture 18","Lecture 19"]:
        print i
        res=WeeklyStats(FilterLogsGlobal(all_logs,action="resource view", info=i))
        for r in res:
            print len(r)
    print "forum add post"
    res=WeeklyStats(FilterLogsGlobal(all_logs,action="forum add post"))
    for r in res:
        print len(r)
    print "forum add discussion"
    res=WeeklyStats(FilterLogsGlobal(all_logs,action="forum add discussion"))
    for r in res:
        print len(r)




##def verify_stats(logs, students, tree):
##    res={}
##    for s in students:
##        res[s.num]={"T":0,"A":0,"V":0,"I":0,"D":0}
##        if logs.has_key(s.num):
##          for l in logs[s.num]:
##              if l.action == "stat award":
##                  data = l.info.strip().split(",")
##                  for d in data:
##                      stat,val = d.split(":")
##                      stat=stat.strip()
##                      val = int(val.strip())
##                      res[s.num][stat]+=val
##    return res
##

def substringin(s,lst):
    for l in lst:
        if s in l:
            return True
    return False

import pprint

def satisfied_tree(students,logs,tree, indicators):
    global _debug

    won = {}
    unlocked = {}
    achieved={}
    for student in students:
        tree_awards, vals, totals=FilterGrades(logs,student,forum="Skill Tree",grades=[3,4,5],max_repeats=None)
        lines = tree_awards
        tree_awards=[l.info[1] for l in tree_awards]
#        if student.num=="83512":
#            print tree_awards, vals, totals
        res=[]
        res2=[]
        for ta in tree:
            if substringin(ta.name,tree_awards):
#                if student.num=="83512":
#                  _debug = True
#                  print ta.name, ta.Satisfied(tree_awards)
#                else:
#                  _debug = False
                if ta.Satisfied(tree_awards):
                    res2.append(ta.name)
                    achieved[student.num] = achieved.get(student.num,[])+[Award(student.num,"Skill Tree",0,int(ta.xp),False,time.time(),ta.name)]
                    if not student.num in indicators:
                        indicators[student.num]={}
                    for l in range(0,len(lines)):
                        if ta.name in lines[l].info[1]:
                            indicators[student.num][ta.name]=(vals[l],[lines[l]])
                else:
#                    pass
                    print "Locked Tree Attempt",ta.name,"from",student.name

            else:
                if ta.Satisfied(tree_awards):
                    res.append(ta.name)
        won[student.num] = res2
        unlocked[student.num] = res
    return won, unlocked, achieved, indicators



def read_tree():
    res=[]
    preconds = []
    for s in csv.reader(codecs.open(os.path.join(METADIR,"tree.txt"),"r","utf8"),delimiter=";"):
        if len(s)>0:
            (level,name,pcs,color,xp) = s
            pcs=pcs.strip()
            pcs = pcs.split("|") #different possible preconditions
            preconds=[]
            for pc in pcs:
                if len(pc)>0:
                    nodes = pc.split("+")
                    preconds.append(PreCondition(nodes))
                else:
                    nodes=[]
            res.append(TreeAward(name,int(level),preconds,color,xp))
#    print res
    return res


def get_unlocks(students,awards):
    unlocks={}
    unlocked={}
    for s in csv.reader(codecs.open(os.path.join(AVATARDIR,"achievement_unlocks.csv"),"r","utf8"),delimiter=";"):
        if len(s)>0:
            (name, u1, u2, u3) = s
            unlocks[name]=(u1,u2,u3)
    for a in awards:
        try:
            unlocked[a.student]=unlocked.get(a.student,[])+[unlocks[a.achievement][a.level]]
        except:
            try:
                if not a.level:
                    unlocked[a.student]=unlocked.get(a.student,[])+[unlocks[a.achievement+":"+a.info][a.level]]
            except:
                pass
    return unlocked


def gen_unlocks(students,awards,achievement_list):
    unlocked=get_unlocks(students,awards)
    f=codecs.open(os.path.join(AVATARDIR,"student_unlocks.csv"),"w","utf8")
    for s in students:
        if unlocked.has_key(s.num):
            xp, plus, minus, tree = count_xp(s,awards,achievement_list)
            level = compute_level(xp)
            res=u"%s;student;%s;%s;%s\n" % (s.name,xp,level,",".join(unlocked[s.num]))
            f.write(res)
        else:
            xp, plus, minus, tree = count_xp(s,awards,achievement_list)
            level = compute_level(xp)
            res=u"%s;student;%s;%s;\n" % (s.name,xp,level)
            f.write(res)
    f.close()


###############################################################################
###############################################################################
##################################   Main!  ###################################
###############################################################################
###############################################################################


def MergeAchievements(old,new):
    for s in new.keys():
        old[s]=old.get(s,[])+new[s]
    return old

import codecs

def save_indicators(indicators):
    print "\nSaving Indicators"
    f = codecs.open(os.path.join(METADIR,"indicators.json"),"w","ascii")
    f.write("[")
    for i in indicators.keys()[:-1]:
        #print i, indicators[i]
        f.write('{ "num": "%s", "indicators": %s },\n' % (i, str(indicators[i]).replace("u'","'").replace('u"','"').replace("None","null").replace('False',"False").replace("(","[").replace(")","]").replace(", '",', "').replace("':",'":').replace("{'",'{"').replace("['",'["').replace("',",'",')))

    if len(indicators)>0:
        f.write('{ "num": "%s", "indicators": %s }\n' % (indicators.keys()[-1], str(indicators[indicators.keys()[-1]]).replace("u'","'").replace('u"','"').replace("None","null").replace('False',"False").replace("(","[").replace(")","]").replace(", '",', "').replace("':",'":').replace("{'",'{"').replace("['",'["').replace("',",'",')))
    f.write("]")
    f.close()


def export_grades(students,awards,achievement_list):
  f=codecs.open("grades.txt",encoding="utf8",mode="w")
  for s in students:
    xp, plus, minus, tree = count_xp(s,awards,achievement_list)
    level = compute_level(xp)
    f.write("%s;%s;%s;%s\n" % (s.num,s.name,level,s.campus))
  f.close()


def export_loglines(lines):
  f = codecs.open("logs/loglines.csv","w","latin1")
  f.write("num;name;timestamp;action;xp;info;url\n")
  for s in lines.keys():
      for l in lines[s]:
          try:
            f.write(u"%s;%s;%s;%s;%s;%s;%s\n" % (
                                         l.num,
                                         l.name,
                                         l.timestamp,
                                         l.action,
                                         l.xp,
                                         l.info,
                                         l.url))
          except:
            print "*",
  f.close()


def save_tree_won(won):
    f = open("tree_won.txt","w")
    for w in won.keys():
        f.write(w+",")
        f.write(str(len(won[w])))
        for a in won[w]:
            f.write(",%s" % a)
        f.write("\n")
    f.close()


def print_award_stats(awards):
    students = []
    counts = {}

    for a in awards:
        if not a.student in students:
            students.append(a.student)
    for s in students:
        count = 0
        for a in awards:
             if a.student == s:
                 if a.achievement == "Skill Tree":
                     count += 1
        counts[count] = counts.get(count,[]) + [s]
    kk = counts.keys()
    kk.sort()
    for k in kk:
      print("%d Skills - %d " % (k, len(counts[k])))


def main():
    tree = read_tree()
    students = read_student_list()
    logs=read_log_files(students)
#    export_loglines(logs)
#    return
    achievement_list=read_achievements()
    achieved, indicators=verify_achievements(logs, students, achievement_list)
    tree_won,tree_unlocked, tree_achieved, indicators = satisfied_tree(students,logs,tree,indicators)
    save_tree_won(tree_won)
    save_indicators(indicators)
    achieved = MergeAchievements(achieved,tree_achieved)
    awards=commit_awards(achieved, achievement_list)
    print_award_stats(awards)
    #gen_pages(students, achievement_list, awards, indicators, tree, tree_won, tree_unlocked,logs)
    #gen_unlocks(students, awards, achievement_list)
    export_grades(students,awards,achievement_list)
    print
    print "Done! at ", time.strftime("%d %B %Y, %H:%M %p")
#    print "Press <return> to exit"
#    sys.stdin.readline()






if __name__=="__main__":
    main()
