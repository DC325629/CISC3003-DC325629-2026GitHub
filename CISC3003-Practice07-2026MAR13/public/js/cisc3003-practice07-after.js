
/* add code here  */

// Wait for the DOM to be fully loaded before attaching event listeners
window.addEventListener('DOMContentLoaded', function() {

    
    // Select all elements that have the 'hilightable' class
    var hilightableElements = document.querySelectorAll('.hilightable');
    
    // Loop through each element and attach focus and blur event handlers
    for (var i = 0; i < hilightableElements.length; i++) {
        var element = hilightableElements[i];
        
        // When the element gains focus, add the 'highlight' class
        element.addEventListener('focus', function() {
            this.classList.add('highlight');
        });
        
        // When the element loses focus, remove the 'highlight' class
        element.addEventListener('blur', function() {
            this.classList.remove('highlight');
        });
    }


    
    // Get the form element by its ID
    var form = document.getElementById('mainForm');
    
    // Attach a submit event handler to the form
    form.addEventListener('submit', function(event) {
        
        // Select all elements that have the 'required' class
        var requiredElements = document.querySelectorAll('.required');
        var hasError = false;  // Flag to track if any required field is empty
        
        // Loop through each required element
        for (var i = 0; i < requiredElements.length; i++) {
            var reqElem = requiredElements[i];
            
            // Check if the field's value is empty or only whitespace
            if (reqElem.value.trim() === '') {
                // Add the 'error' class to highlight the empty field
                reqElem.classList.add('error');
                hasError = true;  // Set flag to true
            } else {
                // If the field is not empty, ensure any previous error class is removed
                reqElem.classList.remove('error');
            }
        }
        
        // If any required field was empty, prevent the form from submitting
        if (hasError) {
            event.preventDefault();
        }
        // If all required fields are filled, the form will submit normally
    });

    
    // Select all required elements again (or reuse the collection)
    var requiredElements = document.querySelectorAll('.required');
    
    for (var i = 0; i < requiredElements.length; i++) {
        var reqElem = requiredElements[i];
        
        // Attach an 'input' event handler to remove the error class as soon as the user types
        reqElem.addEventListener('input', function() {
            this.classList.remove('error');
        });
    }

});