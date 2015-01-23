<?php
define("DS", DIRECTORY_SEPARATOR);

$_unclean = ( array ) array ();
$_clean = ( array ) array ();

if (isset ( $argv )) {
	if ($argc > 2) {
		die ( "You may only enter one argument to the script" );
	} else {
		foreach ( $argv as $_value ) {
			if ($_value !== $_SERVER ['PHP_SELF']) {
				$_unclean ["pwd"] = $_value;
			}
		}
	}
}
function _autoload($_classname) {
	spl_autoload ( "classes" . DS . $_classname, ".php" );
}

if (array_key_exists ( 'pwd', $_unclean )) {
	
	spl_autoload_register ( "_autoload" );
	$_classname = "pwd_tools";
	$newPwdObj = new $_classname ();
	
	$_hashed_pwd = $newPwdObj->create_pwd ($_unclean ['pwd']);
	
	$_file = date ( "Y-m-d_H_i_s" ) . "_pwd.txt";
	
	if ($fh = fopen ( $_file, "w+" )) {
		fwrite ( $fh, $_hashed_pwd );
		fclose ( $fh );
		echo "New password save to file {$_file}\n";
	}
}