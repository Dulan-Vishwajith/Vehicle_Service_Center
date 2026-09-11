<?php

/*
|--------------------------------------------------------------------------
| MANAGEMENT - BANK DETAILS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

if (($_SESSION['role_id'] ?? null) !== 3) {
    header("Location: ../../login/login-form.php?error=invalid_role");
    exit();
}


require_once __DIR__ . '/../../../config/database.php';

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['bank_details_csrf'])) {
    $_SESSION['bank_details_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION['bank_details_csrf'];


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$bankMessage =
    $_SESSION['bank_details_message'] ?? '';

$bankMessageType =
    $_SESSION['bank_details_message_type'] ?? '';

unset(
    $_SESSION['bank_details_message'],
    $_SESSION['bank_details_message_type']
);


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function bankDetailsMessage($message, $type = 'success')
{
    $_SESSION['bank_details_message'] = $message;
    $_SESSION['bank_details_message_type'] = $type;

    header('Location: ?page=bank-details');
    exit();
}


function validBankDetailsCsrf()
{
    return isset(
        $_POST['csrf_token'],
        $_SESSION['bank_details_csrf']
    )
    && hash_equals(
        $_SESSION['bank_details_csrf'],
        $_POST['csrf_token']
    );
}


/*
|--------------------------------------------------------------------------
| FORM ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validBankDetailsCsrf()) {

        bankDetailsMessage(
            'Invalid security request. Please try again.',
            'error'
        );
    }


    $action =
        $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | ADD BANK ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $bankName =
            trim($_POST['bank_name'] ?? '');

        $accountName =
            trim($_POST['account_name'] ?? '');

        $accountNumber =
            trim($_POST['account_number'] ?? '');

        $branch =
            trim($_POST['branch'] ?? '');


        if (
            $bankName === ''
            || $accountName === ''
            || $accountNumber === ''
            || $branch === ''
        ) {

            bankDetailsMessage(
                'Please complete all bank details.',
                'error'
            );
        }


        if (
            strlen($bankName) > 150
            || strlen($accountName) > 150
            || strlen($accountNumber) > 100
            || strlen($branch) > 150
        ) {

            bankDetailsMessage(
                'One or more fields are too long.',
                'error'
            );
        }


        try {

            /*
            |--------------------------------------------------------------
            | Make the new account the active payment account.
            |--------------------------------------------------------------
            */

            $pdo->beginTransaction();


            $pdo->exec("
                UPDATE bank_accounts
                SET is_active = 0
                WHERE is_active = 1
            ");


            $stmt = $pdo->prepare("
                INSERT INTO bank_accounts
                (
                    bank_name,
                    account_name,
                    account_number,
                    branch,
                    is_active
                )
                VALUES
                (
                    :bank_name,
                    :account_name,
                    :account_number,
                    :branch,
                    1
                )
            ");


            $stmt->execute([
                ':bank_name' =>
                    $bankName,

                ':account_name' =>
                    $accountName,

                ':account_number' =>
                    $accountNumber,

                ':branch' =>
                    $branch
            ]);


            $pdo->commit();


            bankDetailsMessage(
                'Bank account added successfully.'
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            bankDetailsMessage(
                'Unable to add bank account.',
                'error'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT BANK ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $id =
            (int) ($_POST['id'] ?? 0);

        $bankName =
            trim($_POST['bank_name'] ?? '');

        $accountName =
            trim($_POST['account_name'] ?? '');

        $accountNumber =
            trim($_POST['account_number'] ?? '');

        $branch =
            trim($_POST['branch'] ?? '');


        if ($id <= 0) {

            bankDetailsMessage(
                'Invalid bank account.',
                'error'
            );
        }


        if (
            $bankName === ''
            || $accountName === ''
            || $accountNumber === ''
            || $branch === ''
        ) {

            bankDetailsMessage(
                'Please complete all bank details.',
                'error'
            );
        }


        try {

            $stmt = $pdo->prepare("
                UPDATE bank_accounts

                SET
                    bank_name = :bank_name,
                    account_name = :account_name,
                    account_number = :account_number,
                    branch = :branch

                WHERE id = :id
            ");


            $stmt->execute([

                ':bank_name' =>
                    $bankName,

                ':account_name' =>
                    $accountName,

                ':account_number' =>
                    $accountNumber,

                ':branch' =>
                    $branch,

                ':id' =>
                    $id
            ]);


            if ($stmt->rowCount() === 0) {

                /*
                | It may simply mean the values were unchanged.
                | Check that the record actually exists.
                */

                $check = $pdo->prepare("
                    SELECT id
                    FROM bank_accounts
                    WHERE id = ?
                ");

                $check->execute([$id]);


                if (!$check->fetch()) {

                    bankDetailsMessage(
                        'Bank account was not found.',
                        'error'
                    );
                }
            }


            bankDetailsMessage(
                'Bank account updated successfully.'
            );

        } catch (PDOException $e) {

            bankDetailsMessage(
                'Unable to update bank account.',
                'error'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACTIVATE ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($action === 'activate') {

        $id =
            (int) ($_POST['id'] ?? 0);


        if ($id <= 0) {

            bankDetailsMessage(
                'Invalid bank account.',
                'error'
            );
        }


        try {

            $pdo->beginTransaction();


            /*
            | Only one account is shown to customers.
            */

            $pdo->exec("
                UPDATE bank_accounts
                SET is_active = 0
            ");


            $stmt = $pdo->prepare("
                UPDATE bank_accounts
                SET is_active = 1
                WHERE id = ?
            ");

            $stmt->execute([
                $id
            ]);


            if ($stmt->rowCount() === 0) {

                throw new Exception(
                    'Bank account not found.'
                );
            }


            $pdo->commit();


            bankDetailsMessage(
                'Bank account is now active for customer payments.'
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            bankDetailsMessage(
                'Unable to activate bank account.',
                'error'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id =
            (int) ($_POST['id'] ?? 0);


        if ($id <= 0) {

            bankDetailsMessage(
                'Invalid bank account.',
                'error'
            );
        }


        try {

            /*
            | Check whether this is the active account.
            */

            $stmt = $pdo->prepare("
                SELECT is_active
                FROM bank_accounts
                WHERE id = ?
            ");

            $stmt->execute([
                $id
            ]);

            $account =
                $stmt->fetch();


            if (!$account) {

                bankDetailsMessage(
                    'Bank account was not found.',
                    'error'
                );
            }


            if ((int) $account['is_active'] === 1) {

                /*
                | Do not allow customers to reach a payment page
                | without a valid bank account.
                */

                $countStmt = $pdo->query("
                    SELECT COUNT(*)
                    FROM bank_accounts
                    WHERE is_active = 1
                ");

                $activeCount =
                    (int) $countStmt->fetchColumn();


                if ($activeCount <= 1) {

                    bankDetailsMessage(
                        'You cannot remove the only active bank account. Add another account and activate it first.',
                        'error'
                    );
                }
            }


            $delete = $pdo->prepare("
                DELETE FROM bank_accounts
                WHERE id = ?
            ");

            $delete->execute([
                $id
            ]);


            bankDetailsMessage(
                'Bank account removed successfully.'
            );

        } catch (PDOException $e) {

            bankDetailsMessage(
                'Unable to remove bank account.',
                'error'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UNKNOWN ACTION
    |--------------------------------------------------------------------------
    */

    bankDetailsMessage(
        'Invalid bank account action.',
        'error'
    );
}


/*
|--------------------------------------------------------------------------
| LOAD BANK ACCOUNTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            id,
            bank_name,
            account_name,
            account_number,
            branch,
            is_active,
            created_at,
            updated_at

        FROM bank_accounts

        ORDER BY
            is_active DESC,
            id DESC
    ");


    $bankAccounts =
        $stmt->fetchAll();

} catch (PDOException $e) {

    $bankAccounts = [];

    $bankMessage =
        'Unable to load bank accounts.';

    $bankMessageType =
        'error';
}

?>

<div class="panel-header">

    <div>

        <span class="section-label">
            PAYMENT SETTINGS
        </span>

        <h2>
            Manage Bank Details
        </h2>

        <p>
            Manage the bank account displayed to customers
            when they choose Bank Deposit / Transfer.
        </p>

    </div>

</div>


<?php if ($bankMessage): ?>

    <div class="booking-<?= 
        $bankMessageType === 'success'
            ? 'success'
            : 'error'
    ?>-message">

        <?= htmlspecialchars($bankMessage) ?>

    </div>

<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| ADD BANK ACCOUNT
|--------------------------------------------------------------------------
-->

<div class="dashboard-panel bank-details-form-panel">

    <div class="panel-header">

        <div>

            <span class="section-label">
                ADD
            </span>

            <h3>
                Add Bank Account
            </h3>

        </div>

    </div>


    <form
        method="POST"
        class="bank-details-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="add"
        >


        <div class="form-grid">

            <div class="form-group">

                <label for="bank_name">
                    Bank Name
                </label>

                <input
                    type="text"
                    id="bank_name"
                    name="bank_name"
                    maxlength="150"
                    required
                    placeholder="e.g. Commercial Bank"
                >

            </div>


            <div class="form-group">

                <label for="account_name">
                    Account Name
                </label>

                <input
                    type="text"
                    id="account_name"
                    name="account_name"
                    maxlength="150"
                    required
                    placeholder="e.g. VEYRO Vehicle Service Centre"
                >

            </div>


            <div class="form-group">

                <label for="account_number">
                    Account Number
                </label>

                <input
                    type="text"
                    id="account_number"
                    name="account_number"
                    maxlength="100"
                    required
                    placeholder="Enter account number"
                >

            </div>


            <div class="form-group">

                <label for="branch">
                    Branch
                </label>

                <input
                    type="text"
                    id="branch"
                    name="branch"
                    maxlength="150"
                    required
                    placeholder="e.g. Colombo Main Branch"
                >

            </div>

        </div>


        <button
            type="submit"
            class="primary-button"
        >
            Add Bank Account
        </button>

    </form>

</div>


<!--
|--------------------------------------------------------------------------
| EXISTING ACCOUNTS
|--------------------------------------------------------------------------
-->

<div class="bank-accounts-list">

    <div class="panel-header">

        <div>

            <span class="section-label">
                ACCOUNTS
            </span>

            <h3>
                Bank Accounts
            </h3>

        </div>

    </div>


    <?php if (empty($bankAccounts)): ?>

        <div class="empty-message">

            <h3>
                No Bank Accounts
            </h3>

            <p>
                Add a bank account above to enable bank
                deposit payments.
            </p>

        </div>

    <?php endif; ?>


    <?php foreach ($bankAccounts as $account): ?>

        <div class="bank-account-card">

            <div class="bank-account-header">

                <div>

                    <h3>
                        <?= htmlspecialchars(
                            $account['bank_name']
                        ) ?>
                    </h3>

                    <?php if ((int) $account['is_active'] === 1): ?>

                        <span class="bank-active-badge">
                            ACTIVE FOR PAYMENTS
                        </span>

                    <?php else: ?>

                        <span class="bank-inactive-badge">
                            INACTIVE
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <form
                method="POST"
                class="bank-edit-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="edit"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $account['id'] ?>"
                >


                <div class="form-grid">

                    <div class="form-group">

                        <label>
                            Bank Name
                        </label>

                        <input
                            type="text"
                            name="bank_name"
                            maxlength="150"
                            required
                            value="<?= htmlspecialchars(
                                $account['bank_name']
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Account Name
                        </label>

                        <input
                            type="text"
                            name="account_name"
                            maxlength="150"
                            required
                            value="<?= htmlspecialchars(
                                $account['account_name']
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Account Number
                        </label>

                        <input
                            type="text"
                            name="account_number"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars(
                                $account['account_number']
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Branch
                        </label>

                        <input
                            type="text"
                            name="branch"
                            maxlength="150"
                            required
                            value="<?= htmlspecialchars(
                                $account['branch']
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="bank-account-actions">

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Save Changes
                    </button>

                </div>

            </form>


            <div class="bank-secondary-actions">

                <?php if ((int) $account['is_active'] !== 1): ?>

                    <form
                        method="POST"
                        style="display:inline;"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrfToken) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="activate"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $account['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="secondary-button"
                        >
                            Use for Customer Payments
                        </button>

                    </form>

                <?php endif; ?>


                <form
                    method="POST"
                    style="display:inline;"
                    onsubmit="return confirm(
                        'Are you sure you want to remove this bank account?'
                    );"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $account['id'] ?>"
                    >

                    <button
                        type="submit"
                        class="danger-button"
                    >
                        Remove
                    </button>

                </form>

            </div>

        </div>

    <?php endforeach; ?>

</div>