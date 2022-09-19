#!/usr/bin/env python
# -*- coding: utf-8 -*-


from context import parser, Rule, Block, Statement, BaseNode
from context import ParseError

def assert_block(test,result,s=None,f=None):
	test.assertIsInstance(result,Block)
	test.assertIsInstance(result,BaseNode)
	test.assertIsInstance(result.stmts(),list)
	test.assertIsInstance(result.fpath(),str)
	test.assertIsInstance(s,list)
	test.assertEqual(len(result.stmts()),len(s))
	test.assertEqual(result.stmts(),s)
	for i in range(len(s)):
		test.assertEqual(result.stmts()[i],s[i])
		test.assertEqual(result.stmts()[i].path(),result.path())
	if isinstance(f,str):
		test.assertEqual(result.fpath(),f)

# def assert_statement(test,result,s,f,l,b,t):
# 	test.assertIsInstance(result,Statement,"Expected type Statement")
# 	test.assertIsInstance(result,BaseNode,"Statement must inherit from BaseNode")
# 	test.assertIsInstance(result.text(),str,"Expected type String")
# 	test.assertIsInstance(result.fpath(),str,"Expected type String")
# 	test.assertIsInstance(result.line(),(int,long),"Expected type Int or Long")
# 	# test.assertIsInstance(result.isassignment(),bool,"Expected type Bool")
# 	# test.assertIsInstance(result.type(),int,"Expected type Int")
# 	test.assertEquals(result.text(),s)
# 	test.assertEquals(result.fpath(),f)
# 	test.assertEquals(result.line(),l)
	# test.assertEquals(result.isassignment(),b)
	# test.assertEquals(result.type(),t)
	# if isinstance(s,basestring): test.assertEquals(result.text(),s)
	# else: raise TypeError("Expected type String")
	# if isinstance(f,basestring): test.assertEquals(result.fpath,f)
	# else: raise TypeError("Expected type String")
	# if isinstance(l,(int,long)): test.assertEquals(result.line,l)
	# else: raise TypeError("Expected type Int or Long")
	# if isinstance(b,bool): test.assertEquals(result.is_assignment,b)
	# else: raise TypeError("Expected type Bool")
	# if isinstance(t,(int,long)): test.assertEquals(result.type,t)
	# else: raise TypeError("Expected type Int or Long")

# def assert_list_rules (test, l1, l2):
# 	test.assertIsInstance(l1,list,"Expected object type \'list\'")
# 	test.assertIsInstance(l2,list,"Expected object type \'list\'")
# 	test.assertEquals(len(l1),len(l2))
# 	for i in xrange(len(l1)):
# 		test.assertIsInstance(l1[i],Rule)
# 		test.assertIsInstance(l2[i],Rule)
# 		assert_rules(test,l1[i],l2[i])
# 	test.assertEquals(l1,l2)

# def assert_result_parse_rule (test,text,pos=-1,n="",d="",p=None,a=None,f="",l=1):
# 	if not isinstance(p,Block): p = Block()
# 	if not isinstance(a,Block): a = Block()
# 	## Expected
# 	rule = Rule(n,d,p,a,f,l)
# 	if pos == -1:
# 		pos = len(text)
# 	line = 1 + text.count("\n")
# 	expected = (rule, pos, line)
# 	## Act
# 	result = parser.parse_rule(text)
# 	## Assert
# 	#### Compare Rule objects
# 	assert_rule(test,result[0],n,d,p,a,f,l)
# 	assert_rules(test,result[0],expected[0])
# 	#### Compare pos and line
# 	test.assertEqual(result[1:],expected[1:])

# def assert_rules(test,r1,r2):
# 	test.assertIsInstance(r1,Rule,"Expected object type Rule")
# 	test.assertIsInstance(r1,BaseNode)
# 	test.assertIsInstance(r2,Rule,"Expected object type Rule")
# 	test.assertIsInstance(r2,BaseNode)
# 	test.assertEqual(r1.name(),r2.name())
# 	test.assertEqual(r1.description(),r2.description())
# 	msg = "rules have different preconditions.\n"
# 	msg+= "Rule-1:\n" + str(r1) + "\n"
# 	msg+= "Rule-2:\n" + str(r2) + "\n"
# 	test.assertEqual(r1.preconditions(),r2.preconditions(),msg)
# 	msg = "rules have different actions.\n"
# 	msg+= "Rule-1:\n" + str(r1) + "\n"
# 	msg+= "Rule-2:\n" + str(r2) + "\n"
# 	test.assertEqual(r1.actions(),r2.actions(),msg)

