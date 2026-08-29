
document.addEventListener("DOMContentLoaded", function () {
    
    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const bookingForm =
        document.getElementById("bookingForm");


    const serviceSelector =
        document.getElementById("serviceSelector");


    const selectedServicesContainer =
        document.getElementById("selectedServices");


    const serviceInputs =
        document.getElementById("serviceInputs");


    const summaryContainer =
        document.getElementById("selectedServicesSummary");


    const servicePriceElement =
        document.getElementById("servicePrice");


    const serviceTotalElement =
        document.getElementById("serviceTotal");


    const totalDurationElement =
        document.getElementById("totalDuration");


    const vehicleModel =
        document.getElementById("vehicleModel");


    const licensePlate =
        document.getElementById("licensePlate");


    const vehicleYear =
        document.getElementById("vehicleYear");


    const vehicleType =
        document.getElementById("vehicleType");


    const bookingDate =
        document.getElementById("bookingDate");


    const timeSlot =
        document.getElementById("timeSlot");


    const notes =
        document.getElementById("notes");


    const terms =
        document.querySelector(
            'input[name="terms"]'
        );


    /*
    |--------------------------------------------------------------------------
    | SELECTED SERVICES
    |--------------------------------------------------------------------------
    */

    let selectedServices = [];


    /*
    |--------------------------------------------------------------------------
    | FORMAT MONEY
    |--------------------------------------------------------------------------
    */

    function formatMoney(amount) {

        return new Intl.NumberFormat(
            "en-LK",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(amount);

    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT DURATION
    |--------------------------------------------------------------------------
    */

    function formatDuration(minutes) {

        minutes =
            parseInt(minutes) || 0;


        if (minutes <= 0) {

            return "0 Minutes";

        }


        const hours =
            Math.floor(
                minutes / 60
            );


        const remainingMinutes =
            minutes % 60;


        let result = "";


        if (hours > 0) {

            result +=
                hours +
                (
                    hours === 1
                        ? " Hour"
                        : " Hours"
                );

        }


        if (remainingMinutes > 0) {

            if (result !== "") {

                result += " ";

            }


            result +=
                remainingMinutes +
                " Minutes";

        }


        return result;

    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    /*
    |--------------------------------------------------------------------------
    | ADD SERVICE
    |--------------------------------------------------------------------------
    */

    serviceSelector.addEventListener(
        "change",
        function () {


            const option =
                serviceSelector.options[
                    serviceSelector.selectedIndex
                ];


            /*
            | Nothing selected
            */

            if (!option.value) {

                return;

            }


            const serviceId =
                parseInt(option.value);


            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE
            |--------------------------------------------------------------------------
            */

            const alreadyExists =
                selectedServices.some(
                    function (service) {

                        return (
                            service.id ===
                            serviceId
                        );

                    }
                );


            if (alreadyExists) {

                alert(
                    "This service has already been added."
                );


                serviceSelector.value =
                    "";


                return;

            }


            /*
            |--------------------------------------------------------------------------
            | CREATE SERVICE
            |--------------------------------------------------------------------------
            */

            const service = {

                id:
                    serviceId,

                name:
                    option.dataset.name,

                price:
                    parseFloat(
                        option.dataset.price
                    ) || 0,

                duration:
                    parseInt(
                        option.dataset.duration
                    ) || 0,

                durationText:
                    option.dataset.durationText

            };


            /*
            |--------------------------------------------------------------------------
            | ADD SERVICE
            |--------------------------------------------------------------------------
            */

            selectedServices.push(
                service
            );


            /*
            |--------------------------------------------------------------------------
            | RESET DROPDOWN
            |--------------------------------------------------------------------------
            */

            serviceSelector.value =
                "";


            /*
            |--------------------------------------------------------------------------
            | UPDATE BOOKING
            |--------------------------------------------------------------------------
            */

            updateBooking();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | UPDATE BOOKING
    |--------------------------------------------------------------------------
    */

    function updateBooking() {


        selectedServicesContainer.innerHTML =
            "";


        serviceInputs.innerHTML =
            "";


        summaryContainer.innerHTML =
            "";


        let totalPrice = 0;

        let totalDuration = 0;


        /*
        |--------------------------------------------------------------------------
        | NO SERVICES
        |--------------------------------------------------------------------------
        */

        if (
            selectedServices.length === 0
        ) {

            selectedServicesContainer.innerHTML = `

                <div class="no-services">

                    No services selected yet.

                </div>

            `;


            summaryContainer.innerHTML = `

                <p class="empty-summary">

                    Select one or more services.

                </p>

            `;

        }


        /*
        |--------------------------------------------------------------------------
        | DISPLAY SELECTED SERVICES
        |--------------------------------------------------------------------------
        */

        selectedServices.forEach(
            function (service, index) {


                totalPrice +=
                    service.price;


                totalDuration +=
                    service.duration;


                /*
                |--------------------------------------------------------------------------
                | LEFT SIDE SERVICE
                |--------------------------------------------------------------------------
                */

                const serviceItem =
                    document.createElement(
                        "div"
                    );


                serviceItem.className =
                    "selected-service-item";


                serviceItem.innerHTML = `

                    <div
                        class="selected-service-info"
                    >

                        <span
                            class="selected-service-name"
                        >

                            ${escapeHtml(
                                service.name
                            )}

                        </span>


                        <span
                            class="selected-service-duration"
                        >

                            ${escapeHtml(
                                service.durationText
                            )}

                        </span>

                    </div>


                    <div
                        class="selected-service-right"
                    >

                        <span
                            class="selected-service-price"
                        >

                            Rs.
                            ${formatMoney(
                                service.price
                            )}

                        </span>


                        <button
                            type="button"
                            class="remove-service"
                            data-index="${index}"
                            aria-label="Remove service"
                            title="Remove service"
                        >

                            ×

                        </button>

                    </div>

                `;


                selectedServicesContainer.appendChild(
                    serviceItem
                );


                /*
                |--------------------------------------------------------------------------
                | HIDDEN SERVICE INPUT
                |--------------------------------------------------------------------------
                */

                const hiddenInput =
                    document.createElement(
                        "input"
                    );


                hiddenInput.type =
                    "hidden";


                hiddenInput.name =
                    "services[]";


                hiddenInput.value =
                    service.id;


                serviceInputs.appendChild(
                    hiddenInput
                );


                /*
                |--------------------------------------------------------------------------
                | RIGHT SIDE SUMMARY
                |--------------------------------------------------------------------------
                */

                const summaryRow =
                    document.createElement(
                        "div"
                    );


                summaryRow.className =
                    "summary-service-row";


                summaryRow.innerHTML = `

                    <span
                        class="summary-service-name"
                    >

                        ${escapeHtml(
                            service.name
                        )}

                    </span>


                    <strong
                        class="summary-service-price"
                    >

                        Rs.
                        ${formatMoney(
                            service.price
                        )}

                    </strong>

                `;


                summaryContainer.appendChild(
                    summaryRow
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | UPDATE PRICE
        |--------------------------------------------------------------------------
        */

        servicePriceElement.textContent =
            "Rs. " +
            formatMoney(
                totalPrice
            );


        serviceTotalElement.textContent =
            "Rs. " +
            formatMoney(
                totalPrice
            );


        /*
        |--------------------------------------------------------------------------
        | UPDATE DURATION
        |--------------------------------------------------------------------------
        */

        totalDurationElement.textContent =
            formatDuration(
                totalDuration
            );


        /*
        |--------------------------------------------------------------------------
        | REMOVE SERVICES
        |--------------------------------------------------------------------------
        */

        const removeButtons =
            document.querySelectorAll(
                ".remove-service"
            );


        removeButtons.forEach(
            function (button) {


                button.addEventListener(
                    "click",
                    function () {


                        const index =
                            parseInt(
                                this.dataset.index
                            );


                        if (
                            Number.isInteger(index)
                        ) {

                            selectedServices.splice(
                                index,
                                1
                            );

                        }


                        updateBooking();

                    }
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPER
    |--------------------------------------------------------------------------
    */

    function setError(
        element,
        message
    ) {

        if (!element) {
            return false;
        }


        element.setCustomValidity(
            message
        );


        return false;

    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR VALIDATION ERROR
    |--------------------------------------------------------------------------
    */

    function clearError(element) {

        if (!element) {
            return;
        }


        element.setCustomValidity("");

    }


    /*
    |--------------------------------------------------------------------------
    | VEHICLE MODEL VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateVehicleModel() {

        if (!vehicleModel) {
            return true;
        }


        const value =
            vehicleModel.value.trim();


        if (value === "") {

            return setError(
                vehicleModel,
                "Please enter your vehicle make and model."
            );

        }


        if (value.length < 2) {

            return setError(
                vehicleModel,
                "Vehicle make and model must contain at least 2 characters."
            );

        }


        if (value.length > 100) {

            return setError(
                vehicleModel,
                "Vehicle make and model cannot exceed 100 characters."
            );

        }


        /*
        | Prevent only symbols
        */

        if (
            !/[A-Za-z0-9]/.test(value)
        ) {

            return setError(
                vehicleModel,
                "Please enter a valid vehicle make and model."
            );

        }


        clearError(vehicleModel);

        return true;

    }


    /*
|--------------------------------------------------------------------------
| LICENSE PLATE VALIDATION
|--------------------------------------------------------------------------
|
| Allowed formats:
|
| 50-1234
| 256-2134
| AB-1234
| ABC-1234
|
| Also accepts:
|
| 50 1234
| 256 2134
| AB 1234
| ABC 1234
| 501234
| 2562134
| AB1234
| ABC1234
|
|--------------------------------------------------------------------------
*/


function validateLicensePlate() {

    if (!licensePlate) {
        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Get value
    |--------------------------------------------------------------------------
    */

    let value =
        licensePlate.value
            .trim()
            .toUpperCase();


    /*
    |--------------------------------------------------------------------------
    | Remove spaces and hyphens
    | before checking
    |--------------------------------------------------------------------------
    */

    const cleanPlate =
        value.replace(
            /[\s-]/g,
            ""
        );


    /*
    |--------------------------------------------------------------------------
    | EMPTY
    |--------------------------------------------------------------------------
    */

    if (cleanPlate === "") {

        return setError(
            licensePlate,
            "Please enter your vehicle registration number."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LICENSE PLATE PATTERN
    |--------------------------------------------------------------------------
    |
    | 2 or 3 NUMBERS + 4 NUMBERS
    |
    | Example:
    | 50-1234
    | 256-2134
    |
    |
    | OR
    |
    | 2 or 3 LETTERS + 4 NUMBERS
    |
    | Example:
    | AB-1234
    | ABC-1234
    |
    */

    const platePattern =
        /^(?:[0-9]{2,3}|[A-Z]{2,3})[0-9]{4}$/;


    /*
    |--------------------------------------------------------------------------
    | CHECK PATTERN
    |--------------------------------------------------------------------------
    */

    if (
        !platePattern.test(
            cleanPlate
        )
    ) {

        return setError(
            licensePlate,
            "Invalid registration number. Examples: 50-1234, 256-2134, AB-1234, ABC-1234."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALID
    |--------------------------------------------------------------------------
    */

    clearError(
        licensePlate
    );


    return true;

}


/*
|--------------------------------------------------------------------------
| LICENSE PLATE INPUT
|--------------------------------------------------------------------------
*/

if (licensePlate) {

    licensePlate.addEventListener(
        "input",
        function () {


            /*
            |--------------------------------------------------------------------------
            | Convert to uppercase
            |--------------------------------------------------------------------------
            */

            let value =
                this.value.toUpperCase();


            /*
            |--------------------------------------------------------------------------
            | Allow only:
            |
            | A-Z
            | 0-9
            | spaces
            | hyphens
            |--------------------------------------------------------------------------
            */

            value =
                value.replace(
                    /[^A-Z0-9\s-]/g,
                    ""
                );


            /*
            |--------------------------------------------------------------------------
            | Remove existing spaces and hyphens
            | temporarily
            |--------------------------------------------------------------------------
            */

            let cleanValue =
                value.replace(
                    /[\s-]/g,
                    ""
                );


            /*
            |--------------------------------------------------------------------------
            | Maximum 7 characters
            |
            | ABC1234 = 7
            | 2561234 = 7
            |--------------------------------------------------------------------------
            */

            cleanValue =
                cleanValue.substring(
                    0,
                    7
                );


            /*
            |--------------------------------------------------------------------------
            | Automatically add hyphen
            |--------------------------------------------------------------------------
            |
            | Examples:
            |
            | 501234
            | becomes
            | 50-1234
            |
            | 2561234
            | becomes
            | 256-1234
            |
            | AB1234
            | becomes
            | AB-1234
            |
            | ABC1234
            | becomes
            | ABC-1234
            |
            |--------------------------------------------------------------------------
            */

            const firstPart =
                cleanValue.match(
                    /^[A-Z]{2,3}|^[0-9]{2,3}/
                );


            if (firstPart) {

                const prefix =
                    firstPart[0];

                const numbers =
                    cleanValue.substring(
                        prefix.length
                    );


                /*
                |--------------------------------------------------------------------------
                | Only add hyphen when there are numbers
                |--------------------------------------------------------------------------
                */

                if (numbers.length > 0) {

                    this.value =
                        prefix +
                        "-" +
                        numbers;

                } else {

                    this.value =
                        prefix;

                }

            } else {

                this.value =
                    cleanValue;

            }


            /*
            |--------------------------------------------------------------------------
            | Validate while typing
            |--------------------------------------------------------------------------
            */

            validateLicensePlate();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Validate when leaving the field
    |--------------------------------------------------------------------------
    */

    licensePlate.addEventListener(
        "blur",
        function () {

            validateLicensePlate();

        }
    );

}

    /*
    |--------------------------------------------------------------------------
    | VEHICLE YEAR VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateVehicleYear() {

        if (!vehicleYear) {
            return true;
        }


        /*
        | Year is optional in your form.
        | If selected, validate it.
        */

        if (vehicleYear.value === "") {

            clearError(vehicleYear);

            return true;

        }


        const year =
            parseInt(
                vehicleYear.value
            );


        const currentYear =
            new Date().getFullYear();


        if (
            isNaN(year) ||
            year < 1900 ||
            year > currentYear
        ) {

            return setError(
                vehicleYear,
                "Please select a valid vehicle year."
            );

        }


        clearError(vehicleYear);

        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | VEHICLE TYPE VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateVehicleType() {

        if (!vehicleType) {
            return true;
        }


        /*
        | Vehicle type is optional in your form.
        */

        if (
            vehicleType.value === ""
        ) {

            clearError(vehicleType);

            return true;

        }


        clearError(vehicleType);

        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | BOOKING DATE VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateBookingDate() {

        if (!bookingDate) {
            return true;
        }


        const value =
            bookingDate.value;


        if (value === "") {

            return setError(
                bookingDate,
                "Please select a service date."
            );

        }


        /*
        | Create today's date
        */

        const today =
            new Date();


        today.setHours(
            0,
            0,
            0,
            0
        );


        /*
        | Selected date
        */

        const selectedDate =
            new Date(
                value + "T00:00:00"
            );


        /*
        | Do not allow past dates
        */

        if (
            selectedDate < today
        ) {

            return setError(
                bookingDate,
                "Service date cannot be in the past."
            );

        }


        clearError(
            bookingDate
        );


        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | TIME SLOT VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateTimeSlot() {

        if (!timeSlot) {
            return true;
        }


        if (
            timeSlot.value === ""
        ) {

            return setError(
                timeSlot,
                "Please select a time slot."
            );

        }


        clearError(
            timeSlot
        );


        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | NOTES VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateNotes() {

        if (!notes) {
            return true;
        }


        const value =
            notes.value.trim();


        /*
        | Notes are optional.
        */

        if (value === "") {

            clearError(notes);

            return true;

        }


        if (value.length > 1000) {

            return setError(
                notes,
                "Additional notes cannot exceed 1000 characters."
            );

        }


        clearError(notes);

        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | TERMS VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateTerms() {

        if (!terms) {
            return true;
        }


        if (!terms.checked) {

            setError(
                terms,
                "Please accept the booking terms and conditions."
            );


            return false;

        }


        clearError(terms);

        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | SERVICES VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateServices() {

        if (
            selectedServices.length === 0
        ) {

            alert(
                "Please select at least one service."
            );


            serviceSelector.focus();


            return false;

        }


        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETE FORM VALIDATION
    |--------------------------------------------------------------------------
    */

    function validateForm() {


        /*
        | Validate services
        */

        if (!validateServices()) {

            return false;

        }


        /*
        | Validate vehicle model
        */

        if (!validateVehicleModel()) {

            vehicleModel.focus();

            return false;

        }


        /*
        | Validate license plate
        */

        if (!validateLicensePlate()) {

            licensePlate.focus();

            return false;

        }


        /*
        | Validate vehicle year
        */

        if (!validateVehicleYear()) {

            vehicleYear.focus();

            return false;

        }


        /*
        | Validate vehicle type
        */

        if (!validateVehicleType()) {

            vehicleType.focus();

            return false;

        }


        /*
        | Validate booking date
        */

        if (!validateBookingDate()) {

            bookingDate.focus();

            return false;

        }


        /*
        | Validate time slot
        */

        if (!validateTimeSlot()) {

            timeSlot.focus();

            return false;

        }


        /*
        | Validate notes
        */

        if (!validateNotes()) {

            notes.focus();

            return false;

        }


        /*
        | Validate terms
        */

        if (!validateTerms()) {

            terms.focus();

            return false;

        }


        /*
        |--------------------------------------------------------------------------
        | ALL VALID
        |--------------------------------------------------------------------------
        */

        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | REAL-TIME VALIDATION
    |--------------------------------------------------------------------------
    */

    if (vehicleModel) {

        vehicleModel.addEventListener(
            "blur",
            validateVehicleModel
        );

    }


    if (vehicleYear) {

        vehicleYear.addEventListener(
            "change",
            validateVehicleYear
        );

    }


    if (vehicleType) {

        vehicleType.addEventListener(
            "change",
            validateVehicleType
        );

    }


    if (bookingDate) {

        bookingDate.addEventListener(
            "change",
            validateBookingDate
        );

    }


    if (timeSlot) {

        timeSlot.addEventListener(
            "change",
            validateTimeSlot
        );

    }


    if (notes) {

        notes.addEventListener(
            "blur",
            validateNotes
        );

    }


    if (terms) {

        terms.addEventListener(
            "change",
            validateTerms
        );

    }


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMIT
    |--------------------------------------------------------------------------
    */

    if (bookingForm) {

        bookingForm.addEventListener(
            "submit",
            function (event) {


                /*
                | Run complete validation
                */

                const isValid =
                    validateForm();


                /*
                | Stop submission if invalid
                */

                if (!isValid) {

                    event.preventDefault();

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | VALID
                |--------------------------------------------------------------------------
                |
                | Allow booking-submit.php to process the form.
                |
                */

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE OLD SELECTED SERVICES
    |--------------------------------------------------------------------------
    */

    if (
        Array.isArray(
            window.oldSelectedServices
        )
    ) {


        window.oldSelectedServices.forEach(
            function (oldId) {


                const option =
                    serviceSelector.querySelector(
                        `option[value="${oldId}"]`
                    );


                if (!option) {

                    return;

                }


                const serviceId =
                    parseInt(
                        option.value
                    );


                /*
                | Avoid duplicates
                */

                const exists =
                    selectedServices.some(
                        function (service) {

                            return (
                                service.id ===
                                serviceId
                            );

                        }
                    );


                if (exists) {

                    return;

                }


                selectedServices.push({

                    id:
                        serviceId,

                    name:
                        option.dataset.name,

                    price:
                        parseFloat(
                            option.dataset.price
                        ) || 0,

                    duration:
                        parseInt(
                            option.dataset.duration
                        ) || 0,

                    durationText:
                        option.dataset.durationText

                });

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ADD SERVICE FROM "BOOK SERVICE" BUTTON
    |--------------------------------------------------------------------------
    |
    | booking.php reads ?service_id=X from the URL and
    | booking-form.php marks the matching <option> as
    | "selected" on the server side.
    |
    | The dropdown being pre-selected does not by itself
    | add the service to the list/summary, so we do that
    | here on page load, the same way a manual dropdown
    | selection would.
    |
    */

    if (serviceSelector.value) {


        const preselectedOption =
            serviceSelector.options[
                serviceSelector.selectedIndex
            ];


        const preselectedId =
            parseInt(serviceSelector.value);


        const alreadyAdded =
            selectedServices.some(
                function (service) {

                    return (
                        service.id ===
                        preselectedId
                    );

                }
            );


        if (!alreadyAdded) {

            selectedServices.push({

                id:
                    preselectedId,

                name:
                    preselectedOption.dataset.name,

                price:
                    parseFloat(
                        preselectedOption.dataset.price
                    ) || 0,

                duration:
                    parseInt(
                        preselectedOption.dataset.duration
                    ) || 0,

                durationText:
                    preselectedOption.dataset.durationText

            });

        }


        /*
        | Reset the dropdown back to the placeholder
        | so it behaves like the normal add flow.
        */

        serviceSelector.value =
            "";

    }


    /*
    |--------------------------------------------------------------------------
    | INITIAL UPDATE
    |--------------------------------------------------------------------------
    */

    updateBooking();

});