<?php

/*
|--------------------------------------------------------------------------
| Management - Monitor Operations and Reports
|--------------------------------------------------------------------------
*/

function renderManagementOperations(
    PDO $pdo,
    int $managementId
): void {
    try {
        $users = (int) $pdo
            ->query("SELECT COUNT(*) FROM users")
            ->fetchColumn();

        $appointments = (int) $pdo
            ->query("SELECT COUNT(*) FROM appointments")
            ->fetchColumn();

        $activeServices = (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM services
                WHERE status = 1
            ")
            ->fetchColumn();

        $revenue = (float) $pdo
            ->query("
                SELECT COALESCE(SUM(amount), 0)
                FROM payments
            ")
            ->fetchColumn();

        $completedServices = (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM service_records AS sr
                LEFT JOIN service_status AS ss
                    ON sr.status_id = ss.status_id
                WHERE LOWER(COALESCE(ss.status_name, '')) = 'completed'
            ")
            ->fetchColumn();

    } catch (PDOException $e) {
        $users = 0;
        $appointments = 0;
        $activeServices = 0;
        $revenue = 0;
        $completedServices = 0;
    }

    ?>

    <div class="stats">

        <div>
            <span>Users</span>
            <b><?= $users ?></b>
        </div>

        <div>
            <span>Appointments</span>
            <b><?= $appointments ?></b>
        </div>

        <div>
            <span>Active Services</span>
            <b><?= $activeServices ?></b>
        </div>

        <div>
            <span>Revenue</span>
            <b>
                Rs. <?= number_format($revenue, 2) ?>
            </b>
        </div>

    </div>

    <section class="card">

        <h2>
            Monitor Operations
        </h2>

        <div class="grid">

            <div class="mini">
                <small>Completed Services</small>
                <h3><?= $completedServices ?></h3>
                <p>
                    Services currently marked as completed.
                </p>
            </div>

            <div class="mini">
                <small>Active Services</small>
                <h3><?= $activeServices ?></h3>
                <p>
                    Services available to customers.
                </p>
            </div>

            <div class="mini">
                <small>Recorded Revenue</small>
                <h3>
                    Rs. <?= number_format($revenue, 2) ?>
                </h3>
                <p>
                    Total amount recorded in payments.
                </p>
            </div>

        </div>

    </section>

    <?php
}

?>
