<?php
if (array_key_exists("goBack", $_POST)) {
    return "goBack";
}
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?php echo Utils::createBase(); ?>" target="_blank">
    <title>GameCourse</title>
    <link rel="stylesheet" type="text/css" href="css/login.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet"> 
</head>

<body>
    <div class="background"></div>
    <div class="login_box">
        <div id="logo"></div>
        <div class="middle-box">
            <h3>Hello! Thank you for logging in.</h3>
            <div class="content">
                Apparently you do not have access to GameCourse.
                Please ask to the teacher of your course to add you to the user's list.
            </div>
        </div>
    </div>
    <div id="go_back">
        <form action="?login" method="post" target="_self" id="back_form">
            <div class="icon arrow_back"></div>
            <input name="goBack" id="goBack" type="submit" class="button" value="back">
        </form>
    </div>

</body>

</html>