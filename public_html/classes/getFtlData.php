<?php
// : Error reporting for debugging purposes
error_reporting ( E_ALL );
ini_set ( "display_errors", "1" );
// : End

// : Includes
include "PullDataFromMySQLQuery.php";
// : End

$_errors = ( array ) array ();
$_loginStatus = false;

session_start ();

if (isset ( $_SESSION ['user_email'] ) && isset ( $_SESSION ['user_pwd'] ) && isset ( $_SESSION ['userAgent'] ) && isset ( $_SESSION ['IPaddress'] )) {
	
	if (($_SESSION ['userAgent']) === $_SERVER ['HTTP_USER_AGENT'] && $_SESSION ['IPaddress'] === $_SERVER ['REMOTE_ADDR'] && ($_SERVER ['REQUEST_METHOD'] == 'GET')) {
		$_loginStatus = true;
		if (isset ( $_GET ['truckSelect'] ) && isset ( $_POST ['start_date'] ) && isset ( $_POST ['stop_date'] ) && isset ( $_POST ['opSelect'] )) {
			try {
			// : Predefined queries that will be used
			$_queries = array (
					"SELECT id, name FROM udo_fleet ORDER BY name ASC;",
					"SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;",
					"SELECT truck_id, fleet_id FROM udo_fleettrucklink WHERE truck_id=%s;",
					"SELECT ftl.truck_id, ftl.fleet_id FROM udo_fleettrucklink AS ftl LEFT JOIN udo_truck AS t ON (t.id=ftl.truck_id) LEFT JOIN udo_fleet AS f ON (f.id=ftl.fleet_id) LEFT JOIN daterangevalue AS drv ON (drv.objectInstanceId=ftl.id) WHERE (drv.beginDate IS NOT NULL) AND (drv.endDate IS NULL OR drv.endDate >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AND ftl.truck_id=%s;",
					"SELECT * FROM `process` WHERE state != 'completed' AND session_id=\"" . session_id () . "\";",
					"SELECT id FROM users WHERE user_email='{$_SESSION['user_email']}';",
					"INSERT INTO `process` (detail, process_type_id, state, process_start, session_id, user_id) VALUES (:detail, :process_type_id, :state, :process_start, :session_id, :user_id);",
					"INSERT INTO ftl_data (process_id, truck_id, fleets, operation, start_date, end_date) VALUES (:process_id, :truck_id, :fleets, :operation, :start_date, :end_date);" 
			);
			// : End
			
			$_dbh = new PullDataFromMySQLQuery ( 'bwt_max_auto', 'localhost', 'user', 'pwd' );
			
			// : Check if a process already exists for the user and session, else create a new process
			$_query = $_queries [4];
			$_result = $_dbh->getDataFromQuery ( $_query );
			// : Add data for the process to the ftl_data table
			if (isset ( $_result [0] ['id'] )) {
				$_process_id = $_result [0] ['id'];
			} else {
				$_errors [] = "Something failed. Could not obtain the process id.";
			}
			// : End
			
			$_dbh = null;
		} catch (Exception $e) {
			$_errors [] = $e->getMessage();
		}
		} else {
			$_errors [] = "Validation of POST data failed.";
		}
} else {
	$_errors [] = 'User agent and/or remote ip address not the same for the session ID that orginally logged into the system.';
}
} else {
	$_errors[] = 'User has not logged in. Please login.';
}

if ($_loginStatus) {
	if ($_errors) {
		$_errmsg = implode(",", $_errors);
		echo json_encode(array('phpresult' => 'false', 'phperrors' => $_errmsg));
	} else {
		echo json_encode(array('phpresult' => 'true'));
	}
} else {
	header("Location: ../logout.php");
}