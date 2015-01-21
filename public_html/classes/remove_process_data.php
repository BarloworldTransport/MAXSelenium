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
        if (isset($_POST['ftl_id'])) {
            try {
                // : Predefined queries that will be used
                $_queries = array(
                    "SELECT * FROM `process` WHERE state != 'completed' AND user_id=%s;",
                    "SELECT id FROM users WHERE user_email='%s';",
                	"SELECT id, process_id FROM ftl_data WHERE id=%d;",
                    "DELETE FROM ftl_data WHERE id=:ftl_id;"
                );
                // : End
                
                if ($_POST['ftl_id']) {
                    
                    // Open new connection to BWT Auto database
                    
                    $_dbh = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');
                    
                    // : Check if a process already exists for the user and session, else create a new process
                    $_query = preg_replace("/%s/", $_SESSION['user_email'], $_queries[1]);
                    $_result = $_dbh->getDataFromQuery($_query);
                    
                    if ($_result) {
                        $_userid = $_result[0]['id'];
                    } else {
                        $_errors[] = "Something happened. Could not obtain the user id.";
                    }
                    if (isset($_userid)) {
                        
                        $_query = preg_replace("/%s/", $_userid, $_queries[0]);
                        $_result = $_dbh->getDataFromQuery($_query);
                        if ($_result) {
                        	
                            if (isset($_result[0]['id'])) {
                            	$_process_id = $_result[0]['id'];
                            }
                            
                            if (isset($_process_id)) {
                        	$_query = preg_replace("/%d/", $_POST['ftl_id'], $_queries[2]);
                        	$_resultB = $_dbh->getDataFromQuery($_query);
                        	
                        	if ($_resultB) {
                        		if (isset($_resultB[0]['id']) && isset($_resultB[0]['process_id'])) {
                        			
                        			if ($_resultB[0]['process_id'] == $_process_id) {
                        				
                        				$_keys = array(
                        						"id"
                        				);
                        				
                        				$_values = array(
                        						$_POST['ftl_id']
                        				);
                        				
                        				// Run query to delete the FTL_DATA record
                        				$_dbh->insertSQLQuery($_keys, $_values, $_queries[3]);
                        				
                        				// Rerun query to check if the record has been deleted
                        				$_result = $_dbh->getDataFromQuery($_query);
                        				
                        				// Check if the record still exists, report error
                        				if ($_result) {
                        					$_errors[] = "Failed to delete process data record.";
                        				}
                        				
                        			}
                        		} else {
                        			$_errors[] = "Active process ID does not match for this user for which you are deleting the record.";
                        		}
                        	} else {
                        		$_errors[] = "The record you requested to be deleted, does not exist.";
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