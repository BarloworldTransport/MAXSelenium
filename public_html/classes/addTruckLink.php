<?php
// : Error reporting for debugging purposes
error_reporting(E_ALL);
ini_set("display_errors", "1");
// : End

// : Includes
include "PullDataFromMySQLQuery.php";
// : End

$_errors = (array) array();
$_loginStatus = false;
session_start();

var_dump($_POST);
die("thanks");

if (isset($_SESSION['user_email']) && isset($_SESSION['user_pwd']) && isset($_SESSION['userAgent']) && isset($_SESSION['IPaddress'])) {
	
	if (($_SESSION['userAgent']) === $_SERVER['HTTP_USER_AGENT'] && $_SESSION['IPaddress'] === $_SERVER['REMOTE_ADDR'] && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
		$_loginStatus = true;
		if (isset($_POST['truckSelect']) && isset($_POST['start_date']) && isset($_POST['stop_date']) && isset($_POST['opSelect'])) {
			// : Predefined queries that will be used
			$_queries = array(
					"SELECT id, name FROM udo_fleet ORDER BY name ASC;",
					"SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;",
					"SELECT truck_id, fleet_id FROM udo_fleettrucklink WHERE truck_id=%s;",
					"SELECT ftl.truck_id, ftl.fleet_id FROM udo_fleettrucklink AS ftl LEFT JOIN udo_truck AS t ON (t.id=ftl.truck_id) LEFT JOIN udo_fleet AS f ON (f.id=ftl.fleet_id) LEFT JOIN daterangevalue AS drv ON (drv.objectInstanceId=ftl.id) WHERE (drv.beginDate IS NOT NULL) AND (drv.endDate IS NULL OR drv.endDate >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AND ftl.truck_id=%s;",
					"SELECT * FROM `process` WHERE state != 'completed' AND session_id=" . session_id() . ";",
					"SELECT id FROM users WHERE user_email='{$_SESSION['user_email']}';",
					"INSERT INTO `process` (detail, process_type_id, state, process_start, session_id, user_id) VALUES (:detail, :process_type_id, :state, :process_start, :session_id, :user_id);",
					"INSERT INTO ftl_data (process_id, truck_id, fleets, operation, start_date, end_date) VALUES (:process_id, :truck_id, :fleets, :operation, :start_date, :end_date);"
			);
			// : End
			
			// : Fetch all selected fleets for the transaction
			$_fleet_str = (string) $_POST['fleets'];
			if ($_fleet_str) {
				$_fleets_list = explode(",", $_fleet_str);
			} else {
				$_errors[] = "No fleets supplied.";
			}
			
			/*foreach($_POST as $key => $value) {
				if (preg_match("/^fleetSelect.*$/", $key)) {
					preg_match("/^fleetSelect(.*)$/", $key, $_matches);
					if ($_matches) {
						$_fleets_list[] = $_matches[1];
					}
				}
			}*/
			// : End
			
			if ($_fleets_list) {
				$_fleet_str = implode(",", $_fleets_list);
				
				// Open new connection to BWT Auto database
					
				$_dbh = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');
				
				// : Check if a process already exists for the user and session, else create a new process
				$_query = $_queries[4];
				$_result = $_dbh->getDataFromQuery($_query);
				if (!$_result) {
					$_userid = $_SESSION['userID'];
					
					$_dateNew = new DateTime();
					$_unixTimeNow = $_dateNew->getTimestamp();
					
					$_keys = array (
						"detail", "process_type_id", "state", "process_start", "session_id", "user_id"
					);
					
					$_values = array (
							"fleet_truck_link_batch", 1, "in_progess", $_unixTimeNow, session_id(), $_userid
					);
					$_dbh->insertSQLQuery($_keys, $_values, $_queries[6]);
					$_result = $_dbh->getDataFromQuery($_query);
					
					// Check if process was created
					if (!$_result) {
						$_errors[] = "Unable to create new process for user.";
					}
				}
				// : End
				
				// : Add data for the process to the ftl_data table
				if (isset($_result[0]['id'])) {
					$_process_id = $_result[0]['id'];
				} else {
					$_errors[] = "Something failed. Could not obtain the process id.";
				}
				// : End
				
				if (isset($_process_id)) {
					if ($_process_id) {
						if ($_SESSION['start_date']) {
							$_start_date = strtotime($_SESSION['start_date']);
						} else {
							$_errors[] = 'There was no start date specified.';
						}
						if ($_SESSION['stop_date']) {
							$_end_date = strtotime($_SESSION['stop_date']);
						} else {
							$_end_date = NULL;
						}
						$_keys = array('process_id','truck_id', 'fleets', 'operation', 'start_date', 'end_date');
						$_values = array($_process_id, $_SESSION['truckSelect'], $_fleet_str, $_SESSION['opSelect'], $_start_date, $_end_date);
						if ($_dbh->insertSQLQuery($_keys, $_values, $_queries[7])) {
							header('Location: fleettrucklinks_admin.php');
						} else {
							$_errors[] = "Could not successfuly save the new data for the truck link operation into the database. Something went wrong.";
						}
					}
				}
				
				$_dbh = null;
			}
			
		}
	} else {
		$_errors[] = 'User agent and/or remote ip address not the same for the session ID that orginally logged into the system.';
	}
} else {
	$_errors[] = 'User has not logged in. Please login.';
}

if ($_loginStatus) {
	if ($_errors) {
		$_errmsg = implode(",", $_errors);
		echo $_errmsg;
		/*echo "Redirecting back to Fleet Truck Links Admin page..." . PHP_EOL;
		sleep(5);*/
		
	} else {
		echo "true";
	}
	//header("Location: fleettrucklinks_admin.php");
} else {
	header("Location: ../logout.php");
}