<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;

class PostTypeService
{
    use Register;

    public function boot(): void
    {
        add_action('init', [$this, 'registerVisboot']);
        add_action('init', [$this, 'registerBooking']);
    }

    public function registerBooking(): void
    {
        register_post_type('booking', [
            'labels' => [
                'name'               => __('Boekingen', 'visboothuren'),
                'singular_name'      => __('Boeking', 'visboothuren'),
                'menu_name'          => __('Boekingen', 'visboothuren'),
                'add_new'            => __('Nieuwe boeking', 'visboothuren'),
                'add_new_item'       => __('Nieuwe boeking toevoegen', 'visboothuren'),
                'edit_item'          => __('Boeking bekijken', 'visboothuren'),
                'view_item'          => __('Boeking bekijken', 'visboothuren'),
                'search_items'       => __('Boekingen zoeken', 'visboothuren'),
                'not_found'          => __('Geen boekingen gevonden', 'visboothuren'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false,
            'has_archive'         => false,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => ['title', 'editor', 'custom-fields'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ]);
    }

    public function registerVisboot(): void
    {
        register_post_type('visboot', [
            'labels' => [
                'name'               => __('Visboten', 'visboothuren'),
                'singular_name'      => __('Visboot', 'visboothuren'),
                'menu_name'          => __('Visboten', 'visboothuren'),
                'add_new'            => __('Nieuwe visboot', 'visboothuren'),
                'add_new_item'       => __('Nieuwe visboot toevoegen', 'visboothuren'),
                'edit_item'          => __('Visboot bewerken', 'visboothuren'),
                'new_item'           => __('Nieuwe visboot', 'visboothuren'),
                'view_item'          => __('Visboot bekijken', 'visboothuren'),
                'search_items'       => __('Visboten zoeken', 'visboothuren'),
                'not_found'          => __('Geen visboten gevonden', 'visboothuren'),
                'not_found_in_trash' => __('Geen visboten in prullenbak', 'visboothuren'),
            ],
            'public'              => true,
            'has_archive'         => false,
            'show_in_rest'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-image-flip-horizontal',
            'supports'            => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
            'rewrite'             => ['slug' => 'visboot', 'with_front' => false],
        ]);
    }
}
