# T3SB Builder

![TYPO3](https://img.shields.io/badge/TYPO3-14-FF8700?logo=typo3&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/t3sbootstrap)

> **Visual Bootstrap 5.3 theme builder for TYPO3 v14.** Design Bootstrap themes *per site*
> in the backend — start from a Bootswatch preset, fine-tune ~200 variables with a live
> preview, then publish or export clean SCSS. All compilation runs through
> **`EXT:t3sbootstrap`**.

The backend module is fully localized (English source, German translation) and follows the
editor's backend language.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Preview, Publish &amp; Export](#preview-publish--export)
- [How it works](#how-it-works)
- [Editor features](#editor-features)
- [SCSS export](#scss-export)
- [Per-site themes](#per-site-themes)
- [Localization](#localization)
- [Tips &amp; troubleshooting](#tips--troubleshooting)
- [Third-party](#third-party)
- [License](#license)

## Features

- **Visual theme editor** in the TYPO3 backend — no hand-written SCSS required.
- **~200 Bootstrap 5.3 variables** across 24 collapsible groups.
- **26 Bootswatch presets** as ready-made starting points.
- **Interactive component preview** — real Bootstrap components (dropdowns, tabs, modal,
  carousel …) rendered live from your compiled CSS.
- **Live frontend preview** — see unpublished changes on your real site, risk-free.
- **Separate Preview and Publish** — nothing goes live until you say so.
- **Standalone SCSS export** — clean, portable `.scss` files, no lock-in.
- **Per-site themes** and full **English / German** localization.

## Requirements

| Component | Requirement |
| --- | --- |
| **TYPO3** | v14 |
| **PHP** | 8.2+ |
| **`EXT:t3sbootstrap`** | Installed, with a config record on each site's root page and **`customScss` enabled** (CDN disabled), so the outsourced SCSS files are used. |
| **Bootstrap SCSS sources** | Present at `EXT:t3sb_package/Resources/Public/T3SB-Bootstrap/Bootstrap/scss/` (created by t3sbootstrap; required for compilation). |

## Installation

### Composer (recommended)

```bash
composer require t3sbs/t3sbootstrap-builder
```

### Classic (non-Composer)

1. Download and unpack into `typo3conf/ext/t3sbootstrap_builder/`.
2. Activate the extension in the **Extension Manager**.
3. Run **Analyze Database Structure** to create `tx_t3sbootstrapbuilder_theme`.

### After installing (either way)

Flush caches:

```bash
php typo3/sysext/core/bin/typo3 cache:flush
```

The module then appears under **Content → T3SB Builder** (icon shared with t3sbootstrap).

## Quick start

1. Open **Content → T3SB Builder** and pick the site you want to theme.
2. *(Optional)* Choose a **Bootswatch preset** as a starting point and load its values.
3. Adjust colors, typography, spacing and component variables in the grouped editor.
4. Hit **Preview** to compile and check your changes — safely, without touching the live frontend.
5. Hit **Publish** to make the theme live, or **Export SCSS (.zip)** to download it.

## Preview, Publish &amp; Export

The builder is the **single source of truth** for the variables it manages: on every save the
variable files are fully overwritten (with a timestamped backup), never merged or appended.
Three actions sit at the bottom of the editor:

| Action | Color | What it does | Affects frontend? |
| --- | :---: | --- | :---: |
| **Preview** | green | Writes a *separate* preview track and compiles **only** the backend preview (`bb-preview-{id}.css`). Runs via AJAX and refreshes only the preview iframe — no page reload, so open groups, scroll position and field state are preserved. Falls back to a normal submit if JS is off. | No |
| **Publish** | primary | Writes the live frontend files, keeps the t3sbootstrap config record in sync and recompiles. A confirmation dialog is shown first. | **Yes** |
| **Export SCSS (.zip)** | grey | Saves the current values and downloads a ZIP (see [SCSS export](#scss-export)). | No |

Both Preview and Publish also refresh the in-backend component preview. Your edits are stored
either way (and pre-filled when reopening) — only **Publish** goes live.

## How it works

1. The editor exposes a catalog of Bootstrap 5.3 variables (`$primary`, `$card-border-radius`,
   `$enable-rounded`, …) in collapsible groups.
2. On save, the builder writes a complete variables SCSS file from all field values (defaults
   overlaid with your edits).
3. Compilation uses the scssphp engine shipped with `EXT:t3sbootstrap` (`Contrib/scssphp`),
   importing `custom-variables → bootstrap → custom`. The backend preview is written to
   `typo3temp/assets/t3sbootstrap/css/bb-preview-{id}.css`.
4. **Publish** additionally writes t3sbootstrap's live SCSS files and config record, so the
   frontend recompiles with the new theme.
5. **Preview** uses a backend AJAX route (`ajax_t3sbootstrap_builder_preview`, handled by
   `AjaxController`) that compiles the preview track and returns the CSS URL as JSON, so the
   editor swaps the preview iframe's `srcdoc` in place instead of reloading the page.

## Editor features

**~200 variables across 24 collapsible groups:** Theme colors, Body, Text colors, Links,
Typography, Grid &amp; Spacing, Border radius, Buttons, Forms, Options &amp; Toggles, Cards,
Carousel, Navbar, Nav, Dropdowns, Alerts, Badges, List groups, Tables, Modals, Accordion,
Progress, Pagination, Tooltips &amp; Popovers.

- **Collapsible groups** (native `<details>`) as an exclusive accordion: opening one closes the
  others, and the component preview scrolls to the matching section.
- **Boolean options** (the `enable-*` flags) render as on/off switches.
- **Color inputs:**
  - *Theme colors* group — color picker + text field.
  - All other color groups — color picker + text field + a **theme-color reference select** that
    writes an SCSS reference (`$primary`, `$success`, …) into the field.
  - The text field always stays editable for `rgba()` or `$variable` references.
- **Bootswatch presets** — pick a base preset and load its values into the fields client-side
  (no reload). Your own edits stay on top.

**Two-tab preview:**

| Tab | What you see |
| --- | --- |
| **Components** | An interactive component gallery rendered from the compiled CSS in an isolated iframe. Bootstrap's bundle JS runs inside, so dropdowns, tabs, accordion, tooltips, popovers, modal and carousel actually work. Resizable by dragging the bottom-right corner; translucent section backgrounds let a theme's body background (e.g. Quartz's gradient) show through. |
| **Frontend** | Your real frontend root page in an iframe (loaded on demand). After **Preview**, the stylesheet is swapped for the freshly compiled preview CSS *in this iframe only*, so you see unpublished changes without affecting the live site. An **Open** link opens the frontend in a new tab. |

A spinner is shown while each preview renders.

## SCSS export

**Export SCSS (.zip)** saves the current editor values and downloads a ZIP named
`{site}-{YYYY-MM-DD-HHMMSS}.zip` containing exactly two files:

- `scss/_variables.scss` — the pre-Bootstrap variables (your builder values).
- `scss/_custom.scss` — the Bootswatch component overrides, followed by your additional custom
  SCSS (the *Additional custom SCSS (after Bootstrap)* field).

## Per-site themes

One `tx_t3sbootstrapbuilder_theme` record per site (keyed by site identifier), stored at root
level (`pid 0`). The TCA sets `ignorePageTypeRestriction`, so the record never blocks page
doktype changes.

## Localization

UI strings live in `Resources/Private/Language/`:

- `locallang.xlf` / `de.locallang.xlf` — module UI and flash messages
- `locallang_mod.xlf` / `de.locallang_mod.xlf` — backend module label

English is the source language; German is provided as a translation. The backend follows the
user's language, falling back to English. Variable group names stay in English (they mirror the
Bootstrap SCSS variable names).

## Tips &amp; troubleshooting

> [!TIP]
> `$enable-rounded` (group *Options &amp; Toggles*) is a global Bootstrap switch: when off,
> **all** border-radius values (including `$card-border-radius`) are forced to `0`. Turn it on
> for radii to take effect.

> [!IMPORTANT]
> The **Frontend** preview tab embeds the real site in an iframe and swaps in the preview CSS,
> which requires backend and frontend on the **same origin**. If the frontend forbids embedding
> (`X-Frame-Options` / CSP `frame-ancestors`) or is cross-origin, use the **Open** link instead.

> [!NOTE]
> Backups of the live variable files are kept as `_{timestamp}-custom-variables-{id}.scss` next
> to the originals in `EXT:t3sb_package/Resources/Public/T3SB-SCSS/` (old backups are
> auto-pruned).

## Third-party

- **Bootstrap bundle JS** (`Resources/Public/JavaScript/vendor/bootstrap.bundle.min.js`, v5.3.3,
  MIT) — shipped to power the interactive component preview.
- **Bootswatch presets** — MIT-licensed (<https://github.com/thomaspark/bootswatch>). The 26
  presets are pre-cached in `Resources/Public/Presets/` (each as `_variables.scss` +
  `_bootswatch.scss`) and loaded at runtime; no download happens at runtime.

## License

GPL-2.0-or-later — see [`LICENSE.txt`](LICENSE.txt).

---

<sub>Maintained by **T3Solution** · [t3solution.de](https://t3solution.de) · part of the
[t3sbootstrap](https://www.t3sbootstrap.de) ecosystem.</sub>
