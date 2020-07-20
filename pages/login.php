<?php
if (array_key_exists("Fenix", $_POST)) {
    return "fenix";
}
if (array_key_exists("Google", $_POST)) {
    return "google";
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
        </form>
    </div>
    <!-- <a href="https://fenix.tecnico.ulisboa.pt/oauth/userdialog?client_id=1977390058176612&redirect_uri=http%3A%2F%2Flocalhost%2Fgamecourse%2Fauth%2F">Fenix</a>
    <a href="https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=online&client_id=370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Flocalhost%2Fgamecourse%2Fauth&state&scope=email%20profile&approval_prompt=auto">Google</a> -->
</body>

</html>