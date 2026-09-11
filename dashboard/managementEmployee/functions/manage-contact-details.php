<?php

/*
|--------------------------------------------------------------------------
| MANAGE CONTACT DETAILS
|--------------------------------------------------------------------------
| Allows Management Employees to update the contact information
| displayed in the website footer.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ROLE PROTECTION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['role_id'] ?? null) !== 3) {

    header("Location: ../../login/login-form.php?error=invalid_role");
    exit();

}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$contactMessage = '';
$contactMessageType = '';

$contact = [
    'phone'   => '',
    'email'   => '',
    'address' => ''
];


/*
|--------------------------------------------------------------------------
| LOAD CURRENT CONTACT DETAILS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT id, phone, email, address
        FROM contact_details
        ORDER BY id ASC
        LIMIT 1
    ");

    $stmt->execute();

    $contactRecord = $stmt->fetch();

    if ($contactRecord) {

        $contact = [
            'phone'   => $contactRecord['phone'],
            'email'   => $contactRecord['email'],
            'address' => $contactRecord['address']
        ];

    }

} catch (PDOException $e) {

    $contactMessage = "Unable to load contact details.";
    $contactMessageType = "error";

}


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_contact_details'])
) {


    /*
    |--------------------------------------------------------------
    | GET INPUT
    |--------------------------------------------------------------
    */

    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');


    /*
    |--------------------------------------------------------------
    | REMOVE SPACES FROM PHONE
    |--------------------------------------------------------------
    */

    $phoneDigits = str_replace(' ', '', $phone);


    /*
    |--------------------------------------------------------------
    | FORMAT PHONE NUMBER
    |--------------------------------------------------------------
    |
    | User enters:
    | 0712345689
    |
    | Saved/displayed as:
    | 071 234 5689
    |
    */

    $formattedPhone = $phone;

    if (preg_match('/^0[0-9]{9}$/', $phoneDigits)) {

        $formattedPhone =
            substr($phoneDigits, 0, 3)
            . ' '
            . substr($phoneDigits, 3, 3)
            . ' '
            . substr($phoneDigits, 6, 4);

    }


    /*
    |--------------------------------------------------------------
    | KEEP FORM VALUES
    |--------------------------------------------------------------
    */

    $contact = [
        'phone'   => $formattedPhone,
        'email'   => $email,
        'address' => $address
    ];


    /*
    |--------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------
    */

    $errors = [];


    /*
    |--------------------------------------------------------------
    | PHONE VALIDATION
    |--------------------------------------------------------------
    */

    if ($phoneDigits === '') {

        $errors[] = "Phone number is required.";

    } elseif (!preg_match('/^0[0-9]{9}$/', $phoneDigits)) {

        $errors[] =
            "Phone number must contain exactly 10 digits and start with 0.";

    }


    /*
    |--------------------------------------------------------------
    | EMAIL VALIDATION
    |--------------------------------------------------------------
    |
    | Email must:
    | - be a valid email address
    | - end with .com or .lk
    |
    */

    if ($email === '') {

        $errors[] = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    } elseif (!preg_match('/\.(com|lk)$/i', $email)) {

        $errors[] = "Email address must end with .com or .lk.";

    }


    /*
    |--------------------------------------------------------------
    | ADDRESS VALIDATION
    |--------------------------------------------------------------
    */

    if ($address === '') {

        $errors[] = "Address is required.";

    }


    /*
    |--------------------------------------------------------------
    | UPDATE DATABASE
    |--------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            /*
            | Check whether the contact record exists.
            */

            $stmt = $pdo->prepare("
                SELECT id
                FROM contact_details
                ORDER BY id ASC
                LIMIT 1
            ");

            $stmt->execute();

            $existingContact = $stmt->fetch();


            if ($existingContact) {

                /*
                | Update existing record.
                */

                $stmt = $pdo->prepare("
                    UPDATE contact_details
                    SET
                        phone = ?,
                        email = ?,
                        address = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                $stmt->execute([
                    $formattedPhone,
                    $email,
                    $address,
                    $existingContact['id']
                ]);

            } else {

                /*
                | Create the first contact record if one does not exist.
                */

                $stmt = $pdo->prepare("
                    INSERT INTO contact_details
                    (
                        phone,
                        email,
                        address
                    )
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $formattedPhone,
                    $email,
                    $address
                ]);

            }


            $contact['phone'] = $formattedPhone;

            $contactMessage =
                "Contact details updated successfully.";

            $contactMessageType =
                "success";


        } catch (PDOException $e) {

            $contactMessage =
                "Unable to update contact details.";

            $contactMessageType =
                "error";

        }

    } else {

        /*
        | Show the first validation error.
        */

        $contactMessage = $errors[0];
        $contactMessageType = "error";

    }

}

?>


<div class="panel-header">

    <h2>
        Manage Contact Details
    </h2>

</div>


<?php if ($contactMessage): ?>

    <div class="admin-message <?= htmlspecialchars($contactMessageType) ?>">

        <?= htmlspecialchars($contactMessage) ?>

    </div>

<?php endif; ?>


