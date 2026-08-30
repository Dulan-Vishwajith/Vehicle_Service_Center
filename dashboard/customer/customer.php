<?php

/*
|--------------------------------------------------------------------------
| Customer Dashboard
|--------------------------------------------------------------------------
| This file loads every customer feature from the functions directory.
| To add a new customer feature, create a PHP file inside functions/
| and include it here.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/functions/appointments.php";
require_once __DIR__ . "/functions/tracking.php";
require_once __DIR__ . "/functions/ratings.php";
require_once __DIR__ . "/functions/warranty.php";
require_once __DIR__ . "/functions/offers.php";

/*
|--------------------------------------------------------------------------
| Customer Action Dispatcher
|--------------------------------------------------------------------------
*/

function customerHandleAction(PDO $pdo, int $userId, array $data): string
{
    $action = $data["dashboard_action"] ?? "";

    foreach ($GLOBALS["CUSTOMER_ACTION_HANDLERS"] ?? [] as $handler) {
        $result = $handler($pdo, $userId, $data, $action);

        if ($result !== "") {
            return $result;
        }
    }

    return "";
}

/*
|--------------------------------------------------------------------------
| Customer Dashboard Renderer
|--------------------------------------------------------------------------
*/

function renderCustomer(PDO $pdo, int $userId): void
{
    renderCustomerAppointments($pdo, $userId);
    renderCustomerTracking($pdo, $userId);
    renderCustomerWarranty($pdo, $userId);
    renderCustomerOffers($pdo, $userId);
    renderCustomerRatings($pdo, $userId);
}

?>
