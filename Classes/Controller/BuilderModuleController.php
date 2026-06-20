<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Controller;

use Psr\Http\Message\ResponseInterface;
use T3SBS\T3sbootstrapBuilder\Definition\BootstrapVariableRegistry;
use T3SBS\T3sbootstrapBuilder\Domain\Model\Theme;
use T3SBS\T3sbootstrapBuilder\Domain\Repository\ThemeRepository;
use T3SBS\T3sbootstrapBuilder\Service\BootswatchPresetService;
use T3SBS\T3sbootstrapBuilder\Service\ScssExportService;
use T3SBS\T3sbootstrapBuilder\Service\ScssVariableService;
use T3SBS\T3sbootstrapBuilder\Service\T3sbootstrapBridge;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BuilderModuleController extends ActionController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly BootstrapVariableRegistry $registry,
        private readonly BootswatchPresetService $presetService,
        private readonly ScssVariableService $variableService,
        private readonly ScssExportService $exportService,
        private readonly T3sbootstrapBridge $bridge,
        private readonly ThemeRepository $themeRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly SiteFinder $siteFinder,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $sites = $this->siteFinder->getAllSites();
        $themes = [];
        foreach ($sites as $site) {
            $themes[$site->getIdentifier()] = $this->themeRepository->findBySiteIdentifier($site->getIdentifier());
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple([
            'sites' => $sites,
            'themes' => $themes,
            'presets' => $this->presetService->listPresets(),
        ]);
        return $moduleTemplate->renderResponse('BuilderModule/Index');
    }

    public function editAction(string $siteIdentifier, string $preset = ''): ResponseInterface
    {
        $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        $theme = $this->themeRepository->findBySiteIdentifier($siteIdentifier);

        // Live preview strategy: instead of loading the frontend (which may share the
        // backend domain on some hosts, e.g. Mittwald, and then load the backend itself),
        // we load t3sbootstrap's freshly COMPILED CSS file directly into a self-contained
        // preview document. This works regardless of the site's frontend URL.
        $compiledCssUrl = $this->findLatestCompiledCssUrl();

        // Effective selected preset: explicit ?preset= wins, else the saved theme's preset.
        $selectedPreset = $preset !== '' ? $preset : ($theme ? $theme->getBasePreset() : '');

        // Pre-fill values: start from each variable's Bootstrap default, then overlay the
        // chosen preset, then the user's saved values. Precedence: user > preset > default.
        // This makes every field visible and ensures Apply can write a complete file.
        $values = [];
        foreach ($this->registry->getFlat() as $key => $def) {
            $values[$key] = (string)$def['default'];
        }
        if ($selectedPreset !== '') {
            $presetData = $this->presetService->getPreset($selectedPreset);
            if ($presetData !== null) {
                $values = array_merge($values, $this->variableService->resolvePresetValues($presetData['variables']));
            }
        }
        if ($theme) {
            $values = array_merge($values, $theme->getVariables());
        }

        // Inject the effective value into each variable definition so the template can use
        // {var.value} directly. Fluid cannot resolve a dynamic array key like {values.{var.key}}.
        $groups = $this->registry->getGroups();
        foreach ($groups as $groupName => $vars) {
            foreach ($vars as $i => $var) {
                $groups[$groupName][$i]['value'] = $values[$var['key']] ?? (string)$var['default'];
                $isColor = ($var['type'] === BootstrapVariableRegistry::TYPE_COLOR);
                // Picker for ALL color fields (incl. Theme colors).
                $groups[$groupName][$i]['picker'] = $isColor;
                // Theme-color reference select only OUTSIDE the "Theme colors" group
                // (those are the base definitions; referencing themselves makes no sense).
                $groups[$groupName][$i]['colorRef'] = ($isColor && $groupName !== 'Theme colors');
            }
        }

        $presetList = $this->presetService->listPresets();
        $presetOptions = array_combine($presetList, $presetList) ?: [];

        // Resolve every preset's values once, so the editor can fill fields CLIENT-SIDE
        // (no page reload -> no routing/double-menu issues). Map: presetName => {key:value}.
        $presetValues = [];
        foreach ($presetList as $name) {
            $pd = $this->presetService->getPreset($name);
            if ($pd !== null) {
                $presetValues[$name] = $this->variableService->resolvePresetValues($pd['variables']);
            }
        }
        $presetValuesJson = json_encode($presetValues, JSON_THROW_ON_ERROR);

        // Frontend URL of the site's root page for the "Frontend" preview tab.
        $frontendUrl = '';
        try {
            $frontendUrl = (string)$site->getRouter()->generateUri(
                $site->getRootPageId()
            );
        } catch (\Throwable $e) {
            $frontendUrl = (string)$site->getBase();
        }

        // AJAX endpoint for live preview compile (no full page reload).
        $ajaxPreviewUrl = (string)GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class)
            ->buildUriFromRoute('ajax_t3sbootstrap_builder_preview');

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple([
            'site' => $site,
            'theme' => $theme,
            'groups' => $groups,
            'values' => $values,
            'presets' => $presetOptions,
            'presetValuesJson' => $presetValuesJson,
            'compiledCssUrl' => $compiledCssUrl,
            'selectedPreset' => $selectedPreset,
            'frontendUrl' => $frontendUrl,
            'ajaxPreviewUrl' => $ajaxPreviewUrl,
        ]);
        return $moduleTemplate->renderResponse('BuilderModule/Edit');
    }

    /**
     * PREVIEW: compile only the backend preview, without touching the live frontend files.
     *
     * @param array<string, string> $variables
     */
    public function previewAction(string $siteIdentifier, array $variables = [], string $customScss = '', string $basePreset = ''): ResponseInterface
    {
        $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        $rootPageUid = $site->getRootPageId();

        [$variablesScss, $combinedCustomScss, $overrides] = $this->buildScssFromPost($variables, $customScss, $basePreset);

        // Remember the edits so re-opening shows them, but do NOT publish to the frontend.
        $this->saveTheme($site->getIdentifier(), $rootPageUid, $basePreset, $overrides, $customScss);

        $configUid = $this->bridge->findConfigUidForRootPage($rootPageUid);
        if ($configUid === null) {
            $this->addFlashMessage(
                $this->trans('flash.config.missing.body'),
                $this->trans('flash.config.missing.title'),
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('edit', null, null, ['siteIdentifier' => $siteIdentifier]);
        }

        $errors = $this->bridge->applyPreview($configUid, $variablesScss, $combinedCustomScss);

        if ($errors !== []) {
            $this->addFlashMessage(implode("\n", $errors), $this->trans('flash.preview.error'), \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);
        } else {
            $this->addFlashMessage($this->trans('flash.preview.updated'), $this->trans('flash.preview.title'));
        }
        return $this->redirect('edit', null, null, ['siteIdentifier' => $siteIdentifier]);
    }

    /**
     * Build the complete variables SCSS, combined custom SCSS and the override map from POST.
     *
     * @param array<string, string> $variables
     * @return array{0:string,1:string,2:array<string,string>}
     */
    private function buildScssFromPost(array $variables, string $customScss, string $basePreset): array
    {
        $allValues = [];
        foreach ($this->variableService->getFlatDefaults() as $key => $default) {
            $allValues[$key] = $default;
        }
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
        $overrides = $this->variableService->filterOverrides($variables);

        return [$variablesScss, $combinedCustomScss, $overrides];
    }

    /**
     * PUBLISH: persist the variable map AND push it into t3sbootstrap config for the frontend.
     *
     * @param array<string, string> $variables
     */
    public function applyAction(string $siteIdentifier, array $variables = [], string $customScss = '', string $basePreset = '', string $intent = 'publish'): ResponseInterface
    {
        // The form posts here for all buttons; the intent decides preview vs. publish vs. export.
        if ($intent === 'preview') {
            return $this->previewAction($siteIdentifier, $variables, $customScss, $basePreset);
        }
        if ($intent === 'export') {
            return $this->exportAction($siteIdentifier, $variables, $customScss, $basePreset);
        }

        $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        $rootPageUid = $site->getRootPageId();

        [$variablesScss, $combinedCustomScss, $overrides] = $this->buildScssFromPost($variables, $customScss, $basePreset);

        $this->saveTheme($site->getIdentifier(), $rootPageUid, $basePreset, $overrides, $customScss);

        $configUid = $this->bridge->findConfigUidForRootPage($rootPageUid);
        if ($configUid === null) {
            $this->addFlashMessage(
                $this->trans('flash.config.missing.body'),
                $this->trans('flash.config.missing.title'),
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('edit', null, null, ['siteIdentifier' => $siteIdentifier]);
        }

        $errors = $this->bridge->apply(
            $configUid,
            $variablesScss,
            $combinedCustomScss
        );

        if ($errors !== []) {
            $this->addFlashMessage(implode("\n", $errors), $this->trans('flash.datahandler.error'), \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);
        } elseif ($this->findLatestCompiledCssUrl() !== '') {
            $this->addFlashMessage($this->trans('flash.published'), $this->trans('flash.published.title'));
        } else {
            $this->addFlashMessage(
                $this->trans('flash.nocss.body'),
                $this->trans('flash.nocss.title'),
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING
            );
        }
        return $this->redirect('edit', null, null, ['siteIdentifier' => $siteIdentifier]);
    }

    public function loadPresetAction(string $siteIdentifier, string $preset): ResponseInterface
    {
        // Loading a preset just pre-fills the editor; user can then tweak & apply.
        return $this->redirect('edit', null, null, [
            'siteIdentifier' => $siteIdentifier,
            'preset' => $preset,
        ]);
    }

    public function exportAction(string $siteIdentifier, ?array $variables = null, ?string $customScss = null, ?string $basePreset = null): ResponseInterface
    {
        // Use the live form values when the export came from the editor form; otherwise fall
        // back to the saved theme (e.g. direct link).
        if ($variables !== null) {
            $allValues = $this->variableService->getFlatDefaults();
            foreach ($variables as $key => $val) {
                $val = trim((string)$val);
                if ($val !== '') {
                    $allValues[$key] = $val;
                }
            }
            $preset = ($basePreset !== null && $basePreset !== '') ? $this->presetService->getPreset($basePreset) : null;
            $bootswatchScss = $preset['bootswatch'] ?? '';
            $variablesScss = $this->variableService->buildCompleteVariablesScss($allValues);
            $customScssOut = (string)$customScss;
            // Persist so the editor stays in sync with what was exported.
            $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
            $overrides = $this->variableService->filterOverrides($variables);
            $this->saveTheme($site->getIdentifier(), $site->getRootPageId(), (string)$basePreset, $overrides, $customScssOut);
        } else {
            $theme = $this->themeRepository->findBySiteIdentifier($siteIdentifier);
            if ($theme === null) {
                $this->addFlashMessage($this->trans('flash.export.empty'), $this->trans('flash.export.empty.title'), \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING);
                return $this->redirect('edit', null, null, ['siteIdentifier' => $siteIdentifier]);
            }
            $bootswatchScss = '';
            if ($theme->getBasePreset() !== '') {
                $preset = $this->presetService->getPreset($theme->getBasePreset());
                if ($preset !== null) {
                    $bootswatchScss = $preset['bootswatch'];
                }
            }
            $allValues = $this->variableService->getFlatDefaults();
            foreach ($theme->getVariables() as $key => $val) {
                $val = trim((string)$val);
                if ($val !== '') {
                    $allValues[$key] = $val;
                }
            }
            $variablesScss = $this->variableService->buildCompleteVariablesScss($allValues);
            $customScssOut = $theme->getCustomScss();
        }

        $zipPath = $this->exportService->buildZip($variablesScss, $bootswatchScss, $customScssOut, $siteIdentifier);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . basename($zipPath) . '"')
            ->withBody($this->streamFactory->createStreamFromFile($zipPath));
    }

    /**
     * @param array<string, string> $variables
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
            // Store on pid 0 (true root level). The record is keyed by site_identifier and
            // is not bound to a page; keeping it off the root page avoids blocking page
            // doktype changes ("records from tables ... not allowed with new doktype").
            $theme->setPid(0);
            $this->themeRepository->add($theme);
        } else {
            // Migrate any legacy record that still sits on a real page down to pid 0.
            if ($theme->getPid() !== 0) {
                $theme->setPid(0);
            }
            $this->themeRepository->update($theme);
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Returns the public URL of the most recently compiled t3sbootstrap CSS file, with a
     * cache-busting query param, or '' if none exists yet. Used for the self-contained
     * preview so we never need a frontend URL.
     */
    private function findLatestCompiledCssUrl(): string
    {
        $dir = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('typo3temp/assets/t3sbootstrap/css/');
        if ($dir === '' || !is_dir($dir)) {
            return '';
        }

        // Prefer the deterministic preview file written by the bridge for the current root page.
        $latest = null;
        foreach (glob(rtrim($dir, '/') . '/bb-preview-*.css') ?: [] as $file) {
            $latest = $file; // there is normally exactly one
        }

        // Fallback: newest .css in the directory (e.g. t3sbootstrap's own compiled file).
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

        $publicPath = \TYPO3\CMS\Core\Core\Environment::getPublicPath();
        $relative = ltrim(str_replace($publicPath, '', $latest), '/');
        return '/' . $relative . '?bbts=' . (int)@filemtime($latest);
    }

    /**
     * Translate a key from this extension's locallang.xlf.
     */
    private function trans(string $key): string
    {
        return (string)(LocalizationUtility::translate(
            'LLL:EXT:t3sbootstrap_builder/Resources/Private/Language/locallang.xlf:' . $key
        ) ?? $key);
    }
}
