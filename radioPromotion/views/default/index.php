<?php

use YiiApp\components\SHtml;
use YiiApp\modules\mp\modules\radioPromotion\models\RadioPromotion;

/**
 * @var YiiApp\modules\mp\modules\radioPromotion\controllers\DefaultController $this
 * @var CActiveDataProvider                                                    $radioPromotionProvider
 */
?>

<style>
    .btn-holder{
        text-align: right;
    }

</style>

<h2>Рекламные акции</h2>

<div class="btn-holder">
    <a class="btn btn-secondary" href="/mp/radioPromotion/default/new"><i class="bi-plus-square-dotted"></i> Добавить акцию</a>
</div>

<?php $this->widget(TbGridView::class, [
    'dataProvider' => $radioPromotionProvider,
    'ajaxUpdate' => false,
    'columns' => [
        [
            'header' => 'ID',
            'name' => 'id',
        ],
        [
            'header' => 'Название',
            'name' => 'name',
        ],
        [
            'header' => 'Ссылка',
            'type' => 'raw',
            'value' => fn (RadioPromotion $radioPromotion): string => SHtml::link('/radioPromotion/' . $radioPromotion->id . '/' . $radioPromotion->link, '/radioPromotion/' . $radioPromotion->id . '/' . $radioPromotion->link, ['target' => '_blank']),
        ],
        [
            'name' => 'created',
            'filter' => false,
        ],
        [
            'header' => 'Товаров',
            'type' => 'raw',
            'value' => fn (RadioPromotion $radioPromotion): string => '' . count($radioPromotion->goods),
        ],
        [
            'header' => 'Сортировка',
            'filter' => false,
            'type' => 'raw',
            'value' => fn (RadioPromotion $radioPromotion): string => RadioPromotion::SORT_LIST[$radioPromotion->sortMethod] ?? '-',
        ],
        [
            'name' => 'active',
            'type' => 'raw',
            'value' => fn (RadioPromotion $radioPromotion): string => '<input type="checkbox" name="checkbox_active" class="radioPromotionToggleActive" data-id="' . $radioPromotion->id . '" value="1" ' . ($radioPromotion->active ? 'checked' : '') . '>',
        ],

        [
            'type' => 'raw',
            'value' => fn (RadioPromotion $radioPromotion): string => implode(' / ', [
                SHtml::link('Редактировать', ['edit', 'id' => $radioPromotion->id]),
                SHtml::link('Удалить', ['delete', 'id' => $radioPromotion->id], ['class' => 'bootbox-confirm', 'data-message' => 'Вы действительно хотите удалить эту акцию?']),
            ]),
        ],
    ],
]);

?>

<script>
    $(function () {
        $('.radioPromotionToggleActive').on('change', function (e) {
            let activeNewValue = $(this).prop('checked')?1:0;
            let modelId = $(this).data('id');

            $.ajax({
                type: "POST",
                url: "/mp/radioPromotion/default/updateActive",
                data: {'id' : modelId, 'value': activeNewValue},
                success: (response) => {
                    if (response.result) {
                        Utils.alert(activeNewValue ? 'Акция запущена' : 'Акция приостановлена', 'success');
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
