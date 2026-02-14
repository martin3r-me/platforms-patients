<?php

return [
    'routing' => [
        'mode' => env('PATIENTS_MODE', 'path'),
        'prefix' => 'patients',
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'patients.dashboard',
        'icon'  => 'heroicon-o-clipboard-document-list',
        'order' => 35,
    ],

    'sidebar' => [
        [
            'group' => 'Patients',
            'dynamic' => [
                'model'     => \Platform\Patients\Models\PatientsPatient::class,
                'team_based' => true,
                'order_by'  => 'name',
                'route'     => 'patients.patients.show',
                'icon'      => 'heroicon-o-clipboard-document-list',
                'label_key' => 'name',
            ],
        ],
    ],
    'billables' => [],
];
