create table config_moodle(
                              id 		int unsigned auto_increment primary key,
                              course int unsigned not null,
                              dbServer varchar(200) not null,
                              dbUser varchar(200) not null,
                              dbPass varchar(200) null,
                              dbName varchar(200)  not null,
                              dbPort int null,
                              tablesPrefix varchar(200) null,
                              moodleTime int null,
                              moodleCourse varchar(200) null,
                              moodleUser varchar(200) null,
                              isEnabled boolean,
                              periodicityNumber int,
                              periodicityTime varchar(25),
                              foreign key(course) references course(id) on delete cascade
);
