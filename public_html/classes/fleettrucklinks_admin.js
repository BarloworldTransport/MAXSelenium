
//: Global scope variables
var countInterval, countTmr;
var trucksList = new Array(0);

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

//: Set all checkbox input elements to true|false
function setAllCheckboxStates(chkboxState) {
	var btnLabel = document.truckLinkForm.btnChangeChkbox;
	// Assign count value to php fleets array count value
	var fleetChkboxes = document.getElementsByName("cbxFleet");
	var chkboxCount = fleetChkboxes.length;

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
//: End

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

	// : Get number of fleet checkbox elements on the page
	var fleetChkboxes = document.getElementsByName("cbxFleet");
	var chkboxCount = fleetChkboxes.length;
	// : End

	// Fetch list of selected fleets
	var selected_fleets = new Array(0);
	
	for (x = 1; x <= chkboxCount; x++) {
		var cbxElement = document.getElementById("cbx_fleet_" + x); 
		if (cbxElement.checked) {
			// x - 1 when referencing index where count starts at 1 when adding to arrays
			selected_fleets[selected_fleets.length] = cbxElement.value;
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
		window.location.hash = 'frmHeading';
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

		// : Get number of fleet checkbox elements on the page
		var fleetChkboxes = document.getElementsByName("cbxFleet");
		var chkboxCount = fleetChkboxes.length;
		// : End

		try{
			// Opera 8.0+, Firefox, Safari
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
			// Something went wrong
			alert("Your browser broke!");
			return false;
		}
		// Create a function that will receive data sent from the server
		try {
			ajaxRequest.onreadystatechange = function(){
				if(ajaxRequest.readyState == 4 && ajaxRequest.status == 200){
					console.log("I have detected a return from post");
					console.log(ajaxRequest.responseText);

					// Setup variables to parse and store the JSON response from the PHP script
					var errStr;
					
					// Try parse JSON response. If invalid JSON response then throw error and do nothing
					try {
						var tempVar = JSON.parse(ajaxRequest.responseText);

						console.log(tempVar);
						if ('phpresult' in tempVar) {
							var post_status = tempVar['phpresult'];
							if (post_status !== 'true' && 'phperrors' in tempVar) {

								var resultErrs = tempVar['phperrors'];
								console.log(resultErrs);
								for (x = 0; x <= tempVar.length; x++) {
									errStr += resultErrs[x] + "<br>";
								}


								// : Display error message for 30 seconds and then clear the message
								document.getElementById('divError').hidden = false;
								document.getElementById('errorMsg').innerHTML = "The following error(s) occured while trying to add a truck link:<br>" + errStr + "This message will automatically clear in: <strong id=\"tmrCount\">30</strong> seconds";
								window.location.hash = 'frmHeading';
								tmrCount = 30000;
								setTimeout(clearErrors, 30000);
								countInterval = setInterval(function () {updateCount(1000)}, 1000);
								// : End


							} else {
								/*var tbl = document.getElementById("tblOpList");
					var tblStr;
					tbl.innerHTML = "<tr><td>test</td><td><from script/td><td></td><td></td></tr>*/
								console.log("Some code to draw the table");
							}
						}
					} catch (e) {
						window.alert(e.message);
					}
				}
			}
		} catch (e) {
			window.alert(e.message);
		}

		// Fetch list of selected trucks
		try {
			var selected_fleets = new Array(0);
			var fleets_str;

			for (x = 1; x <= chkboxCount; x++) {
				var cbxElement = document.getElementById("cbx_fleet_" + x); 
				if (cbxElement.checked) {
					// x - 1 when referencing index where count starts at 1 when adding to arrays
					selected_fleets[selected_fleets.length] = cbxElement.value;
				}
			}
			// Convert array into string
			fleets_str = selected_fleets.join();
			// : End

			// : Run through each trucksList array item, fetch the id for each truck and store into an array
			var selected_truck_ids = new Array(0);
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
			var operation = document.getElementById("cbxOperation").options[document.getElementById("cbxOperation").selectedIndex].value;
			var post_data = "truckSelect=" + selected_truck_ids + "&start_date=" + start_date + "&stop_date=" + stop_date + "&opSelect=" + operation + "&fleets=" + fleets_str;
			if (post_data != false) {
				console.log(post_data);
				ajaxRequest.open("POST", "addTruckLink.php", true);
				ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				ajaxRequest.send(post_data);
			}

		} catch (e) {
			console.log("Failed posting to addtrucklink.php");
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