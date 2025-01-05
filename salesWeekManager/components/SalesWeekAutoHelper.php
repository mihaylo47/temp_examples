<?php

declare(strict_types=1);

namespace YiiApp\modules\mp\modules\salesWeekManager\components;

use YiiApp\modules\search\components\Service\SearchFilter;

class SalesWeekAutoHelper
{
    public static function getGroupFiltersMap(): array
    {
        return [
            SearchFilter::ATTR_QUERY => [
                'title' => 'Поисковой запрос',
                'type' => 'string',
                'desc' => 'Строка поиска - не обязательный параметр, но если указан, то должен соответствовать правильному sphinx запросу. ' . "\n"
                    . 'Порядок слов важен, окончания не важны. Поиск будет искать товары в которых есть ВСЕ указанные слова (исключения через оператор ИЛИ и ОТРИЦАНИЕ)' . "\n"
                    . 'Оператор ИЛИ "|" распространяется на стоящие вокруг него слова, например "деревянный стол | стул", если необходимо, чтобы ИЛИ работало на фразы, то их следует обернуть в скобки "домашний (настольная лампа) | (станочный светильник)".' . "\n"
                    . 'В иных случаях скобки ставить бессмысленно, а пустые и неверно поставленные могут привести к ошибке.' . "\n"
                    . 'Оператор ОТРИЦАНИЕ "-" распространяется на идущее после него слово без пробела! В других случаях использовать дефис нельзя, это может привести к ошибке. Поисковой запрос не может состоять только из оператора отрицания, должно быть хотябы одно поисковое слово. Можно использовать несколько исключений сразу, например: "носки -женские -мужские"' . "\n"
                    . 'Кавычки и иные спецсимволы в запросе использовать нельзя, никакие. Они будут вырезаны из запроса.',
                'defaultVisible' => true,
            ],
            SearchFilter::ATTR_CATEGORIES_LIST => [
                'title' => 'Список категорий (+)',
                'type' => 'int[]',
                'desc' => 'Номера категорий через запятую, если указаны то в выборку попадут товары только из этих категорий. Пробелы не важны, пример: "30834, 30835, 30837, 110060, 30815"',
                'defaultVisible' => true],
            SearchFilter::ATTR_NOT_CATEGORIES_LIST => [
                'title' => 'Список категорий (-)',
                'type' => 'int[]',
                'desc' => 'Номера категорий через запятую, если указаны, то в выборку не попадут товары из этих категорий. Например: "30847, 30486"',
                'defaultVisible' => false,
            ],
            SearchFilter::ATTR_PURCHASE_ID_LIST => [
                'title' => 'Список покупок (+)',
                'type' => 'int[]',
                'desc' => 'Номера покупок через запятую, если указаны, то в выборку попадут товары только из этих покупок, например : "974603, 1059046, 1031803"',
                'defaultVisible' => false,
            ],
            SearchFilter::ATTR_COLLECTION_ID_LIST => [
                'title' => 'Список коллекций (+)',
                'type' => 'int[]',
                'desc' => 'Номера коллекций через запятую, если указаны, то в выборку попадут товары только из этих коллекций, например: "19789617, 19789618, 18776498"',
                'defaultVisible' => false,
            ],
            SearchFilter::ATTR_EXCLUDE_GOODS_LIST => [
                'title' => 'Исключить товары (-)',
                'type' => 'int[]',
                'desc' => 'Номера товаров через запятую, которые не должны попасть в выборку, например: "1141080647, 1141080648"',
                'defaultVisible' => false,
            ],
            SearchFilter::ATTR_PURCHASE_TYPE => [
                'title' => 'Тип покупки',
                'type' => 'select',
                'values' => [
                    \Purchases::T_NOT_DEFINED => 'Все покупки',
                    \Purchases::T_SP => 'Совместные покупки',
                    \Purchases::T_PRISTROI => 'Пристрой',
                    \Purchases::T_SHOP => 'Shopping Club',
                    \Purchases::T_EAST_MARKET => 'China Market',
                    \Purchases::T_SALES => 'Распродажа',
                ],
                'desc' => '',
                'defaultVisible' => false,
            ],
            SearchFilter::ATTR_SORT => [
                'title' => 'Сортировка',
                'type' => 'select',
                'values' => [
                    0 => 'По релевантности',
                    SearchFilter::SORT_PRICE_DESC => 'По убыванию цены',
                    SearchFilter::SORT_PRICE_ASC => 'По возрастанию цены',
                ],
                'desc' => 'Сортировка результатов',
                'defaultVisible' => false,
            ],
        ];
    }

    public static function camelCaseKeys(array $arr): array
    {
        return array_combine(array_map(fn ($item) => self::snakeToCamel($item), array_keys($arr)), array_values($arr));
    }

    public static function snakeCaseKeys(array $arr): array
    {
        return array_combine(array_map(fn ($item) => self::camelToSnake($item, '-'), array_keys($arr)), array_values($arr));
    }

    public static function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $input))));
    }

    public static function camelToSnake(string $input, string $delimiter = '_'): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', $delimiter . '$0', $input));
    }

    public static function clearOutRequest(array $request, bool $convertKeys = false): array
    {
        $map = self::getGroupFiltersMap();
        $res = [];
        foreach ($request as $key => $value) {
            $camelKey = self::camelToSnake($key, '-');
            $value = trim(str_replace(['"', "'"], '', $value));
            if (isset($map[$camelKey]) && !empty($value)) {
                $resValue = match ($map[$camelKey]['type']) {
                    'int[]' => array_filter(array_map('intval', explode(',', str_replace(' ', '', $value)))),
                    'select', 'string' => $value,
                    default => $value,
                };
                $res[$convertKeys ? $camelKey : $key] = $resValue;
            }
        }

        return $res;
    }

    /**
     * Возвращает true если скобки употреблены верно
     */
    public static function isValidBrackets(string $string): bool
    {
        if (!str_contains($string, '(') && !str_contains($string, ')')) {
            return true;
        }
        preg_match('/([^()]*\((?<myname>[^()]+|(?R))+\)[^()]*)+/x', $string, $matches);

        return $matches && $matches[0] == $string;
    }
}
