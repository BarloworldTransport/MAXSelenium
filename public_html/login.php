<?php
// : Constants
define ( "INI_FILE", "config%ssettings.ini" );
define ( "DS", DIRECTORY_SEPARATOR );

// : Variables
$_error = "";
$_clean = ( array ) array ();

// : Functions
function _autoload($_classname) {
	spl_autoload ( "classes" . DS . $_classname, ".php" );
}

// Start session
session_start ();

if (isset ( $_POST ["submit"] )) {
	
	spl_autoload_register ( "_autoload" );
	$_pwdObj = new pwd_tools ();
	
	$_ini_file = sprintf ( INI_FILE, DS );
	
	if (file_exists ( $_ini_file )) {
		$_data = parse_ini_file ( $_ini_file );
		if ((isset ( $_data ["db_host"] ) && $_data ["db_host"]) && (isset ( $_data ["db_name"] ) && $_data ["db_name"]) && (isset ( $_data ["db_user"] ) && $_data ["db_user"]) && (isset ( $_data ["db_pwd"] ) && $_data ["db_pwd"])) {
			$_clean ["db_host"] = $_data ["db_host"];
			$_clean ["db_name"] = $_data ["db_name"];
			$_clean ["db_user"] = $_data ["db_user"];
			$_clean ["db_pwd"] = $_data ["db_pwd"];
		} else {
			die ( "Sorry but the settings.ini does not have all the required fields. Please make sure the following fields are present: db_host, db_name, db_user, db_pwd\n" );
		}
	} else {
		die ( "Fatal error: settings.ini file not found. This file is needed for this script." );
	}
	
	// : Initializing PDO Objects and making connection to DB
	$_dsn = sprintf ( "mysql:host=%2$s;dbname=%1$s", $_clean ["db_name"], $_clean ["db_host"] );
	try {
		$_db = new PDO ( $_dsn, $_clean ["db_user"], $_clean ["db_pwd"] );
	} catch ( Exception $e ) {
		die ("Failed to connect to the database. Check settings.ini file.\n");
	}
	
	// : Verifying password
	
	if (empty ( $_POST ["user_email"] ) || (empty ( $_POST ["user_pwd"] ))) {
		$_error = "ERROR: Your email and/or password were not entered. Please complete the login form and try again.";
	} else {
		$_email = $_POST ["user_email"];
		$_pwd = $_POST ["user_pwd"];
		
		$_email = stripslashes ( $_email );
		$_pwd = stripslashes ( $_pwd );
		
		$_email = mysql_real_escape_string($_email);
		$_pwd = mysql_real_escape_string($_pwd);
		
		// Query the DB
		$_stmt = $_db->prepare ( "SELECT `user_email`, `user_password` FROM `users` WHERE `user_email`=:user_email AND `user_password`=:user_pwd;" );
		$_stmt->bindParam(":user_email", $_email);
		$_stmt->bindParam(":user_pwd", $_pwd);
		$_rows = $_stmt->execute();
		
		$_count = count($_rows);
		if ($_count > 0) {
			$_SESSION ["user_email"] = $_email;
			header ( "location: home.php" );
		} else {
			$_error = "You have entered your email or password incorrectly. Please check them and try again.";
		}
		// Close connection to SQL Server
		mysql_close ( $_sqlconn );
	}
}

