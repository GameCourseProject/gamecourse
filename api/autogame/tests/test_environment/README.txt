This is a folder created for the backing up of tables from the DB and course_data folder.

There are two general scripts for backing up data:
-> download_backup.py
    Runs copy_course_data.py and download_tables.py scripts
    Arguments: python3 download_backup.py [dbHost] [dbName] [dbUser] [dbPass]

    Saves a copy of:
        * All tables from DB into /tables/tables_files/ directory
        * Entire course_data folder into /course_data/course_data_files/ directory


-> upload_backup.py
    Runs paste_course_data.py and upload_tables.py scripts
    Arguments: python3 upload_backup.py [dbHost] [dbName] [dbUser] [dbPass]

    Opposite of download_backup.py
    Uploads the copy of the data from /tables/tables_files/ and /course_data/course_data_files/ directories
    into DB and course_data folder, respectively.



************************************************************************************************************************
************************************************************************************************************************

If any of these scripts is needed to run manually and/or individually:


    FOR COURSE_DATA:
    Inside directory ./course_data/ there are two scripts:
        -> copy_course_data.py
            Arguments: python3 copy_course_data.py [dbHost] [dbName] [dbUser] [dbPass] [backup]
            This script copies all the information inside the course_data folder into the course_data_files
            folder of this directory.

            Last argument [backup] is to be ignored when running individually. It's only used to indicate
            that the file is being called from the download_backup.py script.

        -> paste_course_data.py
            Arguments: python3 paste_course_data.py [dbHost] [dbName] [dbUser] [dbPass] [backup]
            This script pastes all the information from course_data_files into the course_data folder.

            Last argument [backup] is to be ignored when running individually. It's only used to indicate
            that the file is being called from the download_backup.py script.


    FOR TABLES:
    Inside directory ./tables/ there are two scripts:
        -> download_tables.py
        Arguments: python3 download_tables.py [dbHost] [dbName] [dbUser] [dbPass] [table_name] [backup]
        This script downloads information from the db's tables so later the DB can be re-instantiated for testing.

        Last argument its either [table_name] or [backup]:
            * table_name: When running this script only by itself, its optional to pass a specific table_name to download
            * backup: Indicates when this script is being called from the download_backup.py script

        -> upload_tables.py
        Arguments: python3 upload_tables.py [dbHost] [dbName] [dbUser] [dbPass] [table_name] [backup]
        This script uploads the information from cvs files inside the directory tables_files into the DB.
        It also acts as a re-instantiation of the DB for the testing environment.

        Last argument its either [table_name] or [backup]:
            * table_name: When running this script only by itself, its optional to pass a specific table_name to upload
            * backup: Indicates when this script is being called from the upload_backup.py script