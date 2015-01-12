<?php

// : Error reporting for debugging purposes
error_reporting(E_ALL);
ini_set("display_errors", "1");
// : End

include "PullDataFromMySQLQuery.php";

$_dbh = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');

$_keys = array("detail", "process_type_id", "state", "process_start", "session_id", "user_id");
$_values = array("myDetail", 1, 1, 20140112, "aSessionID", 1);
$_query = "INSERT INTO `process` (detail, process_type_id, state, process_start, session_id, user_id) VALUES (:detail, :process_type_id, :state, :process_start, :session_id, :user_id);";

if ($_dbh->insertSQLQuery($_keys, $_values, $_query)) {
	$_dbh = null;
	die("success");
} else {
	$_dbh = null;
	die("failed.");
}