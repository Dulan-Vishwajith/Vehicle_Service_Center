<?php

/*
|--------------------------------------------------------------------------
| MANAGE TERMS & CONDITIONS
|--------------------------------------------------------------------------
| Allows Management Employees to edit the booking Terms & Conditions
| displayed in the customer booking form.
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

$termsMessage = '';
$termsMessageType = '';

$termsContent = '';


/*
|--------------------------------------------------------------------------
| LOAD CURRENT TERMS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT id, content, status
        FROM terms_conditions
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute();

    $termsRecord = $stmt->fetch();

    if ($termsRecord) {

        $termsContent = $termsRecord['content'];

    }

} catch (PDOException $e) {

    $termsMessage = "Unable to load Terms & Conditions.";
    $termsMessageType = "error";

}


/*
|--------------------------------------------------------------------------
| HANDLE UPDATE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_terms'])
) {

    /*
    |--------------------------------------------------------------
    | GET CONTENT
    |--------------------------------------------------------------
    */

    $termsContent = trim($_POST['content'] ?? '');


    /*
    |--------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------
    */

    if ($termsContent === '') {

        $termsMessage = "Terms & Conditions cannot be empty.";
        $termsMessageType = "error";

    } else {

        try {

            /*
            |----------------------------------------------------------
            | CHECK EXISTING RECORD
            |----------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT id
                FROM terms_conditions
                ORDER BY id ASC
                LIMIT 1
            ");

            $stmt->execute();

            $existingTerms = $stmt->fetch();


            /*
            |----------------------------------------------------------
            | UPDATE EXISTING RECORD
            |----------------------------------------------------------
            */

            if ($existingTerms) {

                $stmt = $pdo->prepare("
                    UPDATE terms_conditions
                    SET
                        content = ?,
                        status = 1,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                $stmt->execute([
                    $termsContent,
                    $existingTerms['id']
                ]);

            }


            /*
            |----------------------------------------------------------
            | CREATE RECORD IF NONE EXISTS
            |----------------------------------------------------------
            */

            else {

                $stmt = $pdo->prepare("
                    INSERT INTO terms_conditions
                    (
                        content,
                        status
                    )
                    VALUES
                    (
                        ?,
                        1
                    )
                ");

                $stmt->execute([
                    $termsContent
                ]);

            }


            /*
            |----------------------------------------------------------
            | SUCCESS
            |----------------------------------------------------------
            */

            $termsMessage =
                "Terms & Conditions updated successfully.";

            $termsMessageType = "success";


        } catch (PDOException $e) {

            $termsMessage =
                "Unable to update Terms & Conditions.";

            $termsMessageType = "error";

        }

    }

}

?>


<!-- =========================================================
     MANAGE TERMS & CONDITIONS
========================================================= -->

<div class="panel-header">

    <div>

        <h2>
            Terms & Conditions
        </h2>

        <p class="panel-description">
            Edit the Terms & Conditions displayed during customer
            booking.
        </p>

    </div>

</div>


<!-- =========================================================
     MESSAGE
========================================================= -->

<?php if ($termsMessage): ?>

    <div class="admin-message <?= htmlspecialchars($termsMessageType) ?>">

        <?= htmlspecialchars($termsMessage) ?>

    </div>

<?php endif; ?>


<!-- =========================================================
     TERMS FORM
========================================================= -->

<div class="admin-form-card">

    <form
        method="post"
        action="?page=terms-conditions"
        id="termsConditionsForm"
    >

        <div class="form-group">

            <label for="termsContent">

                Booking Terms & Conditions

                <span class="required">
                    *
                </span>

            </label>


            <p class="form-help-text">

                You can use HTML formatting such as
                headings, paragraphs, lists and bold text.
                This content will be displayed in the booking
                Terms & Conditions popup.

            </p>


            <textarea
                name="content"
                id="termsContent"
                class="form-control terms-editor"
                rows="25"
                required
            ><?= htmlspecialchars(
                $termsContent,
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>

        </div>


        <!-- =====================================================
             ACTIONS
        ===================================================== -->

        <div class="form-actions">

            <button
                type="submit"
                name="update_terms"
                value="1"
                class="admin-primary-button"
            >
                Save Terms & Conditions
            </button>

            <button
                type="reset"
                class="admin-secondary-button"
            >
                Reset
            </button>

        </div>

    </form>

</div>


<!-- =========================================================
     PREVIEW
========================================================= -->

<div class="admin-form-card terms-preview-card">

    <div class="terms-preview-header">

        <div>

            <span class="terms-preview-label">
                PREVIEW
            </span>

            <h3>
                Customer Popup Preview
            </h3>

        </div>

    </div>


    <div
        class="terms-preview-content"
        id="termsPreview"
    >
        <?= $termsContent ?>
    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const textarea =
            document.getElementById('termsContent');

        const preview =
            document.getElementById('termsPreview');


        if (!textarea || !preview) {
            return;
        }


        /*
        |----------------------------------------------------------
        | LIVE PREVIEW
        |----------------------------------------------------------
        */

        textarea.addEventListener(
            'input',
            function () {

                preview.innerHTML =
                    textarea.value;

            }
        );

    }
);

</script>