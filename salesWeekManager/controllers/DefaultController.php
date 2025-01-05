<?php

declare(strict_types=1);

namespace YiiApp\modules\mp\modules\salesWeekManager\controllers;

use CActiveDataProvider;
use CDbCriteria;
use Legacy\RBAC;
use Sp\Yii\Controller;
use Sp\Yii\Exceptions\ValidationException;
use Yii;
use YiiApp\components\SHtml;
use YiiApp\helpers\AjaxResponse;
use YiiApp\helpers\Scenario;
use YiiApp\modules\mp\modules\salesWeekManager\components\SalesWeekAutoHelper;

class DefaultController extends Controller
{
    public const URL_PREFIX = '/sales-week-auto';

    public function filters(): array
    {
        return ['accessControl'];
    }

    public function accessRules(): array
    {
        return [
            [
                'allow',
                'roles' => [
                    RBAC::R_ADMIN,
                    RBAC::R_MODERATOR,
                ],
            ],
            [
                'deny',
                'users' => ['*'],
            ],
        ];
    }

    public function actionIndex(): void
    {
        $order = Yii::app()->request->getParam(SHtml::modelName(\SalesWeekAuto::class) . '_sort');

        $criteria = new CDbCriteria();
        if (!$order) {
            $criteria->order = 't.sort ASC';
        }

        $salesWeekAutoProvider = new CActiveDataProvider(\SalesWeekAuto::model(), [
            'criteria' => $criteria,
            'pagination' => ['pageSize' => 100],
        ]);

        $this->render('index', compact('salesWeekAutoProvider'));
    }

    public function actionEdit(int $id): void
    {
        $salesWeekAuto = \SalesWeekAuto::model()->findByPk($id);

        if (!$salesWeekAuto) {
            $this->notFound('Подборка не найдена');
        }

        $criteria = new CDbCriteria();
        $criteria->order = 't.sort ASC';
        $groupList = \SalesWeekAutoItem::model()->findAllByAttributes(['connected_id' => $id], $criteria);

        $groupOptionsMap = SalesWeekAutoHelper::camelCaseKeys(SalesWeekAutoHelper::getGroupFiltersMap());
        $this->render('edit', compact('salesWeekAuto', 'groupOptionsMap', 'groupList'));
    }

    public function actionNew(): void
    {
        $salesWeekAuto = null;
        $groupOptionsMap = SalesWeekAutoHelper::camelCaseKeys(SalesWeekAutoHelper::getGroupFiltersMap());
        $groupList = [];

        $this->render('edit', compact('salesWeekAuto', 'groupOptionsMap', 'groupList'));
    }

    public function actionSave(): void
    {
        $ajaxResponse = new AjaxResponse();

        // $id==0 - добавление новой подборки
        $id = $this->getRequest()->getParam('id', 0);

        $name = $this->getRequest()->getParam('name', '');
        $link = $this->getRequest()->getParam('link', '');
        $picid = (int) $this->getRequest()->getParam('picid', 0);
        $active = (int) $this->getRequest()->getParam('active', 0);
        $sort = (int) $this->getRequest()->getParam('sort', 0);

        $salesWeekAuto = $id
            ? \SalesWeekAuto::model()->findByPk($id)
            : new \SalesWeekAuto(Scenario::INSERT);

        if (!$salesWeekAuto) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Подборка не найдена';

            $this->json($ajaxResponse);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.name', $name);
        $criteria->compare('t.id', '<>' . $id);
        $sameNameRows = \SalesWeekAuto::model()->findAll($criteria);
        if (count($sameNameRows)) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Подборка с таким именем уже существует, придумайте другое';

            $this->json($ajaxResponse);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.link', $link);
        $criteria->compare('t.id', '<>' . $id);
        $sameLinkRows = \SalesWeekAuto::model()->findAll($criteria);
        if (count($sameLinkRows)) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Подборка с такой ссылкой уже существует, измените ссылку';

            $this->json($ajaxResponse);
        }

        $salesWeekAuto->name = $name;
        $salesWeekAuto->link = $link;
        $salesWeekAuto->picid = $picid;
        $salesWeekAuto->active = $active;
        $salesWeekAuto->sort = $sort;

