create table PCMLogs(
id int unsigned auto_increment primary key,
course int unsigned not null, 
Num varchar(200) null, 
Name varchar(200) null, 
AT varchar(200) null, 
Action varchar(200) null, 
XP varchar(200) null, 
Info varchar(200) null, 
PossibleActions varchar(200) null, 
foreign key(course) references course(id) on delete cascade
);