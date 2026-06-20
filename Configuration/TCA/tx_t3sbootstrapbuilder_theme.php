<?php
return [
    'ctrl' => [
        'title' => 'Bootstrap Builder Theme',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title ASC',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'hideTable' => true,
        'rootLevel' => -1,
    ],
    'columns' => [
        'title' => ['label' => 'Title', 'config' => ['type' => 'input', 'size' => 40]],
        'site_identifier' => ['label' => 'Site', 'config' => ['type' => 'input']],
        'root_page_uid' => ['label' => 'Root page', 'config' => ['type' => 'number']],
        'base_preset' => ['label' => 'Bootswatch preset', 'config' => ['type' => 'input']],
        'variables_json' => ['label' => 'Variables (JSON)', 'config' => ['type' => 'text']],
        'custom_scss' => ['label' => 'Custom SCSS', 'config' => ['type' => 'text']],
    ],
    'types' => [
        '0' => ['showitem' => 'title, site_identifier, root_page_uid, base_preset, variables_json, custom_scss'],
    ],
];
