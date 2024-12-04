<?php

namespace aesis\user\controllers\resource;

use aesis\rest\controllers\ControllerCRUDAbstract;
use aesis\traits\helpers\InternalChecker;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use yii\filters\VerbFilter;

class AuthKeyController extends ControllerCRUDAbstract
{
    use ModuleTrait;
    use EventTrait;

    const EVENT_AFTER_AUTH_KEY_DELETE_ALL = 'afterAuthKeyDeleteAll';
    const EVENT_AFTER_AUTH_KEY_DELETE = 'afterAuthKeyDelete';

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete-all' => ['post', 'delete'],
                'current' => ['get']
            ],
        ];

        return $behaviors;
    }

    public function checkAccess($method, $id): bool
    {
        return !in_array($method, ['create', 'update']);
    }

    public function actionCurrent()
    {
        if (!InternalChecker::isInternalApi())
            return $this->makeResponse();
        return $this->makeResponse($this->modelClass::getCurrentKey());
    }

    public function actionDeleteAll()
    {
        if (InternalChecker::isInternalApi()) {
            $current = $this->modelClass::getCurrentKey();
            $result = $this->makeResponse('', '', $this->modelClass::deleteAll(['!=', 'key', $current->key]) ? 200 : 500);
        } else {
            $result = $this->makeResponse('', '', $this->modelClass::deleteAll() ? 200 : 500);
        }

        if ($result['status']) {
            $event = $this->getKeyEvent($this->user, 'all');
            $this->trigger(self::EVENT_AFTER_AUTH_KEY_DELETE_ALL, $event);
        }

        return $result;
    }

    function RESTdelete($id)
    {
        $key = $this->modelClass::findOne($id);
        $result = parent::RESTdelete($id);

        if ($result['status']) {
            $event = $this->getKeyEvent($this->user, $key);
            $this->trigger(self::EVENT_AFTER_AUTH_KEY_DELETE, $event);
        }

        return $result;
    }


    function _getModelClass(): string
    {
        return $this->getModule()->modelMap['AuthKeyResource'];
    }
}