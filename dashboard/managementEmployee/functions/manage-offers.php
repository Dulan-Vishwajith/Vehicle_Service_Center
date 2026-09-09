<?php

$message = $_GET['message'] ?? '';
$type = $message ? 'success' : '';

/*
|--------------------------------------------------------------------------
| Handle Offer Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_action'])) {

    $id = (int) ($_POST['offer_id'] ?? 0);
    $action = $_POST['offer_action'];

    if ($id > 0) {

        try {

            /*
            |--------------------------------------------------------------------------
            | Activate / Deactivate Offer
            |--------------------------------------------------------------------------
            */

            if ($action === 'toggle_status') {

                $stmt = $pdo->prepare(
                    "UPDATE offers
                     SET status = IF(status = 1, 0, 1)
                     WHERE id = ?"
                );

                $stmt->execute([$id]);

                $message = 'Offer status updated.';
                $type = 'success';
            }

            /*
            |--------------------------------------------------------------------------
            | Delete Offer
            |--------------------------------------------------------------------------
            */

            elseif ($action === 'delete') {

                $stmt = $pdo->prepare(
                    "DELETE FROM offers WHERE id = ?"
                );

                $stmt->execute([$id]);

                $message = 'Offer deleted.';
                $type = 'success';
            }

        } catch (PDOException $e) {

            $message = 'Unable to update the offer.';
            $type = 'error';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Load All Offers
|--------------------------------------------------------------------------
*/

$offers = [];

try {

    $stmt = $pdo->query(
        "SELECT *
         FROM offers
         ORDER BY created_at DESC, id DESC"
    );

    $offers = $stmt->fetchAll();

} catch (PDOException $e) {

    $message = 'Unable to load offers.';
    $type = 'error';
}

?>


<!-- ========================================================
     PAGE HEADER
======================================================== -->

<div class="panel-header">

    <h2>Manage Offers</h2>

    <a
        class="btn btn-primary"
        href="?page=offer-form"
    >
        Create Offer
    </a>

</div>


<!-- ========================================================
     SUCCESS / ERROR MESSAGE
======================================================== -->

<?php if ($message): ?>

    <div class="message <?= htmlspecialchars($type) ?>">

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<!-- ========================================================
     OFFERS TABLE
======================================================== -->

<?php if (empty($offers)): ?>

    <div class="empty-message">

        <p>No offers have been created yet.</p>

    </div>

<?php else: ?>

    <div class="ops-table offers-table">


        <!-- TABLE HEADER -->

        <div class="ops-table-heading">

            <span class="offer-col-title">
                Offer
            </span>

            <span class="offer-col-discount">
                Discount
            </span>

            <span class="offer-col-valid">
                Valid Text
            </span>

            <span class="offer-col-status">
                Status
            </span>

            <span class="offer-col-actions">
                Actions
            </span>

        </div>


        <!-- TABLE ROWS -->

        <?php foreach ($offers as $o): ?>

            <div class="ops-table-row">


                <!-- OFFER -->

                <span class="offer-col-title">

                    <strong>
                        <?= htmlspecialchars($o['title']) ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars($o['offer_type']) ?>
                    </small>

                </span>


                <!-- DISCOUNT -->

                <span class="offer-col-discount">

                    <?= htmlspecialchars($o['discount']) ?>

                </span>


                <!-- VALID TEXT -->

                <span class="offer-col-valid">

                    <?= htmlspecialchars($o['valid_text']) ?>

                </span>


                <!-- STATUS -->

                <span class="offer-col-status">

                    <span class="status <?= $o['status'] ? 'status-completed' : 'status-cancelled' ?>">

                        <?= $o['status'] ? 'Active' : 'Inactive' ?>

                    </span>

                </span>


                <!-- ACTIONS -->

                <span class="offer-col-actions">


                    <!-- EDIT -->

                    <a
                        href="?page=offer-form&id=<?= (int) $o['id'] ?>"
                        class="admin-action-link"
                    >
                        Edit
                    </a>


                    <!-- ACTIVATE / DEACTIVATE -->

                    <form
                        method="post"
                        class="admin-inline-form"
                    >

                        <input
                            type="hidden"
                            name="offer_id"
                            value="<?= (int) $o['id'] ?>"
                        >

                        <button
                            type="submit"
                            name="offer_action"
                            value="toggle_status"
                            class="admin-action-link"
                        >

                            <?= $o['status'] ? 'Deactivate' : 'Activate' ?>

                        </button>

                    </form>


                    <!-- DELETE -->

                    <form
                        method="post"
                        class="admin-inline-form"
                        onsubmit="return confirm('Delete this offer?')"
                    >

                        <input
                            type="hidden"
                            name="offer_id"
                            value="<?= (int) $o['id'] ?>"
                        >

                        <button
                            type="submit"
                            name="offer_action"
                            value="delete"
                            class="admin-action-link admin-action-danger"
                        >
                            Delete
                        </button>

                    </form>


                </span>


            </div>

        <?php endforeach; ?>


    </div>

<?php endif; ?>