<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;

class ThemeService
{
    use Register;

    public function boot(): void
    {
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(200, 200, true);
        add_theme_support('menus');
        add_theme_support('automatic-feed-links');
        add_theme_support('title-tag');
        add_theme_support('html5', [
            'comment-list',
            'search-form',
            'comment-form',
        ]);
        add_filter('widget_text', 'do_shortcode');
    }
}
