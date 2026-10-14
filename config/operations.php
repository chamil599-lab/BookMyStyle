<?php

return [
    // Enable only on the reviewed host after cron/cache/mail verification.
    'featured_cleanup_enabled' => (bool) env('FEATURED_CLEANUP_ENABLED', false),
    'subscription_reminders_enabled' => (bool) env('SUBSCRIPTION_REMINDERS_ENABLED', false),
];
