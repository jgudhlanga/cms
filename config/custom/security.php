<?php

return [
    // Sends Content-Security-Policy-Report-Only so violations show in the browser console before a policy is enforced.
    'csp_report_only' => (bool) env('CSP_REPORT_ONLY', false),

    'csp_policy' => env('CSP_POLICY', implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
        "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net",
        "img-src 'self' data: blob:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "object-src 'none'",
    ])),
];
