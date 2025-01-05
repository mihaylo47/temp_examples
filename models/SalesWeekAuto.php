<?php

use Sp\Yii\BaseActiveRecord;

/**
 * @property numeric-string|positive-int $id
 * @property string                      $created
 * @property string                      $name
 * @property string                      $link
 * @property numeric-string|positive-int $picid
 * @property string|int|bool             $active
 * @property numeric-string|positive-int $sort
 */
class SalesWeekAuto extends BaseActiveRecord
{
    public const TABLE_NAME = 'sales_week_auto';

    public function rules()
    {
        return [
            [['name', 'link'], CRequiredValidator::class],
            ['name, link', CTypeValidator::class, 'type' => 'string'],
            ['picid, active, picid', CTypeValidator::class, 'type' => 'integer'],
        ];
    }

    public function relations(): array
    {
        return [
            'items' => [
                self::HAS_MANY,
                SalesWeekAutoItem::class,
                ['set_id' => 'id'],
            ],
        ];
    }

    public function beforeSave(): bool
    {
        return parent::beforeSave();
    }

    public function isActive(): bool
    {
        return $this->active;
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
            'created' => 'Дата создания',
            'name' => 'Название',
            'link' => 'Ссылка',
            'active' => 'Активна',
            'picid' => 'Картинка',
            'sort' => 'Порядок',
        ];
    }
}
