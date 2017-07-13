/********************************************************
 *	Javascript for dlb's Send-A-Link (dsl)
 *
 *	Handles AJAX communication of user input and 
 *	server responses to eliminate need for re-sending entire page.
 *
 *	This file is used by dsl.php, and is expected to be found 
 *	in the same folder as dsl.php.
 *
 *	This is written with plenty of white-space and comments for clarity,
 *	but that means it takes longer than necessary to download. 
 *	Visit http://jscompress.com/ to make a condensed version for production use. 
 */
/********************************************************
 * dslSend()
 *
 * Sends input to server, and sets up a process 
 * to receive the response.
 *
 * This is called by an onsubmit attribute on the form element
 */
function dslSend(form){
	// Create AJAX object 
	// See explanation of AJAX setup at http://www.tizag.com/ajaxTutorial/ajaxform.php
	var ajax, params;
	try{
		ajax = new XMLHttpRequest(); // Opera 8.0+, Firefox, Safari
	} catch (e){
		try{
			ajax = new ActiveXObject("Msxml2.XMLHTTP"); // Internet Explorer Browsers
		} catch (e) {
			try{
				ajax = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e){
				alert("Your browser does not support AJAX!"); // Something went wrong
				return false;
			}
		}
	}
	// Create a function to receive the response from server
	// It will listen continuously from the time the request is sent to the server
	// until the time it receives a response indicating "complete"
	ajax.onreadystatechange = function(){
		if(ajax.readyState == 4){ // indicates that response has been completely received from the server
			// pass control to the function that does the work
			dslProcessResponse(form, ajax.response);
		}
	}
	// Collect inputs from the form input for sending to server as a POST request
	// Note that the global variable ajaxData was provided by the server via a wp_localize_script call
	params = 'action=' + ajaxData.action + '&' + dslGetFormData(form);
	// Establish type of request (i.e., using POST, not GET) and the URL
	ajax.open("POST", ajaxData.url, true); 
	// Establish headers to tell data type
	ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
	// Send request to the server
	ajax.send(params); 
	// show the "loading..." image
	document.getElementById('dsl-loader').style.display = "block";
	// Erase any prior error messages from the form
	dslClearErrors(form);
	// the program ends, leaving active only the onreadystatechange "listener"
	// that was created above
}
/********************************************************
 * dslGetFormData()
 *
 * Returns form data in format for sending to server,
 * which is "key"="value" pairs separated by "&"
 */
function dslGetFormData(form){
	var i, params='';
	// loop through all elements of the form
	// this simple routine works for text inputs
	// but would have to be extended to handle checkboxes, radios, etc.
	for(i=0; i<form.elements.length; i++){ 
		if(form.elements[i].name && form.elements[i].value) {
			// this element has both a name and a value, so add it to the list
			params += form.elements[i].name + '=' + form.elements[i].value + '&';
		}
	}
	return params;
}
/********************************************************
 * dslProcessResponse()
 *
 * Handles the response sent from the server,
 * either showing error messages, or showing confirmation message
 */
function dslProcessResponse(form, response){
	var cDiv;
	// hide the "loading" image
	document.getElementById('dsl-loader').style.display = "none";
	// convert the response from a JSON string to javascript objects
	response = JSON.parse(response);
	// Either show errors or show confirmation
	if (typeof response.reset != 'undefined') {
		// server says to reset the page
		// do so showing the form-level error message
		dslDoReset(response.errors['dsl_pid']);
	} else if (typeof response.errors != 'undefined') {
		// got errors
		// show the messages
		dslShowErrors(form, response.errors);
		// check if a 2nd nonce was received that says CAPTCHA was previously satisfied,
		// and if so, store that nonce it in a form element, and hide the captcha div
		if (typeof response.secure != 'undefined') {
			form.elements["dsl_secure"].value = response.secure;
			document.getElementById('dsl-captcha').style.display = 'none';
		}
		// scroll top of form into view
		document.getElementById('dsl-form-div').scrollIntoView();
		// put up an alert box to tell user what to do
		alert(ajaxData.errorAlert);
	} else if (typeof response.confirms != 'undefined') {
		// got confirms
		// hide the form div
		document.getElementById('dsl-form-div').style.display = 'none';
		// Select the confirm DIV
		cDiv = document.getElementById('dsl-confirm-div')
		// then select all of the DIVs within it via .getElementsByTagName,
		// then index to the first of them via [0]
		// then access property innerHTML, and
		// load the formatted response from server into it
		cDiv.getElementsByTagName('DIV')[0].innerHTML = response.confirms.body;
		// show the confirm div
		cDiv.style.display = 'block';
		// scroll top of page into view
		document.getElementsByTagName('body')[0].scrollIntoView();
	} else {
		// unexplained error, so reset using error message from the global sent by the server
		dslDoReset(ajaxData.errorGeneral);
	}
}
/********************************************************
 * dslDoReset()
 *
 * Displays an alert for a processing error and reloads the page
 */
function dslDoReset(alertMsg) {
	// display message for a processing error
	alert (alertMsg);
	// force a page reload from the server
	document.location.reload(true);
}
/********************************************************
 * dslShowErrors()
 *
 * Shows each error message in the label element of the associated input element.
 * Expects that the errors object contains "key":value pairs, 
 * where key is the name given to each input element.
 */
function dslShowErrors(form, errors){
	var field, spans;
	// assocate labels to elements
	dslAssociateLabels(form);
	// loop through all of the error messages
	for (field in errors){
		// use the "key" for this error to select the form element
		// then access its label attribute,
		// then get all the spans within it
		// then find one with the right class 
		// and put the error message into its innerHTML
		spans = form.elements[field].label.getElementsByTagName('span');
		for (i=0; i<spans.length; i++) {
			if(spans[i].className == 'dsl-error-field') {
				spans[i].innerHTML = '<br />' + errors[field];
				break;
			}
		}
	}
}
/********************************************************
 * dslAssociateLabels()
 *
 * Associates labels with inputs so that they can be updated with error messages
 * Expects that each label will have a "for" attribute equal to the name of the associated input
 */
function dslAssociateLabels(form){
	var labels,	i, elem;
	// select all the labels on the form
	labels = form.getElementsByTagName('LABEL');
	// loop through them
	for (i = 0; i < labels.length; i++) {
		if (labels[i].htmlFor != '') {
			// this label has an htmlFor attribute,
			// so attempt to select the matching form element
			elem = form.elements[labels[i].htmlFor];
			// if selection found something, set an attribute on it equal to the label
			if (elem) elem.label = labels[i];			
		}
	}
}
/********************************************************
 * dslClearErrors()
 *
 * Finds all of the error messages by looking for SPAN elements within the form 
 * with the right class attribute and removes them from the DOM
 */
function dslClearErrors(form){
	var errors,	i;
	// select all the span elements on the form
	errors = form.getElementsByTagName('SPAN');
	// Loop thru them
	for (i=0; i<errors.length; i++){
		// check each span to see if it is an error message
		if(errors[i].className == 'dsl-error-field') {
			// this is one so clear its value
			errors[i].innerHTML = '';
		}
	}
}
