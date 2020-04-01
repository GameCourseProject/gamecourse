GameCourse
=
Installation
---
Needs a server with PHP 5.6 and a MySQL Database (needs to be a higher version - 7.3)
(For example you can use Xampp to create the server and DB, but you may need to ajust the php version)

1. Setup a FenixEdu Application, if you don't have one already
  - In Fenix go to **Personal** &gt; **External Applications** &gt; **Manage Applications**
  - Create the application defining the **Redirect Url** as **&lt;base website url&gt;/auth/** and check **Information** in **Scopes**
2. Change the **config.php** constants:
  - The CONNECTION constants have the info to connect to the databas,
  - Base has the name of the system (if you wish to change ir follow the instructions to change the name, further ahead),
  - The FENIX constants should match those values in the fenix app.
3. If there is a file called **setup.done** , delete it
4. Setup the course by visiting the GameCourse page
5. There will be a folder inside **legacy_data** with the id and name of the course created, if it is empty copy the contents of **legacy_data/defaultData** to the new folder.
(this will allow you to have the data of the PCM on 2017, the rest of the years data are in PCM's sigma)
6. Go to the GameCourse page, in there go to the course that was created, and then to its Settings page. Now you may enable modules, you can enable Views, XP and Levels, Skills, Profile, Leaderboard and Badges.
7. There will be a side menu on the left, now go every option inside Configurations to set it up (there will at least be configuration of students and teachers)
 -In those pages you can define the lists of Students, Teachers, Skills, Badges and Levels
 -There will be default list on the rigth side, you may edit them as you please and then click the button to replace them
8. Go back to global settings and click the **Load Legacy** button. (If there is a problem here which can happen in sigma, you can run loadLegacy.php manually)
9. Get the cookie values (**BACKENDID**, **JSESSIONID**) from fenix, put them in their respective fields on the Settings page and press **Download Photos** (you may also run the downloadPhotos.php script manualy).
> **Note:** The cookie values are required to properly download the photos, because not all students have their photos made public.

Now you have instaled the course and can set up the pages.
You may also want to configure the landing page by going selecting Roles->Default in the side menu

Everything should be good to go! Now you just need to update GameCourse if something changes.


Update
----

1. Run the **old python script**(not in this repository, can be found in PCM's area in sigma) (using python 2.7) to update de txt files in the **legacy_data** folder of the course
2. Go to the course settings page and click on **Load Legacy** or run the **loadLegacy.php** script manualy (if you run the script it needs to receive a course argument or it will assume it's the 1st)
3. If a new user was added to the system:
  - Get the cookie values and click on **Download Photos** or run **downloadPhotos.php**


Other setups that may be necessary
----
If you want to by compile .less files (by runnning *compileLess.sh*) into CSS:
  - Instal Less from NPM 

If you make changes to the .jison file (used to define the expression language):
  - Install Less and Jison from NPM
  - Download https://github.com/zaach/jison (alternative: https://github.com/AndreBaltazar8/jison)
  - Set correct *JISON_PHP* path to the Jison PHP port in the *generateParsers.sh* script
  - Now you can run the *generateParsers.sh* script (which should be run after changes in the .jison file)
Warning: After the sript is run there can be an error when compiling the language related with a $match variable, if that happens go to the `ExpressionEvaluatorBase.php` file and before the line with `if ( $match )` add `$match=""`

In order to use Google Authentication, it is necessary to setup the a google API in [https://console.developers.google.com/apis/dashboard](https://console.developers.google.com/apis/dashboard) 
     - Create a project 
     - Click on **Enable APIs**, and search for People API
     - Add credentials
     - On **Authorised redirect URIs**  you should put the url addres for gamecourse + "/guestLogin.php" (e.g: http://localhost/gamecourse/guestLogin.php) 
     - Download JSON file of the credentials, name it credentials.json and replace the file in the *google-api-php-client* folder.
 This authentication is only allowing login by pre-authorized users, the list of authorized emails can be edited in the *guestLogin.php* file.


Creating a new Course 
----
1. Go to **Settings -> Courses**
2. Press **Create new**
3. Enter the details, and choose between a blank course or copy from existing one
4. Go to the **Course's Settings page** to configure the course (e.g. enable modules, set default page, etc)
5. Visit the **Configuration** pages in the sidebar to setup information suchs as Students, Teacher, Skills, Badges, and Levels
6. Configure the cookie values in the course settings and click on **Download Photos** .
7. Press **Load Legacy**, or run *loadLegacy.php*, specifying the *course id* in the first parameter.


Changing the name of the platform
----
If you wish to have a name other than GameCourse:

1. Change name of the folder gamecourse
2. Change BASE and FENIX_REDIRECT_URL in config.php
3. Setup a fenix application for the new url (more detail in the intalation instructions)
4. Update FENIX_CLIENT_ID and FENIX_CLIENT_SECRET according to the fenix application
5. In *.htaccess* change RewriteRule so it has the new name instead of gamecourse
