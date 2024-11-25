# GameCourse

## Technology

- **Server**:<br>
<ins>Environment</ins>: **PHP 8.2.22** and **Python 3.11.2**<br>
You will need a server running PHP 8.2.22 version and a composer (dependency manager).<br>
For example, you can use _XAMPP_. Guides [here](#xampp).

- **Database**:<br>
<ins>Environment</ins>: **MySQL**<br>
You will need a MySQL Database.<br>
For example, you can use _phpMyAdmin_ for database management. Guides [here](#phpmyadmin).

- **Frontend**:<br>
<ins>Environment</ins>: **Angular 13**<br>
You will need to install _npm_ (dependency manager), _NodeJS_, and _Angular CLI_.

## Setup GameCourse - Localhost
This is a setup guide to run the project on your machine.

- **Backend**:<br>
  1. Copy the configuration file template (_api/inc/config.template.ts_) and rename it to _config.php_. Update its configuration variables.
  2. Uncomment on php.ini (if using xampp, on xampp/php/php.ini) the lines with _extension=zip_ and _extension=gd_ (remove the ';')
  3. Install all dependencies by running: ```composer install```
  4. Because of incompatibility of PHP version and project, we need to add #[\AllowDynamicProperties] to vendor/opis/closure/src/SerializableClosure.php and vendor/opis/closure/src/ClosureStream.php above the class declaration.
    ```
      #[\AllowDynamicProperties]
      class SerializableClosure implements Serializable { ... }
    ```
  5. Create Fénix app, if necessary (guides [here](#fenixapp)), and update configuration information from _api/inc/config.php_
  
- **Frontend**:<br>
  1. Copy the configuration file template (_frontend/src/environments/config.template.ts_) and rename it to _config.ts_. Update its configuration variables.
  2. Install all dependencies by running: ```npm install```
  3. Run Angular CLI server by running: ```ng serve```
  4. Access [http://localhost:4200/](http://localhost:4200/) and you will see GameCourse!

## Setup GameCourse - Production
If this project has not been set up in _Google Cloud_, jump to section [_Setup Google Cloud_](#googlecloud) first.

- **Frontend**:<br>
  1. Change API Endpoint (_frontend/src/environments/config.ts_)
  2. Change baseHref, e.g. /gamecourse/ (_frontend/angular.json_)
  3. Build project for production by running: ```npm run-script build:prod```
  4. Transfer everything in _/dist/gamecourse/_ to server
     
- **Backend**:<br>
  1. Change configuration variables (_api/inc/config.php_ & _api/modules/composer.json_)
  2. Create Fénix app, if necessary (guides [here](#fenixapp)), and update configuration information from _api/inc/config.php_
  3. Transfer files (ignore folders _legacy_, _cache_, _logs_, and _test_, vendor, as well as unnecessary files)
  4. Go to api folder (command ```cd /var/www/html/<folder_name>```) and install dependencies by running: ```sudo -u www-data composer install```
  5. Change directory permissions to www-data: ```sudo chown -R www-data:www-data /var/www/html/<folder_name>```
  6. Create database
  7. Delete file _api/setup/setup.done_ if exists

After all of these steps, you should be able to access [https://pcm.rnl.ulisboa.pt/project-name](https://pcm.rnl.ulisboa.pt/project-name) and set up the rest of GameCourse through the website (change "project-name" with the corresponding name). 

## Additional Guides
### Useful Software & Tools
  * **<a id="xampp"></a>XAMPP**: <br>
  XAMPP is a _PHP development environment_. It is a completely free, easy-to-install **Apache** distribution containing _MariaDB_, _PHP_, and _Perl_. See more [here](https://www.apachefriends.org/). <br><br>
  **How to Install?**
    - Download the latest version of XAMPP for **PHP 8.2** (version 8.2.22) [here](https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/).
    - Click on the installer and follow the installation wizard.
        - <ins>Select components</ins>: You must install **Apache**, **MySQL**, and **PHP**. The rest of the components are up to you, but phpMyAdmin is also useful for database management (more [here](https://www.siteground.com/tutorials/phpmyadmin/database-management/)).
    - Change MySQL server charset
        - Click on MySQL _Config_ > _my.ini_ in the **Control Panel**. This will open _my.ini_ file.
        - Edit UTF 8 Settings: ```character_set_server=utf8mb4```

          ![xampp1](https://github.com/GameCourseProject/gamecourse/assets/55749544/9c50f4cf-cd62-42b0-949c-312ee4258141)
          
    - (Optional): By default, XAMPP will serve documents in the _/xampp/htdocs_ directory. If you want to **change your Apache root directory**, click the Apache _Config_ > _Apache_ (httpd.conf) in the Control Panel and adjust the entries for the *DocumentRoot* and the corresponding Directory entry.
   
      ![xampp1](https://github.com/GameCourseProject/gamecourse/assets/55749544/4afa3632-1b02-4c40-9c7c-1053bfa0a32b)

   
    **How to use it?**
      - Once installed, you can go ahead and run XAMPP. This will open the XAMPP Control Panel which is where you will turn on/off your Apache and MySQL server.
      - (Optional) You can use **phpMyAdmin** for database management (more [here](https://www.siteground.com/tutorials/phpmyadmin/database-management/)).
      - (Optional) You can use XAMPP to run Python (guides [here](#xampp)).

* **<a id="phpmyadmin"></a>phpMyAdmin**: <br>
phpMyAdmin allows you to easily manage your databases using a Graphical User Interface instead of executing multiple SQL commands (though you can still do this as well). See more [here](https://www.phpmyadmin.net/).

  **How to Install?**<br>
  You probably already installed it while setting up XAMPP. If not, go back and add it to your XAMPP components list - guides [here](#xampp).<br>
  If you only want to update your phpMyAdmin version inside your XAMPP installation, follow this [guide](https://www.ostraining.com/blog/coding/update-phpmyadmin/).

  **Connecting to remote databases:**<br>
  It might be useful to have access to GameCourse's databases and Moodle's databases directly on phpMyAdmin. You can always connect to them via Terminal, but having a Graphical User Interface can be beneficial and easier to work with.
    - Open file _/xampp/phpMyAdmin/config.inc.php_
    - Add new servers you wish to connect to before "_End of servers configuration_"
    - **Connect to GameCourse database**:
      - Create a tunnel using Putty to a new port, 13306 for example
      - Increment your server counter and add configuration information for GameCourse's database
    - **Connect to Moodle database**:
      - Create a tunnel using Putty to a new port, 23306 for example
      - Increment your server counter and add configuration for Moodle's database

* **NodeJS & Angular**: <br>
  * To install dependencies in project use: ```npm install```
  * To run server use: ```ng serve``` 
  
* **WinSCP**: <br> 
  Is a free and open-source SSH File Transfer Protocol (SFTP), File Transfer Protocol (FTP), WebDAV, Amazon S3, and secure copy protocol (SCP) client for Microsoft Windows.
  * Downloading: You can obtain WinsSCP from the [WinSCP download page](https://winscp.net/eng/download.php). Follow the _Installation package link_.
  * Installing: Follow guides (here)[https://winscp.net/eng/docs/guide_install].

    You can also use PuTTY to complement WinSCP. PuTTY is a terminal emulator that provides a command-line interface to connect to remote servers. Follow [this guide](https://www.ibm.com/docs/en/flashsystem-5x00/8.2.x?topic=suscwh-configuring-putty-session-cli-3) for more.

* **Python**: <br>
  This is a guide to setting up Python as a server-side scripting language on your XAMPP installation **for Windows Operating System**. This was based off [this guide](https://blog.terresquall.com/2021/10/running-python-in-xampp/) (which also includes installation for MacOS).
  * Install Python: <br>
    To begin, check if you have **python** already installed. Use the following command in your Command Prompt (Windows): ```py --version``` <br>
    
    If the command outputs a version number, then Python is installed on your computer. Otherwise, you can download the Python installer [here](https://www.python.org/downloads/release/python-373/).
  
  * Add _Python_ to XAMPP's Apache: <br>
  Open the XAMPP Control Panel and click on **Config** > **Apache (httpd.conf)** <br>

  * Add support for .py files: <br>
  Once **_httpd.conf_** is open (you can open it with any text editor, such as Notepad), add the following lines **at the end of the file**: <br>
    ```
    AddHandler cgi-script .py
    ScriptInterpreterSource Registry-Strict
    ```

    (Optional) If you want XAMPP to automatically load **_index.py_** when a directory is accessed, find the following section in **_httpd.conf_** and add the highlighted portion below: <br>
      ```
      <IfModule dir_module>
        DirectoryIndex index.php index.pl index.cgi index.asp index.shtml index.html index.htm index.py \
                       default.php default.pl default.cgi default.asp default.shtml default.html default.htm default.py \
                       home.php home.pl home.cgi home.asp home.shtml home.html home.htm home.py
      </IfModule>
      ```
    This will cause **_index.py_**, **_default.py_** or **_home.py_** to be among the candidates to be loaded when a directory is accessed.
  
* Running your Python file: <br>
  To test if your setup works, you can copy the following file into the htdocs folder of your XAMPP installation. **Note that you will have to replace the first line with the path to Python**. <br>
  ```
  #!C:\Program Files\Python39\python.exe
  print("Content-Type: text/html\n\n")

  print("Hello world! Python works!")
  ```

  **The first two lines need to be included for Python file to work.** The first line tells Apache which program to run to interpret the file, while the second line outputs the file as a webpage.<br>
  If you don't know where Python is installed, in your computer go to **Start**, search for **Python**, the **Right-click** > **Open file location**. <br>
  With the file in _htdocs_, turn on Apache in XAMPP and you should be able to run the file by accessing ```http://localhost/python.py```. **Make sure to restart Apache on XAMPP before doing any testing**. This is so that the changes made to _httpd.conf_ will apply.

* Importing Python as PIP packages: <br>
  If you are looking to utilize additional packages from Python PIP, you'll need to add some additional lines of code on your Python script(s): <br>
    ```
    import sys
    sys.path.append("[YOUR PACKAGES FOLDER]")
    ```

  You will need to replace ```[YOUR PACKAGES FOLDER]``` above with the place where your package(s) were installed. If you don't know where it is, open **Command Prompt** and type the following: <br>
    ```pip show [YOUR PACKAGE NAME]```

### Create your own Fénix application
<a id="fenixapp"></a>If you don't already have a Fénix app for GameCourse, follow these steps:

- Go to **Personal** > **External Applications** > **Manage Applications** (this last tab appears as **API Terms**, if no app was created previously).
- Click on **Create** to create a FenixEdu application
  - Define **Site** with the desired website URL where GameCourse will run, for example, http://localhost/gamecourse/auth/ if running it locally.
  - Define **Redirect Url** as **<base website url>/auth/**, for example, http://localhost/gamecourse/auth/ if running it locally.
  - In **Scope**, select "Information".
- After its creation, it will appear in the application list. In **Edit**, we have access to the **Client Id** and the **Client secret**, which will be used in the next step.
- Go to _api/inc/config.php_:
  - Set FENIX_CLIENT_ID to the **Client ID**.
  - Set FENIX_CLIENT_SECRET_ to the **Client Secret**.
  - Set FENIX_REDIRECT_URL to what was set in the Fénix application. 
  
### Setup Google Cloud
<a id="googlecloud"></a>If the project hasn't been set up in Google Cloud console, it should be added:<br>
  - Access Google Cloud with gamecourse credentials.
  - Got to "console".<br><br>
    ![image](https://github.com/GameCourseProject/gamecourse/assets/55749544/f686f589-48b2-432d-abed-fb860a3bae84)
  
  - Once there, select the "Credentials" tab inside "APIs & Services".<br><br>
    ![image](https://github.com/GameCourseProject/gamecourse/assets/55749544/1b91a8c0-9c0a-4b3f-a362-c0af47eba63a)
  
  - Click on "Create credentials" and select the option "OAuth client ID".<br><br>
    ![image](https://github.com/GameCourseProject/gamecourse/assets/55749544/616579ac-dda5-466b-88b1-f538556c2292)

  - Add configuration:<br>
      * **Application type**: Web application <br>
      * **Name**: (Desired name - try to make it simple and specific!) E.g.: _gamecourse (testing env)_ <br>
      * **Authorized redirect URIs**: Add your paths according to your config.php file <br>
            ```<API_URL>/modules/GoogleSheets/scripts/auth.php```<br>
            ```<API_URL>/auth?google```<br><br>
      The end results should look similar to:<br><br>
        ![image](https://github.com/GameCourseProject/gamecourse/assets/55749544/0737597a-36b2-403d-b764-f32634d7642d)

  - Once created, a small window will appear with clientID and secret:<br>
      * **Download the JSON file**, which will be later needed to enable modules and get them up and running such as GoogleSheets.<br>
      * **Copy _client ID_ and _client secret_ and paste them on the config.php file**, in the "Google Auth" section.<br><br>

