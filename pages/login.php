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
        </form>
    </div>
    <!-- <a href="https://fenix.tecnico.ulisboa.pt/oauth/userdialog?client_id=1977390058176612&redirect_uri=http%3A%2F%2Flocalhost%2Fgamecourse%2Fauth%2F">Fenix</a>
    <a href="https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=online&client_id=370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Flocalhost%2Fgamecourse%2Fauth&state&scope=email%20profile&approval_prompt=auto">Google</a> -->
</body>

<script>
    FB.login(function(response) {
        if (response.authResponse) {
            console.log('Welcome!  Fetching your information.... ');
            FB.api('/me', function(response) {
                console.log('Good to see you, ' + response.name + '.');
            });
        } else {
            console.log('User cancelled login or did not fully authorize.');
        }
    });

    window.fbAsyncInit = function() {
        FB.init({
            appId: '{2373275799648058}',
            cookie: true,
            xfbml: true,
            version: '{v7.0}'
        });

        FB.AppEvents.logPageView();

    };

    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
            return;
        }
        js = d.createElement(s);
        js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>

</html>