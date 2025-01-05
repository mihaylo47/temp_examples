<?php
declare(strict_types=1);

/**
 * @var YiiApp\modules\salesWeekAuto\controllers\DefaultController $this
 * @var array                                                      $items
 **/
?>

<div class="sales-week-index">
    <h1><?= $this->pageTitle; ?></h1>

    <div class="accordion-wrapper grid">
        <?php foreach ($items as $code => $item): ?>
            <a href="<?= '/sales-week-auto/' . $code; ?>" class="accordion-item " style="background-image: url(<?= $item['picture'] ?? 0; ?>)">
                <span class="accordion-item__name" style="background-color: #94883480"><?= $item['name']; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

</div>
