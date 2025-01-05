<?php

declare(strict_types=1);

namespace YiiApp\modules\salesWeekAuto\controllers;

use CDataProvider;
use CDataProvider;
use CDbCriteria;
use Legacy\RBAC;
use Sp\Arr;
use Sp\CategoriesTree;
use Sp\KoreanShop\KoreanShopGapRenderer;
use Sp\Sp;
use Sp\Yii\BaseActiveRecord;
use Sp\Yii\Controller;
use Yii;
use YiiApp\components\ClientScript;
use YiiApp\helpers\Cache;
use YiiApp\modules\mp\modules\salesWeekManager\components\SalesWeekAutoHelper;
use YiiApp\modules\search\components\Service\SearchFilter;
use YiiApp\modules\search\components\Service\SiteSearchResults;
use YiiApp\modules\search\components\Service\SiteSearchResultsRetriever;
use YiiApp\modules\search\components\Service\SiteSearchService;

class DefaultController extends Controller
{
    private const LIMIT_ON_GROUP = 15;
    private const VIEW_ALL_ON_PAGE = 100;
    private const SELECT_ON_GROUP = 1000;

    public const VIEW_MODE = 'VIEW';
    public const VIEW_MODE_ALL = 'VIEW_ALL';
    public const VIEW_MODE_CUSTOM = 'VIEW_CUSTOM';

    public function __construct(private CategoriesTree $categoriesTree, private SiteSearchService $siteSearchService, $id, $module = null)
    {
        parent::__construct($id, $module);
    }

    public function actionIndex(): void
    {
        Yii::app()->clientScript->requireWebpackModule('yii-widgets.teasers-widget.grid');
        $this->pageTitle = 'Подборки';

        $saleList = $this->getSalesWeekAutoList();

        $params = [
            'items' => $saleList,
        ];

        $this->render('index', $params);
    }

    private function safeDescriptionCut(string $string, int $limit = 100): string
    {
        return trim(mb_substr($string, 0, mb_strrpos(mb_substr($string, 0, $limit), ',')), ',');
    }

    public function actionView(?string $name = null): void
    {
        if (Sp::rules()->isKoreanShopPage()) {
            $this->actionViewKoreanShop();

            return;
        }

        $nameConfig = $this->getConfigByName($name);
        if (!$nameConfig) {
            $this->notFound('Подборка не найдена');
        }

        $this->pageTitle = $nameConfig['name'];
        $groupsListing = implode(', ', array_map(
            static fn ($group) => mb_strtolower(mb_substr($group['name'], 0, 1)) . mb_substr($group['name'], 1),
            $nameConfig['groups'] ?? []
        ));
        $this->pageDescription = 'Купить ' . $this->safeDescriptionCut($groupsListing) . '. выгодно и быстро!';

        $this->addBodyClass('only-goods');
        Yii::app()->clientScript->requireWebpackModule('sales-week', [], ClientScript::POS_BEGIN);

        $groups = [];
        $gidCrossList = [];

        $stickGroup = Yii::app()->request->getParam('stick', '') ?: null;

        if ($stickGroup && isset($nameConfig['groups'][$stickGroup])) {
            $nameConfig['groups'] = [$stickGroup => $nameConfig['groups'][$stickGroup], ...$nameConfig['groups']];
        }

        $limit = self::LIMIT_ON_GROUP;
        // в мобилке, если лимит не четное, то делаем четным, чтобы товары красиво вставали в строку по 2
        if (Sp::isMobileVersion() && ($limit % 2 === 1)) { // @phpstan-ignore-line
            --$limit;
        }

        foreach ($nameConfig['groups'] as $groupCode => $group) {
            $goodsProvider = $this->getGoodsProviderByRequest($group['request'], $gidCrossList, $limit, self::SELECT_ON_GROUP);
            $groups[] = ['name' => $group['name'], 'note' => $group['note'] ?? '', 'provider' => $goodsProvider, 'linkToAll' => '/' . rtrim(Yii::app()->request->pathInfo, '/\\') . '/' . $groupCode];
            $goodsIds = Arr::pluck($goodsProvider->getData(), 'gid');
            $gidCrossList = [...$gidCrossList, ...$goodsIds];
        }

        $params = [
            'mode' => self::VIEW_MODE,
            'nameConfig' => $nameConfig,
            'groups' => $groups,
            'baseUrlPath' => '/' . explode('/', Yii::app()->request->url)[1],
        ];

        BaseActiveRecord::onFromReplica();
        BaseActiveRecord::offBehaviors();
        Yii::app()->cache->offActualCheck();
        try {
            $this->render('view', $params);
        } finally {
            BaseActiveRecord::offFromReplica();
            BaseActiveRecord::onBehaviors();
            Yii::app()->cache->onActualCheck();
        }
    }

