<?php
if (array_key_exists("Fenix", $_POST)) {
    return "fenix";
}
if (array_key_exists("Google", $_POST)) {
    return "google";
}
if (array_key_exists("Facebook", $_POST)) {
    return "facebook";
}
if (array_key_exists("Linkedin", $_POST)) {
    return "linkedin";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?php echo Utils::createBase(); ?>" target="_blank">
    <title>GameCourse</title>
    <link rel="stylesheet" type="text/css" href="css/login.css" />
</head>

<body>
    <div style="text-align:center;">
        <div id="logo"></div>
        <h3>Login using:</h3>
        <form action="?login" method="post" target="_self">
            <div class="block">
                <div class="hovicon effect-1 sub-a">
                    <input name="Fenix" id="Fenix" type="submit" class="icon" value="Fenix">
                </div>
            </div>
            <div class="block">
                <div class="hovicon effect-1 sub-a">
                    <input name="Google" id="Google" type="submit" class="icon" value="Google">
                </div>
            </div>
            <div class="block">
                <div class="hovicon effect-1 sub-a">
                    <input name="Facebook" id="Facebook" type="submit" class="icon" value="Facebook">
                </div>
            </div>
            <div class="block">
                <div class="hovicon effect-1 sub-a">
                    <input name="Linkedin" id="Linkedin" type="submit" class="icon" value="Linkedin">
                </div>
            </div>
            
        </form>
    </div>
</body>


</html>