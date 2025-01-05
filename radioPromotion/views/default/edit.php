<?php

use Sp\Goods\GoodsPrice;
use Sp\Sp;
use YiiApp\components\SHtml;
use YiiApp\modules\mp\modules\radioPromotion\models\RadioPromotion;

/**
 * @var YiiApp\modules\mp\modules\radioPromotion\controllers\DefaultController $this
 * @var ?radioPromotion                                                        $radioPromotion
 * @var Goods[]                                                                $goods
 */
?>

<style>

    .radio-promotion-manager .field-info {
        margin-top: 20px;
    }

    .radio-promotion-manager .field-header {
        font-weight: bold;
        margin-bottom: 6px;
    }

    .radio-promotion-manager input {
        padding: 8px 10px;
    }

    .col-holder {
        display: flex;
        box-sizing: border-box;
        width: 800px;
    }

    .col-holder .field-info {
        width: 50%;
        box-sizing: border-box;
    }

    .radio-promotion-manager input[type=text], .radio-promotion-manager select {
        width: 360px;
    }

</style>

<a href="/mp/radioPromotion/"> Вернуться в список акций </a>

<?php if ($radioPromotion): ?>
    <h2 class="main-header">Акция "<?= $radioPromotion->name; ?>"</h2>
<?php else: ?>
    <h2 class="main-header">Новая акция</h2>
<?php endif; ?>

<div class="radio-promotion-manager">
    <?= SHtml::form('/mp/radioPromotion/default/save/' . ($radioPromotion ? $radioPromotion->id : '0'), 'POST', ['id' => 'radioPromotionForm']); ?>

    <div class="well">
        <div class="col-holder">
            <div class="field-info">
                <div class="field-header">Название акции</div>
                <div>
                    <input type="text" id="valueRadioPromotionName"
                           value="<?= $radioPromotion ? $radioPromotion->name : ''; ?>"/>
                </div>
            </div>
            <div class="field-info">
                <div class="field-header">Ссылка</div>
                <div>
                    <input type="text" id="valueRadioPromotionLink"
                           value="<?= $radioPromotion ? $radioPromotion->link : ''; ?>"/>
                </div>
            </div>
        </div>

        <div class="col-holder">
            <div class="field-info">
                <div class="field-header">Метод сортировки</div>
                <div>
                    <?= SHtml::dropDownList('valueRadioPromotionSortMethod', $radioPromotion?->sortMethod, RadioPromotion::SORT_LIST, [
                        'id' => 'valueRadioPromotionSortMethod',
                    ]); ?>
                </div>
            </div>
            <div class="field-info">
                <div class="field-header">
                    <label style="margin-top: 30px;">
                        <input type="checkbox" id="valueRadioPromotionActive" <?= $radioPromotion && !$radioPromotion->active ? '' : 'checked'; ?> > Активна
                    </label>
                </div>
            </div>
        </div>

        <div class="col-holder">

            <div class="field-info">
                <div class="field-header jsMainBtnHolder">
                    <?= SHtml::htmlButton('Сохранить', ['type' => 'submit', 'class' => 'btn btn-primary requestSaveRadioPromotion', 'data-id' => $radioPromotion ? $radioPromotion->id : 0]); ?>
                    <?php
                    if($radioPromotion) {
                        echo SHtml::link('Удалить', ['delete', 'id' => $radioPromotion->id], ['class' => 'bootbox-confirm', 'data-message' => 'Вы действительно хотите удалить эту акцию?']);
                    }
?>
                </div>
            </div>
            <div class="field-info"></div>
        </div>
        <div class="alert alert-danger alert-mt2 submit-error" role="alert" style="display: none;"></div>
    </div>
    <?= SHtml::endForm(); ?>

    <?php if (count($goods)): ?>
        <h3>Товары акции: </h3>
        <div class="radio-promotion-goods">
            <?php foreach($goods as $good): ?>
                <div class="radio-promotion-good"> #<?=$good->gid; ?> <a target="_blank" href="/good/<?=$good->gid; ?>"><?=$good->getDisplayName(); ?></a> <?=Sp::get(GoodsPrice::class)->getGoodPriceWithFee($good, $good->collection->purchase) . ' р.'; ?>
                    <a href="#" data-promotion-id="<?=$radioPromotion->id; ?>" data-gid="<?=$good->gid; ?>" class="radioPromotionExclude">Удалить</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>


