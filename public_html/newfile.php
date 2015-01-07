<html>
<body>

<script language="javascript" type="text/javascript">
<!-- 
//Browser Support Code
function ajaxFunction(){
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
	var age = document.getElementById('age').value;
	var wpm = document.getElementById('wpm').value;
	var sex = document.getElementById('sex').value;
	var queryString = "?age=" + age + "&wpm=" + wpm + "&sex=" + sex;
	ajaxRequest.open("GET", "ajax-example.php" + queryString, true);
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
	var age = document.getElementById('age').value;
	var wpm = document.getElementById('wpm').value;
	var sex = document.getElementById('sex').value;
	var queryString = "?age=" + age + "&wpm=" + wpm + "&sex=" + sex;
	ajaxRequest.open("GET", "ajax-example.php" + queryString, true);
	ajaxRequest.send(null); 
}

//-->
</script>



<form name='myForm'>
Max Age: <input type='text' id='age' /> <br />
Max WPM: <input type='text' id='wpm' />
<br />
Sex: <select id='sex'>
<option value='m'>m</option>
<option value='f'>f</option>
</select>
<br />
Fleets: <select name='selectFleet' onchange='ajaxLoadFleets()'>
<option value='truck1'>truck1</option>
<option value='truck2'>truck2</option>
</select>
<input type='button' onclick='ajaxFunction()' value='Query MySQL' />
</form>
<div id='ajaxDiv'>Your result will display here</div>
</body>
</html>