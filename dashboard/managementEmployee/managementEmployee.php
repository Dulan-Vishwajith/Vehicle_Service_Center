<?php

/*
|--------------------------------------------------------------------------
| Management Dashboard
|--------------------------------------------------------------------------
| Every management feature is loaded from the functions directory.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/functions/operations.php";
require_once __DIR__ . "/functions/feedback.php";
require_once __DIR__ . "/functions/assistant_ratings.php";
require_once __DIR__ . "/functions/offers.php";
require_once __DIR__ . "/functions/users.php";
require_once __DIR__ . "/functions/services_packages.php";
require_once __DIR__ . "/functions/branches.php";
require_once __DIR__ . "/functions/payment_options.php";
require_once __DIR__ . "/functions/warranty_policies.php";

function managementHandleAction(
    PDO $pdo,
    int $managementId,
    array $data
): string {
    $action = $data["dashboard_action"] ?? "";

    foreach ($GLOBALS["MANAGEMENT_ACTION_HANDLERS"] ?? [] as $handler) {
        $result = $handler($pdo, $managementId, $data, $action);

        if ($result !== "") {
            return $result;
        }
    }

    return "";
}

function renderManagement(
    PDO $pdo,
    int $managementId
): void {
    renderManagementOperations($pdo, $managementId);
    renderManagementFeedback($pdo, $managementId);
    renderAssistantRatings($pdo, $managementId);
    renderManagementOffers($pdo, $managementId);
    renderManagementUsers($pdo, $managementId);
    renderManagementServicesAndPackages($pdo, $managementId);
    renderManagementBranches($pdo, $managementId);
    renderManagementPaymentOptions($pdo, $managementId);
    renderManagementWarrantyPolicies($pdo, $managementId);
}

?>
