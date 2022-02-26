create table notifications_progress_report(
    id 		            int unsigned PRIMARY KEY AUTO_INCREMENT,
    course              int unsigned NOT NULL,
    periodicityTime     varchar(25) DEFAULT 'Weekly',
    periodicityHours    int DEFAULT 18,
    periodicityDay      int DEFAULT 5,
    isEnabled           boolean,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);