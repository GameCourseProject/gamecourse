create table classcheck_config(
	id 		                int unsigned PRIMARY KEY AUTO_INCREMENT,
	course                  int unsigned NOT NULL,
	tsvCode                 varchar(200) NOT NULL,
	isEnabled               boolean,
	periodicityNumber       int,
	periodicityTime         varchar(25),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);
