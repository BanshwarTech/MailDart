<?php
/** App-wide actions (campaign details bar, Excel banner). Each handler receives the logged-in user row. */

return [
    // Company name / category in the "Campaign details" bar (Template Studio)
    'updateDetails' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        $patch = [];
        if (isset($_POST['companyName'])) {
            $patch['companyName'] = mb_substr(input('companyName'), 0, 255);
        }
        if (isset($_POST['festival']) && preg_match('/^[a-z_]{2,40}$/', input('festival'))) {
            $patch['festival'] = input('festival');
        }
        campaign_update($campaign['id'], $patch);
        redirect_back('editor');
    },

    'dismissToast' => function (array $user): void {
        unset($_SESSION['excel_toast']);
        redirect_back('overview');
    },
];
