window.addEventListener("load", function () {

    // Add event listener to the button
    document.addEventListener("input", function (e) {
        const inputElement = e.target;

        // Validation for input type card :)
        if (inputElement.id === 'pinch_card') {
            // Remove all non-digit characters
            inputElement.value = inputElement.value.replace(/[^\d]/g, "");

            // Add whitespace after every 4 digits
            inputElement.value = inputElement.value.replace(/\d{4}(?=\d)/g, '$& ');

            // Limit the length to 16 digits
            if (inputElement.value.length > 19) {
                inputElement.value = inputElement.value.slice(0, 19);
            }
        }

        // Validation for Expiry Date.
        if (inputElement.id === 'pinch_exp') {
            // Remove all non-digit characters
            inputElement.value = inputElement.value.replace(/[^\d]/g, "");

            // Format as MM / YY
            if (inputElement.value.length > 2) {
                inputElement.value = inputElement.value.slice(0, 2) + ' / ' + inputElement.value.slice(2);
            }

            // Limit the length to 7 characters (5 digits and 2 spaces)
            if (inputElement.value.length > 7) {
                inputElement.value = inputElement.value.slice(0, 7);
            }
        }

        // Validation for Expiry Date.
        if (inputElement.id === 'pinch_cvv') {
            // Remove all non-digit characters
            inputElement.value = inputElement.value.replace(/[^\d]/g, "");

            // Limit the length to 3 characters
            if (inputElement.value.length > 3) {
                inputElement.value = inputElement.value.slice(0, 3);
            }
        }
    });

});