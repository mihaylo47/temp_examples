<?php

declare(strict_types=1);

namespace YiiApp\modules\salesWeekAuto\widgets;

use TbPager;

class SalesPagerWidget extends TbPager
{
    public string $baseUrlPath;
    public string $name;
    public ?string $group;

    protected function createPageUrl($page): string
    {
        $newPage = $page > 0 ? $page + 1 : null;

        return $this->baseUrlPath . '/' . $this->name . ($this->group ? '/' . $this->group : '') . ($newPage ? '/page/' . $newPage : '');
    }
}
