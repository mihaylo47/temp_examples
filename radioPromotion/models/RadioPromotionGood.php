<?php

namespace YiiApp\modules\mp\modules\radioPromotion\models;

use CRequiredValidator;
use CTimestampBehavior;
use CTypeValidator;
use Sp\Yii\BaseActiveRecord;

/**
 * @property numeric-string|positive-int $id
 * @property numeric-string|positive-int $connected_id
 * @property string                      $created
 * @property numeric-string|positive-int $gid
 * @property numeric-string|positive-int $uid
 * @property RadioPromotion|null         $radioPromotion
 */
class RadioPromotionGood extends BaseActiveRecord
{
    public const TABLE_NAME = 'radio_promotion_goods';

    public function rules()
    {
        return [
            [['connected_id', 'gid', 'uid'], CRequiredValidator::class],
            [['uid', 'gid', 'connected_id'], CTypeValidator::class, 'type' => 'integer'],
        ];
    }

    public function relations(): array
    {
        return [
            'radioPromotion' => [
                self::HAS_ONE,
                RadioPromotion::class,
                ['id' => 'connected_id'],
            ],
        ];
    }

    public function beforeSave(): bool
    {
        return parent::beforeSave();
    }

    public function behaviors(): array
    {
        return [
            CTimestampBehavior::class => [
                'class' => CTimestampBehavior::class,
                'createAttribute' => 'created',
                'updateAttribute' => null,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'gid' => 'Номер товара',
            'connected_id' => 'Номер акции',
            'uid' => 'Кто добавил',
            'created' => 'Дата создания',
        ];
    }
}