# def assert_rule(test,r,n,d,p,a,f,l):
# 	test.assertIsInstance(r,Rule,"Expected object type Rule")
# 	test.assertIsInstance(r,BaseNode)
# 	test.assertIsInstance(r.name(),str)
# 	test.assertIsInstance(r.description(),str)
# 	test.assertIsInstance(r.preconditions(),Block)
# 	test.assertIsInstance(r.actions(),Block)
# 	test.assertIsInstance(r.path(),str)
# 	test.assertIsInstance(r.line(),(int,long))
# 	test.assertEqual(r.name(),n)
# 	test.assertEqual(r.description(),d)
# 	test.assertEqual(r.preconditions(),p)
# 	test.assertEqual(r.actions(),a)
# 	test.assertEqual(r.path(),f)
# 	test.assertEqual(r.path(),r.preconditions().path())
# 	test.assertEqual(r.path(),r.actions().path())
# 	test.assertEqual(r.line(),l)

def get_empty_rules ():
	from context import testfiles_path
	from os.path import join
	path = join(testfiles_path,'0 fields','rule_empty.txt')
	rules = []
	for i in range(12):
		rules.append(Rule(fpath=path))
	return rules                         
def get_rule_empty ():
	return get_empty_rules()
# def get_0fields_rules ():
# 	return get_empty_rules()

def get_1field_rules ():
	rules = []
	rules += get_rule_acts()
	rules += get_rule_desc()
	rules += get_rule_name()
	rules += get_rule_precs()
	return rules

def get_2fields_rules ():
	rules = []
	rules += get_rule_descXacts()
	rules += get_rule_descXprecs()
	rules += get_rule_nameXacts()
	rules += get_rule_nameXdesc()
	rules += get_rule_nameXprecs()
	rules += get_rule_precsXacts()
	return rules

def get_3fields_rules ():
	rules = []
	rules += get_rule_descXprecsXacts()
	rules += get_rule_nameXdescXacts()
	rules += get_rule_nameXdescXprecs()
	rules += get_rule_nameXprecsXacts()
	return rules

def get_allfields_rules ():
	return get_rule_nameXdescXprecsXacts()

# def get_all_rules():
# 	rules = []
# 	rules += get_rule_acts()
# 	rules += get_rule_desc()
# 	rules += get_rule_descXacts()
# 	rules += get_rule_descXprecs()
# 	rules += get_rule_descXprecsXacts()
# 	rules += get_empty_rules()
# 	rules += get_rule_name()
# 	rules += get_rule_nameXacts()
# 	rules += get_rule_nameXdesc()
# 	rules += get_rule_nameXdescXacts()
# 	rules += get_rule_nameXdescXprecs()
# 	rules += get_rule_nameXdescXprecsXacts()
# 	rules += get_rule_nameXprecs()
# 	rules += get_rule_nameXprecsXacts()
# 	rules += get_rule_precs()
# 	rules += get_rule_precsXacts()
# 	return rules

# def get_parse_block_iargs(test_id=None):
	# to_ignore = "!!invalid things, but, should be ignored .... (+__+) ...."
	# text = "\n\t\tvalid = True\r\v\f\n\t\tvalid is True\nend:"
	# t = to_ignore + text + to_ignore
	# p = len(to_ignore)
	# e = "end"
	# f = "test_parse_block_i"
	# if test_id: f += str(test_id)
	# else: f += "**" 
	# l = 3456789
	# s1 = Statement("valid = True",f,l)
	# s2 = Statement("valid is True",f,l)
	# block = Block([s1,s2],f)
	# pos = len(to_ignore+text)-len(e+":")
	# line = l + to_ignore.count("\n") + text.count("\n")
	# result = (block,pos,line)
	# return t,p,e,f,l,result