    public function actionViewAll(string $name, string $group, ?int $page = 1): void
    {
        Yii::app()->clientScript->requireWebpackModule('sales-week', [], ClientScript::POS_BEGIN);

        $nameConfig = $this->getConfigByName($name);
        if (!$nameConfig) {
            $this->notFound('Подборка не найдена');
        }

        if (!isset($nameConfig['groups'][$group])) {
            $this->notFound('Подборка не найдена.');
        }

        $groupName = $nameConfig['groups'][$group]['name'];
        $loweredGroupName = mb_strtolower(mb_substr($groupName, 0, 1)) . mb_substr($groupName, 1);
        $this->pageTitle = $nameConfig['name'] . ' | Купить ' . $loweredGroupName;
        $this->pageDescription = 'Купить ' . $loweredGroupName . '.  выгодно и быстро!';

        $goodsProvider = $this->getGoodsProviderByRequest($nameConfig['groups'][$group]['request'], perPage: self::VIEW_ALL_ON_PAGE);
        $groups = [['groupCode' => $group, 'name' => $nameConfig['groups'][$group]['name'], 'note' => $nameConfig['groups'][$group]['note'] ?? '', 'goodsList' => [], 'provider' => $goodsProvider]];

        $params = [
            'mode' => self::VIEW_MODE_ALL,
            'nameConfig' => $nameConfig,
            'groups' => $groups,
            // сложности с подменой url нужны только для возможности подмены старых ручных выборок на автоматические
            'baseUrlPath' => '/' . explode('/', Yii::app()->request->url)[1],
        ];

        $this->render('view', $params);
    }

    public function actionViewAllCustom(?int $page = 1): void
    {
        if (!Yii::app()->user->checkAccess(RBAC::R_ADMIN) && !Yii::app()->user->checkAccess(RBAC::R_MODERATOR)) {
            $this->notFound();
        }
        Yii::app()->clientScript->requireWebpackModule('sales-week', [], ClientScript::POS_BEGIN);

        $group = 'custom';
        $groupName = Yii::app()->request->getParam('group');

        $request = Yii::app()->request->getParam('request', '') ?: [];
        if (!is_array($request)) {
            $this->badRequest('Нет условий поиска');
        }

        $request = SalesWeekAutoHelper::clearOutRequest($request, true);
        if (!count($request)) {
            $this->badRequest('Нет условий поиска');
        }

        $nameConfig = [
            'urlCode' => 'custom',
            'name' => '',
            'groups' => [
                'custom' => [
                    'name' => $groupName,
                    'request' => $request,
                    'note' => '',
                ],
            ],
        ];
        $this->pageTitle = $nameConfig['groups'][$group]['name'];

        $goodsProvider = $this->getGoodsProviderByRequest($nameConfig['groups'][$group]['request'], perPage: self::VIEW_ALL_ON_PAGE);
        $groups = [['groupCode' => $group, 'name' => $nameConfig['groups'][$group]['name'], 'note' => $nameConfig['groups'][$group]['note'], 'goodsList' => [], 'provider' => $goodsProvider]];

        $params = [
            'mode' => self::VIEW_MODE_CUSTOM,
            'nameConfig' => $nameConfig,
            'groups' => $groups,
            // сложности с подменой url нужны только для возможности подмены старых ручных выборок на автоматические
            'baseUrlPath' => '/' . explode('/', Yii::app()->request->url)[1],
        ];

        $this->render('view', $params);
    }

