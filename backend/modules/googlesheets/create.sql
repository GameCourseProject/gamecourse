create table googlesheets_config(
	id 		                int unsigned PRIMARY KEY AUTO_INCREMENT,
	course                  int unsigned NOT NULL,
	key_                    varchar(200) NOT NULL,
	clientId                varchar(200) NOT NULL,
	projectId               varchar(200) NOT NULL,
	authUri                 varchar(200) NOT NULL,
	tokenUri                varchar(200) NOT NULL,
	authProvider            varchar(200) NOT NULL,
	clientSecret            varchar(200) NOT NULL,
	redirectUris            varchar(500) NOT NULL,
	authUrl                 varchar(500) NULL,
	accessToken             varchar(200) NULL,
	expiresIn               varchar(200) NULL,
	scope                   varchar(200) NULL,
	tokenType               varchar(200) NULL,
	created                 varchar(200) NULL,
	refreshToken            varchar(500) NULL,
	spreadsheetId           varchar(200) NULL,
	sheetName               varchar(200) NULL,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);
