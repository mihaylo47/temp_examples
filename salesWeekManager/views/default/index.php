<?php

use YiiApp\components\SHtml;

/**
 * @var YiiApp\modules\mp\modules\salesWeekManager\controllers\DefaultController $this
 * @var CActiveDataProvider                                                      $salesWeekAutoProvider
 */
?>

<style>
    .btn-holder{
        text-align: right;
    }
    .sales-week-set-tile{
        display: block;
        width: 200px;
        height: 80px;
        background-position-x: center;
        background-position-y: 0;
        background-size: contain;
        background-repeat: no-repeat;
    }

</style>

<h2>Автоматические подборки</h2>

<div class="btn-holder">
    <a class="btn btn-secondary" href="/mp/salesWeekManager/default/new"><i class="bi-plus-square-dotted"></i> Добавить подборку</a>
</div>

<?php $this->widget(TbGridView::class, [
    'dataProvider' => $salesWeekAutoProvider,
    'ajaxUpdate' => false,
    'columns' => [
        [
            'header' => 'Картинка',
            'type' => 'raw',
            'value' => fn (SalesWeekAuto $set): string => '<div class="sales-week-set-tile" style="background-image: url(https://cdn.domen.ru/pictures/' . $set->picid . ')"></div>',
        ],
        [
            'header' => 'Название',
            'type' => 'raw',
            'value' => fn (SalesWeekAuto $set): string => SHtml::link($set->name, $this::URL_PREFIX . '/' . $set->link, ['target' => '_blank']),
        ],
        [
            'header' => 'Ссылка',
            'type' => 'raw',
            'value' => fn (SalesWeekAuto $set): string => SHtml::link($this::URL_PREFIX . '/' . $set->link, $this::URL_PREFIX . '/' . $set->link, ['target' => '_blank']),
        ],
        [
            'name' => 'created',
            'filter' => false,
        ],
        [
            'name' => 'sort',
            'filter' => false,
        ],
        // @todo вместо чекбокса переключатель как в телефоне
        [
            'name' => 'active',
            'type' => 'raw',
            'value' => fn (SalesWeekAuto $set): string => '<input type="checkbox" name="checkbox_active" class="salesWeekItemToggleActive" data-id="' . $set->id . '" value="1" ' . ($set->active ? 'checked' : '') . '>',
        ],
        [
            'type' => 'raw',
            'value' => fn (SalesWeekAuto $set): string => implode(' / ', [
                SHtml::link('Редактировать', ['edit', 'id' => $set->id]),
                SHtml::link('Удалить', ['delete', 'id' => $set->id], ['class' => 'bootbox-confirm', 'data-message' => 'Вы действительно хотите удалить эту подборку?']),
            ]),
        ],
    ],
]);

?>

<script>
    $(function () {
        $('.salesWeekItemToggleActive').on('change', function (e) {
            let activeNewValue = $(this).prop('checked')?1:0;
            let modelId = $(this).data('id');

            $.ajax({
                type: "POST",
                url: "/mp/salesWeekManager/default/updateActive",
                data: {'id' : modelId, 'value': activeNewValue},
                success: (response) => {
                    if (response.result) {
                        Utils.alert(activeNewValue ? 'Подборка включена' : 'Подборка выключена', 'success');
                    }else if(response){
                        Utils.handleAjaxError(response);
                    }else{
                        Utils.alert('Неизвестная ошибка.', 'error');
                    }
                },
                error: function (resp) {
                    global.Utils.handleAjaxError(resp);
                }
            })
        });

    });
</script>
