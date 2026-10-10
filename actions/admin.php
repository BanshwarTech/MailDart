<?php
/** Admin-only actions: roles, plans, manual plan assignment (index.php refuses this namespace for non-admins). */

return [
    'setRole' => function (array $user): void {
        try {
            $target = user_set_role($user, (int) input('id'), input('role'));
        } catch (AuthFailure $e) {
            flash('error', $e->getMessage());
            redirect_back('users');
        }
        $name = $target['display_name'] ?: $target['email'];
        flash('success', $target['role'] === 'admin' ? "$name is now an admin." : "$name is now a regular user.");
        redirect_back('users');
    },

    // Master switch on Manage Plans
    'setPlansEnabled' => function (array $user): void {
        $on = input_bool('enabled');
        app_setting_set('plans_enabled', $on ? '1' : '0');
        if ($on) {
            db()->exec('UPDATE users SET plan_id = NULL WHERE plan_id IS NOT NULL AND plan_id NOT IN (SELECT id FROM plans)');
            foreach (db()->query('SELECT id FROM users WHERE plan_id IS NULL')->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                user_assign_default_plan((int) $uid); // anyone without a plan starts on the free plan
            }
        }
        flash('success', $on
            ? 'Plans system is ON: plan limits apply, and Plans & Billing and Pricing are now visible.'
            : 'Plans system is OFF: everyone has every feature with no limits, and Plans & Billing and Pricing are hidden.');
        redirect('plans');
    },

    'setUserPlan' => function (array $user): void {
        try {
            user_admin_set_plan((int) input('id'), (int) input('plan_id'));
        } catch (BillingFailure $e) {
            flash('error', $e->getMessage());
            redirect_back('users');
        }
        flash('success', 'Plan updated.');
        redirect_back('users');
    },

    'savePlan' => function (array $user): void {
        $id = (int) input('id');
        try {
            plan_save($id > 0 ? $id : null, [
                'name' => input('name'),
                'description' => input('description'),
                'price' => input('price'),
                'billing_period' => input('billing_period'),
                'features' => input_raw('features'),
                'is_active' => input_bool('is_active'),
                'is_featured' => input_bool('is_featured'),
                'sort_order' => input('sort_order'),
                'max_recipients' => input('max_recipients'),
                'allow_excel' => input_bool('allow_excel'),
                'allow_ai' => input_bool('allow_ai'),
                'allow_export' => input_bool('allow_export'),
                'allow_custom_interval' => input_bool('allow_custom_interval'),
                'allow_test_email' => input_bool('allow_test_email'),
                'test_email_limit' => input('test_email_limit'),
            ]);
        } catch (BillingFailure $e) {
            flash('error', $e->getMessage());
            keep_old_input();
            redirect('plans', $id > 0 ? ['edit' => $id] : []);
        }
        flash('success', $id > 0 ? 'Plan updated.' : 'Plan created.');
        redirect('plans');
    },

    'togglePlan' => function (array $user): void {
        try {
            $plan = plan_set_active((int) input('id'), input_bool('active'));
        } catch (BillingFailure $e) {
            flash('error', $e->getMessage());
            redirect_back('plans');
        }
        flash('success', $plan['is_active'] ? "“{$plan['name']}” is now live on the website." : "“{$plan['name']}” is hidden from the website.");
        redirect_back('plans');
    },

    'deletePlan' => function (array $user): void {
        plan_delete((int) input('id'));
        flash('success', 'Plan deleted. Users who had it keep their access until you change it.');
        redirect('plans');
    },
];