def get_parse_nblock_iargs(test_id=None):
	# Input
	f = "test_parser.py::TestParser::test_parse_named_block_i"
	f += str(test_id) if test_id else "**"
	# if test_id:
	# 	f += str(test_id)
	# else:
	# 	f += "**"
	block = Block([],f)
	n = "blockname"
	l = 19
	
	t = "\t"*14 + "\n"*514 + n + "	 \f"*51 + ":" + "\n\n\n\t\t\n" * 20
	line = l + t.count("\n")
	s = Statement("valid=True",f,line)
	block.add_stmt(s)
	t += "valid=True" + "  	 	  	 \f\v\r"*44 + "\n" # assignment-1

	line = l + t.count("\n")
	s = Statement("text = True",f,line)
	block.add_stmt(s)
	t += "text =" + " 	 \v\t 	\r"*5 +"True\n" # assignment-2

	line = l + t.count("\n")
	s = Statement("1 and \"string\" and 1.4 and 40%1",f,line)
	block.add_stmt(s)
	t += "1 and \"string\" and 1.4 and 40%1\n" # expression-1

	line = l + t.count("\n")
	s = Statement("valid and text",f,line)
	block.add_stmt(s)
	t += "valid and text\n" # expression-2

	line = l + t.count("\n")
	s = Statement("True or False",f,line)
	block.add_stmt(s)
	t += "True or False\n" # expression-3
	
	t += "\n"*4 + " 	 "*2 + "\r  \f"*5 # to ignore
	t_final = t + "stop:_ERROR this should NOT BE PARSED!! KABOOM!! THE END!"
	p = 13
	s = "stop"
	# Expected Output	
	pos = len(t)
	line = l + t.count("\n")
	result = (block, pos, line)
	return n,t_final,p,s,f,l,result


def get_rule_name():
	list_rules = []
	list_rules.append(Rule("simple ascii 	name"))
	list_rules.append(Rule("àèìòù áéíóú ãõ âêîôû äëïöü ç"))
	list_rules.append(Rule("ÀÈÌÒÙ ÁÉÍÓÚ ÃÕ ÂÊÎÔÛ ÄËÏÖÜ Ç"))
	list_rules.append(Rule(",;.:-_?!"))
	list_rules.append(Rule("::::::::::"))
	list_rules.append(Rule("@£€§\"\|/%&+*"))
	list_rules.append(Rule("<> «» { } [] () \"\" \'\'"))
	list_rules.append(Rule("0123456789"))
	list_rules.append(Rule("abcdefghijklmnopqrstuvwxyz"))
	list_rules.append(Rule("ABCDEFGHIJKLMNOPQRSTUVWXYZ"))
	return list_rules

def get_rule_nameXdescXprecs ():
	l = []

	n = "Attack on Titan"
	d = """After his hometown is destroyed and his mother is killed,
young Eren Yeager vows to cleanse the earth of the giant
humanoid Titans that have brought humanity to the brink of
extinction."""
	p = Block()
	p.add_stmt(Statement("eren = \"titan\""))
	p.add_stmt(Statement("eren == \"titan\""))
	p.add_stmt(Statement("eren = \"human\""))
	p.add_stmt(Statement("eren == \"human\""))
	l.append(Rule(name=n,desc=d,precs=p))

	n = "with 	tabulation"
	d = """this 	rule		makes my

	eyes hurt 	







					a little!!


		when,: this is finnished
		I will
		rule,: the world!"""
	p = Block()
	p.add_stmt(Statement("my_work = \"finished\""))
	p.add_stmt(Statement("my_work == \"finished\" == True"))
	p.add_stmt(Statement("my_feelings = \"joy\""))
	l.append(Rule(name=n,desc=d,precs=p))

	n = "Bring on the Titans!"
	d = """it's about 
	time they air

	season 3 of Attack on Titan"""
	p = Block()
	p.add_stmt(Statement("season_3 = True"))
	p.add_stmt(Statement("happening = True"))
	p.add_stmt(Statement("excitement = season_3 == happening and \"very high\""))
	p.add_stmt(Statement("303 >= len(excitement)**(season_3+happening)"))
	l.append(Rule(name=n,desc=d,precs=p))

	return l

