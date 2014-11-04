<?php
include "login.php";
?>
<!DOCTYPE html>
<html>
<head>
<title>
</title>
</head>
<body>
<section id="login_frame">
	<h1>MAX Automation System Login</h1>
	<section id="login_form_top">
		<article id="image_logo">
		</article>
	</section>
	<section id="login_form_bottom">
		<h2>Login</h2>
		<form action="" method="post">
		<label>Email:</label>
		<input id="user_email" name="user_email" type="text" placeholder="Enter your email address here">
		<label>Password:</label>
		<input id="user_pwd" name="user_pwd" type="password" placeholder="**********">
		<input name="submit" type="submit" value="login">
		</form>
		<?=$_error?>
	</section>
</section>
</body>
</html>