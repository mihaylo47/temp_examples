<?php

namespace YiiApp\modules\mp\modules\radioPromotion\models;

use CDbCriteria;
use CRequiredValidator;
use CTimestampBehavior;
use CTypeValidator;
use Goods;
use Sp\Arr;
use Sp\Yii\BaseActiveRecord;

/**
 * @property numeric-string|positive-int $id
 * @property string                      $created
 * @property string                      $name
 * @property string                      $link
 * @property string|int|bool             $active
 * @property string                      $sortMethod
 * @property RadioPromotionGood[]|null   $goods
 */
class RadioPromotion extends BaseActiveRecord
{
    public const TABLE_NAME = 'radio_promotions';

    public const SORT_RAND = 'sort_rand';
    public const SORT_DATE_ASC = 'sort_date_asc';
    public const SORT_DATE_DESC = 'sort_date_desc';
    public const SORT_PRICE_ASC = 'sort_price_asc';
    public const SORT_PRICE_DESC = 'sort_price_desc';

    public const SORT_LIST = [
        self::SORT_RAND => 'Случайное перемешивание',
        self::SORT_DATE_ASC => 'Сначала первые добавленные',
        self::SORT_DATE_DESC => 'Сначала последние добавленные',
        self::SORT_PRICE_ASC => 'По возрастанию цены',
        self::SORT_PRICE_DESC => 'По убыванию цены',
    ];

    public function rules()
    {
        return [
            [['name', 'link', 'sortMethod'], CRequiredValidator::class],
            [['name', 'sortMethod'], CTypeValidator::class, 'type' => 'string'],
            ['active', CTypeValidator::class, 'type' => 'integer'],
        ];
    }

    public function relations(): array
    {
        return [
            'goods' => [
                self::HAS_MANY,
                RadioPromotionGood::class,
                ['connected_id' => 'id'],
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
            'sortMethod' => 'Метод сортировки',
        ];
    }

    /**
     * @return Goods[]
     */
    public function getSortedGoods(): array
    {
        $goodsList = $this->goods;
        if (in_array($this->sortMethod, [self::SORT_DATE_ASC, self::SORT_DATE_DESC], true)) {
            usort($goodsList, fn (RadioPromotionGood $gA, RadioPromotionGood $gB) => $gA->created <=> $gB->created);
        }
        $gidList = Arr::pluck($goodsList, 'gid');

        $criteria = new CDbCriteria();

        $criteria->order = match ($this->sortMethod) {
            self::SORT_DATE_ASC => 'FIELD(gid,' . implode(',', $gidList) . ')',
            self::SORT_DATE_DESC => 'FIELD(gid,' . implode(',', array_reverse($gidList)) . ')',
            self::SORT_PRICE_ASC => 'price ASC',
            self::SORT_PRICE_DESC => 'price DESC',
            default => 'rand()' // self::SORT_RAND
        };

        return Goods::model()->findAllByPk($gidList, $criteria);
    }
}