def get_rule_nameXdescXprecsXacts ():
	l = []

	n = "gladiator"
	d = """When a Roman General is betrayed, and his family murdered by an emperor's
corrupt son, he comes to Rome as a gladiator to seek revenge."""
	p = Block()
	a = Block()
	p.add_stmt(Statement("director = 'Ridley Scott'"))
	p.add_stmt(Statement("stars = [\"Russell Crowe\", \"Joaquin Phoenix\", \"Connie Nielsen\"]"))
	p.add_stmt(Statement("russel = \"Russell Crowe\""))
	p.add_stmt(Statement("russel in stars"))
	a.add_stmt(Statement("distances = sum(map(len,stars))"))
	a.add_stmt(Statement("distances > 10"))
	l.append(Rule(name=n,desc=d,precs=p,acts=a))
	l.append(Rule(name=n,desc=d,precs=p,acts=a))
	l.append(Rule(name=n,desc=d,precs=p,acts=a))

	return l

def get_rule_desc():
	l = []

	s = "valid description with ascii chars"
	l.append(Rule(desc=s))
	
	s = """E quando Carlos Eduardo
		pousou o olhar em Maria Eduarda,
		nunca mais foi o mesmo ..."""
	l.append(Rule(desc=s))
	
	s = """à
	è
	ì 
	ò 
	ù 

	áéíóú 

	äëïöü  ãõ âêîôû ç
	ÁÉÍÓÚ ÀÈÌÒÙ ÄËÏÖÜ ÃÕ ÂÊÎÔÛ Ç"""
	l.append(Rule(desc=s))

	s = ",.-_:;ºª+*?=/&$!€|\n1234567890"
	l.append(Rule(desc=s))

	s = "{[(\"\'\'«»\")]}"
	l.append(Rule(desc=s))

	return l

def get_rule_precs ():
	l = get_list_block()
	rules = [Rule(precs=b) for b in l]
	return rules

def get_rule_acts ():
	l = get_list_block()
	rules = [Rule(acts=b) for b in l]
	return rules

def get_list_block ():
	l = []
	# Rule 1
	b = Block()
	b.add_stmt(Statement("1234567890"))
	b.add_stmt(Statement(".0123456789"))
	b.add_stmt(Statement("767"))
	b.add_stmt(Statement("0xabcdef"))
	b.add_stmt(Statement("-2"))
	b.add_stmt(Statement("\"what?\""))
	b.add_stmt(Statement("True"))
	b.add_stmt(Statement("False"))
	b.add_stmt(Statement("[1,2,3]"))
	b.add_stmt(Statement("(True, False)"))
	b.add_stmt(Statement("{\"a\":0,\"j\":1,\"m\":-1}"))
	l.append(b)
	# Rule 2
	b = Block()
	b.add_stmt(Statement("a = 2"))
	b.add_stmt(Statement("b = a"))
	b.add_stmt(Statement("c = b"))
	b.add_stmt(Statement("a"))
	b.add_stmt(Statement("b"))
	b.add_stmt(Statement("c"))
	l.append(b)
	# Rule 3
	b = Block()
	b.add_stmt(Statement("a = 2"))
	b.add_stmt(Statement("((a + a) * a) - a / a % a ** (a // a)"))
	b.add_stmt(Statement("b = True"))
	b.add_stmt(Statement("b == 1 != False != True >= False <= b > 0 < 1"))
	b.add_stmt(Statement("1 and a or b"))
	b.add_stmt(Statement("a += b"))
	b.add_stmt(Statement("b -= a + 1"))
	b.add_stmt(Statement("a *= a"))
	b.add_stmt(Statement("a /= b"))
	b.add_stmt(Statement("a %= a"))
	b.add_stmt(Statement("a **= a"))
	b.add_stmt(Statement("a //= a"))
	b.add_stmt(Statement("a = 0"))
	b.add_stmt(Statement("b = 1"))
	b.add_stmt(Statement("~ a & b | b ^ a << a >> a"))
	b.add_stmt(Statement("c = [\"string\",1,False, {a:b}, (a,b), [a,b]]"))
	b.add_stmt(Statement("a in c"))
	b.add_stmt(Statement("b not in c"))
	b.add_stmt(Statement("type(c) is list"))
	l.append(b)

	return l

