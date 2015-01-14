<?php

// : Error reporting for debugging purposes
error_reporting(E_ALL);
ini_set("display_errors", "1");
// : End

/*include "PullDataFromMySQLQuery.php";

$_dbh = new PullDataFromMySQLQuery('bwt_max_auto', 'localhost', 'user', 'pwd');

$_keys = array("detail", "process_type_id", "state", "process_start", "session_id", "user_id");
$_values = array("myDetail", 1, 1, 20140112, "aSessionID", 1);
$_query = "INSERT INTO `process` (detail, process_type_id, state, process_start, session_id, user_id) VALUES (:detail, :process_type_id, :state, :process_start, :session_id, :user_id);";

if ($_dbh->insertSQLQuery($_keys, $_values, $_query)) {
	$_dbh = null;
	die("success");
} else {
	$_dbh = null;
	die("failed.");
}*/
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
    
<script src="playjs.js"></script>
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
			
			</div>
			<!--/.nav-collapse -->
	
	</nav>

	<div class="container-fluid">
	
		<div class="row">
		
			<div class="col-sm-3 col-md-2 sidebar">
				<ul class="nav nav-sidebar">
					<h6>My Tasks</h6>
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
					<div class="row" id="divError" hidden>
						<div class="col-md-12">
							<div class="alert alert-danger" role="alert" id="error_msg"></div>
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
							<select id="truckId" name="truckSelect" class="form-control" required>
								<option value="truck1">truck1</option>
								<option value="truck2">truck2</option>
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
					<div class="row">
						<div class="col-md-2">
							<label for="fleet_id">Select Fleet(s)</label>
						</div>
						<!-- Dynamically list fleets -->
						<div class="col-md-10">
						</div>
					</div>
					
					<!-- Checkbox operation buttons -->
					<div class="row">
						<div class="col-md-2">
						</div>
						<div class="col-md-2">
						  <button class="btn btn-default" name="btnChangeChkbox" type="button" onclick="disableBtn()">Select All</button>
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
							<button class="btn btn-lg btn-primary btn-block" id="btn1" name="addTruckLink" onclick="disableBtn" type="button">Add</button>
						</div>

						<div class="col-md-6">
							<button class="btn btn-lg btn-primary btn-block" id="btn2" name="commitTruckLinks" type="submit">Commit</button>
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