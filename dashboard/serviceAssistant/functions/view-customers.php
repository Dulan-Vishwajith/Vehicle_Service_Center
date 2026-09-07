<?php


$customers = [];


try {


    $stmt = $pdo->prepare("

        SELECT

            u.user_id,

            u.name,

            u.phone,

            u.email,

            COUNT(
                DISTINCT b.id
            ) AS total_bookings

        FROM users u

        INNER JOIN bookings b
            ON b.user_id = u.user_id

        WHERE b.assigned_assistant_id = ?

        GROUP BY

            u.user_id,
            u.name,
            u.phone,
            u.email

        ORDER BY u.name ASC

    ");


    $stmt->execute([

        $assistantId

    ]);


    $customers =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {


    $customers = [];


}

?>


<div class="panel-header">

    <div>

        <span class="section-label">

            CUSTOMERS

        </span>

        <h2>

            My Customers

        </h2>

    </div>

</div>


<?php if (empty($customers)): ?>


    <div class="empty-message">


        <h3>

            No Customers Found

        </h3>


        <p>

            You currently have no assigned
            customer appointments.

        </p>


    </div>


<?php else: ?>


    <div class="my-bookings-list">


        <?php foreach ($customers as $customer): ?>


            <div class="booking-card">


                <h3>

                    <?= htmlspecialchars(
                        $customer['name']
                    ) ?>

                </h3>


                <p>

                    <strong>Phone:</strong>

                    <?= htmlspecialchars(
                        $customer['phone']
                    ) ?>

                </p>


                <p>

                    <strong>Email:</strong>

                    <?= htmlspecialchars(
                        $customer['email']
                    ) ?>

                </p>


                <p>

                    <strong>Total Bookings:</strong>

                    <?= (int)
                        $customer['total_bookings'] ?>

                </p>


            </div>


        <?php endforeach; ?>


    </div>


<?php endif; ?>