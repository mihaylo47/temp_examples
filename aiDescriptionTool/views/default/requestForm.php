<?php

/**
 * @see \YiiApp\modules\org\modules\aiDescriptionTool\controllers\DefaultController::actionAiRequestForm()
 *
 * @var YiiApp\modules\org\modules\aiDescriptionTool\controllers\DefaultController $this
 * @var string                                                                     $requestInstruction
 */

use Sp\Sp;
use YiiApp\components\SHtml;
use YiiApp\modules\org\modules\purchase\widgets\MeasuresWidget\MeasuresWidget;
use YiiApp\widgets\HorizontalSlider\HtmlSlider;

Yii::app()->clientScript->requireWebpackModule('ai-helper');

$brandDefaultChecked = ($this->good->getPublicBrand()?->getDisplayName() ?? '') ? 'checked' : '';
$categoryDefaultChecked = ($this->good->category->name ?? '') ? 'checked' : '';
$collectionDefaultChecked = $this->good->collection->name ? 'checked' : '';
$descriptionDefaultChecked = $this->good->description ? 'checked' : '';
$priceLabel = $this->aiServicePrice . ' ' . Sp::number($this->aiServicePrice, 'балл|балла|баллов', false);
$balanceLabel = $this->balance . ' ' . Sp::number($this->balance, 'балл|балла|баллов', false);
?>

