<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use T3SBS\T3sbootstrapBuilder\Domain\Model\Theme;
use T3SBS\T3sbootstrapBuilder\Domain\Repository\ThemeRepository;
use T3SBS\T3sbootstrapBuilder\Service\BootswatchPresetService;
use T3SBS\T3sbootstrapBuilder\Service\ScssVariableService;
use T3SBS\T3sbootstrapBuilder\Service\T3sbootstrapBridge;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Backend AJAX endpoint that compiles ONLY the preview track (bb-preview-<id>.css) and
 * returns the resulting CSS URL as JSON, so the editor can refresh just the preview iframe
 * without a full page reload. The live frontend is never touched.
 *
 * This mirrors BuilderModuleController::previewAction but as a plain PSR-15 handler.
 */
final class AjaxController
{
    public function __construct(
        private readonly T3sbootstrapBridge $bridge,
        private readonly ScssVariableService $variableService,
        private readonly BootswatchPresetService $presetService,
        private readonly SiteFinder $siteFinder,
        private readonly ThemeRepository $themeRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {}

    public function previewAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array)$request->getParsedBody();
        $siteIdentifier = (string)($body['siteIdentifier'] ?? '');
        $variables = (array)($body['variables'] ?? []);
        $customScss = (string)($body['customScss'] ?? '');
        $basePreset = (string)($body['basePreset'] ?? '');

        if ($siteIdentifier === '') {
            return new JsonResponse(['success' => false, 'message' => 'Missing site identifier.'], 400);
        }

        try {
            $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Unknown site.'], 404);
        }
        $rootPageUid = $site->getRootPageId();

        // Build the complete variables SCSS (defaults overlaid with posted values).
        $allValues = $this->variableService->getFlatDefaults();
        foreach ($variables as $key => $val) {
            $val = trim((string)$val);
            if ($val !== '') {
                $allValues[$key] = $val;
            }
        }
        $bootswatchScss = '';
        if ($basePreset !== '') {
            $preset = $this->presetService->getPreset($basePreset);
            if ($preset !== null) {
                $bootswatchScss = $preset['bootswatch'];
            }
        }
        $variablesScss = $this->variableService->buildCompleteVariablesScss($allValues);
        $combinedCustomScss = trim($bootswatchScss . "\n" . $customScss);

        // Persist the edits so reopening shows them (but do NOT publish to the frontend).
        $this->saveTheme($siteIdentifier, $rootPageUid, $basePreset, $this->variableService->filterOverrides($variables), $customScss);

        $configUid = $this->bridge->findConfigUidForRootPage($rootPageUid);
        if ($configUid === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No t3sbootstrap config record found on the root page.',
            ], 422);
        }

        $errors = $this->bridge->applyPreview($configUid, $variablesScss, $combinedCustomScss);
        if ($errors !== []) {
            return new JsonResponse(['success' => false, 'message' => implode("\n", $errors)], 500);
        }

        $cssUrl = $this->findPreviewCssUrl();

        return new JsonResponse([
            'success' => $cssUrl !== '',
            'cssUrl' => $cssUrl,
            'message' => $cssUrl !== '' ? 'compiled' : 'no css produced',
        ]);
    }

    /**
     * @param array<string, string> $variables overrides only
     */
    private function saveTheme(string $siteIdentifier, int $rootPageUid, string $basePreset, array $variables, string $customScss): void
    {
        $theme = $this->themeRepository->findBySiteIdentifier($siteIdentifier) ?? new Theme();
        $theme->setSiteIdentifier($siteIdentifier);
        $theme->setRootPageUid($rootPageUid);
        $theme->setBasePreset($basePreset);
        $theme->setVariablesJson(json_encode($variables, JSON_THROW_ON_ERROR));
        $theme->setCustomScss($customScss);
        if ($theme->getTitle() === '') {
            $theme->setTitle($siteIdentifier);
        }

        if ($theme->getUid() === null) {
            $theme->setPid(0);
            $this->themeRepository->add($theme);
        } else {
            if ($theme->getPid() !== 0) {
                $theme->setPid(0);
            }
            $this->themeRepository->update($theme);
        }
        $this->persistenceManager->persistAll();
    }

    private function findPreviewCssUrl(): string
    {
        $dir = GeneralUtility::getFileAbsFileName('typo3temp/assets/t3sbootstrap/css/');
        if ($dir === '' || !is_dir($dir)) {
            return '';
        }
        $latest = null;
        foreach (glob(rtrim($dir, '/') . '/bb-preview-*.css') ?: [] as $file) {
            $latest = $file;
        }
        if ($latest === null) {
            $latestMtime = 0;
            foreach (glob(rtrim($dir, '/') . '/*.css') ?: [] as $file) {
                $mtime = (int)@filemtime($file);
                if ($mtime >= $latestMtime) {
                    $latestMtime = $mtime;
                    $latest = $file;
                }
            }
        }
        if ($latest === null) {
            return '';
        }
        $publicPath = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath($latest);
        return $publicPath . (str_contains($publicPath, '?') ? '&' : '?') . 'bbts=' . (int)@filemtime($latest);
    }
}
