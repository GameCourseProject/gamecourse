# GameCourse

## Technology

- **Server**:<br>
<ins>Environment</ins>: **PHP 7.3.21** and **Python 3.7.3**<br>
You will need a server running PHP 7.3.21 version and a composer (dependency manager).<br>
For example, you can use _XAMPP_. Guides [here](#my-anchor).

- **Database**:<br>
<ins>Environment</ins>: **MySQL**<br>
You will need a MySQL Database.<br>
For example, you can use _phpMyAdmin_ for database management. Guides here.

- **Frontend**:<br>
<ins>Environment</ins>: **Angular 13**<br>
You will need to install _npm_ (dependency manager), _NodeJS_, and _Angular CLI_. Guides here.

## Setup GameCourse - Localhost
This is a setup guide to run the project on your machine.

- **Backend**:<br>
  1. Copy the configuration file template (_api/inc/config.template.ts_) and rename it to _config.php_. Update its configuration variables.
  2. TODO - Add missing files for modules
  3. Install all dependencies by running: ```composer install```
  4. Create Fénix app, if necessary (guides here), and update configuration information from _api/inc/config.php_
  
- **Frontend**:<br>
  1. Copy the configuration file template (_frontend/src/environments/config.template.ts_) and rename it to _config.ts_. Update its configuration variables.
  2. Install all dependencies by running: ```npm install```
  3. Run Angular CLI server by running: ```ng serve```
  4. Access [http://localhost:4200/](http://localhost:4200/) and you will see GameCourse!

## Setup GameCourse - Production
If this project has not been set up in _Google Cloud_, do the section "_Setup Google Cloud_" first.

- **Frontend**:<br>
  1. Change API Endpoint (_frontend/src/environments/config.ts_)
  2. Change baseHref, e.g. /gamecourse/ (_frontend/angular.json_)
  3. Build project for production by running: ```npm run-script build:prod```
  4. Transfer everything in _/dist/gamecourse/_ to server
     
- **Backend**:<br>
  1. Change configuration variables (_api/inc/config.php_ & _api/modules/composer.json_)
  2. Create Fénix app, if necessary (guides here), and update configuration information from _api/inc/config.php_
  3. Transfer files (ignore folders _legacy_, _cache_, _logs_, and _test_, vendor, as well as unnecessary files)
  4. Go to api folder (command ```cd /var/www/html/<folder_name>```) and install dependencies by running: ```sudo -u www-data composer install```
  5. Change directory permissions to www-data: ```sudo chown -R www-data:www-data /var/www/html/<folder_name>```
  6. Create database
  7. Delete file _api/setup/setup.done_ if exists

After all of these steps, you should be able to access [https://pcm.rnl.ulisboa.pt/project-name](https://pcm.rnl.ulisboa.pt/project-name) and set up the rest of GameCourse through the website (change "project-name" with the corresponding name). 

## Additional Guides
### Useful Software & Tools
  * **<a id="my-anchor"></a>XAMPP**: <br>
  XAMPP is a _PHP development environment_. It is a completely free, easy-to-install **Apache** distribution containing _MariaDB_, _PHP_, and _Perl_. See more [here](https://www.apachefriends.org/). <br><br>
  **How to Install?**
    - Download the latest version of XAMPP for **PHP 7.3** (version 7.3.21) [here](https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/7.3.21/).
    - Click on the installer and follow the installation wizard.
        - <ins>Select components</ins>: You must install **Apache**, **MySQL**, and **PHP**. The rest of the components are up to you, but phpMyAdmin is also useful for database management (guides here).
    - Change MySQL server charset
        - Click on MySQL _Config_ > _my.ini_ in the **Control Panel**. This will open _my.ini_ file.
        - Edit UTF 8 Settings: ```character_set_server=utf8mb4```

          ![xampp1](https://github.com/GameCourseProject/gamecourse/assets/55749544/9c50f4cf-cd62-42b0-949c-312ee4258141)
          
    - (Optional): By default, XAMPP will serve documents in the _/xampp/htdocs_ directory. If you want to **change your Apache root directory**, click the Apache _Config_ > _Apache_ (httpd.conf) in the Control Panel and adjust the entries for the *DocumentRoot* and the corresponding Directory entry.
   
      ![xampp1](https://github.com/GameCourseProject/gamecourse/assets/55749544/4afa3632-1b02-4c40-9c7c-1053bfa0a32b)

   
    **How to use it?**
      - Once installed, you can go ahead and run XAMPP. This will open the XAMPP Control Panel which is where you will turn on/off your Apache and MySQL server.
      - (Optional) You can use **phpMyAdmin** for database management (guides here).
      - (Optional) You can use XAMPP to run Python (guides here).

* **phpMyAdmin**: <br>
phpMyAdmin allows you to easily manage your databases using a Graphical User Interface instead of executing multiple SQL commands (though you can still do this as well). See more [here](https://www.phpmyadmin.net/).

  **How to Install?**<br>
  You probably already installed it while setting up XAMPP. If not, go back and add it to your XAMPP components list - guides here.<br>
  If you only want to update your phpMyAdmin version inside your XAMPP installation, follow this [guide](https://www.ostraining.com/blog/coding/update-phpmyadmin/).

  **How to use it?**<br>
  TODO: guides for common operations and general usage

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
        
* **NodeJS & Angular**: <br> TODO
* **WinSCP**: <br> TODO
* **Python**: <br> TODO

### Create your own Fénix application
TODO

### Setup Google Cloud
TODO
