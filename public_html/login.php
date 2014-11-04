<?php
// : Constants
CONST db_host = "localhost";
CONST db_user = "selenium_user";
CONST db_pwd = "Cwright2207";
CONST db_tenant = "selenium_test";

// : Variables
$error = "";

// Start session
session_start ();

if (isset ( $_POST ["submit"] )) {
	if (empty ( $_POST ["login_email"] ) || (empty ( $_POST ["login_pwd"] ))) {
		$_error = "ERROR: Your email and/or password were not entered. Please complete the login form and try again.";
	} else {
		$_email = $_POST ["login_email"];
		$_pwd = $_POST ["login_pwd"];
		
		$_email = stripslashes($_email);
		$_pwd = stripslashes($_pwd);
		
		$_email = mysql_real_escape_string($_email);
		$_pwd = mysql_real_escape_string($_pwd);
		
		// Open new connection to SQL Server
		$_sqlconn = mysql_connect ( db_host, db_user, db_pwd );
		
		// Open the DB
		$_db = mysql_select_db ( db_tenant, $_sqlconn );
		
		// Query the DB
		$_query = mysql_query ( "SELECT `user_email`, `user_pwd` FROM `users` WHERE `user_email`='$_email' AND `user_pwd`='$_pwd'" );
		
		$_count = mysql_num_rows($_query);
		if ($_count > 0) {
			$_SESSION["user_email"];
			header("location: home.php");
		} else {
			$_error = "You have entered your email or password incorrectly. Please check them and try again.";
		}
		// Close connection to SQL Server
		mysql_close($_sqlconn);
	}
}