    public function getConfigByName(string $name): ?array
    {
        $config = $this->getConfigList();

        return isset($config[$name]) ? [...$config[$name], 'urlCode' => $name] : null;
    }

    private function getGoodsProviderByRequest(array $request, array $excludeGidList = [], int $perPage = 10, ?int $selectLimit = null): CDataProvider
    {
        $userRating = (int) floor($this->getUser()->loadModel()?->getRating() ?? 0);
        $cacheKey = __METHOD__ . '|' . $this->getUser()->getCityId() . '|' . $userRating . '|' . md5(serialize([$request, $excludeGidList, $perPage, $selectLimit, $_GET]));
        $cache = new Cache($cacheKey);
        $cachedData = $cache->get();
        if ($cachedData !== false) {
            return $cachedData;
        }
        $filter = $this->getSearchFilterByRequest($request, $excludeGidList, $selectLimit);

        // вдохновлялся SphinxSearcher.php::getSearchResults()
        $results = $this->siteSearchService->search($filter);
        $provider = $this->getGoodsProvider($results, $filter, $perPage);
        $cache->set($provider, Cache::T_MINUTE * 5);

        return $provider;
    }

    private function getGoodsProviderByMultiRequest(array $requestList, int $perRequest = 10, int $selectLimit = 100): CDataProvider
    {
        $userRating = (int) floor($this->getUser()->loadModel()?->getRating() ?? 0);
        $cacheKey = __METHOD__ . '|' . $this->getUser()->getCityId() . '|' . $userRating . '|' . md5(serialize([$requestList, $perRequest, $selectLimit, $_GET]));
        $cache = new Cache($cacheKey);
        $cachedData = $cache->get();
        if ($cachedData !== false) {
            return $cachedData;
        }

        $filter = new SearchFilter([]);
        $resultsList = [];
        foreach ($requestList as $request) {
            unset($request[SearchFilter::ATTR_SORT]); // сбрасываем сортировку для мультизапроса
            $filter = $this->getSearchFilterByRequest($request, [], $selectLimit);
            $result = $this->siteSearchService->search($filter);
            $resultsList[] = $result;
        }

        $firstWeightArray = array_filter(array_map(static fn (SiteSearchResults $result) => $result->getGoods()['rawSphinxGoodsData'][0]['weight'] ?? null, $resultsList));
        $max = max($firstWeightArray);
        $kWeightArray = array_map(fn ($w) => round($max / $w - 0.005, 2), $firstWeightArray);

        // нормализуем веса результатов всех подборок к единому порядку, для последующего сквозного ранжирования по весу
        $commonRawSphinxGoodsData = [];
        foreach ($resultsList as $resultIndex => $result) {
            $lastWeight = 0;
            foreach ($result->getGoods()['rawSphinxGoodsData'] as $goodsData) {
                $goodsData['resultIndex'] = $resultIndex;
                if ($lastWeight > 0 && $goodsData['weight'] > $lastWeight) {
                    break; // делаем отсечку на двойниках
                }
                $lastWeight = $goodsData['weight'];
                $id = $goodsData['id'];
                $k = $kWeightArray[$resultIndex];
                $newWeight = (int)floor($goodsData['weight'] * $k);
                if (!isset($commonRawSphinxGoodsData[$id]) || $commonRawSphinxGoodsData[$id]['weight'] < $newWeight) {
                    $commonRawSphinxGoodsData[$id] = [...$goodsData, 'weight' => $newWeight];
                }
            }
        }
        usort($commonRawSphinxGoodsData, fn ($a, $b) => $b['weight'] <=> $a['weight']);
        $commonResult = new SiteSearchResults();
        $commonResult->setGoods([
            'goodsIds' => Arr::pluck($commonRawSphinxGoodsData, 'id'),
            'rawSphinxGoodsData' => $commonRawSphinxGoodsData,
            'brandList' => [], // @todo пока не заморачивался слиянием
        ]);

        $provider = $this->getGoodsProvider($commonResult, $filter, $perRequest);
        $cache->set($provider, Cache::T_MINUTE * 5);

        return $provider;
    }

