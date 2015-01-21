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

if (isset($_SESSION['user_email']) && isset($_SESSION['user_pwd']) && isset($_SESSION['userAgent']) && isset($_SESSION['IPaddress'])) {
    
    if (($_SESSION['userAgent']) === $_SERVER['HTTP_USER_AGENT'] && $_SESSION['IPaddress'] === $_SERVER['REMOTE_ADDR'] && ($_SERVER['REQUEST_METHOD'] == 'GET')) {
        $_loginStatus = true;
        try {
            // : Predefined queries that will be used
            $_queries = array(
                "SELECT id, name FROM udo_fleet ORDER BY name ASC;",
                "SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;"
            );
            // : End
            
            // Open database connection to MAX
            $_dbh = new PullDataFromMySQLQuery('max2', '192.168.1.19');
            
            // : Run query to get all fleets from MAX DB
            $_fleets = (array) array();
            $_result = $_dbh->getDataFromQuery($_queries[0]);
            if ($_result) {
                foreach ($_result as $_value) {
                    if (array_key_exists("id", $_value) && array_key_exists("name", $_value)) {
                        $_fleets[$_value["id"]] = $_value["name"];
                    }
                }
            }
            // : End
            
            // : Run query to get all trucks from MAX DB
            $_trucks = (array) array();
            $_result = $_dbh->getDataFromQuery($_queries[1]);
            if ($_result) {
                foreach ($_result as $_value) {
                    if (array_key_exists("id", $_value) && array_key_exists("fleetnum", $_value)) {
                        $_trucks[$_value["id"]] = $_value["fleetnum"];
                    }
                }
            }
            // : End
        } catch (Exception $e) {
            $_errors[] = $e->getMessage();
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
        echo json_encode(array(
            'phpresult' => 'false',
            'phperrors' => $_errmsg
        ));
    } else {
        try {
            $_jsonData = (array) array();
            
            if (isset($_trucks)) {
                foreach ($_trucks as $_key => $_value) {
                    $_jsonData["trucks"][$_key] = $_value;
                }
            }
            
            if (isset($_fleets)) {
                foreach ($_fleets as $_key => $_value) {
                    $_jsonData["fleets"][$_key] = $_value;
                }
            }
            
            if (isset($_jsonData)) {
                if ($_jsonData) {
                    echo json_encode($_jsonData);
                }
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'phpresult' => 'false',
                'phperrors' => $e->getMessage()
            ));
        }
    }
} else {
    header("Location: ../logout.php");
}