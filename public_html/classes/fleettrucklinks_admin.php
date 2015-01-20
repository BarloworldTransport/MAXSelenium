<?php
const DEFAULT_PROCESS = "fleet_truck_link_batch";

/* 
 * IDEAS TO IMPLEMENT LATER (EXCLUSIVE TO FLEET TRUCK LINKS ADMIN):
 * 1. Filters for trucks and fleets to make administrating quicker
 * 2. Change selecting 1 truck to multiple trucks at a time for a batch process
 * 3. Explore a busy processing foreground message while processing transactions
 * 4. When multiple transactions are to be performed run a mysql transaction
 * 5. RND better security for ajax calls
 * 
 * CHANGES TO BE MADE:
 * 1. Update js for selecting fleets to which multiple trucks belong too instead of (current code) single truck
 * 2. Commit transaction once ready - validate all data and that there is a task to be performed
 * 3. Complete add truck link code to add truck to DB and table on page to reflect current actions added by user
 */

// : Error reporting for debugging purposes
error_reporting(E_ALL);
ini_set("display_errors", "1");
// : End

// : Includes
include "PullDataFromMySQLQuery.php";
// : End

$_ftl_data = (array) array();
$_userStatus = (boolean) false;
$_errors = (array) array();

session_start();

if (isset($_SESSION['user_email']) && isset($_SESSION['user_pwd']) && isset($_SESSION['userAgent']) && isset($_SESSION['IPaddress'])) {
	if (($_SESSION['userAgent']) == $_SERVER['HTTP_USER_AGENT'] || $_SESSION['IPaddress'] == $_SERVER['REMOTE_ADDR']) {
	    
	    $_userStatus = true;
	    
    if (isset($_GET["content"])) {
        switch ($_GET["content"]) {
            case "fleettrucklink" : {
                header("location: fleettrucklinks_admin.php");
                break;
            }
            case "logout" : {
                header("Location: ../logout.php");
                break;
            }
            case "dashboard" : {
                header("Location: dashboard.php");
                break;
            }
            default: {
                break;
            }
        }
    }
	
	try {
		// Boolean used to determined if continuing existing process or starting a new process
		$_newProcess = false;
		
		// : Predefined queries that will be used
		$_queries = array(
				"SELECT id, name FROM udo_fleet ORDER BY name ASC;",
				"SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;",
				"SELECT truck_id, fleet_id FROM udo_fleettrucklink WHERE truck_id=%s;",
				"SELECT ftl.truck_id, ftl.fleet_id FROM udo_fleettrucklink AS ftl LEFT JOIN udo_truck AS t ON (t.id=ftl.truck_id) LEFT JOIN udo_fleet AS f ON (f.id=ftl.fleet_id) LEFT JOIN daterangevalue AS drv ON (drv.objectInstanceId=ftl.id) WHERE (drv.beginDate IS NOT NULL) AND (drv.endDate IS NULL OR drv.endDate >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AND ftl.truck_id=%s;",
				"SELECT * FROM `process` WHERE state != 'completed' AND user_id=%s;",
				"SELECT id FROM users WHERE user_email='%s';",
				"SELECT id, truck_id, fleets, operation, start_date, end_date FROM ftl_data WHERE process_id=%s;"
		);
		// : End
		
		// Open database connection to BWT Auto DB
		$_bwtdb = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');
		
		// Open database connection to MAX
		$_dbh = new PullDataFromMySQLQuery('max2', '192.168.1.19');
		
		// : Check if existing process already started for fleettrucklink. If no process found the start with clean data.
		$_result = $_bwtdb->getDataFromQuery($_queries[4]);
		
		if (count($_result) == 1) {
			// Process exists
		} else if (!$_result) {
			// New process
			$_newProcess = true;
		}
		// : End
		
		// : Get fleets where truck is actively linked
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
		// : End
	
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
	
		// : Run query to get all trucks from MAX DB
		$_trucks = (array) array();
		$_result = $_dbh->getDataFromQuery($_queries[1]);
		if ($_result) {
			foreach($_result as $_value) {
				if (array_key_exists("id", $_value) && array_key_exists("fleetnum", $_value)) {
					$_trucks[$_value["id"]] = $_value["fleetnum"];
				}
			}
		}
		// : End
	
		$_itrucks = array_keys($_trucks);
	
		if ($_trucks && $_fleets) {
			$_fleets_by_truck = get_fleets_for_truck($_itrucks[0], $_queries[3]);
		}
		// : End
	      
		try {
		    $_dbhBWT = new PullDataFromMySQLQuery ( 'bwt_max_auto', 'localhost', 'user', 'pwd' );
		    // : Check if a process already exists for the user and session, else create a new process
		    $_query = preg_replace("/%s/", $_SESSION["user_email"], $_queries[5]);
		    $_result = $_dbhBWT->getDataFromQuery ( $_query );
		    
		    if (isset ( $_result [0] ['id'] )) {
		        $_user_id = $_result [0] ['id'];
		        
		        $_query = preg_replace("/%s/", $_user_id, $_queries[4]);
		        $_result = $_dbhBWT->getDataFromQuery ( $_query );
		        // : Add data for the process to the ftl_data table
		        if (isset ( $_result [0] ['id'] )) {
		            $_process_id = $_result [0] ['id'];
		        } else {
		            $_errors [] = "Something failed. Could not obtain the process id.";
		        }
		    } else {
		        $_errors [] = "Something failed. Could not obtain the user id.";
		    }
		    		    
		    // : End
		    if (isset ( $_process_id )) {
		        if ($_process_id) {
		            $_data = ( array ) array ();
		            $_query = preg_replace ( "/%s/", $_process_id, $_queries [6] );
		            $_queryResult = $_dbhBWT->getDataFromQuery ( $_query );
		            if ($_queryResult) {
		                foreach ( $_queryResult as $_value ) {
		                    $_data [] = $_value;
		                }
		            }
		        }
		    }
		} catch ( Exception $e ) {
		    $_errors [] = $e->getMessage ();
		}
		
		// Close DB connection
		$_dbh = null;
		$_dbhBWT = null;
	
	} catch (Exception $e) {
		die("Was not able to successfully connect to the database.");
	}
	
	// : End
} else {
	header("Location: ../logout.php");
}
} else {
    header("Location: ../logout.php");
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="Unknown" >
<link rel="icon" href="../favicon.ico">

<title>Barloworld Transport | MAX Automation System - Fleet Truck Links</title>

<!-- Bootstrap core CSS -->
<link href="../dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom styles for this template -->
<link href="../dist/css/dashboard.css" rel="stylesheet">

<!-- Main JS code for this page -->
<script src="fleettrucklinks_admin.js"></script>

</head>

<body>
	<!-- Fixed navbar -->
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">

			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed"
					data-toggle="collapse" data-target="#navbar" aria-expanded="false"
					aria-controls="navbar">
					<span class="sr-only">Toggle navigation</span> <span
						class="icon-bar"></span> <span class="icon-bar"></span> <span
						class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">BWT Automation</a>
			</div>
			
			<div id="navbar" class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li class="active"><a href="?=content=dashboard">My Dashboard</a></li>
					<li class="dropdown"><a href="#" class="dropdown-toggle"
						data-toggle="dropdown" role="button" aria-expanded="false">Automation
							<span class="caret"></span>
					</a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="#">Reporting</a></li>
							<li><a href="#">Fleet Truck Links</a></li>
							<li><a href="#">Batch Rate Processing</a></li>
						</ul></li>
					<li><a href="#">Settings</a></li>
					<li class="dropdown"><a href="#" class="dropdown-toggle"
						data-toggle="dropdown" role="button" aria-expanded="false">My
							Account <span class="caret"></span>
					</a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="#">My Profile</a></li>
							<li><a href="?content=logout">Logout</a></li>
						</ul></li>
					</ul>
			</div>
			<!--/.nav-collapse -->
			</div>
	</nav>

	<div class="container-fluid">
	
		<div class="row">
		
			<div class="col-sm-3 col-md-2 sidebar">
				<ul class="nav nav-sidebar">
					<li class="active"><a href="?content=dashboard">My Dashboard <span class="sr-only">(current)</span></a></li>
				</ul>
			</div>
			
			<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">

				<div class="jumbotron">
					<h2>Fleet Truck Links</h2>
					<h3>Administration of Fleet Truck Links in MAX</h3>
					<p>Use the form below to administrate truck link changes and click
						add to add each truck link operation individually. When you are
						ready to commit the changes click Commit.</p>
				</div>
				
				<form name="truckLinkForm" id="frmMain" class="form-signin" role="form" action="addTruckLink.php" method="post">
					<h2 id="frmHeading" class="form-signin-heading">Add truck link</h2>
					<div class="row" id="divError" hidden=true>
						<div class="col-md-12">
							<div class="alert alert-danger" role="alert" id="errorMsg"></div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<label for="start_date">Start Date:</label>
						</div>
						<div class="col-md-10">
							<input type="date" class="form-control" id="start_date" name="start_date">
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-2">
							<label for="stop_date">Stop Date:</label>
						</div>
						<div class="col-md-10">
							<input type="date" class="form-control" id="stop_date" name="stop_date">
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-2">
							<label for="cbxOperation">Operation to perform:</label>
						</div>
						<div class="col-md-10">
							<select id="cbxOperation" name="opSelect" class="form-control" onchange="" required>
							     <option value="create">Create New Link</option>
							     <option value="update">Update Existing Link</option>
							</select>
						</div>
					</div>
					
					<div class="row">
					
						<div class="col-md-2">
							<label for="truckId">Select a truck:</label>
						</div>
						<div class="col-md-10">
							<select id="truckId" name="truckSelect" class="form-control">
						          <!-- Dynamically generate select options with trucks from MAX -->
						          <?php
						              if (isset($_trucks)) {
						                  if ($_trucks) {
						                      $a = 1;
						                      foreach($_trucks as $_id => $_fleetnum) {
						                          printf('<option value="%d">%s</option>', $_id, $_fleetnum . ' [' . $_id . ']');
						                          $a++;
						                      }
						                  }
						              }
						          ?>
						<!-- End -->
							</select>
							<div class="row">
								<div class="col-md-2">
									<button class="btn btn-default" name="btnAddTruckToList" type="button" onclick="addTruckToPanel()">Add Truck to Operation</button>
								</div>
								<div class="col-md-10"></div>
							</div>
							<div class="panel panel-default" id="panelTruckList">
								<div class="panel-heading">
									<h3 class="panel-title">Trucks selected:</h3>
								</div>
								<div class="panel-body" id="truckPanelBody">
								<!-- Panel contents for trucks goes here -->
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<label for="fleet_id">Select Fleet(s)</label>
						</div>
						<!-- Dynamically list fleets -->
						<div class="col-md-10">
						<?php
						if (isset($_fleets)) {
						    if ($_fleets) {
						      $a = 1;
						      $_checked = "";
						        foreach($_fleets as $_id => $_fleetname) {
						          if ($_fleets_by_truck) {
										if (array_key_exists($_id, $_fleets_by_truck)) {
						              		$_checked = "true";
						              	}
						          } else {
						              $_checked = "";
						          }
						          printf('<label class="checkbox-inline"> <input type="checkbox" id="cbx_fleet_%d" name="cbxFleet" value="%d" %s>%s</label>', $a, $_id, $_checked, $_fleetname);
						          $a++;
						        }
						   }
						}
						?>
						<!-- End -->
						</div>
					</div>
					
					<!-- Checkbox operation buttons -->
					<div class="row">
						<div class="col-md-2">
						</div>
						<div class="col-md-2">
						  <button class="btn btn-default" name="btnChangeChkbox" type="button" onclick="changeCheckboxState()">Select All</button>
						</div>
						<div class="col-md-8">
						  <button class="btn btn-default" name="btnSelectFleetTruckLinks" type="button" onclick="ajaxResetFleetCheckboxes()">Select Fleets where Truck is Active</button>
						</div>
					</div>
					<!-- End -->
					
					<!-- Table summary of operations -->
					<h4>Operations Summary:</h4>
					<div class="row" id="tblOpList">
					   <table class="table table-hover">
					   <thead>
					       <tr>
					           <th>ID #:</th>
					           <th>Truck IDs:</th>
					           <th>Fleets:</th>
					           <th>Operation:</th>
					           <th>Start Date:</th>
					           <th>Stop Date:</th>
					           <th>Delete:</th>
					       </tr>
					   </thead>
					       <tbody>
					       <!-- Table data goes here -->
					       <?php
					           $_outputHTML = (string) "";
					           if (isset($_data) && (isset($_process_id))) {
					               if ($_process_id && $_data && is_array($_data)) {
					                   foreach ($_data as $_key => $_record) {
					                       $_outputHTML = "<tr>";
					                       foreach ($_record as $_field => $_value) {
					                           
					                           // : Fetch fleet names using the fleet IDs
					                           if ($_field == "fleets" || $_field == "truck_id") {
					                               
					                               $_arrData = array();
					                               if (isset($_fleets) && $_field == "fleets") {
					                                   $_arrData = $_fleets;
					                               } else if (isset($_trucks) && $_field == "truck_id") {
					                                   $_arrData = $_trucks;
					                               }
					                               
					                               $_valuename = '';
					                               // : If there is a comma found in the fleet string then extract each ID and get its fleetname
					                               if (strpos($_value, ',')) {
					                                   
					                                   $_arrValues = explode(',', $_value);
					                                   if ($_arrValues && is_array($_arrValues)) {
					                                       foreach ($_arrValues as $_valuekey => $_valueid) {
					                                           switch ($_valuekey) {
					                                               case 0: {
					                                                   $_valuename = $_arrData[$_valueid];
					                                                   break;
					                                               }
					                                               default: {
					                                                   $_valuename .= "," . $_arrData[$_valueid];
					                                                   break;
					                                               }
					                                           }
					                                       }
					                                   }
					                                  // : End
					                               } else {
					                                   // Else if single fleet ID value then return that fleet IDs fleetname
					                                   $_valuename = $_arrData[$_value];
					                               }
					                               if ($_valuename) {
					                                   $_outputHTML .= "<td>$_valuename</td>";
					                               }
					                               // : End
					                               
					                           } else {
					                               // Else print the fleet IDs for the fleets
					                               
					                               $_outputHTML .= "<td>$_value</td>";
					                           }
					                       }
					                       $_outputHTML .= "<td><a id={$_record['id']}>Delete</a></td></tr>";
					                       if ($_outputHTML) {
					                           print($_outputHTML);
					                       }
					                   }
					               }
					           }
					       ?>
					       </tbody>
					   </table>
					</div>
					<!-- End -->
					
					<div class="row">
						<div class="col-md-6">
							<button class="btn btn-lg btn-primary btn-block" id="btnAddTruckLink" name="addTruckLink" onclick="ajaxAddTruckLink()" type="button">Add</button>
						</div>

						<div class="col-md-6">
							<button class="btn btn-lg btn-primary btn-block" id="btnCommitTruckLinks" name="commitTruckLinks" type="submit">Commit</button>
						</div>
					</div>
				</form>
				
			</div>
		</div>
		</div>

		<!-- Bootstrap core JavaScript
    ================================================== -->
		<!-- Placed at the end of the document so the pages load faster -->
		<script
			src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<script src="../dist/js/bootstrap.min.js"></script>
		<script src="../assets/js/docs.min.js"></script>
</body>
</html>