    private function getSearchFilterByRequest(array $request, array $excludeGidList = [], ?int $selectLimit = null): SearchFilter
    {
        if (isset($request[SearchFilter::ATTR_CATEGORIES_LIST])) {
            // категории размножаем подкатегориями
            $request[SearchFilter::ATTR_CATEGORIES_LIST] = $this->categoriesTree->getChildrenForManyParents($request[SearchFilter::ATTR_CATEGORIES_LIST]);
        }
        if (count($excludeGidList)) {
            $request[SearchFilter::ATTR_EXCLUDE_GOODS_LIST] = [...$request[SearchFilter::ATTR_EXCLUDE_GOODS_LIST] ?? [], ...$excludeGidList];
        }

        // принудительно отключаем поиск оргов/категорий/покупок из стандартного алгоритма поиска
        $request[SearchFilter::ATTR_ALLOW_SEARCH_ORGS] = 0;
        $request[SearchFilter::ATTR_ALLOW_SEARCH_PURCHASES] = 0;
        $request[SearchFilter::ATTR_ALLOW_SEARCH_CATEGORIES] = 0;

        // включаем принудительное перемешивание оргов
        $request[SearchFilter::RESULT_MODIFIER_SHUFFLE] = 1;
        $request[SearchFilter::RESULT_MODIFIER_SHUFFLE_ORG_THRESHOLD] = 2;
        $request[SearchFilter::RESULT_MODIFIER_SHUFFLE_GOODS_PER_ORG] = 1;

        // Отключаем рекламное перемешивание
        $request[SearchFilter::RESULT_MODIFIER_ADV_SORTING] = false;

        if (isset($request[SearchFilter::ATTR_QUERY])) {
            $request[SearchFilter::ATTR_QUERY] = preg_replace('/[^a-zA-Zа-яА-Яеё0-9-()| ]/u', '', $request[SearchFilter::ATTR_QUERY]);

            if (!SalesWeekAutoHelper::isValidBrackets($request[SearchFilter::ATTR_QUERY])) {
                $request[SearchFilter::ATTR_QUERY] = str_replace(['(', ')'], '', $request[SearchFilter::ATTR_QUERY]);
            }
        }

        // Если правило содержит спец-операторы включаем поиск "как есть"
        if (isset($request[SearchFilter::ATTR_QUERY])
            && (str_contains($request[SearchFilter::ATTR_QUERY], '|') || str_contains($request[SearchFilter::ATTR_QUERY], '-'))) {
            $request[SearchFilter::ATTR_QUERY_AS_IS_FLAG] = true;
        }

        // выгребаем намного больше, чтобы нивелировать удаление подобных и лучше перемешать продавцов, для viewAll выборка без ограничений
        return new SearchFilter([
            SearchFilter::ATTR_SORT => SearchFilter::SORT_RELEVANCY,
            SearchFilter::ATTR_GOODS_LIMIT => $selectLimit ?? '',
            ...$request,
        ]);
    }

    private function getGoodsProvider(SiteSearchResults $results, SearchFilter $filter, int $perPage): CDataProvider
    {
        $retriever = new SiteSearchResultsRetriever($results, $filter);
        $provider = $retriever->getGoodsProvider(goodsPerPage: $perPage);

        return $provider;
    }