        if (!$id && !$sort) {
            $sql = 'select MAX(sort) from ' . $salesWeekAuto::TABLE_NAME;
            $maxSort = Yii::app()->db->commandBuilder->createSqlCommand($sql)->queryScalar();
            $salesWeekAuto->sort = $maxSort + 1;
        }

        $salesWeekAuto->save();
        $ajaxResponse->data = ['id' => $salesWeekAuto->id];

        $this->json($ajaxResponse);
    }

    public function actionDelete(): void
    {
        $id = (int) $this->getRequest()->getParam('id', 0);

        if (!$id) {
            $this->notFound('Подборка не найдена');
        }

        $salesWeekAuto = \SalesWeekAuto::model()->findByPk($id);

        if (!$salesWeekAuto) {
            $this->notFound('Подборка не найдена');
        }
        $salesWeekAuto->delete();
        $this->redirect(['index']);
    }

    public function actionSaveGroup(): void
    {
        $ajaxResponse = new AjaxResponse();

        // $id==0 - добавление новой подборки
        $id = $this->getRequest()->getParam('id', 0);
        $name = $this->getRequest()->getParam('name', '');
        $link = $this->getRequest()->getParam('link', '');
        $note = $this->getRequest()->getParam('note', 0);
        $active = (int) (bool)$this->getRequest()->getParam('active', 0);
        $connectedId = (int) $this->getRequest()->getParam('connectedId', 0);
        $filter = $this->getRequest()->getParam('filter', '') ?: [];

        try {
            $salesWeekAutoItem = $id
                ? \SalesWeekAutoItem::model()->findByPk($id)
                : new \SalesWeekAutoItem(Scenario::INSERT);

            if (!$salesWeekAutoItem) {
                $ajaxResponse->result = false;
                $ajaxResponse->errors[] = 'Группа подборки не найдена';

                $this->json($ajaxResponse);
            }

            $salesWeekAutoItem->name = $name;
            $salesWeekAutoItem->link = $link;
            $salesWeekAutoItem->note = $note;
            $salesWeekAutoItem->active = $active;

            if (!$id) {
                $salesWeekAutoItem->created = (new \DateTime())->format('Y-m-d H:i:s');
                $salesWeekAutoItem->connected_id = $connectedId;
                $salesWeekAutoItem->sort = 1;
            }

            $filterList = SalesWeekAutoHelper::clearOutRequest($filter);
            $salesWeekAutoItem->filterList = $filterList;

            $salesWeekAutoItem->save(false);
            $ajaxResponse->data = ['id' => $salesWeekAutoItem->id];
        } catch (ValidationException $exception) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = $exception->getMessage();
        }

        $this->json($ajaxResponse);
    }

    public function actionDeleteGroup(): void
    {
        $ajaxResponse = new AjaxResponse();

        $id = (int)$this->getRequest()->getParam('id', 0);

        if (!$id) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Группа подборки не найдена';
            $this->json($ajaxResponse);
        }
        try {
            $salesWeekAutoItem = \SalesWeekAutoItem::model()->findByPk($id);

            if (!$salesWeekAutoItem) {
                $ajaxResponse->result = false;
                $ajaxResponse->errors[] = 'Группа подборки не найдена';
                $this->json($ajaxResponse);
            }
            $name = $salesWeekAutoItem->name;
            $salesWeekAutoItem->delete();
            $ajaxResponse->data = ['id' => $id, 'name' => $name];
        } catch (ValidationException $exception) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = $exception->getMessage();
        }

        $this->json($ajaxResponse);
    }

    public function actionUpdateActive(): void
    {
        $ajaxResponse = new AjaxResponse();

        $id = $this->getRequest()->getParam('id', 0);
        $value = (bool)$this->getRequest()->getParam('value', 0);

        if (!$id) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Подборка не найдена';
        }
        $salesWeekAuto = \SalesWeekAuto::model()->findByPk($id);

        if (!$salesWeekAuto) {
            $ajaxResponse->result = false;
            $ajaxResponse->errors[] = 'Подборка не найдена';
        }

        $salesWeekAuto->active = (int) $value;
        $salesWeekAuto->save();

        $this->json($ajaxResponse);
    }
}
