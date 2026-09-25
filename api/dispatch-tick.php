<?php
/**
 * POST api/dispatch-tick.php
 * Called by the dashboard countdown when it reaches zero: sends the next recipient if the campaign is due.
 */

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

api(function () {
    require_method('POST');
    request_json();
    $user = require_login();
    session_write_close(); // don't block other tabs while SMTP talks
    json_response(['success' => true] + dispatch_tick((int) $user['id']));
});
