//: Global scope variables
var countInterval, countTmr, tableData;
var trucksList = new Array(0);
var fleetArr = new Array(0);
var truckArr = new Array(0);
var loadScrState = false;

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

		showInfo("error", "The following error(s) occured while attempting to remove a data operation entry for this process:<br>" + errStr, 15000);

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

					// Setup variables to parse and store the JSON response from the PHP script
					var errStr;

					// Try parse JSON response. If invalid JSON response then throw error and do nothing
					try {
						var tempVar = JSON.parse(ajaxRequest.responseText);

						if ('phpresult' in tempVar) {
							console.log(tempVar);
							var post_status = tempVar['phpresult'];
							if (post_status !== 'true' && 'phperrors' in tempVar) {

								var resultErrs = tempVar['phperrors'];

								for (x = 0; x <= tempVar.length; x++) {
									errStr += resultErrs[x] + "<br>";
								}

								showInfo("error", "The following error(s) occured while attempting to remove a data operation entry for this process:<br>" + errStr, 15000);

							} else {
								ajaxGetDataForProcess();
								resetFormData();
								showInfo("success", "Successfully added the new transaction to the process.", 5000);
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

function ajaxGetDataForProcess(){
	setDisableSubmitBtn(true);

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
	try {
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4 && ajaxRequest.status == 200){ 
				// Setup variables for getting response
				tableData = JSON.parse(ajaxRequest.responseText);
				console.log(tableData);

				if ('phperrors' in tableData && tableData['phpresult'] == "false") {

					var errStr = tableData['phperrors'];
					if (errStr.indexOf("Something failed. Could not obtain the process id") === -1) {
						showInfo("error", "The following error(s) occured while attempting to remove a data operation entry for this process:<br>" + errStr, 15000);
					}

				} else {
					redrawTable(tableData);		
				}
			}
		}
	} catch (e) {
		window.alert("Getting data for this session and process has failed. Error message: " + e.message);
	}
	ajaxRequest.open("GET", "get_ftl_data.php", true);
	ajaxRequest.send(null);

	setDisableSubmitBtn(false);
}

function ajaxGetNames(){

	var ajaxRequest;  // The variable that makes Ajax possible!

	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Something went wrong
		window.alert("Your browser broke!");
		return false;
	}
	// Create a function that will receive data sent from the server
	try {
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4 && ajaxRequest.status == 200){

				// Setup variables for getting response
				var data = JSON.parse(ajaxRequest.responseText);
				console.log("Got JSON response from PHP script. Dumping data object below:");
				console.log(data);

				if ('phperrors' in data) {

					var errStr = data['phperrors'];
					console.log(errStr);
					return false;

				} else if ('fleets' in data && 'trucks' in data){

					objKeys = Object.keys(data['fleets']);
					objCount = objKeys.length;
					if (objCount !== 0) {
						for (x = 0; x < objCount; x++) {
							fleetArr[objKeys[x]] = data['fleets'][objKeys[x]];
						}
					}

					objKeys = Object.keys(data['trucks']);
					objCount = objKeys.length;
					if (objCount !== 0) {
						for (x = 0; x < objCount; x++) {
							truckArr[objKeys[x]] = data['trucks'][objKeys[x]];
						}
					}

					ajaxGetDataForProcess();
				}
			}
		}
	} catch (e) {
		window.alert("Getting fleet and/or truck name values failed. Error message: " + e.message);
	}
	console.log("Sending get request to PHP script.");
	ajaxRequest.open("GET", "get_fleet_truck_name_values.php", true);
	ajaxRequest.send(null);
}

