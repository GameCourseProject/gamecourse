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
    <link rel="stylesheet" type="text/css" href="css/simple-page.css" />
</head>

<body>
    <div style="text-align:center;">
        <h3>Login</h3>
        <form action="?login" method="post" target="_self">
            <input name="Fenix" id="Fenix" type="submit" class="button big" value="Fenix">
            <input name="Google" id="Google" type="submit" class="button big" value="Google">
            <input name="Facebook" id="Facebook" type="submit" class="button big" value="Facebook">
            <input name="Linkedin" id="Linkedin" type="submit" class="button big" value="Linkedin">
        </form>
    </div>
</body>


</html>