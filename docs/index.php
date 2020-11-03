<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1">
    <title>GameCourse Documentation</title>
    <link rel="stylesheet" href="/gamecourse/css/navbar.css"/>
    <link rel="stylesheet" href="/gamecourse/css/docs.css"/>
    <link rel="stylesheet" href="/gamecourse/css/geral.css"/>
    <script src="/gamecourse/js/angular.min.js"></script>
    <script src="/gamecourse/js/jquery.min.js"></script>
    <script type="text/javascript" src="documentation.js"></script>
    <script type="text/javascript" src="documentation-views.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet"> 


</head>
<body>
    <div class="navbar">  
        <a class="logo" href="/gamecourse/"></a>
        <ul class="menu documentation">
            <li id="menu-views"><a href="javascript:void(0);" class="focused">Views</a></li>
            <li id="menu-functions"><a href="/gamecourse/docs/functions">Functions</a></li>
            <li id="menu-modules"><a href="/gamecourse/docs/modules">Modules</a></li>
        </ul>
    </div>

    <div class="page">
        <?php include'pages/plugins/views.html'; ?>
    </div>
</body>
</html>