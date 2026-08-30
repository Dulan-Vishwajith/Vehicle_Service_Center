<?php

/*
|--------------------------------------------------------------------------
| Sales Assistant Dashboard
|--------------------------------------------------------------------------
| All Service Assistant features are loaded from the functions directory.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/functions/appointments.php";
require_once __DIR__ . "/functions/service_stages.php";
require_once __DIR__ . "/functions/service_records.php";
require_once __DIR__ . "/functions/replaced_parts.php";

function salesAssistantHandleAction(
    PDO $pdo,
    int $assistantId,
    array $data
): string {
    $action = $data["dashboard_action"] ?? "";

    foreach ($GLOBALS["SALES_ASSISTANT_ACTION_HANDLERS"] ?? [] as $handler) {
        $result = $handler($pdo, $assistantId, $data, $action);

        if ($result !== "") {
            return $result;
        }
    }

    return "";
}

function renderSalesAssistant(
    PDO $pdo,
    int $assistantId
): void {
    renderAssistantAppointments($pdo, $assistantId);
    renderServiceStages($pdo, $assistantId);
    renderServiceRecords($pdo, $assistantId);
    renderReplacedParts($pdo, $assistantId);
}

?>
