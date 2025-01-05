<?php

namespace YiiApp\modules\org\modules\aiDescriptionTool;

use CWebModule;

class AIDescriptionToolModule extends CWebModule
{
    public function preinit(): void
    {
        parent::preinit();

        $this->controllerNamespace = __NAMESPACE__ . '\\controllers';
    }

    public function getUrlRules(): array
    {
        return [
            '<_a:\w+>/<gid:\d+>' => 'default/<_a>',
            'responseData/<gid:\d+>/<rid:\d+>' => 'default/responseData',
        ];
    }
}
