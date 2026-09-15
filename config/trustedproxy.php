<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated proxy IPs/CIDRs, or "*" when every request arrives through a
    | trusted load balancer. Leave empty when PHP-FPM is reached directly (e.g. nginx
    | on the same host). Client IPs drive rate limiting, and scheme/host drive signed URLs.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
