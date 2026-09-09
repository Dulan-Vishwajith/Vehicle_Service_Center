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

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_contact_details'])) {


    /*
    |--------------------------------------------------------------
    | GET AND SANITIZE INPUT
    |--------------------------------------------------------------
    */

    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');


    /*
    |--------------------------------------------------------------
    | KEEP FORM VALUES
    |--------------------------------------------------------------
    */

    $contact = [
        'phone'   => $phone,
        'email'   => $email,
        'address' => $address
    ];


    /*
    |--------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------
    */

    $errors = [];


    if ($phone === '') {

        $errors[] = "Phone number is required.";

    } elseif (!preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {

        $errors[] = "Please enter a valid phone number.";

    }


    if ($email === '') {

        $errors[] = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


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
            | Check whether the single contact record exists.
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
                    $phone,
                    $email,
                    $address,
                    $existingContact['id']
                ]);

            } else {

                /*
                | Create the first record if one does not exist.
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
                    $phone,
                    $email,
                    $address
                ]);

            }


            $contactMessage = "Contact details updated successfully.";
            $contactMessageType = "success";


        } catch (PDOException $e) {

            $contactMessage = "Unable to update contact details.";
            $contactMessageType = "error";

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

    <p style="margin-bottom: 20px; color: var(--color-muted); font-size: 13px;">

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
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($contact['phone']) ?>"
                    placeholder="+94 77 123 4567"
                    maxlength="30"
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