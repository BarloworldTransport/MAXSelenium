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
                "SELECT * FROM `process` WHERE state != 'completed' and user_id=%s;",
                "SELECT id FROM users WHERE user_email='%s';",
                "SELECT id, truck_id, fleets, operation, start_date, end_date FROM ftl_data WHERE process_id=%s;"
            );
            // : End
            
            $_dbh = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');
            
            $_query = preg_replace("/%s/", $_SESSION['user_email'], $_queries[1]);
            $_result = $_dbh->getDataFromQuery($_query);
            if ($_result) {
                $_userid = $_result[0]['id'];
            }
            
            // : Check if a process already exists for the user and session, else create a new process
            if (isset($_userid)) {
                $_query = preg_replace("/%s/", $_userid, $_queries[0]);
                $_result = $_dbh->getDataFromQuery($_query);
                // : Add data for the process to the ftl_data table
                if (isset($_result[0]['id'])) {
                    $_process_id = $_result[0]['id'];
                } else {
                    $_errors[] = "Something failed. Could not obtain the process id.";
                }
            } else {
                $_errors[] = "Something failed. Could not obtain the user id.";
            }
            // : End
            
            if (isset($_process_id)) {
                if ($_process_id) {
                    $_data = (array) array();
                    $_query = preg_replace("/%s/", $_process_id, $_queries[2]);
                    $_queryResult = $_dbh->getDataFromQuery($_query);
                    if ($_queryResult) {
                        foreach ($_queryResult as $_value) {
                            $_data[] = $_value;
                        }
                    }
                }
            }
            
            $_dbh = null;
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
            if (isset($_data)) {
                $_jsonData = (array) array();
                foreach ($_data as $_key => $_value) {
                    foreach ($_value as $_fieldKey => $_fieldValue) {
                        $_jsonData[$_key][$_fieldKey] = $_fieldValue;
                    }
                }
                echo json_encode($_jsonData);
            } else {
                echo json_encode(array(
                    'phpresult' => 'false',
                    'phperrors' => 'Data array empty.'
                ));
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