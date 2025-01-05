<?php

namespace YiiApp\modules\org\modules\aiDescriptionTool\components;

use Sp\Yii\Model\EmitBalance;
use Yii;
use YiiApp\helpers\Scenario;

class AIGoodsService
{
    public function __construct()
    {
    }

    public static function getRequestInstruction(): string
    {
        return 'Улучши название товара, придумай новое улучшенное полное описание, выдели особенности товара.';
    }

    public static function getPrice(): float
    {
        return Yii::app()->params['AIGoodsHelper']['price'];
    }

    public function call(AIRequest $aiRequest, bool $isPay = false): int
    {
        $id = $aiRequest->register();

        $aiRequest->call();

        if ($isPay) {
            $this->pay($aiRequest->getUid());
            $aiRequest->setPayed(self::getPrice());
        }

        return $id;
    }

    private function pay(int $uid): void
    {
        $bonus = new EmitBalance(Scenario::INSERT);
        $bonus->create(
            -self::getPrice(),
            $uid,
            'Услуга ИИ помощника генерации описания товара'
        );
        $bonus->emit();
    }
}
