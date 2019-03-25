Smartboards
=

Installation and Update guide for SmartBoards.

---

Installation
-

(Recommended php version :5.6)

1. Run the **old python script** (using python 2.7)
2. Setup a FenixEdu Application, if you don't have one already
    2.1. In Fenix go to **Personal** &gt; **External Applications** &gt; **Manage Applications**
    2.2. Create the application defining the **Redirect Url** as **&lt;base website url&gt;/auth/** and check **Information** in **Scopes**
3. Change the **config.php** constants.
4. Copy the **old legacy files** to the folder specified in **LEGACY&lowbar;DATA&lowbar;FOLDER**
  - **indicators.json**
  - **achievements.txt**
  - **awards.txt**
  - **level.txt**
  - **students.txt**
  - **tree.txt**
  - **tree folder with html pages** of skills
5. Create a file with name **teachers.txt** in **LEGACY&lowbar;DATA&lowbar;FOLDER** and add the teachers, one per line, with the format **id;name;email**
6. Copy the **folders of each skill** in the Skill Tree to **&lt;MODULES&lowbar;FOLDER&gt;/skills/resources**
7. Install dependencies and run **generate.sh** (this is needed when the less or jison files are updated)
  7.1. Install Less and Jison from NPM
  7.2. Download https://github.com/zaach/jison (alternative: https://github.com/AndreBaltazar8/jison)
  7.3. Set correct **JISON_PHP** path to the Jison PHP port in the **generate.sh** script
  7.4. Run **generate.sh**
8. If there is a file called setup.done , delete it
9. Setup the course by visiting the SmartBoards page
10. Run **loadLegacy.php** in the console or visit it in your browser
11. Add the Grade page URL from Fenix Course page (the page which lists the Students with their **username**, **number**, name, etc..) to the array in **updateUsernames.php** 
12. Get the cookie values (**BACKENDID**, **JSESSIONID**) from fenix and either put them in the **updateUsernames.php** and **downloadPhotos.php** files and run them or run them throug the course settings page
and run it.

You now have a installed course. Now proceed to setting up the SmartBoard!

> **Note:** The cookie values are required to properly download the photos, because not all students have their photos made public.

Setup
=

1. In the **Settings** page:
  1. Enable all the modules you want
  2. Configure the landing page for the default role and header link

Everything should be good to go! Now you just need to update the SmartBoards if something changes.

Update
=

1. Run old python script
2. Place the updated legacy files in the chosen folder
3. Run **loadLegacy.php**
4. If a new user was added to the system:
  4.1. Configure the cookie values and **downloadPhotos.php** and **updateUsernames.php**

Creating a new Course
=

1. Go to **Settings -> Courses**
2. Press **Create new**
3. Enter the details, and choose between a blank course or copy from existing one
4. Go to the **Course's Settings page** to configure the course (ex. enable modules, set default page, etc)
5. Visit **/updateUsernames.php?course=&lt;course_id&gt;&courseurl0=&lt;grades_page&gt;** to update the usernames
6. Configure the cookie values in **downloadPhotos.php** file, and run it, to download the student's photos
7. Run **loadLegacy.php**, specifying the **course id** in the first parameter (**VERY IMPORTANT**: if you don't specify the parameter, the course with id 0 will be updated).

