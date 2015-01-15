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

session_start();

if (isset($_SESSION['user_email']) && isset($_SESSION['user_pwd']) && isset($_SESSION['userAgent']) && isset($_SESSION['IPaddress'])) {
	if (($_SESSION['userAgent']) == $_SERVER['HTTP_USER_AGENT'] || $_SESSION['IPaddress'] == $_SERVER['REMOTE_ADDR']) {
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
		// Debug variable that can be echo'ed inside javascript code
		$_debugjs = "testing";
		
		// : Predefined queries that will be used
		$_queries = array(
				"SELECT id, name FROM udo_fleet ORDER BY name ASC;",
				"SELECT id, fleetnum FROM udo_truck ORDER BY fleetnum ASC;",
				"SELECT truck_id, fleet_id FROM udo_fleettrucklink WHERE truck_id=%s;",
				"SELECT ftl.truck_id, ftl.fleet_id FROM udo_fleettrucklink AS ftl LEFT JOIN udo_truck AS t ON (t.id=ftl.truck_id) LEFT JOIN udo_fleet AS f ON (f.id=ftl.fleet_id) LEFT JOIN daterangevalue AS drv ON (drv.objectInstanceId=ftl.id) WHERE (drv.beginDate IS NOT NULL) AND (drv.endDate IS NULL OR drv.endDate >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AND ftl.truck_id=%s;",
				"SELECT * FROM `process WHERE state != 'completed' AND session_id=" . session_id() . ";",
				"SELECT id FROM users WHERE user_email='{$_SESSION['user_email']}';",
				"INSERT INTO `process` (detail, process_type_id, state, process_start, session_id) VALUES (:detail, :process_type_id, :state, :process_start, :session_id);"
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
	
		// Close DB connection
		$_dbh = null;
	
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
<meta name="author" content="">
<link rel="icon" href="../favicon.ico">

<title>Barloworld Transport | MAX Automation System - Fleet Truck Links</title>

<!-- Bootstrap core CSS -->
<link href="../dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom styles for this template -->
<link href="../dist/css/dashboard.css" rel="stylesheet">

<!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
<!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
<script src="../assets/js/ie-emulation-modes-warning.js"></script>

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>

	<!--  AJAX Code for loading data onchange of select elements -->
	<script type="text/javascript">

	// : Global scope variables
	var countInterval, countTmr;
	var trucksList = new Array();

	function drawLbl(truckName, truckAction, truckID) {
		var lblResult = "<span id=\"lbl" + truckID + "\" class=\"label label-info\">" + truckName + "<a id=\"" + truckID + "\" onclick=\"" + truckAction + "\">X</a></span>";
		return lblResult;
	}

	function drawTruckPanel() {
		// Store element for the panel body into a variable
		var truckPanel = document.getElementById("truckPanelBody");
		var tCount = trucksList.length;

		// : Build string to be inserted into the panel body
		var panelStr = "<h4>";
		for (x = 0; x < tCount; x++) {
			panelStr += drawLbl(trucksList[x], "truckRemove(this)", x);
		}
		panelStr += "</h4>";
		// : End
		
		/* 	Insert the HTML string into the panel which contains the labels for each truck
		*	that has been added by the user during this transaction
		*/
		truckPanel.innerHTML = panelStr;
	}

	function addTruckToPanel() {
		var truckSel = document.getElementById("truckId");
		var truckValue = truckSel.options[truckSel.selectedIndex].text;
		
		// : Check if truck has been added to trucksList array and if not add truck and redraw the truck list panel
		if (trucksList.indexOf(truckValue) == -1) {
			trucksList.push(truckValue);
			drawTruckPanel();
		}
		// : End
	}

	function truckRemove(lblElement) {
		// : Setup variables to get the span element that was clicked
		var truckPanel = document.getElementById("truckPanelBody");
		var elementID = "lbl" + lblElement.id;
		var spanValue = document.getElementById(elementID).innerHTML;
		var truckValue = spanValue.substr(0, spanValue.indexOf("<a id="));
		var spanElement = document.getElementById(elementID);
		
		// : Remove truck from the trucksList array and redraw the truck list panel
		var arrIndex = trucksList.indexOf(truckValue);
		if (arrIndex > -1) {
			trucksList.splice(arrIndex, 1);
			drawTruckPanel();
		}
		// : End
	}

	// : Set all checkbox input elements to true|false
    function setAllCheckboxStates(chkboxState) {
		var btnLabel = document.truckLinkForm.btnChangeChkbox;
	    // Assign count value to php fleets array count value
		var chkboxCount = <?php echo count($_fleets);?>;

	    // : Change label of button
		if (chkboxState == true) {
			btnLabel.innerHTML = "Deselect All";
		} else {
			btnLabel.innerHTML = "Select All";
		}
		// : End

		// : Change state of all checkboxes on page
		for (x = 1; x <= chkboxCount; x++) {
		    document.getElementById("cbx_fleet_" + x).checked = chkboxState;
		}
		// : End
    }
    // : End
    
	function changeCheckboxState() {
		var aState;
		var btnLabel = document.truckLinkForm.btnChangeChkbox;

	    // : Change label of button
		if (btnLabel.innerHTML == "Select All") {
			aState = true;
		} else {
			aState = false;
		}
		setAllCheckboxStates(aState);
		// : End
	}
	/* 	THIS FUNCTION CODE IS REDUNDANT AND NEEDS UPDATING TO WORK WITH MULTIPLE TRUCKS
		(STILL CURRENTLY WORKS FOR SINGLE SELECTED TRUCK) BUT I HAVE REMOVED ITS CALL VIA ONCLICK
	*/
    function ajaxResetFleetCheckboxes() {
    	var t = document.truckLinkForm.truckSelect;
		var ajaxRequest;  // The variable that makes Ajax possible!
		
		try{
			// Opera 8.0+, Firefox, Safari
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
					// Something went wrong
					alert("Your browser broke!");
					return false;
		}
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function(){
			
			if(ajaxRequest.readyState == 4){ 
				
				// Setup variables for getting response
				var tempVar = ajaxRequest.responseText;
				
				var fleets = tempVar.split(",");
		    	setAllCheckboxStates(false);

			    // Assign count value to php fleets array count value
				var chkboxCount = fleets.length;

				// : Change state of all checkboxes on page
				for (x = 1; x <= chkboxCount; x++) {
				    document.getElementById("cbx_fleet_" + fleets[x]).checked = true;
				}
				// : End
						
			}
		}
		var queryString = "?truck_id=" + t.options[t.selectedIndex].value + "&data_type=keys";
		ajaxRequest.open("GET", "get_fleets_for_truck.php" + queryString, true);
		ajaxRequest.send(null); 
    }
    
	function validateAddTruckLink() {

		// Define empty array for error messages that can be gathered for each validation error found
		var errMsg = new Array(0);
		// Use PHP variable to get the number of fleets/checkbox elements on page
		var chkboxCount = <?php echo count($_fleets);?>;

		// Fetch list of selected fleets
		var selected_fleets = new Array();
		for (x = 1; x <= chkboxCount; x++) {
		    if (document.getElementById("cbx_fleet_" + x).checked) {
		    	selected_fleets[x] = document.getElementById("cbx_fleet_" + x).value;
		    }
		}
		// : End
		
		// : Validate Start Date
		if (document.getElementById('start_date').value == "") {
			errMsg.push("Start Date: Start Date field is required.");
		}
		// : End
		
		// : Validate Trucks: Has at least 1 truck been added to the truck list?		
		if (trucksList.length < 1) {
			errMsg.push("Truck: No trucks have been added to the list. At least 1 truck has to be added.");
		}
		// : End
		
		// : Validate Fleets: Has at least 1 fleet checkbox been checked?
		if (selected_fleets == undefined || selected_fleets.length == 0) {
			errMsg.push("Fleet: No fleets have been selected. At least 1 fleet has to be checked.");
		}

		// : Validate Operation: Has an operation been selected (this is almost a useless check)
		if (document.getElementById('cbxOperation').options[document.getElementById('cbxOperation').selectedIndex] == "") {
			errMsg.push("Operation: Operation field is required.");
		}
		// : End
		
		// : If any errors then display in alert element on page for duration of time and return false else return true
		if (errMsg.length > 0) {
			var errStr, arrCount;
			arrCount = errMsg.length;
			for (x = 0; x <= arrCount; x++) {
				errStr += errMsg[x];
			}
			
			// : Display error message for 30 seconds and then clear the message
			document.getElementById('divError').hidden = false;
			document.getElementById('errorMsg').innerHTML = "The following error(s) occured while trying to add a truck link:<br>" + errStr + "This message will automatically clear in: <strong id=\"tmrCount\">30</strong> seconds";
			document.getElementById('divError').focus;
			tmrCount = 30000;
			setTimeout(clearErrors, 30000);
			countInterval = setInterval(function () {updateCount(1000)}, 1000);
			// : End
			
			// Validation has failed and code may not proceed
			return false;
		} else {
			// Validation has passed and code may proceed
			return true;
		}
		// : End
	}
	
    function ajaxAddTruckLink(){
        // Disable both submit buttons on page until process is complete
    	setDisableSubmitBtn(true);
    	
    	if (validateAddTruckLink() == true) {
        
		var ajaxRequest;  // The variable that makes Ajax possible!
		var chkboxCount = <?php echo count($_fleets);?>;
		
		try{
			// Opera 8.0+, Firefox, Safari
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
					// Something went wrong
					alert("Your browser broke!");
					return false;
		}
		// Create a function that will receive data sent from the server
		/*try {
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4){
				
				// Setup variables for getting response
				var errStr;
				var tempVar = ajaxRequest.responseText;
				if (tempVar !== "true") {
					var resultErrs = tempVar.split(",");
					for (x = 0; x <= tempVar.length; x++) {
						errStr += tempVar[x] + "<br>";
					}
					
					// : Display error message for 30 seconds and then clear the message
					document.getElementById('divError').hidden = false;
					document.getElementById('errorMsg').innerHTML = "The following error(s) occured while trying to add a truck link:<br>" + errStr + "This message will automatically clear in: <strong id=\"tmrCount\">30</strong> seconds";
					document.getElementById('divError').focus;
					tmrCount = 30000;
					setTimeout(clearErrors, 30000);
					countInterval = setInterval(function () {updateCount(1000)}, 1000);
					// : End
					

				} else {
					var tbl = document.getElementById("tblOpList");
					var tblStr;
					tbl.innerHTML = "<tr><td>test</td><td><from script/td><td></td><td></td></tr>
				}
			}
		}
		} catch (e) {
			window.alert(e.message);
		}*/

		// Fetch list of selected trucks
		try {
		var selected_fleets = new Array();
		var prep_fleets_str;

		for (x = 1; x <= chkboxCount; x++) {
		    if (document.getElementById("cbx_fleet_" + x).checked) {
		    	selected_fleets[x] = document.getElementById("cbx_fleet_" + x).value;
		    }
		}
		// Convert array into string
		prep_fleets_str = selected_fleets.join();
		// : End
		
		// : Run through each trucksList array item, fetch the id for each truck and store into an array
		var selected_truck_ids = new Array();
		var tCount = trucksList.length;
		var truckSel = document.getElementById("truckId");
		var tempID;
		if (tCount > 0) {
			for (x = 0; x < tCount; x++) {
				tempID = findValueInSelectBox(trucksList[x], truckSel);
				if (tempID !== false) {
					selected_truck_ids.push(tempID);
				}
			}
		}

		// : End
		
		var start_date = document.getElementById("start_date").value;
		var stop_date = document.getElementById("stop_date").value;
		var operation = document.getElementById("cbxOperation").options[document.getElementById("cbxOperation").selectedIndex];
		
		ajaxRequest.open("POST", "addTruckLink.php", true);
		ajaxRequest.send("truckSelect=" + selected_truck_ids + "&start_date=" + start_date + "&stop_date=" + stop_date + "&opSelect=" + operation + "&fleets=" + prep_fleets_str);
		} catch (e) {
			window.alert(e.message);
		}
		setDisableSubmitBtn(false);
        }
	}

    function findValueInSelectBox(needle, haystack) {
    	if (typeof haystack == "object") {
    		var searchResult = false;
    		var selCount = haystack.length;
    		for (x = 0; x < selCount; x++) {
    			if (haystack.options[x].text == needle) {
    				searchResult = haystack.options[x].value;
    				break;
    			}
    		}
    		return searchResult;
    	} else {
    		return false;
    	}
    }
	
  	function ajaxLoadFleets(){
		var ajaxRequest;  // The variable that makes Ajax possible!
		
		try{
			// Opera 8.0+, Firefox, Safari
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
					// Something went wrong
					alert("Your browser broke!");
					return false;
		}
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4){ 
				// Setup variables for getting response
				var tempVar = ajaxRequest.responseText;
				var fleets = tempVar.split(",");
				document.myForm.selectFleet.length=0;
				var b = true;
				for (a = 0; a < fleets.length; a++) {
					if (a > 0) {b = false};
					document.myForm.selectFleet.options[a] = new Option(fleets[a], "truck"+ toString(a), b, b);
				}
			}
		}
		var queryString = "?age=" + age + "&wpm=" + wpm + "&sex=" + sex;
		ajaxRequest.open("GET", "ajax-example.php" + queryString, true);
		ajaxRequest.send(null); 
	}

	function clearErrors() {
		clearInterval(countInterval);
		document.getElementById('divError').hidden = true;
		setDisableSubmitBtn(false);
	}

	function updateCount(stepByMs) {
		tmrCount -= stepByMs;
		displayTime = tmrCount / 1000;
		document.getElementById("tmrCount").innerHTML = displayTime;
	}

	function setDisableSubmitBtn(btnState) {
		document.getElementById("btnAddTruckLink").disabled = btnState;
		document.getElementById("btnCommitTruckLinks").disabled = btnState;
	}
  </script>
	<!--  End of AJAX Code -->

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
				
				<form name="truckLinkForm" class="form-signin" role="form" action="addTruckLink.php" method="post">
					<h2 class="form-signin-heading">Add truck link</h2>
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
						          printf('<label class="checkbox-inline"> <input type="checkbox" id="cbx_fleet_%d" name="fleetSelect%d" value="%d" %s>%s</label>', $a, $a, $_id, $_checked, $_fleetname);
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
					<div class="row">
					   <table class="table table-hover">
					   <thead>
					       <tr>
					           <th>#</th>
					           <th>Truck ID:</th>
					           <th>Fleetnum:</th>
					           <th>Operation:</th>
					           <th></th>
					       </tr>
					   </thead>
					       <tbody name="tblOpList">
					           <tr>
					               <td></td>
					               <td></td>
					               <td></td>
					               <td></td>
					               <td></td>				           
					           </tr>
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
		<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
		<script src="../assets/js/ie10-viewport-bug-workaround.js"></script>

</body>
</html>
