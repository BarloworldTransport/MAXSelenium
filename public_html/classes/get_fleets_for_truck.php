<?php
error_reporting(E_ALL);
ini_set("display_errors", "1");
include "PullDataFromMySQLQuery.php";

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET)) {
	try {
		function get_fleets_for_truck($_truck_id, $_query) {
			$_data = (array) array();
			global $_fleets, $_dbh;
			$_sql = preg_replace("/%s/", $_truck_id, $_query);
			$_result = $_dbh->getDataFromQuery($_sql);
	
			if ($_result) {
				foreach($_result as $_value) {
					var_dump($_value);
					if (array_key_exists("truck_id", $_value) && array_key_exists("fleet_id", $_value)) {
						$_data[$_value["fleet_id"]]= $_fleets[$_value["fleet_id"]];
						return $_data;
					} else {
						return FALSE;
					}
				}
			} else {
				return FALSE;
			}
		}
	
		$_queries = array(
				"SELECT id, name FROM udo_fleet ORDER BY name ASC;",
				"SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;",
				"SELECT truck_id, fleet_id FROM udo_fleettrucklink WHERE truck_id=%s;"
		);
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
		
		if ($_fleets && array_key_exists('truckSelect', $_GET) ) {
			$_data = get_fleets_for_truck($_GET['truckSelect'], $_queries[2]);
			echo implode(',', $_data);
		} else {
			echo "false";
		}
		// : End
	
	} catch (Exception $e) {
		echo "false";
	}
} else {
	echo "false";
}