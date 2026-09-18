<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Console module command allowlist
|--------------------------------------------------------------------------
|
| Only the commands listed here can be dispatched from the Console UI. The
| browser sends a command key, never a signature, so nothing outside this
| file is reachable.
|
| risk:       read_only | mutating | destructive. Destructive commands also
|             require the run:destructive-console-commands permission and a
|             typed confirmation in the UI.
| pinned:     parameters forced on server-side; the user cannot change them.
| arguments:  positional arguments, keyed by name (no leading dashes).
| options:    switches and options, keyed with their leading dashes.
|
| Param types: boolean, integer, string, date, select, radio, file.
|
*/

return [

    'queue' => env('CONSOLE_QUEUE', 'default'),

    /*
     * Directory the `file` parameter type picks from. Chosen values are resolved
     * back against this path by basename so they cannot escape it.
     */
    'csv_path' => storage_path('app/mode-restore'),

    'groups' => [

        'students' => [
            'label_key' => 'console.group_students',
            'icon' => 'school',
            'commands' => [

                'students-audit-phase-data' => [
                    'signature' => 'students:audit-phase-data',
                    'risk' => 'read_only',
                    'timeout' => 900,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [
                        ['name' => '--limit', 'type' => 'integer', 'default' => 25, 'min' => 1, 'max' => 500],
                    ],
                ],

                'students-auto-confirm-study-position' => [
                    'signature' => 'students:auto-confirm-study-position',
                    'risk' => 'mutating',
                    'timeout' => 1800,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [
                        ['name' => '--dry-run', 'type' => 'boolean', 'default' => true],
                        ['name' => '--chunk', 'type' => 'integer', 'default' => 200, 'min' => 1, 'max' => 5000],
                        ['name' => '--student', 'type' => 'integer', 'min' => 1],
                        ['name' => '--department', 'type' => 'integer', 'min' => 1],
                    ],
                ],

                'students-restore-mode-reassign-bug' => [
                    'signature' => 'students:restore-mode-reassign-bug',
                    'risk' => 'destructive',
                    'timeout' => 1800,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [
                        [
                            'name' => '--mode',
                            'type' => 'radio',
                            'required' => true,
                            'default' => '--dry-run',
                            'options' => ['--dry-run', '--execute'],
                            'expands_to_flag' => true,
                        ],
                        ['name' => '--csv', 'type' => 'file'],
                        ['name' => '--course-modes-csv', 'type' => 'file'],
                    ],
                ],

                'app-fix-o-level-results' => [
                    'signature' => 'app:fix-o-level-results',
                    'risk' => 'destructive',
                    'timeout' => 1800,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [
                        ['name' => 'student_id', 'type' => 'integer', 'min' => 1],
                    ],
                    'options' => [
                        ['name' => '--force', 'type' => 'boolean', 'default' => false],
                    ],
                ],

            ],
        ],

        'offer-letters' => [
            'label_key' => 'console.group_offer_letters',
            'icon' => 'mail',
            'commands' => [

                'app-bulk-process-offer-letters' => [
                    'signature' => 'app:bulk-process-offer-letters-command',
                    'risk' => 'mutating',
                    'timeout' => 900,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [
                        ['name' => 'dateFrom', 'type' => 'date', 'required' => true],
                    ],
                    'options' => [],
                ],

                'app-process-ojet-offer-letters' => [
                    'signature' => 'app:process-ojet-offer-letters-command',
                    'risk' => 'mutating',
                    'timeout' => 900,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

            ],
        ],

        'ledgers' => [
            'label_key' => 'console.group_ledgers',
            'icon' => 'money',
            'commands' => [

                'app-bulk-data-update' => [
                    'signature' => 'app:bulk-data-update',
                    'risk' => 'destructive',
                    'timeout' => 1800,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

                'app-clean-ledger-entries' => [
                    'signature' => 'app:clean-ledger-entries',
                    'risk' => 'destructive',
                    'timeout' => 1800,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

            ],
        ],

        'statements' => [
            'label_key' => 'console.group_statements',
            'icon' => 'landmark',
            'commands' => [

                'statements-plan-fetch-windows' => [
                    'signature' => 'statements:plan-fetch-windows',
                    'risk' => 'mutating',
                    'timeout' => 300,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

                'statements-dispatch-fetch-jobs' => [
                    'signature' => 'statements:dispatch-fetch-jobs',
                    'risk' => 'mutating',
                    'timeout' => 300,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [
                        ['name' => '--limit', 'type' => 'integer', 'min' => 1, 'max' => 1000],
                    ],
                ],

                'statements-get-request' => [
                    'signature' => 'statements:get-request',
                    'risk' => 'destructive',
                    'timeout' => 900,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [
                        [
                            'name' => 'accountType',
                            'type' => 'select',
                            'required' => true,
                            'options' => ['usd', 'zwg', 'income-gen'],
                        ],
                        ['name' => 'startDate', 'type' => 'date', 'required' => true],
                        ['name' => 'endDate', 'type' => 'date', 'required' => true],
                    ],
                    'options' => [],
                ],

            ],
        ],

        'notifications' => [
            'label_key' => 'console.group_notifications',
            'icon' => 'megaphone',
            'commands' => [

                'assessment-calendars-send-missing-marks-notifications' => [
                    'signature' => 'assessment-calendars:send-missing-marks-notifications',
                    'risk' => 'mutating',
                    'timeout' => 900,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

                'hms-expire-unpaid-applications' => [
                    'signature' => 'hms:expire-unpaid-applications',
                    'risk' => 'mutating',
                    'timeout' => 600,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

                'setup-gaps-scan' => [
                    'signature' => 'setup-gaps:scan',
                    'risk' => 'mutating',
                    'timeout' => 900,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [
                        ['name' => '--quiet-notifications', 'type' => 'boolean', 'default' => true],
                    ],
                ],

            ],
        ],

        'maintenance' => [
            'label_key' => 'console.group_maintenance',
            'icon' => 'trash',
            'commands' => [

                'account-purge-archives-flush-expired' => [
                    'signature' => 'account-purge-archives:flush-expired',
                    'risk' => 'destructive',
                    'timeout' => 900,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

                'activitylog-clean' => [
                    'signature' => 'activitylog:clean',
                    'risk' => 'destructive',
                    'timeout' => 1800,
                    'pinned' => ['--no-interaction' => true, '--force' => true],
                    'arguments' => [
                        ['name' => 'log', 'type' => 'string', 'max' => 100],
                    ],
                    'options' => [
                        ['name' => '--days', 'type' => 'integer', 'default' => 365, 'min' => 1, 'max' => 3650],
                    ],
                ],

                'app-fix-data-issues' => [
                    'signature' => 'app:fix-data-issues-command',
                    'risk' => 'mutating',
                    'timeout' => 300,
                    'pinned' => ['--no-interaction' => true],
                    'arguments' => [],
                    'options' => [],
                ],

            ],
        ],

    ],
];
