<?php

namespace YiiApp\modules\mp\modules\radioPromotion\components;

use CDbCriteria;
use YiiApp\modules\mp\modules\radioPromotion\models\RadioPromotion;
use YiiApp\modules\mp\modules\radioPromotion\models\RadioPromotionGood;

class RadioPromotionService
{
    public function __construct()
    {
    }

    public function getRadioPromotionList(): array
    {
        // @todo cache
        $criteria = new CDbCriteria();
        $criteria->compare('active', 1);
        $criteria->order = 'created DESC';
        $criteria->index = 'id';

        return RadioPromotion::model()->findAll($criteria);
    }

    public function getGoodEntryList(int $gid): array
    {
        $criteria = new CDbCriteria();
        $criteria->compare('gid', $gid);
        $criteria->index = 'connected_id';

        return RadioPromotionGood::model()->findAll($criteria);
    }

    private function normalizeName(string $string): string
    {
        return preg_replace('/[^a-zа-яё0-9]/', '', mb_strtolower($string));
    }

    public function detectRadioPromotion(string $searchString): ?RadioPromotion
    {
        $searchString = $this->normalizeName($searchString);
        $promoList = $this->getRadioPromotionList();

        $normalizedPromoList = array_map(fn (RadioPromotion $radioPromotion) => $this->normalizeName($radioPromotion->name), $promoList);
        $foundPromotionKey = array_search($searchString, $normalizedPromoList, true);

        return $foundPromotionKey ? $promoList[$foundPromotionKey] : null;
    }
}
