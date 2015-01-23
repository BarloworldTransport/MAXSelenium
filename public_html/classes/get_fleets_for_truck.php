<?php

// : All errors to display for debugging purposes
error_reporting(E_ALL);
ini_set("display_errors", "1");
// : End

// : Includes
include "PullDataFromMySQLQuery.php";
// : End

// : Check if request_method is GET and expected params present then run script process
if ($_SERVER["REQUEST_METHOD"] === "GET" && array_key_exists('truck_id', $_GET) && array_key_exists('data_type', $_GET)) {
	try {
		function get_fleets_for_truck($_truck_id, $_query, $_data_type) {
		    global $_fleets, $_dbh;
		    $_data = (array) array();
			$_sql = preg_replace("/%s/", $_truck_id, $_query);
			$_result = $_dbh->getDataFromQuery($_sql);
			
			if ($_result) {
				foreach($_result as $_value) {
					if (array_key_exists("truck_id", $_value) && array_key_exists("fleet_id", $_value)) {
					    switch ($_data_type) {
					        case "keys" || "key" : {
					            $_data[]= $_value["fleet_id"];
					            break;
					        }
					        case "values" || "value" :
					        default : {
					            $_data[$_value["fleet_id"]]= $_fleets[$_value["fleet_id"]];
					        }
					    }
					}
				}
				return $_data;
			} else {
				return FALSE;
			}
		}
	
		$_queries = array(
				"SELECT id, name FROM udo_fleet ORDER BY name ASC;",
				"SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;",
				"SELECT truck_id, fleet_id FROM udo_fleettrucklink WHERE truck_id=%s;",
                "SELECT ftl.truck_id, ftl.fleet_id FROM udo_fleettrucklink AS ftl LEFT JOIN udo_truck AS t ON (t.id=ftl.truck_id) LEFT JOIN udo_fleet AS f ON (f.id=ftl.fleet_id) LEFT JOIN daterangevalue AS drv ON (drv.objectInstanceId=ftl.id) WHERE (drv.beginDate IS NOT NULL) AND (drv.endDate IS NULL OR drv.endDate >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AND ftl.truck_id=%s;"		        
		);
		
		// : Open a new connection to the DB
		$_dbh = new PullDataFromMySQLQuery('max2', '192.168.1.19');
	
		// : Run query to get all fleets from MAX DB
		$_fleets = (array) array();
		$_result = $_dbh->getDataFromQuery($_queries[0]);
		if ($_result) {
			foreach($_result as $_value) {
				if (array_key_exists("id", $_value) && array_key_exists("name", $_value)) {
					$_fleets[$_value["id"]] = $_value["name"];
				}
			}
		}
		// : End
		
		// : Check fleets array has data and get has truck_id and return results
		if ($_fleets && array_key_exists('truck_id', $_GET) && array_key_exists('data_type', $_GET)) {
			$_data = get_fleets_for_truck($_GET['truck_id'], $_queries[3], $_GET['data_type']);
			if ($_data) {
			     $_result = implode(",", $_data);
			     echo $_result;
			} else {
			    echo "false";
			}
		} else {
			echo "false";
		}
		// : End
		
		// : Close DB Connection
		$_dbh = null;
		// : End
		
	} catch (Exception $e) {
	    // If an error occurs return false
		echo "false";
	}
} else {
    // if not a get request and or expected params not found then return false
	echo "false";
}
// : End