    public function getSalesWeekAutoList(): array
    {
        $criteria = new CDbCriteria();
        $criteria->compare('active', 1);
        $criteria->order = 'sort ASC';
        $criteria->index = 'link';

        $salesWeekAutoList = \SalesWeekAuto::model()->findAll($criteria);

        // @todo потом всё перевести на модели, а пока массив
        return array_map(fn (\SalesWeekAuto $salesWeekAuto) => [
            'id' => $salesWeekAuto->id,
            'link' => $salesWeekAuto->link,
            'name' => $salesWeekAuto->name,
            'picture' => 'https://cdn.domen.ru/pictures/' . $salesWeekAuto->picid,
            'groups' => [],
        ], $salesWeekAutoList);
    }

    public function getConfigList(): array
    {
        $configList = $this->getSalesWeekAutoList();

        $configListIdIndexed = array_column($configList, null, 'id');

        $criteria = new CDbCriteria();
        $criteria->compare('active', 1);
        $criteria->order = 'sort ASC';

        $salesWeekAutoItemList = \SalesWeekAutoItem::model()->findAll($criteria);

        foreach ($salesWeekAutoItemList as $item) {
            $configListIdIndexed[$item->connected_id]['groups'][$item->link] = [
                'name' => $item->name,
                'note' => $item->note,
                'link' => $item->link,
                'request' => SalesWeekAutoHelper::snakeCaseKeys($item->getFilterList()),
            ];
        }

        return array_column($configListIdIndexed, null, 'link');
    }

