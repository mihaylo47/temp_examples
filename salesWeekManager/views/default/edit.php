<?php

use YiiApp\components\SHtml;

/**
 * @var YiiApp\modules\mp\modules\salesWeekManager\controllers\DefaultController $this
 * @var ?SalesWeekAuto                                                           $salesWeekAuto
 * @var ?array                                                                   $groupOptionsMap
 * @var ?array                                                                   $groupList
 */
?>

<style>

    .sales-week-manager .field-info {
        margin-top: 20px;
    }

    .sales-week-manager .field-header {
        font-weight: bold;
        margin-bottom: 6px;
    }

    .sales-week-manager input, .sales-week-manager textarea {
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

    .sales-week-manager input[type=text], .sales-week-manager select {
        width: 360px;
    }

    .sales-week-manager textarea {
        width: 760px;
        height: 80px;
    }
    .sales-week-manager .well2{
        background-color: #ffffff;
        border: 1px dashed #999999;
        margin-bottom: 20px;
    }
    .sales-week-manager .add-group-btn-holder{
        margin-bottom: 20px;
    }

    .sales-week-manager .group-filter-header{
        margin: 30px 0 20px;
        color: #666;
        font-weight: bold;
        font-size: 1.2em;
    }

    .sales-week-manager .group-btn-top-menu{
        display: block;
        float: right;
    }

    .sales-week-manager .filter-tag-area{
        margin-top: 20px;
    }

    .sales-week-manager .filter-desc{
        color: #999999;
        margin-top: 10px;
    }

    .group-btn-holder .btn{
        margin-left: 20px;
    }

    .jsMainBtnHolder .btn{
        margin-right: 20px;
    }
</style>

<a href="/mp/salesWeekManager/"> Вернуться в список подборок </a>
<?php if ($salesWeekAuto): ?>
    <h2 class="main-header">Подборка: <?= $salesWeekAuto->name; ?></h2>
<?php else: ?>
    <h2 class="main-header">Новая подборка</h2>
<?php endif; ?>

<div class="sales-week-manager">
    <?= SHtml::form('/mp/salesWeekManager/default/save/' . ($salesWeekAuto ? $salesWeekAuto->id : '0'), 'POST', ['id' => 'salesWeekAutoForm']); ?>

    <div class="well">

        <div class="col-holder">
            <div class="field-info">
                <div class="field-header">Название подборки</div>
                <div>
                    <input type="text" id="valueSalesWeekAutoName"
                           value="<?= $salesWeekAuto ? $salesWeekAuto->name : ''; ?>"/>
                </div>
            </div>

            <div class="field-info">
                <div class="field-header">Ссылка</div>
                <div>
                    <input type="text" id="valueSalesWeekAutoLink"
                           value="<?= $salesWeekAuto ? $salesWeekAuto->link : ''; ?>"/>
                </div>
            </div>
        </div>

        <div class="field-info">
            <!-- @todo картинку надо аплоадер прикрутить -->
            <div class="field-header">ID картинки</div>
            <div>
                <input type="text" id="valueSalesWeekAutoPicid"
                       value="<?= $salesWeekAuto ? $salesWeekAuto->picid : ''; ?>"/>
            </div>
        </div>

        <div class="col-holder">
            <div class="field-info">
                <div class="field-header">
                    <label>
                        <input type="checkbox" id="valueSalesWeekAutoActive" <?= $salesWeekAuto && $salesWeekAuto->active ? 'checked' : ''; ?> >Включить
                    </label>
                </div>
            </div>
            <div class="field-info">
                <div class="field-header jsMainBtnHolder">
                    <?= SHtml::htmlButton('Сохранить', ['type' => 'submit', 'class' => 'btn btn-primary requestSaveSalesWeek', 'data-id' => $salesWeekAuto ? $salesWeekAuto->id : 0]); ?>
                    <?php
                    if($salesWeekAuto) {
                        echo SHtml::link('Удалить', ['delete', 'id' => $salesWeekAuto->id], ['class' => 'bootbox-confirm', 'data-message' => 'Вы действительно хотите удалить эту подборку?']);
                    }
?>
                </div>
            </div>
        </div>
        <div class="alert alert-danger alert-mt2 submit-error" role="alert" style="display: none;"></div>
    </div>

    <?= SHtml::endForm(); ?>

    <div class="add-group-btn-holder">
        <a class="btn btn-secondary jsAddGroup" href="#"><i class="bi-plus-square-dotted"></i> Добавить группу товаров</a>
    </div>


    <div class="group-holder"><!-- dynamic data--></div>

    <!-- this is html template for group -->
    <div class="" id="template-group" style="display: none;">
        <div class="well well2" data-id="0">
            <div class="col-holder">
                <div class="field-info">
                    <div class="field-header">Название</div>
                    <div>
                        <input type="text" name="groupName" class="groupNameValue" value=""/>
                    </div>
                </div>

                <div class="field-info">
                    <div class="field-header">Ссылка</div>
                    <div>
                        <input type="text" name="groupLink"  class="groupLinkValue" value=""/>
                    </div>
                </div>
            </div>

            <div class="field-info">
                <div class="field-header">Описание</div>
                <div>
                    <textarea name="groupNote" class="groupNoteValue"></textarea>
                </div>
            </div>

            <div class="group-filter-header">Критерии</div>

            <div class="group-filter-holder">
                <?php foreach ($groupOptionsMap as $filterCode => $filterOptions): ?>
                    <div class="field-info" style="display: none;">
                        <div class="field-header"><?=$filterOptions['title']; ?></div>
                        <div>
                            <?php if ($filterOptions['type'] == 'string' || $filterOptions['type'] == 'int[]'): ?>
                                <input type="text" data-filter="<?= $filterCode; ?>" class="groupFilter<?= $filterCode; ?>" value=""/>
                            <?php elseif ($filterOptions['type'] == 'select'): ?>
                                <select data-filter="<?= $filterCode; ?>" class="groupFilter<?= $filterCode; ?>">
                                    <?php foreach ($filterOptions['values'] as $value => $name): ?>
                                        <option value="<?= $value; ?>"><?= $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <a style="display: inline-block;" class="btn btn-secondary jsDelFilter"  data-ident="<?= $filterCode; ?>" href="#"><i class="bi-trash3"></i></a>
                        </div>
                        <?php if (isset($filterOptions['desc']) && $filterOptions['desc'] != ''): ?>
                        <div class="filter-desc"><?= nl2br($filterOptions['desc']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="filter-tag-area">
                <?php foreach ($groupOptionsMap as $filterCode => $filterOptions): ?>
                    <a class="btn btn-secondary jsFilterBtn jsFilterBtn<?= $filterCode; ?>" href="#" data-ident="<?= $filterCode; ?>">
                        <?= $filterOptions['title']; ?>
                    </a>
                <?php endforeach; ?>
            </div>


            <div class="field-info group-btn-holder">
                <div class="field-header" style="display: inline-block; margin-right: 20px;">
                    <label> <input type="checkbox" name="groupActive" class="groupActiveValue" checked>Включить </label>
                </div>
                <a href="#" class="jsTestGroup">Проверить результат</a>

                <?= SHtml::htmlButton('Сохранить', ['type' => 'submit', 'class' => 'btn btn-primary jsSaveGroup']); ?>
                <?= SHtml::htmlButton('<i class="bi-trash3"></i> Удалить группу', [
                    'type' => 'submit',
                    'class' => 'btn btn-secondary jsDelGroup',
                    'data-message' => 'Вы уверены, что хотите удалить эту группу?',
                ]); ?>

            </div>
        </div>
    </div>

    <div class="add-group-btn-holder">
        <a class="btn btn-secondary jsAddGroup" href="#"><i class="bi-plus-square-dotted"></i> Добавить группу товаров</a>
    </div>

</div>


<script>
    let salesWeekAutoId = parseInt("<?= $salesWeekAuto ? $salesWeekAuto->id : '0'; ?>");
    let groupFiltersMap = <?= json_encode($groupOptionsMap, JSON_UNESCAPED_UNICODE) ?: null; ?>;
    let groupList = <?= json_encode(array_map(fn (SalesWeekAutoItem $item) => $item->toArray(), $groupList), JSON_UNESCAPED_UNICODE|JSON_HEX_QUOT); ?>;
    $(function () {

        $('#valueSalesWeekAutoName').on('keyup', function(){
            $('#valueSalesWeekAutoLink').val(transliterate($(this).val().toLowerCase()));
        });

        $('.group-holder').on('keyup', '.groupNameValue', function (e) {
            let groupBlock = $(this).parents('.well2:first');
            $(groupBlock).find('.groupLinkValue').val(transliterate($(this).val().toLowerCase()));
        });


        $('.requestSaveSalesWeek').on('click', function (e) {
            e.preventDefault();
            let btn = this;
            if (!$(btn).prop('disabled')) {
                $(btn).prop('disabled', true);

                // @todo валидация
                let data = {
                    'id': $(btn).data('id'),
                    'name': $('#valueSalesWeekAutoName').val(),
                    'link': $('#valueSalesWeekAutoLink').val(),
                    'picid': $('#valueSalesWeekAutoPicid').val(),
                    'active': $('#valueSalesWeekAutoActive').prop('checked')?1:0
                }

                $.ajax({
                    type: "POST",
                    url: "/mp/salesWeekManager/default/save",
                    data: data,
                    success: (response) => {
                        if (response.result) {
                            Utils.alert('Подборка сохранена', 'success');

                            $('.main-header').text('Подборка: '+data.name);
                            if (data.id == 0){
                                salesWeekAutoId = response.data.id;
                                $(btn).data('id', salesWeekAutoId);
                                $('.jsMainBtnHolder').append('<a href="/mp/salesWeekManager/default/delete/id/'+salesWeekAutoId+'" class="bootbox-confirm" data-message="Вы действительно хотите удалить эту подборку?">Удалить</a>')

                            }
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

        $('.group-holder').on('click', '.jsFilterBtn', function (e) {
            let ident = $(this).data('ident');
            let groupBlock = $(this).parents('.well2:first');

            if (groupFiltersMap[ident] === undefined){
                Utils.alert('Фильтр '+ident+' не найден', 'error');
                return false;
            }

            showFilter(groupBlock, ident);
            return false;

        }).on('click', '.jsDelFilter', function (e) {
            let ident = $(this).data('ident');
            let groupBlock = $(this).parents('.well2:first');

            if (groupFiltersMap[ident] === undefined){
                Utils.alert('Фильтр '+ident+' не найден', 'error');
                return false;
            }
            //setEmptyFilterValue(groupBlock, ident);
            hideFilter(groupBlock, ident);
            $(groupBlock).find('.jsFilterBtn'+ident).show();
            return false;

        }).on('click', '.jsSaveGroup', function (e) {
            e.preventDefault();
            let groupBlock = $(this).parents('.well2:first');
            requestSaveGroup(this, groupBlock);

        }).on('click', '.jsTestGroup', function (e) {
            e.preventDefault();

            let groupSelector = $(this).parents('.well2:first');

            let data = {
                'group': $(groupSelector).find('.groupNameValue').val(),
            };
            $(groupSelector).find('.group-filter-holder .field-info').each(function(){
                if ($(this).css('display')!=="none") {
                    // @todo надо придумать более красивое решение
                    let input = $(this).find('input')
                    if ($(input).length) {
                        data['request['+$(input).data('filter')+']'] = $(input).val();
                    } else {
                        let select = $(this).find('select')
                        if ($(select).length) {
                            data['request['+$(select).data('filter')+']'] = $(select).val();
                        }
                    }
                }
            });

            const params = new URLSearchParams(data);
            window.open('/sales-week-auto/custom/?'+params.toString(), 'testGroup')

        }).on('click', '.jsDelGroup', function (e) {
            let groupSelector = $(this).parents('.well2:first');
            let name = $(groupSelector).find('.groupNameValue').val();
            name = name?name:'без названия';
            $(this).data('message', 'Вы уверены, что хотите удалить группу "'+name+'"?')

        });

        $('.jsAddGroup').on('click', function(){
            addNewGroup(true);
            return false;
        });

        /// on pageLoad init group blocks
        if (groupList.length){
            for (let i in groupList){
                addGroup(groupList[i]);
            }
        }else{
            addNewGroup();
        }

        function requestSaveGroup(btn, groupSelector){
            if (!salesWeekAutoId){
                Utils.alert('Сначала сохраните подборку', 'error');
                return false;
            }
            if (!$(btn).prop('disabled')) {
                $(btn).prop('disabled', true);

                let data = {
                    'id': $(groupSelector).data('id'),
                    'name': $(groupSelector).find('.groupNameValue').val(),
                    'link': $(groupSelector).find('.groupLinkValue').val(),
                    'note': $(groupSelector).find('.groupNoteValue').val(),
                    'active': $(groupSelector).find('.groupActiveValue').prop('checked')?"1":"0",
                    'connectedId': salesWeekAutoId
                }
                let filter = {};
                $(groupSelector).find('.group-filter-holder .field-info').each(function(){
                    if ($(this).css('display')!=="none") {
                        // @todo надо придумать более красивое решение
                        let input = $(this).find('input');
                        if ($(input).length) {
                            filter[$(input).data('filter')] = $(input).val();
                        } else {
                            let select = $(this).find('select')
                            if ($(select).length) {
                                filter[$(select).data('filter')] = $(select).val();
                            }
                        }
                    }
                });
                data.filter = filter;
                // @todo валидация
                $.ajax({
                    type: "POST",
                    url: "/mp/salesWeekManager/default/saveGroup",
                    data: data,
                    success: (response) => {
                        if (response.result) {
                            Utils.alert('Группа '+data.name+' сохранена', 'success');
                            $(groupSelector).data('id', response.data.id);
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
        }



        function addNewGroup(scrollTo = false){
            addGroup({
                active: 1, // true не сработает
                name: '',
                link: '',
                note: '',
                filterList: [],
            }, scrollTo);
        }

        function addGroup(groupData, scrollTo = false){
            let template = $('#template-group').children(0).clone();
            let groupBlock = template.clone();
            $(groupBlock).data('id', groupData.id);
            $(groupBlock).find('.groupActiveValue').prop('checked', !!parseInt(groupData.active));
            $(groupBlock).find('.groupNameValue').val(groupData.name);
            $(groupBlock).find('.groupLinkValue').val(groupData.link);
            $(groupBlock).find('.groupNoteValue').val(groupData.note);
            for (let filterIdent in groupData.filterList){
                let filterValue = groupData.filterList[filterIdent];

                if (groupFiltersMap[filterIdent] !== undefined) {
                    if (groupFiltersMap[filterIdent]['type'] === 'select') {
                        $(groupBlock).find('.groupFilter' + filterIdent + ' option[value=' + filterValue + ']').prop('selected', true);
                    } else if (groupFiltersMap[filterIdent]['type'] === 'int[]') {
                        $(groupBlock).find('.groupFilter' + filterIdent).val(filterValue.join(', '));
                    } else {
                        $(groupBlock).find('.groupFilter' + filterIdent).val(filterValue);
                    }

                    if (groupFiltersMap[filterIdent]['defaultVisible'] || filterValue) {
                        showFilter(groupBlock, filterIdent);
                    }
                }
            }

            $('.group-holder').append(groupBlock);

            if (scrollTo) {
                $('html, body').animate({
                    scrollTop: $(groupBlock).offset().top - (sp.isMobileVersion() ? $('.header-top').height() : 0)
                }, 400, function(){ $(groupBlock).find('.groupNameValue').trigger( "focus" ); });
            }
        }

        function showFilter(groupBlock, ident){
            $(groupBlock).find('.groupFilter'+ident).parents('.field-info:first').show();
            //$(groupBlock).find('.jsFilterBtn'+ident).hide();
            $(groupBlock).find('.jsFilterBtn'+ident).animate({opacity: 0}, 500, ()=>$(groupBlock).find('.jsFilterBtn'+ident).hide().css('opacity',1));
        }

        function hideFilter(groupBlock, ident){
            $(groupBlock).find('.groupFilter'+ident).parents('.field-info:first').hide();
            setEmptyFilterValue(groupBlock, ident);
        }

        function setEmptyFilterValue(groupBlock, ident){
            if (groupFiltersMap[ident]['type']==='select'){
                $(groupBlock).find('.groupFilter'+ident+' option').prop('selected', false);
            }else{
                $(groupBlock).find('.groupFilter'+ident).val('');
            }
        }


        sp.confirmDialog('.jsDelGroup', {
            callback: function (btn) {

                let groupSelector = $(btn).parents('.well2:first');
                let name = $(groupSelector).find('.groupNameValue').val();
                btn.addClass('loading');
                if (!$(btn).prop('disabled')) {
                    $(btn).prop('disabled', true);

                    let id = parseInt($(groupSelector).data('id'));
                    if (id===0) {
                        $(groupSelector).remove();
                        // если нет ни одной, открываем пустую форму группы
                        if (!$('.group-holder .well2').length){
                            addNewGroup();
                        }
                    }else{
                        $.ajax({
                            type: "POST",
                            url: "/mp/salesWeekManager/default/deleteGroup",
                            data: {'id': id},
                            success: (response) => {
                                if (response.result) {
                                    $(groupSelector).remove();
                                    // если нет ни одной, открываем пустую форму группы
                                    if (!$('.group-holder .well2').length){
                                        addNewGroup();
                                    }
                                    Utils.alert('Группа #'+response.data.name+' удалена', 'success');
                                } else if (response) {
                                    Utils.handleAjaxError(response);
                                } else {
                                    Utils.alert('Неизвестная ошибка.', 'error');
                                }
                            },
                            complete: () => {
                                if ($(btn).length) {
                                    $(btn).removeClass('loading').prop('disabled', false);
                                }
                            }
                        });
                    }
                }
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
