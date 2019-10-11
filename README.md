Smartboards
=
Installation and Update guide for SmartBoards.
---
Installation
-

(Recommended php version :5.6)


1. Setup a FenixEdu Application, if you don't have one already
  - In Fenix go to **Personal** &gt; **External Applications** &gt; **Manage Applications**
  - Create the application defining the **Redirect Url** as **&lt;base website url&gt;/auth/** and check **Information** in **Scopes**
2. Change the **config.php** constants.
3. If there is a file called setup.done , delete it
4. Setup the course by visiting the SmartBoards page
5. There will be a folder inside **legacy_data** with the id and name of the course created, if it is empty copy the contents of **legacy_data/defaultData** to the new folder.
6. Go to the SmartBoards page, in there go to the course that was created, and then to its Settings page. Now you may enable modules, you can enable Views, XP and Levels, Skills, Profile, Leaderboard and Badges.
7. There will be a side menu on the left, now go every option inside Configurations to set it up (there will at least be configuration of students and teachers)
 -In those pages you can define the lists of Students, Teachers, Skills, Badges and Levels
 -There will be default list on the rigth side, you may edit them as you please and then click the button to replace them
8. Go back to global settings and click the **Load Legacy** button. (If there is a problem here which can happen in sigma, you can run loadLegacy.php manually)
9. Get the cookie values (**BACKENDID**, **JSESSIONID**) from fenix, put them in their respective fields on the Settings page and press **Download Photos** (you may also run the downloadPhotos.php script manualy).
> **Note:** The cookie values are required to properly download the photos, because not all students have their photos made public.

Now You have instaled the course and can set up the pages.
You may also want to configure the landing page by going selecting Roles->Default in the side menu

Everything should be good to go! Now you just need to update the SmartBoards if something changes.


Update
=

1. Run the **old python script** (using python 2.7) to update de txt files in the **legacy_data** folder of the course
2. Go to the course settings page and click on **Load Legacy** or run the **loadLegacy.php** script manualy (if you run the script it needs to receive a course argument or it will assume it's the 1st)
3. If a new user was added to the system:
  - Get the cookie values and click on **Download Photos** or run **downloadPhotos.php**


Other setups that may be necessary
=
If you want to by compile .less files (by runnning compileLess.sh) into CSS:
  - Instal Less from NPM 

If you make changes to the .jison file (used to define the expression language):
  - Install Less and Jison from NPM
  - Download https://github.com/zaach/jison (alternative: https://github.com/AndreBaltazar8/jison)
  - Set correct **JISON_PHP** path to the Jison PHP port in the **generateParsers.sh** script
  - Now you can run the generateParsers.sh script (which should be run after changes in the .jison file)


Creating a new Course 
=
> **Note:** functionality still not fully corrected to work in this version

1. Go to **Settings -> Courses**
2. Press **Create new**
3. Enter the details, and choose between a blank course or copy from existing one
4. Go to the **Course's Settings page** to configure the course (ex. enable modules, set default page, etc)
5. Visit **/updateUsernames.php?course=&lt;course_id&gt;&courseurl0=&lt;grades_page&gt;** to update the usernames
6. Configure the cookie values in **downloadPhotos.php** file, and run it, to download the student's photos
7. Run **loadLegacy.php**, specifying the **course id** in the first parameter (**VERY IMPORTANT**: if you don't specify the parameter, the course with id 0 will be updated).

