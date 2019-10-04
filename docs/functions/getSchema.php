<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

chdir('../../');

include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\Course;

Core::init();

if(!Core::requireSetup(false))
    die('Please perform setup first!');
$courses = array_column(Core::getCourses(),"id");
if (array_key_exists('list', $_GET)) {
    echo json_encode(array_column(Core::getCourses(),"id"));
}


else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 1);
    $course = Course::getCourse($courseId);
    
    //this should eventually use the dictionary, but i still would have to write the description for all functions
    //So for now I'm just listing them manually
    $modules = array_column(Core::$systemDB->selectMultiple("course_module",["course"=>$courseId,"isEnabled"=>true],"moduleId"),"moduleId");
    $functionsList=[];
    if (in_array("views", $modules)){
        $functionsList[]=[
            "name"=> "Object and Collection Manipulation",
            "desc"=> "Functions that can be called over collections,objects or other values of any library",
            "functions"=>[
                "%collection.crop(start,end)"=>
"Returns the collection only with objects that have an index between start and end, inclusively.",
"%collection.filter(key,value,operation)"=>
"Returns the collection only with objects that have the variable key that satisfy the operation with a
specific value.",
"%collection.index(x)"=>
"Returns the smallest i such that i is the index of the first occurrence of x in the collection.",
"%collection.item(i)"=>
"Returns the element x such that i is the index of x in the collection.",
"%collection.sort(order,keys)"=>
"Returns the collection with objects sorted in a specific order by variables keys, from left to right
separated by a ;. Any key may be an expression.",
"%collection.count"=>
"Returns the number of elements in the collection.",
"%integer.abs"=>
"Returns the absolute value of an integer.",
"%object.id"=>
"Returns an integer that identifies the object.",
"%object.parent"=>
"Returns an object in the next hierarchical level.",
"%string.integer"=>
"Returns an integer representation of the string."
            ]
        ];
        $functionsList[]=[
            "name"=>"system",
            "desc"=>"This library provides general functionalities that aren't related with getting info from the database",
            "functions"=>[
                "system.abs(%integer)"=>
"Returns the absolute value of an integer.",
"system.max(%integer,%integer)"=>
"Returns the greatest number between two integers.",
"system.min(%integer,%integer)"=>
"Returns the smallest number between two integers.",
"system.time"=>"Return the time in seconds since the epoch as a floating point number. The specific date of the epoch and
the handling of leap seconds is platform dependent. On Windows and most Unix systems, the epoch is
January 1, 1970, 00:00:00 (UTC) and leap seconds are not counted towards the time in seconds since the
epoch. This is commonly referred to as Unix time."
            ]
        ];
        
        
        $functionsList[]=[
            "name"=>"users",
            "desc"=>"This library provides access to information regarding Users and their info.",
            "functions"=>[
                
"users.getAllUsers(role,course)"=>
"Returns a collection with all users. The optional parameters can be used to find users that specify a given combination of conditions:
( course: The id of a Course;
 role: The role the GameCourseUser has).",
"users.getUser(id)"=>
"Returns the object user with the specific id.",
"%user.getAllCourses(role)"=>
"Returns a collection of Courses to which the CourseUser is associated. Receives an optional specific
role to search for Courses to which the CourseUser is associated with that role.",
"%user.campus"=>
"Returns a string with the campus of the GameCourseUser.",
"%user.email"=>
"Returns a string with the email of the GameCourseUser.",
"%user.id"=>
"Returns a string with the id of the GameCourseUser.",
"%user.isAdmin"=>
"Returns a boolean regarding whether the GameCourseUser has admin permissions.",
"%user.lastActivity"=>
"Returns a string with the timestamp with the last action of the GameCourseUser in the system.",
"%user.name"=>
"Returns a string with the name of the GameCourseUser.",
"%user.picture"=>
"Returns the picture of the profile of the GameCourseUser.",
"%user.roles"=>
"Returns a collection with the roles of the GameCourseUser in the Course.",
"%user.username"=>
"Returns a string with the username of the GameCourseUser."
            ]
        ];
        
        $functionsList[]=[
            "name"=>"courses",
            "desc"=>"This library provides access to information regarding Courses and their info.",
            "functions"=>[
                "courses.getAllCourses(isActive,isVisible)"=>
"Returns a collection with all the courses in the system. The optional parameters can be used to
find courses that specify a given combination of conditions:
( isActive: active or inactive depending whether the course is active;
 isVisible: visible or invisible depending whether the course is visible).",
"courses.getCourse(id)"=>
"Returns the object course with the specific id.",
"%course.isActive"=>
"Returns a boolean on whether the course is active.",
"%course.isVisible"=>
"Returns a boolean on whether the course is visible.",
"%course.name"=>
"Returns a string with the name of the course.",
"%course.roles"=>
"Returns a collection with all the roles in the course"
            ]
        ];
        $functionsList[]=[
            "name"=>"awards",
            "desc"=>"This library provides access to information regarding Awards.",
            "functions"=>[
"awards.getAllAwards(user,type,moduleInstance,initialDate,finalDate)"=>
"Returns a collection with all the awards in the Course. The optional parameters can be used to find
awards that specify a given combination of conditions:
( user: id of a GameCourseUser;
 type: Type of the event that led to the award;
 moduleInstance: Name of an instance of an object from a Module;
 initialDate: Start of a time interval in DD/MM/YYYY format;
 finalDate: End of a time interval in DD/MM/YYYY format).",
"%award.renderPicture(item)"=>
"Returns a picture of the item associated to the award. item can refer to the GameCourseUser that won
it ('user') and the type of the award ('type').",
"%award.date"=>
"Returns a string in DD/MM/YYYY format of the date the award was created.",
"%award.description"=>
"Returns a string with information regarding the name of the award, the type, the Module instance and the
Reward associated to it.",
"%award.moduleInstance"=>
"Returns a string with the name of the Module instance that provided the award.",
"%award.reward"=>
"Returns a string with the reward provided by the award.",
"%award.type"=>
"Returns a string with the type of the event that provided the award.",
"%award.user"=>
"Returns a string with the id of the GameCourseUser that received the award"
            ]
        ];
        $functionsList[]=[
            "name"=>"participations",
            "desc"=>"This library provides access to information regarding Participations.",
            "functions"=>[
"participations.getAllParticipations(user,type,moduleInstance,rating,evaluator,initialDate,finalDate)"=>
"Returns a collection with all the participations in the Course. The optional parameters can be
used to find participations that specify a given combination of conditions:
( user: id of a GameCourseUser that participated;
 type: Type of participation;
 moduleInstance: Name of an instance of an object from a Module. Note that moduleInstance
only needs a value if type is badge or skill;
 rating: Rate given to the participation;
 evaluator: id of a GameCourseUser that rated the participation;
 initialDate: Start of a time interval in DD/MM/YYYY format;
 finalDate: End of a time interval in DD/MM/YYYY format).",
"%participation.date"=>
"Returns a string in DD/MM/YYYY format of the date of the participation.",
"%participation.description"=>
"Returns a string with the information of the participation.",
"%participation.evaluator"=>
"Returns a string with the id of the user that rated the participation.",
"%participation.moduleInstance"=>
"Returns a string with the name of the Module instance where the user participated.",
"%participation.post"=>
"Returns a string with the link to the post where the user participated.",
"%participation.rating"=>
"Returns a string with the rating of the participation.",
"%participation.type"=>
"Returns a string with the type of the participation.",
"%participation.user"=>
"Returns a string with the id of the user that participated."
            ]
        ];
        $functionsList[]=[
            "name"=>"actions",
            "desc"=>"Library to be used only on EVENTS. These functions define the response to event triggers",
            "functions"=>[
"actions.hideView(label)"=>"Changes the visibility of a view with the specific label to make it invisible.",
"actions.showView(label)"=>
"Changes the visibility of a view with the specific label to make it visible.",
"actions.toggleView(label)"=>
"Toggles the visibility of a view with the specific label.",
"actions.goToPage(name,[user])"=>
"Changes the current page to the page referred by name equal to identifier. If a second
argument is provided, that page needs the user to access a specific context.",
"actions.showPopUp(templateName)"=>
"Creates a view with the contents of the template with the templateName in a form of a pop-up.",
"actions.showTooltip(templateName)"=>
"Creates a view with the contents of the template with the templateName in a form of a tooltip."
            ]
        ];
    }
    if (in_array("xp", $modules)){
        $functionsList[]=[
            "name"=>"xp",
            "desc"=>"This library provides information regarding XP and Levels. It is provided by the xp module.",
            "functions"=>[
    "xp.allLevels"=>
"Returns a collection with all the levels on a Course.
xp.getLevel(user,number,goal)
Returns a level object. The optional parameters can be used to find levels that specify a given
combination of conditions:
( user: The id of a GameCourseUser;
 number: The number to which the level corresponds to;
 goal: The goal required to achieve the target level).",
                
"xp.getBadgesXP(user)"=>
"Returns the sum of XP that all Badges provide as reward from a GameCourseUser identified by user.",
"xp.getSkillTreeXP(user)"=>
"Returns the sum of XP that all SkillTrees provide as reward from a GameCourseUser identified by user.",
"xp.getXP(user)"=>
"Returns the sum of XP that all Modules provide as reward from a GameCourseUser identified by user.",
"%level.description"=>
"Returns a string with information regarding the level.",
"%level.goal"=>
"Returns a string with the goal regarding the level.",
"%level.number"=>
"Returns a string with the number regarding the level."          
            ]
        ];
    }
    if (in_array("skills", $modules)){
       $functionsList[]=[
            "name"=>"skills",
            "desc"=>"This library provides information regarding Skill Trees. It is provided by the skills module.",
            "functions"=>[
         "skillTrees.getTree(id)"=>
"Returns the object skillTree with the id id.
skillTrees.getAllSkills(tree,tier,dependsOn,requiredBy)
Returns a collection with all the skills in the Course. The optional parameters can be used to find
skills that specify a given combination of conditions:
( tree: The skillTree object or the id of the skillTree object to which the skill belongs to;
 tier: The tier object or tier of the tier object of the skill;
 dependsOn: a skill that is used to unlock a specific skill;
 requiredBy: a skill that unlocks a collection of skills).",
"skillTrees.trees"=>
"Returns a collection will all the Skill Trees in the Course.",
"%tree.getAllSkills(tier,dependsOn,requiredBy)"=>
"Returns a collection with all the skills on a skillTree. The optional parameters can be used to
find skills that specify a given combination of conditions:
( tier: The tier or number of the tier of the skill;
 dependsOn: a skill that is used to unlock a specific skill;
 requiredBy: a skill that unlocks a collection of skills).",
"%tree.getSkill(name)"=>
"Returns a skill object from a skillTree with a specific name.",
"%tree.getTier(number)"=>
"Returns a tier object with a specific number from a skillTree.",
"%tier.nextTier"=>
"Returns the next tier object from a skillTree.",
"%tier.previousTier"=>
"Returns the previous tier object from a skillTree.",
"%tier.reward"=>
"Returns a string with the reward of completing a skill from that tier.",
"%tier.tier"=>
"Returns a string with the numeric value of the tier.",
"%tier.skills"=>
"Returns a collection of skill objects from a specific tier.",
"%tree.tiers"=>
"Returns a collection with tier objects from a skillTree.",
"%dependency.isUnlocked(user)"=>
"Returns a boolean regarding whether the GameCourseUser identified by user has unlocked a
dependency.",
"%dependency.simpleSkills"=>
"Returns a collection of skills that are required to unlock a super skill from a dependency.",
"%dependency.superSkill"=>
"Returns the super skill of a dependency.",
"%skill.getPost(user)"=>
"Returns a string with the link to the post of the skill made by the GameCourseUser identified by user.",
"%skill.getStyle(user)"=>
"Returns a string with the style of the skill from a GameCourseUser identified by user. This function is used to render a skill block in a view.",
"%skill.isCompleted(user)"=>
"Returns a boolean regarding whether the GameCourseUser identified by user has completed a skill.",
"%skill.isUnlocked(user)"=>
"Returns a boolean regarding whether the GameCourseUser identified by user has unlocked a skill.",
"%skill.color"=>
"Returns a string with the reference of the color in hexadecimal of the skill.",
"%skill.dependsOn"=>
"Returns a collection of dependency objects that require the skill on any dependency.",
"%skill.name"=>
"Returns a string with the name of the skill.",
"%skill.requiredBy"=>
"Returns a collection of skill objects that are required by the skill on any dependency.",
"%skill.reward"=>
"Returns a string with the reward of the skill.",
"%skill.tier"=>
"Returns a string with the numeric value of the tier of that skill."
            ]
        ];
    }
    if (in_array("badges", $modules)){
        $functionsList[]=[
            "name"=>"badges",
            "desc"=>"This library provides information regarding Badges and their levels. It is provided by the badges module.",
            "functions"=>[
      "badges.getAllBadges(isExtra,isBragging)"=>
"Returns a collection with all the badges in the Course. The optional parameters can be used to find
badges that specify a given combination of conditions:
( isExtra: Badge has a reward;
 isBragging: Badge has no reward).",
"badges.getBadge(name)"=>
"Returns the badge object with the specific name.",
"badges.getBadgesCount(user)"=>
"Returns an integer with the number of badges of the GameCourseUser identified by user. If no argument is provided, the function returns the number of badges of the course.",
"%badge.renderPicture(number)"=>
'Return a picture of a badge’s Level number, e.g. first level is 1',
"%badge.getLevel(number)"=>
"Returns a Level object corresponding to Level number from that badge.",
"%badge.currLevel(user)"=>
"Returns a Level object corresponding to the current Level of a GameCourseUser identified by user from that badge.",
"%badge.nextLevel(user)"=>
"Returns a Level object corresponding to the next Level of a GameCourseUser identified by user from that badge.",
"%badge.previousLevel(user)"=>
"Returns a Level object corresponding to the previous Level of a GameCourseUser identified by user from that badge.",
"%badge.description"=>
"Returns a string with information regarding the name of the badge, the goal to obtain it and the reward associated to it.",
"%badge.isBragging"=>
"Returns a boolean regarding whether the badge provides no reward.",
"%badge.isExtra"=>
"Returns a boolean regarding whether the badge provides reward.",
"%badge.levels"=>
"Returns a collection of Level objects from that badge.",
"%badge.maxLevel"=>
"Returns a Level object corresponding to the maximum Level from that badge.",
"%badge.name"=>
"Returns a string with the name of the badge.",
"%level.description"=>
"Returns a string with the information of the Level.",
"%level.goal"=>
"Returns a string with the goal of the Level.",
"%level.nextLevel"=>
"Returns a Level object corresponding to the next Level.",
"%level.number"=>
"Returns a string with the number of the Level.",
"%level.previousLevel"=>
"Returns a Level object corresponding to the previous Level.",
"%level.reward"=>
"Returns a string with the reward of the Level."
            ]
        ];
    }
    echo json_encode($functionsList);
}
?>
