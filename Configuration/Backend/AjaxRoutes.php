<?php

use T3SBS\T3sbootstrapBuilder\Controller\AjaxController;

return [
    't3sbootstrap_builder_preview' => [
        'path' => '/t3sbootstrap-builder/preview',
        'target' => AjaxController::class . '::previewAction',
    ],
];
