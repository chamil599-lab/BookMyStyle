<?php

return [
    'enabled' => env('PUSH_ENABLED', false),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'reminders_enabled' => env('BOOKING_REMINDERS_ENABLED', false),
];
