<?php

/*
|--------------------------------------------------------------------------
| Customer - Service Ratings and Reviews
|--------------------------------------------------------------------------
*/

function customerRatingAction(
    PDO $pdo,
    int $userId,
    array $data,
    string $action
): string {
    if ($action !== "add_rating") {
        return "";
    }

    $serviceRecordId = (int) ($data["service_record_id"] ?? 0);
    $rating = (int) ($data["rating"] ?? 0);
    $comment = trim($data["comment"] ?? "");

    if (
        $serviceRecordId <= 0 ||
        $rating < 1 ||
        $rating > 5
    ) {
        return "Please select a valid rating.";
    }

    /*
     * Make sure the service belongs to this customer.
     */
    $checkQuery = "
        SELECT sr.service_record_id
        FROM service_records AS sr
        INNER JOIN appointments AS a
            ON sr.appointment_id = a.appointment_id
        WHERE sr.service_record_id = ?
          AND a.user_id = ?
    ";

    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([
        $serviceRecordId,
        $userId
    ]);

    if (!$checkStmt->fetchColumn()) {
        return "Invalid service record.";
    }

    /*
     * Prevent duplicate ratings for the same service record.
     */
    $duplicateQuery = "
        SELECT rating_id
        FROM service_ratings
        WHERE user_id = ?
          AND service_record_id = ?
    ";

    $duplicateStmt = $pdo->prepare($duplicateQuery);
    $duplicateStmt->execute([
        $userId,
        $serviceRecordId
    ]);

    if ($duplicateStmt->fetchColumn()) {
        return "You have already rated this service.";
    }

    $insertQuery = "
        INSERT INTO service_ratings
            (user_id, service_record_id, rating, comment)
        VALUES
            (?, ?, ?, ?)
    ";

    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([
        $userId,
        $serviceRecordId,
        $rating,
        $comment
    ]);

    return "Rating submitted successfully.";
}

$GLOBALS["CUSTOMER_ACTION_HANDLERS"][] = "customerRatingAction";

function renderCustomerRatings(PDO $pdo, int $userId): void
{
    try {
        $query = "
            SELECT
                sr.service_record_id,
                a.appointment_date,
                ss.status_name
            FROM service_records AS sr
            INNER JOIN appointments AS a
                ON sr.appointment_id = a.appointment_id
            LEFT JOIN service_status AS ss
                ON sr.status_id = ss.status_id
            WHERE a.user_id = ?
              AND LOWER(COALESCE(ss.status_name, '')) = 'completed'
            ORDER BY sr.service_record_id DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $records = [];
    }

    ?>

    <section class="card">

        <h2>
            Rate a Completed Service
        </h2>

        <?php if ($records): ?>

            <div class="forms">

                <?php foreach ($records as $record): ?>

                    <form
                        method="post"
                        class="inline-form"
                    >

                        <input
                            type="hidden"
                            name="dashboard_action"
                            value="add_rating"
                        >

                        <input
                            type="hidden"
                            name="service_record_id"
                            value="<?= (int) $record["service_record_id"] ?>"
                        >

                        <b>
                            Service #<?= (int) $record["service_record_id"] ?>
                        </b>

                        <select
                            name="rating"
                            required
                        >
                            <option value="">
                                Rating
                            </option>
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select>

                        <input
                            type="text"
                            name="comment"
                            placeholder="Feedback"
                        >

                        <button
                            class="button"
                            type="submit"
                        >
                            Submit
                        </button>

                    </form>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                Complete a service to submit a rating.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
