<?php

use Sp\Yii\BaseActiveRecord;

/**
 * @property numeric-string|positive-int $id
 * @property numeric-string|positive-int $connected_id
 * @property string                      $created
 * @property string                      $name
 * @property string                      $link
 * @property string                      $note
 * @property string|int|bool             $active
 * @property string                      $json
 * @property numeric-string|positive-int $sort
 */
class SalesWeekAutoItem extends BaseActiveRecord
{
    public const TABLE_NAME = 'sales_week_auto_item';
    public array $filterList = [];

    public function rules()
    {
        return [
            [['name', 'link', 'connected_id'], CRequiredValidator::class],
            ['active, sort, connected_id', CTypeValidator::class, 'type' => 'integer'],
            ['name, link, note, json', CTypeValidator::class, 'type' => 'string'],
        ];
    }

    public function relations(): array
    {
        return [
            'items' => [
                self::HAS_ONE,
                SalesWeekAuto::class,
                ['id' => 'connected_id'],
            ],
        ];
    }

    public function beforeSave(): bool
    {
        $this->json = json_encode($this->filterList, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return true;
    }

    public function afterFind(): void
    {
        if (!empty($this->json)) {
            $this->filterList = json_decode($this->json, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $this->filterList = [];
        }

        parent::afterFind();
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
            'note' => 'Описание',
            'active' => 'Активна',
            'json' => 'Характеристики',
            'sort' => 'Порядок',
        ];
    }

    public function toArray(): array
    {
        $attributes = parent::getAttributes();
        unset($attributes['json']);
        $attributes['filterList'] = $this->filterList;

        return $attributes;
    }

    public function getFilterList(): array
    {
        return $this->filterList;
    }
}
