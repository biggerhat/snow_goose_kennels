<?php

return [
    'phone' => env('KENNEL_PHONE'),
    'email' => env('KENNEL_EMAIL'),
    'location' => env('KENNEL_LOCATION'),
    'facebook' => env('KENNEL_FACEBOOK'),

    // Where files live. 'public' and 'local' keep them on this server; 'b2_photos' and 'b2_documents' use Backblaze.
    'photo_disk' => env('PHOTO_DISK', 'public'),
    'document_disk' => env('DOCUMENT_DISK', 'local'),
];
