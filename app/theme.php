<?php

declare(strict_types=1);

namespace Gradify;

use Gradify\Services\AdvancedCustomFieldsService;
use Gradify\Services\BlockService;
use Gradify\Services\BookingService;
use Gradify\Services\CronService;
use Gradify\Services\MenuService;
use Gradify\Services\MixService;
use Gradify\Services\MollieService;
use Gradify\Services\PostTypeService;
use Gradify\Services\ThemeService;
use Gradify\Traits\Register;

class Theme
{
    use Register;

    public function boot(): void
    {
        AdvancedCustomFieldsService::register();
        ThemeService::register();
        PostTypeService::register();
        MenuService::register();
        MixService::register();
        BlockService::register();
        BookingService::register();
        CronService::register();
    }
}