<div class="ai-helper">
    <section class="ai-helper-annotations">
        <h2>Автопомощник оформления товара</h2>
        <div class="ai-helper-annotation">
            Нейросеть может помочь составить описание Вашего товара на основе фотографии и исходного описания. <br>
            Нейросеть в первую очередь смотрит на изображение, поэтому выберите наиболее удачное. <br>
            На фото не обязательно должен быть только товар, это может быть человек в той самой майке или игрушка робот на столе с другими игрушками. <br>
            Название товара должно помочь нейросети найти и составить описание именно того, что необходимо. <br>
            В качестве инструкций можно просить нейросеть сделать стилизованное описание в том или ином виде, можно указать на что обратить внимание или учесть.
        </div>
        <div class="ai-helper-annotation">
            В левой части формы указаны заполненные для товара данные. <br>
            Все отмеченные галочками данные будут отправлены нейросети как входные данные. <br>
            При желании данные о товаре можно не отправлять или изменить (эти изменения не повлияют на описание товара).
        </div>
        <div class="ai-helper-annotation">
            Каждый раз после нажатия на кнопку получить с Вашего баланса списываются баллы. <br>
            Полученное от нейросети описание можно подредактировать или оставить как есть и после этого применить к товару (сохранить изменения). <br>
            Важно! Последующий импорт и подбор перезапишет поля, не забудьте экспортировать новые данные!
        </div>
    </section>

    <section class="ai-helper-wrapper">
        <div class="ai-helper-side ai-request-form">
            <div class="field-info">
                <div> Выберите самое подходящее фото</div>
                <?php $imageIdList = $this->good->getPictures(); ?>
                <?php $picId = $imageIdList[0] ?? 0; ?>
                <?php $items = array_map(fn ($imageId) => '<div class="ai-helper-image' . ($picId == $imageId ? ' selected' : '') . '" data-id="' . $imageId . '">' . SHtml::getImageTag($imageId, Pictures::THUMB_300) . '</div>', $imageIdList); ?>
                <?php $this->widget(HtmlSlider::class, [
                    'items' => $items,
                    'cssClass' => 'ai-helper-slider',
                    'withArrows' => true,
                    'scrollByEveryItem' => true,
                ]); ?>
            </div>
        </div>
        <div class="ai-helper-side ai-response-form"></div>
    </section>

    <section class="ai-helper-wrapper">
        <div class="ai-helper-side ai-request-form">
            <div class="well">
                <?= SHtml::form('/org/aiDescriptionTool/sendRequest/' . $this->good->gid, 'POST', ['id' => 'aiHelperForm']); ?>
                <input type="hidden" id="picId" name="picId" value="<?= $picId; ?>">
                <input type="hidden" id="gid" name="gid" value="<?= $this->good->gid; ?>">

                <div class="field-info">
                    <div>
                        <label>
                            <input type="checkbox" id="useNameCheck" checked>Название товара
                        </label>
                    </div>
                    <div>
                        <input type="text" id="valueName" value="<?= $this->good->getDisplayName(); ?>"/>
                    </div>
                </div>

                <?php if ($this->good->getJsonData()->measuresPreset || $this->good->getJsonData()->height || $this->good->getJsonData()->netto): ?>
                    <div class="field-info">
                        <div>Габариты</div>
                        <div style="margin-top:10px;">
                            <?php if ($this->good->getJsonData()->measuresPreset ?? null): ?>
                                Размер <?= MeasuresWidget::MEASURES_PRESETS[$this->good->getJsonData()->measuresPreset] ?? '-'; ?>
                            <?php elseif ($this->good->getJsonData()->height ?? null): ?>
                                Размеры <?= $this->good->getJsonData()->width ?? 0; ?> x <?= $this->good->getJsonData()->height ?? 0; ?> x <?= $this->good->getJsonData()->depth ?? 0; ?> см
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:10px;">
                            <?php if ($this->good->getJsonData()->netto ?? null): ?>
                                Вес: <?= $this->good->getJsonData()->netto ?? 0; ?> кг
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" id="measuresExist" value="1">
                <?php else: ?>
                    <input type="hidden" id="measuresExist" value="0">
                <?php endif; ?>

                <div class="field-info">
                    <div>
                        <label>
                            <input type="checkbox" id="useBrandCheck" <?= $brandDefaultChecked; ?>>Бренд
                        </label>
                    </div>
                    <div>
                        <input type="text" id="valueBrand" value="<?= $this->good->getPublicBrand()?->getDisplayName() ?? ''; ?>"/>
                    </div>
                </div>

                <div class="field-info">
                    <div>
                        <label>
                            <input type="checkbox" id="useCategoryCheck" <?= $categoryDefaultChecked; ?>>Категория
                        </label>
                    </div>
                    <div>
                        <input type="text" id="valueCategory" value="<?= $this->good->category->name ?? ''; ?>"/>
                    </div>
                </div>

                <div class="field-info">
                    <div>
                        <label>
                            <input type="checkbox" id="useCollectionCheck" <?= $collectionDefaultChecked; ?>>Коллекция
                        </label>
                    </div>
                    <div>
                        <input type="text" name="valueCollection" id="valueCollection" value="<?= $this->good->collection->getDisplayName(); ?>"/>
                    </div>
                </div>

                <div class="field-info">
                    <div>
                        <label>
                            <input type="checkbox" id="useDescriptionCheck" <?= $descriptionDefaultChecked; ?>>Описание товара
                        </label>
                    </div>
                    <div>
                        <textarea id="valueDescription"><?= $this->good->description; ?></textarea>
                    </div>
                </div>

                <div class="field-info">
                    <div>Инструкция для нейросети <span class="required">*</span></div>
                    <div>
                        <textarea id="requestInstruction"><?= $requestInstruction; ?></textarea>
                    </div>
                </div>
            </div>

            <?php if ($this->pay && $this->balance < $this->aiServicePrice): ?>
                <div class="alert alert-danger alert-mt2" role="alert">
                    Стоимость операции <?= $priceLabel; ?>. У вас не достаточно баллов для продолжения. <br/>
                    Ваш текущий баланс: <?= $balanceLabel; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info alert-mt2" role="alert">
                    <?php if ($this->pay): ?>
                        С вашего баланса будет списано <b><?= $priceLabel; ?></b>
                    <?php else: ?>
                        Стоимость операции <?= $priceLabel; ?>. В режиме модератора <b>операция бесплатна</b>
                    <?php endif; ?>
                    <br/>Ваш текущий баланс: <?= $balanceLabel; ?>
                </div>
                <div class="alert alert-danger alert-mt2 submit-error" role="alert" style="display: none;"></div>
                <div class="form-actions ai-request-form__actions">
                    <?php $this->widget(TbButton::class, [
                        'type' => TbButton::TYPE_LINK,
                        'url' => $this->good->getUrl(),
                        'label' => 'Назад',
                    ]); ?>
                    <?= SHtml::htmlButton('Получить описание <i class="bi-arrow-right"></i>', ['type' => 'submit', 'class' => 'btn btn-primary', 'id' => 'sendRequest']); ?>
                </div>
            <?php endif; ?>
            <?= SHtml::endForm(); ?>

            <div class="alert request-list" style="display: none;"></div>
        </div>

        <div class="ai-helper-side ai-response-form">
            <div class="ai-helper-spinner-holder" id="responseLoader" style="display: none;">
                <div class="ai-helper-spinner"></div>
            </div>
            <div id="responseHolder" style="display: none;">
                <?= SHtml::form('/org/aiDescriptionTool/resultAccept/' . $this->good->gid, 'POST', ['id' => 'aiHelperResultAccept']); ?>
                <div class="well">
                    <div class="field-info">
                        <div>
                            <label>
                                <input type="checkbox" id="useNewNameCheck" name="useNewNameCheck" checked>Название товара
                            </label>
                        </div>
                        <div>
                            <input type="text" id="responseName" name="responseName" value=""/>
                        </div>
                    </div>
                    <div class="field-info measures-holder">
                        <div>
                            <label>
                                <input type="checkbox" id="useNewMeasuresCheck" name="useNewMeasuresCheck" checked>Габариты товара
                            </label>
                        </div>
                        <div>
                            <div class="control-group">
                                <label>Вес товара (кг)</label>
                                <div class="side-sub-wrapper">
                                    <div><input type="text" id="responseWeightRaw" readonly value=""/></div>
                                    <div>=></div>
                                    <div><input type="number" step="any" id="responseWeight" name="responseWeight"
                                                value=""/></div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label>Объем товара (л)</label>
                                <div class="side-sub-wrapper">
                                    <div><input type="text" id="responseVolumeRaw" readonly value=""/></div>
                                    <div>=></div>
                                    <div><input type="number" step="any" id="responseVolume" name="responseVolume"
                                                value=""/></div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label>Размеры (см)</label>
                                <div class="side-sub-wrapper">
                                    <div><input type="text" id="responseDimensionsRaw" readonly value=""/></div>
                                    <div>=></div>
                                    <div>
                                        <input type="number" step="any" id="responseWidth" name="responseWidth" value=""/>
                                        <input type="number" step="any" id="responseHeight" name="responseHeight" value=""/>
                                        <input type="number" step="any" id="responseDepth" name="responseDepth" value=""/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="field-info">
                        <div>
                            <label>
                                <input type="checkbox" id="useNewDescriptionCheck" name="useNewDescriptionCheck"
                                       checked>Описание товара
                            </label>
                        </div>
                        <div>
                            <textarea id="responseDescription" name="responseDescription"></textarea>
                        </div>
                    </div>

                    <div class="field-info usage-holder">
                        <div>Расход токенов</div>
                        <div class="token-div">Токенов в запросе: <span id="promptTokens"></div>
                        <div class="token-div">Токенов в ответе: <span id="completionTokens"></div>
                    </div>

                </div>
                <div class="alert alert-mt2">
                    Внимательно изучите полученный ответ. При необходимости внесите изменения и отметьте галочкой поля, которые следует заменить в товаре.
                    <br><br>
                    Вы так же можете внести изменения в исходный запрос и сгенерировать новое описание.
                </div>
                <div class="form-actions ai-response-form__actions">
                    <?= SHtml::submitButton('Сохранить изменения', ['class' => 'btn btn-success btn-large', 'id' => 'acceptResults']); ?>
                </div>
                <?= SHtml::endForm(); ?>
            </div>
        </div>
    </section>
</div>
