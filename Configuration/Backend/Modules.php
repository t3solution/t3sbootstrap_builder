<?php
declare(strict_types=1);

use T3SBS\T3sbootstrapBuilder\Controller\BuilderModuleController;

return [

    'web_t3sbootstrapbuilder' => [
        'parent' => 'content',
        'position' => ['after' => 'web_list'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/bootstrap-builder',
        'labels' => 'LLL:EXT:t3sbootstrap_builder/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'T3sbootstrapBuilder',
        'iconIdentifier' => 'module-t3sbootstrap-builder',
        // No page tree: empty navigation component + stop inheriting it from the Web parent.
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false,
        'controllerActions' => [
            BuilderModuleController::class => [
                'index', 'edit', 'apply', 'preview', 'export', 'loadPreset',
            ],
        ],
    ],

];
