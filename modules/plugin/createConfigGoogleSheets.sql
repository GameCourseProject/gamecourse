create table config_google_sheets(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	spreadsheetId varchar(200) not null,
	sheetName varchar(200) null,
	sheetRange varchar(200) null,
	isEnabled boolean,
	foreign key(course) references course(id) on delete cascade
);
