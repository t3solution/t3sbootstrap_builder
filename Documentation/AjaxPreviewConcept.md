# Konzept: AJAX-Preview-Rendering

Status: **Implementiert.** Der „Vorschau"-Button kompiliert serverseitig per AJAX
(`ajax_t3sbootstrap_builder_preview`) und aktualisiert **nur den Komponenten-Preview-Iframe**
— ohne Seitenreload. Aufgeklappte Gruppen, Scroll-Position und Feldzustände bleiben
erhalten. Bei deaktiviertem JS oder fehlgeschlagenem fetch fällt der Button automatisch
auf den normalen Form-Submit (`previewAction` im BuilderModuleController) zurück.

Beteiligte Dateien:
- `Configuration/Backend/AjaxRoutes.php` — Route `t3sbootstrap_builder_preview`
- `Classes/Controller/AjaxController.php` — PSR-15-Handler, gibt JSON `{success, cssUrl}` zurück
- `builder.js` — Preview-Button: fetch → `frame.srcdoc = sampleHtml(cssUrl)` statt Submit
- Publish und Export laufen weiterhin als normale Form-Submits.

---

## Ursprüngliches Konzept (zur Referenz)



## Ziel

Apply soll die Variablen schreiben, serverseitig kompilieren und **nur den
Preview-Iframe** aktualisieren — ohne die ganze Modulseite neu zu laden.

Vorteile: aufgeklappte Gruppen, Scroll-Position, Picker- und Feldzustände bleiben
erhalten; flüssigeres Gefühl; einfacherer Spinner (kein sessionStorage-Flag über
Reload nötig).

## Architektur

### 1. Neue Backend-AJAX-Route

Datei `Configuration/Backend/AjaxRoutes.php`:

```php
<?php
use T3S\BootstrapBuilder\Controller\AjaxController;

return [
    'bootstrap_builder_apply' => [
        'path' => '/bootstrap-builder/apply',
        'target' => AjaxController::class . '::applyAction',
    ],
];
```

Aufruf-URL im Backend per `TYPO3.settings.ajaxUrls['bootstrap_builder_apply']`
(wird von TYPO3 automatisch inkl. Token bereitgestellt, wenn man die JS als
ES6-Modul über die Backend-RequireJS/ImportMap-Infrastruktur lädt). Alternativ die
URL serverseitig ins Template schreiben:

```php
$ajaxUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)
    ->buildUriFromRoute('ajax_bootstrap_builder_apply');
// -> als data-Attribut ins Template geben
```

### 2. Neuer AjaxController (kein Extbase, reiner PSR-15)

```php
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

    public function applyAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $siteIdentifier = (string)($body['siteIdentifier'] ?? '');
        $variables = (array)($body['variables'] ?? []);
        $customScss = (string)($body['customScss'] ?? '');
        $basePreset = (string)($body['basePreset'] ?? '');

        // ... identisch zur jetzigen applyAction:
        //   - allValues = defaults overlaid with posted
        //   - buildCompleteVariablesScss()
        //   - saveTheme()
        //   - bridge->apply()  (schreibt Dateien, kompiliert nach bb-preview-<pid>.css)

        $cssUrl = $this->findPreviewCssUrl($rootPageId); // s. findLatestCompiledCssUrl()

        return new JsonResponse([
            'success' => $cssUrl !== '',
            'cssUrl'  => $cssUrl,
            'message' => $cssUrl !== '' ? 'compiled' : 'no css produced',
        ]);
    }
}
```

Wichtig: Die bestehende `T3sbootstrapBridge::apply()` und der scssphp-Compile
bleiben **unverändert** — sie sind frontend-unabhängig und funktionieren bereits
im Backend-Kontext.

### 3. JavaScript (builder.js)

Apply-Button NICHT mehr submit, sondern:

```js
applyBtn.addEventListener('click', async function (e) {
  e.preventDefault();
  showSpinner();
  const form = document.getElementById('bb-form');
  const fd = new FormData(form);
  // variables[...] kommen aus den Feldern; FormData übernimmt sie automatisch.
  try {
    const res = await fetch(ajaxUrl, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await res.json();
    if (data.success && data.cssUrl) {
      // Iframe neu rendern – KEIN Seitenreload
      const frame = document.getElementById('bb-preview-frame');
      frame.setAttribute('data-css-url', data.cssUrl);
      frame.srcdoc = sampleHtml(absUrl(data.cssUrl));
      // hideSpinner passiert wie gehabt im stylesheet-load-Handler
    } else {
      hideSpinner();
      // Fehlermeldung anzeigen
    }
  } catch (err) {
    hideSpinner();
    // Netzwerkfehler anzeigen
  }
});
```

Der Spinner-Mechanismus vereinfacht sich: `showSpinner()` beim Klick,
`hideSpinner()` beim `load` des `<link>` im neu gerenderten Iframe. Das
sessionStorage-Flag und die ganze "cameFromApply"-Logik entfallen.

## Fallstricke (TYPO3 v14)

1. **AJAX-Route vs. Modul-Route.** Backend-AJAX-Routen leben in
   `Configuration/Backend/AjaxRoutes.php`, NICHT in `Modules.php`. Der
   Route-Identifier bekommt automatisch das Präfix `ajax_`.
2. **CSRF/Token.** Backend-AJAX-Requests brauchen einen gültigen Token. Wenn die
   URL über `TYPO3.settings.ajaxUrls[...]` bezogen wird, ist der Token enthalten.
   Bei selbst gebauter URL via UriBuilder->buildUriFromRoute('ajax_...') ebenfalls.
3. **Kein Extbase-Argument-Mapping.** Der AjaxController ist ein reiner PSR-15
   Handler; Parameter aus `$request->getParsedBody()` lesen, nicht über
   Extbase-Argumente.
4. **JS als Modul laden.** Damit `TYPO3.settings.ajaxUrls` verfügbar ist, JS als
   ES6-Modul über `@import` in der Backend-ImportMap registrieren — oder die
   AJAX-URL einfach serverseitig als `data-ajax-url` ins Template schreiben
   (einfacher, kein ImportMap nötig).
5. **DI für AjaxController.** In `Services.yaml` muss der Controller `public: true`
   bzw. autowire/autoconfigure gesetzt sein, damit er als Route-Target auflösbar
   ist.

## Rückfallpunkt

Der jetzige Form-Submit-Weg bleibt als Fallback erhalten: Wenn JS deaktiviert ist
oder der fetch fehlschlägt, kann der Button weiterhin als normaler Submit
funktionieren (progressive enhancement: `e.preventDefault()` nur, wenn fetch
verfügbar). So geht nie Funktionalität verloren.

## Aufwand

Geschätzt überschaubar (~1–2 Stunden), das Hauptrisiko ist das Backend-AJAX-
Routing + Token. Alles andere (Compile, Dateien schreiben, Iframe rendern) ist
bereits vorhanden und wird nur umverdrahtet.
