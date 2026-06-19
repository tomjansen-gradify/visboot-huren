<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;

class BlockService
{
    use Register;

    public function boot(): void
    {
        if (function_exists('acf_register_block_type')) {
            add_action('acf/init', [$this, 'init']);
        }
    }

    public function init(): void
    {
        $blocks = [
            [
                'name'            => 'hero-block',
                'title'           => 'Hero block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/hero-block.php',
                'keywords'        => ['hero', 'header'],
            ],
            [
                'name'            => 'usp-block',
                'title'           => 'USP block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/usp-block.php',
                'keywords'        => ['usp', 'voordelen'],
            ],
            [
                'name'            => 'boat-listing-block',
                'title'           => 'Boat listing block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/boat-listing-block.php',
                'keywords'        => ['visboot', 'aanbod', 'boats'],
            ],
            [
                'name'            => 'text-block',
                'title'           => 'Text block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/text-block.php',
                'keywords'        => ['tekst', 'wysiwyg', 'paragraaf'],
            ],
            [
                'name'            => 'cta-block',
                'title'           => 'CTA block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/cta-block.php',
                'keywords'        => ['cta', 'contact', 'call to action'],
            ],
            [
                'name'            => 'page-header-block',
                'title'           => 'Page header block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/page-header-block.php',
                'keywords'        => ['page', 'header', 'kop'],
            ],
            [
                'name'            => 'image-text-block',
                'title'           => 'Image + text block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/image-text-block.php',
                'keywords'        => ['image', 'tekst', 'afbeelding'],
            ],
            [
                'name'            => 'contact-form-block',
                'title'           => 'Contact form block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/contact-form-block.php',
                'keywords'        => ['contact', 'formulier', 'form'],
            ],
            [
                'name'            => 'contact-info-block',
                'title'           => 'Contact info block',
                'render_template' => get_stylesheet_directory() . '/resources/views/components/contact-info-block.php',
                'keywords'        => ['contact', 'info', 'email', 'telefoon'],
            ],
        ];

        $this->registerBlocks($blocks);
    }

    private function registerBlocks(array $blocks): void
    {
        foreach ($blocks as $block) {
            acf_register_block_type([
                'name'            => $block['name'],
                'title'           => $block['title'],
                'render_template' => $block['render_template'],
                'keywords'        => $block['keywords'],
            ]);
        }
    }
}
