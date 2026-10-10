<?php
/**
 * Razorpay webhook (POST <APP_URL>api/razorpay-webhook).
 * Razorpay Dashboard -> Settings -> Webhooks: add this URL, set a secret (= RAZORPAY_WEBHOOK_SECRET in .env)
 * and tick the events payment.captured and order.paid. Activates the plan even if the buyer closed the browser.
 */

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

api(function (): void {
    require_method('POST');
    try {
        razorpay_handle_webhook((string) file_get_contents('php://input'), (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? ''));
    } catch (BillingFailure $e) {
        json_error($e->getMessage(), 400);
    }
    json_response(['success' => true]);
});
