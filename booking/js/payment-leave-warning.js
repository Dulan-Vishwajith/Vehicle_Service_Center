document.addEventListener('DOMContentLoaded', function () {

    let formStarted = false;
    let formSubmitted = false;

    /*
     * Find forms on the current page.
     *
     * payment.php:
     *   #cardPaymentForm
     *   #bankPaymentForm
     *
     * replace-payment.php:
     *   normal POST form
     */
    const forms = document.querySelectorAll(
        '#cardPaymentForm, #bankPaymentForm, form[data-payment-form]'
    );

    if (forms.length === 0) {
        return;
    }

    forms.forEach(function (form) {

        // User starts filling/changing the form
        form.addEventListener('input', function () {
            formStarted = true;
        });

        form.addEventListener('change', function () {
            formStarted = true;
        });

        // User intentionally submits the form
        form.addEventListener('submit', function () {
            formSubmitted = true;
        });
    });


    /*
     * Warn when leaving while payment form
     * has been started but not submitted.
     */
    window.addEventListener('beforeunload', function (event) {

        if (formStarted && !formSubmitted) {

            event.preventDefault();

            event.returnValue =
                'You have not completed the payment. Are you sure you want to leave?';

            return event.returnValue;
        }

    });

});