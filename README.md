GameCourse
=
Environment
---
Needs a server, for example, XAMPP, with a PHP 7.3 version, and a MySQL Database.

1. Setup a FenixEdu Application, if you don't have one already
  - In Fenix go to **Personal** &gt; **External Applications** &gt; **Manage Applications**
  - Create the application defining the **Redirect Url** as **&lt;base website url&gt;/auth/** and check **Information** in **Scopes**
2. Change the **config.php** constants:
  - The CONNECTION constants have the info to connect to the databas,
  - Base has the name of the system (if you wish to change ir follow the instructions to change the name, further ahead),
  - The FENIX constants should match those values in the fenix app.
3. If there is a file called **setup.done** , delete it
4. Setup the course by visiting the GameCourse page
5. There will be a folder inside **course_data** with the id and name of the course created, if it is empty copy the contents of **course_data/defaultData** to the new folder.
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

1. Run the **old python script**(not in this repository, can be found in PCM's area in sigma) (using python 2.7) to update de txt files in the **course_data** folder of the course
2. Go to the course settings page and click on **Load Legacy** or run the **loadLegacy.php** script manualy (if you run the script it needs to receive a course argument or it will assume it's the 1st)
3. If a new user was added to the system:
  - Get the cookie values and click on **Download Photos** or run **downloadPhotos.php**

Login Applications

**Fénix:**
  1. Go to **Personal** &gt; **External Applications** &gt; **Manage Applications** (this last tab appears as **API Terms**, if no app was created previously).
  2. Click on **Create** to create a FenixEdu application
     - Define **Site** with the desired website URL where GameCourse will run, for example http://localhost/gamecourse/auth/ if running it locally.
     - Define **Redirect Url** as **&lt;base website url&gt;/auth/**, for example, http://localhost/gamecourse/auth/ if running it locally.
     - In **Scope**, select "Information".
  3. After its creation, it will appear in the applications list. In **Edit**, we have access to the **Client Id** and the **Client Secret**, which will be used in the next step.
  4. Go to *config.php*:
     - Set FENIX_CLIENT_ID to the **Client ID**.
     - Set FENIX_CLIENT_SECRET to the **Client Secret**.
     - Set FENIX_REDIRECT_URL to what was set in the Fenix application.

**Google:**
  1. Go to https://console.developers.google.com (it is necessary to have a google account to access it).
  2. Create a new project.
  3. Go to the **Consent screen** tab:
     - Select the "external" user type.
     - Fill mandatory fields and click on **Save and continue**.
  4. Go to the **Credentials** tab:
     - Add an **OAuth client ID** credential. 
     - Fill the mandatory fields and add the website where GameCourse will be run, appending "/auth?google" at the end, to **Authorized redirect URIs**. For example, http://localhost/gamecourse/auth?google if run locally.
     - After the app has been created, a modal is shown with the **Client Id** and the **Secret Key**, which will be used in the next step.
  5. Go to *config.php*:
     - Set GOOGLE_CLIENT_ID to the **Client ID**.
     - Set GOOGLE_CLIENT_SECRET to the **Client Key**.
     - Set GOOGLE_REDIRECT_URL to what was set in the Google application.   

**Facebook:**
  1. Go to https://developers.facebook.com/apps (it is necessary to have a Facebook account to access it).
  2. Click on **Create App** and select the option **Something Else**.
  3. Go to the **Dashboard** tab:
     - Add the product **Facebook Login**.
     - Write the website URL in the input shown, for example: http://localhost/gamecourse.
  4. Go to the **Settings &gt; Basic** tab:
     - Add the following domains to the field **App Domains**:  
       - The server that runs GameCourse, for example localhost
       - The website URL, for example localhost/gamecourse
       - The redirect URL, for example localhost/gamecourse/auth?facebook
     - In the same tab, we have access to **AppId** and to **AppSecret**, which will be used in the next step.
  5. Go to *config.php*:
     - Set FACEBOOK_CLIENT_ID to the **AppId**.
     - Set FACEBOOK_CLIENT_SECRET to the **AppSecret**.
     - Set FACEBOOK_REDIRECT_URL to what was set in the Facebook application.   
 
 **LinkedIn:**
  1. Go to https://www.linkedin.com/developers.
  2. Click on **Create App** (it is necessary to have a LinkedIn account to create an app):   
     - Set **App Name** to the application name. 
     - Below the **LinkedIn Page** field, click on **Create a new Login Page** and create a page.
     - Set **LinkedIn Page** to the URL of the created page.
     - Fill remaining mandatory fields and create the app.
  3. Go to the **Auth** tab: 
     - Save the **Client ID** and the **Client Secret**, because they will be used after.
     - In **OAuth 2.0 settings**, add the redirect URL, which is the website with "/auth?linkedin" appended at the end. For example: http://localhost/gamecourse/auth?linkedin.
  4. Go to the **Products** tab:     
     - Add the products: **Share on LinkedIn** and **Sign In with LinkedIn** (it takes a few minutes to be accepted).
  5. Go to *config.php*:
     - Set LINKEDIN_CLIENT_ID to the **Client ID**.
     - Set LINKEDIN_CLIENT_SECRET to the **Client Secret**.
     - Set LINKEDIN_REDIRECT_URL to what was set in the LinkedIn application.   
 

Setup GameCourse
---
1. If there is a file called *setup.done*, delete it.
2. Go to the GameCourse page, and then go to the **Courses** tab to create a course. Here are the multiple options:
   - Create a course manually, entering its details.
   - Copy an already existing course.
   - Import a zip with course(s) configurations (this needs to be in the correct format, which can be checked when exporting).
4. Go to the course that was created, and then to its **Settings** page to enable the desired modules, such as Views, XP and Levels, Skills, Profile, Leaderboard, and Badges.
5. There are many ways available to insert or update users:
   - To get students' information from Fenix, enable the **plugin** module, and in its configuration page, upload the .csv file get from Fenix with student's data from a specific course.
   - Users can be set in the **Users** tab, inside or outside a course, as desired.
   - GameCourse Users can be uploaded from a .csv with the following format: "name,email,nickname,studentNumber,isAdmin,isActive,username,auth".
   - Course Users can be uploaded from a .csv with the following format: "name,email,nickname,studentNumber,isAdmin,isActive,campus,roles,username,auth", where "roles" are separated by "-". 
6. Badges, Skills, and XP and Levels can be setup on their respective configuration page (those modules need to be enabled first). 
7. To update GameCourse, it is needed to set the configurations to access the external data sources: Moodle, Class Check, Google Sheets, and the QR source data.
   - In the plugin configuration page and QR page, respectively, set those configurations and set the periodicity desired to collect data from a source and insert students participation in the database. This can be changed or disabled at any time.  

Now the course is installed, and pages can be set up by editing views.

It is also possible to configure the course landing page, going to **Course Settings &gt; Roles**.


Changing the name of the platform
----
If you wish to have a name other than GameCourse:

1. Change name of the folder gamecourse.
2. Change BASE, FENIX_REDIRECT_URL, GOOGLE_REDIRECT_URL, FACEBOOL_REDIRECT_URL, and LINKEDIN_REDIRECT_URL in config.php.
3. Setup a fenix, google, facebook and linkedin application for the new url (instructions above in **Login Applications**).
4. Update the clients' id and secret according to the respective applications.
5. In *.htaccess* change RewriteRule so it has the new name instead of gamecourse.
