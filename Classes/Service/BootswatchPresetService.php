<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads pre-cached Bootswatch presets from the repository.
 *
 * Each preset lives in Resources/Public/Presets/{name}/ as:
 *   _variables.scss   (imported BEFORE bootstrap -> custom_variables_scss)
 *   _bootswatch.scss  (imported AFTER bootstrap  -> custom_scss)
 *
 * Source: https://github.com/thomaspark/bootswatch (MIT). Re-cache via the
 * shipped pre-cached presets; do NOT live-fetch at runtime.
 */
final class BootswatchPresetService
{
    public function __construct(
        private readonly string $presetPath = 'EXT:t3sbootstrap_builder/Resources/Public/Presets/',
    ) {}

    /**
     * @return array<int, string> available preset names
     */
    public function listPresets(): array
    {
        $base = GeneralUtility::getFileAbsFileName($this->presetPath);
        if (!is_dir($base)) {
            return [];
        }
        $names = [];
        foreach (scandir($base) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($base . $entry) && is_file($base . $entry . '/_variables.scss')) {
                $names[] = $entry;
            }
        }
        sort($names);
        return $names;
    }

    /**
     * @return array{variables: string, bootswatch: string}|null
     */
    public function getPreset(string $name): ?array
    {
        $safe = preg_replace('/[^a-z0-9_-]/i', '', $name);
        if ($safe === '' || $safe !== $name) {
            return null;
        }
        $dir = GeneralUtility::getFileAbsFileName($this->presetPath) . $safe . '/';
        $varsFile = $dir . '_variables.scss';
        if (!is_file($varsFile)) {
            return null;
        }
        $bsFile = $dir . '_bootswatch.scss';
        return [
            'variables' => (string)file_get_contents($varsFile),
            'bootswatch' => is_file($bsFile) ? (string)file_get_contents($bsFile) : '',
        ];
    }
}
