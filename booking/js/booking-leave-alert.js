/* =========================================================
   UNSAVED BOOKING / LEAVE PAGE PROTECTION
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const bookingForm = document.getElementById("bookingForm");

    if (!bookingForm) {
        return;
    }

    let bookingSubmitted = false;
    let allowLeave = false;

    /*
     * Check whether the customer has started filling the booking.
     */
    function hasBookingData() {

        // Selected services
        const serviceInputs =
            bookingForm.querySelectorAll(
                'input[name="services[]"]'
            );

        if (serviceInputs.length > 0) {
            return true;
        }

        // Normal form fields
        const fields =
            bookingForm.querySelectorAll(
                "input:not([type='hidden']), select, textarea"
            );

        for (const field of fields) {

            if (field.type === "submit") {
                continue;
            }

            if (field.type === "checkbox") {

                if (field.checked) {
                    return true;
                }

            } else if (
                typeof field.value === "string" &&
                field.value.trim() !== ""
            ) {

                return true;
            }
        }

        return false;
    }


    /*
     * Browser refresh / close tab / browser navigation.
     *
     * Modern browsers show their own standard warning.
     */
    window.addEventListener("beforeunload", function (event) {

        if (
            !bookingSubmitted &&
            !allowLeave &&
            hasBookingData()
        ) {

            event.preventDefault();

            // Required for some browsers
            event.returnValue = "";

            return "";
        }

    });


    /*
     * Protect normal links inside the page.
     *
     * Example:
     *     ← Dashboard
     */
    bookingForm.addEventListener("click", function (event) {

        const link = event.target.closest("a");

        if (!link) {
            return;
        }

        if (
            bookingSubmitted ||
            allowLeave ||
            !hasBookingData()
        ) {
            return;
        }

        /*
         * Ignore links that don't actually leave the page.
         */
        const href = link.getAttribute("href");

        if (
            !href ||
            href === "#" ||
            href.startsWith("javascript:")
        ) {
            return;
        }

        const leavePage =
            window.confirm(
                "You have started filling out your booking.\n\n" +
                "If you leave this page now, your entered information may be lost.\n\n" +
                "Do you want to leave this page?"
            );

        if (!leavePage) {

            event.preventDefault();
            event.stopPropagation();

        } else {

            allowLeave = true;

        }

    });


    /*
     * When the booking form is successfully submitted,
     * don't show the leave warning.
     */
    bookingForm.addEventListener("submit", function () {

        /*
         * Let HTML5 validation run first.
         *
         * If the form is invalid, submission will be blocked
         * by the browser and the flag will be reset.
         */
        bookingSubmitted = true;

        /*
         * Give the browser a small chance to perform
         * validation before disabling the protection.
         */
        setTimeout(function () {

            if (!bookingForm.checkValidity()) {
                bookingSubmitted = false;
            }

        }, 100);

    });


    /*
     * Browser Back / Forward protection.
     *
     * The beforeunload handler normally handles this,
     * but this provides an additional safeguard.
     */
    window.addEventListener("popstate", function () {

        if (
            bookingSubmitted ||
            allowLeave ||
            !hasBookingData()
        ) {
            return;
        }

        const leavePage =
            window.confirm(
                "You have started filling out your booking.\n\n" +
                "If you go back now, your entered information may be lost.\n\n" +
                "Do you want to leave this page?"
            );

        if (!leavePage) {

            history.pushState(
                null,
                "",
                window.location.href
            );

        } else {

            allowLeave = true;
            history.back();

        }

    });


    /*
     * Create a history entry so browser back can be detected.
     */
    history.pushState(
        null,
        "",
        window.location.href
    );

});