<div class="ops-section">

    <p style="
        margin-bottom: 20px;
        color: var(--color-muted);
        font-size: 13px;
    ">

        Update the phone number, email address and location
        displayed in the website footer.

    </p>


    <form method="POST">


        <div class="admin-form-grid">


            <!-- PHONE -->

            <div class="admin-form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($contact['phone']) ?>"
                    placeholder="071 234 5689"
                    maxlength="12"
                    inputmode="numeric"
                    autocomplete="tel"
                    title="Enter a 10-digit phone number. Example: 0712345689"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="admin-form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($contact['email']) ?>"
                    placeholder="info@veyro.lk"
                    maxlength="150"
                    pattern="^[^@\s]+@[^@\s]+\.(com|lk)$"
                    title="Email address must end with .com or .lk"
                    autocomplete="email"
                    required
                >

            </div>


            <!-- ADDRESS -->

            <div class="admin-form-group admin-form-full">

                <label for="address">
                    Address
                </label>

                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    maxlength="255"
                    placeholder="Colombo, Sri Lanka"
                    required
                ><?= htmlspecialchars($contact['address']) ?></textarea>

            </div>


        </div>


        <div class="admin-form-actions">

            <button
                type="submit"
                name="update_contact_details"
                class="admin-save-btn"
            >
                Save Contact Details
            </button>

        </div>


    </form>

</div>


<script>

/*
|--------------------------------------------------------------------------
| AUTOMATIC PHONE NUMBER FORMATTING
|--------------------------------------------------------------------------
|
| User types:
| 0712345689
|
| Displayed as:
| 071 234 5689
|
*/

const phoneInput = document.getElementById('phone');


phoneInput.addEventListener('input', function () {

    let numbers = this.value.replace(/\D/g, '');

    numbers = numbers.slice(0, 10);


    if (numbers.length <= 3) {

        this.value = numbers;
        return;

    }


    if (numbers.length <= 6) {

        this.value =
            numbers.slice(0, 3)
            + ' '
            + numbers.slice(3);

        return;

    }


    this.value =
        numbers.slice(0, 3)
        + ' '
        + numbers.slice(3, 6)
        + ' '
        + numbers.slice(6, 10);

});


/*
|--------------------------------------------------------------------------
| PHONE VALIDATION
|--------------------------------------------------------------------------
*/

phoneInput.addEventListener('blur', function () {

    const numbers =
        this.value.replace(/\D/g, '');


    if (
        numbers.length !== 10
        || numbers.charAt(0) !== '0'
    ) {

        this.setCustomValidity(
            'Phone number must contain exactly 10 digits and start with 0.'
        );

    } else {

        this.setCustomValidity('');

    }

});


phoneInput.addEventListener('input', function () {

    this.setCustomValidity('');

});


/*
|--------------------------------------------------------------------------
| EMAIL INPUT CONTROL
|--------------------------------------------------------------------------
|
| Once the email ends with:
|
| .lk
| .com
|
| normal typing after that point is blocked.
|
*/

const emailInput = document.getElementById('email');


/*
|--------------------------------------------------------------------------
| BLOCK TYPING AFTER .LK OR .COM
|--------------------------------------------------------------------------
*/

emailInput.addEventListener('keydown', function (event) {

    const email = this.value.toLowerCase();

    const emailFinished =
        email.endsWith('.lk') ||
        email.endsWith('.com');


    if (emailFinished) {

        /*
        | Allow keys needed for editing/navigation.
        */

        const allowedKeys = [
            'Backspace',
            'Delete',
            'ArrowLeft',
            'ArrowRight',
            'ArrowUp',
            'ArrowDown',
            'Home',
            'End',
            'Tab',
            'Escape'
        ];


        /*
        | Allow common keyboard shortcuts.
        */

        if (
            allowedKeys.includes(event.key) ||
            event.ctrlKey ||
            event.metaKey
        ) {
            return;
        }


        /*
        | Block normal character typing.
        */

        event.preventDefault();

    }

});


/*
|--------------------------------------------------------------------------
| PROTECT AGAINST PASTE / AUTOFILL
|--------------------------------------------------------------------------
|
| Examples:
|
| info@veyro.lkabc
|
| becomes:
|
| info@veyro.lk
|
|--------------------------------------------------------------------------
*/

emailInput.addEventListener('input', function () {

    let email = this.value;

    const lowerEmail = email.toLowerCase();

    const comPosition = lowerEmail.indexOf('.com');
    const lkPosition = lowerEmail.indexOf('.lk');


    /*
    | Find whichever valid ending appears first.
    */

    let endPosition = -1;
    let endLength = 0;


    if (comPosition !== -1 && lkPosition !== -1) {

        if (comPosition < lkPosition) {

            endPosition = comPosition;
            endLength = 4;

        } else {

            endPosition = lkPosition;
            endLength = 3;

        }

    } else if (comPosition !== -1) {

        endPosition = comPosition;
        endLength = 4;

    } else if (lkPosition !== -1) {

        endPosition = lkPosition;
        endLength = 3;

    }


    /*
    | Remove everything after .com or .lk.
    */

    if (endPosition !== -1) {

        this.value =
            email.substring(
                0,
                endPosition + endLength
            );

    }


    this.setCustomValidity('');

});


/*
|--------------------------------------------------------------------------
| EMAIL VALIDATION
|--------------------------------------------------------------------------
*/

function validateEmail() {

    const email = emailInput.value.trim();


    if (email === '') {

        emailInput.setCustomValidity('');
        return;

    }


    /*
    | Complete allowed email structure.
    */

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.(com|lk)$/i;


    if (!emailPattern.test(email)) {

        emailInput.setCustomValidity(
            'Please enter a valid email address ending with .com or .lk.'
        );

        return;

    }


    emailInput.setCustomValidity('');

}


/*
|--------------------------------------------------------------------------
| VALIDATE WHEN LEAVING EMAIL FIELD
|--------------------------------------------------------------------------
*/

emailInput.addEventListener('blur', function () {

    validateEmail();

});


/*
|--------------------------------------------------------------------------
| VALIDATE BEFORE FORM SUBMIT
|--------------------------------------------------------------------------
*/

emailInput.form.addEventListener('submit', function (event) {

    validateEmail();


    if (!emailInput.checkValidity()) {

        event.preventDefault();

        emailInput.reportValidity();

    }

});

</script>