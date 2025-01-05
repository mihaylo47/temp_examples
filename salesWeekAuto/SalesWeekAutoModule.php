<?php

namespace YiiApp\modules\salesWeekAuto;

use CWebModule;

class SalesWeekAutoModule extends CWebModule
{
    public function init(): void
    {
        parent::init();

        $this->controllerNamespace = __NAMESPACE__ . '\\controllers';
    }

    public function getUrlRules(): array
    {
        return [
            'sales-week-auto' => 'default/index',
            'sales-week-auto/<name:[\w\-]+>' => 'default/view',
        ];
    }
}
