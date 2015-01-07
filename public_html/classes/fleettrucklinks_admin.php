<?php
error_reporting(E_ALL);
ini_set("display_errors", "1");
include "PullDataFromMySQLQuery.php";

if (empty($_POST)) {
    $_fleettrucklink_data = (array) array();
} else {
    if (array_key_exists('addTruckLink', $_POST && $_SERVER['REQUEST_METHOD'] === 'POST')) {}
}

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
    	$_fleets_by_truck = get_fleets_for_truck($_itrucks[0], $_queries[2]);
    }
    var_dump($_fleets_by_truck);
    // : End 
    
} catch (Exception $e) {
    die("Was not able to successfully connect to the database.");
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
	<script language="javascript" type="text/javascript">

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
				window.alert('ajax response');
		    	setAllCheckboxStates(false);

			    // Assign count value to php fleets array count value
				var chkboxCount = fleets.length;
				
				// : Change state of all checkboxes on page
				window.alert(fleets);
				for (x = 1; x <= chkboxCount; x++) {
				    document.getElementById("cbx_fleet_" + fleets[x]).checked = true;
				}
				// : End
						
			}
		}
		var queryString = "?truck_id=" + t.options[t.selectedIndex].value;
		ajaxRequest.open("GET", "get_fleets_for_truck.php" + queryString, true);
		ajaxRequest.send(null); 
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
					<li class="active"><a href="#">My Dashboard</a></li>
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
							<li><a href="#">Logout</a></li>
						</ul></li>
			
			</div>
			<!--/.nav-collapse -->
	
	</nav>

	<div class="container-fluid">
	
		<div class="row">
		
			<div class="col-sm-3 col-md-2 sidebar">
				<ul class="nav nav-sidebar">
					<h6>My Tasks</h6>
					<li class="active"><a href="#">My Dashboard <span class="sr-only">(current)</span></a></li>
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
				
				<form name="truckLinkForm" class="form-signin" role="form">
					<h2 class="form-signin-heading">Add truck link</h2>
					<div class="row">
						<div class="col-md-2">
							<label for="truck_id">Select a truck:</label>
						</div>
						<div class="col-md-10">
							<select id="truck_id" name="truckSelect" class="form-control" onchange="ajaxResetFleetCheckboxes()" required autofocus>
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
						          printf('<label class="checkbox-inline"> <input type="checkbox" id="cbx_fleet_%d" value="%d" %s>%s</label>', $a, $_id, $_checked, $_fleetname);
						          $a++;
						        }
						   }
						}
						?>
						<!-- End -->
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
						</div>
						<div class="col-md-10">
						<button class="btn btn-default" name="btnChangeChkbox" type="button" onclick="changeCheckboxState()">Select All</button>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<button class="btn btn-lg btn-primary btn-block" name="addTruckLink" type="submit">Add</button>
						</div>

						<div class="col-md-6">
							<button class="btn btn-lg btn-primary btn-block" name="commitTruckLinks" type="submit">Commit</button>
						</div>
					</div>
				</form>
				
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
