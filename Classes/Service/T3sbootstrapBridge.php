<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Doctrine\DBAL\ParameterType;

/**
 * Bridges the builder to EXT:t3sbootstrap.
 *
 * Strategy (verified against t3sbootstrap v14):
 *   - We write into tx_t3sbootstrap_domain_model_config.custom_variables_scss
 *     and .custom_scss of the root config record via DataHandler.
 *   - t3sbootstrap's OutsourcedFiles hook then writes
 *     custom-variables-{uid}.scss / custom-{uid}.scss into
 *     EXT:t3sb_package/Resources/Public/T3SB-SCSS/ and rebuilds the include file.
 *   - The actual SCSS compile happens lazily via t3sbootstrap CompileService -> ScssParser.
 *
 * We therefore never call ScssParser directly; we just persist and let
 * t3sbootstrap own the build. This keeps us decoupled and upgrade-safe.
 */
final class T3sbootstrapBridge
{
    private const CONFIG_TABLE = 'tx_t3sbootstrap_domain_model_config';

    /** Same temp dir t3sbootstrap CompileService writes to. */
    private const CSS_TEMP_DIR = 'typo3temp/assets/t3sbootstrap/css/';

    /** Where t3sbootstrap keeps the per-root custom SCSS files it imports when compiling. */
    private const VARIABLES_DIR = 'EXT:t3sb_package/Resources/Public/T3SB-SCSS/';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Find the root config record uid for a given root page.
     */
    public function findConfigUidForRootPage(int $rootPageUid): ?int
    {
        $qb = $this->connectionPool->getQueryBuilderForTable(self::CONFIG_TABLE);
        $qb->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $qb->select('uid')
            ->from(self::CONFIG_TABLE)
            ->where($qb->expr()->eq('pid', $qb->createNamedParameter($rootPageUid, ParameterType::INTEGER)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        return $row ? (int)$row['uid'] : null;
    }

    /**
     * Persist generated SCSS into the t3sbootstrap config record.
     * Returns the BE error list (empty = success).
     *
     * @return array<int, string>
     */
    public function apply(int $configUid, string $variablesScss, string $customScss): array
    {
        $data = [
            self::CONFIG_TABLE => [
                $configUid => [
                    'custom_variables_scss' => $variablesScss,
                    'custom_scss' => $customScss,
                ],
            ],
        ];

        // In a BE module context $GLOBALS['BE_USER'] is the acting user.
        // We keep the config record in sync (so T3SB Config shows the same SCSS)...
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, [], $GLOBALS['BE_USER']);
        $dataHandler->process_datamap();

        // ...but we ALSO write the two files t3sbootstrap actually imports when compiling,
        // overwriting them directly (no merge) with a timestamped backup of the previous
        // version. This is the authoritative source for the compile.
        $rootPageId = $this->getRootPageId($configUid);
        if ($rootPageId !== null) {
            $this->writeCustomFile('custom-variables-' . $rootPageId . '.scss', $variablesScss);
            $this->writeCustomFile('custom-' . $rootPageId . '.scss', $customScss);
        }

        // Purge stale CSS, then compile right away so the backend preview can show it
        // without waiting for a frontend request.
        $this->purgeCompiledCss();
        $this->compileNow($configUid);

        return $dataHandler->errorLog;
    }

    /**
     * PREVIEW ONLY: writes a SEPARATE variables/custom/include track and compiles just the
     * backend preview CSS (bb-preview-<id>.css). The live frontend files
     * (custom-variables-<id>.scss, custom-<id>.scss) and the t3sbootstrap config record are
     * NOT touched, so the frontend is unaffected.
     *
     * @return array<int, string> always empty (kept for signature symmetry with apply())
     */
    public function applyPreview(int $configUid, string $variablesScss, string $customScss): array
    {
        $rootPageId = $this->getRootPageId($configUid);
        if ($rootPageId === null) {
            return ['Could not resolve root page for preview.'];
        }

        $dirAbs = GeneralUtility::getFileAbsFileName(self::VARIABLES_DIR);
        if ($dirAbs === '') {
            return ['Could not resolve T3SB-SCSS directory.'];
        }
        GeneralUtility::mkdir_deep($dirAbs);

        // Separate preview-only source files (no backup needed; they are throwaway).
        $previewVars = rtrim($dirAbs, '/') . '/custom-variables-preview-' . $rootPageId . '.scss';
        $previewCustom = rtrim($dirAbs, '/') . '/custom-preview-' . $rootPageId . '.scss';
        GeneralUtility::writeFile($previewVars, $variablesScss);
        GeneralUtility::writeFile($previewCustom, $customScss);

        // Separate include (compile entry point) importing the preview source files.
        $bootstrapImport = 'EXT:t3sb_package/Resources/Public/T3SB-Bootstrap/Bootstrap/scss/bootstrap';
        if (!file_exists(GeneralUtility::getFileAbsFileName($bootstrapImport . '.scss'))) {
            return ['Bootstrap SCSS sources not found; cannot compile preview.'];
        }
        $includeDir = self::VARIABLES_DIR . 'Bootstrap/';
        $includeAbs = GeneralUtility::getFileAbsFileName($includeDir . 'bootstrap-preview-' . $rootPageId . '.scss');
        $includeContent = "\n"
            . '@import "' . self::VARIABLES_DIR . 'custom-variables-preview-' . $rootPageId . '";' . "\n"
            . '@import "' . $bootstrapImport . '";' . "\n"
            . '@import "' . self::VARIABLES_DIR . 'custom-preview-' . $rootPageId . '";' . "\n";
        GeneralUtility::mkdir_deep(GeneralUtility::getFileAbsFileName($includeDir));
        GeneralUtility::writeFile($includeAbs, $includeContent);

        // Compile ONLY the preview CSS via scssphp. Do not call t3sbootstrap's CompileService
        // and do not purge the frontend's compiled CSS.
        $outFile = GeneralUtility::getFileAbsFileName(self::CSS_TEMP_DIR) . 'bb-preview-' . $rootPageId . '.css';
        $this->compileWithScssphp($includeAbs, $outFile);

        return [];
    }

    /**
     * Overwrites one of t3sbootstrap's custom SCSS files, backing up the previous version
     * as "_{timestamp}-{name}" (same scheme t3sbootstrap uses, so its own cleanup keeps
     * only the most recent backups).
     */
    private function writeCustomFile(string $fileName, string $content): void
    {
        $dirAbs = GeneralUtility::getFileAbsFileName(self::VARIABLES_DIR);
        if ($dirAbs === '') {
            return;
        }
        GeneralUtility::mkdir_deep($dirAbs);
        $target = rtrim($dirAbs, '/') . '/' . $fileName;

        if (is_file($target)) {
            $backup = rtrim($dirAbs, '/') . '/_' . time() . '-' . $fileName;
            @copy($target, $backup);
        }
        GeneralUtility::writeFile($target, $content);
    }

    private function getRootPageId(int $configUid): ?int
    {
        $qb = $this->connectionPool->getQueryBuilderForTable(self::CONFIG_TABLE);
        $qb->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $qb->select('pid')
            ->from(self::CONFIG_TABLE)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($configUid, ParameterType::INTEGER)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        return $row ? (int)$row['pid'] : null;
    }

    /**
     * Triggers t3sbootstrap's CompileService directly for the given root page's include
     * SCSS, producing the compiled CSS immediately (t3sbootstrap itself only compiles on
     * frontend requests, which a backend module never makes).
     */
    private function compileNow(int $configUid): void
    {
        $rootPageId = $this->getRootPageId($configUid);
        if ($rootPageId === null) {
            return;
        }

        $varsDir = self::VARIABLES_DIR;
        $includeDir = $varsDir . 'Bootstrap/';
        $includeScss = $includeDir . 'bootstrap-' . $rootPageId . '.scss';
        $includeAbs = GeneralUtility::getFileAbsFileName($includeScss);

        // The include file (compile entry point) is normally created by t3sbootstrap's
        // CLI command, which a backend save never runs. Create it ourselves if missing,
        // mirroring the command's import order: custom-variables -> bootstrap -> custom.
        if (!file_exists($includeAbs)) {
            $bootstrapImport = 'EXT:t3sb_package/Resources/Public/T3SB-Bootstrap/Bootstrap/scss/bootstrap';
            if (!file_exists(GeneralUtility::getFileAbsFileName($bootstrapImport . '.scss'))) {
                // Bootstrap sources not present -> cannot compile here; leave to frontend.
                return;
            }
            $includeContent = "\n"
                . '@import "' . $varsDir . 'custom-variables-' . $rootPageId . '";' . "\n"
                . '@import "' . $bootstrapImport . '";' . "\n"
                . '@import "' . $varsDir . 'custom-' . $rootPageId . '";' . "\n";
            GeneralUtility::mkdir_deep(GeneralUtility::getFileAbsFileName($includeDir));
            GeneralUtility::writeFile($includeAbs, $includeContent);
        }

        $serviceClass = 'T3SBS\\T3sbootstrap\\Service\\CompileService';
        if (class_exists($serviceClass)) {
            try {
                $compileService = GeneralUtility::makeInstance($serviceClass);
                $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
                $compileService->getCompiledFile($request, $includeScss);
            } catch (\Throwable $e) {
                // ignore; we always also build our own deterministic preview file below
            }
        }

        // ALWAYS build our own preview CSS with a FIXED filename, so the backend preview
        // loads a known file instead of guessing "newest in dir". This is the authoritative
        // file for the module preview.
        $outFile = GeneralUtility::getFileAbsFileName(self::CSS_TEMP_DIR) . 'bb-preview-' . $rootPageId . '.css';
        $this->compileWithScssphp($includeAbs, $outFile);
    }

    private function compiledCssExists(): bool
    {
        $dir = GeneralUtility::getFileAbsFileName(self::CSS_TEMP_DIR);
        if ($dir === '' || !is_dir($dir)) {
            return false;
        }
        return (glob(rtrim($dir, '/') . '/*.css') ?: []) !== [];
    }

    private function compileWithScssphp(string $includeAbs, string $outFileAbs): void
    {
        // Ensure scssphp is loaded (t3sbootstrap ships it under Contrib).
        if (!class_exists('ScssPhp\\ScssPhp\\Compiler')) {
            $inc = GeneralUtility::getFileAbsFileName('EXT:t3sbootstrap/Contrib/scssphp/scss.inc.php');
            if ($inc !== '' && is_file($inc)) {
                require_once $inc;
            }
        }
        if (!class_exists('ScssPhp\\ScssPhp\\Compiler')) {
            return;
        }

        try {
            $compiler = new \ScssPhp\ScssPhp\Compiler();
            $compiler->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);
            // Resolve EXT: imports and relative paths the way t3sbootstrap does.
            $compiler->addImportPath(\TYPO3\CMS\Core\Core\Environment::getExtensionsPath());
            $compiler->addImportPath(dirname($includeAbs));
            $compiler->addImportPath(static function ($url): ?string {
                $full = GeneralUtility::getFileAbsFileName($url);
                if ($full === '') {
                    return null;
                }
                if (is_file($full . '.scss')) {
                    return $full . '.scss';
                }
                if (preg_match('/\.s?css$/', $url) && is_file($full)) {
                    return $full;
                }
                return null;
            });

            $result = $compiler->compileString('@import "' . $includeAbs . '"');
            $css = $result->getCss();

            GeneralUtility::mkdir_deep(dirname($outFileAbs));
            GeneralUtility::writeFile($outFileAbs, $css);
            @touch($outFileAbs);
        } catch (\Throwable $e) {
            // Leave to frontend compile; preview will show the hint.
        }
    }

    /**
     * Deletes compiled .css/.css.meta files from t3sbootstrap's temp directory,
     * forcing a clean recompile on the next frontend request.
     */
    private function purgeCompiledCss(): void
    {
        $dir = GeneralUtility::getFileAbsFileName(self::CSS_TEMP_DIR);
        if ($dir === '' || !is_dir($dir)) {
            return;
        }
        foreach (glob(rtrim($dir, '/') . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
