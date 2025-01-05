<?php

namespace YiiApp\modules\org\modules\aiDescriptionTool\controllers;

use Goods;
use Legacy\RBAC;
use Pictures;
use Sp\Purchase\ViewContext;
use Sp\Yii\Controller;
use Sp\Yii\IgnoredHttpException;
use Yii;
use YiiApp\components\SHtml;
use YiiApp\modules\org\modules\aiDescriptionTool\components\AIGoodsService;
use YiiApp\modules\org\modules\aiDescriptionTool\components\AIRequest;
use YiiApp\widgets\Breadcrumb\Breadcrumb;

class DefaultController extends Controller
{
    public ?Goods $good;
    public ?float $balance;
    public bool $pay;
    public float $aiServicePrice;

    public function filters(): array
    {
        return [
            'accessControl',
        ];
    }

    public function accessRules(): array
    {
        return [
            [
                'allow',
                'roles' => [
                    RBAC::R_ORG,
                    RBAC::R_MODERATOR,
                    RBAC::R_AGENT_ORG_PURCHASES_ASSISTANT,
                    RBAC::T_CHANGE_PURCHASE_STATUS,
                    RBAC::R_ADMIN,
                ],
            ],
            ['deny', 'users' => ['*']],
        ];
    }

    public function beforeAction($action): bool
    {
        $gid = (int)Yii::app()->request->getParam('gid', 0);

        if (!$gid) {
            $this->notFound('Неверная ссылка');
        }

        $this->good = Goods::model()->with([
            'collection',
            'collection.purchase',
        ])->findByPk($gid);

        if (!$this->good) {
            $this->notFound('Товар не найден');
        }

        $viewContext = new ViewContext($this->good->collection->purchase);
        if (!$viewContext->canEditPurchase) {
            $this->forbidden('У вас нет доступа к этой странице');
        }

        $this->pay = !Yii::app()->user->isAdminOrModerator();

        $user = \Users::model()->findByPk(Yii::app()->user->id);
        $this->balance = $user->account->balance ?? 0;

        $this->aiServicePrice = AIGoodsService::getPrice();

        return parent::beforeAction($action);
    }

    public function actionRequestForm(int $gid): void
    {
        $this->setBreadcrumbs([new Breadcrumb('Запрос на оформление товара', '')]);
        $requestInstruction = AIGoodsService::getRequestInstruction();
        $this->render('requestForm', compact('requestInstruction'));
    }

    private function setBreadcrumbs(array $breadcrumbs): void
    {
        $this->links[] = new Breadcrumb($this->good->collection->purchase->getDisplayName(), $this->good->collection->purchase->getUrl(), $this->good->collection->purchase->getDisplayName(), SHtml::getImagePathById($this->good->collection->purchase->picid, Pictures::THUMB_150));
        $this->links[] = new Breadcrumb($this->good->collection->getDisplayName(), $this->good->collection->getUrl(), $this->good->collection->getDisplayName(), SHtml::getImagePathById($this->good->collection->picid, Pictures::THUMB_150));
        $this->links[] = new Breadcrumb($this->good->getDisplayName(), $this->good->getUrl(), $this->good->getDisplayName(), SHtml::getImagePathById($this->good->picid, Pictures::THUMB_150));
        foreach ($breadcrumbs as $breadcrumb) {
            $this->links[] = $breadcrumb;
        }
    }

    public function actionSendRequest(int $gid): void
    {
        if ($this->pay && $this->balance < $this->aiServicePrice) {
            $this->setFlash('Не достаточно бонусов на балансе для выполнения операции', self::SF_ERROR);
            $this->redirect('/org/aiDescriptionTool/requestForm/' . $this->good->gid);
        }

        $picId = (int)$this->getRequest()->getPost('picId', '');
        $valueName = $this->getRequest()->getPost('valueName', '');
        $valueBrand = $this->getRequest()->getPost('valueBrand', '');
        $valueCategory = $this->getRequest()->getPost('valueCategory', '');
        $valueCollection = $this->getRequest()->getPost('valueCollection', '');
        $valueDescription = $this->getRequest()->getPost('valueDescription', '');
        $requestInstruction = $this->getRequest()->getPost('requestInstruction', '');
        $measuresExist = (bool)$this->getRequest()->getPost('measuresExist', 0);

        // ограничение на количество символов, ибо это деньги
        $valueName = mb_substr($valueName, 0, 100);
        $valueBrand = mb_substr($valueBrand, 0, 100);
        $valueCategory = mb_substr($valueCategory, 0, 100);
        $valueCollection = mb_substr($valueCollection, 0, 100);
        $valueDescription = mb_substr($valueDescription, 0, 1000);
        $requestInstruction = mb_substr($requestInstruction, 0, 1000);

        if (mb_strlen($requestInstruction) < 10) {
            $this->setFlash('Необходимо указать осмысленный запрос для нейросети', self::SF_ERROR);
            $this->redirect('/org/aiDescriptionTool/requestForm/' . $this->good->gid);
        }

        if ($picId == 0) {
            $this->json(['requestId' => 0, 'mess' => 'Не найдено фото товара']);
        }
        $url = Pictures::model()->getPath($picId, Pictures::ORIGINAL);

        $inputData =
            'На фотографии изображен товар: ' .
            ($valueName ? $valueName . '. ' : '') .
            ($valueBrand ? ' Бренд: ' . $valueBrand . '. ' : '') .
            ($valueCategory ? 'Категория: ' . $valueCategory . '. ' : '') .
            ($valueCollection ? 'Коллекция: ' . $valueCollection . '. ' : '') .
            ($valueDescription ? $valueDescription . '. ' : '');

        $aiGoodsService = new AIGoodsService();
        $aiRequest = AIRequest::newRequest($inputData, $requestInstruction, $url, (int)Yii::app()->user->id, $this->good->gid, $this->good->collection->purchase->uid, !$measuresExist);
        $callId = $aiGoodsService->call($aiRequest, $this->pay);

        $this->json(['requestId' => $callId]);
    }