def get_rule_descXacts ():
	l = []

	d = """the nature
	of man
	
	
	is savage"""
	a = Block()
	a.add_stmt(Statement("deactivated = False"))
	a.add_stmt(Statement("trap = False"))
	a.add_stmt(Statement("trap == deactivated"))
	a.add_stmt(Statement("comment = \"I will NOT, fall for that,\""))
	a.add_stmt(Statement("comment += \"not again I wil not!\""))
	a.add_stmt(Statement("say = \"not again I wil not!\""))
	a.add_stmt(Statement("say in comment"))
	a.add_stmt(Statement("comment = \"False Trap!\""))
	l.append(Rule(desc=d,acts=a))

	d = """whoo
that other
rule, sure was long, wasn't it Watson?"""
	a = Block()
	a.add_stmt(Statement("watson = \"Sure, Sherlock!\""))
	a.add_stmt(Statement("watson = \"But if you think I forgot what just happened,\""))
	a.add_stmt(Statement("watson = \" you\'re in a for a big treat.\""))
	a.add_stmt(Statement("watson == \"staring intensely\""))
	l.append(Rule(desc=d,acts=a))

	return l

def get_rule_descXprecs ():
	l = []

	d = """this nameless rules
	isn't exactly
		useful
but is good for testing"""
	p = Block([])
	p.add_stmt(Statement("number = 123"))
	p.add_stmt(Statement("number > 0"))
	p.add_stmt(Statement("number = -23"))
	p.add_stmt(Statement("isinstance(number, int)"))
	p.add_stmt(Statement("\"rule:\""))
	p.add_stmt(Statement("\"then:\""))
	l.append(Rule(desc=d,precs=p))

	d = """my my"""
	p = Block([])
	p.add_stmt(Statement("True"))
	l.append(Rule(desc=d,precs=p))

	return l

def get_rule_nameXacts():
	l = []

	n = "my oh my"
	a = Block([])
	a.add_stmt(Statement("1+2"))
	l.append(Rule(name=n,acts=a))

	n = "another one ..."
	a = Block([])
	a.add_stmt(Statement("a = \"a\""))
	a.add_stmt(Statement("b = \"baba\""))
	a.add_stmt(Statement("a in b"))
	l.append(Rule(name=n,acts=a))

	n = "__init__"
	a = Block([])
	a.add_stmt(Statement("1 * 4"))
	a.add_stmt(Statement("f = 3 < 4"))
	a.add_stmt(Statement("f is True and not False"))
	l.append(Rule(name=n,acts=a))

	return l

def get_rule_descXprecsXacts ():
	l = []

	d = """whithout saying the name of the movie,
which is the title. You can already guess.
In fact you already started to."""
	p = Block()
	p.add_stmt(Statement("\"rule is over\""))
	p.add_stmt(Statement("\"you have already found the answer\""))
	p.add_stmt(Statement("year = 1997"))
	p.add_stmt(Statement("movie = \"unknown\""))
	p.add_stmt(Statement("movie == \"unknown\""))
	a = Block()
	a.add_stmt(Statement("time_slows_down = True"))
	a.add_stmt(Statement("persons_dodge_bullets = True"))
	a.add_stmt(Statement("black_suits = True"))
	a.add_stmt(Statement("the_one = True"))
	a.add_stmt(Statement("time_slows_down and the_one"))
	a.add_stmt(Statement("movie = \"The Matrix (1997)\""))
	l.append(Rule(desc=d,precs=p,acts=a))
	return l

