<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;

class AdvancedCustomFieldsService
{
    use Register;

    public function boot(): void
    {
        add_action('init', [$this, 'init']);
        add_filter('acf/settings/save_json', [$this, 'saveJSON']);
        add_filter('acf/settings/load_json', [$this, 'loadJSON']);
    }

    public function init(): void
    {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title'  => 'Site Instellingen',
            'menu_title'  => 'Site Instellingen',
            'menu_slug'   => 'site-settings',
            'capability'  => 'edit_posts',
            'icon_url'    => 'dashicons-admin-settings',
            'position'    => 3,
            'redirect'    => false,
        ]);
    }

    public function saveJSON(): string
    {
        return THEME_INDEX_DIR . '/acf';
    }

    public function loadJSON(array $paths): array
    {
        $paths[] = $this->saveJSON();

        return $paths;
    }
}
