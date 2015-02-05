<?php

// : Error reporting for debugging purposes
error_reporting(E_ALL);
ini_set("display_errors", "1");
// : End

const ini_file = 'config/settings.ini';
const dbname = 'bwt_max_auto';

$_errors = (array) array();

if (isset($_SESSION['SID'])) {
    session_start();
    header("Location: classes/dashboard.php");
} else 
    if (isset($_POST['btnLogin']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        session_start();
        
        if (file_exists(ini_file) && is_file(ini_file)) {
            $_data = parse_ini_file(ini_file);
            if (isset($_data["dsn"]) && isset($_data["user"]) && isset($_data["pwd"]) && isset($_data["host"])) {
                $_dbdsn = $_data["dsn"];
                $_dbhost = $_data["host"];
                $_dbuser = $_data["user"];
                $_dbpwd = $_data["pwd"];
            }
            $_dbdsn = preg_replace("/%h/", $_dbhost, $_dbdsn);
            $_dbdsn = preg_replace("/%s/", dbname, $_dbdsn);
            
            try {
                // : Open connection to the database
                $_dbh = new PDO($_dbdsn, $_dbuser, $_dbpwd);

                $_unclean = (array) array();
                $_clean = (array) array();
                if ($_POST['user_email'] && $_POST['user_pwd']) {
                    
                    $_unclean['user_email'] = $_POST['user_email'];
                    $_unclean['user_pwd'] = $_POST['user_pwd'];
                    if ($_unclean['user_email'] && $_unclean['user_pwd']) {
                            // : Clean inputted data from form/user
                            $_clean['user_email'] = stripslashes($_unclean['user_email']);
                            $_clean['user_pwd'] = stripslashes($_unclean['user_pwd']);
                            // : End
                            
                            $sql = 'SELECT id FROM users WHERE user_email=:email AND user_pwd=:pwd;';
                            $sth = $_dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $sth->execute(array(':email' => $_clean['user_email'], ':pwd' => $_clean['user_pwd']));
                            $_result = $sth->fetchAll();

                            if (count($_result) === 1) {
                                $_SESSION['user_email'] = $_clean['user_email'];
                                $_SESSION['user_pwd'] = $_clean['user_pwd'];
                                $_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
                                $_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
                                $_SESSION['userID'] = $_result[0]['id'];
                                $_dbh = null;
                                header("Location: classes/dashboard.php");
                            } else {
                                $_errors[] = "Invalid email address and/or password. Please re-enter email and password carefully and try again.";
                            }
                    } else {
                        $_errors["Please provide both email and password login details and try again."];
                    }
                }
            } catch (Exception $e) {
                $_errors[$e->getMessage()];
            }
        } else {
            $_errors["Could not find the ini settings file. Please contact the administrator and report this error to him."];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">
<link rel="icon" href="favicon.ico">

<title>Barloworld Transport | Automation System - Login</title>

<!-- Bootstrap core CSS -->
<link href="assets/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom styles for this template -->
<link href="assets/css/signin.css" rel="stylesheet">
</head>

<body>


	<div class="container">

		<form class="form-signin" role="form"
			action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
			<!-- Page heading and company logo -->
			<div class="row placeholders">
				<div class="col-md-12 placeholder">
					<div class="placeholder">
						<h2 class="placeholder">Automation System</h2>
						<img class="displayed" src="assets/img/bwt_transport_logo.png" />
					</div>
				</div>
			</div>
			<!-- End -->

			<!-- Login Form -->
			<h2 class="form-signin-heading">Please sign in</h2>
			<?php
if ($_errors) {
    echo '<div class="alert alert-danger" role="alert"><strong>Login failed:</strong></div>';
    foreach ($_errors as $_value) {
        echo '<div class="alert alert-danger" role="alert"><strong>Error: </strong>' . $_value . '</div>';
    }
    session_destroy();
}
?>
			<label for="inputEmail" class="sr-only">Email address</label> <input
				type="email" name="user_email" id="inputEmail" class="form-control"
				placeholder="Email address" required autofocus> <label
				for="inputPassword" class="sr-only">Password</label> <input
				type="password" name="user_pwd" id="inputPassword"
				class="form-control" placeholder="Password" required>

			<div class="checkbox">
				<label> <input type="checkbox" value="remember-me"> Remember me
				</label>
			</div>

			<button class="btn btn-lg btn-primary btn-block" type="submit"
				name="btnLogin">Login</button>

		</form>
		<!-- End -->

	</div>
	<!-- /container -->

</body>
</html>
