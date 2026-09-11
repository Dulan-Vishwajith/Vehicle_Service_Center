<?php

/*
|--------------------------------------------------------------------------
| LEGACY SELF-ASSIGN ENDPOINT
|--------------------------------------------------------------------------
| Service assistants no longer confirm or assign bookings. Management
| performs both steps. Keep this endpoint as a safe redirect so an old
| bookmark cannot bypass the new workflow.
|--------------------------------------------------------------------------
*/

session_start();

header('Location: ../../dashboard.php?page=appointments&error=unauthorized');
exit;
