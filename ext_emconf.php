<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "t3sbootstrap_builder".
 *
 * Auto generated 29-06-2026 13:55
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
     'title' => 'T3SB Bootstrap Builder',
     'description' => 'Visual Bootstrap 5.3 theme builder with Bootswatch presets and SCSS export. Compiles via EXT:t3sbootstrap.',
     'category' => 'be',
     'version' => '1.0.2',
     'state' => 'stable',
     'author' => 'Helmut Hackbarth',
     'author_email' => 'typo3@t3solution.de',
     'author_company' => 'T3Solution',
     'constraints' => [
         'depends' => [
             'typo3' => '14.3.0-14.3.99',
             't3sbootstrap' => '5.3.50-5.3.99',
         ],
         'conflicts' => [],
         'suggests' => [],
     ],
     'autoload' => [
         'psr-4' => [
             'T3SBS\\T3sBootstrapBuilder\\' => 'Classes/',
         ],
     ],
 ];