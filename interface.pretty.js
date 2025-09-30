

// --------------------------------------------------- FORMS SECTION -----------------------------------------------------------------------

var htmlform = document.getElementById("inputs");
var displayform = document.getElementById("results");


// Drop Down menu
var methods = document.getElementById("methods");

var option1 = document.createElement("option");
option1.value = "GET";
option1.textContent = "GET";
methods.appendChild(option1);

var option2 = document.createElement("option");
option2.value = "POST";
option2.textContent = "POST";
methods.appendChild(option2);

var option3 = document.createElement("option");
option3.value = "PATCH";
option3.textContent = "PATCH";
methods.appendChild(option3);

var option4 = document.createElement("option");
option4.value = "DELETE";
option4.textContent = "DELETE";
methods.appendChild(option4);

// ---------------------------------------------------- AJAX SECTION -----------------------------------------------------------------------



// add an event listener for the forms

// console.log(htmlform);
htmlform.addEventListener("submit", handleSubmit);
displayform.addEventListener("submit", handleClear);

function handleClear(e) {
e.preventDefault();
let status = document.getElementById("status");
let responseBody = document.getElementById("responseBody");

status.value = "";
responseBody.value = "";
}

function handleSubmit(e) {
// Prevent the form from actually submitting (page reload)
e.preventDefault();
// var xhr = new XMLHttpRequest();

// Get the value of the resource 
let httpmethod = document.getElementById("methods").value;
let url = document.getElementById("resource").value;
let jsonBody = document.getElementById("jsonBody").value;

// Output the value of the resource textarea to the console
console.log(httpmethod);
console.log(url);
console.log(jsonBody);


let request = new XMLHttpRequest();
request.open(httpmethod, url, true);
// request.setRequestHeader('Content-type', 'application/json');

// send the data to the server
if (httpmethod === 'POST' || httpmethod === 'PATCH') {
    // collect the data from the forms
    let formData = new FormData(htmlform); // Collect all the form dat
    // Append JSON body to form dataa
    formData.append('jsonBody', jsonBody); 

    // Set the request header to JSON
    request.setRequestHeader('Content-Type', 'application/json');
    
    // Send JSON data to the server
    request.send(JSON.stringify({
        methods: formData.get('methods'), // Collect the selected HTTP method
        resource: formData.get('resource'), // Collect the resource URL
        jsonBody: formData.get('jsonBody') // Collect the JSON body
    }));
} else {
    // POST and DELETE does not need JSON body as information is in the resource URI
    request.send();
}

request.onload = function() {
    if (this.status == 200) {
        // this logs the response and returns the values as an array
        console.log('Response:', this.responseText);
        processJSONResponse(this.responseText, this.status);
    } else {
        console.error("Error: Invalid response");
        processJSONResponse(this.responseText, this.status);
    }
}


}


function processJSONResponse(responseText, statusCode) {

// Initialize the display section
let status = document.getElementById("status");
let responseBody = document.getElementById("responseBody");

// Update status and response body fields
status.value = `HTTP Status Code: ${statusCode}`;

// Try to parse the response if it's JSON
try {
    var jsonResponse = JSON.parse(responseText);
    responseBody.value = JSON.stringify(jsonResponse, null, 2); // Pretty-print JSON
} catch (e) {
    responseBody.value = responseText; // Just show raw response if not JSON
}
}