def get_rule_nameXdesc ():
	"""
	return a list of tuples,
	each tuple contains a name and a description
	"""
	l = []

	n = "simple name"
	d = "this rule does nothing"
	l.append(Rule(name=n,desc=d))
	
	n = "Talkative"
	d = """Participate in Theoretical Lectures!
lvl1: participate 2 times (+100xp)
lvl2: participate 6 times (+100xp)
lvl3: participate 12 times (+100xp)"""
	l.append(Rule(name=n,desc=d))

	n = "Attentive Student"
	d = """Find relevant bugs in class materials
		get four points - +50 XP
	get eight points - +50 XP
get twelve points - +50 XP"""
	l.append(Rule(name=n,desc=d))

	n = "Wild Imagination;Suggest presentation subjects;sugest a new subject for your presentation;;;350;;;False;True;False;;;Presentation Zen Master;Think about your presentation before opening powerpoint;hand in document about the rationalle of your presentation.;;;250;;;False;False;False;;;"
	d = """Popular Choice Award;Have the most liked multimedia presentation;be the third most liked;be the second most liked;be the most liked!;-50;-50;-50;False;False;False;;;

Hollywood Wannabe;Create great videos for your presentation;remixed video;created own video (single shoot);created own video, relevant edits;150;150;150;False;False;False;;;

Golden Star;Be creative and do relevant things to help improve the course;perform one task;perform two tasks;perform three tasks;-100;-100;-100;False;False;False;;;

Lab Master;Excel at the labs;top grade in four graded classes;top grade in six graded classes;top grade in all graded classes;0;0;0;True;False;False;4;6;9

Quiz Master;Excel at the quizzes;top grade in four quizzes;top grade in six quizzes;top grade in eight quizzes;0;0;0;True;False;False;4;6;8

Post Master;Post something in the forums;make twenty posts;make thirty posts;make fifty posts;0;0;0;True;True;False;;;

Book Master;Read class slides;read slides for 50% of lectures;read slides for 75% of lectures;read all lectures slides;0;0;0;True;False;False;;;

Tree Climber;Reach higher levels of the skill tree;reach level two;reach level three;reach level four;0;0;0;False;True;False;;;

Lab King;Attend the labs, be the best;Have the highest grade in the labs;;;-80;;;False;False;False;;;

Presentation King;Present your thing, be the best;Have the highest grade in the presentations;;;-80;;;False;False;False;;;

Quiz King;Take the quizzes, be the best;Have the highest grade in the quizzes!;;;-80;;;False;False;False;;;

Course Emperor;Take the course, be the best;Have the highest course grade!;;;-80;;;False;False;False;;;"""
	l.append(Rule(name=n,desc=d))

	return l

def get_rule_nameXdescXacts ():
	l = []

	n = "berserk (1997)"
	d = """Guts is a skilled swordsman who joins forces with a mercenary group named
'The Band of the Hawk', lead by the charismatic Griffith, and fights with
them as they battle their way into the royal court."""
	a = Block()
	a.add_stmt(Statement("guts = 100 * \"men\""))
	a.add_stmt(Statement("weapon = \"sword\""))
	a.add_stmt(Statement("len(guts + weapon) >= len(\"a big army!\")"))
	l.append(Rule(name=n,desc=d,acts=a))

	n = "weapons of 	war"
	d = """Humans proved themselves remarkably ingenuous and adaptable
when it came to finding new ways to maim and kill during the First World War.
		The list below explores many of the
weapons used to produce millions of casualties in four short years."""
	a = Block()
	a.add_stmt(Statement("rifle = 4"))
	a.add_stmt(Statement("machine_guns = 5"))
	a.add_stmt(Statement("flamethrowers = 2"))
	a.add_stmt(Statement("mortars = 6"))
	a.add_stmt(Statement("poison_gas = 2"))
	a.add_stmt(Statement("tanks = 20"))
	a.add_stmt(Statement("tanks and mortars + rifle is machine_guns * flamethrowers"))
	l.append(Rule(name=n,desc=d,acts=a))

	return l


def get_rule_nameXprecs ():
	l = []
	
	p = Block([])
	n = "Class Annotator"
	p.add_stmt(Statement("contributions = 8"))
	p.add_stmt(Statement("contributions > 8"))
	p.add_stmt(Statement("contributions < 10"))
	l.append(Rule(name=n,precs=p))
	
	p = Block([])
	n = "oppsidussi"
	s = Statement("i=0")
	p.add_stmt(s)
	s = Statement("j=10+i")
	p.add_stmt(s)
	s = Statement("j**i")
	p.add_stmt(s)
	s = Statement("s = \"miscellaneous\"")
	p.add_stmt(s)
	s = Statement("len(s) > j")
	p.add_stmt(s)
	s = Statement("True")
	p.add_stmt(s)
	l.append(Rule(name=n,precs=p))

	p = Block([])
	n = "whaaaaaaaaaaaaaaaaaaaaaaaaat???"
	s = Statement("1")
	p.add_stmt(s)
	s = Statement("2")
	p.add_stmt(s)
	s = Statement("3")
	p.add_stmt(s)
	s = Statement("a = 1")
	p.add_stmt(s)
	s = Statement("a > 0")
	p.add_stmt(s)
	s = Statement("a = -1")
	p.add_stmt(s)
	l.append(Rule(name=n,precs=p))

	return l

