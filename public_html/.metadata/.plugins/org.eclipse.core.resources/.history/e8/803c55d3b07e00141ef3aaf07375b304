<?php
// HTTP requests have no restriction on the fields they contain. Client side validation is never sufficient.

// Array used to store filtered data
$_cleanParam = (array) array();
$_error = (string) "";

session_start();

if (isset($_POST['color'])) {
switch ($_POST['color']) {
	case 'red' : 
	case 'green' :
	case 'blue' :
		$_cleanParam['color'] =  $_POST['color'];
		header("Location: pickacolor.php");
		break;
	default :
		/* ERROR */
		$_error = "Invalid color selection. Please select a color from the given selectbox and try again.\n";
	break;
}
}
