<?php
// : Error reporting for debugging purposes
error_reporting(E_ALL);
ini_set("display_errors", "1");
// : End
const INI_DIR = "config";
const INI_FILE = "automation.ini";
// : Includes
include "PullDataFromMySQLQuery.php";
// : End

$_errors = (array) array();
$_loginStatus = false;

session_start();

if (isset($_SESSION['user_email']) && isset($_SESSION['user_pwd']) && isset($_SESSION['userAgent']) && isset($_SESSION['IPaddress'])) {
    
    if (($_SESSION['userAgent']) === $_SERVER['HTTP_USER_AGENT'] && $_SESSION['IPaddress'] === $_SERVER['REMOTE_ADDR'] && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
        
        $_loginStatus = true;
        
        $ini = dirname ( realpath ( __FILE__ ) ) . self::DS . self::INI_DIR . self::DS . self::INI_FILE;
        
        if (is_file ( $ini ) === FALSE) {
            $_errors[] = "File not found for automation configuration: $ini";
        }
        
        $_config = parse_ini_file ( $ini );
        
        if (isset($_POST['ftl_id'])) {
            try {
                // : Predefined queries that will be used
                $_queries = array(
                    "SELECT * FROM `process` WHERE state != 'completed' AND user_id=%s;",
                    "SELECT id FROM users WHERE user_email='%s';",
                    "SELECT process_id, truck_id, fleets, operation, start_date, end_date FROM ftl_data WHERE process_id=%d;",
                    "UPDATE `process` SET state = 'completed' WHERE id=:process_id;"
                );
                // : End
                
                // Open new connection to BWT Auto database
                $_dbh = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');
                
                // : Check if a process already exists for the user and session else fail
                $_query = preg_replace("/%s/", $_SESSION['user_email'], $_queries[1]);
                $_result = $_dbh->getDataFromQuery($_query);
                
                if ($_result) {
                    $_userid = $_result[0]['id'];
                } else {
                    $_errors[] = "Something happened. Could not obtain the user id.";
                }
                if (isset($_userid)) {
                    
                    // : Check if there is an active process for the user
                    $_query = preg_replace("/%s/", $_userid, $_queries[0]);
                    $_result = $_dbh->getDataFromQuery($_query);
                    
                    if ($_result) {
                        
                        if (isset($_result[0]['id'])) {
                            $_process_id = $_result[0]['id'];
                        }
                        // : End
                        
                        if (isset($_process_id)) {
                            
                            $_query = preg_replace("/%d/", $_process_id, $_queries[2]);
                            $_resultB = $_dbh->getDataFromQuery($_query);
                            if ($_resultB) {
                                $_exclude_key = (array) array(
                                    "process_id" => ""
                                );
                                $_data = (array) array();
                                $_headers = (array) array();
                                
                                if (isset($_resultB['process_id']) && isset($_resultB['truck_id']) && isset($_resultB['fleets']) && isset($_resultB['operation'])) {
                                    
                                    // Get result headers and exclude the process_id indice
                                    $_headers = array_keys(array_diff_key($_resultB[0], $_exclude_key));
                                    
                                    // : Get all ftl data for the process
                                    foreach ($_resultB as $key1 => $value1) {
                                        
                                        $_num = count($value1);
                                        $x = 0;
                                        
                                        foreach ($value1 as $key2 => $value2) {
                                            if ($key2 != "process_id") {
                                                $_data[$key1] .= "'$value2'";
                                                
                                                if ($x != ($_num - 1)) {
                                                    $_data[$key1] .= ",";
                                                }
                                                
                                                $x ++;
                                            }
                                        }
                                    }
                                    // : End
                                    
                                    // : Generate CSV file and save into automation inbox
                                    $fp = fopen('file.csv', 'w');
                                    foreach ($list as $fields) {
                                        fputcsv($fp, $fields);
                                    }
                                    fclose($fp);
                                    // : End
                                    
                                    // : Update the process state to completed
                                    $_keys = array(
                                        "process_id"
                                    );
                                    
                                    $_values = array(
                                        $_process_id
                                    );
                                    
                                    // Run query to delete the FTL_DATA record
                                    $_dbh->insertSQLQuery($_keys, $_values, $_queries[3]);
                                    
                                    // Rerun query to check if the record has been deleted
                                    $_query = preg_replace("/%d/", $_userid, $_queries[0]);
                                    $_result = $_dbh->getDataFromQuery($_query);
                                    
                                    // Check if the record still exists, report error
                                    if ($_result) {
                                        $_errors[] = "Failed to update the process state.";
                                    }
                                    // : End
                                }
                            } else {
                                $_errors[] = "There is no transactions created for this process. Please first add transactions and then commit.";
                            }
                        } else {
                            $_errors[] = "No active process found for your user. Cannot continue.";
                        }
                    }
                } else {
                    $_errors[] = "Could not find the user your session data identifies. Will not continue.";
                }
                // : End
            }
            
            // Close DB connection
            $_dbh = null;
        } catch (Exception $e) 
        {
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
        echo json_encode(array(
            'phpresult' => 'true'
        ));
    }
} else {
    header("Location: ../logout.php");
}