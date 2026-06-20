<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Definition;

/**
 * Catalog of editable Bootstrap 5.3 SCSS variables, grouped like bootstrap.build.
 * Each entry: key (SCSS var name without $), type, default, group, label.
 * Extend freely – this is the source of truth for the editor UI and export.
 */
final class BootstrapVariableRegistry
{
    public const TYPE_COLOR = 'color';
    public const TYPE_LENGTH = 'length';
    public const TYPE_NUMBER = 'number';
    public const TYPE_SELECT = 'select';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_FONT = 'font';

    /**
     * @return array<string, array<int, array<string, mixed>>> grouped variable definitions
     */
    public function getGroups(): array
    {
        return [
            'Theme colors' => [
                self::def('primary', self::TYPE_COLOR, '#0d6efd'),
                self::def('secondary', self::TYPE_COLOR, '#6c757d'),
                self::def('success', self::TYPE_COLOR, '#198754'),
                self::def('info', self::TYPE_COLOR, '#0dcaf0'),
                self::def('warning', self::TYPE_COLOR, '#ffc107'),
                self::def('danger', self::TYPE_COLOR, '#dc3545'),
                self::def('light', self::TYPE_COLOR, '#f8f9fa'),
                self::def('dark', self::TYPE_COLOR, '#212529'),
            ],
            'Body' => [
                self::def('body-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('body-color', self::TYPE_COLOR, '#212529'),
            ],
            'Text colors' => [
                self::def('body-secondary-color', self::TYPE_COLOR, 'rgba(33,37,41,0.75)'),
                self::def('body-tertiary-color', self::TYPE_COLOR, 'rgba(33,37,41,0.5)'),
                self::def('headings-color', self::TYPE_COLOR, 'inherit'),
                self::def('link-color', self::TYPE_COLOR, '#0d6efd'),
                self::def('link-hover-color', self::TYPE_COLOR, '#0a58ca'),
                self::def('code-color', self::TYPE_COLOR, '#d63384'),
                self::def('text-muted', self::TYPE_COLOR, 'rgba(33,37,41,0.75)'),
            ],
            'Links' => [
                self::def('link-decoration', self::TYPE_SELECT, 'underline', ['none', 'underline']),
                self::def('link-hover-decoration', self::TYPE_SELECT, 'underline', ['none', 'underline']),
                self::def('link-shade-percentage', self::TYPE_LENGTH, '20%'),
            ],
            'Typography' => [
                self::def('font-family-base', self::TYPE_FONT, 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'),
                self::def('font-size-base', self::TYPE_LENGTH, '1rem'),
                self::def('font-size-sm', self::TYPE_LENGTH, '0.875rem'),
                self::def('font-size-lg', self::TYPE_LENGTH, '1.25rem'),
                self::def('line-height-base', self::TYPE_NUMBER, '1.5'),
                self::def('font-weight-light', self::TYPE_NUMBER, '300'),
                self::def('font-weight-normal', self::TYPE_NUMBER, '400'),
                self::def('font-weight-bold', self::TYPE_NUMBER, '700'),
                self::def('h1-font-size', self::TYPE_LENGTH, '2.5rem'),
                self::def('h2-font-size', self::TYPE_LENGTH, '2rem'),
                self::def('h3-font-size', self::TYPE_LENGTH, '1.75rem'),
                self::def('h4-font-size', self::TYPE_LENGTH, '1.5rem'),
                self::def('h5-font-size', self::TYPE_LENGTH, '1.25rem'),
                self::def('h6-font-size', self::TYPE_LENGTH, '1rem'),
                self::def('headings-font-weight', self::TYPE_NUMBER, '500'),
                self::def('headings-line-height', self::TYPE_NUMBER, '1.2'),
                self::def('headings-margin-bottom', self::TYPE_LENGTH, '0.5rem'),
                self::def('paragraph-margin-bottom', self::TYPE_LENGTH, '1rem'),
                self::def('lead-font-size', self::TYPE_LENGTH, '1.25rem'),
                self::def('lead-font-weight', self::TYPE_NUMBER, '300'),
            ],
            'Grid & Spacing' => [
                self::def('spacer', self::TYPE_LENGTH, '1rem'),
                self::def('grid-gutter-width', self::TYPE_LENGTH, '1.5rem'),
            ],
            'Border radius' => [
                self::def('border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('border-radius-sm', self::TYPE_LENGTH, '0.25rem'),
                self::def('border-radius-lg', self::TYPE_LENGTH, '0.5rem'),
            ],
            'Buttons' => [
                self::def('btn-padding-y', self::TYPE_LENGTH, '0.375rem'),
                self::def('btn-padding-x', self::TYPE_LENGTH, '0.75rem'),
                self::def('btn-font-size', self::TYPE_LENGTH, '1rem'),
                self::def('btn-font-weight', self::TYPE_NUMBER, '400'),
                self::def('btn-line-height', self::TYPE_NUMBER, '1.5'),
                self::def('btn-border-width', self::TYPE_LENGTH, '1px'),
                self::def('btn-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('btn-padding-y-sm', self::TYPE_LENGTH, '0.25rem'),
                self::def('btn-padding-x-sm', self::TYPE_LENGTH, '0.5rem'),
                self::def('btn-padding-y-lg', self::TYPE_LENGTH, '0.5rem'),
                self::def('btn-padding-x-lg', self::TYPE_LENGTH, '1rem'),
                self::def('btn-disabled-opacity', self::TYPE_NUMBER, '0.65'),
            ],
            'Forms' => [
                self::def('input-padding-y', self::TYPE_LENGTH, '0.375rem'),
                self::def('input-padding-x', self::TYPE_LENGTH, '0.75rem'),
                self::def('input-font-size', self::TYPE_LENGTH, '1rem'),
                self::def('input-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('input-color', self::TYPE_COLOR, '#212529'),
                self::def('input-border-color', self::TYPE_COLOR, '#dee2e6'),
                self::def('input-border-width', self::TYPE_LENGTH, '1px'),
                self::def('input-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('input-focus-border-color', self::TYPE_COLOR, '#86b7fe'),
                self::def('input-placeholder-color', self::TYPE_COLOR, '#6c757d'),
                self::def('input-disabled-bg', self::TYPE_COLOR, '#e9ecef'),
                self::def('input-btn-focus-width', self::TYPE_LENGTH, '0.25rem'),
                self::def('form-label-font-size', self::TYPE_LENGTH, '1rem'),
                self::def('form-label-font-weight', self::TYPE_NUMBER, '400'),
            ],
            'Options & Toggles' => [
                self::def('enable-rounded', self::TYPE_BOOLEAN, 'true'),
                self::def('enable-shadows', self::TYPE_BOOLEAN, 'false'),
                self::def('enable-gradients', self::TYPE_BOOLEAN, 'false'),
                self::def('enable-transitions', self::TYPE_BOOLEAN, 'true'),
                self::def('enable-smooth-scroll', self::TYPE_BOOLEAN, 'true'),
                self::def('enable-grid-classes', self::TYPE_BOOLEAN, 'true'),
                self::def('enable-button-pointers', self::TYPE_BOOLEAN, 'true'),
                self::def('enable-rfs', self::TYPE_BOOLEAN, 'true'),
                self::def('enable-validation-icons', self::TYPE_BOOLEAN, 'true'),
                self::def('enable-negative-margins', self::TYPE_BOOLEAN, 'false'),
                self::def('enable-important-utilities', self::TYPE_BOOLEAN, 'true'),
            ],
            'Cards' => [
                self::def('card-spacer-y', self::TYPE_LENGTH, '1rem'),
                self::def('card-spacer-x', self::TYPE_LENGTH, '1rem'),
                self::def('card-title-spacer-y', self::TYPE_LENGTH, '0.5rem'),
                self::def('card-border-width', self::TYPE_LENGTH, '1px'),
                self::def('card-border-color', self::TYPE_COLOR, 'rgba(0,0,0,0.175)'),
                self::def('card-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('card-inner-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('card-cap-padding-y', self::TYPE_LENGTH, '0.5rem'),
                self::def('card-cap-padding-x', self::TYPE_LENGTH, '1rem'),
                self::def('card-cap-bg', self::TYPE_COLOR, 'rgba(0,0,0,0.03)'),
                self::def('card-cap-color', self::TYPE_COLOR, 'inherit'),
                self::def('card-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('card-color', self::TYPE_COLOR, 'inherit'),
                self::def('card-img-overlay-padding', self::TYPE_LENGTH, '1rem'),
                self::def('card-group-margin', self::TYPE_LENGTH, '0.75rem'),
                self::def('card-height', self::TYPE_LENGTH, 'auto'),
                self::def('card-subtitle-color', self::TYPE_COLOR, 'rgba(33,37,41,0.75)'),
            ],
            'Carousel' => [
                self::def('carousel-control-color', self::TYPE_COLOR, '#ffffff'),
                self::def('carousel-control-width', self::TYPE_LENGTH, '15%'),
                self::def('carousel-control-opacity', self::TYPE_NUMBER, '0.5'),
                self::def('carousel-control-hover-opacity', self::TYPE_NUMBER, '0.9'),
                self::def('carousel-indicator-width', self::TYPE_LENGTH, '30px'),
                self::def('carousel-indicator-height', self::TYPE_LENGTH, '3px'),
                self::def('carousel-indicator-spacer', self::TYPE_LENGTH, '3px'),
                self::def('carousel-indicator-active-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('carousel-indicator-opacity', self::TYPE_NUMBER, '0.5'),
                self::def('carousel-caption-color', self::TYPE_COLOR, '#ffffff'),
                self::def('carousel-caption-width', self::TYPE_LENGTH, '70%'),
            ],
            'Navbar' => [
                self::def('navbar-padding-y', self::TYPE_LENGTH, '0.5rem'),
                self::def('navbar-padding-x', self::TYPE_LENGTH, '0'),
                self::def('navbar-brand-font-size', self::TYPE_LENGTH, '1.25rem'),
                self::def('navbar-brand-padding-y', self::TYPE_LENGTH, '0.3125rem'),
                self::def('navbar-nav-link-padding-x', self::TYPE_LENGTH, '0.5rem'),
                self::def('navbar-toggler-padding-y', self::TYPE_LENGTH, '0.25rem'),
                self::def('navbar-toggler-padding-x', self::TYPE_LENGTH, '0.75rem'),
                self::def('navbar-toggler-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('navbar-dark-color', self::TYPE_COLOR, 'rgba(255,255,255,0.55)'),
                self::def('navbar-dark-hover-color', self::TYPE_COLOR, 'rgba(255,255,255,0.75)'),
                self::def('navbar-dark-active-color', self::TYPE_COLOR, '#ffffff'),
                self::def('navbar-dark-brand-color', self::TYPE_COLOR, '#ffffff'),
                self::def('navbar-light-color', self::TYPE_COLOR, 'rgba(0,0,0,0.55)'),
                self::def('navbar-light-hover-color', self::TYPE_COLOR, 'rgba(0,0,0,0.7)'),
                self::def('navbar-light-active-color', self::TYPE_COLOR, 'rgba(0,0,0,0.9)'),
                self::def('navbar-light-brand-color', self::TYPE_COLOR, 'rgba(0,0,0,0.9)'),
            ],
            'Nav' => [
                self::def('nav-link-padding-y', self::TYPE_LENGTH, '0.5rem'),
                self::def('nav-link-padding-x', self::TYPE_LENGTH, '1rem'),
                self::def('nav-link-font-weight', self::TYPE_NUMBER, '400'),
                self::def('nav-link-color', self::TYPE_COLOR, '#0d6efd'),
                self::def('nav-link-hover-color', self::TYPE_COLOR, '#0a58ca'),
                self::def('nav-link-disabled-color', self::TYPE_COLOR, '#6c757d'),
                self::def('nav-tabs-border-color', self::TYPE_COLOR, '#dee2e6'),
                self::def('nav-tabs-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('nav-tabs-link-active-color', self::TYPE_COLOR, '#495057'),
                self::def('nav-tabs-link-active-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('nav-tabs-link-active-border-color', self::TYPE_COLOR, '#dee2e6'),
                self::def('nav-pills-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('nav-pills-link-active-color', self::TYPE_COLOR, '#ffffff'),
                self::def('nav-pills-link-active-bg', self::TYPE_COLOR, '#0d6efd'),
            ],
            'Dropdowns' => [
                self::def('dropdown-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('dropdown-border-color', self::TYPE_COLOR, 'rgba(0,0,0,0.15)'),
                self::def('dropdown-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('dropdown-link-color', self::TYPE_COLOR, '#212529'),
                self::def('dropdown-link-hover-bg', self::TYPE_COLOR, '#e9ecef'),
                self::def('dropdown-link-active-bg', self::TYPE_COLOR, '#0d6efd'),
            ],
            'Alerts' => [
                self::def('alert-padding-y', self::TYPE_LENGTH, '1rem'),
                self::def('alert-padding-x', self::TYPE_LENGTH, '1rem'),
                self::def('alert-margin-bottom', self::TYPE_LENGTH, '1rem'),
                self::def('alert-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('alert-border-width', self::TYPE_LENGTH, '1px'),
                self::def('alert-link-font-weight', self::TYPE_NUMBER, '700'),
                self::def('alert-bg-scale', self::TYPE_LENGTH, '-80%'),
                self::def('alert-border-scale', self::TYPE_LENGTH, '-70%'),
                self::def('alert-color-scale', self::TYPE_LENGTH, '40%'),
            ],
            'Badges' => [
                self::def('badge-font-size', self::TYPE_LENGTH, '0.75em'),
                self::def('badge-font-weight', self::TYPE_NUMBER, '700'),
                self::def('badge-padding-y', self::TYPE_LENGTH, '0.35em'),
                self::def('badge-padding-x', self::TYPE_LENGTH, '0.65em'),
                self::def('badge-border-radius', self::TYPE_LENGTH, '0.375rem'),
            ],
            'List groups' => [
                self::def('list-group-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('list-group-border-color', self::TYPE_COLOR, 'rgba(0,0,0,0.125)'),
                self::def('list-group-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('list-group-active-bg', self::TYPE_COLOR, '#0d6efd'),
                self::def('list-group-active-border-color', self::TYPE_COLOR, '#0d6efd'),
            ],
            'Tables' => [
                self::def('table-cell-padding-y', self::TYPE_LENGTH, '0.5rem'),
                self::def('table-cell-padding-x', self::TYPE_LENGTH, '0.5rem'),
                self::def('table-cell-padding-y-sm', self::TYPE_LENGTH, '0.25rem'),
                self::def('table-cell-padding-x-sm', self::TYPE_LENGTH, '0.25rem'),
                self::def('table-color', self::TYPE_COLOR, '#212529'),
                self::def('table-bg', self::TYPE_COLOR, 'transparent'),
                self::def('table-border-width', self::TYPE_LENGTH, '1px'),
                self::def('table-border-color', self::TYPE_COLOR, '#dee2e6'),
                self::def('table-striped-bg', self::TYPE_COLOR, 'rgba(0,0,0,0.05)'),
                self::def('table-striped-color', self::TYPE_COLOR, '#212529'),
                self::def('table-active-bg', self::TYPE_COLOR, 'rgba(0,0,0,0.1)'),
                self::def('table-hover-bg', self::TYPE_COLOR, 'rgba(0,0,0,0.075)'),
                self::def('table-hover-color', self::TYPE_COLOR, '#212529'),
            ],
            'Modals' => [
                self::def('modal-content-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('modal-content-border-color', self::TYPE_COLOR, 'rgba(0,0,0,0.2)'),
                self::def('modal-content-border-radius', self::TYPE_LENGTH, '0.5rem'),
                self::def('modal-backdrop-bg', self::TYPE_COLOR, '#000000'),
                self::def('modal-header-padding-y', self::TYPE_LENGTH, '1rem'),
            ],
            'Accordion' => [
                self::def('accordion-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('accordion-color', self::TYPE_COLOR, '#212529'),
                self::def('accordion-border-color', self::TYPE_COLOR, 'rgba(0,0,0,0.125)'),
                self::def('accordion-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('accordion-button-color', self::TYPE_COLOR, '#212529'),
                self::def('accordion-button-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('accordion-button-active-bg', self::TYPE_COLOR, '#cfe2ff'),
                self::def('accordion-button-active-color', self::TYPE_COLOR, '#0c63e4'),
            ],
            'Progress' => [
                self::def('progress-height', self::TYPE_LENGTH, '1rem'),
                self::def('progress-bg', self::TYPE_COLOR, '#e9ecef'),
                self::def('progress-bar-bg', self::TYPE_COLOR, '#0d6efd'),
                self::def('progress-border-radius', self::TYPE_LENGTH, '0.375rem'),
            ],
            'Pagination' => [
                self::def('pagination-padding-y', self::TYPE_LENGTH, '0.375rem'),
                self::def('pagination-padding-x', self::TYPE_LENGTH, '0.75rem'),
                self::def('pagination-color', self::TYPE_COLOR, '#0d6efd'),
                self::def('pagination-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('pagination-active-bg', self::TYPE_COLOR, '#0d6efd'),
                self::def('pagination-active-border-color', self::TYPE_COLOR, '#0d6efd'),
            ],
            'Tooltips & Popovers' => [
                self::def('tooltip-bg', self::TYPE_COLOR, '#000000'),
                self::def('tooltip-color', self::TYPE_COLOR, '#ffffff'),
                self::def('tooltip-border-radius', self::TYPE_LENGTH, '0.375rem'),
                self::def('popover-bg', self::TYPE_COLOR, '#ffffff'),
                self::def('popover-border-color', self::TYPE_COLOR, 'rgba(0,0,0,0.2)'),
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>> flat key => definition
     */
    public function getFlat(): array
    {
        $flat = [];
        foreach ($this->getGroups() as $group => $vars) {
            foreach ($vars as $var) {
                $var['group'] = $group;
                $flat[$var['key']] = $var;
            }
        }
        return $flat;
    }

    /**
     * @param array<int, string> $options
     * @return array<string, mixed>
     */
    private static function def(string $key, string $type, string $default, array $options = []): array
    {
        return [
            'key' => $key,
            'type' => $type,
            'default' => $default,
            'options' => $options,
            'label' => ucwords(str_replace('-', ' ', $key)),
        ];
    }
}
