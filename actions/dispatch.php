<?php
/** Dispatcher actions (was the campaign timer controls in src/App.tsx / src/components/DispatchController.tsx). */

return [
    // "Start 5m Campaign" / "Resume 5m Campaign"
    'start' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        $error = campaign_start($campaign);
        if ($error !== null) {
            flash('error', $error);
        }
        redirect_back('dispatch');
    },

    // "Pause Campaign"
    'pause' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        campaign_pause($campaign);
        redirect_back('dispatch');
    },

    // "Reset All Statuses to Pending" (window.confirm('Reset all recipients back to Pending status so they can be re-sent?'))
    'reset' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        campaign_reset($campaign);
        redirect_back('dispatch');
    },

    // "Send To This User Immediately (Skip Timer)"
    'sendNext' => function (array $user): void {
        $campaign = campaign_load((int) $user['id']);
        $next = first_pending($campaign);
        if ($next) {
            dispatch_recipient($campaign, $next, smtp_load((int) $user['id']));
        }
        redirect_back('dispatch');
    },

    // Interval presets (5 / 2 / 1 min, 30 sec) and the custom-minutes input, both post "minutes".
    // Disabled server-side too while running, matching the TSX buttons' disabled={campaign.status === 'running'}.
    'interval' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        if ($campaign['status'] !== 'running') {
            $minutes = (float) input('minutes');
            if ($minutes > 0) {
                campaign_set_interval($campaign, $minutes);
            }
        }
        redirect_back('dispatch');
    },
];