<script>
    $(function () {
        $('#valueRadioPromotionName').on('keyup', function(){
            $('#valueRadioPromotionLink').val(transliterate($(this).val().toLowerCase()));
        });

        $('.requestSaveRadioPromotion').on('click', function (e) {
            e.preventDefault();
            let btn = this;
            if (!$(btn).prop('disabled')) {
                $(btn).prop('disabled', true);

                let data = {
                    'id': $(btn).data('id'),
                    'name': $('#valueRadioPromotionName').val(),
                    'link': $('#valueRadioPromotionLink').val(),
                    'active': $('#valueRadioPromotionActive').prop('checked')?1:0,
                    'sortMethod': $('#valueRadioPromotionSortMethod').val()
                }

                $.ajax({
                    type: "POST",
                    url: "/mp/radioPromotion/default/save",
                    data: data,
                    success: (response) => {
                        if (response.result) {
                            Utils.alert('Акция сохранена', 'success');

                            $('.main-header').text('Акция "'+data.name+'"');
                            if (data.id === '0'){
                                $(btn).data('id', response.data.id);
                                $('.jsMainBtnHolder').append('<a href="/mp/radioPromotion/default/delete/id/'+response.data.id+'" class="bootbox-confirm" data-message="Вы действительно хотите удалить эту акцию?">Удалить</a>')
                            }
                            location.href = '/mp/radioPromotion';
                        } else if (response) {
                            Utils.handleAjaxError(response);
                        } else {
                            Utils.alert('Неизвестная ошибка.', 'error');
                        }
                    },
                    complete: () => {}
                })
            }
        });

        $('.radioPromotionExclude').on('click', function (e) {
            e.preventDefault();
            let btn = this;
            if (!$(btn).prop('disabled')) {
                $(btn).addClass('loading').prop('disabled', true);

                let data = {
                    'promotionId': $(btn).data('promotion-id'),
                    'gid': $(btn).data('gid'),
                }

                $.ajax({
                    type: "POST",
                    url: "/mp/radioPromotion/default/removeFromPromotion",
                    data: data,
                    success: (response) => {
                        if (response.result) {
                            Utils.alert('Товар удален из акции', 'success');
                            $(btn).parents('.radio-promotion-good:first').remove();
                        } else if (response) {
                            Utils.handleAjaxError(response);
                        } else {
                            Utils.alert('Неизвестная ошибка.', 'error');
                        }
                    },
                    complete: () => {
                        $(btn).prop('disabled', false);
                    }
                })
            }
        });
    });

    function transliterate(word, saveSpecialChars = false){
        var answer = "";
        var a = {
            "а": "a", "б": "b", "в": "v", "г": "g", "д": "d", "е": "e", "ё": "yo",
            "ж": "zh", "з": "z", "и": "i", "й": "y", "к": "k", "л": "l", "м": "m",
            "н": "n", "о": "o", "п": "p", "р": "r", "с": "s", "т": "t", "у": "u",
            "ф": "f", "х": "h", "ц": "ts", "ч": "ch", "ш": "sh", "щ": "sch",
            "ь": "", "ы": "y", "ъ": "", "э": "e", "ю": "yu", "я": "ya",
            " ": "-", "_": "-",
        };
        for (i in word){
            if (word.hasOwnProperty(i)) {
                if (a[word[i]] === undefined){
                    if (word[i].match('[0-9a-z]') || saveSpecialChars) {
                        answer += word[i];
                    }
                    // если не saveSpecialChars - остальные символы игнорируем!
                } else {
                    answer += a[word[i]];
                }
            }
        }
        return answer;
    }

    function nl2br( str ) {
        return str.replace(/([^>])\n/g, '$1<br/>');
    }
</script>
