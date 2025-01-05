<?php

namespace YiiApp\modules\mp\modules\salesWeekManager;

use CWebModule;

class SalesWeekManagerModule extends CWebModule
{
    public function init(): void
    {
        parent::init();

        $this->controllerNamespace = __NAMESPACE__ . '\\controllers';
    }
}
