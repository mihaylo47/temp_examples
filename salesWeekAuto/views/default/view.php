<?php
declare(strict_types=1);

/**
 * @var YiiApp\modules\salesWeekAuto\controllers\DefaultController $this
 * @var string                                                     $mode
 * @var array                                                      $nameConfig
 * @var string                                                     $baseUrlPath
 * @var array                                                      $groups
 */

use YiiApp\components\SHtml;
use YiiApp\modules\salesWeek\components\SalesWeekRendererHelper;
use YiiApp\modules\salesWeekAuto\widgets\SalesPagerWidget;
use YiiApp\widgets\GoodsListWidget\GoodsListWidget;

$user = Yii::app()->user;
?>

<style>
    .sales-week__header {
        margin: 40px 0;
    }
    .sales-week__category-header h2 a {
        text-decoration: none;
        color: #000;
    }
    .sales-week__category-header h2 a:hover,
    .sales-week__category-header h2 a:focus,
    .sales-week__category-header h2 a:active {
        text-decoration: underline;
        color: #000;
    }

    .sales-week__back {
        display: block;
        margin: 16px 0 28px;
        color: #333 !important;
    }
    .sales-week__back:before {
        content: '';
        display: inline-block;
        width: 12px;
        height: 12px;
        background-image: url("data:image/svg+xml,%3Csvg version='1.1' id='Layer_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' width='22px' height='40.995px' viewBox='0 0 22 40.995' enable-background='new 0 0 22 40.995' xml:space='preserve'%3E%3Cg%3E%3Cpath fill='%23000000' d='M21.5,19.289c0.667,0.667,0.667,1.748,0,2.415l0,0c-0.667,0.667-1.748,0.667-2.415,0L0.931,3.55 c-0.667-0.667-0.667-1.748,0-2.415l0,0c0.667-0.667,1.748-0.667,2.415,0L21.5,19.289z'/%3E%3Cpath fill='%23000000' d='M19.085,19.291c0.667-0.667,1.748-0.667,2.415,0l0,0c0.667,0.667,0.667,1.748,0,2.415L3.346,39.86 c-0.667,0.667-1.748,0.667-2.415,0l0,0c-0.667-0.667-0.667-1.748,0-2.415L19.085,19.291z'/%3E%3C/g%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        transform: rotateY(180deg);
        vertical-align: middle;
        margin-right: 8px;
        margin-bottom: 2px;
    }

    .sales-week__category .alert {
        margin-top: 20px;
    }
</style>
<div class="sales-week list-items">

    <?php if($mode == $this::VIEW_MODE): ?>
        <a class="sales-week__back" href = "<?= $baseUrlPath; ?>">Все акции</a>
    <?php elseif($mode == $this::VIEW_MODE_ALL): ?>
        <a class="sales-week__back" href = "<?= $baseUrlPath . '/' . $nameConfig['urlCode']; ?>">Вернуться в "<?= $nameConfig['name']; ?>"</a>
    <?php endif; ?>

    <?php if($mode == $this::VIEW_MODE): ?>
        <h1 class="sales-week__header"><?= $nameConfig['name']; ?></h1>
    <?php endif; ?>

    <?php SalesWeekRendererHelper::renderNavigation(array_map(function (array $group) {
        return [
            'name' => $group['name'],
            'url' => '#' . $group['name'],
            'active' => false,
        ];
    }, $groups)); ?>

    <div id="purchases-list-container">
        <div class="row-fluid">
                <?php foreach ($groups as $group): ?>
                <div class="sales-week__category" >
                    <div class="sales-week__category-header" id="<?= $group['name']; ?>">
                        <h2>
                            <?php if(isset($group['linkToAll'])): ?>
                                <a href="<?= $group['linkToAll']; ?>"><?= $group['name']; ?></a>
                            <?php else: ?>
                                <?= $group['name']; ?>
                            <?php endif; ?>

                        </h2>
                        <?php if(isset($group['note']) && $group['note'] != ''): ?>
                            <div class="sales-week__category-description"><?= SHtml::encode($group['note']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="">
                        <?php
                            Yii::app()->controller->widget(GoodsListWidget::class, [
                                'goods' => $group['provider']->getData(),
                                'showExtendedInfo' => true,
                            ]);

                    if ($mode == $this::VIEW_MODE_ALL) {
                        echo '<div class="pagination">';
                        Yii::app()->controller->widget(SalesPagerWidget::class, [
                            'baseUrlPath' => $baseUrlPath,
                            'name' => $nameConfig['urlCode'],
                            'group' => $group['groupCode'],

                            'pages' => $group['provider']->getPagination(),
                            'header' => '',
                            'prevPageLabel' => '<i class="pag-back"></i> Назад',
                            'nextPageLabel' => 'Вперед <i class="pag-next"></i>',
                            'displayFirstAndLast' => false,
                            'maxButtonCount' => Yii::app()->mobileDetect->isMobileVersion() ? 6 : 10,
                        ]);
                        echo '</div>';
                    }
                    ?>
                    </div>

                    <?php if(isset($group['linkToAll'])): ?>
                        <a style="margin-top:20px;" href="<?= $group['linkToAll']; ?>" class="sales-week__category-items--showbtn-link ">Смотреть все "<?= $group['name']; ?>"</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
        </div>
    </div>
</div>
