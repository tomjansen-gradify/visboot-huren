<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;

class MenuService
{
    use Register;

    public function boot(): void
    {
        add_action('init', [$this, 'init']);
    }

    public function init(): void
    {
        register_nav_menus([
            'primary'       => __('Hoofdmenu'),
            'mobile'        => __('Mobiel menu'),
            'footer-col-1'  => __('Footer kolom 1'),
            'footer-col-2'  => __('Footer kolom 2'),
            'footer-col-3'  => __('Footer kolom 3'),
            'footer-bottom' => __('Footer onder'),
        ]);
    }
}
