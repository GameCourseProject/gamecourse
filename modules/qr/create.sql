create table qr_code(
	qrkey varchar(50) not null,
	course int unsigned not null,
	studentNumber int unsigned,
	classNumber int,
	classType  varchar(50),
	foreign key (course) references course(id) on delete cascade
);
create table qr_error(
	studentNumber int unsigned not null,
	course  int unsigned not null,
	major varchar(8),
	ip varchar(50),
	qrkey varchar(50), 
	msg varchar(500),
	date timestamp default CURRENT_TIMESTAMP
);
create table config_qr(
	id 		int unsigned auto_increment primary key,
	course  int unsigned not null,
	isEnabled boolean,
	periodicityNumber int,
	periodicityTime varchar(25),
	foreign key (course) references course(id) on delete cascade
)