    public function actionViewKoreanShop(): void
    {
        $this->layout = '//layouts/korean-shop/default';
        Yii::app()->clientScript->requireWebpackModule('korean-shop');

        if (!Sp::rules()->isKoreanShopPage()) {
            $this->notFound('Подборка не найдена');
        }

        $nameConfig = $this->getConfigByName('korean-shop');
        if (!$nameConfig) {
            $this->notFound('Подборка не найдена');
        }

        $this->pageTitle = $nameConfig['name'];
        $groupsListing = implode(', ', array_map(
            static fn ($group) => mb_strtolower(mb_substr($group['name'], 0, 1)) . mb_substr($group['name'], 1),
            $nameConfig['groups'] ?? []
        ));
        $this->pageDescription = 'Купить ' . $this->safeDescriptionCut($groupsListing) . '.  выгодно и быстро!';

        $this->addBodyClass('only-goods');
        Yii::app()->clientScript->requireWebpackModule('sales-week', [], ClientScript::POS_BEGIN);

        $groups = [];
        $stickGroup = Yii::app()->request->getParam('stick', '') ?: null;

        if ($stickGroup && isset($nameConfig['groups'][$stickGroup])) {
            $nameConfig['groups'] = [$stickGroup => $nameConfig['groups'][$stickGroup], ...$nameConfig['groups']];
        }

        $groupsNamesPicturesMap = [
            'vitaminy-i-bady' => 'https://cdn.domen.ru/pictures/1672038185',
            'uhod-dlya-volos' => 'https://cdn.domen.ru/pictures/1672041886',
            'dekorativnaya-kosmetika' => 'https://cdn.domen.ru/pictures/1672043794',
            'dlya-problemnoy-kozhi' => 'https://cdn.domen.ru/pictures/1672049193',
            'antivozrastnoy-uhod' => 'https://cdn.domen.ru/pictures/1672052781',
            'premium-kosmetika' => 'https://cdn.domen.ru/pictures/1672059211',
            'nabory-kosmetiki' => 'https://cdn.domen.ru/pictures/1672075115',
            'uhod-dlya-tela' => 'https://cdn.domen.ru/pictures/1672094527',
            'aksesuary' => 'https://cdn.domen.ru/pictures/1672094882',
            'maski' => 'https://cdn.domen.ru/pictures/1672099726', // https://cdn.domen.ru/pictures/1672095162',
            'sredstva-dlya-uhoda-za-kozhey-vokrug-glaz-i-vek' => 'https://cdn.domen.ru/pictures/1672101334',
        ];

        $requests = Arr::pluck($nameConfig['groups'], 'request');
        $goodsProvider = $this->getGoodsProviderByMultiRequest($requests, $this->fixLimit(self::VIEW_ALL_ON_PAGE), self::SELECT_ON_GROUP);

        $group = reset($nameConfig['groups']);
        $groups[] = [
            'id' => 'top',
            'name' => '<i class="korean-shop-icon__cat-with-hearts-in-eyes"></i> Топ',
            'note' => '',
            'link' => '',
            'img' => $groupsNamesPicturesMap[$group['link']],
            'provider' => $goodsProvider,
        ];

        $limit = $this->fixLimit(12, true);

        $gidCrossList = [];
        unset($group);
        foreach ($nameConfig['groups'] as $groupCode => $group) {
            $goodsProvider = $this->getGoodsProviderByRequest($group['request'], $gidCrossList, $limit, self::SELECT_ON_GROUP);

            $groups[] = [
                'id' => $group['link'],
                'name' => $group['name'],
                'note' => $group['note'] ?? '',
                'link' => $group['link'],
                'img' => $groupsNamesPicturesMap[$group['link']],
                'provider' => $goodsProvider,
                'linkToAll' => '/' . rtrim(Yii::app()->request->pathInfo, '/\\') . '/' . $groupCode,
            ];
            $goodsIds = Arr::pluck($goodsProvider->getData(), 'gid');
            $gidCrossList = [...$gidCrossList, ...$goodsIds];
        }

        $defaultGroups = array_map(fn ($g, $gk) => [
            'name' => $g['name'],
            'note' => $g['note'] ?? '',
            'link' => $g['link'],
            'img' => $groupsNamesPicturesMap[$g['link']],
            'provider' => null,
            'linkToAll' => '/' . rtrim(Yii::app()->request->pathInfo, '/\\') . '/' . $gk,
        ], $nameConfig['groups'], array_keys($nameConfig['groups']));

        $params = [
            'mode' => self::VIEW_MODE,
            'nameConfig' => $nameConfig,
            'defaultGroups' => $defaultGroups,
            'groups' => $groups,
            'baseUrlPath' => '/' . explode('/', Yii::app()->request->url)[1],
        ];

        BaseActiveRecord::onFromReplica();
        BaseActiveRecord::offBehaviors();
        Yii::app()->cache->offActualCheck();
        try {
            $this->render('view-korean-shop', $params);
        } finally {
            BaseActiveRecord::offFromReplica();
            BaseActiveRecord::onBehaviors();
            Yii::app()->cache->onActualCheck();
        }
    }

    public function renderKoreanGap(int $i): void
    {
        $renderer = Sp::get(KoreanShopGapRenderer::class);

        echo match ($i) {
            0 => $renderer->getKittyGap(),
            //            0 => $renderer->getFeatures() . $renderer->getCounters(),
            //            1 => $renderer->getProblemsNav(),
            default => '',
        };
    }

    private function fixLimit(int $limit, bool $withShowAllBlock = false): int
    {
        // в мобилке, если лимит не четное, то делаем четным, чтобы товары красиво вставали в строку по 2 товара
        if (Sp::isMobileVersion() && ($limit % 2 !== 0)) { // @phpstan-ignore-line
            ++$limit;
        }

        // в десктопе, если лимит не кратен 6, то делаем кратным 6, чтобы товары красиво вставали в строку по 6 товаров
        if (!Sp::isMobileVersion() && $limit % 6 !== 0) { // @phpstan-ignore-line
            $limit = (int)($limit / 6) * 6;
        }

        if ($withShowAllBlock) {
            --$limit;
        }

        return $limit;
    }
}
