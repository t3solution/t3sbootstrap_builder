<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Builds a downloadable ZIP containing the generated SCSS in a ready-to-compile
 * layout (variables -> bootstrap -> bootswatch -> custom).
 */
final class ScssExportService
{
    public function buildZip(string $variablesScss, string $bootswatchScss, string $customScss, string $themeName): string
    {
        $tmpDir = Environment::getVarPath() . '/transient/';
        GeneralUtility::mkdir_deep($tmpDir);
        $safeName = preg_replace('/[^a-z0-9_-]/i', '-', $themeName);
        $timestamp = date('Y-m-d-His');
        $zipPath = $tmpDir . $safeName . '-' . $timestamp . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create export ZIP.', 1718000000);
        }

        // Two files only:
        //   _variables.scss  -> the builder's pre-bootstrap variables
        //   _custom.scss     -> Bootswatch component overrides (post-bootstrap), followed by
        //                       the user's additional custom SCSS.
        $combinedCustom = rtrim($bootswatchScss);
        $customScss = trim($customScss);
        if ($customScss !== '') {
            $combinedCustom = ($combinedCustom !== '' ? $combinedCustom . "\n\n" : '')
                . "// --- Additional custom SCSS (after Bootstrap) ---\n"
                . $customScss;
        }
        $combinedCustom .= "\n";

        $zip->addFromString('scss/_variables.scss', $variablesScss);
        $zip->addFromString('scss/_custom.scss', $combinedCustom);
        $zip->close();

        return $zipPath;
    }
}