function redrawTable(objData) {

	var tableBody = document.getElementById("tblOpList");
	if (typeof objData == "object" && objData !== null) {
		// Add code to redraw HTML table
		var tableHtml = "";
		var objKeys = Object.keys(objData);
		var objCount = objKeys.length;
		var aDate = "";
		var tempStr = "";
		var tempArr = new Array(0);
		var tempDataStr = "";
		var tempName = "";

		tableHtml += "<table class=\"table table-hover\"><thead><tr><th>ID #:</th><th>Truck IDs:</th><th>Fleets:</th><th>Operation:</th><th>Start Date:</th><th>Stop Date:</th><th>Delete:</th></tr></thead>"

			if (objCount !== 0) {
				for (x = 0; x < objCount; x++) {
					tableHtml += "<tr>";
					var subData = objData[objKeys[x]];
					var subKeys = Object.keys(subData);
					var subCount = subKeys.length;
					if (subCount !== 0) {
						for (y = 0; y < subCount; y++) {
							if (subKeys[y] == "start_date" || (subKeys[y] == "end_date" && subData[subKeys[y]])) {
								aDate = timeConverter(subData[subKeys[y]]);
								tableHtml += "<td>" + aDate + "</td>";
							} else if (subKeys[y] == "truck_id" || subKeys[y] == "fleets") {
								tempDataStr = "";

								// : Search for , in string and then split into array and process each item
								tempStr = subData[subKeys[y]];
								// Check for , in string
								if (tempStr.indexOf(",") != -1) {
									tempArr = tempStr.split(",");

									if (tempArr.length > 0) {
										for (z = 0; z < tempArr.length; z++) {

											if (subKeys[y] == "truck_id") {
												tempName = truckArr[tempArr[z]];
											} else if (subKeys[y] == "fleets") {
												tempName = fleetArr[tempArr[z]];
											}

											tempDataStr += tempName;
											if (z != (tempArr.length - 1)) {
												tempDataStr += ",";
											}
										}
									}
								} else {

									if (subKeys[y] == "truck_id") {
										tempName = truckArr[tempStr];
									} else if (subKeys[y] == "fleets") {
										tempName = fleetArr[tempStr];
									}
									tempDataStr += tempName;
								}
								if (tempDataStr) {
									tableHtml += "<td>" + tempDataStr + "</td>";
								}
							} else {
								tableHtml += "<td>" + subData[subKeys[y]] + "</td>";
							}
						}
					}
					tableHtml += "<td><a id=" + subData["id"] + " onclick=\"removeDataEntryForProcess(this)\">Remove</a></td>"
					tableHtml += "</tr>";
				}
				if (tableHtml) {
					tableHtml += "</tbody></table>";

					tableBody.innerHTML = tableHtml;
				}
			} else {
				tableHtml = "<table class=\"table table-hover\"><thead><tr><th>ID #:</th><th>Truck IDs:</th><th>Fleets:</th><th>Operation:</th><th>Start Date:</th><th>Stop Date:</th><th>Delete:</th></tr></thead></tbody></table>"
					tableBody.innerHTML = tableHtml;
			}
	} else {
		tableHtml = "<table class=\"table table-hover\"><thead><tr><th>ID #:</th><th>Truck IDs:</th><th>Fleets:</th><th>Operation:</th><th>Start Date:</th><th>Stop Date:</th><th>Delete:</th></tr></thead></tbody></table>"
			tableBody.innerHTML = tableHtml;
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

function removeDataEntryForProcess(tdElement) {

	// Disable all submit buttons on page
	setDisableSubmitBtn(true);

	var ajaxRequest;  // The variable that makes Ajax possible!

	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Something went wrong
		window.alert("Your browser broke!");
		return false;
	}

	try {
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4 && ajaxRequest.status == 200){

				// Setup variables to parse and store the JSON response from the PHP script
				var errStr;

				// Try parse JSON response. If invalid JSON response then throw error and do nothing
				try {
					var tempVar = JSON.parse(ajaxRequest.responseText);

					if ('phpresult' in tempVar) {

						console.log(tempVar);
						var post_status = tempVar['phpresult'];

						if (post_status !== 'true' && 'phperrors' in tempVar) {

							var resultErrs = tempVar['phperrors'];

							for (x = 0; x <= tempVar.length; x++) {
								errStr += resultErrs[x] + "<br>";
							}

							showInfo("error", "The following error(s) occured while attempting to remove a data operation entry for this process:<br>" + errStr, 15000);

						} else {
							// PHP Post succeeded and completed operation successfully
							ajaxGetDataForProcess();
							resetFormData();

							showInfo("success", "Successfully removed the transaction from the process.", 5000);
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

	try {

		var ftl_id = tdElement.id;
		var post_data = "ftl_id=" + ftl_id;
		if (post_data != false) {
			ajaxRequest.open("POST", "remove_process_data.php", true);
			ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			ajaxRequest.send(post_data);
		}

	} catch (e) {
		console.log("Failed posting to remove_process_data.php");
		window.alert(e.message);
	}
	setDisableSubmitBtn(false);

}

function commitTransactions() {

	// Disable all submit buttons on page
	setDisableSubmitBtn(true);
	// Show loading screen
	showLoadingScr(true);

	var ajaxRequest;  // The variable that makes Ajax possible!

	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Something went wrong
		window.alert("Your browser broke!");
		return false;
	}

	try {
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4 && ajaxRequest.status == 200){

				// Setup variables to parse and store the JSON response from the PHP script
				var errStr;

				// Try parse JSON response. If invalid JSON response then throw error and do nothing
				try {
					var tempVar = JSON.parse(ajaxRequest.responseText);

					if ('phpresult' in tempVar) {

						console.log(tempVar);
						var post_status = tempVar['phpresult'];

						if (post_status !== 'true' && 'phperrors' in tempVar) {

							var resultErrs = tempVar['phperrors'];

							for (x = 0; x <= tempVar.length; x++) {
								errStr += resultErrs[x] + "<br>";
							}

							showInfo("error", "The following error(s) occured while attempting to remove a data operation entry for this process:<br>" + errStr, 15000);

						} else {
							// PHP Post succeeded and completed operation successfully
							redrawTable(null);
							ajaxGetDataForProcess();
							resetFormData();

							setDisableSubmitBtn(false);
							// Remove loading screen
							showLoadingScr(false);

							showInfo("success", "Successfully processed your transactions.", 5000);
						}
					}
				} catch (e) {
					setDisableSubmitBtn(false);
					showLoadingScr(false);
					window.alert(e.message);
				}
			}
		}
	} catch (e) {
		setDisableSubmitBtn(false);
		showLoadingScr(false);
		window.alert(e.message);
	}

	try {
		ajaxRequest.open("POST", "commit_process_data.php", true);
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(null);

	} catch (e) {

		console.log("Failed posting to commit_process_data.php");
		window.alert(e.message);

		setDisableSubmitBtn(false);
		showLoadingScr(false);
	}

}

function showInfo(infoType, infoMsg, duration) {

	var infoSec = duration / 1000;

	if (infoType === "error") {

		// : Display error message for duration milliseconds and then clear the message
		document.getElementById('panelMsg').className = "alert alert-danger";
		document.getElementById('divPanel').hidden = false;
		document.getElementById('panelMsg').innerHTML = infoMsg.toString() +  "\n This message will automatically clear in: <strong id=\"tmrCount\">" + infoSec.toString() + "</strong> seconds";
		window.location.hash = 'frmHeading';
		tmrCount = duration;
		setTimeout(clearErrors, duration);
		countInterval = setInterval(function () {updateCount(1000)}, 1000);
		// : End

	} else if (infoType === "success") {

		// : Display success message for duration milliseconds and then clear the message
		document.getElementById('panelMsg').className = "alert alert-success";
		document.getElementById('divPanel').hidden = false;
		document.getElementById('panelMsg').innerHTML = infoMsg.toString() +  "\n This message will automatically clear in: <strong id=\"tmrCount\">" + infoSec.toString() + "</strong> seconds";
		window.location.hash = 'frmHeading';
		tmrCount = duration;
		setTimeout(clearErrors, duration);
		countInterval = setInterval(function () {updateCount(1000)}, 1000);
		// : End

	}
} 

function resetFormData(){
	// : Reset form for new addition process
	trucksList.length = 0;
	drawTruckPanel();
	clearErrors();
}

function clearErrors() {
	clearInterval(countInterval);
	document.getElementById('divPanel').hidden = true;
	setDisableSubmitBtn(false);
	if (loadScrState === true) {
		showLoadingScr(false);
	}
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



function timeConverter(UNIX_timestamp){
	var a = new Date(UNIX_timestamp*1000);
	var year = a.getFullYear();
	var month = a.getMonth();
	var vmonth = month + 1;
	if (vmonth < 10) {
		vmonth  = "0" + vmonth.toString();
	}
	var day = a.getDate();
	if (day < 9) {
		day = "0" + day.toString();
	}
	var hour = a.getHours();
	var min = a.getMinutes();
	if (min < 9) {
		min = "0" + min.toString();
	}
	var sec = a.getSeconds();
	if (sec < 9) {
		sec = "0" + sec.toString();
	}
	var time = year + '-' + vmonth + '-' + day + ' ' + hour + ':' + min + ':' + sec ;
	return time;
}

function showLoadingScr(setState) {
	var ldiv = document.getElementById('LoadingDiv');
	loadScrState = setState;
	if (setState === true) {
		ldiv.style.display='block';
	} else if (setState === false) {
		ldiv.style.display='none';
	}
}

ajaxGetNames();