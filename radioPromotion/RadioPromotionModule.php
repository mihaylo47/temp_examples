<?php

namespace YiiApp\modules\mp\modules\radioPromotion;

use CWebModule;

class RadioPromotionModule extends CWebModule
{
    protected function preinit(): void
    {
        parent::preinit();

        $this->controllerNamespace = __NAMESPACE__ . '\\controllers';
    }

    /**
     * @return string[]
     */
    public function getUrlRules()
    {
        return [
            '<_a:\w+>' => 'ads/<_a>',
        ];
    }
}
