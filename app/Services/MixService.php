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

        $themeDir = get_template_directory();
        $themeUri = get_template_directory_uri() . '/public';

        $resolve = static function (string $key) use ($manifest, $themeDir, $themeUri): array {
            if (!isset($manifest[$key])) {
                return [null, null];
            }
            $relPath = strtok((string) $manifest[$key], '?');
            $absPath = $themeDir . '/public' . $relPath;
            $version = is_file($absPath) ? (string) filemtime($absPath) : null;
            return [$themeUri . $relPath, $version];
        };

        [$cssUrl, $cssVer] = $resolve('/css/index.css');
        if ($cssUrl) {
            wp_register_style('theme-styling', $cssUrl, [], $cssVer);
            wp_enqueue_style('theme-styling');
        }

        [$jsUrl, $jsVer] = $resolve('/js/index.min.js');
        if ($jsUrl) {
            wp_register_script(
                'theme-js',
                $jsUrl,
                ['jquery'],
                $jsVer,
                ['in_footer' => true, 'strategy' => 'defer']
            );
            wp_enqueue_script('theme-js');
        }
    }
}
