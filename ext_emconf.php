<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'T3SB Bootstrap Builder',
    'description' => 'Visual Bootstrap 5.3 theme builder with Bootswatch presets and SCSS export. Compiles via EXT:t3sbootstrap.',
    'category' => 'be',
    'author' => 'T3Solution',
    'author_company' => 'T3Solution',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.3.99',
            't3sbootstrap' => '',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
