<?php
/** Plans & billing actions for signed-in users (Razorpay checkout). */

return [
    // "Choose plan": free plans activate at once; paid plans get a Razorpay order and the Billing page opens Checkout.
    'checkout' => function (array $user): void {
        if (!plans_enabled()) {
            redirect('overview');
        }
        $plan = plan_find((int) input('plan_id'));
        if (!$plan || empty($plan['is_active'])) {
            flash('error', 'This plan is not available right now.');
            redirect('billing');
        }

        if ((int) $plan['price_paise'] === 0) {
            user_activate_plan((int) $user['id'], $plan);
            flash('success', "You are now on the {$plan['name']} plan.");
            redirect('billing');
        }

        try {
            $order = razorpay_create_order($user, $plan);
        } catch (BillingFailure $e) {
            flash('error', $e->getMessage());
            redirect('billing');
        }
        redirect('billing', ['order' => $order['id']]);
    },

    // Razorpay Checkout success handler posts here (see assets/js/billing.js).
    'verify' => function (array $user): void {
        try {
            $payment = razorpay_verify_checkout($user, input('razorpay_order_id'), input('razorpay_payment_id'), input('razorpay_signature'));
        } catch (BillingFailure $e) {
            flash('error', $e->getMessage());
            redirect('billing');
        }
        flash('success', "Payment received — your {$payment['plan_name']} plan is active. Thank you!");
        redirect('billing');
    },
];
