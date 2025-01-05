<?php

declare(strict_types=1);

namespace YiiApp\widgets\GoodsRepository;

use Goods;
use Sp\Kernel\Environment;
use Yii;
use YiiApp\components\Currency;

class GoodsOverallTop100Repository extends GoodsRepository
{
    public const POPULARITY_COEF = 400;
    public const DAY_PERIOD = 30;

    protected const EXPECTED_GOODS_COUNT = 500;

    /**
     * @param int[] $excludeIds
     *
     * @return array<array{gid: string, orders_count: string}>
     *
     * @throws \CException
     */
    protected function fetchGoods(?int $regionId = null, array $excludeIds = []): array
    {
        $orderPeriodSql = '';
        if (SP_READONLY_DB || Environment::isProduction()) {
            $orderPeriodSql = 'and m.created > now() - interval ' . self::DAY_PERIOD . ' day';
        }

        $params = [
            ':magic_coef' => self::POPULARITY_COEF,
            ':min_price' => 20,
        ];

        $sql = $this->getSql($regionId, $orderPeriodSql, $excludeIds, $params);
        $command = $this->dbConnection->createCommand($sql);

        return $command->queryAll(true, $params);
    }

    protected function getSql(?int $regionId, string $where, array $excludeIds = [], array &$params = []): string
    {
        $params = array_merge([
            ':rejected' => \Purchases::M_REJECTED,
            ':confirmed' => \Orders::S_CONFIRMED,
            ':not_confirmed' => \Orders::S_NOT_CONFIRMED,
            ':usd' => Yii::app()->currency->getRate(Currency::USD),
            ':eur' => Yii::app()->currency->getRate(Currency::EUR),
        ], $params);

        $exceptGoodsSql = '';
        if (\count($excludeIds) > 0) {
            $exceptGoodsSql = 'and g.gid not in (' . implode(', ', $excludeIds) . ')';
        }

        if ($regionId) {
            $where .= ' and c.region_id = ' . $regionId;
        }

        $maxExecutionTime = \PHP_SAPI === 'cli' ? '/*+ MAX_EXECUTION_TIME(500000) */' : '';

        if (isset($params[':magic_coef'])) {
            $order = '(count(*) * (real_price + :magic_coef)) desc';
            $order2 = '(orders_count * (real_price + :magic_coef)) desc';
        } else {
            $order = 'count(*) desc';
            $order2 = 'orders_count desc';
        }

        $maxPriceCondition = '';
        if (isset($params[':max_price'])) {
            $maxPriceCondition = "and (g.price <= :max_price
                or (g.price * (100 + coalesce(c.fee, p.fee)) / 100) * (if(p.currency = 'rub', 1, if(p.currency = 'usd', :usd, if(p.currency = 'eur', :eur, 1)))) <= :max_price)";
        }
        $limit = $this::EXPECTED_GOODS_COUNT;

        return <<<SQL
            select {$maxExecutionTime} gid, orders_count, real_price from (
            with mo as (
                select m.*
                from megaorders m
                         left join purchases p on m.pid = p.pid
                         left join cities c on p.city_id = c.id
                where 1
                  AND p.published IS NOT NULL
                  AND p.moderation_status != :rejected
                  {$where}
            )
            select g.gid,
                   g.cid,
                   count(*) as orders_count,
                   ((g.price * (100 + coalesce(c.fee, p.fee)) / 100) *
                   (if(p.currency = 'rub', 1, if(p.currency = 'usd', :usd, if(p.currency = 'eur', :eur, 1))))) real_price
            from orders o
                     left join goods g on o.gid = g.gid
                     left join collections c on g.cid = c.cid
                     left join purchases p on c.pid = p.pid
            where o.mid in (select id from mo)
                and (g.price >= :min_price or (g.price * (100 + coalesce(c.fee, p.fee)) / 100) *
                                     (if(p.currency = 'rub', 1, if(p.currency = 'usd', :usd, if(p.currency = 'eur', :eur, 1)))) >= :min_price)
                {$maxPriceCondition}
                and g.picid is not null
                and o.status in (:confirmed, :not_confirmed)
                {$exceptGoodsSql}
                and o.oid > 350000000
            group by o.gid
            having count(*) >= 5
            order by {$order}
            ) a
            group by cid
            order by {$order2}
            limit {$limit}
        SQL;
    }

    /**
     * Аналог родительского метода getGoodsGroupedByCategory только без группировки по категориям и пересобиранием getList()
     *
     * @return Goods[]
     */
    public function getTopGoods(): array
    {
        $goods = $this->getGoods();
        $collections = $this->getCollectionsByCids(array_unique(array_column($goods, 'cid')));

        $result = [];
        foreach ($goods as $good) {
            if (!$good['category_id']) {
                $good['collection'] = $collections[$good['cid']];
                $good['category_id'] = $good['collection']->category_id;
            }
            $result[$good['gid']] = $good;
        }

        return $result;
    }

    protected function buildGoods(?int $regionId): array
    {
        $result = $this->fetchGoods($regionId);

        return $this->getViewableGoods($result, $this::EXPECTED_GOODS_COUNT, true, true);
    }
}