    public function actionResponseData(int $gid, int $rid): void
    {
        $aiRequest = AIRequest::loadById($rid);
        if (!$aiRequest) {
            $this->json(['error' => 'Запрос получения описания товара не найден']);
        }

        if ($aiRequest->hasResult()) {
            $goodNewDescData = $aiRequest->getResponseContent();
            $isModerator = Yii::app()->user->isAdminOrModerator();

            $this->json([
                'data' => $goodNewDescData,
                'promptTokens' => $isModerator ? $aiRequest->getResponse()->getPromptTokens() : 0,
                'completionTokens' => $isModerator ? $aiRequest->getResponse()->getCompletionTokens() : 0,
            ]);
        } else {
            $this->json(['error' => 'Запрос еще не обработан']);
        }
    }

    public function actionResponseList(int $gid): void
    {
        $requestList = AIRequest::getRequestList($gid);

        $list = array_map(fn ($item) => [
            'created' => $item['created'],
            'rid' => $item['id'],
        ], $requestList);

        $this->json([
            'list' => array_values($list),
        ]);
    }

    public function actionResultAccept(int $gid): void
    {
        $good = Goods::model()->with([
            'collection',
            'collection.purchase',
        ])->findByPk($gid);
        if (!$good) {
            throw new IgnoredHttpException(404);
        }
        $viewContext = new ViewContext($good->collection->purchase);
        if (!$viewContext->canEditPurchase) {
            throw new IgnoredHttpException(401, 'У вас нет доступа к этой странице');
        }

        $name = $this->getRequest()->getPost('responseName', '');
        $weight = (float)$this->getRequest()->getPost('responseWeight', 0);
        $volume = (float)$this->getRequest()->getPost('responseVolume', 0);
        $width = (float)$this->getRequest()->getPost('responseWidth', 0);
        $height = (float)$this->getRequest()->getPost('responseHeight', 0);
        $depth = (float)$this->getRequest()->getPost('responseDepth', 0);
        $note = $this->getRequest()->getPost('responseDescription', '');

        $nameCheck = (bool)$this->getRequest()->getPost('useNewNameCheck', false);
        $sizesCheck = (bool)$this->getRequest()->getPost('useNewMeasuresCheck', false);
        $noteCheck = (bool)$this->getRequest()->getPost('useNewDescriptionCheck', false);

        if ($nameCheck || $sizesCheck || $noteCheck) {
            if ($nameCheck) {
                $good->name = $name;
            }
            if ($noteCheck) {
                $good->description = $note;
            }
            if ($sizesCheck) {
                if ($weight) {
                    $good->getJsonData()->netto = $weight;
                }
                if ($width > 0 && $height > 0 && $depth > 0) {
                    $good->getJsonData()->width = $width;
                    $good->getJsonData()->depth = $height;
                    $good->getJsonData()->height = $depth;
                } elseif ($volume) {
                    $side = $this->getDimensionSideByVolume($volume);
                    $good->getJsonData()->width = $side;
                    $good->getJsonData()->depth = $side;
                    $good->getJsonData()->height = $side;
                }
            }
            $good->save(false);
        }

        $this->redirect($good->getUrl());
    }

    private function getDimensionSideByVolume(float $volume): float
    {
        $ratio = $volume ** (1 / 3);
        $side = ($volume / $ratio) ** (1 / 2) * 10;

        return round($side > 0 ? $side : 0.01, 2);
    }
}
