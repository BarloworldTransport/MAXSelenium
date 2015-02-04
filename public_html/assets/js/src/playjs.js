var trucksList = new Array();

function changeDiv() {
	document.getElementById("divError").hidden = false;
}

function disableBtn() {
	document.getElementById("btn2").disabled = true;
}

function drawLbl(truckName, truckAction, truckID) {
	var lblResult = "<span id=\"lbl" + truckID + "\" class=\"label label-info\">" + truckName + "<a id=\"" + truckID + "\" onclick=\"" + truckAction + "\">X</a></span>";
	return lblResult;
}

function drawTruckPanel() {
	var truckPanel = document.getElementById("truckPanelBody");
	var tCount = trucksList.length;
	var panelStr = "<h4>";
	for (x = 0; x < tCount; x++) {
		panelStr += drawLbl(trucksList[x], "truckRemove(this)", x);
	}
	panelStr += "</h4>";
	truckPanel.innerHTML = panelStr;
}

function addTruckToPanel() {
	var truckSel = document.getElementById("truckId");
	var truckValue = truckSel.options[truckSel.selectedIndex].text;

	if (trucksList.indexOf(truckValue) == -1) {
		trucksList.push(truckValue);
		drawTruckPanel();
	}
}

function truckRemove(lblElement) {
	// : Setup variables to get the span element that was clicked
	var truckPanel = document.getElementById("truckPanelBody");
	var elementID = "lbl" + lblElement.id;
	var spanValue = document.getElementById(elementID).innerHTML;
	var truckValue = spanValue.substr(0, spanValue.indexOf("<a id="));
	var spanElement = document.getElementById(elementID);
	
	// : Remove array indice
	var arrIndex = trucksList.indexOf(truckValue);
	if (arrIndex > -1) {
		trucksList.splice(arrIndex, 1);
		drawTruckPanel();
	}
	// : End
}

function testSelectBox() {
	var fleetChkboxes = document.getElementsByName("fleetCbx");
	var countChkbox = fleetChkboxes.length;
	var fleetValues = new Array();
	console.log(countChkbox);
	if (countChkbox !== 0) {
		for (x = 0; x < countChkbox; x++) {
			if (fleetChkboxes[x].value != false) {
				fleetValues.push(fleetChkboxes[x].value);
			}
		}
	}
	if (fleetValues != false) {
		window.alert(fleetValues);
	}
	
}

function findValueInSelectBox(needle, haystack) {
	window.alert(typeof haystack);
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

function jsInit() {
	//
}

jsInit();