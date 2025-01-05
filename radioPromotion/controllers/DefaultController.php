<?php

namespace YiiApp\modules\mp\modules\radioPromotion\controllers;

use CActiveDataProvider;
use CDbCriteria;
use Goods;
use Legacy\RBAC;
use Sp\Yii\Controller;
use YiiApp\helpers\AjaxResponse;
use YiiApp\helpers\Scenario;
use YiiApp\modules\mp\modules\radioPromotion\models\RadioPromotion;
use YiiApp\modules\mp\modules\radioPromotion\models\RadioPromotionGood;

class DefaultController extends Controller
{
    public function filters(): array
    {
        return ['accessControl'];
    }

    public function accessRules(): array
    {
        return [
            [
                'allow',
                'roles' => [RBAC::R_ADMIN, RBAC::R_MODERATOR, RBAC::R_EDITOR, RBAC::R_ANALYTIC_1],
            ],
            [
                'deny',
                'users' => ['*'],
            ],
        ];
    }

    public function actionIndex(): void
    {
        $criteria = new CDbCriteria();

        $radioPromotionProvider = new CActiveDataProvider(RadioPromotion::model()->with('goods'), [
            'criteria' => $criteria,
            'pagination' => false,
        ]);

        $this->render('index', compact('radioPromotionProvider'));
    }

    public function actionEdit(int $id): void
    {
        $radioPromotion = RadioPromotion::model()->findByPk($id);

        if (!$radioPromotion) {
            $this->notFound('Акция не найдена');
        }

        $goods = $radioPromotion->getSortedGoods();

        $this->render('edit', compact('radioPromotion', 'goods'));
    }

    public function actionNew(): void
    {
        $radioPromotion = null;
        $goods = [];
        $this->render('edit', compact('radioPromotion', 'goods'));
    }

    public function actionSave(): void
    {
        $ajaxResponse = new AjaxResponse();

        // $id==0 - добавление
        $id = $this->getRequest()->getParam('id', 0);

        $name = $this->getRequest()->getParam('name', '');
        $link = $this->getRequest()->getParam('link', '');
        $active = (int) $this->getRequest()->getParam('active', 0);
        $sortMethod = $this->getRequest()->getParam('sortMethod', '');

        $radioPromotion = $id
            ? RadioPromotion::model()->findByPk($id)
            : new RadioPromotion(Scenario::INSERT);

        if (!$radioPromotion) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Акция не найдена';

            $this->json($ajaxResponse);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.name', $name);
        $criteria->compare('t.id', '<>' . $id);
        $sameNameRows = RadioPromotion::model()->findAll($criteria);
        if (count($sameNameRows)) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Акция с таким именем уже существует, придумайте другое';

            $this->json($ajaxResponse);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.link', $link);
        $criteria->compare('t.id', '<>' . $id);
        $sameLinkRows = RadioPromotion::model()->findAll($criteria);
        if (count($sameLinkRows)) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Акция с такой ссылкой уже существует, измените ссылку';

            $this->json($ajaxResponse);
        }

        $radioPromotion->name = $name;
        $radioPromotion->link = $link;
        $radioPromotion->active = $active;
        $radioPromotion->sortMethod = $sortMethod;

        $radioPromotion->save();
        $ajaxResponse->data = ['id' => $radioPromotion->id];

        $this->json($ajaxResponse);
    }

    public function actionDelete(): void
    {
        $id = (int) $this->getRequest()->getParam('id', 0);

        if (!$id) {
            $this->notFound('Акция не найдена');
        }

        $radioPromotion = RadioPromotion::model()->findByPk($id);

        if (!$radioPromotion) {
            $this->notFound('Акция не найдена');
        }

        $criteria = new CDbCriteria();
        $criteria->compare('connected_id', $id);
        RadioPromotionGood::model()->deleteAll($criteria);
        $radioPromotion->delete();

        $this->redirect(['index']);
    }

    public function actionUpdateActive(): void
    {
        $ajaxResponse = new AjaxResponse();

        $id = $this->getRequest()->getParam('id', 0);
        $value = (bool)$this->getRequest()->getParam('value', 0);

        if (!$id) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Акция не найдена';
        } else {
            $radioPromotion = RadioPromotion::model()->findByPk($id);

            if (!$radioPromotion) {
                $ajaxResponse->result = false;
                $ajaxResponse->errors[] = 'Акция не найдена';
            } else {
                $radioPromotion->active = (int)$value;
                $radioPromotion->save();
                $ajaxResponse->data = ['active' => $value];
            }
        }

        $this->json($ajaxResponse);
    }

    public function actionAddToPromotion(): void
    {
        $ajaxResponse = new AjaxResponse();

        $gid = $this->getRequest()->getParam('gid', 0);
        $promotionId = $this->getRequest()->getParam('promotionId', 0);

        if (!$gid || !$promotionId) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Не достаточно данных';
        } else {
            $radioPromotion = RadioPromotion::model()->findByPk($promotionId);
            $good = Goods::model()->findByPk($gid);
            if (!$radioPromotion || !$radioPromotion->active) {
                $ajaxResponse->result = false;
                $ajaxResponse->errors[] = 'Акция не найдена или не активна';
            } elseif (!$good) {
                $ajaxResponse->result = false;
                $ajaxResponse->errors[] = 'Товар не найден';
            } else {
                $radioPromotionGood = RadioPromotionGood::model()->findByAttributes(['connected_id' => $promotionId, 'gid' => $gid]);
                if ($radioPromotionGood) {
                    $ajaxResponse->messages[] = 'Товар ' . $good->getDisplayName() . ' уже добавлен в акцию ' . $radioPromotion->name;
                } else {
                    $radioPromotionGood = new RadioPromotionGood(Scenario::INSERT);
                    $radioPromotionGood->connected_id = $promotionId;
                    $radioPromotionGood->gid = $gid;
                    $radioPromotionGood->uid = $this->getUser()->id;
                    $radioPromotionGood->save();
                    $ajaxResponse->data = ['id' => $radioPromotionGood->id];
                    $ajaxResponse->messages[] = 'Товар ' . $good->getDisplayName() . ' добавлен в акцию ' . $radioPromotion->name;
                }
            }
        }

        $this->json($ajaxResponse);
    }

    public function actionRemoveFromPromotion(): void
    {
        $ajaxResponse = new AjaxResponse();

        $gid = $this->getRequest()->getParam('gid', 0);
        $promotionId = $this->getRequest()->getParam('promotionId', 0);

        if (!$gid || !$promotionId) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Не достаточно данных';
        } else {
            $radioPromotion = RadioPromotion::model()->findByPk($promotionId);
            $good = Goods::model()->findByPk($gid);
            if (!$radioPromotion) {
                $ajaxResponse->result = false;
                $ajaxResponse->errors[] = 'Акция не найдена или не активна';
            } elseif (!$good) {
                $ajaxResponse->result = false;
                $ajaxResponse->errors[] = 'Товар не найден';
            } else {
                $radioPromotionGood = RadioPromotionGood::model()->findByAttributes(['connected_id' => $promotionId, 'gid' => $gid]);
                if ($radioPromotionGood) {
                    $radioPromotionGood->delete();
                    $ajaxResponse->messages[] = 'Товар ' . $good->getDisplayName() . ' Удален из акции ' . $radioPromotion->name;
                } else {
                    $ajaxResponse->messages[] = 'Товар ' . $good->getDisplayName() . ' не учавствует в акции ' . $radioPromotion->name;
                }
            }
        }

        $this->json($ajaxResponse);
    }
}
