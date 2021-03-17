<?php

include('classes/ClassLoader.class.php');

use GameCourse\Course;

if(isset($_FILES['file']['name'])){

   /* Getting file name */
   $filename = $_FILES['file']['name'];

   /* Location */
   // TODO: get courseId and resources folder
   //$courseFolder = Course::getCourseLegacyFolder($courseId);
   // TODO: change when legacy data changes
   //TODO: get name of skill
   $location = "legacy_data/1-PCM 21/tree/" . $filename;
   $imageFileType = pathinfo($location,PATHINFO_EXTENSION);
   $imageFileType = strtolower($imageFileType);

   /* Valid extensions */
   $valid_extensions = array("jpg","jpeg","png");

   $response = 0;
   /* Check file extension */
   if(in_array(strtolower($imageFileType), $valid_extensions)) {
      /* Upload file */
      if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){
         $response = $location;
      }
   }

   echo $response;
   exit;
}

echo 0;