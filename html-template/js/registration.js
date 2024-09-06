(function ($) {

	"use strict";

// registration button clicked
document.getElementById('registrationForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent the default form submission
    const formData = new FormData(this);
    grecaptcha.ready(function () {
        grecaptcha.execute('6Len0DIqAAAAAJUiTV-ox-TLQ0pHRxqWTVr7WHaw', { action: 'submit' }).then(function (token) {
            formData.set('recaptchaToken', token);
            // Add your logic to submit to your backend server here.
            
            const datePicker = $('#registrationDate')[0];
            const dateId = datePicker[datePicker.value].dataset.dateid;
            formData.set('zajezdId', dateId);

            const numberOfInterests = document.getElementById('reg1').getAttribute('data-interests');
            var interestsString = '';
            for (let index = 1; index <= numberOfInterests; index++) {
                var interest = formData.get('reg' + index);
                if (interest) {
                    if (interestsString) {
                        interestsString = interestsString + ", " + interest;
                    } else {
                        interestsString = interest;
                    }
                }

            }
            if (interestsString) {
                var dotazString = 'Mám předběžný zájem o:' + interestsString + ', a chci zaslat konkrétní nabídky, až budou k dispozici.' + "\n" + formData.get('message');
                formData.set('message', dotazString);
            }
            const button = document.getElementById('registrationTour');
            const spinner = document.querySelector('.spinner');

            button.disabled = true; // Disable the button to prevent multiple submissions
            spinner.classList.remove('hidden'); // Show the spinner
            button.firstElementChild.classList.add('hidden');
            // Send the form data using fetch
            fetch('/dotaz.php', {
                method: 'POST',
                body: formData // Send the FormData object directly
            })
                .then(response => response.text()) // or response.json() if expecting JSON response
                .then(data => {
                    if (data == "OK") {
                        spinner.classList.add('hidden'); // Hide the spinner
                        document.getElementById('registrationForm').style.display = 'none';
                        document.getElementById('registrationSuccess').style.display = 'flex';
                        document.getElementById('registrationError').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    });


});

})(window.jQuery); 