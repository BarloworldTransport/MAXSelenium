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
    
    if (($_SESSION['userAgent']) === $_SERVER['HTTP_USER_AGENT'] && $_SESSION['IPaddress'] === $_SERVER['REMOTE_ADDR'] && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
        $_loginStatus = true;
        if (isset($_POST['truckSelect']) && isset($_POST['start_date']) && isset($_POST['stop_date']) && isset($_POST['opSelect'])) {
            try {
                // : Predefined queries that will be used
                $_queries = array(
                    "SELECT id, name FROM udo_fleet ORDER BY name ASC;",
                    "SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;",
                    "SELECT truck_id, fleet_id FROM udo_fleettrucklink WHERE truck_id=%s;",
                    "SELECT ftl.truck_id, ftl.fleet_id FROM udo_fleettrucklink AS ftl LEFT JOIN udo_truck AS t ON (t.id=ftl.truck_id) LEFT JOIN udo_fleet AS f ON (f.id=ftl.fleet_id) LEFT JOIN daterangevalue AS drv ON (drv.objectInstanceId=ftl.id) WHERE (drv.beginDate IS NOT NULL) AND (drv.endDate IS NULL OR drv.endDate >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AND ftl.truck_id=%s;",
                    "SELECT * FROM `process` WHERE state != 'completed' AND user_id=%s;",
                    "SELECT id FROM users WHERE user_email='%s';",
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
                
                /*
                 * foreach($_POST as $key => $value) { if (preg_match("/^fleetSelect.*$/", $key)) { preg_match("/^fleetSelect(.*)$/", $key, $_matches); if ($_matches) { $_fleets_list[] = $_matches[1]; } } }
                 */
                // : End
                
                // Fetch all trucks for the transaction
                $_trucks_list = explode(",", $_POST['truckSelect']);
                
                if ($_fleets_list && is_array($_trucks_list) && $_trucks_list) {
                    $_fleet_str = implode(",", $_fleets_list);
                    $_trucks_str = implode(",", $_trucks_list);
                    
                    // Open new connection to BWT Auto database
                    
                    $_dbh = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');
                    
                    // : Check if a process already exists for the user and session, else create a new process
                    $_query = preg_replace("/%s/", $_SESSION['user_email'], $_queries[5]);
                    $_result = $_dbh->getDataFromQuery($_query);
                    
                    if ($_result) {
                        $_userid = $_result[0]['id'];
                    } else {
                        $_errors[] = "Something happened. Could not obtain the user id.";
                    }
                    if (isset($_userid)) {
                        
                        $_query = preg_replace("/%s/", $_userid, $_queries[4]);
                        $_result = $_dbh->getDataFromQuery($_query);
                        if (! $_result) {
                            
                            $_dateNew = new DateTime();
                            $_unixTimeNow = $_dateNew->getTimestamp();
                            
                            $_keys = array(
                                "detail",
                                "process_type_id",
                                "state",
                                "process_start",
                                "session_id",
                                "user_id"
                            );
                            
                            $_values = array(
                                "fleet_truck_link_batch",
                                1,
                                "in_progess",
                                $_unixTimeNow,
                                session_id(),
                                $_userid
                            );
                            $_dbh->insertSQLQuery($_keys, $_values, $_queries[6]);
                            $_result = $_dbh->getDataFromQuery($_query);
                            
                            // Check if process was created
                            if (! $_result) {
                                $_errors[] = "Unable to create new process for user.";
                            }
                        } else {
                            // : Get process ID
                            if ($_result) {
                                $_process_id = $_result[0]['id'];
                            } else {
                                $_errors[] = "Something failed. Could not obtain the process id.";
                            }
                            // : End
                        }
                    }
                    // : End
                    
                    if (isset($_process_id)) {
                        if ($_process_id) {
                            
                            if ($_POST['start_date']) {
                                $_start_date = strtotime($_POST['start_date']);
                            } else {
                                $_errors[] = 'There was no start date specified.';
                            }
                            if ($_POST['stop_date']) {
                                $_end_date = strtotime($_POST['stop_date']);
                            } else {
                                $_end_date = NULL;
                            }
                            if ($_POST['opSelect'] == 'create' || $_POST['opSelect'] == 'update') {
                                $_operation = $_POST['opSelect'];
                            } else {
                                $_errors[] = "Operation was not set. Defaulted to value = create";
                                $_operation = "create";
                            }
                            
                            $_keys = array(
                                'process_id',
                                'truck_id',
                                'fleets',
                                'operation',
                                'start_date',
                                'end_date'
                            );
                            $_values = array(
                                $_process_id,
                                $_trucks_str,
                                $_fleet_str,
                                $_operation,
                                $_start_date,
                                $_end_date
                            );
                            
                            if ($_dbh->insertSQLQuery($_keys, $_values, $_queries[7]) !== true) {
                                $_errors[] = "Could not successfuly save the new data for the truck link operation into the database. Something went wrong.";
                            }
                        }
                    }
                }
                
                $_dbh = null;
            } catch (Exception $e) {
                $_errors[] = $e->getMessage();
            }
        } else {
            $_errors[] = "Validation of POST data failed.";
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
        echo json_encode(array(
            'phpresult' => 'true'
        ));
    }
} else {
    header("Location: ../logout.php");
}