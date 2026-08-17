<?php

declare(strict_types=1);

return [
    'serial_prefix' => env('ID_CARD_SERIAL_PREFIX', 'HPC'),
    'reissue_fee' => (float) env('ID_CARD_REISSUE_FEE', env('AUTO_CARD_FEE', 45.00)),
    'website' => env('ID_CARD_WEBSITE', 'www.hrepoly.ac.zw'),
    'return' => [
        'name' => env('ID_CARD_RETURN_NAME', 'Harare Polytechnic'),
        'address' => env('ID_CARD_RETURN_ADDRESS', 'P.O. Box CY 407, Causeway, Harare'),
        'phone' => env('ID_CARD_RETURN_PHONE', '0867 700 0343'),
    ],
    'printer' => [
        'driver' => env('ID_CARD_PRINTER_DRIVER', 'pdf'),
    ],
];
