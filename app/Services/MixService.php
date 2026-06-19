<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;

class MixService
{
    use Register;

    public function boot(): void
    {
        add_action('init', [$this, 'init']);
    }

    public function init(): void
    {
        if (is_admin()) {
            return;
        }

        $manifestPath = THEME_INDEX_DIR . '/public/mix-manifest.json';
        if (!is_file($manifestPath)) {
            return;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            return;
        }

        $themeUri = get_template_directory_uri() . '/public';

        if (isset($manifest['/css/index.css'])) {
            wp_register_style('theme-styling', $themeUri . $manifest['/css/index.css']);
            wp_enqueue_style('theme-styling');
        }

        if (isset($manifest['/js/index.min.js'])) {
            wp_register_script(
                'theme-js',
                $themeUri . $manifest['/js/index.min.js'],
                ['jquery'],
                null,
                ['in_footer' => true, 'strategy' => 'defer']
            );
            wp_enqueue_script('theme-js');
        }
    }
}
