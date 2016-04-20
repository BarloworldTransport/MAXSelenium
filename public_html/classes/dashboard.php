<?php
// comment
session_start();
if (isset($_SESSION['user_email']) && isset($_SESSION['user_pwd'])) {
    if (isset($_GET["content"])) {
        switch ($_GET["content"]) {
            case "fleettrucklink":
                {
                    header("location: fleettrucklinks_admin.php");
                    break;
                }
            case "logout":
                {
                    header("Location: ../logout.php");
                    break;
                }
            default:
                {
                    break;
                }
        }
    }
} else {
    session_destroy();
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

<title>Barloworld | MAX Automation System</title>

<!-- Bootstrap core CSS -->
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom styles for this template -->
<link href="../assets/css/dashboard.css" rel="stylesheet">

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

	<!-- Fixed navbar -->
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<div id="navbar" class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li class="active"><a href="#">My Dashboard</a></li>
					<li class="dropdown"><a href="#" class="dropdown-toggle"
						data-toggle="dropdown" role="button" aria-expanded="false">Automation
							<span class="caret"></span>
					</a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="#BOB">Reporting</a></li>
							<li><a href="?content=fleettrucklink">Fleet Truck Links</a></li>
							<li><a href="#">Batch Rate Processing</a></li>
						</ul></li>
					<li><a href="#">Settings</a></li>
					<li class="dropdown"><a href="#" class="dropdown-toggle"
						data-toggle="dropdown" role="button" aria-expanded="false">My
							Account <span class="caret"></span>
					</a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="?content=profile">My Profile</a></li>
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
					<li class="active"><a href="">My Dashboard <span class="sr-only">(current)</span></a></li>
					<li><a href="#">Reporting</a></li>
				</ul>
			</div>

			<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">

				<div class="jumbotron">
					<h2>Barloworld Transport</h2>
					<h3>MAX Automation System</h3>
					<p>Welcome user to your Dashboard of the MAX automation system.
						Feel free to explore the tools available to you here.</p>
				</div>

				<h1 class="page-header">Dashboard</h1>

			</div>
		</div>
	</div>

	<!-- Bootstrap core JavaScript
    ================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script
		src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="../assets/js/bootstrap.min.js"></script>
	<script src="../assets/js/docs.min.js"></script>
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<script src="../assets/js/ie10-viewport-bug-workaround.js"></script>

</body>
</html>