def get_rule_nameXprecsXacts ():
	l = []

	n = "Charles Chaplin"
	p = Block()
	p.add_stmt(Statement("charlie = \"fenomenal actor\""))
	p.add_stmt(Statement("\"makes a movie about \" + charlie"))
	a = Block()
	a.add_stmt(Statement("charlie == \"fenomenal actor\""))
	a.add_stmt(Statement("charlie += \"a well deserved Oscar\""))
	a.add_stmt(Statement("\"Oscar\" in charlie"))
	l.append(Rule(name=n,precs=p,acts=a))

	return l

def get_rule_precsXacts ():
	l = []

	p = Block()
	p.add_stmt(Statement("x = 909.0"))
	p.add_stmt(Statement("not isinstance(x, int)"))
	a = Block()
	a.add_stmt(Statement("isinstance(x,float)"))
	l.append(Rule(precs=p,acts=a))

	return l

# def get_parse_block_iargs(test_id=None):
# 	to_ignore = "!!invalid things, but, should be ignored .... (+__+) ...."
# 	text = "\n\t\tvalid = True\r\v\f\n\t\tvalid is True\nend:"
# 	t = to_ignore + text + to_ignore
# 	p = len(to_ignore)
# 	e = "end"
# 	f = "test_parse_block_i"
# 	if test_id: f += str(test_id)
# 	else: f += "**" 
# 	l = 3456789
# 	s1 = Statement("valid = True",f,l)
# 	s2 = Statement("valid is True",f,l)
# 	block = Block([s1,s2],f)
# 	pos = len(to_ignore+text)-len(e+":")
# 	line = l + to_ignore.count("\n") + text.count("\n")
# 	result = (block,pos,line)
# 	return t,p,e,f,l,result

# def parse_rule_file_test(test,fpath,name,dirname):
# 	# arrange
# 	fn = "rule_" + name + ".txt"
# 	directory = dirname + "\\"
# 	fp = fpath + directory + fn
# 	### expected
# 	function = "get_rule_" + name + "()"
# 	expected = eval(function)
# 	# act
# 	result = parser.parse_file(fp)
# 	# assert
# 	assert_list_rules(test,result,expected)

# def parse_file_test(test,fpath,name,dirname):
# 	# arrange
# 	fn = "rule_" + name + ".txt"
# 	directory = dirname + "\\"
# 	fp = fpath + directory + fn
# 	### expected
# 	function = "get_rule_" + name + "()"
# 	expected = eval(function)
# 	# act
# 	result = parser.parse(fp)
# 	# assert
# 	assert_list_rules(test,result,expected)

# def test_parse_block_raises(test,t,p,e,f,l,msg=None,s=[]):
# 	with test.assertRaises(ParseError) as cm:
# 		parser.parse_block(t,p,e,f,l,s)
# 	test.assertEqual(cm.exception.val,msg)

def test_parse_comment_aux(test, comment, pos=None):
	result = (pos)
	test.assertEqual(parser.parse_comment(comment),result)

def test_parse_description_aux(test,description,formated=None,pos=None,line=None):
	if pos is None:
		pos = len(description)
	if line is None:
		line = 1 + description.count("\n")
	if formated is None:
		result = (description, pos, line)
	else:
		result = (formated, pos, line)
	test.assertEqual(parser.parse_description(description),result)

def test_parse_name_aux(test, name, formated_name=None, pos=None):
	if pos is None:
		pos = len(name)
	if formated_name is None:
		result = (name,pos)
	else:
		result = (formated_name,pos)
	test.assertEqual(parser.parse_name(name),result)

def test_parse_nblock_raises(test,n,t,p,f,l,msg):
	with test.assertRaises(ParseError) as cm:
		parser.parse_named_block(n,t,p,fpath=f,line=l)
	test.assertEqual(cm.exception.val,msg)

# def update_block(block,text,stmt,raw):
# 	l = 1 + text.count("\n")
# 	s = Statement(stmt,block.fpath(),l)
# 	block.add_stmt(s)
# 	text += raw
# 	return block, text